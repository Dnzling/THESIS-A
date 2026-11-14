<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'view' => 'View Warehouse Stock Issues',
            'manage' => 'Manage Warehouse Stock Issues',
            'approve' => 'Approve Warehouse Stock Issues',
            'delete' => 'Delete Warehouse Stock Issues',
        ];

        foreach ($permissions as $action => $displayName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => "warehouse.stock-issues.{$action}"],
                ['display_name' => $displayName, 'module' => 'warehouse', 'description' => $displayName . '.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'warehouse.stock-issues'],
            ['display_name' => 'Stock Issues', 'module' => 'warehouse', 'section' => 'warehouse', 'route_name' => 'warehouse.stock-issues', 'route_path' => '/warehouse/stock-issues', 'icon' => 'pi pi-exclamation-triangle', 'display_order' => 8, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]
        );

        $navigationId = DB::table('navigation_items')->where('name', 'warehouse.stock-issues')->value('id');
        $viewId = DB::table('permissions')->where('name', 'warehouse.stock-issues.view')->value('id');
        if ($navigationId && $viewId) {
            DB::table('navigation_permissions')->updateOrInsert(['navigation_item_id' => $navigationId, 'permission_id' => $viewId], ['created_at' => $now, 'updated_at' => $now]);
        }

        $roleIds = DB::table('roles')->whereIn('name', ['super_admin', 'store_admin', 'owner', 'warehouse_manager'])->pluck('id');
        $permissionIds = DB::table('permissions')->where('name', 'like', 'warehouse.stock-issues.%')->pluck('id');
        foreach ($roleIds as $roleId) foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], ['created_at' => $now, 'updated_at' => $now]);
        }

        foreach (DB::table('users')->select('id', 'store_id')->get() as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')->where('name', 'warehouse.stock-issues')->value('id');
        if ($navigationId) DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        DB::table('navigation_items')->where('name', 'warehouse.stock-issues')->delete();
        $ids = DB::table('permissions')->where('name', 'like', 'warehouse.stock-issues.%')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
