<?php
// backend/app/Http/Controllers/Procurement/PurchaseOrder/PurchaseOrderController.php

namespace App\Http\Controllers\Api\Procurement\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\PurchaseOrder\PurchaseOrderItem;
use App\Models\Procurement\Config\ProcurementSettings;
use App\Models\Procurement\StockOrder\StockOrderRequest;
use App\Models\Core\ActivityLog;
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

        $activityLogs = ActivityLog::with('user')
            ->where('entity_type', 'purchase_order')
            ->where('entity_id', $po->id)
            ->orderBy('created_at')
            ->get();

        $po->setAttribute('activity_logs', $activityLogs);

        return response()->json([
            'success' => true,
            'data' => $po,
        ]);
    }

    /**
     * Create purchase order from stock order requests
     * PO can no longer be created from scratch - must come from stock requests
     * POST /api/procurement/purchase-orders
     * 
     * Request body:
     * {
     *   "stock_order_request_ids": [1, 2, 3],
     *   "supplier_id": 5,
     *   "payment_terms": "net_30",
     *   "discount_amount": 100,
     *   "notes": "...",
     *   "terms_conditions": "..."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $hasStockRequests = $request->filled('stock_order_request_ids');

        $validated = $request->validate($hasStockRequests ? [
            'stock_order_request_ids' => 'required|array|min:1',
            'stock_order_request_ids.*' => 'exists:stock_order_requests,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'status' => 'nullable|in:draft,pending_finance_approval',
        ] : [
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'status' => 'nullable|in:draft,pending_finance_approval',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $storeId = auth()->user()->store_id;

            // Generate PO number
            $timestamp = now()->format('YmdHis');
            $randomSuffix = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $poNumber = 'PO-' . $timestamp . '-' . $randomSuffix;

            $subtotal = 0;
            $taxAmount = 0;
            $items = [];

            if ($hasStockRequests) {
                // Fetch all stock order requests
                $stockOrderRequests = StockOrderRequest::where('store_id', $storeId)
                    ->whereIn('id', $validated['stock_order_request_ids'])
                    ->with('branchInventory.product')
                    ->get();

                if ($stockOrderRequests->count() !== count($validated['stock_order_request_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more stock order requests not found or belong to different store',
                    ], 404);
                }

                // Verify all requests are approved
                $unapproved = $stockOrderRequests->where('status', '!=', 'approved');
                if ($unapproved->isNotEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'All stock order requests must be approved before converting to PO',
                    ], 400);
                }

                // Get the branch from first stock request
                $branchId = $stockOrderRequests->first()->branchInventory->branch_id;

                foreach ($stockOrderRequests as $stockRequest) {
                    $product = $stockRequest->branchInventory->product;
                    
                    // Use product's unit cost as default
                    $unitCost = $product->unit_cost ?? 0;
                    $itemSubtotal = $unitCost * $stockRequest->requested_quantity;
                    
                    $subtotal += $itemSubtotal;

                    $items[] = [
                        'stock_order_request_id' => $stockRequest->id,
                        'product_id' => $product->id,
                        'variation_id' => $stockRequest->branchInventory->variation_id,
                        'quantity_ordered' => $stockRequest->requested_quantity,
                        'unit_cost' => $unitCost,
                        'discount_percent' => 0,
                    ];
                }

                $shippingCost = 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $totalAmount = $subtotal + $shippingCost - $discountAmount;

                // Get procurement settings for approval tiers
                $settings = ProcurementSettings::where('store_id', $storeId)->first();
                $approvalTier = $settings?->getApprovalTierForAmount($totalAmount);

                // Check if RFQ is required
                $rfqRequired = $settings?->shouldRequireRFQ($totalAmount) ?? false;

                // Create PO linking first stock order request
                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'store_id' => $storeId,
                    'branch_id' => $branchId,
                    'supplier_id' => $validated['supplier_id'],
                    'stock_order_request_id' => $stockOrderRequests->first()->id,
                    'status' => $validated['status'] ?? 'pending_finance_approval',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'approval_tier_level' => $approvalTier['level'] ?? null,
                    'required_approvers' => $approvalTier['approvers'] ?? [],
                    'rfq_required' => $rfqRequired,
                    'payment_status' => 'pending',
                    'payment_terms' => $validated['payment_terms'] ?? 'net_30',
                    'order_date' => now()->toDateString(),
                    'expected_delivery_date' => null,
                    'created_by' => auth()->id(),
                    'notes' => $validated['notes'] ?? null,
                    'terms_conditions' => $validated['terms_conditions'] ?? null,
                ]);

                // Calculate payment due date
                $paymentTerms = $validated['payment_terms'] ?? 'net_30';
                $paymentDueDays = match($paymentTerms) {
                    'net_7' => 7,
                    'net_15' => 15,
                    'net_30' => 30,
                    'net_60' => 60,
                    default => 0,
                };
                $po->payment_due_date = now()->addDays($paymentDueDays);
                $po->save();

                // Create PO items
                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'quantity_received' => 0,
                        'quantity_cancelled' => 0,
                        'unit_cost' => $item['unit_cost'],
                        'tax_rate' => $item['tax_rate'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $item['unit_cost'] * $item['quantity_ordered'],
                    ]);
                }

                // Mark stock order requests as converted
                foreach ($stockOrderRequests as $stockRequest) {
                    $stockRequest->markConverted();
                }

                ActivityLog::record(
                    'po_created',
                    "PO {$po->po_number} created.",
                    ['po_number' => $po->po_number],
                    'purchase_order',
                    $po->id
                );
            } else {
                // Manual PO creation from items
                foreach ($validated['items'] as $item) {
                    $itemSubtotal = $item['unit_cost'] * $item['quantity_ordered'];
                    if (isset($item['discount_percent'])) {
                        $itemSubtotal -= $itemSubtotal * ($item['discount_percent'] / 100);
                    }
                    $subtotal += $itemSubtotal;

                    $items[] = [
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity_ordered' => $item['quantity_ordered'],
                        'unit_cost' => $item['unit_cost'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $itemSubtotal,
                    ];
                }

                $shippingCost = 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $totalAmount = $subtotal + $shippingCost - $discountAmount;

                // Get procurement settings for approval tiers
                $settings = ProcurementSettings::where('store_id', $storeId)->first();
                $approvalTier = $settings?->getApprovalTierForAmount($totalAmount);
                $rfqRequired = $settings?->shouldRequireRFQ($totalAmount) ?? false;

                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'store_id' => $storeId,
                    'branch_id' => $validated['branch_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'status' => $validated['status'] ?? 'pending_finance_approval',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'approval_tier_level' => $approvalTier['level'] ?? null,
                    'required_approvers' => $approvalTier['approvers'] ?? [],
                    'rfq_required' => $rfqRequired,
                    'payment_status' => 'pending',
                    'payment_terms' => $validated['payment_terms'] ?? 'net_30',
                    'order_date' => $validated['order_date'],
                    'expected_delivery_date' => null,
                    'created_by' => auth()->id(),
                    'notes' => $validated['notes'] ?? null,
                    'terms_conditions' => $validated['terms_conditions'] ?? null,
                ]);

                $paymentTerms = $validated['payment_terms'] ?? 'net_30';
                $paymentDueDays = match($paymentTerms) {
                    'net_7' => 7,
                    'net_15' => 15,
                    'net_30' => 30,
                    'net_60' => 60,
                    default => 0,
                };
                $po->payment_due_date = now()->addDays($paymentDueDays);
                $po->save();

                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'quantity_received' => 0,
                        'quantity_cancelled' => 0,
                        'unit_cost' => $item['unit_cost'],
                        'tax_rate' => $item['tax_rate'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $item['line_total'],
                    ]);
                }

                ActivityLog::record(
                    'po_created',
                    "PO {$po->po_number} created.",
                    ['po_number' => $po->po_number],
                    'purchase_order',
                    $po->id
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully from stock order requests',
                'data' => $po->load(['supplier', 'items.product', 'createdBy']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PO Creation Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order',
                'error' => config('app.debug') ? $e->getMessage() : null,
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
            'payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'discount_amount' => 'sometimes|nullable|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
            'terms_conditions' => 'sometimes|nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
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
            'payment_terms' => $validated['payment_terms'] ?? $po->payment_terms,
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

                    // Create new item
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity_ordered' => $item['quantity_ordered'],
                        'unit_cost' => $item['unit_cost'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $itemSubtotal,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // Update totals
                $shippingCost = $po->shipping_cost ?? 0;
                $discountAmount = $validated['discount_amount'] ?? $po->discount_amount;
                $totalAmount = $subtotal + $shippingCost - $discountAmount;

                $po->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
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
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $approvalPermissions = [
            'finance.purchase-orders.approve',
            'procurement.purchase-orders.approve',
        ];

        if (!$this->userHasAnyPermission($approvalPermissions, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve this purchase order',
            ], 403);
        }

        $approvalPermission = null;
        foreach ($approvalPermissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                $approvalPermission = $permission;
                break;
            }
        }
        $approvalPermission = $approvalPermission ?? $approvalPermissions[0];

        $userRole = $user->role->name ?? $user->role ?? null;

        // Check if this role is actually required for approval
        $requiredRoles = $po->required_approvers ?? [];
        $requiresPermissions = collect($requiredRoles)->contains(fn ($value) => is_string($value) && str_contains($value, '.'));
        if ($requiresPermissions && !in_array($approvalPermission, $requiredRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This permission is not required for approval of this purchase order',
            ], 403);
        }

        // Check if this role has already approved
        $approversReceived = collect($po->approvals_received ?? []);
        $approverPermissions = $approversReceived->pluck('approver_permission')->filter()->toArray();
        if (in_array($approvalPermission, $approverPermissions, true)) {
            if ($approvalPermission === 'finance.purchase-orders.approve' && $po->status !== 'approved') {
                $po->update(['status' => 'approved']);
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase order already approved by finance',
                    'data' => $po->fresh(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'This permission has already approved this purchase order',
            ], 422);
        }

        // Add approval
        $po->addApproval(
            $approvalPermission,
            auth()->id(),
            $user->full_name,
            $validated['notes'] ?? null,
            $userRole
        );

        // Finance approval is final in this workflow
        if ($approvalPermission === 'finance.purchase-orders.approve') {
            $po->update(['status' => 'approved']);
        }

        ActivityLog::record(
            'po_approved',
            "PO {$po->po_number} approved by {$approvalPermission}.",
            [
                'po_number' => $po->po_number,
                'permission' => $approvalPermission,
                'role' => $userRole,
                'notes' => $validated['notes'] ?? null,
            ],
            'purchase_order',
            $po->id
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

        ActivityLog::record(
            'po_rejected',
            "PO {$po->po_number} rejected.",
            ['po_number' => $po->po_number, 'reason' => $validated['reason']],
            'purchase_order',
            $po->id
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

        if (!in_array($po->status, ['approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved purchase orders can be sent to suppliers.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $po->sendToSupplier();

            // TODO: Send email to supplier

            ActivityLog::record(
                'po_sent_to_supplier',
                "PO {$po->po_number} sent to supplier.",
                ['po_number' => $po->po_number, 'supplier' => $po->supplier?->supplier_name],
                'purchase_order',
                $po->id
            );

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

        if (in_array($po->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel this purchase order',
            ], 422);
        }

        $po->update([
            'status' => 'cancelled',
            'notes' => ($po->notes ?? '') . "\n\nCancellation reason: " . $validated['reason'],
        ]);

        ActivityLog::record(
            'po_cancelled',
            "PO {$po->po_number} cancelled.",
            ['po_number' => $po->po_number, 'reason' => $validated['reason']],
            'purchase_order',
            $po->id
        );

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
            'sent_to_supplier' => (clone $query)->where('status', 'sent_to_supplier')->count(),
            'supplier_accepted' => (clone $query)->where('status', 'supplier_accepted')->count(),
            'delivered' => (clone $query)->received()->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'overdue' => (clone $query)->where('expected_delivery_date', '<', now())
                ->whereNotIn('status', ['delivered', 'cancelled'])
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
