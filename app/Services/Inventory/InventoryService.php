<?php

namespace App\Services\Inventory;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryConfiguration;
use App\Models\Inventory\StockAlert;
use App\Models\ProductCatalog\Product;
use App\Support\EmployeeContext;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

            $quantityBefore = (int) $inventory->quantity_on_hand;
            $inventory->suppressAutomaticTransactionLog();
            $inventory->update([
                'quantity_on_hand' => $quantityBefore - $quantity,
                'quantity_available' => $inventory->quantity_available - $quantity,
            ]);

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, 'sale', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity_before' => $quantityBefore,
                'quantity_change' => -$quantity,
                'quantity_after' => (int) $inventory->quantity_on_hand,
                'notes' => $reason,
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
    public function addStock(
        int $storeId,
        int $branchId,
        int $productId,
        int $quantity,
        string $reason = 'Receipt',
        ?int $variationId = null,
        string $transactionType = 'purchase',
        array $transactionDetails = []
    ): void
    {
        try {
            DB::beginTransaction();

            $inventory = BranchInventory::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                ->lockForUpdate()
                ->first();

            $quantityBefore = (int) ($inventory?->quantity_on_hand ?? 0);
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
                $inventory->suppressAutomaticTransactionLog();
                $inventory->update([
                    'quantity_on_hand' => $inventory->quantity_on_hand + $quantity,
                    'quantity_available' => $inventory->quantity_available + $quantity,
                ]);
            }

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, $transactionType, array_merge([
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantity,
                'quantity_after' => (int) $inventory->quantity_on_hand,
                'notes' => $reason,
            ], $transactionDetails));

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

            $quantityBefore = (int) $inventory->quantity_on_hand;
            $newQuantity = $quantityBefore + $adjustmentQuantity;
            if ($newQuantity < 0) {
                throw new Exception("Adjustment would result in negative stock");
            }

            $inventory->suppressAutomaticTransactionLog();
            $inventory->update([
                'quantity_on_hand' => $newQuantity,
                'quantity_available' => $newQuantity - $inventory->quantity_reserved,
            ]);

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, 'adjustment', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $adjustmentQuantity,
                'quantity_after' => $newQuantity,
                'notes' => $reason,
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

            $quantityBefore = (int) $inventory->quantity_on_hand;
            $inventory->suppressAutomaticTransactionLog();
            $inventory->update([
                'quantity_damaged' => $inventory->quantity_damaged + $quantity,
                'quantity_available' => $inventory->quantity_available - $quantity,
            ]);

            $inventory->updateStockStatus();

            $this->createTransaction($storeId, $branchId, 'damage', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity_before' => $quantityBefore,
                'quantity_change' => -$quantity,
                'quantity_after' => (int) $inventory->quantity_on_hand,
                'notes' => $reason,
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
        $productId = (int) ($details['product_id'] ?? 0);
        $product = Product::query()->findOrFail($productId);
        $quantityChange = (int) ($details['quantity_change'] ?? 0);
        $quantityBefore = (int) ($details['quantity_before'] ?? 0);
        $allowedTypes = [
            'purchase', 'sale', 'return_to_supplier', 'customer_return',
            'transfer_out', 'transfer_in', 'adjustment', 'damage',
            'expired', 'lost', 'assembly', 'sample', 'writeoff',
        ];
        $type = strtolower($type);
        $type = match ($type) {
            'deduct' => 'sale',
            'add' => 'purchase',
            'adjust' => 'adjustment',
            'transfer_loss' => 'lost',
            default => $type,
        };

        if (!in_array($type, $allowedTypes, true)) {
            throw new Exception("Unsupported inventory transaction type: {$type}");
        }

        $employeeId = EmployeeContext::currentEmployeeId();
        if (!$employeeId) {
            throw new Exception('An employee account is required to record an inventory transaction.');
        }

        return InventoryTransaction::create([
            'transaction_number' => 'TXN-' . strtoupper((string) Str::uuid()),
            'store_id' => $storeId,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variation_id' => $details['variation_id'] ?? null,
            'transaction_type' => $type,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => (int) ($details['quantity_after'] ?? ($quantityBefore + $quantityChange)),
            'related_branch_id' => $details['related_branch_id'] ?? null,
            'reference_type' => $details['reference_type'] ?? null,
            'reference_id' => $details['reference_id'] ?? null,
            'notes' => $details['notes'] ?? null,
            'unit_cost' => $details['unit_cost'] ?? $product->cost_price,
            'total_value' => $details['total_value'] ?? (abs($quantityChange) * (float) ($details['unit_cost'] ?? $product->cost_price ?? 0)),
            'requires_approval' => false,
            'approval_status' => 'not_required',
            'created_by' => $employeeId,
            'transaction_date' => now(),
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
        return (float) BranchInventory::join('products', 'products.id', '=', 'branch_inventory.product_id')
            ->where('branch_inventory.store_id', $storeId)
            ->where('branch_inventory.branch_id', $branchId)
            ->sum(
                DB::raw('branch_inventory.quantity_on_hand * COALESCE(products.cost_price, 0)')
            );
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
