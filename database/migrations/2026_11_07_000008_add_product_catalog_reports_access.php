<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'merchandising.reports.view' => 'View Product Catalog Reports',
            'merchandising.reports.manage' => 'Manage Product Catalog Reports',
        ];

        foreach ($permissions as $name => $label) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $label,
                'module' => 'merchandising',
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($permissions))->pluck('id', 'name');
        $moduleId = DB::table('modules')->where('key', 'merchandising')->value('id');
        if ($moduleId && Schema::hasTable('module_permissions')) {
            foreach ($permissions as $name => $label) {
                DB::table('module_permissions')->updateOrInsert(
                    ['module_id' => $moduleId, 'permission_key' => $name],
                    ['description' => $label, 'is_active' => true, 'updated_at' => $now]
                );
            }
        }

        $navigation = [
            'name' => 'merchandising.reports',
            'display_name' => 'Reports',
            'module' => 'merchandising',
            'section' => null,
            'route_name' => 'merchandising.reports',
            'route_path' => '/merchandising/reports',
            'icon' => 'pi pi-chart-bar',
            'display_order' => 6,
            'is_active' => true,
            'deleted_at' => null,
            'updated_at' => $now,
        ];
        DB::table('navigation_items')->updateOrInsert(['name' => $navigation['name']], $navigation + ['created_at' => $now]);

        $navigationId = DB::table('navigation_items')->where('name', $navigation['name'])->value('id');
        $viewPermissionId = $permissionIds['merchandising.reports.view'] ?? null;
        if ($navigationId && $viewPermissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $viewPermissionId],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
            if ($moduleId) {
                DB::table('plan_modules')->updateOrInsert(
                    ['plan_id' => $planId, 'module_id' => $moduleId],
                    ['included' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
            foreach ($permissionIds as $permissionId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $permissionId],
                    ['included' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        foreach (DB::table('roles')->where('name', 'owner')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        DB::table('users')->whereNotNull('store_id')->select(['id', 'store_id'])->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    Cache::forget("permissions:user:{$user->id}:store:global");
                    Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
                }
            });
    }

    public function down(): void
    {
        // Preserve access grants and navigation changes if user or plan settings changed later.
    }
};
