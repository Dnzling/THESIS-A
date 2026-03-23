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
            // Dashboard / Configuration / Notifications / Requisites
            ['name' => 'inventory.dashboard.view', 'display_name' => 'View Inventory Dashboard', 'module' => 'inventory'],
            ['name' => 'inventory.configuration.manage', 'display_name' => 'Manage Inventory Configuration', 'module' => 'inventory'],
            ['name' => 'inventory.notifications.view', 'display_name' => 'View Inventory Notifications', 'module' => 'inventory'],
            ['name' => 'inventory.notifications.manage', 'display_name' => 'Manage Inventory Notifications', 'module' => 'inventory'],
            ['name' => 'inventory.requisites.view', 'display_name' => 'View Inventory Requisites', 'module' => 'inventory'],

            // Branch Inventory / Items
            ['name' => 'inventory.items.view', 'display_name' => 'View Branch Inventory', 'module' => 'inventory'],
            ['name' => 'inventory.items.create', 'display_name' => 'Create Inventory Records', 'module' => 'inventory'],
            ['name' => 'inventory.items.edit', 'display_name' => 'Edit Inventory Settings', 'module' => 'inventory'],
            ['name' => 'inventory.items.update', 'display_name' => 'Update Inventory Records', 'module' => 'inventory'],
            ['name' => 'inventory.items.delete', 'display_name' => 'Delete Inventory Records', 'module' => 'inventory'],
            ['name' => 'inventory.branch_inventory.view', 'display_name' => 'View Branch Inventory Module', 'module' => 'inventory'],
            ['name' => 'inventory.branch_inventory.create', 'display_name' => 'Create Branch Inventory Entries', 'module' => 'inventory'],
            ['name' => 'inventory.branch_inventory.update', 'display_name' => 'Update Branch Inventory Entries', 'module' => 'inventory'],
            ['name' => 'inventory.branch_inventory.delete', 'display_name' => 'Delete Branch Inventory Entries', 'module' => 'inventory'],
            ['name' => 'inventory.branch_inventory.manage', 'display_name' => 'Manage Branch Inventory Status', 'module' => 'inventory'],
            ['name' => 'inventory.branch_inventory.view_all', 'display_name' => 'View All Branch Inventory', 'module' => 'inventory'],

            // Catalog entities
            ['name' => 'inventory.products.view', 'display_name' => 'View Products', 'module' => 'inventory'],
            ['name' => 'inventory.products.create', 'display_name' => 'Create Products', 'module' => 'inventory'],
            ['name' => 'inventory.products.update', 'display_name' => 'Update Products', 'module' => 'inventory'],
            ['name' => 'inventory.products.delete', 'display_name' => 'Delete Products', 'module' => 'inventory'],
            ['name' => 'inventory.categories.view', 'display_name' => 'View Categories', 'module' => 'inventory'],
            ['name' => 'inventory.categories.create', 'display_name' => 'Create Categories', 'module' => 'inventory'],
            ['name' => 'inventory.categories.update', 'display_name' => 'Update Categories', 'module' => 'inventory'],
            ['name' => 'inventory.categories.delete', 'display_name' => 'Delete Categories', 'module' => 'inventory'],
            ['name' => 'inventory.units.view', 'display_name' => 'View Units', 'module' => 'inventory'],
            ['name' => 'inventory.units.create', 'display_name' => 'Create Units', 'module' => 'inventory'],
            ['name' => 'inventory.units.update', 'display_name' => 'Update Units', 'module' => 'inventory'],
            ['name' => 'inventory.units.delete', 'display_name' => 'Delete Units', 'module' => 'inventory'],
            ['name' => 'inventory.warehouses.view', 'display_name' => 'View Warehouses', 'module' => 'inventory'],
            ['name' => 'inventory.warehouses.create', 'display_name' => 'Create Warehouses', 'module' => 'inventory'],
            ['name' => 'inventory.warehouses.update', 'display_name' => 'Update Warehouses', 'module' => 'inventory'],
            ['name' => 'inventory.warehouses.delete', 'display_name' => 'Delete Warehouses', 'module' => 'inventory'],
            ['name' => 'inventory.locations.view', 'display_name' => 'View Locations', 'module' => 'inventory'],
            ['name' => 'inventory.locations.create', 'display_name' => 'Create Locations', 'module' => 'inventory'],
            ['name' => 'inventory.locations.update', 'display_name' => 'Update Locations', 'module' => 'inventory'],
            ['name' => 'inventory.locations.delete', 'display_name' => 'Delete Locations', 'module' => 'inventory'],

            // Adjustments
            ['name' => 'inventory.adjustments.view', 'display_name' => 'View Stock Adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.adjustments.create', 'display_name' => 'Create Stock Adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.adjustments.submit', 'display_name' => 'Submit Stock Adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.adjustments.approve', 'display_name' => 'Approve Stock Adjustments', 'module' => 'inventory'],
            ['name' => 'inventory.adjustments.reject', 'display_name' => 'Reject Stock Adjustments', 'module' => 'inventory'],

            // Transfers
            ['name' => 'inventory.transfers.view', 'display_name' => 'View Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.create', 'display_name' => 'Create Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.approve', 'display_name' => 'Approve Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.ship', 'display_name' => 'Ship Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.receive', 'display_name' => 'Receive Stock Transfers', 'module' => 'inventory'],
            ['name' => 'inventory.transfers.cancel', 'display_name' => 'Cancel Stock Transfers', 'module' => 'inventory'],

            // Stock counts
            ['name' => 'inventory.stock-counts.view', 'display_name' => 'View Stock Counts', 'module' => 'inventory'],
            ['name' => 'inventory.stock-counts.create', 'display_name' => 'Create Stock Counts', 'module' => 'inventory'],
            ['name' => 'inventory.stock-counts.update', 'display_name' => 'Update Stock Counts', 'module' => 'inventory'],
            ['name' => 'inventory.stock-counts.delete', 'display_name' => 'Delete Stock Counts', 'module' => 'inventory'],
            ['name' => 'inventory.stock-counts.start', 'display_name' => 'Start Stock Counts', 'module' => 'inventory'],
            ['name' => 'inventory.stock-counts.complete', 'display_name' => 'Complete Stock Counts', 'module' => 'inventory'],
            ['name' => 'inventory.stock-counts.approve', 'display_name' => 'Approve Stock Counts', 'module' => 'inventory'],

            // Stock issues
            ['name' => 'inventory.stock-issues.view', 'display_name' => 'View Stock Issues', 'module' => 'inventory'],
            ['name' => 'inventory.stock-issues.create', 'display_name' => 'Create Stock Issues', 'module' => 'inventory'],
            ['name' => 'inventory.stock-issues.edit', 'display_name' => 'Edit Stock Issues', 'module' => 'inventory'],
            ['name' => 'inventory.stock-issues.update', 'display_name' => 'Update Stock Issues', 'module' => 'inventory'],
            ['name' => 'inventory.stock-issues.approve', 'display_name' => 'Approve Stock Issues', 'module' => 'inventory'],

            // Stock returns
            ['name' => 'inventory.stock-returns.view', 'display_name' => 'View Stock Returns', 'module' => 'inventory'],
            ['name' => 'inventory.stock-returns.create', 'display_name' => 'Create Stock Returns', 'module' => 'inventory'],
            ['name' => 'inventory.stock-returns.update', 'display_name' => 'Update Stock Returns', 'module' => 'inventory'],
            ['name' => 'inventory.stock-returns.delete', 'display_name' => 'Delete Stock Returns', 'module' => 'inventory'],
            ['name' => 'inventory.stock-returns.approve', 'display_name' => 'Approve Stock Returns', 'module' => 'inventory'],
            ['name' => 'inventory.stock-returns.reject', 'display_name' => 'Reject Stock Returns', 'module' => 'inventory'],
            ['name' => 'inventory.stock-returns.ship', 'display_name' => 'Ship Stock Returns', 'module' => 'inventory'],
            ['name' => 'inventory.stock-returns.receive', 'display_name' => 'Receive Stock Returns', 'module' => 'inventory'],

            // Alerts
            ['name' => 'inventory.alerts.view', 'display_name' => 'View Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.acknowledge', 'display_name' => 'Acknowledge Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.resolve', 'display_name' => 'Resolve Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.generate', 'display_name' => 'Generate Stock Alerts', 'module' => 'inventory'],
            ['name' => 'inventory.alerts.delete', 'display_name' => 'Delete Stock Alerts', 'module' => 'inventory'],

            // Reorder
            ['name' => 'inventory.reorder-rules.view', 'display_name' => 'View Reorder Rules', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-rules.create', 'display_name' => 'Create Reorder Rules', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-rules.update', 'display_name' => 'Update Reorder Rules', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-rules.delete', 'display_name' => 'Delete Reorder Rules', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-suggestions.view', 'display_name' => 'View Reorder Suggestions', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-suggestions.create', 'display_name' => 'Create Reorder Suggestions', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-suggestions.update', 'display_name' => 'Update Reorder Suggestions', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-suggestions.delete', 'display_name' => 'Delete Reorder Suggestions', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-suggestions.approve', 'display_name' => 'Approve Reorder Suggestions', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-suggestions.reject', 'display_name' => 'Reject Reorder Suggestions', 'module' => 'inventory'],
            ['name' => 'inventory.reorder-suggestions.implement', 'display_name' => 'Implement Reorder Suggestions', 'module' => 'inventory'],

            // Tracking
            ['name' => 'inventory.serial-numbers.view', 'display_name' => 'View Serial Numbers', 'module' => 'inventory'],
            ['name' => 'inventory.serial-numbers.create', 'display_name' => 'Create Serial Numbers', 'module' => 'inventory'],
            ['name' => 'inventory.serial-numbers.update', 'display_name' => 'Update Serial Numbers', 'module' => 'inventory'],
            ['name' => 'inventory.serial-numbers.delete', 'display_name' => 'Delete Serial Numbers', 'module' => 'inventory'],
            ['name' => 'inventory.batches.view', 'display_name' => 'View Batches', 'module' => 'inventory'],
            ['name' => 'inventory.batches.create', 'display_name' => 'Create Batches', 'module' => 'inventory'],
            ['name' => 'inventory.batches.update', 'display_name' => 'Update Batches', 'module' => 'inventory'],
            ['name' => 'inventory.batches.delete', 'display_name' => 'Delete Batches', 'module' => 'inventory'],

            // Transactions / Reports
            ['name' => 'inventory.transactions.view', 'display_name' => 'View Inventory Transactions', 'module' => 'inventory'],
            ['name' => 'inventory.transactions.export', 'display_name' => 'Export Inventory Transactions', 'module' => 'inventory'],
            ['name' => 'inventory.reports.view', 'display_name' => 'View Inventory Reports', 'module' => 'inventory'],
            ['name' => 'inventory.reports.export', 'display_name' => 'Export Inventory Reports', 'module' => 'inventory'],
            ['name' => 'inventory.reports.view_all_branches', 'display_name' => 'View Inventory Reports Across Branches', 'module' => 'inventory'],
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