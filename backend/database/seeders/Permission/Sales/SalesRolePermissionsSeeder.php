<?php

namespace Database\Seeders\Permission\Sales;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');

        $allSalesPermissionIds = DB::table('permissions')
            ->where('module', 'sales')
            ->pluck('id');

        $assignments = [
            'super_admin' => $allSalesPermissionIds,
            'store_admin' => $allSalesPermissionIds,
            'store_manager' => $allSalesPermissionIds,
            'sales_staff' => DB::table('permissions')
                ->whereIn('name', [
                    'sales.dashboard.view',
                    'sales.analytics.view',
                    'sales.crm.view',
                    'sales.crm.manage',
                    'sales.chats.view',
                    'sales.chats.manage',
                    'sales.pos.view',
                    'sales.pos.manage',
                    'sales.deliveries.view',
                    'sales.ecommerce-orders.view',
                    'sales.ecommerce-orders.manage',
                ])
                ->pluck('id'),
            'inventory_staff' => DB::table('permissions')
                ->whereIn('name', [
                    'sales.dashboard.view',
                    'sales.chats.view',
                    'sales.pos.view',
                    'sales.deliveries.view',
                    'sales.ecommerce-orders.view',
                ])
                ->pluck('id'),
        ];

        foreach ($assignments as $roleName => $permissionIds) {
            $roleId = $roles[$roleName] ?? null;
            if (!$roleId) {
                $this->command?->warn("Role '{$roleName}' not found. Skipping.");
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $this->command?->info('Sales role permissions assigned successfully.');
    }
}
