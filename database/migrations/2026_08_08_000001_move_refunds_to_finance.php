<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach (['view', 'manage', 'approve', 'delete'] as $action) {
            DB::table('permissions')->updateOrInsert(
                ['name' => "finance.refunds.{$action}"],
                [
                    'display_name' => 'Finance Refunds ' . ucfirst($action),
                    'module' => 'finance',
                    'description' => "{$action} refunds in finance module.",
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'finance.refunds'],
            [
                'display_name' => 'Refunds',
                'module' => 'finance',
                'section' => 'refunds',
                'route_name' => 'finance.refunds',
                'route_path' => '/finance/refunds',
                'icon' => 'pi pi-replay',
                'display_order' => 8,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('navigation_items')->where('name', 'sales.refunds')->update([
            'is_active' => 0,
            'updated_at' => $now,
        ]);

        $navigationId = DB::table('navigation_items')->where('name', 'finance.refunds')->value('id');
        $viewPermissionId = DB::table('permissions')->where('name', 'finance.refunds.view')->value('id');
        if ($navigationId && $viewPermissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $viewPermissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $roleIds = DB::table('roles')->whereIn('name', ['super_admin', 'store_admin', 'owner', 'accountant'])->pluck('id');
        $permissionIds = DB::table('permissions')->where('name', 'like', 'finance.refunds.%')->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        // PermissionService caches each user's flattened permission list for
        // 30 minutes. Invalidate those entries so the new Finance queue is
        // usable immediately after deployment, without requiring re-login.
        foreach (DB::table('users')->select('id', 'store_id')->get() as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) {
                Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            }
        }
    }

    public function down(): void
    {
        DB::table('navigation_items')->where('name', 'sales.refunds')->update(['is_active' => 1]);
        DB::table('navigation_items')->where('name', 'finance.refunds')->delete();
        DB::table('permissions')->where('name', 'like', 'finance.refunds.%')->delete();
    }
};
