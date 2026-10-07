<?php

namespace Database\Seeders\Permission\Merchandise;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationItemsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'merchandising.dashboard', 'display_name' => 'Dashboard', 'route_name' => 'merchandising.dashboard', 'route_path' => '/merchandising/dashboard', 'icon' => 'pi pi-home', 'display_order' => 1, 'permission' => 'merchandising.dashboard.view'],
            ['name' => 'merchandising.products', 'display_name' => 'Product Listings', 'route_name' => 'merchandising.products', 'route_path' => '/merchandising/products', 'icon' => 'pi pi-box', 'display_order' => 2, 'permission' => 'merchandising.products.view'],
            ['name' => 'merchandising.tags', 'display_name' => 'Collections', 'route_name' => 'merchandising.tags', 'route_path' => '/merchandising/tags', 'icon' => 'pi pi-th-large', 'display_order' => 3, 'permission' => 'merchandising.tags.view'],
            ['name' => 'merchandising.pricing', 'display_name' => 'Promotions & Discounts', 'route_name' => 'merchandising.pricing', 'route_path' => '/merchandising/pricing', 'icon' => 'pi pi-percentage', 'display_order' => 4, 'permission' => 'merchandising.pricing.view'],
            ['name' => 'merchandising.assets', 'display_name' => '3D Product Models', 'route_name' => 'merchandising.3d-gallery', 'route_path' => '/merchandising/3d-gallery', 'icon' => 'pi pi-box', 'display_order' => 5, 'permission' => 'merchandising.assets.view'],
            ['name' => 'merchandising.storefront', 'display_name' => 'Storefront Settings', 'route_name' => 'store.settings', 'route_path' => '/store/settings', 'icon' => 'pi pi-cog', 'display_order' => 6, 'permission' => 'merchandising.storefront.view'],
        ];

        $activeNames = array_column($items, 'name');
        DB::table('navigation_items')->where('module', 'merchandising')->whereNotIn('name', $activeNames)->update(['is_active' => false, 'parent_id' => null, 'updated_at' => now()]);

        foreach ($items as $item) {
            $permissionName = $item['permission'];
            unset($item['permission']);
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                $item + [
                    'module' => 'merchandising',
                    'section' => null,
                    'parent_id' => null,
                    'is_active' => true,
                    'meta' => json_encode(['subtitle' => $item['display_name']]),
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ]
            );

            $navigationId = DB::table('navigation_items')->where('name', $item['name'])->value('id');
            $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
            if ($navigationId && $permissionId) {
                DB::table('navigation_permissions')->updateOrInsert(
                    ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
