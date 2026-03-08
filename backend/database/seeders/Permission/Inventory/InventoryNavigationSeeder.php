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
                'name' => 'inventory.items',
                'display_name' => 'Branch Inventory',
                'module' => 'inventory',
                'route_name' => 'inventory.items',
                'route_path' => '/inventory/items',
                'icon' => 'pi pi-box',
                'parent_id' => null,
                'display_order' => 2,
                'meta' => json_encode(['subtitle' => 'Manage stock levels across branches'])
            ],
            [
                'name' => 'inventory.adjustments',
                'display_name' => 'Stock Adjustments',
                'module' => 'inventory',
                'route_name' => 'inventory.adjustments',
                'route_path' => '/inventory/adjustments',
                'icon' => 'pi pi-sync',
                'parent_id' => null,
                'display_order' => 3,
                'meta' => json_encode(['subtitle' => 'Physical counts & corrections'])
            ],
            [
                'name' => 'inventory.transfers',
                'display_name' => 'Stock Transfers',
                'module' => 'inventory',
                'route_name' => 'inventory.transfers',
                'route_path' => '/inventory/transfers',
                'icon' => 'pi pi-arrow-right-arrow-left',
                'parent_id' => null,
                'display_order' => 4,
                'meta' => json_encode(['subtitle' => 'Move stock between branches'])
            ],
            [
                'name' => 'inventory.alerts',
                'display_name' => 'Stock Alerts',
                'module' => 'inventory',
                'route_name' => 'inventory.alerts',
                'route_path' => '/inventory/alerts',
                'icon' => 'pi pi-bell',
                'parent_id' => null,
                'display_order' => 5,
                'meta' => json_encode(['subtitle' => 'Low stock & reorder notifications', 'badge' => 'count'])
            ],
            [
                'name' => 'inventory.transactions',
                'display_name' => 'Transactions',
                'module' => 'inventory',
                'route_name' => 'inventory.transactions',
                'route_path' => '/inventory/transactions',
                'icon' => 'pi pi-history',
                'parent_id' => null,
                'display_order' => 6,
                'meta' => json_encode(['subtitle' => 'Complete inventory movement history'])
            ],
            [
                'name' => 'inventory.reports',
                'display_name' => 'Reports',
                'module' => 'inventory',
                'route_name' => 'inventory.reports',
                'route_path' => '/inventory/reports',
                'icon' => 'pi pi-chart-bar',
                'parent_id' => null,
                'display_order' => 7,
                'meta' => json_encode(['subtitle' => 'Inventory analytics & insights'])
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
            'inventory.items' => ['inventory.items.view'],
            'inventory.adjustments' => ['inventory.adjustments.view'],
            'inventory.transfers' => ['inventory.transfers.view'],
            'inventory.alerts' => ['inventory.alerts.view'],
            'inventory.transactions' => ['inventory.transactions.view'],
            'inventory.reports' => ['inventory.reports.view'],
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