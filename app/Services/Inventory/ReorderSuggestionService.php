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
        $query = ReorderSuggestion::with(['reorderRule', 'product', 'variation', 'branch', 'approver', 'implementer']);

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
            $query->where(function ($searchQuery) use ($filters): void {
                $searchQuery->whereHas('product', function ($productQuery) use ($filters): void {
                    $productQuery->where('product_name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
                })->orWhereHas('variation', function ($variationQuery) use ($filters): void {
                    $variationQuery->where('variation_name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('variation_sku', 'like', '%' . $filters['search'] . '%');
                });
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
                $unitCost = !empty($data['variation_id'])
                    ? (float) (DB::table('product_variations')->where('id', $data['variation_id'])->value('cost_price') ?? 0)
                    : (float) (Product::find($data['product_id'])?->cost_price ?? 0);
                if ($unitCost > 0) {
                    $data['estimated_cost'] = $data['suggested_quantity'] * $unitCost;
                }
            }

            $suggestion = ReorderSuggestion::create($data);

            DB::commit();
            return $suggestion->load(['reorderRule', 'product', 'variation', 'branch']);

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
                $unitCost = (float) ($suggestion->variation?->cost_price ?? $suggestion->product?->cost_price ?? 0);
                if ($unitCost > 0) {
                    $data['estimated_cost'] = $data['suggested_quantity'] * $unitCost;
                }
            }

            $suggestion->update($data);

            DB::commit();
            return $suggestion->fresh(['reorderRule', 'product', 'variation', 'branch', 'approver', 'implementer']);

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
    public function generateSuggestions(int $storeId, ?int $branchId = null): array
    {
        $this->salesCache = [];
        $rules = ReorderRule::query()
            ->with(['product', 'branch'])
            ->where('is_active', true)
            ->whereHas('branch', fn ($query) => $query->where('store_id', $storeId))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->get();

        $inventoryItems = BranchInventory::query()
            ->with(['product', 'variation', 'branch'])
            ->where('store_id', $storeId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->get();
        $this->primeSalesCache($storeId, $branchId);

        $rulesByProductBranch = [];
        foreach ($rules as $rule) {
            $rulesByProductBranch["{$rule->branch_id}:{$rule->product_id}"] = $rule;
        }

        $suggestions = [];
        $errors = [];
        foreach ($inventoryItems as $item) {
            if (!$item->product || !$item->branch) {
                continue;
            }

            $rule = $rulesByProductBranch["{$item->branch_id}:{$item->product_id}"] ?? null;
            try {
                $suggestion = $this->generateSuggestionForInventoryItem($item, $rule);
                if ($suggestion) {
                    $suggestions[] = $suggestion;
                }
            } catch (Exception $e) {
                $errors[] = [
                    'inventory_item_id' => $item->id,
                    'product_name' => $item->product->product_name ?? $item->product->name,
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

    private array $salesCache = [];

    private function inventoryKey(BranchInventory $item): string
    {
        return implode(':', [(int) $item->branch_id, (int) $item->product_id, (int) ($item->variation_id ?? 0)]);
    }

    private function generateSuggestionForInventoryItem(BranchInventory $item, ?ReorderRule $rule): ?ReorderSuggestion
    {
        $currentStock = (float) ($item->quantity_available ?? 0);
        $sales = $this->getRecentSales($item);
        $configuredPoint = (float) ($rule?->reorder_point ?? $item->reorder_point ?? 0);
        $safetyStock = (float) ($rule?->safety_stock ?? $item->safety_stock ?? 0);
        $leadTime = (int) ($rule?->lead_time_days ?? 0);
        $reviewDays = (int) ($rule?->review_period_days ?? 7);
        $configuredAverage = (float) ($rule?->avg_daily_demand ?? 0);
        $averageDemand = $sales['units'] > 0 ? $sales['units'] / 30 : $configuredAverage;
        $demandMode = $rule && $rule->basis_type === 'demand_lead_time' && $leadTime > 0 && $averageDemand > 0;

        if ($demandMode) {
            $trigger = ($averageDemand * $leadTime) + $safetyStock;
            $target = ($averageDemand * ($leadTime + max(0, $reviewDays))) + $safetyStock;
            $source = $sales['units'] > 0 ? 'sales_30_day' : 'configured_average_demand';
        } else {
            $triggerType = $rule?->trigger_type ?? 'reorder_point';
            $trigger = match ($triggerType) {
                'safety_stock' => $safetyStock,
                'seasonal' => $configuredPoint * (float) ($rule?->getSeasonalAdjustment() ?? 1),
                default => $configuredPoint,
            };
            $configuredQty = (float) ($rule?->reorder_quantity ?? $item->reorder_quantity ?? 0);
            $configuredMax = (float) ($rule?->maximum_stock ?? $item->maximum_stock ?? 0);
            $target = $configuredMax > 0
                ? $configuredMax
                : ($configuredQty > 0 ? $currentStock + $configuredQty : $configuredPoint * 2);
            $source = $rule ? 'configured_reorder_rule' : 'branch_inventory_reorder_point';
        }

        $pendingQuery = ReorderSuggestion::query()
            ->where('product_id', $item->product_id)
            ->where('branch_id', $item->branch_id)
            ->where('status', 'pending')
            ->valid();
        $item->variation_id
            ? $pendingQuery->where('variation_id', $item->variation_id)
            : $pendingQuery->whereNull('variation_id');
        $existing = $pendingQuery->first();

        if ($currentStock > $trigger || $trigger <= 0 || $target <= $currentStock) {
            if ($existing && ($currentStock > $trigger || $trigger <= 0 || $target <= $currentStock)) {
                $existing->update([
                    'status' => 'cancelled',
                    'reason' => 'Available stock recovered above the reorder target; this suggestion is no longer needed.',
                ]);
            }
            return null;
        }

        $quantity = max(1, (int) ceil($target - $currentStock));
        $priority = $this->priorityForTrigger($currentStock, $trigger);
        $metadata = [
            'source' => $source,
            'inventory_item_id' => (int) $item->id,
            'variation_id' => $item->variation_id ? (int) $item->variation_id : null,
            'calculation' => [
                'sales_window_days' => 30,
                'units_sold' => round($sales['units'], 2),
                'average_daily_demand' => round($averageDemand, 4),
                'lead_time_days' => $leadTime,
                'review_period_days' => $reviewDays,
                'safety_stock' => $safetyStock,
                'reorder_trigger' => round($trigger, 2),
                'target_stock' => round($target, 2),
            ],
        ];
        $reason = $demandMode
            ? sprintf('Available stock (%s) is at or below the demand-based reorder trigger (%s). The target covers lead time, review period, and safety stock.', $currentStock, round($trigger, 2))
            : sprintf('Available stock (%s) is at or below the configured reorder point (%s). Replenishment targets %s units.', $currentStock, round($trigger, 2), round($target, 2));

        $values = [
            'reorder_rule_id' => $rule?->id,
            'product_id' => $item->product_id,
            'variation_id' => $item->variation_id,
            'branch_id' => $item->branch_id,
            'suggestion_type' => $rule?->trigger_type === 'seasonal' ? 'seasonal' : 'automatic',
            'current_stock' => $currentStock,
            'suggested_quantity' => $quantity,
            'priority' => $priority,
            'reason' => $reason,
            'metadata' => $metadata,
            'valid_until' => now()->addDays(30),
        ];

        if ($existing) {
            if ((float) $existing->current_stock === $currentStock
                && (float) $existing->suggested_quantity === (float) $quantity
                && ($existing->metadata['calculation'] ?? null) === $metadata['calculation']) {
                return null;
            }
            $existing->update($values);
            return $existing->fresh(['reorderRule', 'product', 'variation', 'branch']);
        }

        return $this->createSuggestion($values);
    }

    private function getRecentSales(BranchInventory $item): array
    {
        $key = $this->inventoryKey($item);
        return $this->salesCache[$key] ?? ['units' => 0.0];
    }

    private function primeSalesCache(int $storeId, ?int $branchId): void
    {
        $this->salesCache = [];
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();

        $posSales = DB::table('sales_pos_order_items as item')
            ->join('sales_pos_orders as orders', 'orders.id', '=', 'item.order_id')
            ->where('orders.store_id', $storeId)
            ->when($branchId, fn ($query) => $query->where('orders.branch_id', $branchId))
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->selectRaw('orders.branch_id as branch_id, item.product_id, item.variation_id, SUM(item.quantity) as units')
            ->groupBy('orders.branch_id', 'item.product_id', 'item.variation_id')
            ->get();
        foreach ($posSales as $sale) {
            $key = implode(':', [(int) $sale->branch_id, (int) $sale->product_id, (int) ($sale->variation_id ?? 0)]);
            $this->salesCache[$key] = ['units' => (float) ($sale->units ?? 0)];
        }

        $ecommerceSales = DB::table('ecommerce_order_items as item')
            ->join('ecommerce_orders as orders', 'orders.id', '=', 'item.order_id')
            ->join('branch_inventory as inventory', 'inventory.id', '=', 'item.branch_inventory_id')
            ->where('orders.store_id', $storeId)
            ->when($branchId, fn ($query) => $query->where('orders.assigned_branch_id', $branchId))
            ->whereIn('orders.status', ['delivered', 'completed'])
            ->where('orders.payment_status', '!=', 'refunded')
            ->whereRaw('COALESCE(orders.placed_at, orders.created_at) BETWEEN ? AND ?', [$start, $end])
            ->where('inventory.store_id', $storeId)
            ->whereNull('inventory.deleted_at')
            ->selectRaw('inventory.branch_id as branch_id, inventory.product_id, inventory.variation_id, SUM(item.quantity) as units')
            ->groupBy('inventory.branch_id', 'inventory.product_id', 'inventory.variation_id')
            ->get();
        foreach ($ecommerceSales as $sale) {
            $key = implode(':', [(int) $sale->branch_id, (int) $sale->product_id, (int) ($sale->variation_id ?? 0)]);
            $this->salesCache[$key] = ['units' => (float) ($this->salesCache[$key]['units'] ?? 0) + (float) ($sale->units ?? 0)];
        }

        $returns = DB::table('ecommerce_order_returns as returns')
            ->join('ecommerce_order_items as item', 'item.id', '=', 'returns.order_item_id')
            ->join('ecommerce_orders as orders', 'orders.id', '=', 'returns.order_id')
            ->join('branch_inventory as inventory', 'inventory.id', '=', 'item.branch_inventory_id')
            ->where('orders.store_id', $storeId)
            ->when($branchId, fn ($query) => $query->where('orders.assigned_branch_id', $branchId))
            ->where('inventory.store_id', $storeId)
            ->whereNull('inventory.deleted_at')
            ->whereIn('returns.status', ['received', 'return_received', 'refund_pending', 'refunded', 'replaced'])
            ->whereRaw('COALESCE(orders.placed_at, orders.created_at) BETWEEN ? AND ?', [$start, $end])
            ->selectRaw('inventory.branch_id as branch_id, inventory.product_id, inventory.variation_id, SUM(returns.requested_quantity) as units')
            ->groupBy('inventory.branch_id', 'inventory.product_id', 'inventory.variation_id')
            ->get();
        foreach ($returns as $return) {
            $key = implode(':', [(int) $return->branch_id, (int) $return->product_id, (int) ($return->variation_id ?? 0)]);
            $this->salesCache[$key] = [
                'units' => max(0, (float) ($this->salesCache[$key]['units'] ?? 0) - (float) ($return->units ?? 0)),
            ];
        }
    }

    private function priorityForTrigger(float $currentStock, float $trigger): string
    {
        if ($currentStock <= 0 || ($trigger > 0 && $currentStock <= $trigger * 0.25)) return 'critical';
        if ($trigger > 0 && $currentStock <= $trigger * 0.5) return 'high';
        if ($trigger > 0 && $currentStock <= $trigger * 0.75) return 'medium';
        return 'low';
    }

    /**
     * Get suggestion statistics
     */
    public function getSuggestionStats(int $storeId, ?int $branchId = null): array
    {
        $query = ReorderSuggestion::query()
            ->whereHas('branch', fn ($branchQuery) => $branchQuery->where('store_id', $storeId));

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

        $suggestion->loadMissing(['product', 'variation', 'branch']);

        $storeId = $suggestion->branch?->store_id;
        if (!$storeId) {
            return null;
        }

        $product = $suggestion->product;
        if (!$product) {
            return null;
        }

        $unitCost = (float) ($suggestion->variation?->cost_price ?? $product->cost_price ?? 0);
        $estimatedAmount = ($suggestion->suggested_quantity ?? 0) * $unitCost;

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

        return DB::transaction(function () use ($suggestion, $userId, $storeId, $estimatedAmount, $procurementRoute, $requiredApprovals, $priorityMap, $prNumber, $product, $unitCost) {
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
                'variation_id' => $suggestion->variation_id,
                'quantity_requested' => (int) ($suggestion->suggested_quantity ?? 1),
                'estimated_unit_cost' => $unitCost ?: null,
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
