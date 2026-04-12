<?php
// backend/app/Http/Controllers/Procurement/PurchaseOrder/PurchaseOrderController.php

namespace App\Http\Controllers\Api\Procurement\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;
use App\Models\Procurement\PurchaseOrder\PurchaseOrderItem;
use App\Models\Procurement\Config\ProcurementSettings;
use App\Models\Procurement\StockOrder\StockOrderRequest;
use App\Models\Procurement\Shipping\PurchaseOrderShipment;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLog;
use App\Models\Core\ActivityLog;
use App\Models\ProductCatalog\Product;
use App\Models\Procurement\Supplier\SupplierContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    private function userHasAnyPermission(array $permissionNames, $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        $storeId = (int) ($user->store_id ?? 0);
        foreach ($permissionNames as $permission) {
            $normalized = (string) $permission;
            $aliases = array_values(array_unique([
                $normalized,
                Str::contains($normalized, '_') ? str_replace('_', '-', $normalized) : $normalized,
                Str::contains($normalized, '-') ? str_replace('-', '_', $normalized) : $normalized,
            ]));

            foreach ($aliases as $candidate) {
                if ($candidate && method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($candidate, $storeId)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * List all purchase orders
     * GET /api/procurement/ purchase_orders
     */
    public function index(Request $request)
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        }

        return Inertia::render('System/Procurement/PurchaseOrders/PurchaseOrderIndex', [
            'orders' => $orders,
            'filters' => $request->only([
                'branch_id',
                'supplier_id',
                'status',
                'payment_status',
                'start_date',
                'end_date',
                'per_page',
            ]),
        ]);
    }

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
     * POST /api/procurement/ purchase_orders
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
        $isSplitFromRequisition = !$hasStockRequests
            && $request->filled('purchase_requisition_id')
            && !$request->filled('supplier_id');

        $validated = $request->validate($hasStockRequests ? [
            'stock_order_request_ids' => 'required|array|min:1',
            'stock_order_request_ids.*' => 'exists:stock_order_requests,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'status' => 'nullable|in:draft,pending_finance_approval',
        ] : ($isSplitFromRequisition ? [
            'purchase_requisition_id' => 'required|exists:purchase_requisitions,id',
            'order_date' => 'nullable|date',
            'payment_terms' => 'nullable|in:cash_on_delivery,net_7,net_15,net_30,net_60,advance_payment',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'status' => 'nullable|in:draft,pending_finance_approval',
        ] : [
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
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
        ]));

        DB::beginTransaction();
        try {
            $storeId = auth()->user()->store_id;

            if ($isSplitFromRequisition) {
                $createdOrders = $this->createSplitPurchaseOrdersFromRequisition($validated, (int) $storeId);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Purchase orders created and grouped by supplier successfully',
                    'data' => $createdOrders,
                ], 201);
            }

            // Generate PO number
            $poNumber = $this->generatePoNumber();

            $subtotal = 0;
            $taxAmount = 0;
            $items = [];
            $contract = SupplierContract::where('store_id', $storeId)
                ->where('supplier_id', $validated['supplier_id'])
                ->active()
                ->orderBy('end_date', 'desc')
                ->first();
            $headerTaxRate = ($contract && !$contract->is_tax_exempt) ? ($contract->tax_rate ?? 0) : 0;

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
                        'tax_rate' => $headerTaxRate,
                    ];
                }

                $shippingCost = 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $taxAmount = $subtotal * ($headerTaxRate / 100);
                $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;

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
                    'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
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
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'order_date' => now()->toDateString(),
                    'expected_delivery_date' => null,
                    'created_by' => auth()->user()?->employee?->id,
                    'notes' => $validated['notes'] ?? null,
                    'terms_conditions' => $validated['terms_conditions'] ?? null,
                ]);

                // Calculate payment due date
                $paymentTerms = $validated['payment_terms'] ?? null;
                $po->payment_due_date = $this->calculatePaymentDueDate($paymentTerms);
                $po->save();

                // Create PO items
                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'quantity_received' => 0,
                        'quantity_rejected' => 0,
                        'unit_cost' => $item['unit_cost'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $item['unit_cost'] * $item['quantity_ordered'],
                        'tax_rate' => $item['tax_rate'] ?? 0,
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

                $this->setPurchaseRequisitionStatus($po->purchase_requisition_id, 'po_created');
            } else {
                // Manual PO creation from items
                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $unitCostValue = $item['unit_cost'] ?? $product?->cost_price ?? 0;
                    $itemSubtotal = $unitCostValue * $item['quantity_ordered'];
                    if (isset($item['discount_percent'])) {
                        $itemSubtotal -= $itemSubtotal * ($item['discount_percent'] / 100);
                    }
                    $subtotal += $itemSubtotal;

                    // Prefill tax from supplier default unless provided, fallback to 0
                    $lineTaxRate = $headerTaxRate;
                    $items[] = [
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity_ordered' => $item['quantity_ordered'],
                        'unit_cost' => $unitCostValue,
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $itemSubtotal,
                        'tax_rate' => $lineTaxRate,
                    ];
                }

                $shippingCost = 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $taxAmount = $subtotal * ($headerTaxRate / 100);
                $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;

                // Get procurement settings for approval tiers
                $settings = ProcurementSettings::where('store_id', $storeId)->first();
                $approvalTier = $settings?->getApprovalTierForAmount($totalAmount);
                $rfqRequired = $settings?->shouldRequireRFQ($totalAmount) ?? false;

                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'store_id' => $storeId,
                    'branch_id' => $validated['branch_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
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
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'order_date' => $validated['order_date'],
                    'expected_delivery_date' => null,
                    'created_by' => auth()->user()?->employee?->id,
                    'notes' => $validated['notes'] ?? null,
                    'terms_conditions' => $validated['terms_conditions'] ?? null,
                ]);

                $paymentTerms = $validated['payment_terms'] ?? null;
                $po->payment_due_date = $this->calculatePaymentDueDate($paymentTerms);
                $po->save();

                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'quantity_received' => 0,
                        'quantity_rejected' => 0,
                        'unit_cost' => $item['unit_cost'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'line_total' => $item['line_total'],
                        'tax_rate' => $item['tax_rate'] ?? 0,
                    ]);
                }

                ActivityLog::record(
                    'po_created',
                    "PO {$po->po_number} created.",
                    ['po_number' => $po->po_number],
                    'purchase_order',
                    $po->id
                );

                $this->setPurchaseRequisitionStatus($po->purchase_requisition_id, 'po_created');
            }

            $user = auth()->user();
            if ($this->userHasAnyPermission(['finance.purchase-orders.approve'], $user) && $po->status === 'pending_finance_approval') {
                $settings = ProcurementSettings::forStore((int) $user->store_id);
                $isSelfApproval = $this->isSelfApproval($po, $user);

                if (!$isSelfApproval || !$settings->enforce_separation_of_duties || $settings->isSelfApprovalAllowedForAmount((float) $po->total_amount)) {
                    $approvalPermission = 'finance.purchase_orders.approve';

                    $approversReceived = collect($po->approvals_received ?? []);
                    $approverPermissions = $approversReceived
                        ->pluck('approver_permission')
                        ->filter()
                        ->map(fn($permission) => $this->normalizePermission((string) $permission))
                        ->toArray();

                    if (!in_array($this->normalizePermission($approvalPermission), $approverPermissions, true)) {
                        $po->addApproval(
                            $approvalPermission,
                            auth()->id(),
                            $user->full_name,
                            'Auto-approved on creation (finance.purchase-orders.approve)',
                            null
                        );

                        $po->update(['status' => 'approved']);
                        $this->enforceMinimumApprovers($po, $settings);
                        $po = $po->fresh();

                        $autoSent = false;
                        if ($this->shouldAutoSendAfterFinanceApproval($po)) {
                            $autoSent = $this->dispatchApprovedPoToSupplier($po, true);
                            $po = $po->fresh();
                        }

                        ActivityLog::record(
                            'po_auto_approved',
                            "PO {$po->po_number} auto-approved by finance on creation.",
                            [
                                'po_number' => $po->po_number,
                                'permission' => $approvalPermission,
                                'auto_sent' => $autoSent,
                            ],
                            'purchase_order',
                            $po->id
                        );
                    }
                }
            }

            DB::commit();

            $this->notifyRequisitionRequesterPoCreated($po);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $po->load(['supplier', 'items.product', 'createdBy']),
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
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
     * PUT /api/procurement/ purchase_orders/{id}
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
            $storeId = $po->store_id ?? auth()->user()->store_id;
            $supplierId = $validated['supplier_id'] ?? $po->supplier_id;
            $contract = SupplierContract::where('store_id', $storeId)
                ->where('supplier_id', $supplierId)
                ->active()
                ->orderBy('end_date', 'desc')
                ->first();
            $headerTaxRate = ($contract && !$contract->is_tax_exempt) ? ($contract->tax_rate ?? 0) : 0;

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
                        'tax_rate' => $headerTaxRate,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // Update totals
                $shippingCost = $po->shipping_cost ?? 0;
                $discountAmount = $validated['discount_amount'] ?? $po->discount_amount;
                $taxAmount = $subtotal * ($headerTaxRate / 100);
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
     * POST /api/procurement/ purchase_orders/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $approvalPermission = $request->is('api/finance/*')
            ? 'finance.purchase_orders.approve'
            : 'procurement.purchase_orders.approve';
        $settings = ProcurementSettings::forStore((int) $user->store_id);
        $isSelfApproval = $this->isSelfApproval($po, $user);

        if ($isSelfApproval && $settings->enforce_separation_of_duties) {
            $canBypass = $settings->isSelfApprovalAllowedForAmount((float) $po->total_amount);
            if (!$canBypass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Self-approval is restricted by workflow settings for this amount.',
                ], 403);
            }
        }

        // Check if this permission has already approved
        $approversReceived = collect($po->approvals_received ?? []);
        $approverPermissions = $approversReceived
            ->pluck('approver_permission')
            ->filter()
            ->toArray();
        if (in_array($approvalPermission, $approverPermissions, true)) {
            if ($approvalPermission === 'finance.purchase_orders.approve' && $po->status !== 'approved') {
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
            null
        );

        // Permission-based workflow: approval action finalizes PO approval.
        $po->update(['status' => 'approved']);

        $this->enforceMinimumApprovers($po, $settings);
        $po = $po->fresh();

        $autoSent = false;
        if ($this->shouldAutoSendAfterFinanceApproval($po)) {
            $autoSent = $this->dispatchApprovedPoToSupplier($po, true);
            $po = $po->fresh();
        }

        ActivityLog::record(
            'po_approved',
            "PO {$po->po_number} approved by {$approvalPermission}.",
            [
                'po_number' => $po->po_number,
                'permission' => $approvalPermission,
                'notes' => $validated['notes'] ?? null,
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
                'action' => 'approved',
                'title' => 'Purchase Order Approved',
                'message' => "PO {$po->po_number} approved.",
                'severity' => 'success',
                'link' => "/system/procurement/purchase-orders/{$po->id}",
            ]);
        }

        if ($this->normalizePermission($approvalPermission) === 'finance.purchase_orders.approve') {
            $this->notifyUsersByPermissions(
                (int) $po->store_id,
                [
                    'procurement.purchase_orders.manage',
                    'procurement.requisitions.manage',
                    'procurement.rfq.manage',
                ],
                [
                    'store_id' => $po->store_id,
                    'branch_id' => $po->branch_id,
                    'module' => 'procurement',
                    'entity_type' => 'purchase_order',
                    'entity_id' => $po->id,
                    'action' => 'finance_approved',
                    'title' => 'PO Approved By Finance',
                    'message' => "PO {$po->po_number} was approved by finance" . ($autoSent ? ' and sent to supplier.' : '.'),
                    'severity' => 'success',
                    'link' => "/system/procurement/purchase-orders/{$po->id}",
                ],
                [auth()->id()]
            );
        }

        return response()->json([
            'success' => true,
            'message' => $autoSent
                ? 'Purchase order approved and automatically sent to supplier'
                : 'Purchase order approved successfully',
            'data' => $po->fresh(),
        ]);
    }

    /**
     * Reject purchase order
     * POST /api/procurement/ purchase_orders/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $po->reject('procurement.purchase_orders.approve', auth()->id(), $validated['reason']);

        ActivityLog::record(
            'po_rejected',
            "PO {$po->po_number} rejected.",
            ['po_number' => $po->po_number, 'reason' => $validated['reason']],
            'purchase_order',
            $po->id
        );

        $this->setPurchaseRequisitionStatus($po->purchase_requisition_id, 'rejected');

        $creatorUserId = $po->createdBy?->user_id;
        if ($creatorUserId) {
            $this->notify($creatorUserId, [
                'store_id' => $po->store_id,
                'branch_id' => $po->branch_id,
                'module' => 'procurement',
                'entity_type' => 'purchase_order',
                'entity_id' => $po->id,
                'action' => 'rejected',
                'title' => 'Purchase Order Rejected',
                'message' => "PO {$po->po_number} rejected. Reason: {$validated['reason']}",
                'severity' => 'danger',
                'link' => "/system/procurement/purchase-orders/{$po->id}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase order rejected',
        ]);
    }

    /**
     * Send PO to supplier
     * POST /api/procurement/ purchase_orders/{id}/send
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
            $this->dispatchApprovedPoToSupplier($po, false);

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
     * POST /api/procurement/ purchase_orders/{id}/cancel
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
     * GET /api/procurement/ purchase_orders/summary
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
     * DELETE /api/procurement/ purchase_orders/{id}
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

    private function createSplitPurchaseOrdersFromRequisition(array $validated, int $storeId): array
    {
        $requisition = PurchaseRequisition::with([
            'items.product',
        ])
            ->where('store_id', $storeId)
            ->findOrFail((int) $validated['purchase_requisition_id']);

        if ($requisition->items->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_requisition_id' => 'Cannot create purchase orders from an empty requisition.',
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
                'items' => 'Some requisition items have no supplier assignment and cannot be grouped into POs. Item IDs: ' . implode(', ', $unassignedItemIds),
            ]);
        }

        if (empty($itemsBySupplier)) {
            throw ValidationException::withMessages([
                'items' => 'No supplier-resolved requisition items were found for PO creation.',
            ]);
        }

        $createdOrders = [];

        foreach ($itemsBySupplier as $supplierId => $requisitionItems) {
            $contract = SupplierContract::where('store_id', $storeId)
                ->where('supplier_id', $supplierId)
                ->active()
                ->orderBy('end_date', 'desc')
                ->first();

            $headerTaxRate = ($contract && !$contract->is_tax_exempt) ? ($contract->tax_rate ?? 0) : 0;

            $subtotal = 0;
            $poItemsPayload = [];

            foreach ($requisitionItems as $requisitionItem) {
                $unitCost = (float) ($requisitionItem->estimated_unit_cost ?? $requisitionItem->product?->cost_price ?? 0);
                $quantity = (int) $requisitionItem->quantity_requested;
                $lineTotal = $unitCost * $quantity;

                $subtotal += $lineTotal;

                $poItemsPayload[] = [
                    'purchase_requisition_item_id' => $requisitionItem->id,
                    'product_id' => $requisitionItem->product_id,
                    'variation_id' => $requisitionItem->variation_id,
                    'quantity_ordered' => $quantity,
                    'quantity_received' => 0,
                    'quantity_rejected' => 0,
                    'allocated_quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'discount_percent' => 0,
                    'line_total' => $lineTotal,
                    'tax_rate' => $headerTaxRate,
                ];
            }

            $shippingCost = 0;
            $discountAmount = 0;
            $taxAmount = $subtotal * ($headerTaxRate / 100);
            $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;

            $settings = ProcurementSettings::where('store_id', $storeId)->first();
            $approvalTier = $settings?->getApprovalTierForAmount($totalAmount);
            $rfqRequired = $settings?->shouldRequireRFQ($totalAmount) ?? false;

            $po = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'store_id' => $storeId,
                'branch_id' => $requisition->branch_id,
                'supplier_id' => $supplierId,
                'purchase_requisition_id' => $requisition->id,
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
                'payment_terms' => $validated['payment_terms'] ?? null,
                'order_date' => $validated['order_date'] ?? now()->toDateString(),
                'expected_delivery_date' => null,
                'created_by' => auth()->user()?->employee?->id,
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
            ]);

            $po->payment_due_date = $this->calculatePaymentDueDate($validated['payment_terms'] ?? null);
            $po->save();

            foreach ($poItemsPayload as $poItemPayload) {
                PurchaseOrderItem::create(array_merge(
                    ['purchase_order_id' => $po->id],
                    $poItemPayload
                ));
            }

            ActivityLog::record(
                'po_created',
                "PO {$po->po_number} created from requisition split by supplier.",
                [
                    'po_number' => $po->po_number,
                    'purchase_requisition_id' => $requisition->id,
                    'supplier_id' => $supplierId,
                ],
                'purchase_order',
                $po->id
            );

            $this->notifyRequisitionRequesterPoCreated($po);

            $createdOrders[] = $po->load(['supplier', 'items.product', 'createdBy']);
        }

        $this->setPurchaseRequisitionStatus($requisition->id, 'po_created');

        return [
            'purchase_requisition_id' => $requisition->id,
            'created_count' => count($createdOrders),
            'purchase_orders' => $createdOrders,
        ];
    }

    private function generatePoNumber(): string
    {
        $timestamp = now()->format('YmdHis');
        $randomSuffix = str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return 'PO-' . $timestamp . '-' . $randomSuffix;
    }

    private function calculatePaymentDueDate(?string $paymentTerms)
    {
        $paymentDueDays = match ($paymentTerms) {
            'net_7' => 7,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            default => 0,
        };

        return now()->addDays($paymentDueDays);
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

    private function setPurchaseRequisitionStatus(?int $requisitionId, string $status): void
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

    private function isSelfApproval(PurchaseOrder $po, $user): bool
    {
        $employeeId = (int) ($user?->employee?->id ?? 0);
        return $employeeId > 0 && $employeeId === (int) ($po->created_by ?? 0);
    }

    private function tryAutoFinanceApprovalForDualRole(
        PurchaseOrder $po,
        $user,
        ProcurementSettings $settings,
        bool $isSelfApproval
    ): void {
        if (!$isSelfApproval) {
            return;
        }

        if (!$settings->isSelfApprovalAllowedForAmount((float) $po->total_amount)) {
            return;
        }

        if (!$this->userHasAnyPermission(['finance.purchase-orders.approve', 'finance.purchase_orders.approve'], $user)) {
            return;
        }

        $approvalsReceived = collect($po->approvals_received ?? []);
        $alreadyFinanceApproved = $approvalsReceived
            ->pluck('approver_permission')
            ->contains('finance.purchase_orders.approve');

        if (!$alreadyFinanceApproved) {
            $po->addApproval(
                'finance.purchase_orders.approve',
                (int) $user->id,
                (string) $user->full_name,
                'Auto-approved by dual-role workflow policy.',
                null
            );
        }

        $po->update(['status' => 'approved']);
    }

    private function enforceMinimumApprovers(PurchaseOrder $po, ProcurementSettings $settings): void
    {
        $minimumRequired = max(1, (int) ($settings->min_approvers_required ?? 1));
        if ($minimumRequired <= 1) {
            return;
        }

        $distinctApprovers = collect($po->approvals_received ?? [])
            ->pluck('approver_id')
            ->filter()
            ->unique()
            ->count();

        if ($distinctApprovers < $minimumRequired) {
            $po->update(['status' => 'pending_finance_approval']);
        }
    }

    private function normalizePermission(?string $permission): string
    {
        $value = strtolower(trim((string) $permission));
        $value = str_replace(' ', '', $value);
        return str_replace('-', '_', $value);
    }

    private function hasFinanceApproval(PurchaseOrder $po): bool
    {
        $approvalsReceived = collect($po->approvals_received ?? []);

        return $approvalsReceived
            ->pluck('approver_permission')
            ->filter()
            ->map(fn($permission) => $this->normalizePermission((string) $permission))
            ->contains('finance.purchase_orders.approve');
    }

    private function shouldAutoSendAfterFinanceApproval(PurchaseOrder $po): bool
    {
        if ($po->status !== 'approved') {
            return false;
        }

        if ($this->normalizePermission((string) $po->status) === 'sent_to_supplier') {
            return false;
        }

        return $this->hasFinanceApproval($po);
    }

    private function dispatchApprovedPoToSupplier(PurchaseOrder $po, bool $isAuto): bool
    {
        if ($po->status !== 'approved') {
            return false;
        }

        $po->loadMissing(['supplier', 'createdBy']);
        $po->sendToSupplier();

        ActivityLog::record(
            'po_sent_to_supplier',
            $isAuto
                ? "PO {$po->po_number} automatically sent to supplier after finance approval."
                : "PO {$po->po_number} sent to supplier.",
            [
                'po_number' => $po->po_number,
                'supplier' => $po->supplier?->supplier_name,
                'auto_sent' => $isAuto,
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
                'action' => 'sent_to_supplier',
                'title' => $isAuto ? 'Purchase Order Auto-Sent' : 'Purchase Order Sent',
                'message' => $isAuto
                    ? "PO {$po->po_number} was automatically sent to supplier after finance approval."
                    : "PO {$po->po_number} sent to supplier.",
                'severity' => 'info',
                'link' => "/system/procurement/purchase-orders/{$po->id}",
            ]);
        }

        return true;
    }

    private function notifyRequisitionRequesterPoCreated(PurchaseOrder $po): void
    {
        if (!$po->purchase_requisition_id) {
            return;
        }

        $pr = PurchaseRequisition::with('requestedBy')->find($po->purchase_requisition_id);
        $requesterUserId = $pr?->requestedBy?->user_id;

        if (!$pr || !$requesterUserId) {
            return;
        }

        $this->notify((int) $requesterUserId, [
            'store_id' => $po->store_id,
            'branch_id' => $po->branch_id,
            'module' => 'procurement',
            'entity_type' => 'purchase_order',
            'entity_id' => $po->id,
            'action' => 'created',
            'title' => 'PO Created From Your PR',
            'message' => "{$po->po_number} was created from PR {$pr->pr_number}.",
            'severity' => 'info',
            'link' => "/system/procurement/purchase-orders/{$po->id}",
        ]);
    }

    /**
     * Delivery logs and shipment status for a PO
     */
    public function deliveryLogs($id): JsonResponse
    {
        $shipment = PurchaseOrderShipment::where('purchase_order_id', $id)->first();
        $logs = [];
        if ($shipment) {
            $logs = PurchaseOrderDeliveryLog::with(['creator', 'attachments'])
                ->where('shipment_id', $shipment->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shipment_status' => $shipment->status ?? null,
                'shipment' => $shipment,
                'logs' => $logs,
            ],
        ]);
    }
}
