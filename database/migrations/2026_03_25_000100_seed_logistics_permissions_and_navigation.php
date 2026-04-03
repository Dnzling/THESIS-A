<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['name' => 'logistics.deliveries.view', 'display_name' => 'Logistics Deliveries View', 'module' => 'logistics'],
            ['name' => 'logistics.deliveries.manage', 'display_name' => 'Logistics Deliveries Manage', 'module' => 'logistics'],
            ['name' => 'logistics.fleet.view', 'display_name' => 'Logistics Fleet View', 'module' => 'logistics'],
            ['name' => 'logistics.fleet.manage', 'display_name' => 'Logistics Fleet Manage', 'module' => 'logistics'],
            ['name' => 'logistics.zones.view', 'display_name' => 'Logistics Delivery Zones View', 'module' => 'logistics'],
            ['name' => 'logistics.zones.manage', 'display_name' => 'Logistics Delivery Zones Manage', 'module' => 'logistics'],
            ['name' => 'logistics.admin', 'display_name' => 'Logistics Admin', 'module' => 'logistics'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    ...$permission,
                    'description' => 'Logistics permission atom.',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $navigationItems = [
            [
                'name' => 'logistics.deliveries',
                'display_name' => 'Deliveries',
                'module' => 'logistics',
                'section' => null,
                'route_name' => 'logistics.deliveries',
                'route_path' => '/logistics/deliveries',
                'icon' => 'pi pi-truck',
                'parent_id' => null,
                'display_order' => 1,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Schedule and track deliveries']),
            ],
            [
                'name' => 'logistics.vehicles',
                'display_name' => 'Fleet',
                'module' => 'logistics',
                'section' => null,
                'route_name' => 'logistics.vehicles',
                'route_path' => '/logistics/vehicles',
                'icon' => 'pi pi-car',
                'parent_id' => null,
                'display_order' => 2,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Vehicles and driver assignment']),
            ],
            [
                'name' => 'logistics.zones',
                'display_name' => 'Delivery Zones',
                'module' => 'logistics',
                'section' => null,
                'route_name' => 'logistics.zones',
                'route_path' => '/logistics/zones',
                'icon' => 'pi pi-map',
                'parent_id' => null,
                'display_order' => 3,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Service areas and pricing']),
            ],
        ];

        foreach ($navigationItems as $item) {
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                [
                    ...$item,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $mappings = [
            'logistics.deliveries' => ['logistics.deliveries.view'],
            'logistics.vehicles' => ['logistics.fleet.view'],
            'logistics.zones' => ['logistics.zones.view'],
        ];

        foreach ($mappings as $navName => $permissionNames) {
            $navItemId = DB::table('navigation_items')->where('name', $navName)->value('id');
            if (!$navItemId) {
                continue;
            }

            $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('navigation_permissions')->updateOrInsert(
                    [
                        'navigation_item_id' => $navItemId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionNames = [
            'logistics.deliveries.view',
            'logistics.deliveries.manage',
            'logistics.fleet.view',
            'logistics.fleet.manage',
            'logistics.zones.view',
            'logistics.zones.manage',
            'logistics.admin',
        ];

        $navNames = [
            'logistics.deliveries',
            'logistics.vehicles',
            'logistics.zones',
        ];

        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');
        $navItemIds = DB::table('navigation_items')->whereIn('name', $navNames)->pluck('id');

        if ($permissionIds->isNotEmpty() && $navItemIds->isNotEmpty()) {
            DB::table('navigation_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->whereIn('navigation_item_id', $navItemIds)
                ->delete();
        }

        DB::table('navigation_items')->whereIn('name', $navNames)->delete();
        DB::table('permissions')->whereIn('name', $permissionNames)->delete();
    }
};

