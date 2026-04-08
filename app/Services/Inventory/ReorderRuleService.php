<?php
// backend/app/Services/Inventory/ReorderRuleService.php

namespace App\Services\Inventory;

use App\Models\Inventory\ReorderRule;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
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
            $data = $this->hydrateDemandDefaults($data);

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
            $mergedData = array_merge($oldData, $data);
            $data = $this->hydrateDemandDefaults($data, $mergedData);

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
                    'product_name' => $rule->product->product_name ?? $rule->product->name,
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
                            'product_name' => $inventory->product->product_name ?? $inventory->product->name,
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
     * Auto-create reorder rules from existing branch inventory records.
     */
    public function autoCreateRulesFromInventory(int $branchId, bool $overwrite = false): array
    {
        try {
            $inventoryRows = BranchInventory::with('product')
                ->where('branch_id', $branchId)
                ->whereNotNull('product_id')
                ->get();

            $created = [];
            $updated = [];
            $skipped = [];

            foreach ($inventoryRows as $row) {
                if (!$row->product_id) {
                    continue;
                }

                $existing = ReorderRule::query()
                    ->where('branch_id', $branchId)
                    ->where('product_id', $row->product_id)
                    ->first();

                $reorderPoint = (float) ($row->reorder_point ?? 0);
                $reorderQty = (float) ($row->reorder_quantity ?? 0);
                $safetyStock = (float) ($row->safety_stock ?? 0);
                $maximumStock = (float) ($row->maximum_stock ?? 0);

                // Safe defaults when inventory thresholds are missing.
                if ($reorderPoint <= 0) {
                    $reorderPoint = 10;
                }
                if ($reorderQty <= 0) {
                    $reorderQty = max(1, (int) ceil($reorderPoint * 1.5));
                }
                if ($safetyStock <= 0 || $safetyStock >= $reorderPoint) {
                    $safetyStock = max(1, (int) floor($reorderPoint * 0.3));
                }
                if ($maximumStock <= $reorderPoint) {
                    $maximumStock = (float) max($reorderPoint + 1, (int) ceil($reorderPoint * 3));
                }

                $payload = [
                    'product_id' => (int) $row->product_id,
                    'branch_id' => $branchId,
                    'rule_type' => 'automatic',
                    'trigger_type' => 'reorder_point',
                    'basis_type' => 'reorder_point',
                    'reorder_point' => $reorderPoint,
                    'reorder_quantity' => $reorderQty,
                    'lead_time_days' => 7,
                    'review_period_days' => 7,
                    'safety_stock' => $safetyStock,
                    'maximum_stock' => $maximumStock,
                    'priority' => 'medium',
                    'auto_generate_po' => false,
                    'is_active' => true,
                    'next_review_date' => now()->addDays(30),
                    'notes' => 'Auto-created from branch inventory thresholds.',
                ];

                if (!$existing) {
                    $createdRule = ReorderRule::create($payload);
                    $created[] = $createdRule->id;
                    continue;
                }

                if (!$overwrite) {
                    $skipped[] = $existing->id;
                    continue;
                }

                $existing->update($payload);
                $updated[] = $existing->id;
            }

            return [
                'created_ids' => $created,
                'updated_ids' => $updated,
                'skipped_ids' => $skipped,
                'created_count' => count($created),
                'updated_count' => count($updated),
                'skipped_count' => count($skipped),
            ];
        } catch (Exception $e) {
            Log::error('Failed to auto-create reorder rules from inventory', [
                'branch_id' => $branchId,
                'overwrite' => $overwrite,
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

    /**
     * Fill demand-related values for demand+lead-time basis if caller did not provide them.
     */
    private function hydrateDemandDefaults(array $data, ?array $context = null): array
    {
        $basisType = (string) ($data['basis_type'] ?? $context['basis_type'] ?? 'reorder_point');
        if ($basisType !== 'demand_lead_time') {
            return $data;
        }

        $branchId = (int) ($data['branch_id'] ?? $context['branch_id'] ?? 0);
        $productId = (int) ($data['product_id'] ?? $context['product_id'] ?? 0);

        $incomingAvg = isset($data['avg_daily_demand']) ? (float) $data['avg_daily_demand'] : null;
        if ($incomingAvg === null || $incomingAvg <= 0) {
            $computed = $this->computeAverageDailyDemand($branchId, $productId);
            if ($computed > 0) {
                $data['avg_daily_demand'] = $computed;
            }
        }

        // Safe fallback when there is still no historical demand.
        $finalAvg = (float) ($data['avg_daily_demand'] ?? 0);
        if ($finalAvg <= 0) {
            $reorderPoint = (float) ($data['reorder_point'] ?? $context['reorder_point'] ?? 0);
            $leadTime = (int) ($data['lead_time_days'] ?? $context['lead_time_days'] ?? 7);
            if ($reorderPoint > 0 && $leadTime > 0) {
                $data['avg_daily_demand'] = round(max(0.0001, $reorderPoint / $leadTime), 4);
            }
        }

        return $data;
    }

    /**
     * Estimate average daily demand from recent branch sales transactions.
     */
    private function computeAverageDailyDemand(int $branchId, int $productId, int $lookbackDays = 30): float
    {
        if ($branchId <= 0 || $productId <= 0 || $lookbackDays <= 0) {
            return 0.0;
        }

        $fromDate = now()->subDays($lookbackDays);

        $unitsSold = (float) InventoryTransaction::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('transaction_type', 'sale')
            ->where('transaction_date', '>=', $fromDate)
            ->where('quantity_change', '<', 0)
            ->sum('quantity_change');

        $unitsSold = abs($unitsSold);
        if ($unitsSold <= 0) {
            return 0.0;
        }

        return round($unitsSold / $lookbackDays, 4);
    }
}
