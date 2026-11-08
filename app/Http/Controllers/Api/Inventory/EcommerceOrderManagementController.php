<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\CRM\EcommerceChatMessage;
use App\Models\CRM\EcommerceChatThread;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Inventory\BranchInventory;
use App\Models\Store\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class EcommerceOrderManagementController extends Controller
{
    private const ORDER_STATUSES = [
        'pending',
        'processing',
        'ready_for_dispatch',
        'ready_for_pickup',
        'packed',
        'shipped',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'pending_cancellation',
        'cancelled',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = EcommerceOrder::query()
            ->with([
                'user:id,fname,lname,email',
                'store',
                'assignedBranch:id,name,branch_code,city,province',
                'delivery:id,order_id,status,vehicle_id,driver_user_id,tracking_number,courier_name,courier_contact,estimated_delivery_at,dispatched_at,delivered_at',
                'delivery.driver:id,fname,lname,email',
            ])
            ->withCount('items');

        $this->applyStoreScope($request, $query);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('shipping_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        $orders->getCollection()->transform(function (EcommerceOrder $order) {
            $payload = $order->toArray();
            $payload['primary_status'] = $this->resolvePrimaryStatus($order);
            return $payload;
        });

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = EcommerceOrder::query()
            ->with([
                'user:id,fname,lname,email',
                'store',
                'assignedBranch:id,name,branch_code,city,province',
                'items:id,order_id,product_id,branch_inventory_id,product_name,sku,customization_request,customization_status,customization_response,customization_reviewed_at,quantity,unit_price,line_subtotal,line_tax,line_total',
                'items.product:id,product_name,sku,unit_of_measurement',
                'items.branchInventory:id,branch_id,product_id,variation_id,quantity_available,stock_status',
                'items.branchInventory.branch:id,name,branch_code,city,province',
                'items.branchInventory.variation:id,variation_name,variation_sku',
                'items.returnRequests',
                'items.returnRequests.financeRefund',
                'items.returnRequests.orderItem:id,product_name,sku',
                'items.returnRequests.replacementBranch:id,name',
                'items.returnRequests.replacementProduct:id,product_name,sku',
                'items.returnRequests.replacementInventory.product:id,product_name,sku',
                'items.returnRequests.replacementInventory.variation:id,variation_name,variation_sku',
                'items.returnRequests.replacementTrip:id,created_at,status,driver_user_id,vehicle_id',
                'items.returnRequests.replacementTrip.driver:id,fname,lname',
                'items.returnRequests.replacementTrip.vehicle:id,vehicle_name,plate_number',
                'items.returnRequests.pickup',
                'items.returnRequests.pickup.trip:id,created_at',
                'items.returnRequests.pickup.destinationBranch:id,name',
                'cancellationRequests',
                'cancellationRefund',
                'delivery.vehicle:id,vehicle_name,plate_number,vehicle_type,status',
                'delivery.driver:id,fname,lname,email,phone_number,role_id',
                'delivery.driver.employee:id,user_id,employee_number,status,department,employment_type',
                'delivery.driver.role:id,name,display_name',
                'delivery.logs:id,delivery_id,order_id,event_type,status_from,status_to,message,meta,created_by,created_at',
                'delivery.logs.creator:id,fname,lname',
            ]);

        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $data = $order->toArray();
        $data['primary_status'] = $this->resolvePrimaryStatus($order);
        $data['timeline'] = $this->formatOrderTimeline($order);
        $data['return_requests'] = $order->items
            ->flatMap(fn ($item) => $item->returnRequests)
            ->sortBy('created_at')
            ->map(fn ($return) => [
                'id' => (int) $return->id,
                'return_number' => $return->return_number,
                'return_type' => $return->return_type,
                'status' => $return->status,
                'reason' => $return->reason,
                'created_at' => $return->created_at,
                'reviewed_at' => $return->reviewed_at,
                'review_notes' => $return->review_notes,
                'inspected_at' => $return->inspected_at,
                'inspection_notes' => $return->inspection_notes,
                'resolved_at' => $return->resolved_at,
                'updated_at' => $return->updated_at,
                'item_name' => $return->orderItem?->product_name,
                'item_sku' => $return->orderItem?->sku,
                'pickup' => $return->pickup ? [
                    'status' => $return->pickup->status,
                    'scheduled_at' => $return->pickup->scheduled_at,
                    'branch_name' => $return->pickup->destinationBranch?->name,
                    'trip_reference' => $return->pickup->trip?->created_at
                        ? sprintf('TRIP-%s-%06d', $return->pickup->trip->created_at->format('Y'), (int) $return->pickup->trip_id)
                        : null,
                    'picked_up_at' => $return->pickup->picked_up_at,
                    'out_for_delivery_at' => $return->pickup->out_for_delivery_at,
                    'delivered_at' => $return->pickup->delivered_at,
                ] : null,
                'refund' => $return->financeRefund ? [
                    'amount' => (float) $return->financeRefund->amount,
                    'status' => $return->financeRefund->status,
                    'method' => $return->financeRefund->refund_method,
                    'account_name' => $return->financeRefund->refund_account_name,
                    'account_number' => $return->financeRefund->refund_account_number,
                    'processed_at' => $return->financeRefund->processed_at,
                    'sent_at' => $return->financeRefund->sent_at,
                    'payout_reference' => $return->financeRefund->payout_reference,
                ] : null,
                'replacement' => $return->return_type === 'replacement' ? [
                    'status' => $return->replacement_status ?: ($return->status === 'received' ? 'awaiting_stock' : null),
                    'price_difference' => (float) ($return->replacement_price_difference ?? 0),
                    'payment_status' => $return->replacement_payment_status,
                    'branch_name' => $return->replacementBranch?->name,
                    'item_name' => $return->replacementInventory?->variation?->variation_name
                        ? trim(($return->replacementInventory->product?->product_name ?? '') . ' - ' . $return->replacementInventory->variation->variation_name)
                        : ($return->replacementInventory?->product?->product_name ?? $return->replacementProduct?->product_name),
                    'sku' => $return->replacementInventory?->variation?->variation_sku
                        ?: $return->replacementInventory?->product?->sku
                        ?: $return->replacementProduct?->sku,
                    'trip_reference' => $return->replacementTrip?->created_at
                        ? sprintf('TRIP-%s-%06d', $return->replacementTrip->created_at->format('Y'), (int) $return->replacement_trip_id)
                        : null,
                    'trip_status' => $return->replacementTrip?->status,
                    'driver_name' => trim(($return->replacementTrip?->driver?->fname ?? '') . ' ' . ($return->replacementTrip?->driver?->lname ?? '')) ?: null,
                    'vehicle' => $return->replacementTrip?->vehicle
                        ? trim($return->replacementTrip->vehicle->vehicle_name . ' · ' . ($return->replacementTrip->vehicle->plate_number ?: ''))
                        : null,
                    'assigned_at' => $return->replacement_assigned_at,
                    'dispatched_at' => $return->replacement_dispatched_at,
                    'delivered_at' => $return->replacement_delivered_at,
                    'received_by' => $return->replacement_received_by,
                    'failure_reason' => $return->replacement_failure_reason,
                    'proof_url' => $return->replacement_proof_path ? \Storage::disk('public')->url($return->replacement_proof_path) : null,
                ] : null,
            ])->values()->all();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function setPickupDate(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $query = EcommerceOrder::query();
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        if ((float) $order->shipping_fee > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup date can only be set for orders with no shipping fee.',
            ], 422);
        }

        $order->pickup_date = $validated['pickup_date'];
        $order->fulfillment_method = 'pickup';
        if (in_array((string) $order->status, ['pending', 'processing', 'ready_for_dispatch'], true)) {
            $order->status = 'ready_for_pickup';
        }
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Pickup date saved.',
            'data' => $order->fresh(),
        ]);
    }

    public function reviewCustomizationRequest(Request $request, int $id, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'response' => ['nullable', 'string', 'max:1000'],
        ]);

        $orderQuery = EcommerceOrder::query();
        $this->applyStoreScope($request, $orderQuery);
        $order = $orderQuery->findOrFail($id);
        $item = $order->items()->whereKey($itemId)->firstOrFail();

        if (!$item->customization_request) {
            return response()->json(['success' => false, 'message' => 'This item has no customization request to review.'], 422);
        }

        $item->update([
            'customization_status' => $validated['status'],
            'customization_response' => trim((string) ($validated['response'] ?? '')) ?: null,
            'customization_reviewed_by' => $request->user()?->id,
            'customization_reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customization request ' . $validated['status'] . '.',
            'data' => $item->fresh(),
        ]);
    }

    public function receiptPdf(Request $request, int $id)
    {
        if (!Schema::hasTable('ecommerce_orders')) {
            abort(404);
        }

        $query = EcommerceOrder::query()
            ->with([
                'user:id,fname,lname,email',
                'store:id,name,address,city,province,phone,email',
                'assignedBranch:id,name,address,city,province',
                'items:id,order_id,product_id,product_name,sku,quantity,unit_price,line_total',
            ]);

        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $data = [
            'order' => $order,
            'store' => $order->store,
            'branch' => $order->assignedBranch,
            'items' => $order->items,
        ];

        $pdf = \PDF::loadView('sales.ecommerce-order-receipt-pdf', $data)->setPaper('a4', 'portrait');
        $filename = ($order->order_number ?: ('WEB-' . $order->id)) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::ORDER_STATUSES)],
            'delivery' => 'nullable|array',
            'delivery.vehicle_id' => 'nullable|exists:ecommerce_delivery_vehicles,id',
            'delivery.tracking_number' => 'nullable|string|max:80',
            'delivery.courier_name' => 'nullable|string|max:120',
            'delivery.courier_contact' => 'nullable|string|max:50',
            'delivery.estimated_delivery_at' => 'nullable|date',
            'delivery.notes' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $query = EcommerceOrder::query()->with('delivery');
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $targetStatus = (string) $validated['status'];
        if ($targetStatus === 'ready_for_dispatch') {
            $storeId = (int) ($order->store_id ?? 0);
            if (!$request->user()->hasPermissionTo('sales.orders.manage', $storeId ?: null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to approve orders.',
                ], 403);
            }
        }
        $this->ensureValidTransition($order->status, $targetStatus);

        DB::transaction(function () use ($request, $order, $validated, $targetStatus) {
            $previousOrderStatus = (string) $order->status;
            $reservation = app(\App\Services\Ecommerce\EcommerceStockReservationService::class);
            if ($targetStatus === 'cancelled') {
                $reservation->release($order);
            } elseif (in_array($targetStatus, ['ready_for_dispatch', 'ready_for_pickup'], true)) {
                $reservation->consume($order);
            }
            $order->status = $targetStatus;

            if (!empty($validated['notes'])) {
                $current = trim((string) $order->notes);
                $line = '[' . now()->format('Y-m-d H:i') . '] ' . trim((string) $validated['notes']);
                $order->notes = $current === '' ? $line : $current . PHP_EOL . $line;
            }

            if ($targetStatus === 'delivered' && $order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
                $order->payment_status = 'paid';
            }

            $order->save();

            $deliveryPayload = $validated['delivery'] ?? [];
            $needsDelivery = in_array($targetStatus, ['ready_for_dispatch', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'], true) || !empty($deliveryPayload);

            if ($needsDelivery) {
                $delivery = EcommerceOrderDelivery::query()->firstOrNew(['order_id' => $order->id], [
                    'store_id' => $order->store_id,
                    'created_by' => $request->user()->id,
                ]);
                $isNewDelivery = !$delivery->exists;
                $previousDeliveryStatus = (string) ($delivery->status ?: ($targetStatus === 'ready_for_dispatch' ? 'pending' : 'assigned'));

                $delivery->store_id = $order->store_id;
                $delivery->vehicle_id = $deliveryPayload['vehicle_id'] ?? $delivery->vehicle_id;
                $delivery->tracking_number = $deliveryPayload['tracking_number'] ?? $delivery->tracking_number;
                $delivery->courier_name = $deliveryPayload['courier_name'] ?? $delivery->courier_name;
                $delivery->courier_contact = $deliveryPayload['courier_contact'] ?? $delivery->courier_contact;
                $delivery->estimated_delivery_at = $deliveryPayload['estimated_delivery_at'] ?? $delivery->estimated_delivery_at;
                $delivery->notes = $deliveryPayload['notes'] ?? $delivery->notes;
                $delivery->updated_by = $request->user()->id;

                $deliveryStatus = match ($targetStatus) {
                    'ready_for_dispatch' => 'ready_for_dispatch',
                    'packed' => 'packed',
                    'shipped', 'in_transit' => 'in_transit',
                    'out_for_delivery' => 'out_for_delivery',
                    'delivered' => 'delivered',
                    'cancelled' => 'cancelled',
                    default => $delivery->status ?: 'assigned',
                };
                $delivery->status = $deliveryStatus;

                if (in_array($targetStatus, ['shipped', 'in_transit'], true) && !$delivery->dispatched_at) {
                    $delivery->dispatched_at = now();
                }
                if ($targetStatus === 'out_for_delivery' && !$delivery->out_for_delivery_at) {
                    $delivery->out_for_delivery_at = now();
                }
                if ($targetStatus === 'delivered' && !$delivery->delivered_at) {
                    $delivery->delivered_at = now();
                }

                $delivery->save();

                if ($isNewDelivery) {
                    EcommerceDeliveryLog::query()->create([
                        'delivery_id' => $delivery->id,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'event_type' => 'created',
                        'status_to' => $delivery->status,
                        'message' => $targetStatus === 'ready_for_dispatch'
                            ? 'Delivery is pending logistics assignment.'
                            : 'Delivery record created.',
                        'created_by' => $request->user()->id,
                    ]);
                }

                $sameOrderTransition = $previousOrderStatus === $previousDeliveryStatus
                    && $targetStatus === (string) $delivery->status;
                if ($previousDeliveryStatus !== (string) $delivery->status && !$sameOrderTransition) {
                    EcommerceDeliveryLog::query()->create([
                        'delivery_id' => $delivery->id,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'event_type' => 'status_updated',
                        'status_from' => $previousDeliveryStatus,
                        'status_to' => (string) $delivery->status,
                        'message' => "Delivery status updated from {$previousDeliveryStatus} to {$delivery->status}.",
                        'created_by' => $request->user()->id,
                    ]);
                }

                $order->setRelation('delivery', $delivery);
            }

            if ($previousOrderStatus !== $targetStatus && $order->delivery) {
                EcommerceDeliveryLog::query()->create([
                    'delivery_id' => $order->delivery->id,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'status_updated',
                    'status_from' => $previousOrderStatus,
                    'status_to' => $targetStatus,
                    'message' => "Order status updated from {$previousOrderStatus} to {$targetStatus}.",
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => EcommerceOrder::query()->with(['delivery.vehicle'])->find($order->id),
        ]);
    }

    private function resolvePrimaryStatus(EcommerceOrder $order): string
    {
        $orderStatus = strtolower((string) $order->status);
        if (in_array($orderStatus, ['cancelled', 'canceled', 'returned', 'refunded'], true)) {
            return $orderStatus;
        }

        $latestCancellation = $order->relationLoaded('cancellationRequests')
            ? $order->cancellationRequests->sortByDesc('created_at')->first()
            : null;

        if ($latestCancellation) {
            $cancelStatus = strtolower((string) $latestCancellation->status);
            if ($cancelStatus === 'approved') {
                return 'cancelled';
            }
            if ($cancelStatus === 'pending_verification') {
                return 'cancellation_pending_verification';
            }
        }

        $latestReturn = null;
        if ($order->relationLoaded('items')) {
            $latestReturn = $order->items
                ->flatMap(function ($item) {
                    if (!$item->relationLoaded('returnRequests')) {
                        return collect();
                    }

                    return $item->returnRequests;
                })
                ->sortByDesc('created_at')
                ->first();
        }

        if ($latestReturn) {
            $returnStatus = strtolower((string) $latestReturn->status);
            if ($returnStatus === 'refunded') {
                return 'refunded';
            }
            if ($returnStatus === 'received') {
                return 'return_received';
            }
            if ($returnStatus === 'approved') {
                return 'return_approved';
            }
            if ($returnStatus === 'pending_verification') {
                return 'return_pending_verification';
            }
        }

        return $orderStatus;
    }

    public function assignDelivery(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'driver_user_id' => 'required|exists:users,id',
            'vehicle_id' => 'nullable|exists:ecommerce_delivery_vehicles,id',
            'estimated_delivery_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $query = EcommerceOrder::query()->with(['delivery', 'delivery.logs']);
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        if (in_array((string) $order->status, ['delivered', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign delivery for completed or cancelled orders.',
            ], 422);
        }

        $driver = $this->resolveDriver($order->store_id, (int) $validated['driver_user_id']);

        DB::transaction(function () use ($request, $validated, $order, $driver): void {
            $delivery = EcommerceOrderDelivery::query()->firstOrNew(['order_id' => $order->id], [
                'store_id' => $order->store_id,
                'created_by' => $request->user()->id,
            ]);

            $isNew = !$delivery->exists;
            $previousDeliveryStatus = (string) ($delivery->status ?: 'assigned');
            $previousOrderStatus = (string) $order->status;

            $delivery->store_id = $order->store_id;
            $delivery->vehicle_id = $validated['vehicle_id'] ?? $delivery->vehicle_id;
            $delivery->driver_user_id = $driver->id;
            $delivery->courier_name = trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? ''));
            $delivery->courier_contact = $this->resolveDriverContact($driver) ?: $delivery->courier_contact;
            $delivery->tracking_number = $delivery->tracking_number ?: $this->nextTrackingNumber($order->store_id);
            $delivery->estimated_delivery_at = $validated['estimated_delivery_at'] ?? $delivery->estimated_delivery_at;
            $delivery->notes = $validated['notes'] ?? $delivery->notes;
            $delivery->status = 'in_transit';
            $delivery->dispatched_at = $delivery->dispatched_at ?: now();
            $delivery->updated_by = $request->user()->id;
            $delivery->save();

            if ($isNew) {
                EcommerceDeliveryLog::query()->create([
                    'delivery_id' => $delivery->id,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'created',
                    'status_to' => 'in_transit',
                    'message' => 'Delivery assignment created.',
                    'created_by' => $request->user()->id,
                ]);
            }

            EcommerceDeliveryLog::query()->create([
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'event_type' => 'driver_assigned',
                'status_from' => $previousDeliveryStatus,
                'status_to' => 'in_transit',
                'message' => 'Courier assigned: ' . $delivery->courier_name,
                'meta' => [
                    'driver_user_id' => $driver->id,
                    'driver_name' => $delivery->courier_name,
                    'driver_contact' => $delivery->courier_contact,
                    'tracking_number' => $delivery->tracking_number,
                ],
                'created_by' => $request->user()->id,
            ]);

            if ($previousDeliveryStatus !== 'in_transit') {
                EcommerceDeliveryLog::query()->create([
                    'delivery_id' => $delivery->id,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'status_updated',
                    'status_from' => $previousDeliveryStatus,
                    'status_to' => 'in_transit',
                    'message' => "Delivery status updated from {$previousDeliveryStatus} to in_transit.",
                    'created_by' => $request->user()->id,
                ]);
            }

            if ($previousOrderStatus !== 'shipped') {
                $order->status = 'shipped';
                $order->save();

                EcommerceDeliveryLog::query()->create([
                    'delivery_id' => $delivery->id,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'status_updated',
                    'status_from' => $previousOrderStatus,
                    'status_to' => 'shipped',
                    'message' => "Order status updated from {$previousOrderStatus} to shipped.",
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Delivery assigned successfully.',
            'data' => EcommerceOrder::query()
                ->with(['delivery.vehicle', 'delivery.driver:id,fname,lname,email'])
                ->find($order->id),
        ]);
    }

    public function updateDeliveryAssignment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'driver_user_id' => 'nullable|exists:users,id',
            'vehicle_id' => 'nullable|exists:ecommerce_delivery_vehicles,id',
            'estimated_delivery_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $query = EcommerceOrder::query()->with('delivery');
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);
        $delivery = $order->delivery;

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'No delivery assignment found for this order.',
            ], 422);
        }

        $driver = null;
        if (!empty($validated['driver_user_id'])) {
            $driver = $this->resolveDriver($order->store_id, (int) $validated['driver_user_id']);
        }

        DB::transaction(function () use ($request, $validated, $delivery, $order, $driver): void {
            $previousDriver = (int) ($delivery->driver_user_id ?? 0);

            if (array_key_exists('vehicle_id', $validated)) {
                $delivery->vehicle_id = $validated['vehicle_id'] ?: null;
            }
            if (array_key_exists('estimated_delivery_at', $validated)) {
                $delivery->estimated_delivery_at = $validated['estimated_delivery_at'] ?: null;
            }
            if (array_key_exists('notes', $validated)) {
                $delivery->notes = $validated['notes'] ?: null;
            }
            if ($driver) {
                $delivery->driver_user_id = $driver->id;
                $delivery->courier_name = trim(($driver->fname ?? '') . ' ' . ($driver->lname ?? ''));
                $delivery->courier_contact = $this->resolveDriverContact($driver) ?: $delivery->courier_contact;
            }
            if (!$delivery->tracking_number) {
                $delivery->tracking_number = $this->nextTrackingNumber($order->store_id);
            }
            $delivery->updated_by = $request->user()->id;
            $delivery->save();

            EcommerceDeliveryLog::query()->create([
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'event_type' => 'note',
                'message' => 'Delivery assignment updated.',
                'meta' => [
                    'driver_user_id' => $delivery->driver_user_id,
                    'driver_changed' => $previousDriver !== (int) ($delivery->driver_user_id ?? 0),
                    'tracking_number' => $delivery->tracking_number,
                ],
                'created_by' => $request->user()->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Delivery assignment updated.',
            'data' => EcommerceOrder::query()
                ->with(['delivery.vehicle', 'delivery.driver:id,fname,lname,email'])
                ->find($order->id),
        ]);
    }

    public function branchTransferCandidates(Request $request, int $id): JsonResponse
    {
        $query = EcommerceOrder::query()->with(['items:id,order_id,product_id,quantity', 'assignedBranch:id,name,branch_code']);
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $branches = Branch::query()
            ->where('store_id', $order->store_id)
            ->where('status', 'active')
            ->where('id', '!=', $order->assigned_branch_id)
            ->get(['id', 'name', 'branch_code', 'city', 'province']);

        $rows = $branches->map(function (Branch $branch) use ($order) {
            $allAvailable = true;
            $items = [];

            foreach ($order->items as $item) {
                $inventory = BranchInventory::query()
                    ->where('store_id', $order->store_id)
                    ->where('branch_id', $branch->id)
                    ->where('product_id', $item->product_id)
                    ->first();

                $available = (int) ($inventory?->quantity_available ?? 0);
                $required = (int) $item->quantity;
                $status = (string) ($inventory?->stock_status ?? 'out_of_stock');
                $isAvailable = $available >= $required;

                if (!$isAvailable) {
                    $allAvailable = false;
                }

                $items[] = [
                    'product_id' => $item->product_id,
                    'required_qty' => $required,
                    'available_qty' => $available,
                    'stock_status' => $status,
                    'is_available' => $isAvailable,
                ];
            }

            return [
                'branch' => $branch,
                'can_fulfill' => $allAvailable,
                'items' => $items,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function passToBranch(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'to_branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $query = EcommerceOrder::query()->with(['items', 'delivery']);
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        if (!in_array((string) $order->status, ['pending', 'processing', 'packed'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Branch pass is only allowed for pending/processing/packed orders.',
            ], 422);
        }

        $toBranch = Branch::query()
            ->where('store_id', $order->store_id)
            ->where('status', 'active')
            ->findOrFail((int) $validated['to_branch_id']);

        DB::transaction(function () use ($request, $order, $toBranch, $validated): void {
            foreach ($order->items as $item) {
                $targetInventory = BranchInventory::query()
                    ->where('store_id', $order->store_id)
                    ->where('branch_id', $toBranch->id)
                    ->where('product_id', $item->product_id)
                    ->first();

                $available = (int) ($targetInventory?->quantity_available ?? 0);
                if ($available < (int) $item->quantity) {
                    abort(response()->json([
                        'success' => false,
                        'message' => "Selected branch cannot fulfill {$item->product_name}.",
                    ], 422));
                }

                $item->branch_inventory_id = $targetInventory->id;
                $item->save();
            }

            $fromBranchId = $order->assigned_branch_id;
            $order->assigned_branch_id = $toBranch->id;
            if (!empty($validated['notes'])) {
                $line = '[' . now()->format('Y-m-d H:i') . '] Branch handoff: ' . trim((string) $validated['notes']);
                $existing = trim((string) $order->notes);
                $order->notes = $existing === '' ? $line : "{$existing}\n{$line}";
            }
            $order->save();

            if ($order->delivery) {
                EcommerceDeliveryLog::query()->create([
                    'delivery_id' => $order->delivery->id,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'event_type' => 'note',
                    'message' => "Order fulfillment reassigned from branch {$fromBranchId} to branch {$toBranch->id}.",
                    'meta' => [
                        'from_branch_id' => $fromBranchId,
                        'to_branch_id' => $toBranch->id,
                    ],
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order reassigned to selected branch.',
            'data' => EcommerceOrder::query()
                ->with(['assignedBranch:id,name,branch_code,city,province', 'items.branchInventory:id,branch_id,product_id,quantity_available,stock_status'])
                ->find($order->id),
        ]);
    }

    public function chatMessages(Request $request, int $id): JsonResponse
    {
        $query = EcommerceOrder::query()->with('user:id,fname,lname,email');
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $thread = EcommerceChatThread::query()->firstOrCreate([
            'store_id' => $order->store_id,
            'customer_user_id' => $order->user_id,
        ]);

        $messages = EcommerceChatMessage::query()
            ->with('sender:id,fname,lname')
            ->where('thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 50));

        EcommerceChatMessage::query()
            ->where('thread_id', $thread->id)
            ->where('sender_role', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'data' => $messages, 'thread_id' => $thread->id]);
    }

    public function sendChatMessage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $query = EcommerceOrder::query();
        $this->applyStoreScope($request, $query);
        $order = $query->findOrFail($id);

        $thread = EcommerceChatThread::query()->firstOrCreate([
            'store_id' => $order->store_id,
            'customer_user_id' => $order->user_id,
        ]);

        $message = EcommerceChatMessage::query()->create([
            'thread_id' => $thread->id,
            'sender_user_id' => $request->user()->id,
            'sender_role' => 'store',
            'order_id' => $order->id,
            'message' => trim((string) $validated['message']),
        ]);

        $thread->update(['last_message_at' => $message->created_at]);

        $preview = mb_strlen((string) $message->message) > 120
            ? mb_substr((string) $message->message, 0, 117) . '...'
            : (string) $message->message;
        $this->notify((int) $order->user_id, [
            'store_id' => (int) $order->store_id,
            'module' => 'ecommerce',
            'entity_type' => 'ecommerce_chat_thread',
            'entity_id' => (int) $thread->id,
            'action' => 'order_message_received',
            'title' => 'New message about your order',
            'message' => $preview,
            'severity' => 'info',
            'link' => '/chats?store_id=' . (int) $order->store_id,
        ]);

        return response()->json(['success' => true, 'data' => $message], 201);
    }

    private function applyStoreScope(Request $request, $query): void
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

    private function ensureValidTransition(string $current, string $target): void
    {
        if ($current === $target) {
            return;
        }

        $flow = [
            'pending' => ['processing', 'ready_for_dispatch', 'ready_for_pickup', 'cancelled'],
            'processing' => ['ready_for_dispatch', 'ready_for_pickup', 'packed', 'cancelled'],
            'ready_for_dispatch' => ['packed', 'shipped', 'cancelled'],
            'ready_for_pickup' => ['cancelled'],
            'packed' => ['shipped', 'cancelled'],
            'shipped' => ['in_transit', 'out_for_delivery', 'delivered', 'cancelled'],
            'in_transit' => ['out_for_delivery', 'delivered', 'cancelled'],
            'out_for_delivery' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => [],
        ];

        $allowedTargets = $flow[$current] ?? [];
        if (!in_array($target, $allowedTargets, true)) {
            abort(response()->json([
                'success' => false,
                'message' => "Invalid status transition from '{$current}' to '{$target}'.",
            ], 422));
        }
    }

    private function formatOrderTimeline(EcommerceOrder $order): array
    {
        $timeline = [[
            'type' => 'order_created',
            'title' => 'Order placed',
            'description' => 'Customer placed the order.',
            'status_to' => (string) $order->status,
            'actor' => trim((string) (($order->user?->fname ?? '') . ' ' . ($order->user?->lname ?? ''))) ?: 'Customer',
            'created_at' => $order->placed_at ?? $order->created_at,
        ]];

        $deliveryLogs = collect($order->delivery?->logs ?? [])->sortBy('created_at')->values();
        $deliveryLogs = $deliveryLogs->reject(function ($log) use ($deliveryLogs) {
            if ($log->event_type !== 'status_updated' || !str_starts_with((string) $log->message, 'Delivery status updated from ')) {
                return false;
            }

            return $deliveryLogs->contains(fn ($other) =>
                $other->id !== $log->id
                && $other->event_type === 'status_updated'
                && str_starts_with((string) $other->message, 'Order status updated from ')
                && $other->status_from === $log->status_from
                && $other->status_to === $log->status_to
                && $other->created_by === $log->created_by
                && abs($other->created_at->getTimestamp() - $log->created_at->getTimestamp()) <= 5
            );
        });

        foreach ($deliveryLogs as $log) {
            $actor = trim((string) (($log->creator?->fname ?? '') . ' ' . ($log->creator?->lname ?? '')));
            $description = $log->message ?: 'Order updated.';
            if ($log->event_type === 'status_updated' && $log->status_to) {
                $subject = str_starts_with($description, 'Order status updated') ? 'Order' : 'Delivery';
                $description = $subject . ' moved to ' . str($log->status_to)->replace('_', ' ')->title() . '.';
            }
            $timeline[] = [
                'type' => $log->event_type ?: 'update',
                'title' => $this->timelineTitleFromLog($log->event_type, $log->status_to),
                'description' => $description,
                'status_from' => $log->status_from,
                'status_to' => $log->status_to,
                'meta' => $log->meta,
                'actor' => $actor !== '' ? $actor : 'System',
                'created_at' => $log->created_at,
            ];
        }

        $returnCases = collect();
        if ($order->relationLoaded('items')) {
            $returnCases = $order->items
                ->flatMap(fn ($item) => $item->relationLoaded('returnRequests') ? $item->returnRequests : collect())
                ->unique('id');
        }

        foreach ($returnCases as $return) {
            $reference = $return->return_number ?: 'Return #' . $return->id;
            $push = function (string $type, string $title, ?string $description, $at) use (&$timeline, $reference): void {
                if (!$at) return;
                $timeline[] = [
                    'type' => $type,
                    'title' => $title,
                    'description' => trim($reference . ($description ? ' · ' . $description : '')),
                    'created_at' => $at,
                ];
            };

            $push('return_requested', 'Return requested', $return->reason, $return->created_at);
            if ($return->reviewed_at) {
                $isRejected = in_array((string) $return->status, ['rejected', 'cancelled'], true);
                $push($isRejected ? 'return_rejected' : 'return_approved', $isRejected ? 'Return request declined' : 'Return request approved', $return->review_notes, $return->reviewed_at);
            }
            if ($return->inspected_at) {
                $push('return_inspected', 'Returned item inspected', $return->inspection_notes, $return->inspected_at);
                if ($return->return_type === 'replacement' && (string) ($return->replacement_status ?? 'awaiting_stock') === 'awaiting_stock') {
                    $push('replacement_awaiting_stock', 'Awaiting replacement stock', 'Sales follow-up required; keep the returned item quarantined and do not assign a trip yet.', $return->inspected_at);
                }
            }

            $pickup = $return->pickup;
            if ($pickup && ($pickup->driver_user_id || $pickup->trip_id)) {
                $tripReference = $pickup->trip?->created_at
                    ? sprintf('TRIP-%s-%06d', $pickup->trip->created_at->format('Y'), (int) $pickup->trip_id)
                    : null;
                $push('return_pickup_assigned', 'Return pickup assigned', $tripReference, $pickup->updated_at ?? $pickup->created_at);
            }
            if ($pickup?->picked_up_at) $push('return_picked_up', 'Return picked up from customer', null, $pickup->picked_up_at);
            if ($pickup?->out_for_delivery_at) $push('return_out_for_delivery', 'Return on the way to branch', null, $pickup->out_for_delivery_at);
            if ($pickup?->delivered_at) {
                $branchName = $pickup->destinationBranch?->name;
                $push('return_delivered_to_branch', 'Returned item delivered to branch', $branchName ? 'Received at ' . $branchName : null, $pickup->delivered_at);
            }

            if ($return->return_type === 'refund' && $return->financeRefund) {
                $refund = $return->financeRefund;
                $description = 'PHP ' . number_format((float) $refund->amount, 2) . ' · ' . str($refund->status)->replace('_', ' ')->title();
                if ($refund->refund_method) $description .= ' via ' . str($refund->refund_method)->replace('_', ' ')->title();
                $refundIsWithFinance = in_array((string) $return->status, ['refund_pending', 'refunded'], true);
                $push('refund_recorded', $refundIsWithFinance ? 'Refund sent to Finance' : 'Refund case opened', $description, $refundIsWithFinance ? ($return->inspected_at ?: $refund->created_at) : $refund->created_at);
                if ($refund->processed_at) {
                    $push($refund->status === 'rejected' ? 'refund_rejected' : 'refund_processed', $refund->status === 'rejected' ? 'Refund declined' : 'Refund processed', $refund->notes, $refund->processed_at);
                }
                if ($refund->sent_at) $push('refund_sent', 'Refund sent to customer', $refund->payout_reference ? 'Reference ' . $refund->payout_reference : null, $refund->sent_at);
            }

            if ($return->return_type !== 'replacement') continue;
            if ($return->replacement_customer_accepted_at) $push('replacement_accepted', 'Replacement accepted', null, $return->replacement_customer_accepted_at);
            if ($return->replacement_status === 'reserved') $push('replacement_reserved', 'Replacement stock reserved', null, $return->updated_at);
            if ($return->replacement_assigned_at) {
                $tripRef = $return->replacementTrip?->created_at
                    ? sprintf('TRIP-%s-%06d', $return->replacementTrip->created_at->format('Y'), (int) $return->replacement_trip_id)
                    : 'Trip assigned';
                $push('replacement_assigned', 'Replacement delivery assigned', $tripRef, $return->replacement_assigned_at);
            }
            if ($return->replacement_dispatched_at) $push('replacement_dispatched', 'Replacement out for delivery', null, $return->replacement_dispatched_at);
            if ($return->replacement_status === 'delivery_failed') $push('replacement_failed', 'Replacement delivery attempt failed', $return->replacement_failure_reason, $return->updated_at);
            if ($return->replacement_delivered_at) $push('replacement_delivered', 'Replacement delivered', $return->replacement_received_by ? 'Received by ' . $return->replacement_received_by : null, $return->replacement_delivered_at);
        }

        if ($order->cancellationRefund) {
            $refund = $order->cancellationRefund;
            $description = 'PHP ' . number_format((float) $refund->amount, 2);
            if ($refund->refund_method) $description .= ' via ' . str($refund->refund_method)->replace('_', ' ')->title();
            $timeline[] = ['type' => 'refund_recorded', 'title' => 'Cancellation refund recorded', 'description' => $description, 'created_at' => $refund->created_at];
            if ($refund->processed_at) {
                $timeline[] = ['type' => 'refund_processed', 'title' => $refund->status === 'rejected' ? 'Cancellation refund declined' : 'Cancellation refund processed', 'description' => $refund->notes, 'created_at' => $refund->processed_at];
            }
            if ($refund->sent_at) {
                $timeline[] = ['type' => 'refund_sent', 'title' => 'Cancellation refund sent', 'description' => $refund->payout_reference ? 'Reference ' . $refund->payout_reference : null, 'created_at' => $refund->sent_at];
            }
        }

        return collect($timeline)
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    private function timelineTitleFromLog(?string $eventType, ?string $statusTo): string
    {
        if ($eventType === 'status_updated' && $statusTo) {
            return 'Status: ' . str($statusTo)->replace('_', ' ')->title();
        }

        return match ($eventType) {
            'created' => 'Delivery created',
            'driver_assigned' => 'Driver assigned',
            'proof_uploaded' => 'Proof uploaded',
            default => 'Order update',
        };
    }

    private function resolveDriver(int $storeId, int $driverUserId): User
    {
        return User::query()
            ->with('employee:id,user_id,status')
            ->where('id', $driverUserId)
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function resolveDriverContact(User $driver): ?string
    {
        return $driver->phone_number ?: null;
    }

    private function nextTrackingNumber(int $storeId): string
    {
        do {
            $tracking = sprintf('TRK-%s-%d-%04d', now()->format('YmdHis'), $storeId, random_int(1000, 9999));
        } while (EcommerceOrderDelivery::query()->where('tracking_number', $tracking)->exists());

        return $tracking;
    }
}
