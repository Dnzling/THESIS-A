<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach (['view', 'manage', 'approve', 'delete'] as $action) {
            DB::table('permissions')->updateOrInsert(
                ['name' => "finance.liquidations.{$action}"],
                [
                    'display_name' => 'Finance Liquidations ' . ucfirst($action),
                    'module' => 'finance',
                    'description' => ucfirst($action) . ' cash advances and liquidations.',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'finance.liquidations'],
            [
                'display_name' => 'Liquidations',
                'module' => 'finance',
                'section' => 'liquidations',
                'route_name' => 'finance.liquidations',
                'route_path' => '/finance/liquidations',
                'icon' => 'pi pi-receipt',
                'display_order' => 9,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $navigationId = DB::table('navigation_items')->where('name', 'finance.liquidations')->value('id');
        $viewPermissionId = DB::table('permissions')->where('name', 'finance.liquidations.view')->value('id');
        if ($navigationId && $viewPermissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $viewPermissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $roleIds = DB::table('roles')->whereIn('name', ['super_admin', 'store_admin', 'owner', 'accountant'])->pluck('id');
        $permissionIds = DB::table('permissions')->where('name', 'like', 'finance.liquidations.%')->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        foreach (DB::table('users')->select('id', 'store_id')->get() as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')->where('name', 'finance.liquidations')->value('id');
        if ($navigationId) DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        DB::table('navigation_items')->where('name', 'finance.liquidations')->delete();
        $permissionIds = DB::table('permissions')->where('name', 'like', 'finance.liquidations.%')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
