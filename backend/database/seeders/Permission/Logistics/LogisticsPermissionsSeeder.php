<?php

namespace Database\Seeders\Permission\Logistics;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogisticsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $permissions = [
            ['name' => 'logistics.deliveries.view', 'display_name' => 'View Deliveries', 'module' => 'logistics'],
            ['name' => 'logistics.deliveries.manage', 'display_name' => 'Manage Deliveries', 'module' => 'logistics'],
            ['name' => 'logistics.fleet.view', 'display_name' => 'View Fleet', 'module' => 'logistics'],
            ['name' => 'logistics.fleet.manage', 'display_name' => 'Manage Fleet', 'module' => 'logistics'],
            ['name' => 'logistics.zones.view', 'display_name' => 'View Delivery Zones', 'module' => 'logistics'],
            ['name' => 'logistics.zones.manage', 'display_name' => 'Manage Delivery Zones', 'module' => 'logistics'],
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
    }
}

