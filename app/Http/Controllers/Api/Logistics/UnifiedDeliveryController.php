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
use App\Models\Procurement\Shipping\PurchaseOrderShipment;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLog;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLogAttachment;
use App\Services\Sales\OrderCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnifiedDeliveryController extends Controller
{
    public function __construct(
        private readonly OrderCommissionService $commissionService
    ) {
    }

    private const DELIVERY_STATUSES = [
        'assigned',
        'packed',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'failed_delivery',
        'cancelled',
    ];

    private const ORDER_SOURCES = ['ecommerce', 'sales', 'pickup'];

    // Ecommerce orders must be reviewed by Sales before Logistics can dispatch them.
    private const PENDING_ORDER_STATUSES = ['ready_for_dispatch'];

    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $isDriver = (string) ($user?->role?->name ?? $user?->role_name ?? '') === 'driver';
        $source = (string) $request->input('source', 'all');
        $search = trim((string) $request->input('search', ''));
        $statusFilter = strtolower((string) $request->input('status', ''));
        $assignedDriverOnly = $request->boolean('assigned_driver_only') || $isDriver;

        $rows = collect();

        if (in_array($source, ['all', 'ecommerce'], true)) {
            $rows = $rows->merge($this->getEcommerceOrderRows($request));
        }

        if (in_array($source, ['all', 'sales'], true)) {
            $rows = $rows->merge($this->getSalesOrderRows($request));
        }

        if (in_array($source, ['all', 'pickup'], true)) {
            $rows = $rows->merge($this->getPickupSupplyRows($request));
        }

        if ($assignedDriverOnly) {
            $driverId = (int) $request->user()->id;
            $rows = $rows->filter(fn (array $row) => (int) ($row['driver_user_id'] ?? $row['driver_id'] ?? 0) === $driverId);
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

    private function getPickupSupplyRows(Request $request): Collection
    {
        return PurchaseOrderShipment::with(['purchaseOrder.supplier', 'purchaseOrder.branch', 'purchaseOrder.items.product'])
            ->whereHas('purchaseOrder', fn ($query) => $query->where('store_id', $request->user()->store_id))
            ->get()
            ->map(function (PurchaseOrderShipment $pickup): array {
                $po = $pickup->purchaseOrder;
                return [
                    'source_type' => 'pickup',
                    'order_id' => $po?->id,
                    'order_number' => $po?->po_number ?? ('PO-' . $pickup->purchase_order_id),
                    'branch_name' => $po?->branch?->name ?? $po?->branch?->branch_name,
                    'customer_name' => $po?->supplier?->supplier_name ?? 'Supplier',
                    'customer_contact' => $pickup->driver_contact,
                    'delivery_address' => $pickup->origin_address ?? $po?->supplier?->address,
                    'order_status' => $po?->status,
                    'delivery_status' => $pickup->status ?: 'pending',
                    'total_amount' => $po?->total_amount ?? 0,
                    'created_at' => $pickup->created_at,
                    'delivery_date' => $pickup->delivered_at ?? $pickup->created_at,
                    'expected_pickup_date' => $pickup->expected_delivery_date,
                    'quantity_items' => $this->orderItemsQuantity($po?->items ?? []),
                    'weight_kg' => $this->orderItemsWeight($po?->items ?? []),
                    'pickup_id' => $pickup->id,
                    'driver_user_id' => $pickup->driver_user_id,
                    'driver_employee_id' => $pickup->driver_employee_id,
                    'vehicle_id' => $pickup->vehicle_id,
                ];
            });
    }

    public function orderDetail(Request $request, string $source, int $orderId): JsonResponse
    {
        $source = strtolower($source);
        if (!in_array($source, self::ORDER_SOURCES, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid source type.'], 422);
        }

        if ($source === 'pickup') {
            $pickup = $this->resolvePickupShipment($request, $orderId);

            return response()->json([
                'success' => true,
                'data' => [
                    'source_type' => 'pickup',
                    'order' => $pickup->purchaseOrder,
                    'delivery' => $pickup,
                    'logs' => $pickup->logs,
                ],
            ]);
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

            $deliveryData = $delivery ? $this->monitoringDeliveryData($delivery, $request, $order->id, 'ecommerce') : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'source_type' => 'ecommerce',
                    'order' => $order,
                    'delivery' => $deliveryData,
                    'logs' => $this->monitoringLogs($logs, $deliveryData),
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
        $deliveryData = $delivery ? $this->monitoringDeliveryData($delivery, $request, $order->id, 'sales') : null;

        return response()->json([
            'success' => true,
            'data' => [
                'source_type' => 'sales',
                'order' => $order,
                'delivery' => $deliveryData,
                'logs' => $this->monitoringLogs($logs, $deliveryData),
            ],
        ]);
    }

    public function serveProof(Request $request, string $source, int $orderId, string $kind): StreamedResponse|JsonResponse
    {
        $source = strtolower($source);
        $kind = strtolower($kind);
        if (!in_array($source, self::ORDER_SOURCES, true) || !in_array($kind, ['photo', 'signature'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid proof request.'], 422);
        }

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            $delivery = $order->delivery;
        } else {
            $order = $this->resolveSalesOrder($request, $orderId, withDelivery: true);
            $delivery = $order->delivery;
        }

        if (!$delivery) {
            return response()->json(['success' => false, 'message' => 'Delivery not found.'], 404);
        }

        $rawPath = $kind === 'photo'
            ? (string) ($delivery->proof_of_delivery_path ?? '')
            : (string) ($delivery->proof_signature_path ?? '');

        $resolved = $this->resolveReadableDocumentPath($rawPath);
        if (!$resolved) {
            return response()->json(['success' => false, 'message' => 'Proof file not found.'], 404);
        }

        [$driver, $path] = $resolved;
        if ($driver === 'absolute') {
            return response()->file($path);
        }

        return Storage::disk($driver)->response($path, basename($path));
    }

    public function logisticsEmployees(Request $request): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);
        if (!$storeId) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $branchId = $this->resolveBranchId($request);

        $employees = User::query()
            ->with(['role:id,name,display_name', 'employee:id,user_id,branch_id,role_id,employee_number,status', 'employee.branch:id,name', 'employee.role:id,name,display_name'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->whereHas('employee', fn ($query) => $query->where('status', 'active'))
            ->orderBy('fname')
            ->orderBy('lname')
            ->get()
            ->map(fn (User $employee) => [
                'id' => $employee->id,
                'name' => trim(($employee->fname ?? '') . ' ' . ($employee->lname ?? '')),
                'email' => $employee->email,
                'contact' => $employee->phone_number,
                'branch_id' => $employee->employee?->branch_id,
                'branch' => $employee->employee?->branch?->name ?? 'No branch assigned',
                'employee_number' => $employee->employee?->employee_number,
                'role' => $employee->role?->display_name ?? $employee->role?->name ?? 'N/A',
                'is_driver' => strtolower((string) ($employee->role?->name ?? '')) === 'driver'
                    || strtolower((string) ($employee->employee?->role?->name ?? '')) === 'driver',
                'is_same_branch' => $branchId && (int) $employee->employee?->branch_id === $branchId,
            ])
            ->sortBy(fn (array $employee) => [$employee['is_same_branch'] ? 0 : 1, strtolower($employee['name'])])
            ->values();

        return response()->json(['success' => true, 'data' => [
            'drivers' => $employees->where('is_driver', true)->values(),
            'assistants' => $employees->where('is_driver', true)->values(),
        ]]);
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
            'assistant_user_ids' => 'nullable|array',
            'assistant_user_ids.*' => 'integer|distinct|exists:users,id',
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
            ->with(['role:id,name', 'employee:id,user_id,role_id,status', 'employee.role:id,name'])
            ->where('id', (int) $validated['driver_user_id'])
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->firstOrFail();

        $isDriver = strtolower((string) $driver->role?->name) === 'driver'
            || strtolower((string) $driver->employee?->role?->name) === 'driver';
        if (!$isDriver || $driver->employee?->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Please select an active employee with the Driver role.'], 422);
        }

        $assistantIds = collect($validated['assistant_user_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($assistantIds->contains($driver->id)) {
            return response()->json(['success' => false, 'message' => 'The selected driver cannot also be a delivery assistant.'], 422);
        }
        $validAssistantCount = User::query()->where('store_id', $storeId)->where('is_active', true)
            ->whereIn('id', $assistantIds)
            ->whereHas('employee', fn ($query) => $query->where('status', 'active'))
            ->where(function ($query) {
                $query->whereHas('role', fn ($role) => $role->where('name', 'driver'))
                    ->orWhereHas('employee.role', fn ($role) => $role->where('name', 'driver'));
            })
            ->count();
        if ($validAssistantCount !== $assistantIds->count()) {
            return response()->json(['success' => false, 'message' => 'One or more delivery assistants are invalid.'], 422);
        }
        $driverContact = (string) ($driver->phone_number ?? '');
        $ecommerceAssistantData = Schema::hasColumn('ecommerce_order_deliveries', 'assistant_user_ids')
            ? ['assistant_user_ids' => $assistantIds->all()]
            : [];
        $salesAssistantData = Schema::hasColumn('order_deliveries', 'assistant_user_ids')
            ? ['assistant_user_ids' => $assistantIds->all()]
            : [];

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
                $delivery->fill(array_merge([
                    'vehicle_id' => $vehicle->id,
                    'driver_user_id' => $driver->id,
                    'tracking_number' => $delivery->tracking_number ?: $this->nextTrackingNumber(),
                    'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                    'courier_contact' => $driverContact,
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
                ], $ecommerceAssistantData));
                $delivery->save();
            } else {
                $delivery = EcommerceOrderDelivery::query()->create(array_merge([
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'vehicle_id' => $vehicle->id,
                    'driver_user_id' => $driver->id,
                    'tracking_number' => $this->nextTrackingNumber(),
                    'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                    'courier_contact' => $driverContact,
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
                ], $ecommerceAssistantData));
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
        $overrideFee = $order->shipping_fee !== null ? (float) $order->shipping_fee : null;
        $finalEstimatedFee = is_null($overrideFee) ? $estimatedFee : round($overrideFee, 2);

        $delivery = $order->delivery;
        if ($delivery && strtolower((string) $delivery->status) !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Delivery already assigned for this order.'], 422);
        }

        if ($delivery) {
            $delivery->fill(array_merge([
                'driver_user_id' => $driver->id,
                'tracking_number' => $delivery->tracking_number ?: $this->nextTrackingNumber(),
                'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                'courier_contact' => $driverContact,
                'status' => 'assigned',
                'scheduled_delivery_at' => $validated['estimated_delivery_at'] ?? null,
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
            ], $salesAssistantData));
            $delivery->save();
        } else {
            $delivery = SalesOrderDelivery::query()->create(array_merge([
                'sales_order_id' => $order->id,
                'store_id' => $order->store_id,
                'branch_id' => $order->branch_id,
                'driver_user_id' => $driver->id,
                'tracking_number' => $this->nextTrackingNumber(),
                'courier_name' => trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? '')),
                'courier_contact' => $driverContact,
                'status' => 'assigned',
                'scheduled_delivery_at' => $validated['estimated_delivery_at'] ?? null,
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
            ], $salesAssistantData));
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
        $source = strtolower($source);
        $targetStatus = strtolower((string) $request->input('status'));
        $proofRequired = in_array($targetStatus, ['in_transit', 'delivered'], true)
            && in_array($source, ['pickup', 'ecommerce'], true);
        $locationRequired = $proofRequired
            || ($source === 'ecommerce' && $targetStatus === 'out_for_delivery');

        $validated = $request->validate([
            'status' => ['required', Rule::in(self::DELIVERY_STATUSES)],
            'failed_reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'latitude' => $locationRequired ? 'required|numeric|between:-90,90' : 'nullable|numeric|between:-90,90',
            'longitude' => $locationRequired ? 'required|numeric|between:-180,180' : 'nullable|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:2000',
            'photo' => $proofRequired ? 'required|image|max:8192' : 'nullable|image|max:8192',
        ]);

        if ($source === 'pickup') {
            if (!in_array($validated['status'], ['in_transit', 'delivered', 'cancelled'], true)) {
                return response()->json(['success' => false, 'message' => 'Invalid supplier pickup status.'], 422);
            }

            $pickup = $this->resolvePickupShipment($request, $orderId);
            $from = (string) $pickup->status;
            $allowedTransitions = [
                'pending' => ['in_transit', 'cancelled'],
                'in_transit' => ['delivered', 'cancelled'],
            ];
            if (!in_array($validated['status'], $allowedTransitions[$from] ?? [], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Supplier pickup cannot move from {$from} to {$validated['status']}.",
                ], 422);
            }

            $locationAddress = trim((string) ($validated['location_address'] ?? ''));
            if ($proofRequired && $locationAddress === '') {
                $locationAddress = $this->reverseGeocode((float) $validated['latitude'], (float) $validated['longitude']);
            }
            if ($proofRequired && !$locationAddress) {
                $locationAddress = $validated['status'] === 'delivered'
                    ? ($pickup->destination_address ?: $pickup->purchaseOrder?->branch?->address)
                    : ($pickup->origin_address ?: $pickup->purchaseOrder?->supplier?->address);
            }
            $pickup->status = $validated['status'];
            $pickup->current_latitude = $validated['latitude'] ?? $pickup->current_latitude;
            $pickup->current_longitude = $validated['longitude'] ?? $pickup->current_longitude;
            $pickup->current_address = $locationAddress ?: $pickup->current_address;
            if ($validated['status'] === 'in_transit' && !$pickup->dispatched_at) {
                $pickup->dispatched_at = now();
            }
            if ($validated['status'] === 'delivered' && !$pickup->delivered_at) {
                $pickup->delivered_at = now();
            }
            $pickup->save();

            if ($validated['status'] === 'in_transit') {
                $pickup->purchaseOrder?->markInTransit();
            } elseif ($validated['status'] === 'delivered') {
                $pickup->purchaseOrder?->markDelivered();
            }

            $log = PurchaseOrderDeliveryLog::create([
                'shipment_id' => $pickup->id,
                'created_by' => $request->user()->id,
                'event_type' => $validated['status'],
                'notes' => $validated['notes'] ?? "Pickup status updated from {$from} to {$validated['status']}.",
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'location_address' => $locationAddress ?: null,
                'logged_at' => now(),
            ]);

            if ($photo = $request->file('photo')) {
                $path = $photo->store('procurement/pickup-proofs', 'public');
                PurchaseOrderDeliveryLogAttachment::create([
                    'delivery_log_id' => $log->id,
                    'file_path' => $path,
                    'mime_type' => (string) $photo->getMimeType(),
                    'size' => (int) $photo->getSize(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Supplier pickup status updated.', 'data' => $pickup->fresh()->load('logs.attachments')]);
        }

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            $delivery = $order->delivery;
            if (!$delivery) {
                return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
            }
            if ($request->user()->hasRole('driver') && (int) $delivery->driver_user_id !== (int) $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'This delivery is not assigned to you.'], 403);
            }

            $from = (string) $delivery->status;
            if ($from === $validated['status']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery is already in this status.',
                    'data' => $delivery->fresh(['vehicle']),
                ]);
            }
            $allowedTransitions = [
                'pending' => ['assigned', 'packed', 'in_transit', 'cancelled'],
                'ready_for_dispatch' => ['assigned', 'packed', 'in_transit', 'cancelled'],
                'assigned' => ['packed', 'in_transit', 'cancelled'],
                'packed' => ['in_transit', 'out_for_delivery', 'cancelled'],
                'shipped' => ['in_transit', 'out_for_delivery', 'cancelled'],
                'in_transit' => ['out_for_delivery', 'cancelled'],
                'out_for_delivery' => ['delivered', 'cancelled'],
            ];
            if (!in_array($validated['status'], $allowedTransitions[$from] ?? [], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Delivery cannot move from {$from} to {$validated['status']}.",
                ], 422);
            }

            $locationAddress = trim((string) ($validated['location_address'] ?? ''));
            if (isset($validated['latitude'], $validated['longitude']) && $locationAddress === '') {
                $locationAddress = $this->reverseGeocode((float) $validated['latitude'], (float) $validated['longitude']) ?: '';
            }
            $proofPath = null;
            if ($photo = $request->file('photo')) {
                $proofPath = $photo->store('logistics/ecommerce/status-proofs', 'public');
            }

            $delivery->status = $validated['status'];
            $delivery->failed_reason = $validated['failed_reason'] ?? $delivery->failed_reason;
            $delivery->notes = $validated['notes'] ?? $delivery->notes;
            if (Schema::hasColumn('ecommerce_order_deliveries', 'current_latitude')) {
                $delivery->current_latitude = $validated['latitude'] ?? $delivery->current_latitude;
                $delivery->current_longitude = $validated['longitude'] ?? $delivery->current_longitude;
                $delivery->current_address = $locationAddress ?: $delivery->current_address;
            }
            if ($validated['status'] === 'delivered' && $proofPath) {
                $delivery->proof_of_delivery_path = $proofPath;
            }
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
                // Keep event_type compatible with the ecommerce delivery log enum;
                // the specific lifecycle state is stored in status_to.
                'event_type' => 'status_updated',
                'status_from' => $from,
                'status_to' => $validated['status'],
                'message' => $validated['notes'] ?? "Delivery status updated from {$from} to {$validated['status']}.",
                'meta' => array_filter([
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'location_address' => $locationAddress ?: null,
                    'proof_photo_url' => $proofPath ? $this->publicUrl($proofPath) : null,
                ], fn ($value) => $value !== null && $value !== ''),
                'created_by' => $request->user()->id,
            ]);

            // Keep the customer-facing order status synchronized with the
            // driver's delivery milestone.
            $orderStatus = match ($validated['status']) {
                'packed' => 'packed',
                'in_transit' => 'in_transit',
                'out_for_delivery' => 'out_for_delivery',
                'delivered' => 'delivered',
                'cancelled' => 'cancelled',
                default => null,
            };

            if ($orderStatus !== null) {
                $order->status = $orderStatus;
            }

            if ($validated['status'] === 'delivered'
                && $order->payment_method === 'cod'
                && $order->payment_status === 'unpaid') {
                $order->payment_status = 'paid';
            }

            $order->save();

            if ($validated['status'] === 'delivered'
                && $order->payment_method === 'cod'
                && $order->payment_status === 'paid') {
                $this->commissionService->record($order, false);
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

    /**
     * Update the current GPS position without creating a delivery log entry.
     * The map reads the latest position from the shipment record.
     */
    public function updateLocation(Request $request, string $source, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:2000',
        ]);

        $source = strtolower($source);

        if ($source === 'ecommerce') {
            $order = $this->resolveEcommerceOrder($request, $orderId, withDelivery: true);
            $delivery = $order->delivery;
            if (!$delivery) {
                return response()->json(['success' => false, 'message' => 'No delivery found for order.'], 404);
            }
            if ($request->user()->hasRole('driver') && (int) $delivery->driver_user_id !== (int) $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'This delivery is not assigned to you.'], 403);
            }
            $deliveryStatus = strtolower((string) $delivery->status);
            // A five-second GPS request can arrive just after the driver marks
            // the order delivered. Treat that late update as harmless so it
            // does not surface a false 422 to the driver.
            if ($deliveryStatus === 'delivered') {
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery already completed; location tracking is closed.',
                    'data' => $delivery->fresh(['vehicle']),
                ]);
            }
            if (!in_array($deliveryStatus, ['in_transit', 'out_for_delivery', 'on_the_way'], true)) {
                return response()->json(['success' => false, 'message' => 'Live tracking is only available while the order is in transit.'], 422);
            }

            $address = trim((string) ($validated['location_address'] ?? ''));
            if (Schema::hasColumn('ecommerce_order_deliveries', 'current_latitude')) {
                $delivery->update([
                    'current_latitude' => $validated['latitude'],
                    'current_longitude' => $validated['longitude'],
                    'current_address' => $address !== '' ? $address : $delivery->current_address,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Live location updated.',
                'data' => $delivery->fresh(['vehicle']),
            ]);
        }

        if ($source !== 'pickup') {
            return response()->json(['success' => false, 'message' => 'Live location is not available for this delivery type.'], 422);
        }

        $pickup = $this->resolvePickupShipment($request, $orderId);
        if (strtolower((string) $pickup->status) !== 'in_transit') {
            return response()->json(['success' => false, 'message' => 'Live tracking is only available while the pickup is in transit.'], 422);
        }

        $address = trim((string) ($validated['location_address'] ?? ''))
            ?: $this->reverseGeocode((float) $validated['latitude'], (float) $validated['longitude']);

        $pickup->update([
            'current_latitude' => $validated['latitude'],
            'current_longitude' => $validated['longitude'],
            'current_address' => $address ?: $pickup->current_address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Live location updated.',
            'data' => $pickup->fresh(['vehicle']),
        ]);
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
            if ($order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
                $order->payment_status = 'paid';
            }
            $order->save();
            if ($order->payment_method === 'cod' && $order->payment_status === 'paid') {
                $this->commissionService->record($order, false);
            }

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

        if ($source === 'pickup') {
            $pickup = $this->resolvePickupShipment($request, $orderId);
            return response()->json(['success' => true, 'data' => $pickup->logs]);
        }

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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:2000',
        ]);

        $source = strtolower($source);

        if ($source === 'pickup') {
            $pickup = $this->resolvePickupShipment($request, $orderId);
            $log = PurchaseOrderDeliveryLog::create([
                'shipment_id' => $pickup->id,
                'created_by' => $request->user()->id,
                'event_type' => 'note',
                'notes' => $validated['message'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'location_address' => $validated['location_address'] ?? null,
                'logged_at' => now(),
            ]);
            if (isset($validated['latitude'], $validated['longitude'])) {
                $pickup->update([
                    'current_latitude' => $validated['latitude'],
                    'current_longitude' => $validated['longitude'],
                    'current_address' => $validated['location_address'] ?? $this->reverseGeocode((float) $validated['latitude'], (float) $validated['longitude']),
                ]);
            }
            return response()->json(['success' => true, 'data' => $log->load('creator:id,fname,lname,email')], 201);
        }

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
                'assignedBranch:id,name,address,latitude,longitude',
                'delivery:id,order_id,tracking_number,status,driver_user_id,vehicle_id,estimated_delivery_at,delivered_at,created_at,updated_at',
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
                'quantity_items' => $this->orderItemsQuantity($order->items ?? []),
                'delivery_date' => $delivery?->delivered_at ?? $order->created_at,
                'expected_pickup_date' => $delivery?->estimated_delivery_at,
                'created_at' => $order->created_at,
                'driver_user_id' => $delivery?->driver_user_id,
            ];
        });
    }

    private function getSalesOrderRows(Request $request): Collection
    {
        $query = SalesOrder::query()
            ->with([
                'branch:id,name,latitude,longitude',
                'delivery:id,sales_order_id,tracking_number,status,driver_user_id,scheduled_delivery_at,delivered_at,created_at,updated_at',
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
                'quantity_items' => $this->orderItemsQuantity($order->items ?? []),
                'delivery_date' => $delivery?->delivered_at ?? $order->created_at,
                'expected_pickup_date' => $delivery?->scheduled_delivery_at,
                'created_at' => $order->created_at,
                'driver_user_id' => $delivery?->driver_user_id,
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
                'items.product:id,product_name,sku,weight_kg,unit_of_measurement',
                'delivery.driver:id,fname,lname,email,phone_number',
                'delivery.vehicle',
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
                'items.product:id,product_name,sku,weight_kg,unit_of_measurement',
                'delivery.driver:id,fname,lname,email,phone_number',
                'delivery.vehicle',
            ]);
        }

        $this->applySalesTenantScope($request, $query);

        return $query->findOrFail($orderId);
    }

    private function monitoringDeliveryData($delivery, Request $request, int $orderId, string $source): array
    {
        $assistantIds = collect($delivery->assistant_user_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $assistants = User::query()
            ->with('employee.branch:id,name')
            ->where('store_id', $request->user()->store_id)
            ->whereIn('id', $assistantIds)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')),
                'branch' => $user->employee?->branch?->name,
            ])->values();

        return array_merge($delivery->toArray(), [
            'assistants' => $assistants,
            'proof_photo_url' => url("/api/logistics/delivery-orders/{$source}/{$orderId}/proof/photo"),
            'proof_signature_url' => url("/api/logistics/delivery-orders/{$source}/{$orderId}/proof/signature"),
        ]);
    }

    private function monitoringLogs(Collection $logs, ?array $delivery): Collection
    {
        return $logs->map(function ($log) use ($delivery): array {
            $data = $log->toArray();
            $meta = is_array($log->meta) ? $log->meta : [];
            $attachments = collect([
                $meta['proof_photo_url'] ?? null,
                $meta['proof_signature_url'] ?? null,
            ])->filter()->map(fn (string $url, int $index) => [
                'id' => "{$log->id}-{$index}",
                'public_url' => $url,
            ])->values();

            if ($attachments->isEmpty() && $log->event_type === 'proof_uploaded' && $delivery) {
                $attachments = collect([$delivery['proof_photo_url'] ?? null, $delivery['proof_signature_url'] ?? null])
                    ->filter()->map(fn (string $url, int $index) => ['id' => "{$log->id}-proof-{$index}", 'public_url' => $url])->values();
            }

            $data['attachments'] = $attachments;
            $data['latitude'] = $data['latitude'] ?? ($meta['latitude'] ?? null);
            $data['longitude'] = $data['longitude'] ?? ($meta['longitude'] ?? null);
            $data['location_address'] = $data['location_address'] ?? ($meta['location_address'] ?? null);
            return $data;
        });
    }

    private function resolvePickupShipment(Request $request, int $orderId): PurchaseOrderShipment
    {
        $query = PurchaseOrderShipment::query()
            ->with([
                'purchaseOrder.items.product',
                'purchaseOrder.items.variation',
                'purchaseOrder.supplier',
                'purchaseOrder.branch',
                'driverUser:id,fname,lname,email,phone_number',
                'driverEmployee:id,user_id,employee_number',
                'vehicle',
                'logs' => fn ($query) => $query->with(['creator:id,fname,lname,email', 'attachments'])->orderByDesc('logged_at'),
            ])
            ->where('purchase_order_id', $orderId)
            ->whereHas('purchaseOrder', fn ($query) => $query->where('store_id', $request->user()->store_id));

        if ($request->user()->hasRole('driver')) {
            $query->where('driver_user_id', $request->user()->id);
        }

        return $query->firstOrFail();
    }

    private function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => config('app.name', 'FurnitureStoresPlatform') . ' PickupTracker'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'accept-language' => 'en',
                ]);

            return $response->successful() ? ($response->json('display_name') ?: null) : null;
        } catch (\Throwable) {
            return null;
        }
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
            $quantity = (int) ($item->quantity ?? $item->quantity_ordered ?? 0);
            $total += $weight * $quantity;
        }
        return $total;
    }

    private function orderItemsQuantity($items): int
    {
        return (int) collect($items)->sum(
            fn ($item) => (int) ($item->quantity ?? $item->quantity_ordered ?? 0)
        );
    }

    private function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function resolveReadableDocumentPath(string $rawPath): ?array
    {
        $rawPath = trim($rawPath);
        if ($rawPath === '') {
            return null;
        }

        $candidates = [
            ltrim($rawPath, '/'),
            preg_replace('#^storage/#', '', ltrim($rawPath, '/')),
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            if (Storage::disk('public')->exists($candidate)) {
                return ['public', $candidate];
            }

            if (Storage::disk('local')->exists($candidate)) {
                return ['local', $candidate];
            }

            $publicStoragePath = public_path('storage/' . $candidate);
            if (is_file($publicStoragePath)) {
                return ['absolute', $publicStoragePath];
            }

            $publicDirectPath = public_path($candidate);
            if (is_file($publicDirectPath)) {
                return ['absolute', $publicDirectPath];
            }
        }

        return null;
    }
}
