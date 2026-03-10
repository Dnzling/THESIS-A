<?php

namespace App\Observers;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\StockAlert;
use Illuminate\Support\Str;

class BranchInventoryObserver
{
    /**
     * Handle the BranchInventory "saving" event.
     * Auto-calculate stock_status and total_value before every save.
     */
    public function saving(BranchInventory $inventory): void
    {
        // Auto-calculate quantity_available
        $onHand    = (int) ($inventory->quantity_on_hand ?? 0);
        $reserved  = (int) ($inventory->quantity_reserved ?? 0);
        $damaged   = (int) ($inventory->quantity_damaged ?? 0);
        $available = max(0, $onHand - $reserved - $damaged);

        $inventory->quantity_available = $available;

        // Auto-calculate stock_status
        $reorderPoint = (int) ($inventory->reorder_point ?? 0);
        $maximumStock = (int) ($inventory->maximum_stock ?? 0);

        if ($available <= 0) {
            $inventory->stock_status = 'out_of_stock';
        } elseif ($maximumStock > 0 && $available > $maximumStock) {
            $inventory->stock_status = 'overstock';
        } elseif ($reorderPoint > 0 && $available <= $reorderPoint) {
            $inventory->stock_status = 'low_stock';
        } else {
            $inventory->stock_status = 'in_stock';
        }

        // Auto-calculate total_value
        $avgCost = (float) ($inventory->average_cost ?? 0);
        $inventory->total_value = round($onHand * $avgCost, 2);
    }

    /**
     * Handle the BranchInventory "updated" event.
     * After a quantity change: generate/resolve alerts and log a transaction.
     */
    public function updated(BranchInventory $inventory): void
    {
        $quantityChanged = $inventory->wasChanged('quantity_on_hand');

        if ($quantityChanged) {
            $this->manageAlerts($inventory);
            $this->logTransaction($inventory);
        }
    }

    /**
     * Create, resolve, or maintain StockAlert records based on current stock status.
     */
    private function manageAlerts(BranchInventory $inventory): void
    {
        $status    = $inventory->stock_status;
        $branchId  = $inventory->branch_id;
        $productId = $inventory->product_id;
        $varId     = $inventory->variation_id;

        // Alert types that may be relevant
        $relevantTypes = ['out_of_stock', 'low_stock', 'overstock'];

        foreach ($relevantTypes as $type) {
            $active = StockAlert::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->where('variation_id', $varId)
                ->where('alert_type', $type)
                ->where('status', 'active')
                ->first();

            $shouldAlert = match ($type) {
                'out_of_stock' => $status === 'out_of_stock',
                'low_stock'    => $status === 'low_stock',
                'overstock'    => $status === 'overstock',
                default        => false,
            };

            if ($shouldAlert && !$active) {
                // Create new alert
                StockAlert::create([
                    'branch_id'                  => $branchId,
                    'product_id'                 => $productId,
                    'variation_id'               => $varId,
                    'alert_type'                 => $type,
                    'current_quantity'           => $inventory->quantity_available,
                    'reorder_point'              => $inventory->reorder_point ?? 0,
                    'recommended_order_quantity' => $inventory->reorder_quantity ?? 0,
                    'status'                     => 'active',
                ]);
            } elseif (!$shouldAlert && $active) {
                // Resolve the alert automatically
                $active->resolve();
            }
        }
    }

    /**
     * Log an InventoryTransaction audit record for the quantity change.
     */
    private function logTransaction(BranchInventory $inventory): void
    {
        $before = (int) ($inventory->getOriginal('quantity_on_hand') ?? 0);
        $after  = (int) ($inventory->quantity_on_hand ?? 0);
        $change = $after - $before;

        if ($change === 0) {
            return;
        }

        try {
            InventoryTransaction::create([
                'transaction_number' => 'TXN-' . strtoupper(Str::random(10)),
                'store_id'           => $inventory->store_id,
                'branch_id'          => $inventory->branch_id,
                'product_id'         => $inventory->product_id,
                'variation_id'       => $inventory->variation_id,
                'transaction_type'   => 'adjustment',
                'quantity_before'    => $before,
                'quantity_change'    => $change,
                'quantity_after'     => $after,
                'notes'              => 'Auto-logged by system on quantity change',
                'unit_cost'          => $inventory->average_cost ?? 0,
                'total_value'        => abs($change) * (float) ($inventory->average_cost ?? 0),
                'requires_approval'  => false,
                'approval_status'    => 'not_required',
                'created_by'         => auth()->user()?->employee_id ?? auth()->id(),
                'transaction_date'   => now(),
            ]);
        } catch (\Exception $e) {
            // Non-fatal: transaction logging failure should not block the inventory update
            \Illuminate\Support\Facades\Log::warning('BranchInventoryObserver: Failed to log transaction: ' . $e->getMessage());
        }
    }
}
