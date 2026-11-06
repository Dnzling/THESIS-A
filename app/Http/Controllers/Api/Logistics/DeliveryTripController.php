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
use App\Services\Logistics\DriverPermissionEligibility;
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
            ->withCount(['ecommerceDeliveries', 'salesDeliveries', 'returnPickups', 'stockTransfers']);

        $this->applyTenantScope($request, $query);
        if ($this->isDriverOnly($request)) {
            $query->where('driver_user_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                if (ctype_digit($search)) {
                    $builder->orWhere('id', (int) $search);
                }
                $builder->orWhereHas('driver', fn ($driver) => $driver
                    ->where('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn ($vehicle) => $vehicle
                        ->where('vehicle_name', 'like', "%{$search}%")
                        ->orWhere('plate_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortBy = in_array($sortBy, ['created_at', 'scheduled_departure_at', 'status'], true) ? $sortBy : 'created_at';
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(100, max(1, (int) $request->input('per_page', 10)));

        $trips = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        return response()->json(['success' => true, 'data' => $trips]);
    }

    public function compatibleForOrder(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales', 'return_pickup', 'stock_transfer'])],
            'order_id' => ['required', 'integer', 'min:1'],
        ]);

        $storeId = $this->resolveStoreId($request);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $source = $validated['source_type'];
        if ($source === 'ecommerce') {
            $order = EcommerceOrder::query()
                ->with(['delivery', 'items.product:id,weight_kg'])
                ->where('store_id', $storeId)
                ->where('status', 'ready_for_dispatch')
                ->findOrFail((int) $validated['order_id']);
            $delivery = $order->delivery;
            abort_if($delivery && ($delivery->trip_id || !in_array((string) $delivery->status, ['pending', 'ready_for_dispatch'], true)), 422, 'This order is already assigned or no longer ready for dispatch.');
            $address = (string) $order->shipping_address;
            $orderWeight = $this->orderWeight($order->items ?? []);
        } elseif ($source === 'sales') {
            $order = SalesOrder::query()
                ->with(['delivery', 'items.product:id,weight_kg'])
                ->where('store_id', $storeId)
                ->where('delivery_required', true)
                ->where(function ($query) {
                    $query->whereIn('status', ['ready_for_dispatch', 'ready_to_dispatch'])
                        ->orWhere(fn ($legacy) => $legacy->where('status', 'completed')->where('payment_status', 'paid'));
                })
                ->findOrFail((int) $validated['order_id']);
            $delivery = $order->delivery;
            abort_if($delivery && ($delivery->trip_id || !in_array((string) $delivery->status, ['pending', 'ready_for_dispatch'], true)), 422, 'This order is already assigned or no longer ready for dispatch.');
            $address = (string) $order->delivery_address;
            $orderWeight = $this->orderWeight($order->items ?? []);
        } elseif ($source === 'return_pickup') {
            $order = ReturnPickup::query()
                ->with(['returnRequest.orderItem.product:id,weight_kg'])
                ->where('store_id', $storeId)
                ->whereNull('trip_id')
                ->whereIn('status', ['ready_for_dispatch', 'scheduled'])
                ->findOrFail((int) $validated['order_id']);
            $address = (string) $order->pickup_address;
            $orderWeight = $this->returnPickupWeight($order);
        } else {
            $order = \App\Models\Inventory\StockTransfer::query()
                ->with(['items.product:id,weight_kg', 'toBranch:id,address,city,province'])
                ->where('store_id', $storeId)
                ->whereNull('trip_id')
                ->where('status', 'in_transit')
                ->where(fn ($query) => $query->whereNull('delivery_status')->orWhere('delivery_status', 'ready_for_dispatch'))
                ->findOrFail((int) $validated['order_id']);
            $address = (string) collect([
                $order->toBranch?->address,
                $order->toBranch?->city,
                $order->toBranch?->province,
            ])->filter()->implode(', ');
            $orderWeight = $this->stockTransferWeight($order);
        }

        $orderArea = $source === 'sales' ? $this->salesAreaKey($order) : $this->areaKey($address);
        $query = DeliveryTrip::query()->with([
            'vehicle:id,vehicle_name,plate_number,vehicle_type,capacity_kg,max_orders_per_trip',
            'driver:id,fname,lname,email',
            'ecommerceDeliveries.order.items.product:id,weight_kg',
            'salesDeliveries.order.items.product:id,weight_kg',
            'returnPickups.returnRequest.orderItem.product:id,weight_kg',
            'stockTransfers.fromBranch:id,name,address,latitude,longitude',
            'stockTransfers.toBranch:id,name,address,latitude,longitude',
            'stockTransfers.items.product:id,weight_kg',
        ])->withCount(['ecommerceDeliveries', 'salesDeliveries', 'returnPickups', 'stockTransfers'])
            ->where('store_id', $storeId)
            ->where('status', 'planned');
        $this->applyTenantScope($request, $query);

        $compatible = $query->orderByDesc('created_at')->get()->map(function (DeliveryTrip $trip) use ($orderArea, $orderWeight) {
            $capacity = (float) ($trip->vehicle?->capacity_kg ?? 0);
            $weight = $this->currentTripWeight($trip);
            $maxStops = (int) ($trip->vehicle?->max_orders_per_trip ?? 0);
            $stops = (int) $trip->ecommerceDeliveries()->whereNotIn('status', ['delivered', 'cancelled'])->count()
                + (int) $trip->salesDeliveries()->whereNotIn('status', ['delivered', 'cancelled'])->count()
                + (int) $trip->returnPickups()->whereNotIn('status', ['delivered', 'cancelled'])->count()
                + (int) $trip->stockTransfers()->whereNotIn('delivery_status', ['delivered', 'cancelled'])->count();
            $tripArea = $this->tripAreaKey($trip);

            if ($maxStops > 0 && $stops >= $maxStops) return null;
            if ($capacity > 0 && ($weight + $orderWeight) > $capacity) return null;
            if ($tripArea && $tripArea !== $orderArea) return null;

            $driverName = trim((string) ($trip->driver?->fname . ' ' . $trip->driver?->lname));
            return [
                'id' => $trip->id,
                'created_at' => $trip->created_at,
                'status' => $trip->status,
                'driver_name' => $driverName ?: 'Driver not set',
                'driver' => $trip->driver,
                'vehicle_name' => $trip->vehicle?->vehicle_name ?? 'Vehicle not set',
                'vehicle' => $trip->vehicle,
                'plate_number' => $trip->vehicle?->plate_number,
                'vehicle_type' => $trip->vehicle?->vehicle_type,
                'scheduled_departure_at' => $trip->scheduled_departure_at,
                'assigned_stops' => $stops,
                'ecommerce_deliveries_count' => (int) $trip->ecommerce_deliveries_count,
                'sales_deliveries_count' => (int) $trip->sales_deliveries_count,
                'return_pickups_count' => (int) $trip->return_pickups_count,
                'stock_transfers_count' => (int) $trip->stock_transfers_count,
                'max_stops' => $maxStops ?: null,
                'current_weight_kg' => round($weight, 2),
                'order_weight_kg' => round($orderWeight, 2),
                'remaining_capacity_kg' => $capacity > 0 ? round(max(0, $capacity - $weight), 2) : null,
                'remaining_capacity_after_kg' => $capacity > 0 ? round(max(0, $capacity - $weight - $orderWeight), 2) : null,
                'vehicle_capacity_kg' => $capacity ?: null,
                'remaining_stop_slots' => $maxStops > 0 ? max(0, $maxStops - $stops) : null,
                'remaining_stop_slots_after' => $maxStops > 0 ? max(0, $maxStops - $stops - 1) : null,
            ];
        })->filter()->values();

        return response()->json(['success' => true, 'data' => $compatible]);
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
                'stockTransfers.fromBranch:id,name,address,latitude,longitude',
                'stockTransfers.toBranch:id,name,address,latitude,longitude',
                'stockTransfers.items.product:id,weight_kg',
            ]);

        $this->applyTenantScope($request, $query);
        if ($this->isDriverOnly($request)) {
            $query->where('driver_user_id', $request->user()->id);
        }
        $trip = $query->findOrFail($id);

        return response()->json(['success' => true, 'data' => $trip]);
    }

    public function store(Request $request, DriverPermissionEligibility $driverEligibility): JsonResponse
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

        $driver = User::query()->with('employee')
            ->where('id', (int) $validated['driver_user_id'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->whereHas('employee', fn ($query) => $query->where('status', 'active'))
            ->first();
        if (!$driver || !$driverEligibility->userCanDrive($driver, $storeId)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'driver_user_id' => 'Select an active employee with a logistics driver permission.',
            ]);
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
            && !$trip->salesDeliveries()->exists() && !$trip->returnPickups()->exists() && !$trip->stockTransfers()->exists()) {
            return response()->json(['success' => false, 'message' => 'Add an order before dispatching this trip.'], 422);
        }

        if ((string) $validated['status'] === 'completed') {
            $totalDeliveries = (int) $trip->ecommerceDeliveries()->count()
                + (int) $trip->salesDeliveries()->count()
                + (int) $trip->returnPickups()->count()
                + (int) $trip->stockTransfers()->count();
            $undelivered = (int) $trip->ecommerceDeliveries()
                ->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['', 'delivered'])
                ->count()
                + (int) $trip->salesDeliveries()
                    ->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['', 'delivered'])
                    ->count()
                + (int) $trip->returnPickups()
                    ->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['', 'delivered'])
                    ->count()
                + (int) $trip->stockTransfers()
                    ->whereRaw('LOWER(COALESCE(delivery_status, ?)) <> ?', ['', 'delivered'])
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
                    ->with('order')
                    ->whereIn('status', ['pending', 'ready_for_dispatch', 'assigned'])
                    ->get()
                    ->each(function (EcommerceOrderDelivery $delivery) use ($request, $now) {
                        $previousDeliveryStatus = (string) $delivery->status;
                        $order = $delivery->order;
                        $previousOrderStatus = (string) ($order?->status ?? '');

                        $delivery->status = 'out_for_delivery';
                        $delivery->dispatched_at = $delivery->dispatched_at ?: $now;
                        $delivery->out_for_delivery_at = $delivery->out_for_delivery_at ?: $now;
                        $delivery->updated_by = $request->user()->id;
                        $delivery->save();

                        if ($previousDeliveryStatus !== 'out_for_delivery') {
                            EcommerceDeliveryLog::query()->create([
                                'delivery_id' => $delivery->id,
                                'order_id' => $delivery->order_id,
                                'store_id' => $delivery->store_id,
                                'event_type' => 'status_updated',
                                'status_from' => $previousDeliveryStatus,
                                'status_to' => 'out_for_delivery',
                                'message' => "Delivery status updated from {$previousDeliveryStatus} to out_for_delivery.",
                                'created_by' => $request->user()->id,
                            ]);
                        }

                        if ($order && $previousOrderStatus !== 'out_for_delivery') {
                            $order->status = 'out_for_delivery';
                            $order->save();

                            EcommerceDeliveryLog::query()->create([
                                'delivery_id' => $delivery->id,
                                'order_id' => $order->id,
                                'store_id' => $order->store_id,
                                'event_type' => 'status_updated',
                                'status_from' => $previousOrderStatus,
                                'status_to' => 'out_for_delivery',
                                'message' => "Order status updated from {$previousOrderStatus} to out_for_delivery.",
                                'created_by' => $request->user()->id,
                            ]);
                        }
                    });
                $trip->salesDeliveries()
                    ->whereIn('status', ['pending', 'ready_for_dispatch', 'assigned'])
                    ->update(['status' => 'out_for_delivery', 'dispatched_at' => $now, 'out_for_delivery_at' => $now, 'updated_by' => $request->user()->id, 'updated_at' => $now]);
                $trip->returnPickups()
                    ->whereIn('status', ['ready_for_dispatch', 'scheduled'])
                    ->update(['status' => 'out_for_delivery', 'out_for_delivery_at' => $now, 'driver_user_id' => $trip->driver_user_id, 'vehicle_id' => $trip->vehicle_id, 'updated_by' => $request->user()->id, 'updated_at' => $now]);
                $trip->stockTransfers()
                    ->whereIn('delivery_status', ['assigned', 'ready_for_dispatch'])
                    ->update(['delivery_status' => 'in_transit', 'updated_at' => $now]);
            } elseif ((string) $validated['status'] === 'cancelled') {
                $trip->stockTransfers()
                    ->whereIn('delivery_status', ['assigned', 'ready_for_dispatch'])
                    ->update([
                        'trip_id' => null,
                        'vehicle_id' => null,
                        'driver_user_id' => null,
                        'driver_name' => null,
                        'driver_contact' => null,
                        'vehicle_type' => null,
                        'delivery_status' => 'ready_for_dispatch',
                        'updated_at' => now(),
                    ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Trip status updated.', 'data' => $trip]);
    }

    public function suggestions(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales', 'return_pickup', 'stock_transfer'])],
        ]);

        $query = DeliveryTrip::query()->with('vehicle');
        $this->applyTenantScope($request, $query);
        $trip = $query->findOrFail($id);
        $currentWeight = $this->currentTripWeight($trip);
        $capacityKg = (float) ($trip->vehicle?->capacity_kg ?? 0);
        $maxStops = (int) ($trip->vehicle?->max_orders_per_trip ?? 0);
        $currentStops = (int) $trip->ecommerceDeliveries()->count() + (int) $trip->salesDeliveries()->count()
            + (int) $trip->returnPickups()->count() + (int) $trip->stockTransfers()->count();
        $remainingStops = $maxStops > 0 ? max(0, $maxStops - $currentStops) : PHP_INT_MAX;
        $assignedReturnWeight = $trip->returnPickups()->whereNotIn('status', ['delivered', 'cancelled'])
            ->with('returnRequest.orderItem.product:id,weight_kg')->get()
            ->sum(fn (ReturnPickup $pickup) => $this->returnPickupWeight($pickup));
        $remainingWeight = $capacityKg > 0
            ? max(0, $capacityKg - ($validated['source_type'] === 'return_pickup' ? $assignedReturnWeight : $currentWeight))
            : PHP_FLOAT_MAX;

        $rows = match ($validated['source_type']) {
            'ecommerce' => $this->ecommerceSuggestionRows($trip),
            'sales' => $this->salesSuggestionRows($trip),
            'return_pickup' => $this->returnPickupSuggestionRows($trip),
            default => $this->stockTransferSuggestionRows($trip),
        };

        $targetArea = $this->tripAreaKey($trip);

        $groups = collect($rows)->groupBy('area_key');
        if (!$targetArea) {
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
                    'area_key' => $this->salesAreaKey($order)];
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

    private function stockTransferSuggestionRows(DeliveryTrip $trip): array
    {
        return \App\Models\Inventory\StockTransfer::query()
            ->with(['toBranch:id,address,latitude,longitude,city,province', 'fromBranch:id,latitude,longitude', 'items.product:id,weight_kg'])
            ->where('store_id', $trip->store_id)
            ->whereNull('trip_id')
            ->where('status', 'in_transit')
            ->where(fn ($query) => $query->whereNull('delivery_status')->orWhere('delivery_status', 'ready_for_dispatch'))
            ->get()->map(function ($transfer) {
                $address = collect([$transfer->toBranch?->address, $transfer->toBranch?->city, $transfer->toBranch?->province])->filter()->implode(', ');
                return [
                    'id' => 'stock-transfer-'.$transfer->id,
                    'source_type' => 'stock_transfer',
                    'order_id' => $transfer->id,
                    'order_number' => $transfer->transfer_number,
                    'customer_name' => ($transfer->fromBranch?->name ?: 'Source branch').' to '.($transfer->toBranch?->name ?: 'Destination branch'),
                    'delivery_address' => $address,
                    'order_status' => $transfer->status,
                    'distance_km' => $this->haversineDistance($transfer->fromBranch?->latitude, $transfer->fromBranch?->longitude, $transfer->toBranch?->latitude, $transfer->toBranch?->longitude),
                    'weight_kg' => $this->stockTransferWeight($transfer),
                    'area_key' => $this->areaKey($address),
                ];
            })->all();
    }

    private function areaKey(string $address): string
    {
        $parts = array_values(array_filter(array_map(fn ($part) => trim(mb_strtolower($part)), explode(',', $address))));
        return implode(', ', array_slice($parts, 0, 2)) ?: 'unknown area';
    }

    private function salesAreaKey(SalesOrder $order): string
    {
        $province = trim(mb_strtolower((string) $order->delivery_province));
        $city = trim(mb_strtolower((string) $order->delivery_city));
        if ($province !== '' && $city !== '') {
            return "{$province}, {$city}";
        }

        $parts = array_values(array_filter(array_map(fn ($part) => trim(mb_strtolower($part)), explode(',', (string) $order->delivery_address))));
        if (count($parts) >= 3) {
            return implode(', ', array_reverse(array_slice($parts, -2)));
        }

        return $this->areaKey((string) $order->delivery_address);
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
            'source_type' => ['required', Rule::in(['ecommerce', 'sales', 'return_pickup', 'stock_transfer'])],
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
        $currentStops = (int) $trip->ecommerceDeliveries()->count() + (int) $trip->salesDeliveries()->count()
            + (int) $trip->returnPickups()->count() + (int) $trip->stockTransfers()->count();
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

                    if ($delivery->lalamove_order_id) {
                        $skipped++;
                        continue;
                    }

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
                    $delivery->courier_provider = 'internal';
                    $delivery->lalamove_quotation_id = null;
                    $delivery->lalamove_order_id = null;
                    $delivery->lalamove_share_link = null;
                    $delivery->lalamove_status = null;
                    $delivery->lalamove_service_type = null;
                    $delivery->lalamove_pickup_stop_id = null;
                    $delivery->lalamove_dropoff_stop_id = null;
                    $delivery->lalamove_price_breakdown = null;
                    $delivery->lalamove_quote_expires_at = null;
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
                    $orderArea = $this->salesAreaKey($order);
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

                    if ($delivery->lalamove_order_id) {
                        $skipped++;
                        continue;
                    }

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
                    $delivery->courier_provider = 'internal';
                    $delivery->lalamove_quotation_id = null;
                    $delivery->lalamove_order_id = null;
                    $delivery->lalamove_share_link = null;
                    $delivery->lalamove_status = null;
                    $delivery->lalamove_service_type = null;
                    $delivery->lalamove_pickup_stop_id = null;
                    $delivery->lalamove_dropoff_stop_id = null;
                    $delivery->lalamove_price_breakdown = null;
                    $delivery->lalamove_quote_expires_at = null;
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
            } elseif ($validated['source_type'] === 'return_pickup') {
                $pickups = ReturnPickup::query()->with(['returnRequest.orderItem.product:id,weight_kg'])
                    ->whereIn('id', $validated['order_ids'])->where('store_id', $trip->store_id)
                    ->whereNull('trip_id')->whereIn('status', ['ready_for_dispatch', 'scheduled'])->get();

                foreach ($pickups as $pickup) {
                    $pickupArea = $this->areaKey((string) $pickup->pickup_address);
                    $targetArea = $targetArea ?: $pickupArea;
                    if ($pickupArea !== $targetArea) { $areaMismatch++; continue; }
                    if ($maxStops > 0 && $currentStops >= $maxStops) { $overCapacity++; continue; }
                    $pickupWeight = $this->returnPickupWeight($pickup);
                    if ($capacityKg > 0 && ($currentWeight + $pickupWeight) > $capacityKg) { $overCapacity++; continue; }

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
                    $currentWeight += $pickupWeight;
                    $currentStops++;
                }
            } else {
                $transfers = \App\Models\Inventory\StockTransfer::query()
                    ->with(['fromBranch:id,name,latitude,longitude', 'toBranch:id,name,address,latitude,longitude,city,province', 'items.product:id,weight_kg'])
                    ->whereIn('id', $validated['order_ids'])
                    ->where('store_id', $trip->store_id)
                    ->whereNull('trip_id')
                    ->where('status', 'in_transit')
                    ->where(fn ($query) => $query->whereNull('delivery_status')->orWhere('delivery_status', 'ready_for_dispatch'))
                    ->get();

                foreach ($transfers as $transfer) {
                    $address = collect([$transfer->toBranch?->address, $transfer->toBranch?->city, $transfer->toBranch?->province])->filter()->implode(', ');
                    $transferArea = $this->areaKey($address);
                    $targetArea = $targetArea ?: $transferArea;
                    if ($transferArea !== $targetArea) { $areaMismatch++; continue; }
                    if ($maxStops > 0 && $currentStops >= $maxStops) { $overCapacity++; continue; }
                    $transferWeight = $this->stockTransferWeight($transfer);
                    if ($capacityKg > 0 && ($currentWeight + $transferWeight) > $capacityKg) { $overCapacity++; continue; }

                    $driver = $this->resolveDriver($trip->driver_user_id);
                    $transfer->trip_id = $trip->id;
                    $transfer->vehicle_id = $trip->vehicle_id;
                    $transfer->driver_user_id = $trip->driver_user_id;
                    $transfer->vehicle_type = $trip->vehicle?->vehicle_name;
                    $transfer->driver_name = trim((string) ($driver?->fname.' '.$driver?->lname)) ?: null;
                    $transfer->driver_contact = $this->resolveDriverContact($driver);
                    $transfer->tracking_number = $transfer->tracking_number ?: $this->nextTrackingNumber();
                    $transfer->delivery_status = 'assigned';
                    $actor = $request->user();
                    $actorName = trim((string) ($actor?->fname.' '.$actor?->lname)) ?: 'Logistics';
                    $tripRef = 'TRIP-'.($trip->created_at?->format('Y') ?: now()->format('Y')).'-'.str_pad((string) $trip->id, 6, '0', STR_PAD_LEFT);
                    $transfer->notes = trim((string) $transfer->notes."\nLOG2|".now()->toDateTimeString()."|".$actorName."|custom_note|Assigned to trip {$tripRef}");
                    $transfer->save();
                    $added++;
                    $currentWeight += $transferWeight;
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
            ->merge($trip->ecommerceDeliveries()->with('order:id,shipping_address')->get()->map(fn ($delivery) => $delivery->order ? $this->areaKey((string) $delivery->order->shipping_address) : null))
            ->merge($trip->salesDeliveries()->with('order:id,delivery_address,delivery_province,delivery_city')->get()->map(fn ($delivery) => $delivery->order ? $this->salesAreaKey($delivery->order) : null))
            ->merge($trip->returnPickups()->pluck('pickup_address')->map(fn ($address) => $this->areaKey((string) $address)))
            ->merge($trip->stockTransfers()->with('toBranch:id,address,city,province')->get()->map(fn ($transfer) => $this->areaKey(collect([$transfer->toBranch?->address, $transfer->toBranch?->city, $transfer->toBranch?->province])->filter()->implode(', '))))
            ->filter();

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
            'stockTransfers.items.product:id,weight_kg',
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
            if (in_array(strtolower((string) $pickup->status), ['delivered', 'cancelled'], true)) continue;
            $weight += $this->returnPickupWeight($pickup);
        }

        foreach ($trip->stockTransfers as $transfer) {
            if (in_array(strtolower((string) $transfer->delivery_status), ['delivered', 'cancelled'], true)) continue;
            $weight += $this->stockTransferWeight($transfer);
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

    private function stockTransferWeight($transfer): float
    {
        return collect($transfer->items ?? [])->sum(function ($item) {
            $quantity = $item->shipped_quantity ?? $item->approved_quantity ?? $item->requested_quantity ?? 0;
            return (float) ($item->product?->weight_kg ?? 0) * (float) $quantity;
        });
    }

    public function removeOrders(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('logistics.deliveries.manage'), 403);
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['ecommerce', 'sales', 'return_pickup', 'stock_transfer'])],
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
            } elseif ($validated['source_type'] === 'return_pickup') {
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
            } else {
                $transfers = \App\Models\Inventory\StockTransfer::query()
                    ->where('trip_id', $trip->id)
                    ->whereIn('id', $validated['order_ids'])
                    ->whereIn('delivery_status', ['assigned', 'ready_for_dispatch'])
                    ->get();

                foreach ($transfers as $transfer) {
                    $actor = $request->user();
                    $actorName = trim((string) ($actor?->fname.' '.$actor?->lname)) ?: 'Logistics';
                    $tripRef = 'TRIP-'.($trip->created_at?->format('Y') ?: now()->format('Y')).'-'.str_pad((string) $trip->id, 6, '0', STR_PAD_LEFT);
                    $entry = 'LOG2|'.now()->toDateTimeString().'|'.$actorName.'|custom_note|Removed from trip '.$tripRef;
                    $transfer->update([
                        'trip_id' => null,
                        'vehicle_id' => null,
                        'driver_user_id' => null,
                        'driver_name' => null,
                        'driver_contact' => null,
                        'vehicle_type' => null,
                        'delivery_status' => 'ready_for_dispatch',
                        'notes' => trim((string) $transfer->notes."\n".$entry),
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
            || \App\Models\Inventory\StockTransfer::query()->where('tracking_number', $tracking)->exists()
        );

        return $tracking;
    }
}
