<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierPOFeedback;
use App\Models\Procurement\SupplierPortal\SupplierPOFulfillmentUpdate;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLog;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Models\Core\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SupplierPOFeedbackController extends Controller
{
    /**
     * Get all POs sent to supplier
     * GET /api/supplier-portal/pos
     */
    public function getPOs(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 409);
            }

            $supplierIds = $portal->linkedSupplierIds();
            if ($supplierIds->isEmpty()) {
                $perPage = (int) $request->get('per_page', 10);
                $page = (int) $request->get('page', 1);
                $empty = new LengthAwarePaginator([], 0, $perPage, $page, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $empty,
                ]);
            }

            // Get POs for this supplier
            $allowedStatuses = [
                'sent_to_supplier',
                'supplier_accepted',
                'in_transit',
                'delivered',
                'goods_received',
                'declined_supplier',
            ];

            $query = PurchaseOrder::whereIn('supplier_id', $supplierIds)
                ->whereIn('status', $allowedStatuses)
                ->withCount('items')
                ->with(['supplier', 'store']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('po_number', 'LIKE', "%{$search}%");
            }

            // Sorting (default: created_at desc)
            $sortField = $request->get('sort_field', 'created_at');
            $sortOrder = strtolower($request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = ['created_at', 'order_date', 'expected_delivery_date', 'po_number', 'status'];
            if (!in_array($sortField, $allowedSorts, true)) {
                $sortField = 'created_at';
            }
            $query->orderBy($sortField, $sortOrder);

            $pos = $query->paginate($request->get('per_page', 10));

            // Fetch feedback for every PO on this page in one query. This avoids the
            // supplier portal issuing one HTTP request per table row.
            $feedbackByPurchaseOrder = $portal->poFeedbacks()
                ->whereIn('purchase_order_id', $pos->getCollection()->pluck('id'))
                ->orderByDesc('submitted_at')
                ->get()
                ->unique('purchase_order_id')
                ->keyBy('purchase_order_id');

            // add trimmed store info
            $pos->getCollection()->transform(function ($po) use ($feedbackByPurchaseOrder) {
                $arr = $po->toArray();
                $arr['supplier_feedback'] = $feedbackByPurchaseOrder->get($po->id)?->toArray();
                if ($po->relationLoaded('store') && $po->store) {
                    $arr['store'] = [
                        'id' => $po->store->id ?? null,
                        'store_name' => $po->store->store_name ?? null,
                        'name' => $po->store->name ?? null,
                        'store_code' => $po->store->store_code ?? null,
                    ];
                    $arr['store_name'] = $arr['store']['store_name']
                        ?? $arr['store']['name']
                        ?? $arr['store']['store_code']
                        ?? null;
                } else {
                    $arr['store'] = null;
                    $arr['store_name'] = $arr['store_name'] ?? null;
                }
                return $arr;
            });

            return response()->json([
                'success' => true,
                'data' => $pos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching POs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific PO details
     * GET /api/supplier-portal/pos/{id}
     */
    public function getPODetail($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 409);
            }

            if ($portal->linkedSupplierIds()->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier portal is not linked to a supplier.',
                ], 409);
            }

            $poRelations = ['items.product', 'items.variation', 'supplier', 'branch', 'store', 'supplierContract'];
            $hasFulfillmentUpdatesTable = Schema::hasTable('supplier_po_fulfillment_updates');
            if ($hasFulfillmentUpdatesTable) {
                $poRelations[] = 'supplierFulfillmentUpdates.creator';
            }

            $po = PurchaseOrder::with($poRelations)
                ->findOrFail($id);

            if (!$portal->linkedSupplierIds()->contains((int) $po->supplier_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this purchase order.',
                ], 403);
            }

            foreach ($po->items as $item) {
                foreach (['length_cm', 'width_cm', 'height_cm'] as $dimension) {
                    $item->setAttribute(
                        $dimension,
                        $item->{$dimension}
                            ?? $item->variation?->{$dimension}
                            ?? $item->product?->{$dimension}
                    );
                }
            }

            // Get supplier's feedback for this PO
            $feedback = $portal->poFeedbacks()
                ->where('purchase_order_id', $id)
                ->first();

            $shipment = \App\Models\Procurement\Shipping\PurchaseOrderShipment::with([
                'branch',
                'supplier',
                'driverEmployee.user',
                'driverEmployee.branch',
                'driverEmployee.role',
                'driverUser',
                'vehicle',
            ])
                ->where('purchase_order_id', $id)
                ->first();

            $deliveryLogs = $shipment
                ? PurchaseOrderDeliveryLog::with(['creator:id,fname,lname', 'attachments'])
                    ->where('shipment_id', $shipment->id)
                    ->orderByDesc('logged_at')
                    ->orderByDesc('id')
                    ->get()
                : collect();

            $goodsReceipt = \App\Models\Procurement\Receiving\GoodsReceipt::with(['items.product'])
                ->where('purchase_order_id', $id)
                ->latest('id')
                ->first();

            $invoice = \App\Models\Procurement\Invoice\Invoice::with(['items.product'])
                ->where('purchase_order_id', $id)
                ->latest('id')
                ->first();

            $contract = SupplierContract::where('store_id', $po->store_id)
                ->where('supplier_id', $po->supplier_id)
                ->active()
                ->orderBy('end_date', 'desc')
                ->first();
            $contractTaxRate = $po->contract_tax_rate ?? (($contract && !$contract->is_tax_exempt) ? ($contract->tax_rate ?? 0) : 0);
            $contractDiscountPercent = $po->contract_discount_percentage ?? ($contract?->discount_percentage ?? 0);

            $rejectionReason = $po->rejection_details['reason'] ?? null;
            if (!$rejectionReason && $feedback?->rejection_reason) {
                $rejectionReason = $feedback->rejection_reason;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'po' => $po,
                    'supplier_feedback' => $feedback,
                    'fulfillment_updates' => $hasFulfillmentUpdatesTable ? $po->supplierFulfillmentUpdates : collect(),
                    'shipment' => $shipment,
                    'delivery_logs' => $deliveryLogs,
                    'supplier_location' => [
                        'latitude' => $portal->latitude ?? $po->supplier?->latitude,
                        'longitude' => $portal->longitude ?? $po->supplier?->longitude,
                        'address' => $portal->address ?? $po->supplier?->address,
                    ],
                    'goods_receipt' => $goodsReceipt,
                    'invoice' => $invoice,
                    'rejection_reason' => $rejectionReason,
                    'contract_tax_rate' => $contractTaxRate,
                    'contract_discount_percent' => $contractDiscountPercent,
                    'contract_is_tax_exempt' => $contract?->is_tax_exempt ?? false,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier PO detail failed.', [
                'user_id' => Auth::id(),
                'purchase_order_id' => $id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error fetching PO: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return only tracking data for a PO visible to the authenticated supplier.
     */
    public function getPODeliveryTracking($id): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', Auth::id())->firstOrFail();
        abort_unless($portal->isVerified(), 409, 'Your supplier account is not verified yet.');

        $po = PurchaseOrder::with(['supplier:id,supplier_name,address,latitude,longitude', 'branch:id,name,address,city,latitude,longitude'])
            ->whereIn('supplier_id', $portal->linkedSupplierIds())
            ->findOrFail($id);

        $shipment = \App\Models\Procurement\Shipping\PurchaseOrderShipment::with(['branch', 'supplier'])
            ->where('purchase_order_id', $po->id)
            ->first();
        $logs = $shipment
            ? PurchaseOrderDeliveryLog::with(['creator:id,fname,lname', 'attachments'])
                ->where('shipment_id', $shipment->id)
                ->orderByDesc('logged_at')
                ->orderByDesc('id')
                ->get()
            : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'shipment' => $shipment,
                'delivery_logs' => $logs,
                'supplier_location' => [
                    'latitude' => $portal->latitude ?? $po->supplier?->latitude,
                    'longitude' => $portal->longitude ?? $po->supplier?->longitude,
                    'address' => $portal->address ?? $po->supplier?->address,
                ],
            ],
        ]);
    }

    /**
     * Submit PO feedback (accept/reject + delivery schedule)
     * POST /api/supplier-portal/po-feedbacks
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'response' => 'required|in:accepted,rejected',
            'fulfillment_method' => 'prohibited',
            'rejection_reason' => 'required_if:response,rejected|string',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'delivery_quantity' => 'nullable|integer|min:1',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            if (!$portal->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier account is not verified yet.',
                ], 409);
            }

            $po = PurchaseOrder::findOrFail($request->purchase_order_id);

            if (!$portal->linkedSupplierIds()->contains((int) $po->supplier_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this purchase order.',
                ], 403);
            }

            if ($request->response === 'accepted' && !in_array($po->fulfillment_method, ['store_pickup', 'supplier_delivery'], true)) {
                return response()->json(['success' => false, 'message' => 'The store has not selected a fulfillment method for this PO.'], 422);
            }

            // Create or update feedback
            $feedback = SupplierPOFeedback::updateOrCreate(
                [
                    'supplier_id' => $po->supplier_id,
                    'purchase_order_id' => $request->purchase_order_id,
                ],
                [
                    'supplier_portal_id' => $portal->id,
                    'response_source' => 'portal',
                    'response_channel' => 'portal',
                    'response_date' => now()->toDateString(),
                    'recorded_by' => null,
                    'response' => $request->response,
                    'fulfillment_method' => $request->response === 'accepted' ? $po->fulfillment_method : null,
                    'rejection_reason' => $request->get('rejection_reason'),
                    'expected_delivery_date' => $request->get('expected_delivery_date'),
                    'delivery_quantity' => $request->get('delivery_quantity'),
                    'delivery_notes' => $request->get('delivery_notes'),
                    'submitted_at' => now(),
                ]
            );

            if ($request->response === 'accepted') {
                $po->supplier_fulfillment_status = 'preparing';
                $po->save();
                $po->markSupplierAccepted();

                ActivityLog::record(
                    'po_supplier_accepted',
                    "PO {$po->po_number} accepted by supplier.",
                    [
                        'po_number' => $po->po_number,
                        'supplier_id' => $po->supplier_id,
                        'fulfillment_method' => $po->fulfillment_method,
                    ],
                    'purchase_order',
                    $po->id
                );

                $creatorUserId = $po->createdBy?->user_id;
                if ($creatorUserId) {
                    $this->notify($creatorUserId, [
                        'store_id' => $po->store_id,
                        'branch_id' => $po->branch_id,
                        'module' => 'procurement',
                        'entity_type' => 'purchase_order',
                        'entity_id' => $po->id,
                        'action' => 'supplier_accepted',
                        'title' => 'Supplier Accepted PO',
                        'message' => "Supplier accepted PO {$po->po_number}.",
                        'severity' => 'success',
                        'link' => "/system/procurement/purchase-orders/{$po->id}",
                    ]);
                }
            }

            if ($request->response === 'rejected') {
                $po->markSupplierDeclined($request->get('rejection_reason'));

                ActivityLog::record(
                    'po_supplier_declined',
                    "PO {$po->po_number} declined by supplier.",
                    [
                        'po_number' => $po->po_number,
                        'supplier_id' => $po->supplier_id,
                        'reason' => $request->get('rejection_reason'),
                    ],
                    'purchase_order',
                    $po->id
                );

                $creatorUserId = $po->createdBy?->user_id;
                if ($creatorUserId) {
                    $this->notify($creatorUserId, [
                        'store_id' => $po->store_id,
                        'branch_id' => $po->branch_id,
                        'module' => 'procurement',
                        'entity_type' => 'purchase_order',
                        'entity_id' => $po->id,
                        'action' => 'supplier_declined',
                        'title' => 'Supplier Declined PO',
                        'message' => "Supplier declined PO {$po->po_number}.",
                        'severity' => 'danger',
                        'link' => "/system/procurement/purchase-orders/{$po->id}",
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'PO feedback submitted successfully.',
                'data' => $feedback,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting feedback: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateFulfillment(Request $request, int $id): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', (int) $request->user()->id)->firstOrFail();
        abort_unless($portal->isVerified(), 409, 'Your supplier account is not verified yet.');
        $po = PurchaseOrder::query()->whereIn('supplier_id', $portal->linkedSupplierIds())->findOrFail($id);
        abort_unless($po->status === 'supplier_accepted' || $po->status === 'in_transit', 422, 'This PO is not in the supplier fulfillment workflow.');

        $validated = $request->validate([
            'status' => ['required', 'in:ready_for_pickup,dispatched,delivered'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['required_if:status,dispatched,delivered', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $current = strtolower(trim((string) ($po->supplier_fulfillment_status ?: 'preparing')));
        $current = str_replace([' ', '-'], '_', $current);
        $next = $validated['status'];
        if ($po->fulfillment_method === 'store_pickup') {
            if ($current === 'ready_for_pickup' && $next === 'ready_for_pickup') {
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase order is already ready for pickup.',
                    'data' => ['purchase_order' => $po],
                ]);
            }
            abort_unless($current === 'preparing' && $next === 'ready_for_pickup', 422, 'Store pickup can only move from Preparing to Ready for Pickup.');
        } else {
            $valid = ($current === 'preparing' && $next === 'dispatched')
                || ($current === 'dispatched' && $next === 'delivered');
            abort_unless($valid, 422, 'Supplier delivery must move from Preparing to Dispatched, then Delivered.');
        }

        $photoPaths = [];
        foreach ($request->file('photos', []) as $photo) {
            $photoPaths[] = $photo->store("supplier/purchase-orders/{$po->id}/fulfillment", 'public');
        }

        $update = SupplierPOFulfillmentUpdate::create([
            'purchase_order_id' => $po->id,
            'supplier_portal_id' => $portal->id,
            'created_by' => $request->user()->id,
            'status' => $next,
            'notes' => $validated['notes'] ?? null,
            'photo_paths' => $photoPaths,
        ]);

        $po->supplier_fulfillment_status = $next;
        if ($next === 'dispatched') $po->markInTransit();
        elseif ($next === 'delivered') $po->markDelivered();
        else $po->save();

        ActivityLog::record(
            'po_supplier_fulfillment_updated',
            "Supplier updated PO {$po->po_number} fulfillment to " . str_replace('_', ' ', $next) . '.',
            ['status' => $next, 'photos' => count($photoPaths)],
            'purchase_order',
            $po->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase order fulfillment updated.',
            'data' => ['update' => $update->load('creator'), 'purchase_order' => $po->fresh()],
        ]);
    }

    /**
     * Confirm receipt and update delivery schedule
     * POST /api/supplier-portal/po-feedbacks/{id}/confirm-receipt
     */
    public function confirmReceipt($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delivery_quantity' => 'required|integer|min:1',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $feedback = SupplierPOFeedback::where('id', $id)
                ->where('supplier_portal_id', $portal->id)
                ->firstOrFail();

            if (!$feedback->canUpdateDeliverySchedule()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update delivery schedule for this PO.',
                ], 422);
            }

            $feedback->update([
                'receipt_status' => 'confirmed',
                'delivery_quantity' => $request->delivery_quantity,
                'delivery_notes' => $request->get('delivery_notes'),
                'receipt_confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Receipt confirmed successfully.',
                'data' => $feedback,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error confirming receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supplier's PO feedbacks
     * GET /api/supplier-portal/po-feedbacks
     */
    public function getMyFeedbacks(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $portal = SupplierPortal::where('user_id', $user->id)->firstOrFail();

            $feedbacksQuery = $portal->poFeedbacks()
                ->with(['purchaseOrder'])
                ->orderBy('submitted_at', 'desc');

            if ($request->has('purchase_order_id')) {
                $feedbacksQuery->where('purchase_order_id', $request->purchase_order_id);
            }

            $feedbacks = $feedbacksQuery->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $feedbacks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching feedbacks: ' . $e->getMessage(),
            ], 500);
        }
    }
}
