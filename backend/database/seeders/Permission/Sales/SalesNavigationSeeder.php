<?php

namespace Database\Seeders\Permission\Sales;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'sales.dashboard',
                'display_name' => 'Sales Dashboard',
                'module' => 'sales',
                'route_name' => 'sales.dashboard',
                'route_path' => '/sales/dashboard',
                'icon' => 'pi pi-chart-line',
                'parent_id' => null,
                'display_order' => 1,
            ],
            [
                'name' => 'sales.crm',
                'display_name' => 'CRM Leads',
                'module' => 'sales',
                'route_name' => 'sales.crm',
                'route_path' => '/sales/crm',
                'icon' => 'pi pi-users',
                'parent_id' => null,
                'display_order' => 2,
            ],
            [
                'name' => 'sales.pos.overview',
                'display_name' => 'POS Overview',
                'module' => 'sales',
                'route_name' => 'sales.pos.overview',
                'route_path' => '/sales/pos/overview',
                'icon' => 'pi pi-chart-bar',
                'parent_id' => null,
                'display_order' => 3,
            ],
            [
                'name' => 'sales.pos',
                'display_name' => 'POS',
                'module' => 'sales',
                'route_name' => 'sales.pos',
                'route_path' => '/sales/pos',
                'icon' => 'pi pi-shopping-cart',
                'parent_id' => null,
                'display_order' => 4,
            ],
            [
                'name' => 'sales.chats',
                'display_name' => 'Customer Chats',
                'module' => 'sales',
                'route_name' => 'sales.chats',
                'route_path' => '/sales/chats',
                'icon' => 'pi pi-comments',
                'parent_id' => null,
                'display_order' => 5,
            ],
            [
                'name' => 'sales.deliveries',
                'display_name' => 'Sales Deliveries',
                'module' => 'sales',
                'route_name' => 'sales.deliveries',
                'route_path' => '/sales/deliveries',
                'icon' => 'pi pi-truck',
                'parent_id' => null,
                'display_order' => 7,
            ],
            [
                'name' => 'sales.ecommerce-orders',
                'display_name' => 'Ecommerce Orders',
                'module' => 'sales',
                'route_name' => 'sales.ecommerce-orders',
                'route_path' => '/sales/ecommerce-orders',
                'icon' => 'pi pi-shopping-bag',
                'parent_id' => null,
                'display_order' => 6,
            ],
        ];

        foreach ($items as $item) {
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                array_merge($item, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $mappings = [
            'sales.dashboard' => ['sales.dashboard.view'],
            'sales.crm' => ['sales.crm.view'],
            'sales.pos.overview' => ['sales.pos.view'],
            'sales.pos' => ['sales.pos.view'],
            'sales.chats' => ['sales.chats.view'],
            'sales.deliveries' => ['sales.deliveries.view'],
            'sales.ecommerce-orders' => ['sales.ecommerce-orders.view'],
        ];

        foreach ($mappings as $navigationName => $permissionNames) {
            $navigationItem = DB::table('navigation_items')->where('name', $navigationName)->first();
            if (!$navigationItem) {
                continue;
            }

            $permissions = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');
            foreach ($permissions as $permissionId) {
                DB::table('navigation_permissions')->updateOrInsert(
                    [
                        'navigation_item_id' => $navigationItem->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command?->info('Sales navigation seeded successfully.');
    }
}
