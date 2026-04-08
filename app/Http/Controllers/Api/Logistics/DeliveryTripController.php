<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use App\Models\Logistics\DeliveryTrip;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderDelivery;
use App\Models\Sales\SalesOrderDeliveryLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryTripController extends Controller
{
    private const TRIP_STATUSES = ['planned', 'in_transit', 'completed', 'cancelled'];

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryTrip::query()
            ->with([
                'vehicle:id,vehicle_name,plate_number,vehicle_type,capacity_kg',
                'driver:id,fname,lname,email',
            ])
            ->withCount(['ecommerceDeliveries', 'salesDeliveries']);

        $this->applyTenantScope($request, $query);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $trips = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $trips]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = DeliveryTrip::query()
            ->with([
                'vehicle:id,vehicle_name,plate_number,vehicle_type,capacity_kg',
                'driver:id,fname,lname,email',
                'ecommerceDeliveries.order:id,order_number,shipping_name,shipping_phone,shipping_address,total_amount,status,customer_latitude,customer_longitude',
                'ecommerceDeliveries.order.items:id,order_id,product_id,quantity',
                'ecommerceDeliveries.order.items.product:id,weight_kg',
                'salesDeliveries.order:id,order_number,customer_name,customer_phone,delivery_address,total_amount,status,delivery_latitude,delivery_longitude',
                'salesDeliveries.order.items:id,order_id,product_id,quantity',
                'salesDeliveries.order.items.product:id,weight_kg',
            ]);

        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);

        return response()->json(['success' => true, 'data' => $trip]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:ecommerce_delivery_vehicles,id',
            'driver_user_id' => 'required|exists:users,id',
            'scheduled_departure_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $storeId = $this->resolveStoreId($request);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $trip = DeliveryTrip::query()->create([
            'store_id' => $storeId,
            'vehicle_id' => (int) $validated['vehicle_id'],
            'driver_user_id' => (int) $validated['driver_user_id'],
            'status' => 'planned',
            'scheduled_departure_at' => $validated['scheduled_departure_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Trip created.', 'data' => $trip], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::TRIP_STATUSES)],
        ]);

        $query = DeliveryTrip::query();
        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);

        $trip->status = (string) $validated['status'];
        $trip->updated_by = $request->user()->id;
        $trip->save();

        return response()->json(['success' => true, 'message' => 'Trip status updated.', 'data' => $trip]);
    }

    public function addOrders(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales'])],
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|min:1',
        ]);

        $query = DeliveryTrip::query()->with(['vehicle', 'driver']);
        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);

        $driver = $this->resolveDriver($trip->driver_user_id);
        $courierName = $driver ? trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')) : null;
        $courierContact = $this->resolveDriverContact($driver) ?: null;

        $added = 0;
        $skipped = 0;
        $overCapacity = 0;
        $capacityKg = (float) ($trip->vehicle?->capacity_kg ?? 0);
        $currentWeight = $this->currentTripWeight($trip);

        DB::transaction(function () use ($validated, $trip, $request, $courierName, $courierContact, &$added, &$skipped, &$overCapacity, $capacityKg, &$currentWeight) {
            if ($validated['source_type'] === 'ecommerce') {
                $orders = EcommerceOrder::query()
                    ->with(['items.product:id,weight_kg'])
                    ->whereIn('id', $validated['order_ids'])
                    ->where('store_id', $trip->store_id)
                    ->get();

                foreach ($orders as $order) {
                    if ((string) $order->status !== 'ready_for_dispatch') {
                        $skipped++;
                        continue;
                    }

                    $orderWeight = $this->orderWeight($order->items ?? []);
                    if ($capacityKg > 0 && ($currentWeight + $orderWeight) > $capacityKg) {
                        $overCapacity++;
                        continue;
                    }

                    $delivery = EcommerceOrderDelivery::query()->firstOrNew(['order_id' => $order->id], [
                        'store_id' => $order->store_id,
                        'created_by' => $request->user()->id,
                    ]);

                    if ($delivery->trip_id && (int) $delivery->trip_id === (int) $trip->id) {
                        continue;
                    }

                    $delivery->trip_id = $trip->id;
                    $delivery->vehicle_id = $trip->vehicle_id;
                    $delivery->driver_user_id = $trip->driver_user_id;
                    $delivery->courier_name = $courierName;
                    $delivery->courier_contact = $courierContact;
                    $delivery->tracking_number = $delivery->tracking_number ?: $this->nextTrackingNumber();
                    $delivery->status = $delivery->status ?: 'assigned';
                    $delivery->updated_by = $request->user()->id;
                    $delivery->save();

                    EcommerceDeliveryLog::query()->create([
                        'delivery_id' => $delivery->id,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'event_type' => 'driver_assigned',
                        'status_to' => $delivery->status,
                        'message' => "Order assigned to trip #{$trip->id}.",
                        'created_by' => $request->user()->id,
                    ]);

                    $added++;
                    $currentWeight += $orderWeight;
                }
            } else {
                $orders = SalesOrder::query()
                    ->with(['items.product:id,weight_kg'])
                    ->whereIn('id', $validated['order_ids'])
                    ->where('store_id', $trip->store_id)
                    ->where('delivery_required', true)
                    ->get();

                foreach ($orders as $order) {
                    $orderWeight = $this->orderWeight($order->items ?? []);
                    if ($capacityKg > 0 && ($currentWeight + $orderWeight) > $capacityKg) {
                        $overCapacity++;
                        continue;
                    }

                    $delivery = SalesOrderDelivery::query()->firstOrNew(['sales_order_id' => $order->id], [
                        'store_id' => $order->store_id,
                        'branch_id' => $order->branch_id,
                        'created_by' => $request->user()->id,
                    ]);

                    if ($delivery->trip_id && (int) $delivery->trip_id === (int) $trip->id) {
                        continue;
                    }

                    $delivery->trip_id = $trip->id;
                    $delivery->driver_user_id = $trip->driver_user_id;
                    $delivery->courier_name = $courierName;
                    $delivery->courier_contact = $courierContact;
                    $delivery->tracking_number = $delivery->tracking_number ?: $this->nextTrackingNumber();
                    $delivery->status = $delivery->status ?: 'assigned';
                    $delivery->updated_by = $request->user()->id;
                    $delivery->save();

                    SalesOrderDeliveryLog::query()->create([
                        'delivery_id' => $delivery->id,
                        'sales_order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'event_type' => 'trip_assigned',
                        'status_to' => $delivery->status,
                        'message' => "Order assigned to trip #{$trip->id}.",
                        'created_by' => $request->user()->id,
                    ]);

                    $added++;
                    $currentWeight += $orderWeight;
                }
            }
        });

        if ($added === 0 && $overCapacity > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Trip capacity exceeded. Reduce the selection or choose a larger vehicle.',
                'data' => [
                    'added' => $added,
                    'skipped' => $skipped,
                    'over_capacity' => $overCapacity,
                ],
            ], 422);
        }

        $message = "Orders added to trip ({$added}).";
        if ($overCapacity > 0) {
            $message .= " {$overCapacity} skipped due to capacity.";
        } elseif ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'added' => $added,
                'skipped' => $skipped,
                'over_capacity' => $overCapacity,
            ],
        ]);
    }

    private function currentTripWeight(DeliveryTrip $trip): float
    {
        $weight = 0.0;

        $trip->loadMissing([
            'ecommerceDeliveries.order.items.product:id,weight_kg',
            'salesDeliveries.order.items.product:id,weight_kg',
        ]);

        foreach ($trip->ecommerceDeliveries as $delivery) {
            $weight += $this->orderWeight($delivery->order?->items ?? []);
        }

        foreach ($trip->salesDeliveries as $delivery) {
            $weight += $this->orderWeight($delivery->order?->items ?? []);
        }

        return $weight;
    }

    private function orderWeight($items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $w = (float) ($item->product?->weight_kg ?? 0);
            $total += $w * (int) ($item->quantity ?? 0);
        }
        return $total;
    }

    public function removeOrders(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales'])],
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|min:1',
        ]);

        $query = DeliveryTrip::query();
        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);

        $removed = 0;

        DB::transaction(function () use ($validated, $trip, $request, &$removed) {
            if ($validated['source_type'] === 'ecommerce') {
                $deliveries = EcommerceOrderDelivery::query()
                    ->where('trip_id', $trip->id)
                    ->whereIn('order_id', $validated['order_ids'])
                    ->get();

                foreach ($deliveries as $delivery) {
                    $order = EcommerceOrder::query()->find($delivery->order_id);
                    $deliveryId = $delivery->id;
                    $orderId = $delivery->order_id;
                    $storeId = $delivery->store_id;
                    $status = $delivery->status;

                    EcommerceDeliveryLog::query()->create([
                        'delivery_id' => $deliveryId,
                        'order_id' => $orderId,
                        'store_id' => $storeId,
                        'event_type' => 'note',
                        'status_to' => $status,
                        'message' => "Order removed from trip #{$trip->id}.",
                        'created_by' => $request->user()->id,
                    ]);

                    $delivery->delete();

                    if ($order && in_array((string) $order->status, ['assigned', 'ready_for_dispatch', 'pending', 'pending_payment', 'confirmed'], true)) {
                        $order->status = 'ready_for_dispatch';
                        $order->save();
                    }
                    $removed++;
                }
            } else {
                $deliveries = SalesOrderDelivery::query()
                    ->where('trip_id', $trip->id)
                    ->whereIn('sales_order_id', $validated['order_ids'])
                    ->get();

                foreach ($deliveries as $delivery) {
                    $deliveryId = $delivery->id;
                    $salesOrderId = $delivery->sales_order_id;
                    $storeId = $delivery->store_id;
                    $status = $delivery->status;

                    SalesOrderDeliveryLog::query()->create([
                        'delivery_id' => $deliveryId,
                        'sales_order_id' => $salesOrderId,
                        'store_id' => $storeId,
                        'event_type' => 'trip_removed',
                        'status_to' => $status,
                        'message' => "Order removed from trip #{$trip->id}.",
                        'created_by' => $request->user()->id,
                    ]);

                    $delivery->delete();
                    $removed++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Orders removed from trip ({$removed}).",
        ]);
    }

    private function applyTenantScope(Request $request, $query): void
    {
        $user = $request->user();
        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
            return;
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }
    }

    private function resolveStoreId(Request $request): ?int
    {
        $user = $request->user();
        if ($user->hasRole('super_admin') && $request->filled('store_id')) {
            return (int) $request->input('store_id');
        }
        return $user->store_id ? (int) $user->store_id : null;
    }

    private function resolveDriver(?int $driverUserId): ?User
    {
        if (!$driverUserId) {
            return null;
        }

        return User::query()->with('employee:id,user_id,phone')->find($driverUserId);
    }

    private function resolveDriverContact(?User $driver): ?string
    {
        if (!$driver) {
            return null;
        }

        return $driver->employee?->phone ?: $driver->phone_number;
    }

    private function nextTrackingNumber(): string
    {
        $prefix = 'LGS-' . now()->format('Ymd') . '-';

        $lastEcom = EcommerceOrderDelivery::query()
            ->where('tracking_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('tracking_number');

        $lastSales = SalesOrderDelivery::query()
            ->where('tracking_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('tracking_number');

        $maxSequence = 0;
        foreach ([$lastEcom, $lastSales] as $value) {
            if ($value && preg_match('/(\d+)$/', (string) $value, $matches)) {
                $maxSequence = max($maxSequence, (int) $matches[1]);
            }
        }

        return $prefix . str_pad((string) ($maxSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
