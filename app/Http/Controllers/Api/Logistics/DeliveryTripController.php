<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use App\Models\Logistics\DeliveryTrip;
use App\Models\Logistics\ReturnPickup;
use App\Models\Logistics\ReturnPickupLog;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderDelivery;
use App\Models\Sales\SalesOrderDeliveryLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryTripController extends Controller
{
    private const TRIP_STATUSES = ['planned', 'in_transit', 'out_for_delivery', 'completed', 'cancelled'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizeTripViewer($request);
        $query = DeliveryTrip::query()
            ->with([
                'vehicle:id,vehicle_name,plate_number,vehicle_type,capacity_kg,max_orders_per_trip',
                'driver:id,fname,lname,email',
            ])
            ->withCount(['ecommerceDeliveries', 'salesDeliveries', 'returnPickups']);

        $this->applyTenantScope($request, $query);
        if ($this->isDriverOnly($request)) {
            $query->where('driver_user_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $trips = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $trips]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeTripViewer($request);
        $query = DeliveryTrip::query()
            ->with([
                'vehicle:id,vehicle_name,plate_number,vehicle_type,capacity_kg,max_orders_per_trip',
                'driver:id,fname,lname,email',
                'ecommerceDeliveries.order:id,order_number,shipping_name,shipping_phone,shipping_address,total_amount,status,customer_latitude,customer_longitude',
                'ecommerceDeliveries.order.items:id,order_id,product_id,quantity',
                'ecommerceDeliveries.order.items.product:id,weight_kg',
                'salesDeliveries.order:id,order_number,customer_name,customer_phone,delivery_address,total_amount,status,delivery_latitude,delivery_longitude',
                'salesDeliveries.order.items:id,order_id,product_id,quantity',
                'salesDeliveries.order.items.product:id,weight_kg',
                'returnPickups.returnRequest:id,return_number,order_id,order_item_id,requested_quantity',
                'returnPickups.returnRequest.order:id,order_number,shipping_name,shipping_address',
                'returnPickups.returnRequest.orderItem:id,product_id,product_name,sku',
                'returnPickups.returnRequest.orderItem.product:id,weight_kg',
            ]);

        $this->applyTenantScope($request, $query);
        if ($this->isDriverOnly($request)) {
            $query->where('driver_user_id', $request->user()->id);
        }
        $trip = $query->findOrFail($id);

        return response()->json(['success' => true, 'data' => $trip]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
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
        $this->authorizeTripViewer($request);
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::TRIP_STATUSES)],
        ]);

        $query = DeliveryTrip::query();
        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);

        if ($this->isDriverOnly($request)) {
            abort_unless((int) $trip->driver_user_id === (int) $request->user()->id
                && $validated['status'] === 'completed', 403);
        } else {
            abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
        }

        if ($validated['status'] === 'out_for_delivery' && $trip->status !== 'planned') {
            return response()->json(['success' => false, 'message' => 'Only planned trips can be dispatched.'], 422);
        }

        if ($validated['status'] === 'out_for_delivery' && !$trip->ecommerceDeliveries()->exists()
            && !$trip->salesDeliveries()->exists() && !$trip->returnPickups()->exists()) {
            return response()->json(['success' => false, 'message' => 'Add an order before dispatching this trip.'], 422);
        }

        if ((string) $validated['status'] === 'completed') {
            $totalDeliveries = (int) $trip->ecommerceDeliveries()->count()
                + (int) $trip->salesDeliveries()->count()
                + (int) $trip->returnPickups()->count();
            $undelivered = (int) $trip->ecommerceDeliveries()
                ->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['', 'delivered'])
                ->count()
                + (int) $trip->salesDeliveries()
                    ->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['', 'delivered'])
                    ->count()
                + (int) $trip->returnPickups()
                    ->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['', 'delivered'])
                    ->count();

            if ($totalDeliveries === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot complete an empty trip. Add and deliver at least one order first.',
                ], 422);
            }

            if ($undelivered > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot complete this trip. {$undelivered} delivery order(s) are not yet delivered.",
                    'data' => ['undelivered_count' => $undelivered],
                ], 422);
            }
        }

        DB::transaction(function () use ($trip, $validated, $request) {
            $trip->status = (string) $validated['status'];
            $trip->updated_by = $request->user()->id;
            $trip->save();

            if ((string) $validated['status'] === 'out_for_delivery') {
                $now = now();
                $trip->ecommerceDeliveries()
                    ->whereIn('status', ['pending', 'ready_for_dispatch', 'assigned'])
                    ->update(['status' => 'out_for_delivery', 'dispatched_at' => $now, 'out_for_delivery_at' => $now, 'updated_by' => $request->user()->id, 'updated_at' => $now]);
                $trip->salesDeliveries()
                    ->whereIn('status', ['pending', 'ready_for_dispatch', 'assigned'])
                    ->update(['status' => 'out_for_delivery', 'dispatched_at' => $now, 'out_for_delivery_at' => $now, 'updated_by' => $request->user()->id, 'updated_at' => $now]);
                $trip->returnPickups()
                    ->whereIn('status', ['ready_for_dispatch', 'scheduled'])
                    ->update(['status' => 'out_for_delivery', 'out_for_delivery_at' => $now, 'driver_user_id' => $trip->driver_user_id, 'vehicle_id' => $trip->vehicle_id, 'updated_by' => $request->user()->id, 'updated_at' => $now]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Trip status updated.', 'data' => $trip]);
    }

    public function suggestions(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales', 'return_pickup'])],
        ]);

        $query = DeliveryTrip::query()->with('vehicle');
        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);
        $currentWeight = $this->currentTripWeight($trip);
        $capacityKg = (float) ($trip->vehicle?->capacity_kg ?? 0);
        $maxStops = (int) ($trip->vehicle?->max_orders_per_trip ?? 0);
        $currentStops = (int) $trip->ecommerceDeliveries()->count() + (int) $trip->salesDeliveries()->count();
        $remainingStops = $maxStops > 0 ? max(0, $maxStops - $currentStops) : PHP_INT_MAX;
        $assignedReturnWeight = $trip->returnPickups()->whereNotIn('status', ['delivered', 'cancelled'])
            ->with('returnRequest.orderItem.product:id,weight_kg')->get()
            ->sum(fn (ReturnPickup $pickup) => $this->returnPickupWeight($pickup));
        $remainingWeight = $capacityKg > 0
            ? max(0, $capacityKg - ($validated['source_type'] === 'return_pickup' ? $assignedReturnWeight : $currentWeight))
            : PHP_FLOAT_MAX;

        $rows = $validated['source_type'] === 'ecommerce'
            ? $this->ecommerceSuggestionRows($trip)
            : ($validated['source_type'] === 'sales' ? $this->salesSuggestionRows($trip) : $this->returnPickupSuggestionRows($trip));

        $currentAreas = collect()
            ->merge($trip->ecommerceDeliveries()->with('order:id,shipping_address')->get()->pluck('order.shipping_address'))
            ->merge($trip->salesDeliveries()->with('order:id,delivery_address')->get()->pluck('order.delivery_address'))
            ->merge($trip->returnPickups()->pluck('pickup_address'))
            ->filter()->map(fn ($address) => $this->areaKey((string) $address));
        $targetArea = $currentAreas->countBy()->sortDesc()->keys()->first();

        $groups = collect($rows)->groupBy('area_key');
        if (!$targetArea || !$groups->has($targetArea)) {
            $targetArea = $groups->sortByDesc(fn ($group) => $group->count())->keys()->first();
        }

        $selected = [];
        $selectedWeight = 0.0;
        foreach (($groups->get($targetArea, collect()))->sortBy('distance_km') as $row) {
            if (count($selected) >= $remainingStops) break;
            $weight = (float) ($row['weight_kg'] ?? 0);
            if (($selectedWeight + $weight) > $remainingWeight) continue;
            unset($row['area_key']);
            $row['stop_sequence'] = count($selected) + 1;
            $selected[] = $row;
            $selectedWeight += $weight;
        }

        return response()->json(['success' => true, 'data' => [
            'orders' => $selected,
            'area' => $targetArea,
            'selected_weight_kg' => round($selectedWeight, 2),
            'remaining_capacity_kg' => $capacityKg > 0 ? round(max(0, $remainingWeight - $selectedWeight), 2) : null,
        ]]);
    }

    private function ecommerceSuggestionRows(DeliveryTrip $trip): array
    {
        return EcommerceOrder::query()->with(['delivery', 'assignedBranch:id,latitude,longitude', 'items.product:id,weight_kg'])
            ->where('store_id', $trip->store_id)->where('status', 'ready_for_dispatch')
            ->where(fn ($q) => $q->whereDoesntHave('delivery')->orWhereHas('delivery', fn ($d) => $d
                ->whereNull('trip_id')->whereIn('status', ['pending', 'ready_for_dispatch'])))
            ->get()->map(function ($order) {
                $distance = $this->haversineDistance($order->assignedBranch?->latitude, $order->assignedBranch?->longitude, $order->customer_latitude, $order->customer_longitude);
                return ['id' => 'ecommerce-'.$order->id, 'source_type' => 'ecommerce', 'order_id' => $order->id,
                    'order_number' => $order->order_number, 'customer_name' => $order->shipping_name,
                    'delivery_address' => $order->shipping_address, 'order_status' => $order->status,
                    'distance_km' => $distance, 'weight_kg' => $this->orderWeight($order->items ?? []),
                    'area_key' => $this->areaKey((string) $order->shipping_address)];
            })->all();
    }

    private function salesSuggestionRows(DeliveryTrip $trip): array
    {
        return SalesOrder::query()->with(['delivery', 'branch:id,latitude,longitude', 'items.product:id,weight_kg'])
            ->where('store_id', $trip->store_id)->where('delivery_required', true)
            ->where('status', 'ready_for_dispatch')
            ->where(fn ($q) => $q->whereDoesntHave('delivery')->orWhereHas('delivery', fn ($d) => $d
                ->whereNull('trip_id')->whereIn('status', ['pending', 'ready_for_dispatch'])))
            ->get()->map(function ($order) {
                $distance = $this->haversineDistance($order->branch?->latitude, $order->branch?->longitude, $order->delivery_latitude, $order->delivery_longitude);
                return ['id' => 'sales-'.$order->id, 'source_type' => 'sales', 'order_id' => $order->id,
                    'order_number' => $order->order_number, 'customer_name' => $order->customer_name,
                    'delivery_address' => $order->delivery_address, 'order_status' => $order->status,
                    'distance_km' => $distance, 'weight_kg' => $this->orderWeight($order->items ?? []),
                    'area_key' => $this->areaKey((string) $order->delivery_address)];
            })->all();
    }

    private function returnPickupSuggestionRows(DeliveryTrip $trip): array
    {
        return ReturnPickup::query()->with(['returnRequest.orderItem.product:id,weight_kg'])
            ->where('store_id', $trip->store_id)->whereNull('trip_id')
            ->whereIn('status', ['ready_for_dispatch', 'scheduled'])
            ->get()->map(function (ReturnPickup $pickup) {
                return ['id' => 'return-'.$pickup->id, 'source_type' => 'return_pickup', 'order_id' => $pickup->id,
                    'order_number' => $pickup->returnRequest?->return_number ?: 'Return #'.$pickup->id,
                    'customer_name' => $pickup->pickup_name, 'delivery_address' => $pickup->pickup_address,
                    'order_status' => $pickup->status, 'distance_km' => (float) ($pickup->distance_km ?? 0),
                    'weight_kg' => $this->returnPickupWeight($pickup),
                    'area_key' => $this->areaKey((string) $pickup->pickup_address)];
            })->all();
    }

    private function areaKey(string $address): string
    {
        $parts = array_values(array_filter(array_map(fn ($part) => trim(mb_strtolower($part)), explode(',', $address))));
        return implode(', ', array_slice($parts, 0, 2)) ?: 'unknown area';
    }

    private function haversineDistance($originLat, $originLng, $destinationLat, $destinationLng): float
    {
        if (!is_numeric($originLat) || !is_numeric($originLng) || !is_numeric($destinationLat) || !is_numeric($destinationLng)) return 0.0;
        $latDelta = deg2rad((float) $destinationLat - (float) $originLat);
        $lngDelta = deg2rad((float) $destinationLng - (float) $originLng);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad((float) $originLat)) * cos(deg2rad((float) $destinationLat)) * sin($lngDelta / 2) ** 2;
        return round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    public function addOrders(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales', 'return_pickup'])],
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|min:1',
        ]);

        $query = DeliveryTrip::query()->with(['vehicle', 'driver']);
        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);

        if ($trip->status !== 'planned') {
            return response()->json(['success' => false, 'message' => 'Only planned trips can receive new orders.'], 422);
        }

        $driver = $this->resolveDriver($trip->driver_user_id);
        $courierName = $driver ? trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')) : null;
        $courierContact = $this->resolveDriverContact($driver) ?: null;

        $added = 0;
        $skipped = 0;
        $overCapacity = 0;
        $areaMismatch = 0;
        $capacityKg = (float) ($trip->vehicle?->capacity_kg ?? 0);
        $currentWeight = $this->currentTripWeight($trip);
        $maxStops = (int) ($trip->vehicle?->max_orders_per_trip ?? 0);
        $currentStops = (int) $trip->ecommerceDeliveries()->count() + (int) $trip->salesDeliveries()->count() + (int) $trip->returnPickups()->count();
        $targetArea = $this->tripAreaKey($trip);

        DB::transaction(function () use ($validated, $trip, $request, $courierName, $courierContact, &$added, &$skipped, &$overCapacity, &$areaMismatch, $capacityKg, &$currentWeight, $maxStops, &$currentStops, &$targetArea) {
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

                    $orderArea = $this->areaKey((string) $order->shipping_address);
                    $targetArea = $targetArea ?: $orderArea;
                    if ($orderArea !== $targetArea) {
                        $areaMismatch++;
                        continue;
                    }

                    if ($maxStops > 0 && $currentStops >= $maxStops) {
                        $overCapacity++;
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
                        if (in_array(strtolower((string) $delivery->status), ['', 'pending', 'ready_for_dispatch'], true)) {
                            $delivery->status = 'assigned';
                            $delivery->updated_by = $request->user()->id;
                            $delivery->save();
                        }
                        continue;
                    }

                    $delivery->trip_id = $trip->id;
                    $delivery->vehicle_id = $trip->vehicle_id;
                    $delivery->driver_user_id = $trip->driver_user_id;
                    $delivery->courier_name = $courierName;
                    $delivery->courier_contact = $courierContact;
                    $delivery->tracking_number = $delivery->tracking_number ?: $this->nextTrackingNumber();
                    if (in_array(strtolower((string) $delivery->status), ['', 'pending', 'ready_for_dispatch'], true)) {
                        $delivery->status = 'assigned';
                    }
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
                    $currentStops++;
                }
            } elseif ($validated['source_type'] === 'sales') {
                $orders = SalesOrder::query()
                    ->with(['items.product:id,weight_kg'])
                    ->whereIn('id', $validated['order_ids'])
                    ->where('store_id', $trip->store_id)
                    ->where('delivery_required', true)
                    ->get();

                foreach ($orders as $order) {
                    $orderArea = $this->areaKey((string) $order->delivery_address);
                    $targetArea = $targetArea ?: $orderArea;
                    if ($orderArea !== $targetArea) {
                        $areaMismatch++;
                        continue;
                    }
                    if ($maxStops > 0 && $currentStops >= $maxStops) {
                        $overCapacity++;
                        continue;
                    }
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
                        if (in_array(strtolower((string) $delivery->status), ['', 'pending', 'ready_for_dispatch'], true)) {
                            $delivery->status = 'assigned';
                            $delivery->updated_by = $request->user()->id;
                            $delivery->save();
                        }
                        continue;
                    }

                    $delivery->trip_id = $trip->id;
                    $delivery->driver_user_id = $trip->driver_user_id;
                    $delivery->courier_name = $courierName;
                    $delivery->courier_contact = $courierContact;
                    $delivery->tracking_number = $delivery->tracking_number ?: $this->nextTrackingNumber();
                    if (in_array(strtolower((string) $delivery->status), ['', 'pending', 'ready_for_dispatch'], true)) {
                        $delivery->status = 'assigned';
                    }
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
                    $currentStops++;
                }
            } else {
                $pickups = ReturnPickup::query()->with(['returnRequest.orderItem.product:id,weight_kg'])
                    ->whereIn('id', $validated['order_ids'])->where('store_id', $trip->store_id)
                    ->whereNull('trip_id')->whereIn('status', ['ready_for_dispatch', 'scheduled'])->get();

                foreach ($pickups as $pickup) {
                    $pickupArea = $this->areaKey((string) $pickup->pickup_address);
                    $targetArea = $targetArea ?: $pickupArea;
                    if ($pickupArea !== $targetArea) { $areaMismatch++; continue; }
                    if ($maxStops > 0 && $currentStops >= $maxStops) { $overCapacity++; continue; }

                    $pickup->trip_id = $trip->id;
                    $pickup->driver_user_id = $trip->driver_user_id;
                    $pickup->vehicle_id = $trip->vehicle_id;
                    $pickup->status = 'assigned';
                    $pickup->updated_by = $request->user()->id;
                    $pickup->save();
                    ReturnPickupLog::query()->create([
                        'return_pickup_id' => $pickup->id, 'event_type' => 'assigned',
                        'status_to' => 'assigned', 'message' => "Return pickup assigned to trip #{$trip->id}.",
                        'created_by' => $request->user()->id,
                    ]);
                    $added++;
                    $currentStops++;
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

        if ($added === 0 && $areaMismatch > 0) {
            return response()->json([
                'success' => false,
                'message' => "Selected orders do not match this trip's delivery area ({$targetArea}).",
                'data' => ['added' => 0, 'skipped' => $skipped, 'over_capacity' => $overCapacity, 'area_mismatch' => $areaMismatch],
            ], 422);
        }

        $message = "Orders added to trip ({$added}).";
        if ($overCapacity > 0) {
            $message .= " {$overCapacity} skipped due to capacity.";
        } elseif ($areaMismatch > 0) {
            $message .= " {$areaMismatch} skipped because they belong to another delivery area.";
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
                'area_mismatch' => $areaMismatch,
            ],
        ]);
    }

    private function tripAreaKey(DeliveryTrip $trip): ?string
    {
        $areas = collect()
            ->merge($trip->ecommerceDeliveries()->with('order:id,shipping_address')->get()->pluck('order.shipping_address'))
            ->merge($trip->salesDeliveries()->with('order:id,delivery_address')->get()->pluck('order.delivery_address'))
            ->merge($trip->returnPickups()->pluck('pickup_address'))
            ->filter()->map(fn ($address) => $this->areaKey((string) $address));

        return $areas->countBy()->sortDesc()->keys()->first();
    }

    private function returnPickupWeight(ReturnPickup $pickup): float
    {
        $unitWeight = (float) ($pickup->returnRequest?->orderItem?->product?->weight_kg ?? 0);
        $quantity = (int) ($pickup->returnRequest?->requested_quantity ?? 0);
        return $unitWeight * $quantity;
    }

    private function currentTripWeight(DeliveryTrip $trip): float
    {
        $weight = 0.0;

        $trip->loadMissing([
            'ecommerceDeliveries.order.items.product:id,weight_kg',
            'salesDeliveries.order.items.product:id,weight_kg',
            'returnPickups.returnRequest.orderItem.product:id,weight_kg',
        ]);

        foreach ($trip->ecommerceDeliveries as $delivery) {
            if (in_array(strtolower((string) $delivery->status), ['delivered', 'cancelled'], true)) continue;
            $weight += $this->orderWeight($delivery->order?->items ?? []);
        }

        foreach ($trip->salesDeliveries as $delivery) {
            if (in_array(strtolower((string) $delivery->status), ['delivered', 'cancelled'], true)) continue;
            $weight += $this->orderWeight($delivery->order?->items ?? []);
        }

        foreach ($trip->returnPickups as $pickup) {
            if (!in_array(strtolower((string) $pickup->status), ['picked_up', 'out_for_delivery'], true)) continue;
            $weight += $this->returnPickupWeight($pickup);
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
        abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales', 'return_pickup'])],
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
            } elseif ($validated['source_type'] === 'sales') {
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
            } else {
                $pickups = ReturnPickup::query()->where('trip_id', $trip->id)
                    ->whereIn('id', $validated['order_ids'])->get();
                foreach ($pickups as $pickup) {
                    if (!in_array((string) $pickup->status, ['assigned', 'ready_for_dispatch', 'scheduled'], true)) continue;
                    ReturnPickupLog::query()->create([
                        'return_pickup_id' => $pickup->id, 'event_type' => 'trip_removed',
                        'status_from' => $pickup->status, 'status_to' => 'ready_for_dispatch',
                        'message' => "Return pickup removed from trip #{$trip->id}.",
                        'created_by' => $request->user()->id,
                    ]);
                    $pickup->update([
                        'trip_id' => null, 'driver_user_id' => null, 'vehicle_id' => null,
                        'status' => 'ready_for_dispatch', 'updated_by' => $request->user()->id,
                    ]);
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

    private function isDriverOnly(Request $request): bool
    {
        return $request->user()->hasPermissionTo('driver.trips.view')
            && !$request->user()->hasPermissionTo('logistics.deliveries.manage');
    }

    private function authorizeTripViewer(Request $request): void
    {
        abort_unless($request->user()->hasAnyPermission([
            'driver.trips.view', 'logistics.deliveries.view', 'logistics.deliveries.manage',
        ]) || $request->user()->hasRole('super_admin'), 403);
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

        return User::query()->with('employee:id,user_id')->find($driverUserId);
    }

    private function resolveDriverContact(?User $driver): ?string
    {
        if (!$driver) {
            return null;
        }

        return $driver->phone_number;
    }

    private function nextTrackingNumber(): string
    {
        $prefix = 'LGS-' . now()->format('Ymd') . '-';
        do {
            $tracking = $prefix . (string) \Illuminate\Support\Str::ulid();
        } while (
            EcommerceOrderDelivery::query()->where('tracking_number', $tracking)->exists()
            || SalesOrderDelivery::query()->where('tracking_number', $tracking)->exists()
        );

        return $tracking;
    }
}
