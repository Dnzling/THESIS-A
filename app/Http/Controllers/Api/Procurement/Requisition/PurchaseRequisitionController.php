<?php
// backend/app/Http/Controllers/Procurement/Requisition/PurchaseRequisitionController.php

namespace App\Http\Controllers\Api\Procurement\Requisition;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;
use App\Models\Procurement\Config\ProcurementSettings;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Shipping\PurchaseOrderShipment;
use App\Models\Procurement\Shipping\PurchaseOrderDeliveryLog;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequisitionController extends Controller
{
    /**
     * List all purchase requisitions
     * GET /api/procurement/requisitions
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseRequisition::with(['branch', 'requestedBy.user', 'items.product.suppliers', 'items.variation'])
            ->where('store_id', Auth::user()->store_id);

        // Filters
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('requisition_type')) {
            $query->where('requisition_type', $request->requisition_type);
        }

        if ($request->has('procurement_route')) {
            $query->where('procurement_route', $request->procurement_route);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('branch', function ($branchQuery) use ($search) {
                        $branchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('branch_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('requestedBy', function ($requestedByQuery) use ($search) {
                        $requestedByQuery->where('employee_number', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('fname', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'pr_number', 'status', 'requisition_type'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $requisitions = $query->orderBy($sortBy, $sortOrder)
            ->paginate($request->get('per_page', 15));

        $requisitions->getCollection()->transform(function ($pr) {
            $allHaveSuppliers = $pr->items->every(function ($item) {
                return $item->product && $item->product->suppliers->count() > 0;
            });
            $pr->setAttribute('all_items_have_suppliers', $allHaveSuppliers);
            $pr->setAttribute('any_item_missing_supplier', !$allHaveSuppliers);
            $requester = $pr->requestedBy;
            $requesterName = trim(($requester?->user?->fname ?? $requester?->fname ?? '') . ' ' . ($requester?->user?->lname ?? $requester?->lname ?? ''));
            $pr->setAttribute('created_by_name', $requesterName !== '' ? $requesterName : null);
            return $pr;
        });

        return response()->json([
            'success' => true,
            'data' => $requisitions,
        ]);
    }

    /**
     * Show single requisition
     * GET /api/procurement/requisitions/{id}
     */
    public function show(int $id): JsonResponse
    {
        $requisition = PurchaseRequisition::with([
            'branch',
            'requestedBy.user',
            'items.product',
            'items.product.suppliers',
            'items.variation',
            'purchaseOrders.supplier',
            'purchaseOrders.goodsReceipts.items',
            'rfqs.awardedToSupplier',
        ])->findOrFail($id);

        $suppliers = collect();
        foreach ($requisition->rfqs as $rfq) {
            if ($rfq->awardedToSupplier) {
                $suppliers->push($rfq->awardedToSupplier);
            }
        }
        foreach ($requisition->purchaseOrders as $po) {
            if ($po->supplier) {
                $suppliers->push($po->supplier);
            }
        }
        $requisition->setAttribute('suppliers', $suppliers->unique('id')->values());

        return response()->json([
            'success' => true,
            'data' => $requisition,
        ]);
    }

    /**
     * Split preview for a requisition
     * GET /api/procurement/requisitions/{id}/split-preview
     */
    public function splitPreview(int $id): JsonResponse
    {
        $requisition = PurchaseRequisition::with(['items.product', 'items.product.suppliers', 'items.variation'])->findOrFail($id);

        $groups = [];
        $rfqItems = [];

        foreach ($requisition->items as $item) {
            $supId = $item->selected_supplier_id ?? ($item->product->suppliers[0]->id ?? null);
            if ($supId) {
                if (!isset($groups[$supId])) $groups[$supId] = [
                    'supplier_id' => $supId,
                    'supplier' => $item->product->suppliers->firstWhere('id', $supId) ?? null,
                    'items' => [],
                ];
                $groups[$supId]['items'][] = $item;
            } else {
                $rfqItems[] = $item;
            }
        }

        $grouped = array_values($groups);

        return response()->json([
            'success' => true,
            'data' => [
                'supplier_groups' => $grouped,
                'rfq_items' => $rfqItems,
                'requisition' => $requisition,
            ],
        ]);
    }

    /**
     * Create new purchase requisition
     * POST /api/procurement/requisitions
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'requisition_type' => 'required|in:regular,urgent,new_product,seasonal,emergency',
            'reason' => 'required|string',
            'priority' => 'nullable|integer|min:1|max:5',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.selected_supplier_id' => 'nullable|exists:suppliers,id',
            'items.*.quantity_requested' => 'required|integer|min:1',
            'items.*.estimated_unit_cost' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.specifications' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate PR number using datetime for uniqueness
            $prNumber = 'PR-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

            // Resolve item estimated unit costs with fallback:
            // Explicit estimate -> selected variation cost -> linked supplier price -> parent cost -> base price.
            $resolvedItems = [];
            foreach ($validated['items'] as $index => $item) {
                if (!$this->isValidVariationForProduct($item['variation_id'] ?? null, (int) $item['product_id'], (int) Auth::user()->store_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Selected variation does not belong to the chosen product for items.{$index}.",
                        'errors' => [
                            "items.{$index}.variation_id" => [
                                'Selected variation does not belong to the chosen product.',
                            ],
                        ],
                    ], 422);
                }

                $selectedSupplierId = isset($item['selected_supplier_id']) ? (int) $item['selected_supplier_id'] : null;
                if ($selectedSupplierId && !$this->supplierCanProvideProduct($selectedSupplierId, (int) $item['product_id'], Auth::user()->store_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Selected supplier {$selectedSupplierId} is not mapped to product {$item['product_id']} for this store.",
                    ], 422);
                }

                $item['estimated_unit_cost'] = $this->resolveEstimatedUnitCost(
                    (int) $item['product_id'],
                    $item['estimated_unit_cost'] ?? null,
                    isset($item['variation_id']) ? (int) $item['variation_id'] : null
                );
                $resolvedItems[] = $item;
            }

            // Hard validation: disallow mixed selected supplier assignment.
            $hasAnySelectedSupplier = collect($resolvedItems)->contains(function ($item) {
                return !is_null($item['selected_supplier_id'] ?? null);
            });
            $hasAnyWithoutSelectedSupplier = collect($resolvedItems)->contains(function ($item) {
                return is_null($item['selected_supplier_id'] ?? null);
            });
            if ($hasAnySelectedSupplier && $hasAnyWithoutSelectedSupplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create request with mixed supplier selection. Separate items with selected supplier (PO) and without selected supplier (RFQ).',
                    'errors' => [
                        'items' => [
                            'All line items must either all have selected supplier or all have no selected supplier.',
                        ],
                    ],
                ], 422);
            }

            // Also disallow mixed supplier availability by product mapping.
            $itemHasSupplierFlags = collect($resolvedItems)->map(function ($item) {
                return $this->productHasMappedSupplier((int) ($item['product_id'] ?? 0), (int) Auth::user()->store_id);
            });
            if ($itemHasSupplierFlags->contains(true) && $itemHasSupplierFlags->contains(false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mixed items are not allowed in one request. Create separate requisitions: items with supplier (PO) and items without supplier (RFQ).',
                    'errors' => [
                        'items' => [
                            'All line items must either all have supplier mapping or all have no supplier mapping.',
                        ],
                    ],
                ], 422);
            }

            // Calculate estimated amount
            $estimatedAmount = 0;
            foreach ($resolvedItems as $item) {
                $estimatedAmount += ($item['quantity_requested'] * ($item['estimated_unit_cost'] ?? 0));
            }

            // Get procurement settings
            $settings = ProcurementSettings::where('store_id', Auth::user()->store_id)->first();

            // Determine procurement route
            $procurementRoute = 'branch_direct';
            if ($settings) {
                if ($estimatedAmount >= $settings->procurement_threshold) {
                    $procurementRoute = 'centralized';
                }

                if ($settings->shouldRequireRFQ($estimatedAmount)) {
                    $procurementRoute = 'rfq_required';
                }
            }

            // Permission-based approval requirement (no role dependency).
            $requiredApprovals = ['procurement.requisitions.approve'];

            // Create PR
            $requestedBy = auth()->user()?->employee?->id;

            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'store_id' => Auth::user()->store_id,
                'branch_id' => $validated['branch_id'],
                'requisition_type' => $validated['requisition_type'],
                'status' => 'draft',
                'estimated_amount' => $estimatedAmount,
                'procurement_route' => $procurementRoute,
                'required_approvals' => $requiredApprovals,
                'reason' => $validated['reason'],
                'priority' => $validated['priority'] ?? 3,
                'requested_by' => $requestedBy,
            ]);

            // Create items
            foreach ($resolvedItems as $item) {
                PurchaseRequisitionItem::create([
                    'requisition_id' => $pr->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'selected_supplier_id' => $item['selected_supplier_id'] ?? null,
                    'quantity_requested' => $item['quantity_requested'],
                    'estimated_unit_cost' => $item['estimated_unit_cost'] ?? null,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'specifications' => $item['specifications'] ?? null,
                ]);
            }

            DB::commit();

            // Load relations for response
            $pr = $pr->load(['items.product', 'items.product.suppliers', 'items.variation']);

            // Compute supplier flags
            $allHaveSuppliers = collect($pr->items)->every(function ($item) {
                return isset($item->product) && isset($item->product->suppliers) && count($item->product->suppliers) > 0;
            });
            $pr->setAttribute('all_items_have_suppliers', $allHaveSuppliers);
            $pr->setAttribute('any_item_missing_supplier', !$allHaveSuppliers);

            return response()->json([
                'success' => true,
                'message' => 'Purchase requisition created successfully',
                'data' => $pr,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase requisition',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveEstimatedUnitCost(int $productId, $requestedCost, ?int $variationId = null): float
    {
        $requested = is_null($requestedCost) ? null : (float) $requestedCost;
        if (!is_null($requested) && $requested > 0) {
            return round($requested, 2);
        }

        if ($variationId) {
            $variationCost = ProductVariation::query()
                ->where('product_id', $productId)
                ->whereKey($variationId)
                ->value('cost_price');

            if (!is_null($variationCost) && (float) $variationCost > 0) {
                return round((float) $variationCost, 2);
            }
        }

        $bestLinkedSupplierPrice = DB::table('supplier_products')
            ->where('product_id', $productId)
            ->whereNotNull('supplier_price')
            ->orderByDesc('is_preferred_supplier')
            ->orderBy('supplier_price')
            ->value('supplier_price');

        if (!is_null($bestLinkedSupplierPrice) && (float) $bestLinkedSupplierPrice > 0) {
            return round((float) $bestLinkedSupplierPrice, 2);
        }

        $product = Product::query()
            ->select(['id', 'cost_price', 'base_price'])
            ->find($productId);

        $costPrice = (float) ($product?->cost_price ?? 0);
        if ($costPrice > 0) {
            return round($costPrice, 2);
        }

        $basePrice = (float) ($product?->base_price ?? 0);
        return round(max(0, $basePrice), 2);
    }

    /**
     * Update purchase requisition
     * PUT /api/procurement/requisitions/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        if ($pr->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requisitions can be updated',
            ], 422);
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'requisition_type' => 'nullable|in:regular,urgent,new_product,seasonal,emergency',
            'reason' => 'nullable|string',
            'priority' => 'nullable|integer|min:1|max:5',
            'items' => 'nullable|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.selected_supplier_id' => 'nullable|exists:suppliers,id',
            'items.*.quantity_requested' => 'required_with:items|integer|min:1',
            'items.*.estimated_unit_cost' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.specifications' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $updates = [];
            if (isset($validated['branch_id'])) {
                $updates['branch_id'] = $validated['branch_id'];
            }
            if (isset($validated['requisition_type'])) {
                $updates['requisition_type'] = $validated['requisition_type'];
            }
            if (isset($validated['reason'])) {
                $updates['reason'] = $validated['reason'];
            }
            if (isset($validated['priority'])) {
                $updates['priority'] = $validated['priority'];
            }

            if (!empty($validated['items'])) {
                $resolvedItems = [];
                foreach ($validated['items'] as $index => $item) {
                    if (!$this->isValidVariationForProduct($item['variation_id'] ?? null, (int) $item['product_id'], (int) Auth::user()->store_id)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Selected variation does not belong to the chosen product for items.{$index}.",
                            'errors' => [
                                "items.{$index}.variation_id" => [
                                    'Selected variation does not belong to the chosen product.',
                                ],
                            ],
                        ], 422);
                    }

                    $selectedSupplierId = isset($item['selected_supplier_id']) ? (int) $item['selected_supplier_id'] : null;
                    if ($selectedSupplierId && !$this->supplierCanProvideProduct($selectedSupplierId, (int) $item['product_id'], Auth::user()->store_id)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Selected supplier {$selectedSupplierId} is not mapped to product {$item['product_id']} for this store.",
                        ], 422);
                    }

                    $item['estimated_unit_cost'] = $this->resolveEstimatedUnitCost(
                        (int) $item['product_id'],
                        $item['estimated_unit_cost'] ?? null,
                        isset($item['variation_id']) ? (int) $item['variation_id'] : null
                    );
                    $resolvedItems[] = $item;
                }

                // Hard validation on update path as well.
                $hasAnySelectedSupplier = collect($resolvedItems)->contains(function ($item) {
                    return !is_null($item['selected_supplier_id'] ?? null);
                });
                $hasAnyWithoutSelectedSupplier = collect($resolvedItems)->contains(function ($item) {
                    return is_null($item['selected_supplier_id'] ?? null);
                });
                if ($hasAnySelectedSupplier && $hasAnyWithoutSelectedSupplier) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot update request with mixed supplier selection. Separate items with selected supplier (PO) and without selected supplier (RFQ).',
                        'errors' => [
                            'items' => [
                                'All line items must either all have selected supplier or all have no selected supplier.',
                            ],
                        ],
                    ], 422);
                }

                $itemHasSupplierFlags = collect($resolvedItems)->map(function ($item) {
                    return $this->productHasMappedSupplier((int) ($item['product_id'] ?? 0), (int) Auth::user()->store_id);
                });
                if ($itemHasSupplierFlags->contains(true) && $itemHasSupplierFlags->contains(false)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mixed items are not allowed in one request. Create separate requisitions: items with supplier (PO) and items without supplier (RFQ).',
                        'errors' => [
                            'items' => [
                                'All line items must either all have supplier mapping or all have no supplier mapping.',
                            ],
                        ],
                    ], 422);
                }

                $estimatedAmount = 0;
                foreach ($resolvedItems as $item) {
                    $estimatedAmount += ($item['quantity_requested'] * ($item['estimated_unit_cost'] ?? 0));
                }

                $settings = ProcurementSettings::where('store_id', Auth::user()->store_id)->first();

                $procurementRoute = $pr->procurement_route;
                if ($settings) {
                    if ($estimatedAmount >= $settings->procurement_threshold) {
                        $procurementRoute = 'centralized';
                    }

                    if ($settings->shouldRequireRFQ($estimatedAmount)) {
                        $procurementRoute = 'rfq_required';
                    }
                }

                $requiredApprovals = ['procurement.requisitions.approve'];

                $updates['items'] = $resolvedItems;
                $updates['estimated_amount'] = $estimatedAmount;
                $updates['procurement_route'] = $procurementRoute;
                $updates['required_approvals'] = $requiredApprovals;
            }

            $pr->update($updates);

            if (!empty($updates['items'])) {
                PurchaseRequisitionItem::where('requisition_id', $pr->id)->delete();
                foreach ($updates['items'] as $item) {
                    PurchaseRequisitionItem::create([
                        'requisition_id' => $pr->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'selected_supplier_id' => $item['selected_supplier_id'] ?? null,
                        'quantity_requested' => $item['quantity_requested'],
                        'estimated_unit_cost' => $item['estimated_unit_cost'] ?? null,
                        'tax_rate' => $item['tax_rate'] ?? 0,
                        'specifications' => $item['specifications'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase requisition updated successfully',
                'data' => $pr->load(['items.product', 'items.variation']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase requisition',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit PR for approval
     * POST /api/procurement/requisitions/{id}/submit
     */
    public function submit(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::with('items')->findOrFail($id);

        if ($pr->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requisitions can be submitted',
            ], 422);
        }

        if ($pr->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot submit requisition without items',
            ], 422);
        }

        $pr->submit();

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition submitted successfully',
            'data' => $pr->fresh(),
        ]);
    }

    /**
     * Approve PR
     * POST /api/procurement/requisitions/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);
            // Debug logging: record incoming approve requests
            try {
                \Log::info('[Approve] incoming', [
                    'id' => $id,
                    'user_id' => Auth::id(),
                    'payload' => $request->all(),
                ]);
            } catch (\Throwable $e) {
                // ignore logging errors
            }

            $validated = $request->validate([
                'notes' => 'nullable|string',
            ]);
            try {
                // Permission-based approval record (no role dependency).
                $pr->addApproval(
                    'procurement.requisitions.approve',
                    Auth::id(),
                    Auth::user()->full_name,
                    $validated['notes'] ?? null
                );

                DB::beginTransaction();
                try {
                    $pr = PurchaseRequisition::query()
                        ->lockForUpdate()
                        ->findOrFail($id);

                    $pr->update(['status' => 'procurement_processing']);

                    $pr->loadMissing(['requestedBy']);
                    $requesterUserId = (int) ($pr->requestedBy?->user_id ?? 0);
                    $payload = [
                        'store_id' => (int) $pr->store_id,
                        'branch_id' => (int) $pr->branch_id,
                        'module' => 'inventory',
                        'entity_type' => 'purchase_requisition',
                        'entity_id' => (int) $pr->id,
                        'action' => 'approved',
                        'title' => 'Purchase Requisition Approved',
                        'message' => "PR {$pr->pr_number} has been approved by procurement.",
                        'severity' => 'success',
                        'link' => "/inventory/requisites/{$pr->id}",
                        'data' => [
                            'pr_id' => (int) $pr->id,
                            'pr_number' => (string) $pr->pr_number,
                            'status' => 'procurement_processing',
                        ],
                    ];
                    if ($requesterUserId > 0) {
                        $this->notify($requesterUserId, $payload);
                    }
                    if (Auth::id()) {
                        $this->notify((int) Auth::id(), $payload);
                    }
                    $this->notifyUsersByPermissions(
                        (int) $pr->store_id,
                        [
                            'inventory.requisites.view',
                            'inventory.requisites.manage',
                            'inventory.requisites.approve',
                            'inventory.requisitions.view',
                            'inventory.requisitions.manage',
                            'inventory.requisitions.approve',
                        ],
                        $payload,
                        array_values(array_unique(array_filter([
                            $requesterUserId > 0 ? (int) $requesterUserId : null,
                            Auth::id() ? (int) Auth::id() : null,
                        ])))
                    );

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Purchase requisition approved and moved to procurement processing. Create the RFQ or PO manually when ready.',
                        'data' => $pr->fresh(),
                    ]);
                } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }
            } catch (\Throwable $e) {
                \Log::error('[Approve] exception', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve due to server error',
                    'error' => $e->getMessage(),
                ], 500);
            }
    }

    /**
     * Move a pending PR into procurement processing without approving it or
     * auto-creating an RFQ/PO. The UI then opens the appropriate create form.
     */
    public function startProcessing(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $canCreateRequest = $user->hasPermissionTo('procurement.requisitions.manage')
            || $user->hasPermissionTo('procurement.requisitions.approve')
            || $user->hasPermissionTo('procurement.rfq.manage')
            || $user->hasPermissionTo('procurement.purchase_orders.manage');

        abort_unless($canCreateRequest, 403, 'You do not have permission to process this requisition.');

        $pr = DB::transaction(function () use ($id, $user) {
            $requisition = PurchaseRequisition::query()
                ->where('store_id', (int) $user->store_id)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($requisition->status === 'procurement_processing') {
                return $requisition;
            }

            if ($requisition->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending requisitions can be moved to procurement processing.',
                ]);
            }

            $requisition->update(['status' => 'procurement_processing']);

            return $requisition;
        });

        return response()->json([
            'success' => true,
            'data' => $pr->fresh(),
        ]);
    }

    /**
     * Reject PR
     * POST /api/procurement/requisitions/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $pr->update([
            'status' => 'rejected',
            'approval_chain' => array_merge($pr->approval_chain ?? [], [
                [
                    'approver_permission' => 'procurement.requisitions.approve',
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->full_name,
                    'action' => 'rejected',
                    'reason' => $validated['reason'],
                    'rejected_at' => now()->toDateTimeString(),
                ]
            ]),
        ]);

        $pr->loadMissing(['requestedBy']);
        $requesterUserId = (int) ($pr->requestedBy?->user_id ?? 0);
        $payload = [
            'store_id' => (int) $pr->store_id,
            'branch_id' => (int) $pr->branch_id,
            'module' => 'inventory',
            'entity_type' => 'purchase_requisition',
            'entity_id' => (int) $pr->id,
            'action' => 'rejected',
            'title' => 'Purchase Requisition Rejected',
            'message' => "PR {$pr->pr_number} has been rejected by procurement.",
            'severity' => 'warn',
            'link' => "/inventory/requisites/{$pr->id}",
            'data' => [
                'pr_id' => (int) $pr->id,
                'pr_number' => (string) $pr->pr_number,
                'status' => 'rejected',
            ],
        ];
        if ($requesterUserId > 0) {
            $this->notify($requesterUserId, $payload);
        }
        if (Auth::id()) {
            $this->notify((int) Auth::id(), $payload);
        }
        $this->notifyUsersByPermissions(
            (int) $pr->store_id,
            [
                'inventory.requisites.view',
                'inventory.requisites.manage',
                'inventory.requisites.approve',
                'inventory.requisitions.view',
                'inventory.requisitions.manage',
                'inventory.requisitions.approve',
            ],
            $payload,
            array_values(array_unique(array_filter([
                $requesterUserId > 0 ? (int) $requesterUserId : null,
                Auth::id() ? (int) Auth::id() : null,
            ])))
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition rejected',
        ]);
    }

    /**
     * Cancel PR
     * POST /api/procurement/requisitions/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (!in_array($pr->status, ['draft', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or pending requisitions can be cancelled',
            ], 422);
        }

        $pr->update([
            'status' => 'cancelled',
            'reason' => $pr->reason . "\n\nCancellation reason: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition cancelled',
        ]);
    }

    /**
     * Delivery logs tied to a PR (via its linked PO shipment if any)
     * GET /api/procurement/requisitions/{id}/delivery-logs
     */
    public function deliveryLogs($id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);
        $po = PurchaseOrder::where('purchase_requisition_id', $pr->id)->first();
        $shipment = null;
        $logs = [];

        if ($po) {
            $shipment = PurchaseOrderShipment::where('purchase_order_id', $po->id)->first();
            if ($shipment) {
                $logs = PurchaseOrderDeliveryLog::with(['creator', 'attachments'])
                    ->where('shipment_id', $shipment->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $po->status ?? $pr->status ?? null,
                'shipment' => $shipment,
                'logs' => $logs,
            ],
        ]);
    }

    /**
     * Delete PR
     * DELETE /api/procurement/requisitions/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::findOrFail($id);

        if ($pr->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requisitions can be deleted',
            ], 422);
        }

        $pr->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition deleted successfully',
        ]);
    }

    /**
     * Check if a supplier can provide a specific product for the store
     */
    private function supplierCanProvideProduct(int $supplierId, int $productId, int $storeId): bool
    {
        return DB::table('supplier_products')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id')
            ->where('supplier_products.supplier_id', $supplierId)
            ->where('supplier_products.product_id', $productId)
            ->where('suppliers.store_id', $storeId)
            ->exists();
    }

    private function productHasMappedSupplier(int $productId, int $storeId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        return DB::table('supplier_products')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id')
            ->where('supplier_products.product_id', $productId)
            ->where('suppliers.store_id', $storeId)
            ->exists();
    }

    private function isValidVariationForProduct($variationId, int $productId, int $storeId): bool
    {
        if (empty($variationId)) {
            return true;
        }

        return ProductVariation::query()
            ->where('id', (int) $variationId)
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->exists();
    }

}
