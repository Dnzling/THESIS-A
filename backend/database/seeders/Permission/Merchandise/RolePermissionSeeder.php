<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get role IDs (assumes you have a roles table)
        $roles = DB::table('roles')->pluck('id', 'name');
        
        // Get all merchandising permissions
        $allMerchandisingPermissions = DB::table('permissions')
            ->where('module', 'merchandising')
            ->pluck('id');

        $viewOnlyPermissions = DB::table('permissions')
            ->where('module', 'merchandising')
            ->where('name', 'like', '%.view')
            ->pluck('id');

        $inventoryPermissions = DB::table('permissions')
            ->where('module', 'merchandising')
            ->whereIn('name', [
                'merchandising.products.view',
                'merchandising.variations.view',
                'merchandising.inventory.view',
                'merchandising.inventory.edit'
            ])
            ->pluck('id');

        // Assign permissions to roles
        $assignments = [
            // Full access
            'super_admin' => $allMerchandisingPermissions,
            'store_admin' => $allMerchandisingPermissions,
            'store_manager' => $allMerchandisingPermissions,

            // Warehouse team
            'warehouse_manager' => $inventoryPermissions,
            'inventory_staff' => DB::table('permissions')
                ->where('module', 'merchandising')
                ->whereIn('name', [
                    'merchandising.products.view',
                    'merchandising.inventory.view',
                    'merchandising.inventory.edit'
                ])
                ->pluck('id'),

            // Sales team
            'sales_staff' => DB::table('permissions')
                ->where('module', 'merchandising')
                ->whereIn('name', [
                    'merchandising.products.view',
                    'merchandising.variations.view',
                    'merchandising.assets.view'
                ])
                ->pluck('id'),

            // Procurement
            'supplier_coordinator' => DB::table('permissions')
                ->where('module', 'merchandising')
                ->whereIn('name', [
                    'merchandising.products.view',
                    'merchandising.inventory.view',
                    'merchandising.categories.view'
                ])
                ->pluck('id'),

            // Cashier
            'cashier' => DB::table('permissions')
                ->where('module', 'merchandising')
                ->where('name', 'merchandising.products.view')
                ->pluck('id'),
        ];

        foreach ($assignments as $roleName => $permissions) {
            if (!isset($roles[$roleName])) continue;

            foreach ($permissions as $permissionId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roles[$roleName],
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}