<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(['name' => 'warehouse.returns.view'], [
            'display_name' => 'View Warehouse Returns', 'module' => 'warehouse',
            'description' => 'View customer returns delivered to warehouse locations.', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('navigation_items')->updateOrInsert(['name' => 'warehouse.returns'], [
            'display_name' => 'Warehouse Returns', 'module' => 'warehouse', 'section' => null,
            'route_name' => 'warehouse.returns', 'route_path' => '/warehouse-operations/returns',
            'icon' => 'pi pi-replay', 'parent_id' => null, 'display_order' => 7, 'is_active' => 1,
            'meta' => json_encode(['group_label' => 'Warehouse Operations']), 'created_at' => $now, 'updated_at' => $now,
        ]);
        $nav = DB::table('navigation_items')->where('name', 'warehouse.returns')->value('id');
        $permission = DB::table('permissions')->where('name', 'warehouse.returns.view')->value('id');
        DB::table('navigation_permissions')->updateOrInsert(['navigation_item_id' => $nav, 'permission_id' => $permission], ['created_at' => $now, 'updated_at' => $now]);
        foreach (DB::table('roles')->whereIn('name', ['super_admin', 'store_admin', 'owner', 'warehouse_manager'])->pluck('id') as $role) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $role, 'permission_id' => $permission], ['created_at' => $now, 'updated_at' => $now]);
        }
        foreach (DB::table('users')->get(['id', 'store_id']) as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
        }
    }
    public function down(): void { DB::table('navigation_items')->where('name', 'warehouse.returns')->delete(); DB::table('permissions')->where('name', 'warehouse.returns.view')->delete(); }
};
