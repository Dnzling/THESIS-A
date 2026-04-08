<?php

namespace Database\Seeders;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Procurement\Inventory\ProcurementInventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcurementInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates procurement inventory entries for all products and their variations
     */
    public function run(): void
    {
        $this->command->info('Starting Procurement Inventory Seeding (Products & Variations)...');
        $this->command->newLine();

        try {
            $productCreatedCount = 0;
            $productSkippedCount = 0;
            $variationCreatedCount = 0;
            $variationSkippedCount = 0;
            $errors = [];

            // ===== SEED BASE PRODUCTS =====
            $this->command->info('1. Processing Base Products...');
            $products = Product::query()->get();
            
            foreach ($products as $product) {
                $exists = ProcurementInventory::where('store_id', $product->store_id)
                    ->where('product_id', $product->id)
                    ->whereNull('variation_id')
                    ->exists();

                if ($exists) {
                    $productSkippedCount++;
                    continue;
                }

                try {
                    ProcurementInventory::create([
                        'store_id' => $product->store_id,
                        'product_id' => $product->id,
                        'variation_id' => null, // Base product
                        'available_qty' => 0,
                        'on_order_qty' => 0,
                        'received_qty' => 0,
                        'pending_receive_qty' => 0,
                        'status' => $product->is_active ? 'active' : 'inactive',
                        'last_order_date' => null,
                        'last_receive_date' => null,
                        'notes' => 'Auto-created (Base Product). SKU: ' . $product->sku . ' | Name: ' . $product->product_name,
                    ]);
                    $productCreatedCount++;

                    if ($productCreatedCount % 10 === 0) {
                        $this->command->info("  ✓ Created {$productCreatedCount} base product records...");
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error creating base product inventory for product {$product->id}: " . $e->getMessage();
                    Log::error('ProcurementInventorySeeder - Product Error', [
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->command->info("  ✓ Base Products Complete: Created {$productCreatedCount}, Skipped {$productSkippedCount}");
            $this->command->newLine();

            // ===== SEED PRODUCT VARIATIONS =====
            $this->command->info('2. Processing Product Variations...');
            $variations = ProductVariation::query()->get();
            
            foreach ($variations as $variation) {
                $exists = ProcurementInventory::where('store_id', $variation->store_id)
                    ->where('product_id', $variation->product_id)
                    ->where('variation_id', $variation->id)
                    ->exists();

                if ($exists) {
                    $variationSkippedCount++;
                    continue;
                }

                try {
                    ProcurementInventory::create([
                        'store_id' => $variation->store_id,
                        'product_id' => $variation->product_id,
                        'variation_id' => $variation->id, // Variation reference
                        'available_qty' => 0,
                        'on_order_qty' => 0,
                        'received_qty' => 0,
                        'pending_receive_qty' => 0,
                        'status' => $variation->is_active ? 'active' : 'inactive',
                        'last_order_date' => null,
                        'last_receive_date' => null,
                        'notes' => 'Auto-created (Variation). SKU: ' . $variation->variation_sku . ' | Name: ' . $variation->variation_name . ' | Attrs: ' . ($variation->color ?? 'N/A') . ', ' . ($variation->size ?? 'N/A'),
                    ]);
                    $variationCreatedCount++;

                    if ($variationCreatedCount % 10 === 0) {
                        $this->command->info("  ✓ Created {$variationCreatedCount} variation records...");
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error creating variation inventory for variation {$variation->id}: " . $e->getMessage();
                    Log::error('ProcurementInventorySeeder - Variation Error', [
                        'variation_id' => $variation->id,
                        'variation_name' => $variation->variation_name,
                        'product_id' => $variation->product_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->command->info("  ✓ Variations Complete: Created {$variationCreatedCount}, Skipped {$variationSkippedCount}");
            $this->command->newLine();

            // ===== SUMMARY =====
            $this->command->info('=== Seeding Complete ===');
            $totalCreated = $productCreatedCount + $variationCreatedCount;
            $totalSkipped = $productSkippedCount + $variationSkippedCount;
            
            $this->command->info("✓ Total Created: {$totalCreated} records");
            $this->command->warn("⚠ Total Skipped: {$totalSkipped} records");
            $this->command->info("  Base Products: {$productCreatedCount} created, {$productSkippedCount} skipped");
            $this->command->info("  Variations: {$variationCreatedCount} created, {$variationSkippedCount} skipped");
            
            if (!empty($errors)) {
                $this->command->newLine();
                $this->command->error('Errors encountered:');
                foreach ($errors as $error) {
                    $this->command->error("  • {$error}");
                }
            }

            $this->command->newLine();
            $this->command->info('Summary:');
            $this->command->info('• All products and variations now have procurement inventory entries');
            $this->command->info('• Initial quantities set to 0');
            $this->command->info('• Status mirrors product/variation is_active flag');
            $this->command->info('• Variations tracked separately for granular inventory control');

        } catch (\Exception $e) {
            $this->command->error('Fatal error during seeding: ' . $e->getMessage());
            Log::error('ProcurementInventorySeeder Fatal Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}


