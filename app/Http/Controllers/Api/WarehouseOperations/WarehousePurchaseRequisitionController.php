<?php

namespace App\Http\Controllers\Api\WarehouseOperations;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Procurement\Config\ProcurementSettings;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;
use App\Models\Store\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehousePurchaseRequisitionController extends Controller
{
    private function storeId(Request $request): int
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?: $user?->employee?->store_id);
        abort_if($storeId < 1, 403, 'Your account is not assigned to a store.');
        return $storeId;
    }

    public function options(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $user = $request->user();
        $branchId = (int) ($user?->branch_id ?: $user?->employee?->branch_id ?: $user?->branch?->id ?: $user?->employee?->branch?->id);
        abort_if($branchId < 1, 422, 'Your account is not assigned to a branch.');

        $branches = Branch::where('store_id', $storeId)
            ->whereKey($branchId)
            ->where('status', 'active')
            ->get(['id', 'name', 'branch_code', 'branch_type']);

        abort_if($branches->isEmpty(), 422, 'Your assigned branch is not active or does not belong to your store.');
        $inventory = BranchInventory::where('store_id', $storeId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->with(['branch:id,name,branch_code,branch_type', 'product', 'variation'])
            ->orderBy('branch_id')->orderBy('product_id')->get();

        return response()->json(['success' => true, 'data' => compact('branches', 'inventory')]);
    }

    public function index(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $branchIds = Branch::where('store_id', $storeId)->where('branch_type', 'warehouse')->pluck('id');
        $query = PurchaseRequisition::where('store_id', $storeId)->whereIn('branch_id', $branchIds)
            ->with(['branch', 'requestedBy.user', 'items.product', 'items.variation']);

        $query->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')));
        $query->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')));
        $query->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')));
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = trim((string) $request->input('search'));
            $q->where(function ($nested) use ($search) {
                $nested->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('requestedBy.user', function ($userQuery) use ($search) {
                        $userQuery->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%");
                    });
            });
        });

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortBy = in_array($sortBy, ['created_at', 'pr_number', 'status'], true) ? $sortBy : 'created_at';
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $rows = $query->orderBy($sortBy, $sortOrder)->paginate(max(1, min($request->integer('per_page', 15), 100)));
        $rows->getCollection()->transform(function (PurchaseRequisition $requisition) {
            $user = $requisition->requestedBy?->user;
            $name = trim(collect([$user?->fname, $user?->lname])->filter()->implode(' '));
            $requisition->setAttribute('requested_by_name', $name !== '' ? $name : null);
            $requisition->setAttribute('requested_by', $name !== '' ? $name : null);
            return $requisition;
        });

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $storeId = $this->storeId($request);
        $warehouseBranchIds = Branch::where('store_id', $storeId)
            ->where('branch_type', 'warehouse')
            ->pluck('id');

        $pr = PurchaseRequisition::where('store_id', $storeId)
            ->whereIn('branch_id', $warehouseBranchIds)
            ->with([
                'branch',
                'requestedBy.user:id,fname,lname',
                'items.product.suppliers',
                'items.variation',
                'purchaseOrders.supplier',
                'rfqs.awardedToSupplier',
            ])
            ->findOrFail($id);

        $user = $pr->requestedBy?->user;
        $name = trim(collect([$user?->fname, $user?->lname])->filter()->implode(' '));
        $pr->setAttribute('requested_by_name', $name !== '' ? $name : null);

        return response()->json(['success' => true, 'data' => $pr]);
    }

    public function store(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.branch_inventory_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
        ]);

        $branch = Branch::where('store_id', $storeId)->where('branch_type', 'warehouse')
            ->findOrFail((int) $validated['branch_id']);
        $inventoryIds = collect($validated['items'])->pluck('branch_inventory_id')->unique();
        $inventory = BranchInventory::where('store_id', $storeId)->where('branch_id', $branch->id)
            ->whereIn('id', $inventoryIds)->with(['product', 'variation'])->get()->keyBy('id');
        if ($inventory->count() !== $inventoryIds->count()) {
            return response()->json(['success' => false, 'message' => 'One or more items do not belong to the selected warehouse branch.'], 422);
        }

        $pr = DB::transaction(function () use ($request, $storeId, $branch, $validated, $inventory) {
            $amount = collect($validated['items'])->sum(function ($item) use ($inventory) {
                $stock = $inventory[(int) $item['branch_inventory_id']];
                return $this->unitCost($stock) * (int) $item['quantity_requested'];
            });
            $settings = ProcurementSettings::where('store_id', $storeId)->first();
            $procurementRoute = 'branch_direct';
            if ($settings) {
                if ($amount >= $settings->procurement_threshold) {
                    $procurementRoute = 'centralized';
                }
                if ($settings->shouldRequireRFQ($amount)) {
                    $procurementRoute = 'rfq_required';
                }
            }

            $pr = PurchaseRequisition::create([
                'pr_number' => 'PR-WH-'.now()->format('YmdHis').'-'.random_int(1000, 9999),
                'store_id' => $storeId, 'branch_id' => $branch->id, 'requisition_type' => 'regular',
                'status' => 'pending', 'estimated_amount' => $amount,
                'procurement_route' => $procurementRoute,
                'required_approvals' => ['warehouse_manager'], 'reason' => $validated['reason'],
                'priority' => 3, 'requested_by' => $request->user()?->employee?->id, 'submitted_at' => now(),
            ]);
            foreach ($validated['items'] as $item) {
                $stock = $inventory[(int) $item['branch_inventory_id']];
                PurchaseRequisitionItem::create([
                    'requisition_id' => $pr->id, 'product_id' => $stock->product_id,
                    'variation_id' => $stock->variation_id,
                    'quantity_requested' => (int) $item['quantity_requested'],
                    'estimated_unit_cost' => $this->unitCost($stock), 'tax_rate' => 0,
                ]);
            }
            return $pr;
        });

        $this->notifyUsersByPermissions($storeId, ['procurement.requisitions.view', 'procurement.requisitions.manage', 'procurement.requisitions.approve'], [
            'store_id' => $storeId, 'branch_id' => $branch->id, 'module' => 'procurement',
            'entity_type' => 'purchase_requisition', 'entity_id' => $pr->id, 'action' => 'submitted',
            'title' => 'Warehouse Purchase Requisition Submitted',
            'message' => "{$pr->pr_number} from {$branch->name} is ready for procurement.",
            'severity' => 'info', 'link' => "/procurement/purchase-requisitions/{$pr->id}",
        ], [$request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse purchase requisition created.',
            'data' => $pr->load(['items.product', 'items.variation']),
        ], 201);
    }

    private function unitCost(BranchInventory $stock): float
    {
        return (float) ($stock->variation?->cost_price ?? $stock->product?->cost_price ?? 0);
    }
}
