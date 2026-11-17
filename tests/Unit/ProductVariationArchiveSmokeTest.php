<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ProductCatalog\ProductVariationController;
use App\Http\Controllers\Api\Inventory\ProductController;
use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductVariationArchiveSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        Auth::logout();

        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_archive_checks_stock_across_all_store_branches(): void
    {
        $token = bin2hex(random_bytes(5));
        $store = Store::create([
            'name' => 'Variation Archive Smoke Store',
            'store_code' => 'VAS-' . $token,
            'type' => 'furniture',
            'status' => 'active',
        ]);
        $firstBranch = $this->createBranch($store, 'Main ' . $token, 'VAB-M-' . $token);
        $otherBranch = $this->createBranch($store, 'Other ' . $token, 'VAB-O-' . $token);
        $role = Role::create([
            'name' => 'variation_archive_' . $token,
            'display_name' => 'Variation Archive Test',
            'code' => 'VAR-' . $token,
            'is_active' => true,
        ]);
        $userId = DB::table('users')->insertGetId([
            'fname' => 'Variation',
            'lname' => 'Archive Test',
            'email' => 'variation-archive-' . $token . '@example.com',
            'password' => bcrypt('test-password'),
            'store_id' => $store->id,
            'branch_id' => $firstBranch->id,
            'role_id' => $role->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::findOrFail($userId);
        $category = Category::create([
            'store_id' => $store->id,
            'category_code' => 'VAC-' . $token,
            'category_name' => 'Archive Test Furniture',
            'is_active' => true,
        ]);
        $productAttributes = [
            'store_id' => $store->id,
            'sku' => 'VAP-' . $token,
            'product_name' => 'Archive Test Chair',
            'category_id' => $category->id,
            'base_price' => 1000,
            'is_active' => true,
        ];
        if (Schema::hasColumn('products', 'product_type')) {
            $productAttributes['product_type'] = 'finished_good';
        }
        if (Schema::hasColumn('products', 'unit_of_measurement')) {
            $productAttributes['unit_of_measurement'] = 'pcs';
        }
        $product = Product::create($productAttributes);
        $variation = ProductVariation::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'variation_sku' => 'VAV-' . $token,
            'variation_name' => 'Walnut',
            'is_active' => true,
        ]);
        ProductVariation::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'variation_sku' => 'VAV-SIB-' . $token,
            'variation_name' => 'Oak',
            'is_active' => true,
        ]);

        $inventoryRows = [];
        foreach ([[$firstBranch, 0], [$otherBranch, 3]] as [$branch, $quantity]) {
            $row = [
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            foreach ([
                'quantity_on_hand' => $quantity,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'quantity_damaged' => 0,
                'quantity_incoming' => 0,
            ] as $column => $value) {
                if (Schema::hasColumn('branch_inventory', $column)) {
                    $row[$column] = $value;
                }
            }
            if (Schema::hasColumn('branch_inventory', 'stock_status')) {
                $row['stock_status'] = $quantity > 0 ? 'in_stock' : 'out_of_stock';
            }
            $inventoryRows[] = $row;
        }
        $this->upsertInventoryRows($inventoryRows);

        Auth::login($user);
        $controller = app(ProductVariationController::class);

        $blocked = $controller->archive($variation->id);
        $this->assertSame(422, $blocked->getStatusCode());
        $this->assertStringContainsString('Other ' . $token, $blocked->getData(true)['message']);
        $this->assertTrue((bool) $variation->fresh()->is_active);

        $emptyStock = [];
        foreach ([
            'quantity_on_hand',
            'quantity_available',
            'quantity_reserved',
            'quantity_damaged',
            'quantity_incoming',
        ] as $column) {
            if (Schema::hasColumn('branch_inventory', $column)) {
                $emptyStock[$column] = 0;
            }
        }
        DB::table('branch_inventory')
            ->where('store_id', $store->id)
            ->where('variation_id', $variation->id)
            ->update($emptyStock);

        $archived = $controller->archive($variation->id);
        $this->assertSame(200, $archived->getStatusCode());
        $this->assertFalse((bool) $variation->fresh()->is_active);
    }

    public function test_initial_standard_variant_keeps_existing_branch_inventory_attached(): void
    {
        $token = bin2hex(random_bytes(5));
        $store = Store::create([
            'name' => 'Standard Variant Smoke Store',
            'store_code' => 'SVS-' . $token,
            'type' => 'furniture',
            'status' => 'active',
        ]);
        $mainBranch = $this->createBranch($store, 'Standard Main ' . $token, 'SVB-M-' . $token);
        $otherBranch = $this->createBranch($store, 'Standard Other ' . $token, 'SVB-O-' . $token);
        $category = Category::create([
            'store_id' => $store->id,
            'category_code' => 'SVC-' . $token,
            'category_name' => 'Standard Variant Test',
            'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SVP-' . $token,
            'product_name' => 'Standard Variant Chair',
            'category_id' => $category->id,
            'base_price' => 1250,
            'is_active' => true,
        ]);

        $rows = [];
        foreach ([[$mainBranch, 2], [$otherBranch, 0]] as [$branch, $quantity]) {
            $row = [
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'variation_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            foreach ([
                'quantity_on_hand' => $quantity,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'quantity_damaged' => 0,
                'quantity_incoming' => 0,
            ] as $column => $value) {
                if (Schema::hasColumn('branch_inventory', $column)) {
                    $row[$column] = $value;
                }
            }
            if (Schema::hasColumn('branch_inventory', 'stock_status')) {
                $row['stock_status'] = $quantity > 0 ? 'in_stock' : 'out_of_stock';
            }
            $rows[] = $row;
        }
        $this->upsertInventoryRows($rows);

        $method = new \ReflectionMethod(ProductController::class, 'createInitialStandardVariation');
        $method->invoke(new ProductController(), $product, (int) $store->id);

        $standard = ProductVariation::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->where('variation_name', 'Standard')
            ->first();

        $this->assertNotNull($standard);
        $this->assertSame(2, (int) DB::table('branch_inventory')
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->where('variation_id', $standard->id)
            ->count());
        $this->assertSame(0, (int) DB::table('branch_inventory')
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->whereNull('variation_id')
            ->count());
        $this->assertSame(2, (int) DB::table('branch_inventory')
            ->where('branch_id', $mainBranch->id)
            ->where('variation_id', $standard->id)
            ->value('quantity_on_hand'));
    }

    private function createBranch(Store $store, string $name, string $code): Branch
    {
        return Branch::create([
            'store_id' => $store->id,
            'name' => $name,
            'branch_code' => $code,
            'contact_number' => '09170000000',
            'is_main_branch' => str_starts_with($code, 'VAB-M-'),
            'status' => 'active',
        ]);
    }

    private function upsertInventoryRows(array $rows): void
    {
        foreach ($rows as $row) {
            $keys = array_intersect_key($row, array_flip(['store_id', 'branch_id', 'product_id', 'variation_id']));
            $values = array_diff_key($row, $keys);
            DB::table('branch_inventory')->updateOrInsert($keys, $values);
        }
    }
}
