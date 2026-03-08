<?php
namespace Database\Seeders\Permission\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $permissions = [
            // Dashboard
            ['name' => 'inventory.dashboard.view', 'display_name' => 'View Inventory Dashboard', 'module' => 'inventory'],

            // Branch Inventory
            ['name' => 'inventory.items.view', 'display_name' => 'View Branch Inventory', 'module' => 'inventory'],
            ['name' => 'inventory.items.create', 'display_name' => 'Create Inventory Records', 'module' => 'inventory'],
            ['name' => 'inventory.items.edit', 'display_name' => 'Edit Inventory Settings', 'module' => 'inventory'],
            ['name' => 'inventory.items.delete', 'display_name' => 'Delete Inventory Records', 'module' => 'inventory'],

            // Stock Adjustments
            ['name' => 'inventory.adjustments.view', 'display_name' => 'View Stock Adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.adjustments.create', 'display_name' => 'Create Stock Adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.adjustments.approve', 'display_name' => 'Approve Stock Adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.adjustments.reject', 'display_name' => 'Reject Stock Adjustments', 'module' => 'inventory'],

            // Stock Transfers
            ['name' => 'inventory.transfers.view', 'display_name' => 'View Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.create', 'display_name' => 'Create Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.approve', 'display_name' => 'Approve Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.ship', 'display_name' => 'Ship Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.receive', 'display_name' => 'Receive Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.cancel', 'display_name' => 'Cancel Stock Transfers', 'module' => 'inventory'],

            // Stock Alerts
            ['name' => 'inventory.alerts.view', 'display_name' => 'View Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.acknowledge', 'display_name' => 'Acknowledge Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.resolve', 'display_name' => 'Resolve Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.generate', 'display_name' => 'Generate Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.delete', 'display_name' => 'Delete Stock Alerts', 'module' => 'inventory'],

            // Inventory Transactions
            ['name' => 'inventory.transactions.view', 'display_name' => 'View Inventory Transactions', 'module' => 'inventory'],
            ['name' => 'inventory.transactions.export', 'display_name' => 'Export Inventory Transactions', 'module' => 'inventory'],

            // Reports
            ['name' => 'inventory.reports.view', 'display_name' => 'View Inventory Reports', 'module' => 'inventory'],
            ['name' => 'inventory.reports.export', 'display_name' => 'Export Inventory Reports', 'module' => 'inventory'],
        ];

        foreach ($permissions as $permission) {
            $permission['is_active'] = true;
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
            
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✅ Inventory permissions created successfully!');
    }
}