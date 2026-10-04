<?php

use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure existing supply products have branch inventory rows so they can
     * appear in stock adjustments and other branch-scoped inventory screens.
     */
    public function up(): void
    {
        $supplyIds = Product::query()
            ->where('product_type', 'supply')
            ->pluck('id');

        if ($supplyIds->isEmpty()) {
            return;
        }

        $stores = Product::query()
            ->whereIn('id', $supplyIds)
            ->select('store_id')
            ->distinct()
            ->pluck('store_id');

        foreach ($stores as $storeId) {
            $activeBranchIds = Branch::query()
                ->where('store_id', $storeId)
                ->where('status', 'active')
                ->pluck('id');

            if ($activeBranchIds->isEmpty()) {
                continue;
            }

            $storeSupplyIds = Product::query()
                ->where('store_id', $storeId)
                ->where('product_type', 'supply')
                ->pluck('id');

            foreach ($storeSupplyIds as $productId) {
                foreach ($activeBranchIds as $branchId) {
                    BranchInventory::query()->firstOrCreate(
                        [
                            'store_id' => $storeId,
                            'branch_id' => (int) $branchId,
                            'product_id' => (int) $productId,
                            'variation_id' => null,
                        ],
                        [
                            'quantity_on_hand' => 0,
                            'quantity_reserved' => 0,
                            'quantity_available' => 0,
                            'quantity_damaged' => 0,
                            'quantity_incoming' => 0,
                            'reorder_point' => 0,
                            'reorder_quantity' => 0,
                            'maximum_stock' => 0,
                            'safety_stock' => 0,
                            'stock_status' => 'out_of_stock',
                            'total_value' => 0,
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('branch_inventory')
            ->whereIn('product_id', function ($query) {
                $query->select('id')
                    ->from('products')
                    ->where('product_type', 'supply');
            })
            ->delete();
    }
};
