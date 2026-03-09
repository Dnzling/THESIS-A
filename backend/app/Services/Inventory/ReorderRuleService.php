<?php
// backend/app/Services/Inventory/ReorderRuleService.php

namespace App\Services\Inventory;

use App\Models\Inventory\ReorderRule;
use App\Models\Inventory\BranchInventory;
use Illuminate\Support\Facades\Log;
use Exception;

class ReorderRuleService
{
    /**
     * Create a new reorder rule.
     */
    public function createReorderRule(array $data): ReorderRule
    {
        try {
            // Set default values
            $data['is_active'] = $data['is_active'] ?? true;

            $rule = ReorderRule::create($data);

            Log::info('Reorder rule created successfully', [
                'rule_id' => $rule->id,
                'product_id' => $rule->product_id,
                'branch_id' => $rule->branch_id,
                'rule_type' => $rule->rule_type,
                'trigger_type' => $rule->trigger_type,
            ]);

            return $rule;

        } catch (Exception $e) {
            Log::error('Failed to create reorder rule', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing reorder rule.
     */
    public function updateReorderRule(ReorderRule $rule, array $data): ReorderRule
    {
        try {
            $oldData = $rule->toArray();

            $rule->update($data);

            Log::info('Reorder rule updated successfully', [
                'rule_id' => $rule->id,
                'changes' => array_diff_assoc($data, $oldData),
            ]);

            return $rule;

        } catch (Exception $e) {
            Log::error('Failed to update reorder rule', [
                'rule_id' => $rule->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check reorder status for products in a branch.
     */
    public function checkReorderStatus(int $branchId, ?array $productIds = null): array
    {
        try {
            $query = ReorderRule::with(['product'])
                ->where('branch_id', $branchId)
                ->active();

            if ($productIds) {
                $query->whereIn('product_id', $productIds);
            }

            $rules = $query->get();

            $results = [];
            foreach ($rules as $rule) {
                $currentStock = $this->getCurrentStock($rule->product_id, $branchId);
                $shouldReorder = $rule->shouldReorder($currentStock);

                $results[] = [
                    'rule_id' => $rule->id,
                    'product_id' => $rule->product_id,
                    'product_name' => $rule->product->name,
                    'current_stock' => $currentStock,
                    'reorder_point' => $rule->reorder_point,
                    'safety_stock' => $rule->safety_stock,
                    'should_reorder' => $shouldReorder,
                    'recommended_quantity' => $shouldReorder ? $rule->getReorderQuantity() : 0,
                    'priority' => $rule->priority,
                    'rule_type' => $rule->rule_type,
                    'trigger_type' => $rule->trigger_type,
                ];
            }

            return $results;

        } catch (Exception $e) {
            Log::error('Failed to check reorder status', [
                'branch_id' => $branchId,
                'product_ids' => $productIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate reorder suggestions for a branch.
     */
    public function generateReorderSuggestions(int $branchId, bool $includeAllProducts = false): array
    {
        try {
            $suggestions = [];

            if ($includeAllProducts) {
                // Get all products with low stock (no specific rules)
                $lowStockProducts = BranchInventory::with(['product'])
                    ->where('branch_id', $branchId)
                    ->whereRaw('quantity_on_hand <= reorder_point')
                    ->get();

                foreach ($lowStockProducts as $inventory) {
                    // Check if rule already exists
                    $existingRule = ReorderRule::where('product_id', $inventory->product_id)
                        ->where('branch_id', $branchId)
                        ->first();

                    if (!$existingRule) {
                        $suggestions[] = [
                            'type' => 'new_rule',
                            'product_id' => $inventory->product_id,
                            'product_name' => $inventory->product->name,
                            'current_stock' => $inventory->quantity_on_hand,
                            'reorder_point' => $inventory->reorder_point,
                            'suggested_rule' => [
                                'rule_type' => 'automatic',
                                'trigger_type' => 'reorder_point',
                                'reorder_point' => $inventory->reorder_point,
                                'reorder_quantity' => max(10, $inventory->reorder_point * 0.5), // Suggest 50% of reorder point or minimum 10
                                'safety_stock' => max(5, $inventory->reorder_point * 0.2), // Suggest 20% of reorder point or minimum 5
                                'priority' => 'medium',
                            ],
                        ];
                    }
                }
            }

            // Get existing rules that need reordering
            $reorderStatus = $this->checkReorderStatus($branchId);

            foreach ($reorderStatus as $status) {
                if ($status['should_reorder']) {
                    $suggestions[] = [
                        'type' => 'reorder_needed',
                        'rule_id' => $status['rule_id'],
                        'product_id' => $status['product_id'],
                        'product_name' => $status['product_name'],
                        'current_stock' => $status['current_stock'],
                        'recommended_quantity' => $status['recommended_quantity'],
                        'priority' => $status['priority'],
                    ];
                }
            }

            // Sort by priority
            usort($suggestions, function ($a, $b) {
                $priorityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
                $aPriority = $priorityOrder[$a['priority'] ?? 'medium'] ?? 2;
                $bPriority = $priorityOrder[$b['priority'] ?? 'medium'] ?? 2;
                return $bPriority <=> $aPriority;
            });

            return $suggestions;

        } catch (Exception $e) {
            Log::error('Failed to generate reorder suggestions', [
                'branch_id' => $branchId,
                'include_all_products' => $includeAllProducts,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk update rule priorities.
     */
    public function bulkUpdatePriority(array $ruleIds, string $priority): int
    {
        try {
            $count = ReorderRule::whereIn('id', $ruleIds)
                ->update(['priority' => $priority]);

            Log::info('Bulk priority update completed', [
                'rule_ids' => $ruleIds,
                'new_priority' => $priority,
                'updated_count' => $count,
            ]);

            return $count;

        } catch (Exception $e) {
            Log::error('Failed to bulk update priorities', [
                'rule_ids' => $ruleIds,
                'priority' => $priority,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate EOQ for a rule.
     */
    public function calculateEOQ(ReorderRule $rule, float $annualDemand, float $orderingCost, float $holdingCost): float
    {
        try {
            $eoq = $rule->calculateEOQ($annualDemand, $orderingCost, $holdingCost);

            // Update the rule with the calculated EOQ
            $rule->update(['economic_order_quantity' => $eoq]);

            Log::info('EOQ calculated and updated', [
                'rule_id' => $rule->id,
                'annual_demand' => $annualDemand,
                'ordering_cost' => $orderingCost,
                'holding_cost' => $holdingCost,
                'eoq' => $eoq,
            ]);

            return $eoq;

        } catch (Exception $e) {
            Log::error('Failed to calculate EOQ', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get rules needing review.
     */
    public function getRulesNeedingReview(): array
    {
        try {
            return ReorderRule::with(['product', 'branch'])
                ->active()
                ->where(function ($query) {
                    $query->whereNull('next_review_date')
                          ->orWhere('next_review_date', '<=', now());
                })
                ->orderBy('next_review_date')
                ->get()
                ->toArray();

        } catch (Exception $e) {
            Log::error('Failed to get rules needing review', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate reorder rule data before creation/update.
     */
    public function validateReorderRuleData(array $data, ?ReorderRule $rule = null): array
    {
        $errors = [];

        // Check unique product-branch combination
        if (isset($data['product_id']) && isset($data['branch_id'])) {
            $query = ReorderRule::where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id']);

            if ($rule) {
                $query->where('id', '!=', $rule->id);
            }

            if ($query->exists()) {
                $errors[] = 'A reorder rule already exists for this product and branch combination.';
            }
        }

        // Check reorder point vs maximum stock
        if (isset($data['reorder_point']) && isset($data['maximum_stock'])) {
            if ($data['reorder_point'] >= $data['maximum_stock']) {
                $errors[] = 'Maximum stock must be greater than reorder point.';
            }
        }

        // Check safety stock vs reorder point
        if (isset($data['safety_stock']) && isset($data['reorder_point'])) {
            if ($data['safety_stock'] >= $data['reorder_point']) {
                $errors[] = 'Safety stock should be less than reorder point.';
            }
        }

        // Validate seasonal adjustments
        if (isset($data['seasonal_adjustments'])) {
            $months = range(1, 12);
            $providedMonths = array_keys($data['seasonal_adjustments']);

            if (count($providedMonths) !== 12 || array_diff($months, $providedMonths) !== array_diff($providedMonths, $months)) {
                $errors[] = 'Seasonal adjustments must include all 12 months (1-12).';
            }
        }

        return $errors;
    }

    /**
     * Get current stock for a product in a branch.
     */
    private function getCurrentStock(int $productId, int $branchId): float
    {
        return BranchInventory::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->sum('quantity_on_hand');
    }
}