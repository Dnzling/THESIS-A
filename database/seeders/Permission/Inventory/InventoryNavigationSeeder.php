<?php

namespace Database\Seeders\Permission\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'inventory.dashboard',
                'display_name' => 'Dashboard',
                'module' => 'inventory',
                'route_name' => 'inventory.dashboard',
                'route_path' => '/inventory/dashboard',
                'icon' => 'pi pi-chart-line',
                'parent_id' => null,
                'display_order' => 1,
                'meta' => json_encode(['subtitle' => 'Overview of your inventory status'])
            ],
            [
                'name' => 'inventory.products',
                'display_name' => 'Products',
                'module' => 'inventory',
                'route_name' => 'inventory.products',
                'route_path' => '/inventory/products',
                'icon' => 'pi pi-box',
                'parent_id' => null,
                'display_order' => 2,
                'meta' => json_encode(['subtitle' => 'Manage product catalog and details'])
            ],
            [
                'name' => 'inventory.categories',
                'display_name' => 'Categories',
                'module' => 'inventory',
                'route_name' => 'inventory.categories',
                'route_path' => '/inventory/categories',
                'icon' => 'pi pi-tags',
                'parent_id' => null,
                'display_order' => 3,
                'meta' => json_encode(['subtitle' => 'Organize products by categories'])
            ],
            [
                'name' => 'inventory.units',
                'display_name' => 'Units',
                'module' => 'inventory',
                'route_name' => 'inventory.units',
                'route_path' => '/inventory/units',
                'icon' => 'pi pi-calculator',
                'parent_id' => null,
                'display_order' => 4,
                'meta' => json_encode(['subtitle' => 'Define measurement units'])
            ],
            [
                'name' => 'inventory.stock-issues',
                'display_name' => 'Stock Issues',
                'module' => 'inventory',
                'route_name' => 'inventory.stock-issues',
                'route_path' => '/inventory/stock-issues',
                'icon' => 'pi pi-minus-circle',
                'parent_id' => null,
                'display_order' => 5,
                'meta' => json_encode(['subtitle' => 'Track stock issues and deductions'])
            ],
            [
                'name' => 'inventory.stock-returns',
                'display_name' => 'Stock Returns',
                'module' => 'inventory',
                'route_name' => 'inventory.stock-returns',
                'route_path' => '/inventory/stock-returns',
                'icon' => 'pi pi-refresh',
                'parent_id' => null,
                'display_order' => 6,
                'meta' => json_encode(['subtitle' => 'Manage stock returns and credits'])
            ],
            [
                'name' => 'inventory.stock-counts',
                'display_name' => 'Stock Counts',
                'module' => 'inventory',
                'route_name' => 'inventory.stock-counts',
                'route_path' => '/inventory/stock-counts',
                'icon' => 'pi pi-check-circle',
                'parent_id' => null,
                'display_order' => 7,
                'meta' => json_encode(['subtitle' => 'Physical inventory counts and audits'])
            ],
            [
                'name' => 'inventory.warehouses',
                'display_name' => 'Warehouses',
                'module' => 'inventory',
                'route_name' => 'inventory.warehouses',
                'route_path' => '/inventory/warehouses',
                'icon' => 'pi pi-building',
                'parent_id' => null,
                'display_order' => 8,
                'meta' => json_encode(['subtitle' => 'Manage warehouse locations'])
            ],
            [
                'name' => 'inventory.locations',
                'display_name' => 'Locations',
                'module' => 'inventory',
                'route_name' => 'inventory.locations',
                'route_path' => '/inventory/locations',
                'icon' => 'pi pi-map-marker',
                'parent_id' => null,
                'display_order' => 9,
                'meta' => json_encode(['subtitle' => 'Define storage locations within warehouses'])
            ],
            [
                'name' => 'inventory.reorder-rules',
                'display_name' => 'Reorder Rules',
                'module' => 'inventory',
                'route_name' => 'inventory.reorder-rules',
                'route_path' => '/inventory/reorder-rules',
                'icon' => 'pi pi-exclamation-triangle',
                'parent_id' => null,
                'display_order' => 10,
                'meta' => json_encode(['subtitle' => 'Set automatic reorder thresholds'])
            ],
            [
                'name' => 'inventory.reorder-suggestions',
                'display_name' => 'Reorder Suggestions',
                'module' => 'inventory',
                'route_name' => 'inventory.reorder-suggestions',
                'route_path' => '/inventory/reorder-suggestions',
                'icon' => 'pi pi-lightbulb',
                'parent_id' => null,
                'display_order' => 11,
                'meta' => json_encode(['subtitle' => 'View automated reorder recommendations'])
            ],
            [
                'name' => 'inventory.serial-numbers',
                'display_name' => 'Serial Numbers',
                'module' => 'inventory',
                'route_name' => 'inventory.serial-numbers',
                'route_path' => '/inventory/serial-numbers',
                'icon' => 'pi pi-hashtag',
                'parent_id' => null,
                'display_order' => 12,
                'meta' => json_encode(['subtitle' => 'Track individual item serial numbers'])
            ],
            [
                'name' => 'inventory.batches',
                'display_name' => 'Batches',
                'module' => 'inventory',
                'route_name' => 'inventory.batches',
                'route_path' => '/inventory/batches',
                'icon' => 'pi pi-layer-group',
                'parent_id' => null,
                'display_order' => 13,
                'meta' => json_encode(['subtitle' => 'Manage product batches and lots'])
            ],
        ];

        foreach ($items as $item) {
            $item['is_active'] = true;
            $item['created_at'] = now();
            $item['updated_at'] = now();
            
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                $item
            );
        }

        // Link navigation to permissions
        $this->linkNavigationPermissions();

        $this->command->info('✅ Inventory navigation items created successfully!');
    }

    private function linkNavigationPermissions(): void
    {
        $mappings = [
            'inventory.dashboard' => ['inventory.dashboard.view'],
            'inventory.products' => ['inventory.products.view'],
            'inventory.categories' => ['inventory.categories.view'],
            'inventory.units' => ['inventory.units.view'],
            'inventory.stock-issues' => ['inventory.stock-issues.view'],
            'inventory.stock-returns' => ['inventory.stock-returns.view'],
            'inventory.stock-counts' => ['inventory.stock-counts.view'],
            'inventory.warehouses' => ['inventory.warehouses.view'],
            'inventory.locations' => ['inventory.locations.view'],
            'inventory.reorder-rules' => ['inventory.reorder-rules.view'],
            'inventory.reorder-suggestions' => ['inventory.reorder-suggestions.view'],
            'inventory.serial-numbers' => ['inventory.serial-numbers.view'],
            'inventory.batches' => ['inventory.batches.view'],
        ];

        foreach ($mappings as $navName => $permissionNames) {
            $navItem = DB::table('navigation_items')->where('name', $navName)->first();
            
            if (!$navItem) continue;

            $permissions = DB::table('permissions')->whereIn('name', $permissionNames)->get();

            foreach ($permissions as $permission) {
                DB::table('navigation_permissions')->updateOrInsert(
                    [
                        'navigation_item_id' => $navItem->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('✅ Navigation permissions linked successfully!');
    }
}