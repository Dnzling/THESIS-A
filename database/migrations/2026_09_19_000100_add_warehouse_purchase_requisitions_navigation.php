<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'warehouse.purchase-requisitions.view' => 'View Warehouse Purchase Requisitions',
            'warehouse.purchase-requisitions.manage' => 'Manage Warehouse Purchase Requisitions',
        ];

        foreach ($permissions as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'module' => 'warehouse',
                'description' => 'Warehouse purchase requisition access.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $navigation = [
            ['warehouse.purchase-requisitions', 'Purchase Requisitions', 'warehouse.purchase-requisitions', '/warehouse-operations/purchase-requisitions', 'pi pi-file-edit', 4, 'warehouse.purchase-requisitions.view'],
        ];

        foreach ($navigation as [$name, $label, $route, $path, $icon, $order, $permission]) {
            DB::table('navigation_items')->updateOrInsert(['name' => $name], [
                'display_name' => $label,
                'module' => 'warehouse',
                'section' => null,
                'route_name' => $route,
                'route_path' => $path,
                'icon' => $icon,
                'parent_id' => null,
                'display_order' => $order,
                'is_active' => true,
                'meta' => json_encode(['group_label' => 'Warehouse Operations']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $navigationId = DB::table('navigation_items')->where('name', $name)->value('id');
            $permissionId = DB::table('permissions')->where('name', $permission)->value('id');
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
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
        // Keep warehouse navigation and permissions intact for customized installations.
    }
};
