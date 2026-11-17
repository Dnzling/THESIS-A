<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $moduleId = DB::table('modules')->where('key', 'merchandising')->value('id');
        $planIds = DB::table('subscription_plans')->pluck('id');
        $navigationPermissionIds = DB::table('navigation_items as navigation')
            ->join('navigation_permissions as links', 'links.navigation_item_id', '=', 'navigation.id')
            ->join('permissions', 'permissions.id', '=', 'links.permission_id')
            ->where('navigation.module', 'merchandising')
            ->where('navigation.is_active', true)
            ->whereNull('navigation.deleted_at')
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->distinct()
            ->pluck('permissions.id');

        if ($moduleId) {
            foreach ($planIds as $planId) {
                DB::table('plan_modules')->updateOrInsert(
                    ['plan_id' => $planId, 'module_id' => $moduleId],
                    ['included' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        $ownerRoleIds = DB::table('roles')
            ->where('name', 'owner')
            ->whereNull('store_id')
            ->pluck('id');

        foreach ($navigationPermissionIds as $permissionId) {
            foreach ($planIds as $planId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $permissionId],
                    ['included' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }

            foreach ($ownerRoleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        $ownerUsers = DB::table('users')->whereIn('role_id', $ownerRoleIds)->get(['id', 'store_id']);
        foreach ($ownerUsers as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) {
                Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            }
        }
    }

    public function down(): void
    {
        // Keep these grants on rollback; plan and role settings may have changed since migration.
    }
};
