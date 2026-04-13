<?php
// backend/app/Http/Controllers/Procurement/RFQ/RequestForQuotationController.php

namespace App\Http\Controllers\Api\Procurement\RFQ;

use App\Http\Controllers\Controller;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\RFQ\SupplierQuotation;
use App\Models\Procurement\RFQ\SupplierQuotationItem;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Models\Procurement\SupplierPortal\SupplierRFQFeedback;
use App\Models\Procurement\SupplierPortal\SupplierRFQNegotiation;
use App\Models\ProductCatalog\Product;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RequestForQuotationController extends Controller
{
    /**
     * List all RFQs
     * GET /api/procurement/rfqs
     */
    public function index(Request $request): JsonResponse
    {
        $query = RequestForQuotation::with(['createdBy', 'purchaseRequisition'])
            ->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('open')) {
            $query->open();
        }

        if ($request->has('closed')) {
            $query->closed();
        }

        $rfqs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $rfqs,
        ]);
    }

    /**
     * Show single RFQ
     * GET /api/procurement/rfqs/{id}
     */
    public function show(int $id): JsonResponse
    {
        $rfq = RequestForQuotation::with([
            'purchaseRequisition',
            'items.product',
            'items.variation',
            'suppliers.supplier',
            'quotations.supplier',
            'supplierPortalFeedbacks.supplierPortal.supplier',
            'supplierPortalFeedbacks.rfqItem.product',
            'supplierPortalFeedbacks.rfqItem.variation',
            'supplierPortalFeedbacks.negotiations',
            'createdBy',
            'awardedToSupplier'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $rfq,
        ]);
    }

    /**
     * Review supplier portal RFQ feedback
     * POST /api/procurement/rfqs/{id}/portal-feedbacks/{feedbackId}/review
     */
    public function reviewPortalFeedback(Request $request, int $id, int $feedbackId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|min:5',
        ]);

        $rfq = RequestForQuotation::findOrFail($id);
        $feedback = SupplierRFQFeedback::with(['supplierPortal.supplier', 'rfqItem'])
            ->where('rfq_id', $rfq->id)
            ->findOrFail($feedbackId);

        $reviewedBy = auth()->user()?->employee?->id ?? auth()->id();
        $feedback->update([
            'status' => $validated['status'],
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null,
        ]);

        if ($validated['status'] === 'approved') {
            // Reject other feedbacks for the same RFQ item
            SupplierRFQFeedback::where('rfq_item_id', $feedback->rfq_item_id)
                ->where('id', '!=', $feedback->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'reviewed_by' => $reviewedBy,
                    'reviewed_at' => now(),
                    'rejection_reason' => 'Another quote was approved for this item.',
                ]);

            // Use relation to get supplier_id; the attribute supplier_portal is just the FK
            $approvedSupplierId = $feedback->supplierPortal?->supplier_id;
            if ($approvedSupplierId) {
                DB::table('rfq_suppliers')
                    ->where('rfq_id', $rfq->id)
                    ->where('supplier_id', '!=', $approvedSupplierId)
                    ->whereIn('status', ['pending', 'submitted'])
                    ->update([
                        'status' => 'declined',
                        'responded_at' => now(),
                        'decline_reason' => 'Another supplier was approved for this RFQ.',
                    ]);
            }

            // Update supplier_quotations: mark winner quotation accepted and others rejected
            try {
                if (\Schema::hasTable('supplier_quotations')) {
                    $winnerQuotation = \App\Models\Procurement\RFQ\SupplierQuotation::where('rfq_id', $rfq->id)
                        ->where('supplier_id', $approvedSupplierId)
                        ->first();

                    if ($winnerQuotation) {
                        $winnerQuotation->update([
                            'status' => 'accepted',
                            'updated_at' => now(),
                        ]);
                    }

                    \App\Models\Procurement\RFQ\SupplierQuotation::where('rfq_id', $rfq->id)
                        ->where('supplier_id', '!=', $approvedSupplierId)
                        ->update(['status' => 'rejected', 'updated_at' => now()]);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to update supplier_quotations statuses after approval: ' . $e->getMessage());
            }

            $this->updatePurchaseRequisitionStatus($rfq->purchase_requisition_id, 'supplier_selected');
        }

        $this->updateRfqStatus($rfq->id);

        if ($this->isRfqCompleted($rfq->id)) {
            $this->syncApprovedPrices($rfq->id);
        }

        $rfq->refresh();
        if ($rfq->status === 'rejected') {
            $this->updatePurchaseRequisitionStatus($rfq->purchase_requisition_id, 'rejected');
        }

        return response()->json([
            'success' => true,
            'message' => 'Supplier response reviewed.',
            'data' => $feedback->fresh(),
        ]);
    }

    /**
     * Create negotiation record for supplier feedback
     * POST /api/procurement/rfqs/{id}/portal-feedbacks/{feedbackId}/negotiate
     */
    public function negotiatePortalFeedback(Request $request, int $id, int $feedbackId): JsonResponse
    {
        $validated = $request->validate([
            'counter_price' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rfq = RequestForQuotation::findOrFail($id);
        $feedback = SupplierRFQFeedback::with(['supplierPortal', 'rfqItem'])
            ->where('rfq_id', $rfq->id)
            ->findOrFail($feedbackId);

        $negotiation = SupplierRFQNegotiation::create([
            'supplier_rfq_feedback_id' => $feedback->id,
            'supplier_portal_id' => $feedback->supplier_portal_id,
            'rfq_id' => $rfq->id,
            'rfq_item_id' => $feedback->rfq_item_id,
            'counter_price' => $validated['counter_price'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->user()?->employee?->id ?? auth()->id(),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Negotiation sent to supplier.',
            'data' => $negotiation,
        ], 201);
    }

    /**
     * Bulk approve feedbacks
     * POST /api/procurement/rfqs/{id}/portal-feedbacks/bulk-approve
     */
    public function bulkApprovePortalFeedbacks(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'feedback_ids' => 'required|array|min:1',
            'feedback_ids.*' => 'integer|exists:supplier_rfq_feedbacks,id',
        ]);

        $rfq = RequestForQuotation::findOrFail($id);
        $feedbacks = SupplierRFQFeedback::where('rfq_id', $rfq->id)
            ->whereIn('id', $validated['feedback_ids'])
            ->get();

        foreach ($feedbacks as $feedback) {
            $feedback->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            SupplierRFQFeedback::where('rfq_item_id', $feedback->rfq_item_id)
                ->where('id', '!=', $feedback->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'rejection_reason' => 'Another quote was approved for this item.',
                ]);
        }

        $this->updateRfqStatus($rfq->id);

        if ($this->isRfqCompleted($rfq->id)) {
            $this->syncApprovedPrices($rfq->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected responses approved.',
        ]);
    }

    private function updateRfqStatus(int $rfqId): void
    {
        $rfq = RequestForQuotation::with('items')->findOrFail($rfqId);
        $itemIds = $rfq->items->pluck('id')->all();

        $approvedByItem = SupplierRFQFeedback::whereIn('rfq_item_id', $itemIds)
            ->where('status', 'approved')
            ->select('rfq_item_id')
            ->distinct()
            ->pluck('rfq_item_id')
            ->all();

        $allApproved = count($approvedByItem) === count($itemIds) && count($itemIds) > 0;
        $anyApproved = count($approvedByItem) > 0;

        if ($allApproved) {
            $rfq->update(['status' => 'approved']);
            return;
        }

        if ($anyApproved) {
            $rfq->update(['status' => 'partially_approved']);
            return;
        }

        $anyPending = SupplierRFQFeedback::where('rfq_id', $rfqId)->where('status', 'pending')->exists();
        if ($anyPending) {
            $rfq->update(['status' => 'receiving']);
            return;
        }

        $rfq->update(['status' => 'rejected']);
    }

    private function isRfqCompleted(int $rfqId): bool
    {
        $rfq = RequestForQuotation::with('items')->findOrFail($rfqId);
        $itemIds = $rfq->items->pluck('id')->all();

        $approvedByItem = SupplierRFQFeedback::whereIn('rfq_item_id', $itemIds)
            ->where('status', 'approved')
            ->select('rfq_item_id')
            ->distinct()
            ->pluck('rfq_item_id')
            ->all();

        return count($itemIds) > 0 && count($approvedByItem) === count($itemIds);
    }

    private function syncApprovedPrices(int $rfqId): void
    {
        $approvedFeedbacks = SupplierRFQFeedback::with(['supplierPortal', 'rfqItem'])
            ->where('rfq_id', $rfqId)
            ->where('status', 'approved')
            ->get();

        foreach ($approvedFeedbacks as $feedback) {
            $supplierId = $feedback->supplierPortal?->supplier_id;
            $productId = $feedback->rfqItem?->product_id;

            if (!$supplierId || !$productId) {
                continue;
            }

            $existing = DB::table('supplier_products')
                ->where('supplier_id', $supplierId)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                DB::table('supplier_products')
                    ->where('supplier_id', $supplierId)
                    ->where('product_id', $productId)
                    ->update([
                        'supplier_price' => $feedback->quoted_price,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('supplier_products')->insert([
                    'supplier_id' => $supplierId,
                    'product_id' => $productId,
                    'supplier_price' => $feedback->quoted_price,
                    'minimum_order_quantity' => 1,
                    'lead_time_days' => 7,
                    'is_preferred_supplier' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Product::where('id', $productId)->update([
                'cost_price' => $feedback->quoted_price,
            ]);
        }
    }

    private function syncAwardedSupplierProducts(int $rfqId, int $supplierId): void
    {
        $quotation = SupplierQuotation::where('rfq_id', $rfqId)
            ->where('supplier_id', $supplierId)
            ->first();

        if (!$quotation) {
            return;
        }

        $awardedRows = DB::table('supplier_quotation_items as sqi')
            ->join('rfq_items as ri', 'ri.id', '=', 'sqi.rfq_item_id')
            ->where('sqi.quotation_id', $quotation->id)
            ->select('ri.product_id', 'sqi.unit_price')
            ->get();

        foreach ($awardedRows as $row) {
            $productId = (int) ($row->product_id ?? 0);
            $unitPrice = (float) ($row->unit_price ?? 0);

            if ($productId <= 0 || $unitPrice <= 0) {
                continue;
            }

            $existing = DB::table('supplier_products')
                ->where('supplier_id', $supplierId)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                DB::table('supplier_products')
                    ->where('supplier_id', $supplierId)
                    ->where('product_id', $productId)
                    ->update([
                        'supplier_price' => $unitPrice,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('supplier_products')->insert([
                    'supplier_id' => $supplierId,
                    'product_id' => $productId,
                    'supplier_price' => $unitPrice,
                    'minimum_order_quantity' => 1,
                    'lead_time_days' => 7,
                    'is_preferred_supplier' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Product::where('id', $productId)->update([
                'cost_price' => $unitPrice,
            ]);
        }
    }

    /**
     * Create supplier-split RFQs from a single requisition
     * POST /api/procurement/rfqs/create-from-requisition-split
     */
    public function createFromRequisitionSplit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_requisition_id' => 'required|exists:purchase_requisitions,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'rfq_type' => 'nullable|string|in:purchase,service,both',
            'currency' => 'nullable|string|max:3',
            'shipping_terms' => 'nullable|string',
            'instructions' => 'nullable|string',
            'qualification_requirements' => 'nullable|string',
            'issue_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $storeId = (int) (auth()->user()->store_id ?? 0);

            $requisition = PurchaseRequisition::with(['items.product'])
                ->where('store_id', $storeId)
                ->findOrFail((int) $validated['purchase_requisition_id']);

            if ($requisition->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase_requisition_id' => 'Cannot create RFQs from an empty requisition.',
                ]);
            }

            $itemsBySupplier = [];
            $unassignedItemIds = [];

            foreach ($requisition->items as $requisitionItem) {
                $supplierId = $this->resolveSupplierIdForRequisitionItem($requisitionItem, $storeId);
                if (!$supplierId) {
                    $unassignedItemIds[] = $requisitionItem->id;
                    continue;
                }

                if (!$this->supplierCanProvideProduct($supplierId, (int) $requisitionItem->product_id, $storeId)) {
                    throw ValidationException::withMessages([
                        'items' => "Product {$requisitionItem->product_id} is not mapped to supplier {$supplierId} for this store.",
                    ]);
                }

                $itemsBySupplier[$supplierId][] = $requisitionItem;
            }

            if (!empty($unassignedItemIds)) {
                throw ValidationException::withMessages([
                    'items' => 'Some requisition items have no supplier assignment and cannot be grouped into RFQs. Item IDs: ' . implode(', ', $unassignedItemIds),
                ]);
            }

            if (empty($itemsBySupplier)) {
                throw ValidationException::withMessages([
                    'items' => 'No supplier-resolved requisition items were found for RFQ creation.',
                ]);
            }

            $createdRfqs = [];
            foreach ($itemsBySupplier as $supplierId => $requisitionItems) {
                $rfq = RequestForQuotation::create([
                    'rfq_number' => $this->generateRfqNumber(),
                    'store_id' => $storeId,
                    'purchase_requisition_id' => $requisition->id,
                    'title' => $validated['title'] ?? "RFQ for {$requisition->pr_number} - Supplier {$supplierId}",
                    'description' => $validated['description'] ?? $requisition->reason,
                    'rfq_type' => $validated['rfq_type'] ?? 'purchase',
                    'currency' => $validated['currency'] ?? 'PHP',
                    'shipping_terms' => $validated['shipping_terms'] ?? null,
                    'instructions' => $validated['instructions'] ?? null,
                    'qualification_requirements' => $validated['qualification_requirements'] ?? null,
                    'issue_date' => $validated['issue_date'],
                    'status' => 'draft',
                    'created_by' => auth()->user()?->employee?->id ?? auth()->id(),
                ]);

                foreach ($requisitionItems as $requisitionItem) {
                    RFQItem::create([
                        'rfq_id' => $rfq->id,
                        'product_id' => $requisitionItem->product_id,
                        'variation_id' => $requisitionItem->variation_id,
                        'quantity' => $requisitionItem->quantity_requested,
                        'specifications' => $requisitionItem->specifications ?? null,
                        'requirements' => null,
                    ]);
                }

                $rfq->inviteSupplier((int) $supplierId);

                $this->notifyRequisitionRequesterRfQCreated($requisition->id, $rfq->id, $rfq->rfq_number);
                $createdRfqs[] = $rfq->load(['items.product', 'items.variation', 'suppliers.supplier']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RFQs created and grouped by supplier successfully',
                'data' => [
                    'purchase_requisition_id' => $requisition->id,
                    'created_count' => count($createdRfqs),
                    'rfqs' => $createdRfqs,
                ],
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('RFQ Split Creation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier-split RFQs: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Create new RFQ
     * POST /api/procurement/rfqs
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rfq_type' => 'nullable|string|in:purchase,service,both',
            'currency' => 'nullable|string|max:3',
            'shipping_terms' => 'nullable|string',
            'instructions' => 'nullable|string',
            'qualification_requirements' => 'nullable|string',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.specifications' => 'nullable|string',
            'items.*.requirements' => 'nullable|string',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
        ]);

        DB::beginTransaction();
        try {
            // Generate RFQ number using datetime for uniqueness
            $rfqNumber = 'RFQ-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

            // Create RFQ with all fields
            $rfq = RequestForQuotation::create([
                'rfq_number' => $rfqNumber,
                'store_id' => auth()->user()->store_id,
                'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'rfq_type' => $validated['rfq_type'] ?? 'purchase',
                'currency' => $validated['currency'] ?? 'PHP',
                'shipping_terms' => $validated['shipping_terms'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'qualification_requirements' => $validated['qualification_requirements'] ?? null,
                'issue_date' => $validated['issue_date'],
            'status' => 'draft',
            'created_by' => auth()->user()?->employee?->id ?? auth()->id(),
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                RFQItem::create([
                    'rfq_id' => $rfq->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'specifications' => $item['specifications'] ?? null,
                    'requirements' => $item['requirements'] ?? null,
                ]);
            }

            // Invite suppliers
            foreach ($validated['supplier_ids'] as $supplierId) {
                // Only invite suppliers that have an active contract with this store
                $hasActiveContract = SupplierContract::where('store_id', auth()->user()->store_id)
                    ->where('supplier_id', $supplierId)
                    ->active()
                    ->exists();

                if (!$hasActiveContract) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot invite supplier (ID: ' . $supplierId . ') to RFQ: no active contract exists with this store.',
                    ], 422);
                }

                $rfq->inviteSupplier($supplierId);
            }

            if (!empty($validated['purchase_requisition_id'])) {
                $this->notifyRequisitionRequesterRfQCreated(
                    (int) $validated['purchase_requisition_id'],
                    $rfq->id,
                    $rfq->rfq_number
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RFQ created successfully',
                'data' => $rfq->load(['items.product', 'items.variation', 'suppliers']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('RFQ Creation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create RFQ: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Update RFQ
     * PUT /api/procurement/rfqs/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rfq = RequestForQuotation::with(['items', 'suppliers'])->findOrFail($id);

        if ($rfq->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft RFQs can be updated',
            ], 422);
        }

        $validated = $request->validate([
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rfq_type' => 'nullable|string|in:purchase,service,both',
            'currency' => 'nullable|string|max:3',
            'shipping_terms' => 'nullable|string',
            'instructions' => 'nullable|string',
            'qualification_requirements' => 'nullable|string',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.specifications' => 'nullable|string',
            'items.*.requirements' => 'nullable|string',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
        ]);

        DB::beginTransaction();
        try {
            $rfq->update([
                'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'rfq_type' => $validated['rfq_type'] ?? 'purchase',
                'currency' => $validated['currency'] ?? 'PHP',
                'shipping_terms' => $validated['shipping_terms'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'qualification_requirements' => $validated['qualification_requirements'] ?? null,
                'issue_date' => $validated['issue_date'],
            ]);

            $rfq->items()->delete();
            foreach ($validated['items'] as $item) {
                RFQItem::create([
                    'rfq_id' => $rfq->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'specifications' => $item['specifications'] ?? null,
                    'requirements' => $item['requirements'] ?? null,
                ]);
            }

            $rfq->suppliers()->delete();
            foreach ($validated['supplier_ids'] as $supplierId) {
                $rfq->inviteSupplier($supplierId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RFQ updated successfully',
                'data' => $rfq->load(['items.product', 'items.variation', 'suppliers']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('RFQ Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update RFQ: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Send RFQ to suppliers
     * POST /api/procurement/rfqs/{id}/send
     */
    public function send(int $id): JsonResponse
    {
        $rfq = RequestForQuotation::with('suppliers')->findOrFail($id);

        if ($rfq->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft RFQs can be sent',
            ], 422);
        }

        if ($rfq->suppliers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send RFQ without suppliers',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $rfq->update(['status' => 'pending']);
            $this->updatePurchaseRequisitionStatus($rfq->purchase_requisition_id, 'rfq_sent');

            // Create supplier quotation placeholders per item for each invited supplier
            $rfq->load(['items']);
            foreach ($rfq->suppliers as $rfqSupplier) {
                $supplierId = $rfqSupplier->supplier_id;

                // try to find active contract tax rate for this supplier+store
                $contract = SupplierContract::where('supplier_id', $supplierId)
                    ->where('store_id', $rfq->store_id)
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->first();

                $contractTaxRate = ($contract && !$contract->is_tax_exempt) ? ($contract->tax_rate ?? 0) : 0;

                // Create or find a master quotation record for this RFQ + supplier
                // Create minimal master quotation record (avoid writing migration-specific columns)
                // Ensure we include required non-null columns present in the actual table
                $quotationNumber = 'Q-' . $rfq->id . '-' . $supplierId . '-' . time();

                // Build creation attributes defensively based on actual table columns
                $columnRows = DB::select("SHOW COLUMNS FROM supplier_quotations");
                $createAttrs = [
                    'rfq_id' => $rfq->id,
                    'supplier_id' => $supplierId,
                    'quotation_number' => $quotationNumber,
                ];

                foreach ($columnRows as $col) {
                    $field = is_object($col) ? ($col->Field ?? null) : ($col['Field'] ?? null);
                    if (!$field) {
                        continue;
                    }

                    switch ($field) {
                        case 'quotation_date':
                            $createAttrs['quotation_date'] = now();
                            break;
                        case 'valid_until':
                            $createAttrs['valid_until'] = now()->addDays(30);
                            break;
                        case 'subtotal':
                        case 'tax_amount':
                        case 'shipping_cost':
                            $createAttrs[$field] = 0;
                            break;
                        case 'total_amount':
                        case 'total_price':
                            $createAttrs[$field] = 0;
                            break;
                        case 'payment_terms':
                            $createAttrs['payment_terms'] = 'net_30';
                            break;
                        case 'delivery_days':
                            $createAttrs['delivery_days'] = 0;
                            break;
                        default:
                            // leave other columns alone
                            break;
                    }
                }

                // Use query builder for master record to ensure we can set any DB column
                $existing = DB::table('supplier_quotations')
                    ->where('rfq_id', $rfq->id)
                    ->where('supplier_id', $supplierId)
                    ->first();

                if ($existing) {
                    $quotationId = $existing->id;
                    $quotation = SupplierQuotation::find($quotationId);
                } else {
                    $now = now();
                    $toInsert = $createAttrs;
                    if (!isset($toInsert['created_at'])) {
                        $toInsert['created_at'] = $now;
                    }
                    if (!isset($toInsert['updated_at'])) {
                        $toInsert['updated_at'] = $now;
                    }
                    $quotationId = DB::table('supplier_quotations')->insertGetId($toInsert);
                    $quotation = SupplierQuotation::find($quotationId);
                }

                // Create per-item quotation rows under the master quotation if table exists
                if (Schema::hasTable('supplier_quotation_items')) {
                    foreach ($rfq->items as $item) {
                        SupplierQuotationItem::firstOrCreate([
                            'quotation_id' => $quotation->id,
                            'rfq_item_id' => $item->id,
                        ], [
                            'unit_price' => 0,
                            'quantity' => $item->quantity ?? 1,
                            'discount_percent' => 0,
                            'line_total' => 0,
                            'notes' => null,
                        ]);
                    }
                }
            }

            // TODO: Send email notifications to suppliers

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RFQ sent to suppliers successfully',
                'data' => $rfq->fresh()->load(['items', 'suppliers', 'quotations']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('RFQ Send Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send RFQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close RFQ
     * POST /api/procurement/rfqs/{id}/close
     */
    public function close(int $id): JsonResponse
    {
        $rfq = RequestForQuotation::with('quotations')->findOrFail($id);

        if ($rfq->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'RFQ is already closed',
            ], 422);
        }

        $rfq->close();

        return response()->json([
            'success' => true,
            'message' => 'RFQ closed successfully',
            'data' => $rfq->fresh(),
        ]);
    }

    /**
     * Award RFQ to supplier
     * POST /api/procurement/rfqs/{id}/award
     */
    public function award(Request $request, int $id): JsonResponse
    {
        $rfq = RequestForQuotation::with(['suppliers.supplier', 'quotations'])->findOrFail($id);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'evaluation_notes' => 'nullable|string',
        ]);

        if ($rfq->status === 'awarded') {
            return response()->json([
                'success' => false,
                'message' => 'RFQ has already been awarded',
            ], 422);
        }

        $rfq->awardToSupplier($validated['supplier_id'], $validated['evaluation_notes'] ?? null);

        // Update RFQ supplier statuses
        foreach ($rfq->suppliers as $rfqSupplier) {
            $status = $rfqSupplier->supplier_id === (int) $validated['supplier_id'] ? 'submitted' : 'declined';
            $rfqSupplier->update([
                'status' => $status,
                'responded_at' => now(),
            ]);
        }

        // Keep supplier-product mapping in sync even when awarding directly.
        $this->syncAwardedSupplierProducts($rfq->id, (int) $validated['supplier_id']);

        // Notify suppliers of award outcome
        try {
            $winnerSupplier = $rfq->suppliers->firstWhere('supplier_id', (int) $validated['supplier_id'])?->supplier;
            $winnerQuotation = $rfq->quotations->firstWhere('supplier_id', (int) $validated['supplier_id']);
            $portalUrl = config('app.url') . '/supplier-portal/rfqs/' . $rfq->id;

            if ($winnerSupplier && $winnerSupplier->email && $winnerQuotation) {
                \Mail::to($winnerSupplier->email)->send(
                    new \App\Mail\Procurement\RFQAwardWinnerMail($rfq, $winnerSupplier, $winnerQuotation, $portalUrl)
                );
            }

            foreach ($rfq->suppliers as $rfqSupplier) {
                if ($rfqSupplier->supplier_id === (int) $validated['supplier_id']) {
                    continue;
                }

                $supplier = $rfqSupplier->supplier;
                if (!$supplier || !$supplier->email) {
                    continue;
                }

                $quotation = $rfq->quotations->firstWhere('supplier_id', $supplier->id);
                if (!$quotation) {
                    continue;
                }

                \Mail::to($supplier->email)->send(
                    new \App\Mail\Procurement\RFQAwardRejectedMail($rfq, $supplier, $quotation, $portalUrl)
                );
            }
        } catch (\Exception $e) {
            \Log::warning('RFQ award email notification failed: ' . $e->getMessage(), [
                'rfq_id' => $rfq->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'RFQ awarded successfully',
            'data' => $rfq->fresh(),
        ]);
    }

    /**
     * Cancel RFQ
     * POST /api/procurement/rfqs/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $rfq = RequestForQuotation::findOrFail($id);

        if ($rfq->status === 'awarded') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel awarded RFQ',
            ], 422);
        }

        $rfq->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'RFQ cancelled successfully',
        ]);
    }

    /**
     * Delete RFQ
     * DELETE /api/procurement/rfqs/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $rfq = RequestForQuotation::findOrFail($id);

        if ($rfq->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft RFQs can be deleted',
            ], 422);
        }

        $rfq->delete();

        return response()->json([
            'success' => true,
            'message' => 'RFQ deleted successfully',
        ]);
    }

    private function updatePurchaseRequisitionStatus(?int $requisitionId, string $status): void
    {
        if (!$requisitionId) {
            return;
        }

        $pr = PurchaseRequisition::find($requisitionId);
        if (!$pr) {
            return;
        }

        $terminalStatuses = ['rejected', 'cancelled', 'delivered'];
        if (in_array($pr->status, $terminalStatuses, true)) {
            return;
        }

        if ($pr->status === $status) {
            return;
        }

        $pr->update(['status' => $status]);
    }

    private function notifyRequisitionRequesterRfQCreated(int $requisitionId, int $rfqId, string $rfqNumber): void
    {
        $pr = PurchaseRequisition::with('requestedBy')->find($requisitionId);
        $requesterUserId = $pr?->requestedBy?->user_id;

        if (!$pr || !$requesterUserId) {
            return;
        }

        $this->notify((int) $requesterUserId, [
            'store_id' => $pr->store_id,
            'branch_id' => $pr->branch_id,
            'module' => 'procurement',
            'entity_type' => 'request_for_quotation',
            'entity_id' => $rfqId,
            'action' => 'created',
            'title' => 'RFQ Created From Your PR',
            'message' => "{$rfqNumber} was created from PR {$pr->pr_number}.",
            'severity' => 'info',
            'link' => "/system/procurement/rfqs/{$rfqId}",
        ]);
    }

    private function generateRfqNumber(): string
    {
        return 'RFQ-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function resolveSupplierIdForRequisitionItem(PurchaseRequisitionItem $item, int $storeId): ?int
    {
        if (!empty($item->selected_supplier_id)) {
            return (int) $item->selected_supplier_id;
        }

        $supplierIds = DB::table('supplier_products')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id')
            ->where('supplier_products.product_id', $item->product_id)
            ->where('suppliers.store_id', $storeId)
            ->pluck('supplier_products.supplier_id')
            ->unique()
            ->values();

        if ($supplierIds->count() === 1) {
            return (int) $supplierIds->first();
        }

        return null;
    }

    private function supplierCanProvideProduct(int $supplierId, int $productId, int $storeId): bool
    {
        return DB::table('supplier_products')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id')
            ->where('supplier_products.supplier_id', $supplierId)
            ->where('supplier_products.product_id', $productId)
            ->where('suppliers.store_id', $storeId)
            ->exists();
    }
}
