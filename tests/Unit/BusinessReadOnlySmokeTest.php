<?php

namespace Tests\Unit;

use App\Models\Core\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BusinessReadOnlySmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) config('database.connections.mysql.database');
        $this->assertSame('testing', app()->environment());
        $this->assertSame('mysql', config('database.default'));
        $this->assertContains($database, [
            'furnitureplatform_db_1_test',
            'furnitureplatform_flow_test',
        ], 'The business smoke test must never use the local or production database.');
        $this->assertSame($database, DB::selectOne('SELECT DATABASE() AS name')->name);
    }

    public function test_business_schema_and_routes_are_present(): void
    {
        foreach (['users', 'stores', 'branches', 'products', 'branch_inventory',
            'ecommerce_orders', 'subscription_plans', 'permissions', 'plan_permissions',
            'navigation_items'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(app('router')->has('store.dashboard'));
        $this->assertTrue(app('router')->has('store.team'));
    }

    public function test_business_data_relationships_are_valid(): void
    {
        if (! DB::table('stores')->exists()) {
            $this->markTestSkipped('No store data exists in this database; data relationship checks need fixtures.');
        }
        $this->assertTrue(DB::table('branches')->join('stores', 'stores.id', '=', 'branches.store_id')->exists(),
            'No branch belongs to a test store.');
        $this->assertTrue(DB::table('products')->join('stores', 'stores.id', '=', 'products.store_id')->exists(),
            'No product belongs to a test store.');
        $this->assertTrue(DB::table('branch_inventory as stock')
            ->join('products', 'products.id', '=', 'stock.product_id')
            ->whereColumn('stock.store_id', 'products.store_id')->exists(),
            'No stock record matches a product in its store.');

    }

    public function test_public_product_browsing_is_available(): void
    {
        $this->getJson('/api/ecommerce/products/active-stock?per_page=1')
            ->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/ecommerce/categories/active-stock')->assertOk();
    }

    public function test_public_product_search_is_available(): void
    {
        $name = DB::table('products')->where('product_type', 'finished_good')
            ->where('is_active', true)->whereNull('deleted_at')->value('product_name');
        if ($name === null) {
            $this->markTestSkipped('No active finished-good product exists for search verification.');
        }
        $term = mb_substr(trim((string) $name), 0, 2);
        $this->getJson('/api/ecommerce/products/suggestions?search='.urlencode($term))
            ->assertOk()->assertJsonStructure(['data']);
    }

    public function test_assigned_business_user_can_open_the_store_dashboard(): void
    {
        $user = User::query()->where('is_active', true)->whereNotNull('store_id')
            ->where(fn ($query) => $query->whereNotNull('branch_id')
                ->orWhereHas('employee', fn ($employee) => $employee->whereNotNull('branch_id')))
            ->whereHas('store')->first();

        if ($user === null) {
            $this->markTestSkipped('No active branch-assigned business user exists for dashboard verification.');
        }
        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/store-module/dashboard')
            ->assertOk()
            ->assertJsonPath('data.branch.store_id', (int) $user->store_id);
    }

    public function test_growth_team_permission_and_navigation_are_connected(): void
    {
        $growth = DB::table('subscription_plans')->where('plan_key', 'growth')->value('id');
        if ($growth === null) {
            $this->markTestSkipped('No Growth plan exists for permission verification.');
        }
        $navigation = DB::table('navigation_items')->where('name', 'store.team')
            ->where('route_path', '/store/team')->where('is_active', true)->value('id');
        $this->assertNotNull($navigation, 'Team navigation is missing. Apply the Team migration to this test database.');

        foreach (['store.team.view', 'store.team.manage'] as $name) {
            $permission = DB::table('permissions')->where('name', $name)->where('is_active', true)->value('id');
            $this->assertNotNull($permission, "Missing permission: {$name}");
            $this->assertTrue(DB::table('navigation_permissions')
                ->where('navigation_item_id', $navigation)->where('permission_id', $permission)->exists());
            $this->assertTrue(DB::table('plan_permissions')->where('plan_id', $growth)
                ->where('permission_id', $permission)->where('included', true)->exists(),
                "Growth does not include {$name}.");
        }
    }
}
