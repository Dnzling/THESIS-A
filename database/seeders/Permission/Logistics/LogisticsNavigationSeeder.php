<?php

namespace Database\Seeders\Permission\Logistics;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogisticsNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'logistics.deliveries',
                'display_name' => 'Deliveries',
                'module' => 'logistics',
                'route_name' => 'logistics.deliveries',
                'route_path' => '/logistics/deliveries',
                'icon' => 'pi pi-truck',
                'parent_id' => null,
                'display_order' => 1,
                'meta' => json_encode(['subtitle' => 'Schedule and track deliveries']),
            ],
            [
                'name' => 'logistics.trips',
                'display_name' => 'Trips',
                'module' => 'logistics',
                'route_name' => 'logistics.trips',
                'route_path' => '/logistics/trips',
                'icon' => 'pi pi-sitemap',
                'parent_id' => null,
                'display_order' => 2,
                'meta' => json_encode(['subtitle' => 'Group orders per vehicle']),
            ],
            [
                'name' => 'logistics.vehicles',
                'display_name' => 'Fleet',
                'module' => 'logistics',
                'route_name' => 'logistics.vehicles',
                'route_path' => '/logistics/vehicles',
                'icon' => 'pi pi-car',
                'parent_id' => null,
                'display_order' => 3,
                'meta' => json_encode(['subtitle' => 'Vehicles and driver assignment']),
            ],
            [
                'name' => 'logistics.zones',
                'display_name' => 'Delivery Zones',
                'module' => 'logistics',
                'route_name' => 'logistics.zones',
                'route_path' => '/logistics/zones',
                'icon' => 'pi pi-map',
                'parent_id' => null,
                'display_order' => 4,
                'meta' => json_encode(['subtitle' => 'Service areas and pricing']),
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

        $this->linkNavigationPermissions();
    }

    private function linkNavigationPermissions(): void
    {
        $mappings = [
            'logistics.deliveries' => ['logistics.deliveries.view'],
            'logistics.trips' => ['logistics.deliveries.view'],
            'logistics.vehicles' => ['logistics.fleet.view'],
            'logistics.zones' => ['logistics.zones.view'],
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
    }
}
