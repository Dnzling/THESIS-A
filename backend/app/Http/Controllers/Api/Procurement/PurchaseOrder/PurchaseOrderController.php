<?php
// backend/app/Http/Controllers/Procurement/PurchaseOrder/PurchaseOrderController.php

namespace App\Http\Controllers\Api\Procurement\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\PurchaseOrder\PurchaseOrderItem;
use App\Models\Procurement\Config\ProcurementSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * List all purchase orders
     * GET /api/procurement/purchase-orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userStoreId = $user ? $user->store_id : null;
        
        \Log::info("🔍 PO INDEX REQUEST", [
            'user_id' => $user ? $user->id : 'NOT_AUTHENTICATED',
            'user_store_id' => $userStoreId,
            'authenticated' => $user ? true : false,
        ]);
        
        $query = PurchaseOrder::with(['branch', 'supplier', 'createdBy'])
            ->where('store_id', $userStoreId);

        // Filters - only apply if values are actually provided
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('supplier_id') && $request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        \Log::info("🔍 QUERY DEBUG", [
            'query' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'total_results' => $orders->total(),
            'current_page_count' => count($orders->items()),
        ]);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Show single purchase order
     * GET /api/procurement/purchase-orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        $po = PurchaseOrder::with([
            'branch',
            'supplier',
            'purchaseRequisition',
            'rfq',
            'supplierQuotation',
            'items.product',
            'items.variation',
            'createdBy',
            'goodsReceipts'
        ])->where('store_id', auth()->user()->store_id)
          ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $po,
        ]);
    }

    /**
     * Create purchase order
     * POST /api/procurement/purchase-orders
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'rfq_id' => 'nullable|exists:request_for_quotations,id',
            'supplier_quotation_id' => 'nullable|exists:supplier_quotations,id',
            'status' => 'nullable|in:draft,pending_approval',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after:order_date',
            'payment_terms' => 'required|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate unique PO number using date/time  
            $timestamp = now()->format('YmdHis');
            $randomSuffix = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $poNumber = 'PO-' . $timestamp . '-' . $randomSuffix;

            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;

            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['unit_cost'] * $item['quantity_ordered'];
                
                // Apply discount
                if (isset($item['discount_percent'])) {
                    $itemSubtotal -= $itemSubtotal * ($item['discount_percent'] / 100);
                }

                $subtotal += $itemSubtotal;

                // Calculate tax
                $taxRate = $item['tax_rate'] ?? 12.00;
                $taxAmount += $itemSubtotal * ($taxRate / 100);
            }

            $shippingCost = $validated['shipping_cost'] ?? 0;
            $discountAmount = $validated['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;

            // Get procurement settings for approval tiers
            $settings = ProcurementSettings::where('store_id', auth()->user()->store_id)->first();
            $approvalTier = $settings?->getApprovalTierForAmount($totalAmount);

            // Check if RFQ is required
            $rfqRequired = $settings?->shouldRequireRFQ($totalAmount) ?? false;

            // Create PO
            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'store_id' => auth()->user()->store_id,
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $validated['supplier_id'],
                'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
                'rfq_id' => $validated['rfq_id'] ?? null,
                'supplier_quotation_id' => $validated['supplier_quotation_id'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'approval_tier_level' => $approvalTier['level'] ?? null,
                'required_approvers' => $approvalTier['approvers'] ?? [],
                'rfq_required' => $rfqRequired,
                'payment_status' => 'pending',
                'payment_terms' => $validated['payment_terms'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'created_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
            ]);

            // Calculate payment due date
            $paymentDueDays = match($validated['payment_terms']) {
                'net_7' => 7,
                'net_15' => 15,
                'net_30' => 30,
                'net_60' => 60,
                default => 0,
            };
            $po->payment_due_date = now()->addDays($paymentDueDays);
            $po->save();

            // Create items
            foreach ($validated['items'] as $item) {
                $lineTotal = $item['unit_cost'] * $item['quantity_ordered'];
                
                if (isset($item['discount_percent'])) {
                    $lineTotal -= $lineTotal * ($item['discount_percent'] / 100);
                }

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                    'tax_rate' => $item['tax_rate'] ?? 12.00,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'line_total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $po->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update purchase order
     * PUT /api/procurement/purchase-orders/{id}
     * Only allows draft POs to be edited
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        // Only draft POs can be edited
        if ($po->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft purchase orders can be edited',
            ], 422);
        }

        $validated = $request->validate([
            'branch_id' => 'sometimes|required|exists:branches,id',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'order_date' => 'sometimes|required|date',
            'expected_delivery_date' => 'sometimes|required|date|after:order_date',
            'payment_terms' => 'sometimes|required|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'shipping_cost' => 'sometimes|nullable|numeric|min:0',
            'discount_amount' => 'sometimes|nullable|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
            'terms_conditions' => 'sometimes|nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update basic info
            $po->update([
                'branch_id' => $validated['branch_id'] ?? $po->branch_id,
                'supplier_id' => $validated['supplier_id'] ?? $po->supplier_id,
                'order_date' => $validated['order_date'] ?? $po->order_date,
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? $po->expected_delivery_date,
                'payment_terms' => $validated['payment_terms'] ?? $po->payment_terms,
                'shipping_cost' => $validated['shipping_cost'] ?? $po->shipping_cost,
                'discount_amount' => $validated['discount_amount'] ?? $po->discount_amount,
                'notes' => $validated['notes'] ?? $po->notes,
                'terms_conditions' => $validated['terms_conditions'] ?? $po->terms_conditions,
            ]);

            // Update items if provided
            if (isset($validated['items'])) {
                // Delete existing items
                $po->items()->delete();

                // Calculate new totals
                $subtotal = 0;
                $taxAmount = 0;

                foreach ($validated['items'] as $item) {
                    $itemSubtotal = $item['unit_cost'] * $item['quantity_ordered'];
                    
                    if (isset($item['discount_percent'])) {
                        $itemSubtotal -= $itemSubtotal * ($item['discount_percent'] / 100);
                    }

                    $subtotal += $itemSubtotal;
                    $taxRate = $item['tax_rate'] ?? 12.00;
                    $taxAmount += $itemSubtotal * ($taxRate / 100);

                    // Create new item
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity_ordered' => $item['quantity_ordered'],
                        'unit_cost' => $item['unit_cost'],
                        'tax_rate' => $item['tax_rate'] ?? 12.00,
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $itemSubtotal,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // Update totals
                $shippingCost = $validated['shipping_cost'] ?? $po->shipping_cost;
                $discountAmount = $validated['discount_amount'] ?? $po->discount_amount;
                $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;

                $po->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order updated successfully',
                'data' => $po->fresh()->load('items.product'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve purchase order
     * POST /api/procurement/purchase-orders/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'role' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Check if user has this role
        $userRole = auth()->user()->role->name;

        if ($userRole !== $validated['role']) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve as this role',
            ], 403);
        }

        // Check if this role is actually required for approval
        $requiredRoles = $po->required_approvers ?? [];
        if (!in_array($validated['role'], $requiredRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'This role is not required for approval of this purchase order',
            ], 403);
        }

        // Check if this role has already approved
        $approversReceived = collect($po->approvals_received ?? [])->pluck('approver_role')->toArray();
        if (in_array($validated['role'], $approversReceived)) {
            return response()->json([
                'success' => false,
                'message' => 'This role has already approved this purchase order',
            ], 422);
        }

        // Add approval
        $po->addApproval(
            $validated['role'],
            auth()->id(),
            auth()->user()->full_name,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved successfully',
            'data' => $po->fresh(),
        ]);
    }

    /**
     * Reject purchase order
     * POST /api/procurement/purchase-orders/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $po->reject(
            auth()->user()->role->name,
            auth()->id(),
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase order rejected',
        ]);
    }

    /**
     * Send PO to supplier
     * POST /api/procurement/purchase-orders/{id}/send
     */
    public function send(int $id): JsonResponse
    {
        $po = PurchaseOrder::with('supplier')->findOrFail($id);

        if (!$po->isFullyApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order must be fully approved before sending',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $po->sendToSupplier();

            // TODO: Send email to supplier

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order sent to supplier successfully',
                'data' => $po->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to send purchase order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel purchase order
     * POST /api/procurement/purchase-orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (in_array($po->status, ['received', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel this purchase order',
            ], 422);
        }

        $po->update([
            'status' => 'cancelled',
            'notes' => ($po->notes ?? '') . "\n\nCancellation reason: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order cancelled successfully',
        ]);
    }

    /**
     * Get PO summary/statistics
     * GET /api/procurement/purchase-orders/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $query = PurchaseOrder::where('store_id', auth()->user()->store_id);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        $summary = [
            'total_pos' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('total_amount'),
            'pending_approval' => (clone $query)->pending()->count(),
            'approved' => (clone $query)->approved()->count(),
            'ordered' => (clone $query)->ordered()->count(),
            'received' => (clone $query)->received()->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'overdue' => (clone $query)->where('expected_delivery_date', '<', now())
                ->whereNotIn('status', ['received', 'cancelled'])
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Delete purchase order
     * DELETE /api/procurement/purchase-orders/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft purchase orders can be deleted',
            ], 422);
        }

        $po->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order deleted successfully',
        ]);
    }
}