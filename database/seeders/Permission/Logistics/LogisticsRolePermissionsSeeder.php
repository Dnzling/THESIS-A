<?php

namespace Database\Seeders\Permission\Logistics;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogisticsRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');

        $allLogisticsPermissions = DB::table('permissions')
            ->where('module', 'logistics')
            ->pluck('id');

        $deliveriesView = DB::table('permissions')->where('name', 'logistics.deliveries.view')->value('id');
        $deliveriesManage = DB::table('permissions')->where('name', 'logistics.deliveries.manage')->value('id');
        $fleetView = DB::table('permissions')->where('name', 'logistics.fleet.view')->value('id');
        $fleetManage = DB::table('permissions')->where('name', 'logistics.fleet.manage')->value('id');
        $zonesView = DB::table('permissions')->where('name', 'logistics.zones.view')->value('id');
        $zonesManage = DB::table('permissions')->where('name', 'logistics.zones.manage')->value('id');

        $assignments = [
            'super_admin' => $allLogisticsPermissions,
            'store_admin' => $allLogisticsPermissions,
            'warehouse_manager' => collect([$deliveriesView, $deliveriesManage, $fleetView, $fleetManage, $zonesView, $zonesManage])->filter(),
            'inventory_staff' => collect([$deliveriesView, $deliveriesManage, $fleetView])->filter(),
            'branch_manager' => collect([$deliveriesView, $deliveriesManage, $fleetView])->filter(),
            'sales_staff' => collect([$deliveriesView])->filter(),
        ];

        foreach ($assignments as $roleName => $permissionIds) {
            if (!isset($roles[$roleName])) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roles[$roleName],
                        'permission_id' => $permissionId,
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

