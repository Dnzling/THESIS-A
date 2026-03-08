<?php
// backend/app/Http/Controllers/Procurement/RFQ/RequestForQuotationController.php

namespace App\Http\Controllers\Api\Procurement\RFQ;

use App\Http\Controllers\Controller;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
            'suppliers',
            'quotations.supplier',
            'createdBy',
            'awardedToSupplier'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $rfq,
        ]);
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
            'issue_date' => 'required|date',
            'deadline_date' => 'required|date|after:issue_date',
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
            // Generate RFQ number
            $lastRFQ = RequestForQuotation::latest()->first();
            $rfqNumber = 'RFQ-' . date('Y') . '-' . str_pad(($lastRFQ?->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            // Create RFQ
            $rfq = RequestForQuotation::create([
                'rfq_number' => $rfqNumber,
                'store_id' => auth()->user()->store_id,
                'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'issue_date' => $validated['issue_date'],
                'deadline_date' => $validated['deadline_date'],
                'status' => 'draft',
                'created_by' => auth()->id(),
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
                $rfq->inviteSupplier($supplierId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RFQ created successfully',
                'data' => $rfq->load(['items.product', 'suppliers']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create RFQ',
                'error' => $e->getMessage(),
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

        $rfq->update(['status' => 'sent']);

        // TODO: Send email notifications to suppliers

        return response()->json([
            'success' => true,
            'message' => 'RFQ sent to suppliers successfully',
            'data' => $rfq->fresh(),
        ]);
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
        $rfq = RequestForQuotation::findOrFail($id);

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
}