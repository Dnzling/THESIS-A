<?php
// backend/app/Http/Controllers/Api/Inventory/ReorderRuleController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ReorderRule;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Http\Requests\Inventory\ReorderRuleRequest;
use App\Services\Inventory\ReorderRuleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReorderRuleController extends Controller
{
    protected ReorderRuleService $reorderRuleService;

    public function __construct(ReorderRuleService $reorderRuleService)
    {
        $this->reorderRuleService = $reorderRuleService;
    }

    /**
     * Get authenticated user context (store + branch), with employee fallback.
     */
    private function getUserContext(): array
    {
        $user = auth()->user();
        $storeId = (int) ($user?->store_id ?? 0);
        $branchId = (int) ($user?->branch_id ?? 0);

        if ($user && ($storeId === 0 || $branchId === 0)) {
            $employee = Employee::query()
                ->where('user_id', $user->id)
                ->first(['store_id', 'branch_id']);

            $storeId = $storeId ?: (int) ($employee?->store_id ?? 0);
            $branchId = $branchId ?: (int) ($employee?->branch_id ?? 0);
        }

        return [
            'store_id' => $storeId,
            'branch_id' => $branchId,
        ];
    }

    /**
     * Resolve branch from request, defaulting to current user's branch.
     */
    private function resolveBranchId(Request $request): int
    {
        $context = $this->getUserContext();
        return (int) ($request->branch_id ?? $context['branch_id'] ?? 0);
    }

    private function hasGlobalAccess(): bool
    {
        $roleName = strtolower(auth()->user()?->role?->name ?? '');
        return in_array($roleName, ['super_admin', 'owner'], true);
    }

    /**
     * Restrict branch-scoped records when user is branch-bound.
     */
    private function canAccessBranchRecord(int $recordBranchId): bool
    {
        $context = $this->getUserContext();
        if ($this->hasGlobalAccess()) {
            return true;
        }

        if (empty($context['store_id']) && empty($context['branch_id'])) {
            return false;
        }

        if (!empty($context['branch_id'])) {
            return (int) $context['branch_id'] === (int) $recordBranchId;
        }

        return Branch::query()
            ->where('id', $recordBranchId)
            ->where('store_id', $context['store_id'])
            ->exists();
    }

    /**
     * Display a listing of reorder rules.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ReorderRule::with(['product', 'branch']);
            $context = $this->getUserContext();

            if (!$this->hasGlobalAccess() && empty($context['store_id']) && empty($context['branch_id'])) {
                $perPage = $request->get('per_page', 15);
                $empty = ReorderRule::query()->whereRaw('1 = 0')->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'data' => $empty,
                    'message' => 'Reorder rules retrieved successfully'
                ]);
            }

            if (!empty($context['store_id'])) {
                $query->whereHas('branch', function ($q) use ($context) {
                    $q->where('store_id', $context['store_id']);
                });
            }

            // Filter by branch if provided
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            } elseif (!empty($context['branch_id'])) {
                $query->where('branch_id', $context['branch_id']);
            }

            // Filter by product if provided
            if ($request->has('product_id') && $request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by rule type if provided
            if ($request->has('rule_type') && $request->rule_type) {
                $query->where('rule_type', $request->rule_type);
            }

            // Filter by trigger type if provided
            if ($request->has('trigger_type') && $request->trigger_type) {
                $query->where('trigger_type', $request->trigger_type);
            }

            // Filter by priority if provided
            if ($request->has('priority') && $request->priority) {
                $query->where('priority', $request->priority);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by product name
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $rules = $query->orderBy('priority', 'desc')->orderBy('product_id')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $rules,
                'message' => 'Reorder rules retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving reorder rules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reorder rules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created reorder rule.
     */
    public function store(ReorderRuleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $payload = $request->validated();
            if (empty($payload['branch_id'])) {
                $payload['branch_id'] = $this->resolveBranchId($request);
            }

            $rule = $this->reorderRuleService->createReorderRule($payload);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $rule->load(['product', 'branch']),
                'message' => 'Reorder rule created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating reorder rule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reorder rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified reorder rule.
     */
    public function show(ReorderRule $reorderRule): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $reorderRule->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder rule.',
                ], 403);
            }

            $reorderRule->load(['product', 'branch']);

            return response()->json([
                'success' => true,
                'data' => $reorderRule,
                'message' => 'Reorder rule retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving reorder rule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reorder rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified reorder rule.
     */
    public function update(ReorderRuleRequest $request, ReorderRule $reorderRule): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $reorderRule->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder rule.',
                ], 403);
            }

            DB::beginTransaction();

            $payload = $request->validated();
            if (empty($payload['branch_id'])) {
                $payload['branch_id'] = (int) $reorderRule->branch_id;
            }

            $updatedRule = $this->reorderRuleService->updateReorderRule($reorderRule, $payload);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $updatedRule->load(['product', 'branch']),
                'message' => 'Reorder rule updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating reorder rule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reorder rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified reorder rule.
     */
    public function destroy(ReorderRule $reorderRule): JsonResponse
    {
        try {
            if (!$this->canAccessBranchRecord((int) $reorderRule->branch_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to reorder rule.',
                ], 403);
            }

            DB::beginTransaction();

            $reorderRule->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reorder rule deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting reorder rule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reorder rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rule types.
     */
    public function getRuleTypes(): JsonResponse
    {
        try {
            $types = [
                ['value' => 'manual', 'label' => 'Manual'],
                ['value' => 'automatic', 'label' => 'Automatic'],
                ['value' => 'demand_based', 'label' => 'Demand Based'],
            ];

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Rule types retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving rule types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rule types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trigger types.
     */
    public function getTriggerTypes(): JsonResponse
    {
        try {
            $types = [
                ['value' => 'reorder_point', 'label' => 'Reorder Point'],
                ['value' => 'safety_stock', 'label' => 'Safety Stock'],
                ['value' => 'forecast', 'label' => 'Forecast Based'],
                ['value' => 'seasonal', 'label' => 'Seasonal'],
            ];

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Trigger types retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving trigger types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trigger types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get basis types.
     */
    public function getBasisTypes(): JsonResponse
    {
        try {
            $types = [
                ['value' => 'reorder_point', 'label' => 'Reorder Point'],
                ['value' => 'demand_lead_time', 'label' => 'Demand + Lead Time'],
            ];

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Basis types retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving basis types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve basis types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get priority levels.
     */
    public function getPriorities(): JsonResponse
    {
        try {
            $priorities = [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'critical', 'label' => 'Critical'],
            ];

            return response()->json([
                'success' => true,
                'data' => $priorities,
                'message' => 'Priorities retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving priorities: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve priorities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check reorder status for products.
     */
    public function checkReorderStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'integer|exists:products,id',
            ]);

            $branchId = $this->resolveBranchId($request);
            if ($branchId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch is required for reorder status checks.',
                ], 422);
            }

            $result = $this->reorderRuleService->checkReorderStatus(
                $branchId,
                $request->product_ids
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Reorder status checked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking reorder status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check reorder status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate reorder suggestions.
     */
    public function generateSuggestions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
                'include_all_products' => 'boolean',
            ]);

            $branchId = $this->resolveBranchId($request);
            if ($branchId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch is required to generate reorder suggestions.',
                ], 422);
            }

            $suggestions = $this->reorderRuleService->generateReorderSuggestions(
                $branchId,
                $request->boolean('include_all_products', false)
            );

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'message' => 'Reorder suggestions generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating reorder suggestions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate reorder suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rules needing review.
     */
    public function getNeedingReview(): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            $rules = ReorderRule::with(['product', 'branch'])
                ->active()
                ->where(function ($query) {
                    $query->whereNull('next_review_date')
                          ->orWhere('next_review_date', '<=', now());
                });

            if (!empty($context['store_id'])) {
                $rules->whereHas('branch', function ($q) use ($context) {
                    $q->where('store_id', $context['store_id']);
                });
            }

            if (!empty($context['branch_id'])) {
                $rules->where('branch_id', $context['branch_id']);
            }

            $rules = $rules
                ->orderBy('next_review_date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $rules,
                'message' => 'Rules needing review retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving rules needing review: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rules needing review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update rule priorities.
     */
    public function bulkUpdatePriority(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'rule_ids' => 'required|array|min:1',
                'rule_ids.*' => 'integer|exists:reorder_rules,id',
                'priority' => 'required|string|in:low,medium,high,critical',
            ]);

            DB::beginTransaction();

            $ruleIds = $request->rule_ids;
            $context = $this->getUserContext();
            if (!empty($context['branch_id'])) {
                $ruleIds = ReorderRule::query()
                    ->whereIn('id', $request->rule_ids)
                    ->where('branch_id', $context['branch_id'])
                    ->pluck('id')
                    ->all();
            }

            $count = $this->reorderRuleService->bulkUpdatePriority(
                $ruleIds,
                $request->priority
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['updated_count' => $count],
                'message' => "{$count} rules updated successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk updating priorities: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update priorities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-create reorder rules from branch inventory.
     */
    public function autoCreateFromInventory(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
                'overwrite' => 'nullable|boolean',
            ]);

            $branchId = $this->resolveBranchId($request);
            if ($branchId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch is required to auto-create reorder rules.',
                ], 422);
            }

            DB::beginTransaction();

            $result = $this->reorderRuleService->autoCreateRulesFromInventory(
                $branchId,
                (bool) $request->boolean('overwrite', false)
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Auto-create finished. Created {$result['created_count']} rule(s), updated {$result['updated_count']}, skipped {$result['skipped_count']}.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error auto-creating reorder rules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-create reorder rules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
