<?php

namespace Database\Seeders\Permission\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Get role IDs
        $roles = DB::table('roles')->pluck('id', 'name');
        
        // Get all inventory permissions
        $allInventoryPermissions = DB::table('permissions')
            ->where('module', 'inventory')
            ->pluck('id');

        // Define role-specific permissions
        $assignments = [
            // Full access
            'super_admin' => $allInventoryPermissions,
            
            'store_admin' => $allInventoryPermissions,
            
            // Warehouse Manager - Full inventory control
            'warehouse_manager' => DB::table('permissions')
                ->where('module', 'inventory')
                ->whereIn('name', [
                    'inventory.dashboard.view',
                    'inventory.items.view',
                    'inventory.items.create',
                    'inventory.items.edit',
                    'inventory.adjustments.view',
                    'inventory.adjustments.create',
                    'inventory.adjustments.approve',
                    'inventory.adjustments.reject',
                    'inventory.transfers.view',
                    'inventory.transfers.create',
                    'inventory.transfers.approve',
                    'inventory.transfers.ship',
                    'inventory.transfers.receive',
                    'inventory.transfers.cancel',
                    'inventory.alerts.view',
                    'inventory.alerts.acknowledge',
                    'inventory.alerts.resolve',
                    'inventory.alerts.generate',
                    'inventory.transactions.view',
                    'inventory.reports.view',
                ])
                ->pluck('id'),
            
            // Inventory Staff - Operational tasks
            'inventory_staff' => DB::table('permissions')
                ->where('module', 'inventory')
                ->whereIn('name', [
                    'inventory.dashboard.view',
                    'inventory.items.view',
                    'inventory.items.create',
                    'inventory.items.edit',
                    'inventory.adjustments.view',
                    'inventory.adjustments.create',
                    'inventory.transfers.view',
                    'inventory.transfers.create',
                    'inventory.transfers.ship',
                    'inventory.transfers.receive',
                    'inventory.alerts.view',
                    'inventory.alerts.acknowledge',
                    'inventory.transactions.view',
                ])
                ->pluck('id'),
            
            // Branch Manager - View and approve transfers
            'branch_manager' => DB::table('permissions')
                ->where('module', 'inventory')
                ->whereIn('name', [
                    'inventory.dashboard.view',
                    'inventory.items.view',
                    'inventory.adjustments.view',
                    'inventory.transfers.view',
                    'inventory.transfers.create',
                    'inventory.transfers.approve',
                    'inventory.alerts.view',
                    'inventory.alerts.acknowledge',
                    'inventory.transactions.view',
                    'inventory.reports.view',
                ])
                ->pluck('id'),
            
            // Finance Manager - View only for reporting
            'finance_manager' => DB::table('permissions')
                ->where('module', 'inventory')
                ->whereIn('name', [
                    'inventory.dashboard.view',
                    'inventory.items.view',
                    'inventory.transactions.view',
                    'inventory.transactions.export',
                    'inventory.reports.view',
                    'inventory.reports.export',
                ])
                ->pluck('id'),
            
            // Sales Staff - View inventory for selling
            'sales_staff' => DB::table('permissions')
                ->where('module', 'inventory')
                ->whereIn('name', [
                    'inventory.items.view',
                    'inventory.alerts.view',
                ])
                ->pluck('id'),
            
            // Procurement - View inventory for purchasing decisions
            'procurement_staff' => DB::table('permissions')
                ->where('module', 'inventory')
                ->whereIn('name', [
                    'inventory.dashboard.view',
                    'inventory.items.view',
                    'inventory.alerts.view',
                    'inventory.transactions.view',
                    'inventory.reports.view',
                ])
                ->pluck('id'),
        ];

        // Assign permissions to roles
        foreach ($assignments as $roleName => $permissions) {
            if (!isset($roles[$roleName])) {
                $this->command->warn("⚠️  Role '{$roleName}' not found. Skipping...");
                continue;
            }

            foreach ($permissions as $permissionId) {
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
            
            $this->command->info("✅ Assigned permissions to role: {$roleName}");
        }

        $this->command->info('✅ Inventory role permissions assigned successfully!');
    }
}