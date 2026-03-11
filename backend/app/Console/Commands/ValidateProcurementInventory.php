<?php

namespace App\Console\Commands;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Procurement\Inventory\ProcurementInventory;
use App\Models\Store\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateProcurementInventory extends Command
{
    protected $signature = 'procurement:validate';
    protected $description = 'Validate and fix procurement inventory data integrity - ensures all records have valid foreign keys';

    public function handle()
    {
        $this->info('=== Procurement Inventory Data Validation ===');
        $this->newLine();

        // 1. Check for orphaned procurement inventory (product doesn't exist)
        $this->info('1. Checking for orphaned procurement inventory records...');
        $orphanedProducts = ProcurementInventory::whereNotIn('product_id', 
            Product::pluck('id')
        )->get();

        if ($orphanedProducts->count() > 0) {
            $this->warn("   ⚠ Found {$orphanedProducts->count()} orphaned records (product doesn't exist)");
            foreach ($orphanedProducts as $item) {
                $this->error("   • ID: {$item->id}, Product ID: {$item->product_id}, Store ID: {$item->store_id}");
            }
            if ($this->confirm('   Delete these orphaned records?', true)) {
                ProcurementInventory::whereNotIn('product_id', Product::pluck('id'))->delete();
                $this->info("   ✓ Deleted {$orphanedProducts->count()} orphaned records");
            }
        } else {
            $this->info('   ✓ No orphaned product records found');
        }
        $this->newLine();

        // 2. Check for orphaned store_id references
        $this->info('2. Checking for orphaned store references...');
        $orphanedStores = ProcurementInventory::whereNotIn('store_id', 
            Store::pluck('id')
        )->get();

        if ($orphanedStores->count() > 0) {
            $this->warn("   ⚠ Found {$orphanedStores->count()} orphaned records (store doesn't exist)");
            foreach ($orphanedStores as $item) {
                $this->error("   • ID: {$item->id}, Store ID: {$item->store_id}");
            }
            if ($this->confirm('   Delete these orphaned records?', true)) {
                ProcurementInventory::whereNotIn('store_id', Store::pluck('id'))->delete();
                $this->info("   ✓ Deleted {$orphanedStores->count()} orphaned records");
            }
        } else {
            $this->info('   ✓ No orphaned store records found');
        }
        $this->newLine();

        // 3. Check for mismatched store_id between product and procurement inventory
        $this->info('3. Checking for store_id mismatches...');
        $mismatches = DB::table('procurement_inventories as pi')
            ->join('products as p', 'pi.product_id', '=', 'p.id')
            ->where('pi.store_id', '!=', 'p.store_id')
            ->select('pi.id', 'pi.store_id as pi_store', 'p.store_id as p_store', 'p.product_name')
            ->get();

        if ($mismatches->count() > 0) {
            $this->warn("   ⚠ Found {$mismatches->count()} store mismatches");
            foreach ($mismatches as $mismatch) {
                $this->error("   • PI ID: {$mismatch->id}, PI Store: {$mismatch->pi_store}, Product Store: {$mismatch->p_store} ({$mismatch->product_name})");
            }
            if ($this->confirm('   Fix these mismatches by aligning to product store?', true)) {
                // Fix by updating pi_store to match product_store
                foreach ($mismatches as $mismatch) {
                    ProcurementInventory::find($mismatch->id)->update(['store_id' => $mismatch->p_store]);
                }
                $this->info("   ✓ Fixed {$mismatches->count()} store mismatches");
            }
        } else {
            $this->info('   ✓ No store mismatches found');
        }
        $this->newLine();

        // 4. Check if all base products have procurement inventory
        $this->info('4. Checking for missing procurement inventory entries (Base Products)...');
        $productsWithoutInventory = Product::whereNotIn('id', 
            ProcurementInventory::whereNull('variation_id')->pluck('product_id')->unique()
        )->get();

        if ($productsWithoutInventory->count() > 0) {
            $this->warn("   ⚠ Found {$productsWithoutInventory->count()} base products without procurement inventory");
            foreach ($productsWithoutInventory as $product) {
                $this->error("   • Product ID: {$product->id}, Name: {$product->product_name}, Store: {$product->store_id}");
            }
            if ($this->confirm('   Create missing entries for these products?', true)) {
                $created = 0;
                foreach ($productsWithoutInventory as $product) {
                    if (!ProcurementInventory::where('store_id', $product->store_id)
                        ->where('product_id', $product->id)
                        ->whereNull('variation_id')
                        ->exists()) {
                        ProcurementInventory::create([
                            'store_id' => $product->store_id,
                            'product_id' => $product->id,
                            'variation_id' => null,
                            'available_qty' => 0,
                            'on_order_qty' => 0,
                            'received_qty' => 0,
                            'pending_receive_qty' => 0,
                            'status' => $product->is_active ? 'active' : 'inactive',
                            'notes' => 'Auto-created during validation. Product: ' . $product->product_name,
                        ]);
                        $created++;
                    }
                }
                $this->info("   ✓ Created {$created} missing product entries");
            }
        } else {
            $this->info('   ✓ All base products have procurement inventory entries');
        }
        $this->newLine();

        // 5. Check for missing variation inventory entries
        $this->info('5. Checking for missing procurement inventory entries (Variations)...');
        $variationsWithoutInventory = ProductVariation::whereNotIn('id', 
            ProcurementInventory::whereNotNull('variation_id')->pluck('variation_id')->unique()
        )->get();

        if ($variationsWithoutInventory->count() > 0) {
            $this->warn("   ⚠ Found {$variationsWithoutInventory->count()} variations without procurement inventory");
            foreach ($variationsWithoutInventory as $variation) {
                $this->error("   • Variation ID: {$variation->id}, Name: {$variation->variation_name}, Product: {$variation->product_id}, Store: {$variation->store_id}");
            }
            if ($this->confirm('   Create missing entries for these variations?', true)) {
                $created = 0;
                foreach ($variationsWithoutInventory as $variation) {
                    if (!ProcurementInventory::where('store_id', $variation->store_id)
                        ->where('product_id', $variation->product_id)
                        ->where('variation_id', $variation->id)
                        ->exists()) {
                        ProcurementInventory::create([
                            'store_id' => $variation->store_id,
                            'product_id' => $variation->product_id,
                            'variation_id' => $variation->id,
                            'available_qty' => 0,
                            'on_order_qty' => 0,
                            'received_qty' => 0,
                            'pending_receive_qty' => 0,
                            'status' => $variation->is_active ? 'active' : 'inactive',
                            'notes' => 'Auto-created during validation. Variation: ' . $variation->variation_name,
                        ]);
                        $created++;
                    }
                }
                $this->info("   ✓ Created {$created} missing variation entries");
            }
        } else {
            $this->info('   ✓ All variations have procurement inventory entries');
        }
        $this->newLine();

        // 6. Summary statistics
        $this->info('=== Summary Report ===');
        $totalInventory = ProcurementInventory::count();
        $totalProducts = Product::count();
        $totalVariations = ProductVariation::count();
        $baseProductEntries = ProcurementInventory::whereNull('variation_id')->count();
        $variationEntries = ProcurementInventory::whereNotNull('variation_id')->count();
        $activeInventory = ProcurementInventory::where('status', 'active')->count();
        $inactiveInventory = ProcurementInventory::where('status', 'inactive')->count();
        $lowStockItems = ProcurementInventory::where('available_qty', '<', 10)->count();
        $totalOnOrder = ProcurementInventory::sum('on_order_qty');
        $totalReceived = ProcurementInventory::sum('received_qty');

        $this->info("Total Procurement Inventory Records: {$totalInventory}");
        $this->info("  Base Product Entries: {$baseProductEntries}");
        $this->info("  Variation Entries: {$variationEntries}");
        $this->info("Total Products in Catalog: {$totalProducts}");
        $this->info("Total Variations in Catalog: {$totalVariations}");
        $this->info("Active Inventory Items: {$activeInventory}");
        $this->info("Inactive Inventory Items: {$inactiveInventory}");
        $this->warn("Low Stock Items (< 10): {$lowStockItems}");
        $this->info("Total Qty On Order: {$totalOnOrder}");
        $this->info("Total Qty Received: {$totalReceived}");

        $this->newLine();
        // Check alignment
        $expectedTotal = $totalProducts + $totalVariations;
        if ($totalInventory === $expectedTotal) {
            $this->info("✓ DATA ALIGNED! Procurement Inventory ({$totalInventory}) matches total Products + Variations ({$expectedTotal})");
        } else {
            $this->error("✗ DATA MISMATCH! Procurement Inventory ({$totalInventory}) vs Expected ({$expectedTotal})");
            $this->error("   Expected: {$totalProducts} products + {$totalVariations} variations = {$expectedTotal}");
        }

        $this->newLine();
        $this->info('✓ Validation complete!');
    }
}

