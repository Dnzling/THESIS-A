<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StockAlert;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\ReorderRule;
use App\Models\Inventory\InventoryConfiguration;
use App\Models\Inventory\InventoryNotification;
use Illuminate\Database\Eloquent\Collection;

class AlertService
{
    /**
     * Generate alerts for a branch
     */
    public function generateAlerts(int $storeId, int $branchId): Collection
    {
        $alerts = collect();
        $config = InventoryConfiguration::where('store_id', $storeId)->first();

        if (!$config || !$config->enable_auto_alerts) {
            return $alerts;
        }

        // Get all inventory items for the branch
        $inventoryItems = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->get();

        foreach ($inventoryItems as $item) {
            // Check low stock
            if ($this->checkLowStock($item, $storeId, $branchId)) {
                $alert = $this->createAlert($storeId, $branchId, $item, 'low_stock', 'Low Stock Alert');
                $alerts->push($alert);
            }

            // Check out of stock
            if ($this->checkOutOfStock($item, $storeId, $branchId)) {
                $alert = $this->createAlert($storeId, $branchId, $item, 'out_of_stock', 'Out of Stock');
                $alerts->push($alert);
            }

            // Check overstock
            if ($this->checkOverstock($item, $storeId, $branchId)) {
                $alert = $this->createAlert($storeId, $branchId, $item, 'overstock', 'Overstock Alert');
                $alerts->push($alert);
            }
        }

        return $alerts;
    }

    /**
     * Check if stock is low
     */
    public function checkLowStock(BranchInventory $inventory, int $storeId, int $branchId): bool
    {
        if ($inventory->quantity_available <= 0) {
            return false; // Out of stock is handled separately
        }

        // Get reorder rule for this product
        $rule = $this->getApplicableRule($storeId, $branchId, $inventory->product_id);
        
        if (!$rule) {
            return false;
        }

        $isLow = $inventory->quantity_available <= $rule->reorder_point;

        // Only create if not already alerted
        if ($isLow && !$this->alertExists($inventory->id, 'low_stock')) {
            return true;
        }

        return false;
    }

    /**
     * Check if out of stock
     */
    public function checkOutOfStock(BranchInventory $inventory, int $storeId, int $branchId): bool
    {
        $isOut = $inventory->quantity_available <= 0;

        if ($isOut && !$this->alertExists($inventory->id, 'out_of_stock')) {
            return true;
        }

        return false;
    }

    /**
     * Check if stock exceeds maximum
     */
    public function checkOverstock(BranchInventory $inventory, int $storeId, int $branchId): bool
    {
        $rule = $this->getApplicableRule($storeId, $branchId, $inventory->product_id);

        if (!$rule || !$rule->maximum_stock) {
            return false;
        }

        $isOver = $inventory->quantity_on_hand > $rule->maximum_stock;

        if ($isOver && !$this->alertExists($inventory->id, 'overstock')) {
            return true;
        }

        return false;
    }

    /**
     * Create stock alert
     */
    protected function createAlert(
        int $storeId,
        int $branchId,
        BranchInventory $inventory,
        string $alertType,
        string $title
    ): StockAlert {
        $alert = StockAlert::create([
            'store_id' => $storeId,
            'branch_id' => $branchId,
            'inventory_id' => $inventory->id,
            'product_id' => $inventory->product_id,
            'variation_id' => $inventory->variation_id,
            'alert_type' => $alertType,
            'current_quantity' => $inventory->quantity_available,
            'threshold_value' => $this->getThresholdValue($inventory, $alertType, $storeId, $branchId),
            'is_acknowledged' => false,
            'is_resolved' => false,
        ]);

        // Create notification
        $this->createAlertNotification($storeId, $branchId, $alert, $title);

        return $alert;
    }

    /**
     * Get applicable reorder rule for product
     */
    protected function getApplicableRule(int $storeId, int $branchId, int $productId): ?ReorderRule
    {
        // Check product-specific rule for this branch
        $rule = ReorderRule::where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->active()
            ->first();

        if ($rule) {
            return $rule;
        }

        // Check category rule for this branch
        $product = \App\Models\ProductCatalog\Product::find($productId);
        if ($product && $product->category_id) {
            $rule = ReorderRule::where('store_id', $storeId)
                ->where('category_id', $product->category_id)
                ->where('branch_id', $branchId)
                ->active()
                ->first();

            if ($rule) {
                return $rule;
            }
        }

        // Check store-wide rules
        $rule = ReorderRule::where('store_id', $storeId)
            ->where('product_id', $productId)
            ->whereNull('branch_id')
            ->active()
            ->first();

        if ($rule) {
            return $rule;
        }

        return null;
    }

    /**
     * Get threshold value for alert
     */
    protected function getThresholdValue(BranchInventory $inventory, string $alertType, int $storeId, int $branchId): int
    {
        $rule = $this->getApplicableRule($storeId, $branchId, $inventory->product_id);

        return match ($alertType) {
            'low_stock' => $rule?->reorder_point ?? 0,
            'overstock' => $rule?->maximum_stock ?? 0,
            'out_of_stock' => 0,
            default => 0,
        };
    }

    /**
     * Check if alert already exists
     */
    protected function alertExists(int $inventoryId, string $alertType): bool
    {
        return StockAlert::where('inventory_id', $inventoryId)
            ->where('alert_type', $alertType)
            ->where('is_resolved', false)
            ->exists();
    }

    /**
     * Create notification for alert
     */
    protected function createAlertNotification(int $storeId, int $branchId, StockAlert $alert, string $title): InventoryNotification
    {
        return InventoryNotification::create([
            'store_id' => $storeId,
            'branch_id' => $branchId,
            'notification_type' => $alert->alert_type,
            'entity_type' => 'stock_alert',
            'entity_id' => $alert->id,
            'title' => $title,
            'message' => "Product {$alert->product_id}: Current qty {$alert->current_quantity}, Alert threshold {$alert->threshold_value}",
            'action_required' => true,
        ]);
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert(int $alertId, ?string $notes = null): StockAlert
    {
        $alert = StockAlert::findOrFail($alertId);

        $alert->update([
            'is_acknowledged' => true,
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
            'acknowledgement_notes' => $notes,
        ]);

        return $alert;
    }

    /**
     * Resolve alert
     */
    public function resolveAlert(int $alertId, ?string $notes = null): StockAlert
    {
        $alert = StockAlert::findOrFail($alertId);

        $alert->update([
            'is_resolved' => true,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        // Mark associated notification as read
        InventoryNotification::where('entity_type', 'stock_alert')
            ->where('entity_id', $alertId)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $alert;
    }

    /**
     * Get active alerts for branch
     */
    public function getActiveBranchAlerts(int $storeId, int $branchId): Collection
    {
        return StockAlert::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('is_resolved', false)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get active alerts by type
     */
    public function getAlertsByType(int $storeId, int $branchId, string $type): Collection
    {
        return StockAlert::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('alert_type', $type)
            ->where('is_resolved', false)
            ->get();
    }

    /**
     * Get unacknowledged alerts
     */
    public function getUnacknowledgedAlerts(int $storeId, int $branchId): Collection
    {
        return StockAlert::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('is_acknowledged', false)
            ->where('is_resolved', false)
            ->get();
    }

    /**
     * Clear resolved alerts older than N days
     */
    public function clearOldAlerts(int $days = 30): int
    {
        return StockAlert::where('is_resolved', true)
            ->where('resolved_at', '<', now()->subDays($days))
            ->delete();
    }
}
