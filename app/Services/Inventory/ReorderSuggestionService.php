<?php
// backend/app/Services/Inventory/ReorderSuggestionService.php

namespace App\Services\Inventory;

use App\Models\Inventory\ReorderSuggestion;
use App\Models\Inventory\ReorderRule;
use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Product;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;
use App\Models\Procurement\Config\ProcurementSettings;
use App\Models\Store\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ReorderSuggestionService
{
    /**
     * Get paginated list of reorder suggestions with filters
     */
    public function getSuggestions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ReorderSuggestion::with(['reorderRule', 'product', 'branch', 'approver', 'implementer']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['type'])) {
            $query->where('suggestion_type', $filters['type']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['store_id'])) {
            $query->whereHas('branch', function ($q) use ($filters) {
                $q->where('store_id', $filters['store_id']);
            });
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['expired'])) {
            if ($filters['expired']) {
                $query->expired();
            } else {
                $query->valid();
            }
        }

        // Search by product name
        if (isset($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('product_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Order by priority and creation date
        $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') DESC")
              ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create a new reorder suggestion
     */
    public function createSuggestion(array $data): ReorderSuggestion
    {
        DB::beginTransaction();
        try {
            // Set default status if not provided
            $data['status'] = $data['status'] ?? 'pending';
            $data['suggested_at'] = $data['suggested_at'] ?? now();

            // Calculate estimated cost if not provided and we have product cost
            if (!isset($data['estimated_cost']) && isset($data['product_id'])) {
                $product = Product::find($data['product_id']);
                if ($product && $product->cost_price) {
                    $data['estimated_cost'] = $data['suggested_quantity'] * $product->cost_price;
                }
            }

            $suggestion = ReorderSuggestion::create($data);

            DB::commit();
            return $suggestion->load(['reorderRule', 'product', 'branch']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing reorder suggestion
     */
    public function updateSuggestion(ReorderSuggestion $suggestion, array $data): ReorderSuggestion
    {
        DB::beginTransaction();
        try {
            // Recalculate estimated cost if quantity changed
            if (isset($data['suggested_quantity']) &&
                (!isset($data['estimated_cost']) || $data['estimated_cost'] === null)) {
                $product = $suggestion->product;
                if ($product && $product->cost_price) {
                    $data['estimated_cost'] = $data['suggested_quantity'] * $product->cost_price;
                }
            }

            $suggestion->update($data);

            DB::commit();
            return $suggestion->fresh(['reorderRule', 'product', 'branch', 'approver', 'implementer']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve a reorder suggestion
     */
    public function approveSuggestion(ReorderSuggestion $suggestion, ?int $approvedBy = null, ?string $notes = null): bool
    {
        if (!$suggestion->approve($approvedBy, $notes)) {
            return false;
        }

        // Log the approval action
        // You might want to add activity logging here
        try {
            if ($approvedBy) {
                $this->createPurchaseRequisitionFromSuggestion($suggestion, $approvedBy);
            }
        } catch (Exception $e) {
            Log::error('Failed to auto-create PR from suggestion', [
                'suggestion_id' => $suggestion->id,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Reject a reorder suggestion
     */
    public function rejectSuggestion(ReorderSuggestion $suggestion, ?string $notes = null): bool
    {
        if (!$suggestion->reject($notes)) {
            return false;
        }

        // Log the rejection action
        // You might want to add activity logging here

        return true;
    }

    /**
     * Implement a reorder suggestion
     */
    public function implementSuggestion(ReorderSuggestion $suggestion, ?int $implementedBy = null, ?string $notes = null): bool
    {
        if (!$suggestion->implement($implementedBy, $notes)) {
            return false;
        }

        // Log the implementation action
        // You might want to add activity logging here

        return true;
    }

    /**
     * Cancel a reorder suggestion
     */
    public function cancelSuggestion(ReorderSuggestion $suggestion, ?string $notes = null): bool
    {
        if (!$suggestion->cancel($notes)) {
            return false;
        }

        // Log the cancellation action
        // You might want to add activity logging here

        return true;
    }

    /**
     * Generate reorder suggestions based on reorder rules
     */
    public function generateSuggestions(?int $branchId = null): array
    {
        $rules = ReorderRule::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true)
            ->get();

        $suggestions = [];
        $errors = [];
        $generatedProductKeys = [];

        foreach ($rules as $rule) {
            try {
                $suggestion = $this->generateSuggestionFromRule($rule);
                if ($suggestion) {
                    $suggestions[] = $suggestion;
                    $generatedProductKeys["{$suggestion->branch_id}:{$suggestion->product_id}"] = true;
                }
            } catch (Exception $e) {
                $errors[] = [
                    'rule_id' => $rule->id,
                    'product_name' => $rule->product->product_name ?? $rule->product->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Fallback: create suggestions directly from low-stock branch inventory items
        // even when no reorder rule exists yet.
        $lowStockItems = BranchInventory::with(['product', 'branch'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where(function ($query) {
                $query->whereColumn('quantity_available', '<=', 'reorder_point')
                    ->orWhere('quantity_available', '<=', 0);
            })
            ->where(function ($query) {
                $query->where('reorder_point', '>', 0)
                    ->orWhere('quantity_available', '<=', 0);
            })
            ->get();

        foreach ($lowStockItems as $item) {
            try {
                $productId = (int) ($item->product_id ?? 0);
                $rowBranchId = (int) ($item->branch_id ?? 0);
                if ($productId <= 0 || $rowBranchId <= 0 || !$item->product) {
                    continue;
                }

                $itemKey = "{$rowBranchId}:{$productId}";
                if (isset($generatedProductKeys[$itemKey])) {
                    continue;
                }

                $existingPending = ReorderSuggestion::query()
                    ->where('product_id', $productId)
                    ->where('branch_id', $rowBranchId)
                    ->where('status', 'pending')
                    ->valid()
                    ->first();

                if ($existingPending) {
                    $generatedProductKeys[$itemKey] = true;
                    continue;
                }

                $currentStock = (float) ($item->quantity_available ?? $item->quantity_on_hand ?? 0);
                $reorderPoint = (float) ($item->reorder_point ?? 0);
                $targetStock = (float) ($item->maximum_stock ?? 0);
                if ($targetStock <= 0) {
                    $targetStock = $reorderPoint > 0 ? ($reorderPoint * 2) : 10;
                }
                $suggestedQty = max(1, (int) ceil($targetStock - $currentStock));

                $priority = $this->determinePriorityFromInventory($currentStock, $reorderPoint);
                $reason = "Current available stock ({$currentStock}) is below reorder point ({$reorderPoint}).";

                $fallbackSuggestion = $this->createSuggestion([
                    'reorder_rule_id' => null,
                    'product_id' => $productId,
                    'branch_id' => $rowBranchId,
                    'suggestion_type' => 'automatic',
                    'current_stock' => $currentStock,
                    'suggested_quantity' => $suggestedQty,
                    'priority' => $priority,
                    'reason' => $reason,
                    'valid_until' => now()->addDays(30),
                    'metadata' => [
                        'source' => 'branch_inventory_fallback',
                        'inventory_item_id' => $item->id,
                    ],
                ]);

                $suggestions[] = $fallbackSuggestion;
                $generatedProductKeys[$itemKey] = true;
            } catch (Exception $e) {
                $errors[] = [
                    'inventory_item_id' => $item->id ?? null,
                    'product_name' => $item->product->product_name ?? $item->product->name ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'suggestions' => $suggestions,
            'errors' => $errors,
            'total_generated' => count($suggestions),
            'total_errors' => count($errors),
        ];
    }

    /**
     * Generate a single suggestion from a reorder rule
     */
    private function generateSuggestionFromRule(ReorderRule $rule): ?ReorderSuggestion
    {
        $product = $rule->product;
        $branch = $rule->branch;

        // Get current stock level (you might need to implement this based on your stock tracking)
        $currentStock = $this->getCurrentStockLevel($product->id, $branch->id);

        // Check if reorder is needed
        if (!$this->shouldReorder($rule, $currentStock)) {
            return null;
        }

        // Calculate suggested quantity
        $suggestedQuantity = $this->calculateSuggestedQuantity($rule, $currentStock);

        // Determine priority
        $priority = $this->determinePriority($rule, $currentStock);

        // Check if suggestion already exists and is still valid
        $existingSuggestion = ReorderSuggestion::where('reorder_rule_id', $rule->id)
                                             ->where('status', 'pending')
                                             ->valid()
                                             ->first();

        if ($existingSuggestion) {
            // Update existing suggestion if stock levels changed significantly
            if (abs($existingSuggestion->current_stock - $currentStock) > $rule->reorder_point * 0.1) {
                $existingSuggestion->update([
                    'current_stock' => $currentStock,
                    'suggested_quantity' => $suggestedQuantity,
                    'priority' => $priority,
                    'metadata' => array_merge($existingSuggestion->metadata ?? [], [
                        'updated_at' => now()->toISOString(),
                        'previous_stock' => $existingSuggestion->current_stock,
                    ]),
                ]);
                return $existingSuggestion;
            }
            return null; // No significant change, don't create duplicate
        }

        // Create new suggestion
        return $this->createSuggestion([
            'reorder_rule_id' => $rule->id,
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'suggestion_type' => 'automatic',
            'current_stock' => $currentStock,
            'suggested_quantity' => $suggestedQuantity,
            'priority' => $priority,
            'reason' => $this->generateReason($rule, $currentStock),
            'valid_until' => now()->addDays(30), // Valid for 30 days
        ]);
    }

    /**
     * Get current stock level for a product in a branch
     */
    private function getCurrentStockLevel(int $productId, int $branchId): float
    {
        return (float) BranchInventory::query()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->sum('quantity_available');
    }

    /**
     * Check if reorder is needed based on rule and current stock
     */
    private function shouldReorder(ReorderRule $rule, float $currentStock): bool
    {
        if (($rule->basis_type ?? 'reorder_point') === 'demand_lead_time') {
            return $currentStock <= $rule->getDemandLeadTimeTrigger();
        }

        return $currentStock <= $rule->reorder_point;
    }

    /**
     * Calculate suggested reorder quantity
     */
    private function calculateSuggestedQuantity(ReorderRule $rule, float $currentStock): float
    {
        if (($rule->basis_type ?? 'reorder_point') === 'demand_lead_time') {
            $targetStock = (float) $rule->getDemandLeadTimeTargetStock();
            return max(1, ceil($targetStock - $currentStock));
        }

        $configuredQuantity = (float) ($rule->getReorderQuantity() ?? 0);
        if ($configuredQuantity > 0) {
            return $configuredQuantity;
        }

        $targetStock = (float) ($rule->maximum_stock ?? 0);
        if ($targetStock <= 0) {
            $targetStock = (float) max(1, ($rule->reorder_point ?? 0) * 2);
        }

        return max(1, ceil($targetStock - $currentStock));
    }

    /**
     * Determine priority based on stock level and rule
     */
    private function determinePriority(ReorderRule $rule, float $currentStock): string
    {
        if ((float) $rule->reorder_point <= 0) {
            return $currentStock <= 0 ? 'critical' : 'medium';
        }

        $stockRatio = $currentStock / (float) $rule->reorder_point;

        if ($stockRatio <= 0.25) {
            return 'critical';
        } elseif ($stockRatio <= 0.5) {
            return 'high';
        } elseif ($stockRatio <= 0.75) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Generate reason text for the suggestion
     */
    private function generateReason(ReorderRule $rule, float $currentStock): string
    {
        if (($rule->basis_type ?? 'reorder_point') === 'demand_lead_time') {
            $trigger = round((float) $rule->getDemandLeadTimeTrigger(), 2);
            $target = round((float) $rule->getDemandLeadTimeTargetStock(), 2);
            return "Current stock ({$currentStock}) is below demand+lead-time trigger ({$trigger}). " .
                "Suggested quantity targets approximately {$target} units.";
        }

        $target = $rule->maximum_stock ?? ($rule->reorder_point * 2);
        return "Current stock ({$currentStock}) is below reorder point ({$rule->reorder_point}). " .
               "Suggested quantity will bring stock to optimal level ({$target}).";
    }

    private function determinePriorityFromInventory(float $currentStock, float $reorderPoint): string
    {
        if ($currentStock <= 0) {
            return 'critical';
        }

        if ($reorderPoint <= 0) {
            return 'medium';
        }

        $stockRatio = $currentStock / $reorderPoint;
        if ($stockRatio <= 0.25) {
            return 'critical';
        }
        if ($stockRatio <= 0.5) {
            return 'high';
        }
        if ($stockRatio <= 0.75) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get suggestion statistics
     */
    public function getSuggestionStats(?int $branchId = null): array
    {
        $query = ReorderSuggestion::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_suggestions,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "implemented" THEN 1 ELSE 0 END) as implemented_count,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN priority = "critical" AND status = "pending" THEN 1 ELSE 0 END) as critical_pending,
            SUM(estimated_cost) as total_estimated_cost,
            AVG(suggested_quantity) as avg_suggested_quantity
        ')->first();

        return [
            'total_suggestions' => (int) $stats->total_suggestions,
            'pending_count' => (int) $stats->pending_count,
            'approved_count' => (int) $stats->approved_count,
            'implemented_count' => (int) $stats->implemented_count,
            'rejected_count' => (int) $stats->rejected_count,
            'critical_pending' => (int) $stats->critical_pending,
            'total_estimated_cost' => (float) $stats->total_estimated_cost,
            'avg_suggested_quantity' => round((float) $stats->avg_suggested_quantity, 2),
        ];
    }

    /**
     * Bulk approve suggestions
     */
    public function bulkApprove(array $suggestionIds, ?int $approvedBy = null, ?string $notes = null): array
    {
        $suggestions = ReorderSuggestion::whereIn('id', $suggestionIds)->get();

        $approved = [];
        $failed = [];

        foreach ($suggestions as $suggestion) {
            if ($this->approveSuggestion($suggestion, $approvedBy, $notes)) {
                $approved[] = $suggestion->id;
            } else {
                $failed[] = $suggestion->id;
            }
        }

        return [
            'approved' => $approved,
            'failed' => $failed,
            'total_approved' => count($approved),
            'total_failed' => count($failed),
        ];
    }

    /**
     * Auto-create Purchase Requisition from an approved suggestion.
     */
    public function createPurchaseRequisitionFromSuggestion(ReorderSuggestion $suggestion, int $userId): ?PurchaseRequisition
    {
        $existingId = $suggestion->getMetadataValue('purchase_requisition_id');
        if ($existingId) {
            return PurchaseRequisition::find($existingId);
        }

        $suggestion->loadMissing(['product', 'branch']);

        $storeId = $suggestion->branch?->store_id;
        if (!$storeId) {
            return null;
        }

        $product = $suggestion->product;
        if (!$product) {
            return null;
        }

        $estimatedAmount = ($suggestion->suggested_quantity ?? 0) * (float) ($product->cost_price ?? 0);

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
        if ($estimatedAmount >= 100000) {
            $requiredApprovals[] = 'branch_manager';
        }
        if ($estimatedAmount >= 500000) {
            $requiredApprovals[] = 'finance_manager';
        }

        $priorityMap = [
            'low' => 2,
            'medium' => 3,
            'high' => 4,
            'critical' => 5,
        ];

        $prNumber = 'PR-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

        return DB::transaction(function () use ($suggestion, $userId, $storeId, $estimatedAmount, $procurementRoute, $requiredApprovals, $priorityMap, $prNumber, $product) {
            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'store_id' => $storeId,
                'branch_id' => $suggestion->branch_id,
                'requisition_type' => 'regular',
                'status' => 'draft',
                'estimated_amount' => $estimatedAmount,
                'procurement_route' => $procurementRoute,
                'required_approvals' => $requiredApprovals,
                'reason' => "Auto-created from reorder suggestion #{$suggestion->id}",
                'priority' => $priorityMap[$suggestion->priority] ?? 3,
                'requested_by' => $userId,
            ]);

            PurchaseRequisitionItem::create([
                'requisition_id' => $pr->id,
                'product_id' => $suggestion->product_id,
                'variation_id' => null,
                'quantity_requested' => (int) ($suggestion->suggested_quantity ?? 1),
                'estimated_unit_cost' => $product->cost_price ?? null,
                'specifications' => null,
            ]);

            $suggestion->setMetadataValue('purchase_requisition_id', $pr->id);

            return $pr;
        });
    }

    /**
     * Bulk reject suggestions
     */
    public function bulkReject(array $suggestionIds, ?string $notes = null): array
    {
        $suggestions = ReorderSuggestion::whereIn('id', $suggestionIds)->get();

        $rejected = [];
        $failed = [];

        foreach ($suggestions as $suggestion) {
            if ($this->rejectSuggestion($suggestion, $notes)) {
                $rejected[] = $suggestion->id;
            } else {
                $failed[] = $suggestion->id;
            }
        }

        return [
            'rejected' => $rejected,
            'failed' => $failed,
            'total_rejected' => count($rejected),
            'total_failed' => count($failed),
        ];
    }
}
