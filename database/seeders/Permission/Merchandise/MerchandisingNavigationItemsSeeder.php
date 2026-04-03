<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationItemsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'merchandising.dashboard',
                'display_name' => 'Dashboard',
                'module' => 'merchandising',
                'route_name' => 'merchandising.dashboard',
                'route_path' => '/merchandising/dashboard',
                'icon' => 'pi pi-home',
                'parent_id' => null,
                'display_order' => 1,
                'meta' => json_encode(['subtitle' => 'Overview of your product catalog'])
            ],
            [
                'name' => 'merchandising.products',
                'display_name' => 'All Products',
                'module' => 'merchandising',
                'route_name' => 'merchandising.products',
                'route_path' => '/merchandising/products',
                'icon' => 'pi pi-box',
                'parent_id' => null,
                'display_order' => 2,
                'meta' => json_encode(['subtitle' => 'Manage your furniture product catalog'])
            ],
            [
                'name' => 'merchandising.products.create',
                'display_name' => 'Add New Product',
                'module' => 'merchandising',
                'route_name' => 'merchandising.products.create',
                'route_path' => '/merchandising/products/new',
                'icon' => 'pi pi-plus-circle',
                'parent_id' => null,
                'display_order' => 3,
                'meta' => json_encode(['subtitle' => 'Create a new furniture product'])
            ],
            [
                'name' => 'merchandising.inventory',
                'display_name' => 'Inventory Status',
                'module' => 'merchandising',
                'route_name' => 'merchandising.inventory',
                'route_path' => '/merchandising/inventory',
                'icon' => 'pi pi-database',
                'parent_id' => null,
                'display_order' => 4,
                'meta' => json_encode(['subtitle' => 'Monitor stock levels'])
            ],
            [
                'name' => 'merchandising.categories',
                'display_name' => 'Categories',
                'module' => 'merchandising',
                'route_name' => 'merchandising.categories',
                'route_path' => '/merchandising/categories',
                'icon' => 'pi pi-sitemap',
                'parent_id' => null,
                'display_order' => 5,
                'meta' => json_encode(['subtitle' => 'Organize your furniture catalog'])
            ],
            [
                'name' => 'merchandising.attributes',
                'display_name' => 'Product Attributes',
                'module' => 'merchandising',
                'route_name' => 'merchandising.attributes',
                'route_path' => '/merchandising/attributes',
                'icon' => 'pi pi-tags',
                'parent_id' => null,
                'display_order' => 6,
                'meta' => json_encode(['subtitle' => 'Define filterable product characteristics'])
            ]
        ];

        foreach ($items as $item) {
            $item['is_active'] = true;
            $item['created_at'] = now();
            $item['updated_at'] = now();
            DB::table('navigation_items')->insert($item);
        }

        // Link navigation to permissions
        $this->linkNavigationPermissions();
    }

    private function linkNavigationPermissions(): void
    {
        $links = [
            'merchandising.dashboard' => ['merchandising.dashboard.view'],
            'merchandising.products' => ['merchandising.products.view'],
            'merchandising.products.create' => ['merchandising.products.create'],
            'merchandising.inventory' => ['merchandising.inventory.view'],
            'merchandising.categories' => ['merchandising.categories.view'],
            'merchandising.attributes' => ['merchandising.attributes.view'],
        ];

        foreach ($links as $navName => $permissionNames) {
            $navItem = DB::table('navigation_items')->where('name', $navName)->first();
            if (!$navItem) continue;

            foreach ($permissionNames as $permName) {
                $permission = DB::table('permissions')->where('name', $permName)->first();
                if (!$permission) continue;

                DB::table('navigation_permissions')->insert([
                    'navigation_item_id' => $navItem->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}