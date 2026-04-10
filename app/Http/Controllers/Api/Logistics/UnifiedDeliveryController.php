<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderDelivery;
use App\Models\Sales\SalesOrderDeliveryLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnifiedDeliveryController extends Controller
{
    private const DELIVERY_STATUSES = [
        'assigned',
        'packed',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'failed_delivery',
        'cancelled',
    ];

    private const ORDER_SOURCES = ['ecommerce', 'sales'];

    private const PENDING_ORDER_STATUSES = ['pending', 'pending_payment', 'confirmed', 'ready_for_dispatch'];

    public function orders(Request $request): JsonResponse
    {
        $source = (string) $request->input('source', 'all');
        $search = trim((string) $request->input('search', ''));
        $statusFilter = strtolower((string) $request->input('status', ''));

        $rows = collect();

        if (in_array($source, ['all', 'ecommerce'], true)) {
            $rows = $rows->merge($this->getEcommerceOrderRows($request));
        }

        if (in_array($source, ['all', 'sales'], true)) {
            $rows = $rows->merge($this->getSalesOrderRows($request));
        }

        if ($search !== '') {
            $rows = $rows->filter(function (array $row) use ($search) {
                $haystack = strtolower(implode(' ', [
                    $row['order_number'] ?? '',
                    $row['customer_name'] ?? '',
                    $row['customer_contact'] ?? '',
                    $row['delivery_address'] ?? '',
                    $row['delivery_status'] ?? '',
                ]));

                return str_contains($haystack, strtolower($search));
            });
        }

        if ($statusFilter !== '') {
            $rows = $rows->filter(function (array $row) use ($statusFilter) {
                if (in_array($statusFilter, ['pending', 'ready_for_dispatch'], true)) {
                    return (bool) ($row['can_create_delivery'] ?? false);
                }

                return strtolower((string) ($row['delivery_status'] ?? '')) === $statusFilter;
            });
        }

        $rows = $rows->sortByDesc('created_at')->values();

        $perPage = max(1, (int) $request->input('per_page', 15));
        $page = max(1, (int) $request->input('page', 1));
        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function orderDetail(Request $request, string $source, int $orderId): JsonResponse
    {
        $source = strtolower($source);
        if (!in_array($source, self::ORDER_SOURCES, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid source type.'], 422);
        }

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            $delivery = $order->delivery;
            $logs = $delivery
                ? EcommerceDeliveryLog::query()
                    ->with('creator:id,fname,lname,email')
                    ->where('delivery_id', $delivery->id)
                    ->orderByDesc('created_at')
                    ->get()
                : collect();

            return response()->json([
                'success' => true,
                'data' => [
                    'source_type' => 'ecommerce',
                    'order' => $order,
                    'delivery' => $delivery ? array_merge($delivery->toArray(), [
                        'proof_photo_url' => $this->publicUrl($delivery->proof_of_delivery_path),
                        'proof_signature_url' => $this->publicUrl($delivery->proof_signature_path),
                    ]) : null,
                    'logs' => $logs,
                ],
            ]);
        }

        $order = $this->resolveSalesOrder($request, $orderId, withDelivery: true);
        $delivery = $order->delivery;
        $logs = $delivery
            ? SalesOrderDeliveryLog::query()
                ->with('creator:id,fname,lname,email')
                ->where('delivery_id', $delivery->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'source_type' => 'sales',
                'order' => $order,
                'delivery' => $delivery ? array_merge($delivery->toArray(), [
                    'proof_photo_url' => $this->publicUrl($delivery->proof_of_delivery_path),
                    'proof_signature_url' => $this->publicUrl($delivery->proof_signature_path),
                ]) : null,
                'logs' => $logs,
            ],
        ]);
    }

    public function logisticsEmployees(Request $request): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $branchId = $this->resolveBranchId($request);

        $roleIds = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('permissions.name', ['logistics.deliveries.view', 'logistics.deliveries.manage'])
            ->pluck('role_permissions.role_id')
            ->unique()
            ->values();

        $employees = User::query()
            ->with(['role:id,name,display_name', 'employee:id,user_id,branch_id,phone,status'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->when($roleIds->isNotEmpty(), fn ($query) => $query->whereIn('role_id', $roleIds))
            ->when($branchId, fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->where('branch_id', $branchId)))
            ->orderBy('fname')
            ->orderBy('lname')
            ->get()
            ->filter(fn (User $employee) => $employee->hasAnyPermission(['logistics.deliveries.view', 'logistics.deliveries.manage'], $storeId))
            ->map(fn (User $employee) => [
                'id' => $employee->id,
                'name' => trim(($employee->fname ?? '') . ' ' . ($employee->lname ?? '')),
                'email' => $employee->email,
                'contact' => $employee->employee?->phone ?? $employee->phone_number,
                'branch_id' => $employee->employee?->branch_id,
                'role' => $employee->role?->display_name ?? $employee->role?->name ?? 'N/A',
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $employees]);
    }

    public function estimateDistance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_latitude' => 'required|numeric|between:-90,90',
            'origin_longitude' => 'required|numeric|between:-180,180',
            'destination_latitude' => 'required|numeric|between:-90,90',
            'destination_longitude' => 'required|numeric|between:-180,180',
            'per_km_charge' => 'nullable|numeric|min:0',
        ]);

        $distance = $this->haversine(
            (float) $validated['origin_latitude'],
            (float) $validated['origin_longitude'],
            (float) $validated['destination_latitude'],
            (float) $validated['destination_longitude'],
        );

        $perKm = isset($validated['per_km_charge']) ? (float) $validated['per_km_charge'] : null;

        return response()->json([
            'success' => true,
            'data' => [
                'distance_km' => round($distance, 2),
                'per_km_charge' => $perKm,
                'estimated_fee' => $perKm !== null ? round($distance * $perKm, 2) : null,
            ],
        ]);
    }

    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(self::ORDER_SOURCES)],
            'order_id' => 'required|integer|min:1',
            'driver_user_id' => 'required|exists:users,id',
            'courier_contact' => 'required|string|max:50',
            'vehicle_id' => 'required|exists:ecommerce_delivery_vehicles,id',
            'distance_km' => 'nullable|numeric|min:0',
            'per_km_charge' => 'required|numeric|min:0',
            'base_fee' => 'nullable|numeric|min:0',
            'per_kg_fee' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'zone_id' => 'nullable|integer|min:1',
            'zone_rate_id' => 'nullable|integer|min:1',
            'zone_name' => 'nullable|string|max:120',
            'estimated_delivery_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $storeId = $this->resolveStoreId($request);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $driver = User::query()
            ->where('id', (int) $validated['driver_user_id'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->firstOrFail();

        if (!$driver->hasAnyPermission(['logistics.deliveries.view', 'logistics.deliveries.manage'], $storeId)) {
            return response()->json(['success' => false, 'message' => 'Selected employee does not have logistics permissions.'], 422);
        }

        $vehicle = EcommerceDeliveryVehicle::query()
            ->where('id', (int) $validated['vehicle_id'])
            ->where('store_id', $storeId)
            ->firstOrFail();

        $distance = isset($validated['distance_km']) ? (float) $validated['distance_km'] : 0;
        $perKmCharge = (float) $validated['per_km_charge'];
        $baseFee = isset($validated['base_fee']) ? (float) $validated['base_fee'] : 0.0;
        $perKgFee = isset($validated['per_kg_fee']) ? (float) $validated['per_kg_fee'] : 0.0;
        $weightKg = isset($validated['weight_kg']) ? (float) $validated['weight_kg'] : 0.0;
        $estimatedFee = round($baseFee + ($distance * $perKmCharge) + ($weightKg * $perKgFee), 2);
        $zoneLabel = trim((string) ($validated['zone_name'] ?? ''));

        if ($validated['source_type'] === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, (int) $validated['order_id'], withDelivery: true);

            $delivery = $order->delivery;
            if ($delivery && strtolower((string) $delivery->status) !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Delivery already assigned for this order.'], 422);
            }

            $overrideFee = $order->shipping_fee !== null ? (float) $order->shipping_fee : null;
            $finalEstimatedFee = is_null($overrideFee) ? $estimatedFee : round($overrideFee, 2);

            if ($delivery) {
                $delivery->fill([
                    'vehicle_id' => $vehicle->id,
                    'driver_user_id' => $driver->id,
                    'tracking_number' => $delivery->tracking_number ?: $this->nextTrackingNumber(),
                    'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                    'courier_contact' => (string) $validated['courier_contact'],
                    'status' => 'assigned',
                    'estimated_delivery_at' => $validated['estimated_delivery_at'] ?? null,
                    'distance_km' => $distance,
                    'per_km_charge' => $perKmCharge,
                    'estimated_fee' => $finalEstimatedFee,
                    'notes' => $this->composeAssignmentNotes(
                        $validated['notes'] ?? null,
                        $distance,
                        $perKmCharge,
                        $finalEstimatedFee,
                        $vehicle,
                        $baseFee,
                        $perKgFee,
                        $weightKg,
                        $zoneLabel,
                        $validated['zone_rate_id'] ?? null
                    ),
                    'updated_by' => $request->user()->id,
                ]);
                $delivery->save();
            } else {
                $delivery = EcommerceOrderDelivery::query()->create([
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'vehicle_id' => $vehicle->id,
                    'driver_user_id' => $driver->id,
                    'tracking_number' => $this->nextTrackingNumber(),
                    'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                    'courier_contact' => (string) $validated['courier_contact'],
                    'status' => 'assigned',
                    'estimated_delivery_at' => $validated['estimated_delivery_at'] ?? null,
                    'distance_km' => $distance,
                    'per_km_charge' => $perKmCharge,
                    'estimated_fee' => $finalEstimatedFee,
                    'notes' => $this->composeAssignmentNotes(
                        $validated['notes'] ?? null,
                        $distance,
                        $perKmCharge,
                        $finalEstimatedFee,
                        $vehicle,
                        $baseFee,
                        $perKgFee,
                        $weightKg,
                        $zoneLabel,
                        $validated['zone_rate_id'] ?? null
                    ),
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }

            EcommerceDeliveryLog::query()->create([
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'event_type' => 'created',
                'status_to' => 'assigned',
                'message' => 'Delivery assigned from logistics.',
                'meta' => [
                    'distance_km' => $distance,
                    'per_km_charge' => $perKmCharge,
                    'estimated_fee' => $estimatedFee,
                    'vehicle' => [
                        'id' => $vehicle->id,
                        'name' => $vehicle->vehicle_name,
                        'plate_number' => $vehicle->plate_number,
                    ],
                ],
                'created_by' => $request->user()->id,
            ]);

            if (in_array((string) $order->status, self::PENDING_ORDER_STATUSES, true)) {
                $order->status = 'ready_for_dispatch';
                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Delivery assigned successfully.',
                'data' => [
                    'source_type' => 'ecommerce',
                    'order_id' => $order->id,
                    'delivery' => $delivery,
                ],
            ], 201);
        }

        $order = $this->resolveSalesOrder($request, (int) $validated['order_id'], withDelivery: true);

        $delivery = $order->delivery;
        if ($delivery && strtolower((string) $delivery->status) !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Delivery already assigned for this order.'], 422);
        }

        if ($delivery) {
            $delivery->fill([
                'driver_user_id' => $driver->id,
                'tracking_number' => $delivery->tracking_number ?: $this->nextTrackingNumber(),
                'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                'courier_contact' => (string) $validated['courier_contact'],
                'status' => 'assigned',
                'scheduled_delivery_at' => $validated['estimated_delivery_at'] ?? null,
                    'distance_km' => $distance,
                    'per_km_charge' => $perKmCharge,
                    'estimated_fee' => $estimatedFee,
                    'notes' => $this->composeAssignmentNotes(
                        $validated['notes'] ?? null,
                        $distance,
                        $perKmCharge,
                        $estimatedFee,
                        $vehicle,
                        $baseFee,
                        $perKgFee,
                        $weightKg,
                        $zoneLabel,
                        $validated['zone_rate_id'] ?? null
                    ),
                    'updated_by' => $request->user()->id,
                ]);
            $delivery->save();
        } else {
            $delivery = SalesOrderDelivery::query()->create([
                'sales_order_id' => $order->id,
                'store_id' => $order->store_id,
                'branch_id' => $order->branch_id,
                'driver_user_id' => $driver->id,
                'tracking_number' => $this->nextTrackingNumber(),
                'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                'courier_contact' => (string) $validated['courier_contact'],
                'status' => 'assigned',
                'scheduled_delivery_at' => $validated['estimated_delivery_at'] ?? null,
                'distance_km' => $distance,
                'per_km_charge' => $perKmCharge,
                'estimated_fee' => $finalEstimatedFee,
                'notes' => $this->composeAssignmentNotes(
                    $validated['notes'] ?? null,
                    $distance,
                    $perKmCharge,
                    $estimatedFee,
                    $vehicle,
                    $baseFee,
                    $perKgFee,
                    $weightKg,
                    $zoneLabel,
                    $validated['zone_rate_id'] ?? null
                ),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        }

        SalesOrderDeliveryLog::query()->create([
            'delivery_id' => $delivery->id,
            'sales_order_id' => $order->id,
            'store_id' => $order->store_id,
            'event_type' => 'created',
            'status_to' => 'assigned',
            'message' => 'Delivery assigned from logistics.',
            'meta' => [
                'distance_km' => $distance,
                'per_km_charge' => $perKmCharge,
                'estimated_fee' => $finalEstimatedFee,
                'vehicle' => [
                    'id' => $vehicle->id,
                    'name' => $vehicle->vehicle_name,
                    'plate_number' => $vehicle->plate_number,
                ],
            ],
            'created_by' => $request->user()->id,
        ]);

        if ((string) $order->status === 'pending_payment') {
            $order->status = 'completed';
            $order->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery assigned successfully.',
            'data' => [
                'source_type' => 'sales',
                'order_id' => $order->id,
                'delivery' => $delivery,
            ],
        ], 201);
    }

    public function updateStatus(Request $request, string $source, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::DELIVERY_STATUSES)],
            'failed_reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $source = strtolower($source);

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            $delivery = $order->delivery;
            if (!$delivery) {
                return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
            }

            $from = (string) $delivery->status;
            $delivery->status = $validated['status'];
            $delivery->failed_reason = $validated['failed_reason'] ?? $delivery->failed_reason;
            $delivery->notes = $validated['notes'] ?? $delivery->notes;
            $delivery->updated_by = $request->user()->id;
            if ($validated['status'] === 'in_transit' && !$delivery->dispatched_at) {
                $delivery->dispatched_at = now();
            }
            if ($validated['status'] === 'out_for_delivery' && !$delivery->out_for_delivery_at) {
                $delivery->out_for_delivery_at = now();
            }
            if ($validated['status'] === 'delivered' && !$delivery->delivered_at) {
                $delivery->delivered_at = now();
            }
            $delivery->save();

            EcommerceDeliveryLog::query()->create([
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'event_type' => 'status_updated',
                'status_from' => $from,
                'status_to' => $validated['status'],
                'message' => "Delivery status updated from {$from} to {$validated['status']}",
                'created_by' => $request->user()->id,
            ]);

            if ($validated['status'] === 'delivered') {
                $order->status = 'delivered';
                $order->save();
            }

            return response()->json(['success' => true, 'message' => 'Delivery status updated.']);
        }

        $order = $this->resolveSalesOrder($request, $orderId, withDelivery: true);
        $delivery = $order->delivery;
        if (!$delivery) {
            return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
        }

        $from = (string) $delivery->status;
        $delivery->status = $validated['status'];
        $delivery->failed_reason = $validated['failed_reason'] ?? $delivery->failed_reason;
        $delivery->notes = $validated['notes'] ?? $delivery->notes;
        $delivery->updated_by = $request->user()->id;
        if ($validated['status'] === 'in_transit' && !$delivery->dispatched_at) {
            $delivery->dispatched_at = now();
        }
        if ($validated['status'] === 'out_for_delivery' && !$delivery->out_for_delivery_at) {
            $delivery->out_for_delivery_at = now();
        }
        if ($validated['status'] === 'delivered' && !$delivery->delivered_at) {
            $delivery->delivered_at = now();
        }
        $delivery->save();

        SalesOrderDeliveryLog::query()->create([
            'delivery_id' => $delivery->id,
            'sales_order_id' => $order->id,
            'store_id' => $order->store_id,
            'event_type' => 'status_updated',
            'status_from' => $from,
            'status_to' => $validated['status'],
            'message' => "Delivery status updated from {$from} to {$validated['status']}",
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery status updated.']);
    }

    public function delivered(Request $request, string $source, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'photo' => 'required|image|max:5120',
            'signature' => 'required|image|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        $source = strtolower($source);

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            $delivery = $order->delivery;
            if (!$delivery) {
                return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
            }

            $previousStatus = (string) $delivery->status;
            $delivery->proof_of_delivery_path = $request->file('photo')->store('logistics/pod/photos', 'public');
            $delivery->proof_signature_path = $request->file('signature')->store('logistics/pod/signatures', 'public');
            $delivery->status = 'delivered';
            $delivery->delivered_at = $delivery->delivered_at ?: now();
            $delivery->updated_by = $request->user()->id;
            if (!empty($validated['notes'])) {
                $delivery->notes = trim((string) $validated['notes']);
            }
            $delivery->save();

            $order->status = 'delivered';
            $order->save();

            EcommerceDeliveryLog::query()->create([
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'event_type' => 'proof_uploaded',
                'status_from' => $previousStatus,
                'status_to' => 'delivered',
                'message' => 'Proof of delivery uploaded and marked as delivered.',
                'meta' => [
                    'proof_photo_url' => $this->publicUrl($delivery->proof_of_delivery_path),
                    'proof_signature_url' => $this->publicUrl($delivery->proof_signature_path),
                ],
                'created_by' => $request->user()->id,
            ]);

            return response()->json(['success' => true, 'message' => 'Delivery marked as delivered.']);
        }

        $order = $this->resolveSalesOrder($request, $orderId, withDelivery: true);
        $delivery = $order->delivery;
        if (!$delivery) {
            return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
        }

        $previousStatus = (string) $delivery->status;
        $delivery->proof_of_delivery_path = $request->file('photo')->store('logistics/pod/photos', 'public');
        $delivery->proof_signature_path = $request->file('signature')->store('logistics/pod/signatures', 'public');
        $delivery->status = 'delivered';
        $delivery->delivered_at = $delivery->delivered_at ?: now();
        $delivery->updated_by = $request->user()->id;
        if (!empty($validated['notes'])) {
            $delivery->notes = trim((string) $validated['notes']);
        }
        $delivery->save();

        SalesOrderDeliveryLog::query()->create([
            'delivery_id' => $delivery->id,
            'sales_order_id' => $order->id,
            'store_id' => $order->store_id,
            'event_type' => 'proof_uploaded',
            'status_from' => $previousStatus,
            'status_to' => 'delivered',
            'message' => 'Proof of delivery uploaded and marked as delivered.',
            'meta' => [
                'proof_photo_url' => $this->publicUrl($delivery->proof_of_delivery_path),
                'proof_signature_url' => $this->publicUrl($delivery->proof_signature_path),
            ],
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery marked as delivered.']);
    }

    public function logs(Request $request, string $source, int $orderId): JsonResponse
    {
        $source = strtolower($source);

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            if (!$order->delivery) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $logs = EcommerceDeliveryLog::query()
                ->with('creator:id,fname,lname,email')
                ->where('delivery_id', $order->delivery->id)
                ->orderByDesc('created_at')
                ->get();

            return response()->json(['success' => true, 'data' => $logs]);
        }

        $order = $this->resolveSalesOrder($request, $orderId, withDelivery: true);
        if (!$order->delivery) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $logs = SalesOrderDeliveryLog::query()
            ->with('creator:id,fname,lname,email')
            ->where('delivery_id', $order->delivery->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function addLog(Request $request, string $source, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $source = strtolower($source);

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            if (!$order->delivery) {
                return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
            }
            if (!in_array(strtolower((string) $order->delivery->status), ['in_transit', 'out_for_delivery'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only record logs when delivery is In Transit or Out For Delivery.',
                ], 422);
            }

            $log = EcommerceDeliveryLog::query()->create([
                'delivery_id' => $order->delivery->id,
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'event_type' => 'note',
                'message' => (string) $validated['message'],
                'created_by' => $request->user()->id,
            ]);

            return response()->json(['success' => true, 'data' => $log->load('creator:id,fname,lname,email')], 201);
        }

        $order = $this->resolveSalesOrder($request, $orderId, withDelivery: true);
        if (!$order->delivery) {
            return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
        }
        if (!in_array(strtolower((string) $order->delivery->status), ['in_transit', 'out_for_delivery'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only record logs when delivery is In Transit or Out For Delivery.',
            ], 422);
        }

        $log = SalesOrderDeliveryLog::query()->create([
            'delivery_id' => $order->delivery->id,
            'sales_order_id' => $order->id,
            'store_id' => $order->store_id,
            'event_type' => 'note',
            'message' => (string) $validated['message'],
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'data' => $log->load('creator:id,fname,lname,email')], 201);
    }

    private function getEcommerceOrderRows(Request $request): Collection
    {
        $query = EcommerceOrder::query()
            ->with([
                'assignedBranch:id,name,latitude,longitude',
                'delivery:id,order_id,tracking_number,status,driver_user_id,vehicle_id,created_at,updated_at',
                'items:id,order_id,product_id,quantity',
                'items.product:id,weight_kg',
            ])
            ->select([
                'id',
                'store_id',
                'assigned_branch_id',
                'order_number',
                'status',
                'shipping_name',
                'shipping_phone',
                'shipping_address',
                'customer_latitude',
                'customer_longitude',
                'total_amount',
                'created_at',
            ]);

        $this->applyEcommerceTenantScope($request, $query);

        return $query->get()->map(function (EcommerceOrder $order): array {
            $delivery = $order->delivery;
            $status = strtolower((string) $order->status);
            $deliveryStatus = strtolower((string) ($delivery?->status ?? ''));
            $canCreate = in_array($status, self::PENDING_ORDER_STATUSES, true)
                && (!$delivery || $deliveryStatus === 'pending');

            return [
                'id' => 'ecommerce-' . $order->id,
                'source_type' => 'ecommerce',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
                'customer_name' => $order->shipping_name,
                'customer_contact' => $order->shipping_phone,
                'delivery_address' => $order->shipping_address,
                'destination_latitude' => $order->customer_latitude,
                'destination_longitude' => $order->customer_longitude,
                'origin_latitude' => $order->assignedBranch?->latitude,
                'origin_longitude' => $order->assignedBranch?->longitude,
                'branch_name' => $order->assignedBranch?->name,
                'total_amount' => $order->total_amount,
                'delivery_id' => $delivery?->id,
                'delivery_status' => $canCreate ? 'ready_for_dispatch' : $delivery?->status,
                'tracking_number' => $delivery?->tracking_number,
                'can_create_delivery' => $canCreate,
                'weight_kg' => $this->orderItemsWeight($order->items ?? []),
                'created_at' => $order->created_at,
            ];
        });
    }

    private function getSalesOrderRows(Request $request): Collection
    {
        $query = SalesOrder::query()
            ->with([
                'branch:id,name,latitude,longitude',
                'delivery:id,sales_order_id,tracking_number,status,driver_user_id,created_at,updated_at',
                'items:id,order_id,product_id,quantity',
                'items.product:id,weight_kg',
            ])
            ->select([
                'id',
                'store_id',
                'branch_id',
                'order_number',
                'status',
                'payment_status',
                'delivery_required',
                'customer_name',
                'customer_phone',
                'delivery_address',
                'delivery_latitude',
                'delivery_longitude',
                'total_amount',
                'created_at',
            ])
            ->where('delivery_required', true);

        $this->applySalesTenantScope($request, $query);

        return $query->get()->map(function (SalesOrder $order): array {
            $delivery = $order->delivery;
            $deliveryStatus = strtolower((string) ($delivery?->status ?? ''));
            $canCreate = (bool) $order->delivery_required
                && (!$delivery || $deliveryStatus === 'pending');

            return [
                'id' => 'sales-' . $order->id,
                'source_type' => 'sales',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
                'customer_name' => $order->customer_name,
                'customer_contact' => $order->customer_phone,
                'delivery_address' => $order->delivery_address,
                'destination_latitude' => $order->delivery_latitude,
                'destination_longitude' => $order->delivery_longitude,
                'origin_latitude' => $order->branch?->latitude,
                'origin_longitude' => $order->branch?->longitude,
                'branch_name' => $order->branch?->name,
                'total_amount' => $order->total_amount,
                'delivery_id' => $delivery?->id,
                'delivery_status' => $canCreate ? 'ready_for_dispatch' : $delivery?->status,
                'tracking_number' => $delivery?->tracking_number,
                'can_create_delivery' => $canCreate,
                'weight_kg' => $this->orderItemsWeight($order->items ?? []),
                'created_at' => $order->created_at,
            ];
        });
    }

    private function resolveStoreId(Request $request): ?int
    {
        $user = $request->user();

        if (!$user->hasRole('super_admin')) {
            return $user->store_id ? (int) $user->store_id : null;
        }

        if ($request->filled('store_id')) {
            return (int) $request->input('store_id');
        }

        return $user->store_id ? (int) $user->store_id : null;
    }

    private function resolveBranchId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->hasRole('super_admin') && $request->filled('branch_id')) {
            return (int) $request->input('branch_id');
        }

        return $user->employee?->branch_id ? (int) $user->employee->branch_id : null;
    }

    private function applyEcommerceTenantScope(Request $request, $query): void
    {
        $storeId = $this->resolveStoreId($request);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $branchId = $this->resolveBranchId($request);
        if ($branchId) {
            $query->where(function ($builder) use ($branchId) {
                // Include orders already assigned to the branch and orders pending branch assignment.
                $builder->where('assigned_branch_id', $branchId)
                    ->orWhereNull('assigned_branch_id');
            });
        } elseif ($request->user()->hasRole('super_admin') && $request->filled('branch_id')) {
            $query->where('assigned_branch_id', (int) $request->input('branch_id'));
        }
    }

    private function applySalesTenantScope(Request $request, $query): void
    {
        $storeId = $this->resolveStoreId($request);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $branchId = $this->resolveBranchId($request);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($request->user()->hasRole('super_admin') && $request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
    }

    private function resolveEcommerceOrder(Request $request, int $orderId, bool $withDelivery = false): EcommerceOrder
    {
        $query = EcommerceOrder::query();
        if ($withDelivery) {
            $query->with([
                'assignedBranch:id,name,latitude,longitude',
                'items:id,order_id,product_id,product_name,sku,quantity,unit_price,line_total',
                'items.product:id,product_name,sku,weight_kg',
                'delivery',
            ]);
        }

        $this->applyEcommerceTenantScope($request, $query);

        return $query->findOrFail($orderId);
    }

    private function resolveSalesOrder(Request $request, int $orderId, bool $withDelivery = false): SalesOrder
    {
        $query = SalesOrder::query();
        if ($withDelivery) {
            $query->with([
                'branch:id,name,latitude,longitude',
                'items:id,order_id,product_id,product_name,sku,quantity,unit_price,line_total',
                'items.product:id,product_name,sku,weight_kg',
                'delivery',
            ]);
        }

        $this->applySalesTenantScope($request, $query);

        return $query->findOrFail($orderId);
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

    private function composeAssignmentNotes(
        ?string $notes,
        float $distance,
        float $perKmCharge,
        float $estimatedFee,
        EcommerceDeliveryVehicle $vehicle,
        float $baseFee = 0,
        float $perKgFee = 0,
        float $weightKg = 0,
        string $zoneLabel = '',
        ?int $zoneRateId = null
    ): string
    {
        $parts = [];
        if (!empty($notes)) {
            $parts[] = trim($notes);
        }

        if ($zoneLabel !== '') {
            $rateLabel = $zoneRateId ? "Rate #{$zoneRateId}" : 'Rate applied';
            $parts[] = sprintf('Zone: %s (%s)', $zoneLabel, $rateLabel);
        }

        if ($baseFee > 0 || $perKgFee > 0 || $weightKg > 0) {
            $parts[] = sprintf('Base Fee: %.2f | Weight: %.2f kg | Per KG: %.2f', $baseFee, $weightKg, $perKgFee);
        }

        $parts[] = sprintf(
            'Vehicle: %s (%s) | Distance: %.2f km | Per KM: %.2f | Estimated Fee: %.2f',
            $vehicle->vehicle_name,
            $vehicle->plate_number,
            $distance,
            $perKmCharge,
            $estimatedFee
        );

        return implode("\n", $parts);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function orderItemsWeight($items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $weight = (float) ($item->product?->weight_kg ?? 0);
            $total += $weight * (int) ($item->quantity ?? 0);
        }
        return $total;
    }

    private function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
