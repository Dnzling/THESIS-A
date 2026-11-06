<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'store.team.view' => 'View Branch Team',
            'store.team.manage' => 'Manage Branch Team Shifts',
        ];

        foreach ($permissions as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'module' => 'store',
                'description' => $name === 'store.team.view'
                    ? 'View employees and upcoming shifts in the assigned branch.'
                    : 'Approve or reject shift swaps in the assigned branch.',
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
        }

        DB::table('modules')->updateOrInsert(['key' => 'store'], [
            'name' => 'Store',
            'is_active' => true,
            'updated_at' => $now,
        ]);
        $moduleId = DB::table('modules')->where('key', 'store')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($permissions))->pluck('id', 'name');
        foreach ($permissionIds as $name => $permissionId) {
            DB::table('module_permissions')->updateOrInsert(
                ['module_id' => $moduleId, 'permission_key' => $name],
                ['description' => $permissions[$name], 'is_active' => true, 'updated_at' => $now]
            );
        }

        DB::table('navigation_items')->updateOrInsert(['name' => 'store.team'], [
            'display_name' => 'Team',
            'module' => 'store',
            'section' => 'Store',
            'route_name' => 'store.team',
            'route_path' => '/store/team',
            'icon' => 'pi pi-users',
            'display_order' => 25,
            'is_active' => true,
            'deleted_at' => null,
            'updated_at' => $now,
        ]);
        $navigationId = DB::table('navigation_items')->where('name', 'store.team')->value('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                ['updated_at' => $now]
            );
        }

        $planIds = DB::table('subscription_plans')->whereIn('plan_key', ['growth', 'enterprise'])->pluck('id');
        foreach ($planIds as $planId) {
            DB::table('plan_modules')->updateOrInsert(
                ['plan_id' => $planId, 'module_id' => $moduleId],
                ['included' => true, 'updated_at' => $now]
            );
            foreach ($permissionIds as $permissionId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $permissionId],
                    ['included' => true, 'updated_at' => $now]
                );
            }
        }

        $managerRoles = DB::table('roles')->whereIn('name', ['store_manager', 'branch_manager'])->pluck('id');
        foreach ($managerRoles as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now]
                );
            }
        }
        foreach (DB::table('roles')->where('name', 'owner')->pluck('id') as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionIds['store.team.view']],
                ['updated_at' => $now]
            );
        }

        DB::table('users')->whereIn('store_id', DB::table('stores')
            ->whereIn('subscription_tier', $planIds)->select('stores.id'))
            ->get(['id', 'store_id'])
            ->each(function ($user): void {
                Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
                Cache::forget("permissions:user:{$user->id}:store:global");
            });
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', ['store.team.view', 'store.team.manage'])->pluck('id');
        $navigationId = DB::table('navigation_items')->where('name', 'store.team')->value('id');
        DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        DB::table('navigation_items')->where('id', $navigationId)->delete();
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('plan_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('module_permissions')->whereIn('permission_key', ['store.team.view', 'store.team.manage'])->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
