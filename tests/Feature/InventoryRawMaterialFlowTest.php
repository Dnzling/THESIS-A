<?php

namespace Tests\Feature;

use App\Models\Core\User;
use App\Models\ProductCatalog\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryRawMaterialFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_material_can_be_created_with_minimal_fields_and_generated_sku(): void
    {
        $category = Category::create([
            'store_id' => 1,
            'category_code' => 'RM-001',
            'category_name' => 'Raw Materials',
            'description' => 'Test category',
            'is_active' => true,
        ]);

        $user = User::create([
            'fname' => 'Inventory',
            'lname' => 'Admin',
            'email' => 'inventory-admin@example.com',
            'password' => bcrypt('password123'),
            'store_id' => 1,
            'branch_id' => 1,
            'role_id' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/inventory/products', [
            'product_name' => 'Marine Plywood',
            'product_type' => 'raw_material',
            'unit_of_measurement' => 'sheet',
            'unit_cost' => 125.5,
            'supplier_name' => 'Wood Supply Co',
            'initial_stock' => 10,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.product_type', 'raw_material');
        $response->assertJsonPath('data.unit_of_measurement', 'sheet');
        $response->assertJsonPath('data.supplier_name', 'Wood Supply Co');
        $response->assertJsonPath('data.initial_stock', '10.00');
        $this->assertNotEmpty($response->json('data.sku'));
        $this->assertSame($category->id, $response->json('data.category_id'));
        $this->assertDatabaseHas('products', [
            'product_name' => 'Marine Plywood',
            'product_type' => 'raw_material',
            'store_id' => $user->store_id,
            'unit_of_measurement' => 'sheet',
            'supplier_name' => 'Wood Supply Co',
        ]);
    }
}
