<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereNull('deleted_at')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('product_variations')
                    ->whereColumn('product_variations.product_id', 'products.id')
                    ->whereNull('product_variations.deleted_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    DB::table('product_variations')
                        ->where('product_id', $product->id)
                        ->whereNull('deleted_at')
                        ->update([
                            'base_price' => DB::raw('COALESCE(base_price, ' . (float) ($product->base_price ?? 0) . ')'),
                            'cost_price' => DB::raw('COALESCE(cost_price, ' . (float) ($product->cost_price ?? 0) . ')'),
                            'supplier_name' => DB::raw('COALESCE(supplier_name, ' . DB::getPdo()->quote($product->supplier_name ?? '') . ')'),
                            'unit_of_measurement' => DB::raw('COALESCE(unit_of_measurement, ' . DB::getPdo()->quote($product->unit_of_measurement ?? '') . ')'),
                        ]);

                    $hasParentInventory = DB::table('branch_inventory')
                        ->where('store_id', $product->store_id)
                        ->where('product_id', $product->id)
                        ->whereNull('variation_id')
                        ->whereNull('deleted_at')
                        ->exists();

                    $baselineId = DB::table('product_variations')
                        ->where('store_id', $product->store_id)
                        ->where('product_id', $product->id)
                        ->where('is_baseline', true)
                        ->whereNull('deleted_at')
                        ->value('id');

                    if (!$hasParentInventory) {
                        continue;
                    }

                    if (!$baselineId) {
                        $sku = $this->uniqueSku((int) $product->store_id, (string) $product->sku);
                        $baselineId = DB::table('product_variations')->insertGetId([
                            'store_id' => $product->store_id,
                            'product_id' => $product->id,
                            'variation_sku' => $sku,
                            'variation_name' => 'Standard',
                            'price_adjustment' => 0,
                            'base_price' => $product->base_price,
                            'discounted_price' => $product->discounted_price,
                            'cost_price' => $product->cost_price,
                            'reorder_point' => (int) ($product->reorder_point ?? 0),
                            'supplier_name' => $product->supplier_name,
                            'unit_of_measurement' => $product->unit_of_measurement,
                            'is_baseline' => true,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('branch_inventory')
                        ->where('store_id', $product->store_id)
                        ->where('product_id', $product->id)
                        ->whereNull('variation_id')
                        ->whereNull('deleted_at')
                        ->update([
                            'variation_id' => $baselineId,
                            'updated_at' => now(),
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Data ownership conversion is intentionally not reversed automatically.
    }

    private function uniqueSku(int $storeId, string $productSku): string
    {
        $base = trim($productSku) !== '' ? trim($productSku) : 'PRODUCT';
        $candidate = $base;
        $suffix = 1;

        while (DB::table('product_variations')->where('store_id', $storeId)->where('variation_sku', $candidate)->exists()) {
            $candidate = $base . '-STD' . ($suffix > 1 ? '-' . $suffix : '');
            $suffix++;
        }

        return $candidate;
    }
};
