<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        // The registered Inertia routes use /warehouse. Keep database-driven
        // navigation links aligned with those routes.
        DB::table('navigation_items')
            ->where('module', 'warehouse')
            ->where('route_path', 'like', '/warehouse-operations/%')
            ->update([
                'route_path' => DB::raw("REPLACE(route_path, '/warehouse-operations/', '/warehouse/')"),
                'updated_at' => $now,
            ]);

        DB::table('permissions')->updateOrInsert(['name' => 'warehouse.locations.view'], [
            'display_name' => 'View Warehouse Locations',
            'module' => 'warehouse',
            'description' => 'View and manage warehouse storage locations.',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_items')->updateOrInsert(['name' => 'warehouse.locations'], [
            'display_name' => 'Locations',
            'module' => 'warehouse',
            'section' => null,
            'route_name' => 'warehouse.locations',
            'route_path' => '/warehouse/locations',
            'icon' => 'pi pi-map-marker',
            'parent_id' => null,
            'display_order' => 3,
            'is_active' => true,
            'meta' => json_encode(['group_label' => 'Warehouse Operations']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $navId = DB::table('navigation_items')->where('name', 'warehouse.locations')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'warehouse.locations.view')->value('id');
        DB::table('navigation_permissions')->updateOrInsert(
            ['navigation_item_id' => $navId, 'permission_id' => $permissionId],
            ['created_at' => $now, 'updated_at' => $now]
        );

        foreach (DB::table('roles')->whereIn('name', ['super_admin', 'store_admin', 'owner', 'warehouse_manager'])->pluck('id') as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('navigation_items')->where('name', 'warehouse.locations')->delete();
        DB::table('permissions')->where('name', 'warehouse.locations.view')->delete();
    }
};
