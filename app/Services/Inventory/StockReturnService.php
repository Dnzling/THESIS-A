<?php
// backend/app/Services/Inventory/StockReturnService.php

namespace App\Services\Inventory;

use App\Models\Inventory\StockReturn;
use App\Models\Inventory\StockReturnItem;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockReturnService
{
    /**
     * Create a new stock return
     */
    public function createReturn(array $data): StockReturn
    {
        return DB::transaction(function () use ($data) {
            $branchInventoryRows = [];
            foreach ($data['items'] as $itemData) {
                $branchInventory = BranchInventory::query()
                    ->with('product')
                    ->where('store_id', $data['store_id'])
                    ->where('branch_id', $data['from_branch_id'])
                    ->whereKey($itemData['branch_inventory_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $branchInventory->product_id !== (int) $itemData['product_id']
                    || (int) ($branchInventory->variation_id ?? 0) !== (int) ($itemData['variation_id'] ?? 0)) {
                    throw new \InvalidArgumentException('The selected item does not match its branch inventory record.');
                }
                if ($data['return_type'] === 'supplier_return'
                    && (int) $itemData['quantity_returned'] > (int) $branchInventory->quantity_damaged) {
                    throw new \InvalidArgumentException('Supplier returns can only include units already recorded as damaged.');
                }
                $branchInventoryRows[$branchInventory->id] = $branchInventory;
            }

            if (!empty($data['supplier_id'])) {
                $supplier = Supplier::query()->where('store_id', $data['store_id'])->findOrFail($data['supplier_id']);
                foreach ($data['items'] as $itemData) {
                    if (!$supplier->products()->where('products.id', $itemData['product_id'])->exists()) {
                        throw new \InvalidArgumentException('The selected supplier is not linked to every item in this return.');
                    }
                }
            }

            // Generate return number
            $returnNumber = $this->generateReturnNumber($data['store_id']);

            // Create the return
            $return = StockReturn::create([
                'return_number' => $returnNumber,
                'store_id' => $data['store_id'],
                'from_branch_id' => $data['from_branch_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'to_branch_id' => $data['to_branch_id'] ?? null,
                'return_type' => $data['return_type'],
                'status' => 'requested',
                'requested_date' => now(),
                'expected_return_date' => $data['expected_return_date'] ?? null,
                'requested_by' => $data['requested_by'],
                'return_reason' => $data['return_reason'] ?? null,
                'reason_details' => $data['reason_details'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create return items
            $totalValue = 0;
            foreach ($data['items'] as $itemData) {
                $branchInventory = $branchInventoryRows[$itemData['branch_inventory_id']];
                $unitCost = (float) ($branchInventory->product?->getRawOriginal('cost_price') ?? 0);

                $item = StockReturnItem::create([
                    'stock_return_id' => $return->id,
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'branch_inventory_id' => $itemData['branch_inventory_id'],
                    'quantity_returned' => $itemData['quantity_returned'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $itemData['quantity_returned'],
                    'unit_value' => $unitCost,
                    'total_value' => $unitCost * $itemData['quantity_returned'],
                    'condition' => $itemData['condition'] ?? 'good',
                    'return_reason' => $itemData['return_reason'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $totalValue += $item->total_value;
            }

            // Update return total value
            $return->update(['total_value' => $totalValue]);

            return $return;
        });
    }

    /**
     * Update an existing stock return
     */
    public function updateReturn(StockReturn $return, array $data): StockReturn
    {
        return DB::transaction(function () use ($return, $data) {
            if ($return->status !== 'draft') {
                throw new \InvalidArgumentException('Only draft returns can be edited.');
            }

            // Update return details
            $return->update([
                'from_branch_id' => $data['from_branch_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'to_branch_id' => $data['to_branch_id'] ?? null,
                'return_type' => $data['return_type'],
                'expected_return_date' => $data['expected_return_date'] ?? null,
                'return_reason' => $data['return_reason'] ?? null,
                'reason_details' => $data['reason_details'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Delete existing items and recreate them
            $return->items()->delete();

            // Recreate items
            $totalValue = 0;
            foreach ($data['items'] as $itemData) {
                $branchInventory = BranchInventory::query()
                    ->with('product')
                    ->where('store_id', $return->store_id)
                    ->where('branch_id', $data['from_branch_id'])
                    ->whereKey($itemData['branch_inventory_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($return->return_type === 'supplier_return'
                    && (int) $itemData['quantity_returned'] > (int) $branchInventory->quantity_damaged) {
                    throw new \InvalidArgumentException('Supplier returns can only include units already recorded as damaged.');
                }
                $unitCost = (float) ($branchInventory->product?->getRawOriginal('cost_price') ?? 0);

                $item = StockReturnItem::create([
                    'stock_return_id' => $return->id,
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'branch_inventory_id' => $itemData['branch_inventory_id'],
                    'quantity_returned' => $itemData['quantity_returned'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $itemData['quantity_returned'],
                    'unit_value' => $unitCost,
                    'total_value' => $unitCost * $itemData['quantity_returned'],
                    'condition' => $itemData['condition'] ?? 'good',
                    'return_reason' => $itemData['return_reason'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $totalValue += $item->total_value;
            }

            // Update return total value
            $return->update(['total_value' => $totalValue]);

            return $return;
        });
    }

    /**
     * Approve a stock return
     */
    public function approveReturn(StockReturn $return, array $data): StockReturn
    {
        return DB::transaction(function () use ($return, $data) {
            if ($return->status !== 'requested') {
                throw new \InvalidArgumentException('Only requested returns can be approved.');
            }

            if ($return->return_type === 'supplier_return') {
                foreach ($return->items as $item) {
                    $inventory = BranchInventory::query()->where('store_id', $return->store_id)
                        ->where('branch_id', $return->from_branch_id)
                        ->whereKey($item->branch_inventory_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    if ((int) $item->quantity_returned > (int) $inventory->quantity_damaged) {
                        throw new \InvalidArgumentException('Damaged stock changed before approval. Refresh the return and review quantities.');
                    }
                }
            }

            $return->update([
                'status' => 'approved',
                'approved_date' => now(),
                'approved_by' => $data['approved_by'],
                'notes' => ($return->notes ? $return->notes . "\n" : '') . ($data['notes'] ?? ''),
            ]);

            return $return;
        });
    }

    /**
     * Reject a stock return
     */
    public function rejectReturn(StockReturn $return, array $data): StockReturn
    {
        $return->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return $return;
    }

    /**
     * Mark return as shipped
     */
    public function shipReturn(StockReturn $return, array $data): StockReturn
    {
        return DB::transaction(function () use ($return, $data) {
            if ($return->status !== 'approved') {
                throw new \InvalidArgumentException('Only approved returns can be dispatched.');
            }

            if ($return->return_type === 'supplier_return') {
                foreach ($return->items as $item) {
                    $inventory = BranchInventory::query()->where('store_id', $return->store_id)
                        ->where('branch_id', $return->from_branch_id)
                        ->whereKey($item->branch_inventory_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $quantity = (int) $item->quantity_returned;
                    if ((int) $inventory->quantity_damaged < $quantity || (int) $inventory->quantity_on_hand < $quantity) {
                        throw new \InvalidArgumentException('Damaged on-hand stock changed before dispatch.');
                    }

                    $before = (int) $inventory->quantity_on_hand;
                    $inventory->quantity_damaged -= $quantity;
                    $inventory->quantity_on_hand -= $quantity;
                    $inventory->suppressAutomaticTransactionLog();
                    $inventory->save();
                    $inventory->updateStockStatus();

                    InventoryTransaction::create([
                        'transaction_number' => 'TXN-' . now()->format('YmdHis') . '-' . random_int(10000, 99999),
                        'store_id' => $return->store_id,
                        'branch_id' => $return->from_branch_id,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'transaction_type' => 'return_to_supplier',
                        'quantity_before' => $before,
                        'quantity_change' => -$quantity,
                        'quantity_after' => $inventory->quantity_on_hand,
                        'reference_type' => 'stock_return',
                        'reference_id' => $return->id,
                        'notes' => "Damaged stock dispatched to supplier: {$return->return_number}",
                        'unit_cost' => $item->unit_cost,
                        'total_value' => $item->total_value ?? $item->total_cost,
                        'created_by' => $data['shipped_by'],
                        'transaction_date' => now(),
                    ]);
                }
            }

            $return->update([
                'status' => 'in_transit',
                'shipped_date' => now(),
                'shipped_by' => $data['shipped_by'],
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'driver_contact' => $data['driver_contact'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
            ]);

            return $return->fresh();
        });
    }

    /**
     * Mark return as received
     */
    public function receiveReturn(StockReturn $return, array $data): StockReturn
    {
        return DB::transaction(function () use ($return, $data) {
            if ($return->status !== 'in_transit') {
                throw new \InvalidArgumentException('Only dispatched returns can be marked received.');
            }

            $return->update([
                'status' => 'received',
                'received_date' => now(),
                'received_by' => $data['received_by'],
                'supplier_resolution' => $data['supplier_resolution'] ?? null,
                'supplier_resolution_notes' => $data['supplier_resolution_notes'] ?? null,
            ]);

            // For branch returns, add items back to destination branch inventory
            if ($return->return_type === 'branch_return' && $return->to_branch_id) {
                foreach ($return->items as $item) {
                    // Find or create branch inventory for destination branch
                    $destinationInventory = BranchInventory::firstOrCreate([
                        'store_id' => $return->store_id,
                        'branch_id' => $return->to_branch_id,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                    ], [
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'quantity_available' => 0,
                        'quantity_damaged' => 0,
                        'quantity_incoming' => 0,
                        'stock_status' => 'in_stock',
                    ]);

                    // Add quantity to destination branch
                    $destinationInventory->suppressAutomaticTransactionLog();
                    $destinationInventory->increment('quantity_on_hand', $item->quantity_returned);
                    $destinationInventory->increment('quantity_available', $item->quantity_returned);

                    // Create inventory transaction for destination branch
                    InventoryTransaction::create([
                        'store_id' => $return->store_id,
                        'branch_id' => $return->to_branch_id,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'transaction_type' => 'return_received',
                        'quantity' => $item->quantity_returned, // Positive for incoming
                        'unit_cost' => $item->unit_cost,
                        'total_cost' => $item->total_cost,
                        'reference_type' => 'stock_return',
                        'reference_id' => $return->id,
                        'transaction_date' => now(),
                        'notes' => "Return received: {$return->return_number}",
                    ]);
                }
            }

            return $return;
        });
    }

    /**
     * Generate a unique return number
     */
    private function generateReturnNumber(int $storeId): string
    {
        do {
            $number = 'RTN-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (StockReturn::where('return_number', $number)->exists());

        return $number;
    }
}
