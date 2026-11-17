<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permission = 'warehouse.reorder.view';
        DB::table('permissions')->updateOrInsert(['name' => $permission], [
            'display_name' => 'View Warehouse Reorder',
            'module' => 'warehouse',
            'description' => 'Manage warehouse reorder rules and suggestions.',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('navigation_items')->updateOrInsert(['name' => 'warehouse.reorder'], [
            'display_name' => 'Reorder',
            'module' => 'warehouse',
            'section' => null,
            'route_name' => 'warehouse.reorder',
            'route_path' => '/warehouse/reorder',
            'icon' => 'pi pi-refresh',
            'parent_id' => null,
            'display_order' => 5,
            'is_active' => true,
            'meta' => json_encode(['group_label' => 'Warehouse Operations']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $navigationId = DB::table('navigation_items')->where('name', 'warehouse.reorder')->value('id');
        $permissionId = DB::table('permissions')->where('name', $permission)->value('id');
        DB::table('navigation_permissions')->updateOrInsert(
            ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
            ['created_at' => $now, 'updated_at' => $now]
        );
        $roleIds = DB::table('roles')->whereIn('name', ['super_admin', 'store_admin'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        // Keep custom role and navigation assignments intact.
    }
};
