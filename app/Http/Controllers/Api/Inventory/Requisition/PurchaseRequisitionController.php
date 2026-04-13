<?php

namespace App\Http\Controllers\Api\Inventory\Requisition;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;
use App\Models\Procurement\Config\ProcurementSettings;
use App\Models\ProductCatalog\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Inventory-scoped Purchase Requisitions (branch-only).
 *
 * This replaces the legacy Stock Order Requests flow.
 * Endpoints live under /api/inventory/requisitions and always scope to the user's branch.
 */
class PurchaseRequisitionController extends Controller
{
    protected function userHasAnyPermission(array $permissions, $user = null): bool
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return false;
        }

        $storeId = is_int($user)
            ? $user
            : (int) ($authUser->store_id ?? 0);

        foreach ($permissions as $permission) {
            $normalized = (string) $permission;
            $aliases = array_values(array_unique([
                $normalized,
                Str::contains($normalized, '_') ? str_replace('_', '-', $normalized) : $normalized,
                Str::contains($normalized, '-') ? str_replace('-', '_', $normalized) : $normalized,
            ]));

            foreach ($aliases as $candidate) {
                if ($candidate && $authUser->hasPermissionTo($candidate, $storeId)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveBranchId(): int
    {
        $user = Auth::user();
        return (int) ($user?->branch_id ?: $user?->employee?->branch_id ?: 0);
    }

    /**
     * GET /api/inventory/requisitions
     * List requisitions for the current user's branch only.
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = (int) Auth::user()->store_id;
        $branchId = $this->resolveBranchId();
        $hasEmployeesTable = Schema::hasTable('employees');
        $hasEmployeeNumber = $hasEmployeesTable && Schema::hasColumn('employees', 'employee_number');

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'No branch is assigned to your user profile.',
            ], 422);
        }

        $with = ['branch', 'items.product', 'items.variation'];
        if ($hasEmployeesTable) {
            $with[] = 'requestedBy';
        }

        $query = PurchaseRequisition::with($with)
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('requisition_type')) {
            $query->where('requisition_type', (string) $request->input('requisition_type'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', (int) $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search, $hasEmployeesTable, $hasEmployeeNumber) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");

                if ($hasEmployeesTable) {
                    $q->orWhereHas('requestedBy', function ($rq) use ($search, $hasEmployeeNumber) {
                        $rq->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");

                        if ($hasEmployeeNumber) {
                            $rq->orWhere('employee_number', 'like', "%{$search}%");
                        }
                    });
                }
            });
        }

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'pr_number', 'status', 'requisition_type'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $rows = $query->orderBy($sortBy, $sortOrder)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * GET /api/inventory/requisitions/{id}
     */
    public function show(int $id): JsonResponse
    {
        $storeId = (int) Auth::user()->store_id;
        $branchId = $this->resolveBranchId();
        $hasEmployeesTable = Schema::hasTable('employees');

        $with = [
            'branch',
            'items.product.suppliers',
            'items.variation',
            'purchaseOrders.supplier',
            'rfqs.awardedToSupplier',
        ];
        if ($hasEmployeesTable) {
            $with[] = 'requestedBy';
        }

        $pr = PurchaseRequisition::with($with)
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pr,
        ]);
    }

    /**
     * POST /api/inventory/requisitions
     *
     * Supports:
     * 1) Full PR payload: { requisition_type, reason, priority?, items: [...] }
     * 2) Branch inventory shortcut: { branch_inventory_id, requested_quantity, reason?, requisition_type? }
     *
     * Branch is always auto-filled from the authenticated user's profile.
     */
    public function store(Request $request): JsonResponse
    {
        $storeId = (int) Auth::user()->store_id;
        $branchId = $this->resolveBranchId();

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'No branch is assigned to your user profile.',
            ], 422);
        }

        $validated = $request->validate([
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

            'branch_inventory_id' => 'required_without:items|nullable|exists:branch_inventory,id',
            'selected_supplier_id' => 'nullable|exists:suppliers,id',
            'requested_quantity' => 'required_without:items|nullable|integer|min:1',

            // Inventory flow default: create+submit in one click
            'auto_submit' => 'nullable|boolean',
        ]);

        $items = $validated['items'] ?? null;
        $reason = $validated['reason'] ?? null;

        if (!$items) {
            $inv = BranchInventory::query()
                ->where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->findOrFail((int) $validated['branch_inventory_id']);

            $items = [[
                'product_id' => (int) $inv->product_id,
                'variation_id' => $inv->variation_id ? (int) $inv->variation_id : null,
                'quantity_requested' => (int) $validated['requested_quantity'],
                'estimated_unit_cost' => null,
                'selected_supplier_id' => isset($validated['selected_supplier_id']) ? (int) $validated['selected_supplier_id'] : null,
                'tax_rate' => 0,
                'specifications' => null,
            ]];

            if (!$reason) {
                $reason = 'Stock replenishment request.';
            }
        }

        DB::beginTransaction();
        try {
            $prNumber = 'PR-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

            $resolvedItems = [];
            foreach ($items as $item) {
                $selectedSupplierId = isset($item['selected_supplier_id']) ? (int) $item['selected_supplier_id'] : null;
                if ($selectedSupplierId && !$this->supplierCanProvideProduct($selectedSupplierId, (int) $item['product_id'], $storeId)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Selected supplier {$selectedSupplierId} is not mapped to product {$item['product_id']} for this store.",
                    ], 422);
                }

                $item['estimated_unit_cost'] = $this->resolveEstimatedUnitCost(
                    (int) $item['product_id'],
                    $item['estimated_unit_cost'] ?? null
                );
                $resolvedItems[] = $item;
            }

            // Hard validation: mixed selected supplier assignment is not allowed.
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

            // Hard validation: when suppliers are selected, all selected items must use the same supplier.
            $selectedSupplierIds = collect($resolvedItems)
                ->map(function ($item) {
                    return isset($item['selected_supplier_id']) ? (int) $item['selected_supplier_id'] : null;
                })
                ->filter(function ($supplierId) {
                    return !is_null($supplierId) && $supplierId > 0;
                })
                ->unique()
                ->values();

            if ($selectedSupplierIds->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected suppliers must be the same for one request. Create separate requisitions per supplier to avoid processing errors.',
                    'errors' => [
                        'items' => [
                            'Selected supplier must be identical across all line items.',
                        ],
                    ],
                ], 422);
            }

            // Validation rule by product supplier mapping:
            // - all items with supplier mapping => allowed (PO flow)
            // - all items without supplier mapping => allowed (RFQ flow)
            // - mixed (some with supplier mapping, some without) => not allowed
            $itemHasSupplierFlags = collect($resolvedItems)->map(function ($item) use ($storeId) {
                return $this->productHasMappedSupplier((int) ($item['product_id'] ?? 0), $storeId);
            });
            $hasAnyWithSupplier = $itemHasSupplierFlags->contains(true);
            $hasAnyWithoutSupplier = $itemHasSupplierFlags->contains(false);
            if ($hasAnyWithSupplier && $hasAnyWithoutSupplier) {
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
                $estimatedAmount += ((int) $item['quantity_requested'] * (float) ($item['estimated_unit_cost'] ?? 0));
            }

            $settings = ProcurementSettings::where('store_id', $storeId)->first();
            $procurementRoute = 'branch_direct';
            if ($settings) {
                if ($estimatedAmount >= $settings->procurement_threshold) {
                    $procurementRoute = 'centralized';
                }
                if ($settings->shouldRequireRFQ($estimatedAmount)) {
                    $procurementRoute = 'rfq_required';
                }
            }

            $requiredApprovals = ['warehouse_manager'];
            if ($estimatedAmount >= 100000) $requiredApprovals[] = 'branch_manager';
            if ($estimatedAmount >= 500000) $requiredApprovals[] = 'finance_manager';

            $requestedByEmployeeId = Auth::user()?->employee?->id;

            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'store_id' => $storeId,
                'branch_id' => $branchId,
                'requisition_type' => $validated['requisition_type'] ?? 'regular',
                'status' => 'draft',
                'estimated_amount' => $estimatedAmount,
                'procurement_route' => $procurementRoute,
                'required_approvals' => $requiredApprovals,
                'reason' => (string) $reason,
                'priority' => $validated['priority'] ?? 3,
                'requested_by' => $requestedByEmployeeId,
            ]);

            foreach ($resolvedItems as $item) {
                PurchaseRequisitionItem::create([
                    'requisition_id' => $pr->id,
                    'product_id' => (int) $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'selected_supplier_id' => $item['selected_supplier_id'] ?? null,
                    'quantity_requested' => (int) $item['quantity_requested'],
                    'estimated_unit_cost' => $item['estimated_unit_cost'] ?? null,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'specifications' => $item['specifications'] ?? null,
                ]);
            }

            $autoSubmit = array_key_exists('auto_submit', $validated) ? (bool) $validated['auto_submit'] : true;
            if ($autoSubmit) {
                $pr->submit();
                $this->notifyProcurementTeamForSubmittedPr($pr);
            }

            $canInventoryApprove = $this->userHasAnyPermission([
                'inventory.requisitions.approve',
                'inventory.requisition.approve',
                'inventory.requisites.approve',
            ], $storeId);

            if ($autoSubmit && $canInventoryApprove) {
                $note = 'Auto-approved on creation.';

                $chain = $pr->approval_chain ?? [];
                $chain[] = [
                    'role' => Auth::user()->role->name ?? 'approver',
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->full_name ?? null,
                    'action' => 'approved',
                    'notes' => $note,
                    'approved_at' => now()->toDateTimeString(),
                ];

                $pr->update([
                    'status' => 'procurement_processing',
                    'approval_chain' => $chain,
                ]);

                if ($this->userHasAnyPermission(['procurement.requisitions.approve'], $storeId)) {
                    $pr->addApproval('procurement.requisitions.approve', (int) Auth::id(), (string) (Auth::user()->full_name ?? 'Approver'), $note);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase requisition created successfully',
                'data' => $pr->load(['branch', 'requestedBy', 'items.product', 'items.variation']),
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

    /**
     * POST /api/inventory/requisitions/{id}/submit
     */
    public function submit(int $id): JsonResponse
    {
        $storeId = (int) Auth::user()->store_id;
        $branchId = $this->resolveBranchId();

        $pr = PurchaseRequisition::with('items')
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->findOrFail($id);

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
        $this->notifyProcurementTeamForSubmittedPr($pr);

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition submitted successfully',
            'data' => $pr->fresh(),
        ]);
    }

    private function notifyProcurementTeamForSubmittedPr(PurchaseRequisition $pr): void
    {
        $storeId = (int) $pr->store_id;
        if ($storeId <= 0) {
            return;
        }

        $this->notifyUsersByPermissions(
            $storeId,
            [
                'procurement.requisitions.manage',
                'procurement.requisitions.approve',
                'procurement.rfq.manage',
                'procurement.purchase_orders.manage',
            ],
            [
                'store_id' => $pr->store_id,
                'branch_id' => $pr->branch_id,
                'module' => 'procurement',
                'entity_type' => 'purchase_requisition',
                'entity_id' => $pr->id,
                'action' => 'submitted',
                'title' => 'New Purchase Requisition Submitted',
                'message' => "PR {$pr->pr_number} is ready for procurement processing.",
                'severity' => 'info',
                'link' => "/system/procurement/purchase-requisitions/{$pr->id}",
            ],
            [Auth::id()]
        );
    }

    /**
     * POST /api/inventory/requisitions/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $storeId = (int) Auth::user()->store_id;
        $branchId = $this->resolveBranchId();

        $pr = PurchaseRequisition::query()
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $pr->update([
            'status' => 'rejected',
            'approval_chain' => array_merge($pr->approval_chain ?? [], [
                [
                    'role' => Auth::user()->role->name ?? null,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->full_name ?? null,
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
            'message' => "PR {$pr->pr_number} has been rejected.",
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
     * POST /api/inventory/requisitions/{id}/approve
     * Simple branch-level approval used by inventory module.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $storeId = (int) Auth::user()->store_id;
        $branchId = $this->resolveBranchId();

        $pr = PurchaseRequisition::query()
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        if (!in_array($pr->status, ['draft', 'pending', 'warehouse_approved', 'branch_manager_approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requisitions can be approved here.',
            ], 422);
        }

        $note = trim((string) ($request->input('notes') ?? ''));
        $approvalEntry = [
            'role' => Auth::user()->role->name ?? 'approver',
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->full_name ?? null,
            'action' => 'approved',
            'notes' => $note ?: null,
            'approved_at' => now()->toDateTimeString(),
        ];

        $chain = $pr->approval_chain ?? [];
        $chain[] = $approvalEntry;

        $pr->update([
            'status' => 'procurement_processing',
            'approval_chain' => $chain,
        ]);

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
            'message' => "PR {$pr->pr_number} has been approved and moved to procurement processing.",
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

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition approved',
            'data' => $pr->fresh(),
        ]);
    }

    /**
     * POST /api/inventory/requisitions/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $storeId = (int) Auth::user()->store_id;
        $branchId = $this->resolveBranchId();

        $pr = PurchaseRequisition::query()
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (!in_array($pr->status, ['draft', 'pending'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or pending requisitions can be cancelled',
            ], 422);
        }

        $pr->update([
            'status' => 'cancelled',
            'reason' => trim((string) $pr->reason) . "\n\nCancellation reason: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase requisition cancelled',
        ]);
    }

    private function resolveEstimatedUnitCost(int $productId, $requestedCost): float
    {
        $requested = is_null($requestedCost) ? null : (float) $requestedCost;
        if (!is_null($requested) && $requested > 0) {
            return round($requested, 2);
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
        if ($costPrice > 0) return round($costPrice, 2);

        $basePrice = (float) ($product?->base_price ?? 0);
        if ($basePrice > 0) return round($basePrice, 2);

        return 0.0;
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
}
