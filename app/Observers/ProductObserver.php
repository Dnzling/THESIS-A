<?php

namespace App\Observers;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\StockAlert;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     * Auto-create a BranchInventory record for every branch in the store.
     */
    public function created(Product $product): void
    {
        $branches = Branch::where('store_id', $product->store_id)->get();

        foreach ($branches as $branch) {
            $exists = BranchInventory::where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->whereNull('variation_id')
                ->exists();

            if ($exists) {
                continue;
            }

            BranchInventory::create([
                'store_id'          => $product->store_id,
                'branch_id'         => $branch->id,
                'product_id'        => $product->id,
                'variation_id'      => null,
                'quantity_on_hand'  => 0,
                'quantity_reserved' => 0,
                'quantity_available'=> 0,
                'quantity_damaged'  => 0,
                'quantity_incoming' => 0,
                'reorder_point'     => 10,
                'reorder_quantity'  => 10,
                'safety_stock'      => 5,
                'maximum_stock'     => 0,
                'stock_status'      => 'out_of_stock',
                'unit_cost'         => $product->cost_price ?? 0,
                'average_cost'      => $product->cost_price ?? 0,
                'total_value'       => 0,
            ]);

            // Generate initial out-of-stock alert
            $alertExists = StockAlert::where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->whereNull('variation_id')
                ->where('alert_type', 'out_of_stock')
                ->where('status', 'active')
                ->exists();

            if (!$alertExists) {
                StockAlert::create([
                    'branch_id'                  => $branch->id,
                    'product_id'                 => $product->id,
                    'variation_id'               => null,
                    'alert_type'                 => 'out_of_stock',
                    'current_quantity'           => 0,
                    'reorder_point'              => 10,
                    'recommended_order_quantity' => 10,
                    'status'                     => 'active',
                ]);
            }
        }
    }
}
