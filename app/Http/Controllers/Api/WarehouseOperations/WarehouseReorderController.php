<?php

namespace App\Http\Controllers\Api\WarehouseOperations;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\ReorderRule;
use App\Models\Inventory\ReorderSuggestion;
use App\Models\Store\Branch;
use App\Services\Inventory\ReorderSuggestionNotificationService;
use App\Services\Inventory\ReorderSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseReorderController extends Controller
{
    public function __construct(
        private readonly ReorderSuggestionService $suggestions,
        private readonly ReorderSuggestionNotificationService $notifications,
    ) {}

    private function branch(Request $request): Branch
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?: $user?->employee?->store_id);
        $branchId = (int) ($user?->branch_id ?: $user?->employee?->branch_id);
        abort_if($storeId < 1 || $branchId < 1, 403, 'A warehouse branch assignment is required.');

        return Branch::query()->where('store_id', $storeId)->where('branch_type', 'warehouse')
            ->findOrFail($branchId);
    }

    public function rules(Request $request): JsonResponse
    {
        $branch = $this->branch($request);
        return response()->json(['data' => ReorderRule::with('product:id,product_name,sku')
            ->where('branch_id', $branch->id)->orderBy('id', 'desc')->paginate(15)]);
    }

    public function options(Request $request): JsonResponse
    {
        $branch = $this->branch($request);
        return response()->json(['data' => BranchInventory::with('product:id,product_name,sku')
            ->where('branch_id', $branch->id)->where('store_id', $branch->store_id)
            ->whereNull('variation_id')->get(['id', 'product_id', 'branch_id'])
            ->pluck('product')->filter()->unique('id')->values()]);
    }

    public function saveRule(Request $request): JsonResponse
    {
        $branch = $this->branch($request);
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('branch_inventory', 'product_id')->where('branch_id', $branch->id)],
            'reorder_point' => ['required', 'numeric', 'min:0'],
            'reorder_quantity' => ['required', 'numeric', 'min:1'],
            'safety_stock' => ['nullable', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'gt:reorder_point'],
            'avg_daily_demand' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'review_period_days' => ['nullable', 'integer', 'min:0'],
            'basis_type' => ['required', Rule::in(['reorder_point', 'demand_lead_time'])],
        ]);
        $rule = ReorderRule::firstOrNew(['branch_id' => $branch->id, 'product_id' => $data['product_id']]);
        $rule->fill($data + ['branch_id' => $branch->id, 'rule_type' => 'automatic',
            'trigger_type' => $data['basis_type'] === 'demand_lead_time' ? 'forecast' : 'reorder_point',
            'priority' => 'medium', 'auto_generate_po' => false, 'is_active' => true]);
        $rule->save();

        BranchInventory::query()
            ->where('store_id', $branch->store_id)
            ->where('branch_id', $branch->id)
            ->where('product_id', $rule->product_id)
            ->pluck('id')
            ->each(function ($inventoryId) use ($branch): void {
                $result = $this->suggestions->generateSuggestions(
                    (int) $branch->store_id,
                    (int) $branch->id,
                    (int) $inventoryId,
                );
                foreach (($result['suggestions'] ?? []) as $suggestion) {
                    $this->notifications->notify($suggestion, 'reorder_rule_saved');
                }
            });

        return response()->json(['data' => $rule->load('product:id,product_name,sku')]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $branch = $this->branch($request);
        $rows = ReorderSuggestion::with([
            'product:id,product_name,sku',
            'variation:id,variation_name,variation_sku',
            'reorderRule:id,branch_id,product_id,basis_type,trigger_type,reorder_point,reorder_quantity,lead_time_days,review_period_days,safety_stock,maximum_stock,avg_daily_demand',
        ])
            ->where('branch_id', $branch->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()->paginate(15);
        return response()->json(['data' => $rows]);
    }

    public function generate(Request $request): JsonResponse
    {
        $branch = $this->branch($request);
        return response()->json(['data' => $this->suggestions->generateSuggestions((int) $branch->store_id, (int) $branch->id)]);
    }

    public function approve(Request $request, ReorderSuggestion $suggestion): JsonResponse
    {
        $branch = $this->branch($request);
        abort_unless($request->user()?->hasAnyPermission([
            'warehouse.requisition.manage',
            'warehouse.purchase-requisitions.manage',
        ], (int) $branch->store_id), 403, 'You do not have permission to approve warehouse reorder suggestions.');
        abort_unless((int) $suggestion->branch_id === (int) $branch->id, 404);
        abort_unless($suggestion->status === 'pending', 422, 'Only pending suggestions can be approved.');
        $employeeId = $request->user()?->employee?->id;
        abort_if(!$employeeId, 403, 'An employee profile is required to approve a reorder suggestion.');
        $approved = $this->suggestions->approveSuggestion($suggestion, $employeeId);
        return response()->json(['success' => $approved, 'data' => $suggestion->fresh()]);
    }

    public function createPurchaseRequisitions(Request $request): JsonResponse
    {
        $branch = $this->branch($request);
        $user = $request->user();
        abort_unless($user?->hasAnyPermission([
            'warehouse.requisition.manage',
            'warehouse.purchase-requisitions.manage',
        ], (int) $branch->store_id), 403, 'You do not have permission to create warehouse purchase requisitions.');

        $validated = $request->validate([
            'suggestion_ids' => ['required', 'array', 'min:1', 'max:100'],
            'suggestion_ids.*' => ['required', 'integer', 'distinct', 'exists:reorder_suggestions,id'],
        ]);
        $employeeId = $user?->employee?->id;
        abort_if(!$employeeId, 403, 'An employee profile is required to create purchase requisitions.');

        $purchaseRequisitions = $this->suggestions->createGroupedPurchaseRequisitions(
            $validated['suggestion_ids'],
            (int) $branch->store_id,
            (int) $branch->id,
            $employeeId,
        );

        return response()->json([
            'success' => true,
            'data' => $purchaseRequisitions,
            'message' => count($purchaseRequisitions) . ' draft purchase requisition(s) created.',
        ], 201);
    }
}
