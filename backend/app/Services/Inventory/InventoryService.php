<?php

namespace App\Services\Inventory;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryConfiguration;
use App\Models\Inventory\StockAlert;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Get available stock for a product in a branch
     */
    public function getAvailableStock(int $branchId, int $productId, ?int $variationId = null): int
    {
        $query = BranchInventory::where('branch_id', $branchId)
            ->where('product_id', $productId);

        if ($variationId) {
            $query->where('variation_id', $variationId);
        }

        $inventory = $query->first();
        return $inventory?->quantity_available ?? 0;
    }

    /**
     * Get total on-hand quantity
     */
    public function getOnHandQuantity(int $branchId, int $productId, ?int $variationId = null): int
    {
        $query = BranchInventory::where('branch_id', $branchId)
            ->where('product_id', $productId);

        if ($variationId) {
            $query->where('variation_id', $variationId);
        }

        $inventory = $query->first();
        return $inventory?->quantity_on_hand ?? 0;
    }

    /**
     * Reserve stock for an order or transfer
     */
    public function reserveStock(int $storeId, int $branchId, int $productId, int $quantity, ?int $variationId = null): void
    {
        try {
            DB::beginTransaction();

            $inventory = BranchInventory::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new Exception("Inventory not found for product {$productId} in branch {$branchId}");
            }

            if ($inventory->quantity_available < $quantity) {
                throw new Exception("Insufficient stock. Available: {$inventory->quantity_available}, Requested: {$quantity}");
            }

            $inventory->update([
                'quantity_reserved' => $inventory->quantity_reserved + $quantity,
                'quantity_available' => $inventory->quantity_available - $quantity,
            ]);

            // Log transaction
            $this->createTransaction($storeId, $branchId, 'RESERVE', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'reason' => 'Stock reservation',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Release reserved stock back to available
     */
    public function releaseReservation(int $storeId, int $branchId, int $productId, int $quantity, ?int $variationId = null): void
    {
        try {
            DB::beginTransaction();

            $inventory = BranchInventory::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new Exception("Inventory not found");
            }

            $inventory->update([
                'quantity_reserved' => max(0, $inventory->quantity_reserved - $quantity),
                'quantity_available' => $inventory->quantity_available + $quantity,
            ]);

            $this->createTransaction($storeId, $branchId, 'RELEASE', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'reason' => 'Reservation release',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deduct stock (for sales)
     */
    public function deductStock(int $storeId, int $branchId, int $productId, int $quantity, string $reason = 'Sale', ?int $variationId = null): void
    {
        try {
            DB::beginTransaction();

            $inventory = BranchInventory::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new Exception("Inventory not found");
            }

            if ($inventory->quantity_available < $quantity) {
                throw new Exception("Insufficient stock for deduction");
            }

            $inventory->update([
                'quantity_on_hand' => $inventory->quantity_on_hand - $quantity,
                'quantity_available' => $inventory->quantity_available - $quantity,
            ]);

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, 'DEDUCT', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'reason' => $reason,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add stock (for receipts/returns)
     */
    public function addStock(int $storeId, int $branchId, int $productId, int $quantity, string $reason = 'Receipt', ?int $variationId = null): void
    {
        try {
            DB::beginTransaction();

            $inventory = BranchInventory::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                // Create new inventory entry if doesn't exist
                $inventory = BranchInventory::create([
                    'store_id' => $storeId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'quantity_on_hand' => $quantity,
                    'quantity_available' => $quantity,
                ]);
            } else {
                $inventory->update([
                    'quantity_on_hand' => $inventory->quantity_on_hand + $quantity,
                    'quantity_available' => $inventory->quantity_available + $quantity,
                ]);
            }

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, 'ADD', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'reason' => $reason,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Adjust stock for discrepancies (physical count)
     */
    public function adjustInventory(int $storeId, int $branchId, int $productId, int $adjustmentQuantity, string $reason = 'Adjustment', ?int $variationId = null): void
    {
        try {
            DB::beginTransaction();

            $inventory = BranchInventory::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new Exception("Inventory not found");
            }

            $newQuantity = $inventory->quantity_on_hand + $adjustmentQuantity;
            if ($newQuantity < 0) {
                throw new Exception("Adjustment would result in negative stock");
            }

            $inventory->update([
                'quantity_on_hand' => $newQuantity,
                'quantity_available' => $newQuantity - $inventory->quantity_reserved,
            ]);

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, 'ADJUST', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $adjustmentQuantity,
                'old_quantity' => $inventory->quantity_on_hand,
                'new_quantity' => $newQuantity,
                'reason' => $reason,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark stock as damaged
     */
    public function markDamaged(int $storeId, int $branchId, int $productId, int $quantity, string $reason = 'Damage', ?int $variationId = null): void
    {
        try {
            DB::beginTransaction();

            $inventory = BranchInventory::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new Exception("Inventory not found");
            }

            if ($inventory->quantity_available < $quantity) {
                throw new Exception("Cannot mark more as damaged than available");
            }

            $inventory->update([
                'quantity_damaged' => $inventory->quantity_damaged + $quantity,
                'quantity_available' => $inventory->quantity_available - $quantity,
            ]);

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, 'DAMAGE', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'reason' => $reason,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create inventory transaction for audit trail
     */
    public function createTransaction(int $storeId, int $branchId, string $type, array $details): InventoryTransaction
    {
        return InventoryTransaction::create([
            'store_id' => $storeId,
            'branch_id' => $branchId,
            'transaction_type' => $type,
            'created_by' => auth()->id(),
            'details' => $details,
        ]);
    }

    /**
     * Get inventory for branch with optional filters
     */
    public function getBranchInventory(int $storeId, int $branchId, array $filters = []): Collection
    {
        $query = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId);

        if (isset($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['warehouse_section'])) {
            $query->where('warehouse_section', $filters['warehouse_section']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
        }

        return $query->get();
    }

    /**
     * Get low stock items for branch
     */
    public function getLowStockItems(int $storeId, int $branchId): Collection
    {
        return BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('stock_status', 'low_stock')
            ->get();
    }

    /**
     * Get out of stock items for branch
     */
    public function getOutOfStockItems(int $storeId, int $branchId): Collection
    {
        return BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('stock_status', 'out_of_stock')
            ->get();
    }

    /**
     * Calculate total inventory value for branch
     */
    public function calculateBranchValue(int $storeId, int $branchId): float
    {
        return (float) BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->sum('total_value');
    }

    /**
     * Get inventory configuration for store
     */
    public function getConfiguration(int $storeId): InventoryConfiguration
    {
        return InventoryConfiguration::firstOrCreate(
            ['store_id' => $storeId],
            [
                'model_type' => 'centralized',
                'enable_transfer_approvals' => true,
                'enable_finance_approval' => true,
                'enable_auto_alerts' => true,
            ]
        );
    }
}
