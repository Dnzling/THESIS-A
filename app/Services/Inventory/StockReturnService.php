<?php
// backend/app/Services/Inventory/StockReturnService.php

namespace App\Services\Inventory;

use App\Models\Inventory\StockReturn;
use App\Models\Inventory\StockReturnItem;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
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
                'status' => 'draft',
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
                $branchInventory = BranchInventory::findOrFail($itemData['branch_inventory_id']);

                $item = StockReturnItem::create([
                    'stock_return_id' => $return->id,
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'branch_inventory_id' => $itemData['branch_inventory_id'],
                    'quantity_returned' => $itemData['quantity_returned'],
                    'unit_cost' => $branchInventory->unit_cost ?? 0,
                    'total_cost' => ($branchInventory->unit_cost ?? 0) * $itemData['quantity_returned'],
                    'unit_value' => $branchInventory->average_cost ?? $branchInventory->unit_cost ?? 0,
                    'total_value' => ($branchInventory->average_cost ?? $branchInventory->unit_cost ?? 0) * $itemData['quantity_returned'],
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
                $branchInventory = BranchInventory::findOrFail($itemData['branch_inventory_id']);

                $item = StockReturnItem::create([
                    'stock_return_id' => $return->id,
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'branch_inventory_id' => $itemData['branch_inventory_id'],
                    'quantity_returned' => $itemData['quantity_returned'],
                    'unit_cost' => $branchInventory->unit_cost ?? 0,
                    'total_cost' => ($branchInventory->unit_cost ?? 0) * $itemData['quantity_returned'],
                    'unit_value' => $branchInventory->average_cost ?? $branchInventory->unit_cost ?? 0,
                    'total_value' => ($branchInventory->average_cost ?? $branchInventory->unit_cost ?? 0) * $itemData['quantity_returned'],
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
            $return->update([
                'status' => 'approved',
                'approved_date' => now(),
                'approved_by' => $data['approved_by'],
                'notes' => ($return->notes ? $return->notes . "\n" : '') . ($data['notes'] ?? ''),
            ]);

            // Create inventory transactions for approved returns
            foreach ($return->items as $item) {
                InventoryTransaction::create([
                    'store_id' => $return->store_id,
                    'branch_id' => $return->from_branch_id,
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'transaction_type' => 'return',
                    'quantity' => -$item->quantity_returned, // Negative for outgoing
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'reference_type' => 'stock_return',
                    'reference_id' => $return->id,
                    'transaction_date' => now(),
                    'notes' => "Return approved: {$return->return_number}",
                ]);

                // Update branch inventory (reduce quantity)
                $branchInventory = $item->branchInventory;
                if ($branchInventory) {
                    $branchInventory->decrement('quantity_on_hand', $item->quantity_returned);
                    $branchInventory->decrement('quantity_available', $item->quantity_returned);
                }
            }

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
        $return->update([
            'status' => 'in_transit',
            'shipped_date' => now(),
            'shipped_by' => $data['shipped_by'],
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'driver_contact' => $data['driver_contact'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
        ]);

        return $return;
    }

    /**
     * Mark return as received
     */
    public function receiveReturn(StockReturn $return, array $data): StockReturn
    {
        return DB::transaction(function () use ($return, $data) {
            $return->update([
                'status' => 'received',
                'received_date' => now(),
                'received_by' => $data['received_by'],
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
                        'unit_cost' => $item->unit_cost,
                        'average_cost' => $item->unit_value,
                        'total_value' => 0,
                    ]);

                    // Add quantity to destination branch
                    $destinationInventory->increment('quantity_on_hand', $item->quantity_returned);
                    $destinationInventory->increment('quantity_available', $item->quantity_returned);
                    $destinationInventory->update([
                        'total_value' => $destinationInventory->quantity_on_hand * $destinationInventory->average_cost,
                    ]);

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