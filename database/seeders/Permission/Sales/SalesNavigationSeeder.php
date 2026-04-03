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
                'is_active' => true,
            ],
            [
                'name' => 'sales.crm',
                'display_name' => 'CRM Leads',
                'module' => 'sales',
                'route_name' => 'sales.crm',
                'route_path' => '/sales/crm',
                'icon' => 'pi pi-users',
                'parent_id' => null,
                'display_order' => 6,
                'is_active' => false,
            ],
            [
                'name' => 'sales.orders',
                'display_name' => 'Orders',
                'module' => 'sales',
                'route_name' => 'sales.orders',
                'route_path' => '/sales/orders',
                'icon' => 'pi pi-clipboard',
                'parent_id' => null,
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'sales.pos.overview',
                'display_name' => 'POS Overview',
                'module' => 'sales',
                'route_name' => 'sales.pos.overview',
                'route_path' => '/sales/pos/overview',
                'icon' => 'pi pi-chart-bar',
                'parent_id' => null,
                'display_order' => 8,
                'is_active' => false,
            ],
            [
                'name' => 'sales.pos',
                'display_name' => 'POS',
                'module' => 'sales',
                'route_name' => 'sales.pos',
                'route_path' => '/sales/pos',
                'icon' => 'pi pi-shopping-cart',
                'parent_id' => null,
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'sales.chats',
                'display_name' => 'Customer Chats',
                'module' => 'sales',
                'route_name' => 'sales.chats',
                'route_path' => '/sales/chats',
                'icon' => 'pi pi-comments',
                'parent_id' => null,
                'display_order' => 7,
                'is_active' => false,
            ],
            [
                'name' => 'sales.deliveries',
                'display_name' => 'Deliveries',
                'module' => 'sales',
                'route_name' => 'sales.deliveries',
                'route_path' => '/sales/deliveries',
                'icon' => 'pi pi-truck',
                'parent_id' => null,
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'sales.ecommerce-orders',
                'display_name' => 'Online Orders',
                'module' => 'sales',
                'route_name' => 'sales.ecommerce-orders',
                'route_path' => '/sales/ecommerce-orders',
                'icon' => 'pi pi-shopping-bag',
                'parent_id' => null,
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'sales.reviews',
                'display_name' => 'Reviews',
                'module' => 'sales',
                'route_name' => 'sales.reviews',
                'route_path' => '/sales/reviews',
                'icon' => 'pi pi-star',
                'parent_id' => null,
                'display_order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'sales.reports',
                'display_name' => 'Reports',
                'module' => 'sales',
                'route_name' => 'sales.reports',
                'route_path' => '/sales/reports',
                'icon' => 'pi pi-chart-bar',
                'parent_id' => null,
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'sales.refunds',
                'display_name' => 'Refunds',
                'module' => 'sales',
                'route_name' => 'sales.refunds',
                'route_path' => '/sales/refunds',
                'icon' => 'pi pi-undo',
                'parent_id' => null,
                'display_order' => 11,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                array_merge($item, [
                    'is_active' => $item['is_active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $mappings = [
            'sales.dashboard' => ['sales.dashboard.view'],
            'sales.crm' => ['sales.crm.view'],
            'sales.orders' => ['sales.pos.view', 'sales.ecommerce-orders.view'],
            'sales.pos' => ['sales.pos.view'],
            'sales.chats' => ['sales.chats.view'],
            'sales.deliveries' => ['sales.deliveries.view'],
            'sales.ecommerce-orders' => ['sales.ecommerce-orders.view'],
            'sales.pos.overview' => ['sales.pos.view'],
            'sales.reviews' => ['sales.reviews.view'],
            'sales.reports' => ['sales.reports.view'],
            'sales.refunds' => ['sales.refunds.view'],
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
