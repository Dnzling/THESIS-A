<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'warehouse.dashboard.view' => 'View Warehouse Dashboard',
            'warehouse.warehouses.view' => 'View Warehouses',
            'warehouse.stock.view' => 'View Warehouse Stock',
            'warehouse.transfers.view' => 'View Warehouse Transfers',
            'warehouse.receiving.view' => 'View Warehouse Receiving',
        ];

        foreach ($permissions as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName, 'module' => 'warehouse',
                'description' => 'Warehouse Operations access.', 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $items = [
            ['warehouse.dashboard', 'Dashboard', 'warehouse.dashboard', '/warehouse-operations/dashboard', 'pi pi-chart-bar', 1, 'warehouse.dashboard.view'],
            ['warehouse.warehouses', 'Warehouses', 'warehouse.warehouses', '/warehouse-operations/warehouses', 'pi pi-building', 2, 'warehouse.warehouses.view'],
            ['warehouse.stock', 'Warehouse Stock', 'warehouse.stock', '/warehouse-operations/stock', 'pi pi-box', 3, 'warehouse.stock.view'],
            ['warehouse.transfer-requests', 'Transfer Requests', 'warehouse.transfer-requests', '/warehouse-operations/transfer-requests', 'pi pi-arrow-right-arrow-left', 4, 'warehouse.transfers.view'],
            ['warehouse.receiving', 'Receiving', 'warehouse.receiving', '/warehouse-operations/receiving', 'pi pi-inbox', 5, 'warehouse.receiving.view'],
            ['warehouse.transfer-history', 'Transfer History', 'warehouse.transfer-history', '/warehouse-operations/transfer-history', 'pi pi-history', 6, 'warehouse.transfers.view'],
        ];

        foreach ($items as [$name, $label, $route, $path, $icon, $order, $permission]) {
            DB::table('navigation_items')->updateOrInsert(['name' => $name], [
                'display_name' => $label, 'module' => 'warehouse', 'section' => null,
                'route_name' => $route, 'route_path' => $path, 'icon' => $icon,
                'parent_id' => null, 'display_order' => $order, 'is_active' => true,
                'meta' => json_encode(['group_label' => 'Warehouse Operations']),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $navId = DB::table('navigation_items')->where('name', $name)->value('id');
            $permissionId = DB::table('permissions')->where('name', $permission)->value('id');
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $roleIds = DB::table('roles')->whereIn('name', ['super_admin', 'store_admin'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($permissions))->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive: role/navigation configuration may be customized after deployment.
    }
};
