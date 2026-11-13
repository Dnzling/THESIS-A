<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissionNames = [
            'store.expenses.view' => ['View Branch Expenses', 'View expense and maintenance requests for the assigned branch.'],
            'store.expenses.manage' => ['Manage Branch Expenses', 'Create branch expense requests and submit actual costs and receipts.'],
            'store.expenses.approve' => ['Approve Branch Expenses', 'Approve or reject branch spending requests before work begins.'],
        ];

        foreach ($permissionNames as $name => [$displayName, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'module' => 'store',
                'description' => $description,
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
        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($permissionNames))->pluck('id', 'name');

        foreach ($permissionIds as $name => $permissionId) {
            DB::table('module_permissions')->updateOrInsert(
                ['module_id' => $moduleId, 'permission_key' => $name],
                ['description' => $permissionNames[$name][1], 'is_active' => true, 'updated_at' => $now]
            );
        }

        DB::table('navigation_items')->updateOrInsert(['name' => 'store.expenses'], [
            'display_name' => 'Maintenance & Expenses',
            'module' => 'admin',
            'section' => 'Store',
            'route_name' => 'store.expenses',
            'route_path' => '/store/expenses',
            'icon' => 'pi pi-wrench',
            'parent_id' => null,
            'display_order' => 26,
            'is_active' => true,
            'deleted_at' => null,
            'meta' => json_encode(['subtitle' => 'Track branch maintenance and operating expenses']),
            'updated_at' => $now,
        ]);

        $navigationId = DB::table('navigation_items')->where('name', 'store.expenses')->value('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                ['updated_at' => $now]
            );
        }

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
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
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now]
                );
            }
        }

        DB::table('users')->whereNotNull('store_id')->get(['id', 'store_id'])->each(function ($user): void {
            Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            Cache::forget("permissions:user:{$user->id}:store:global");
        });
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', [
            'store.expenses.view', 'store.expenses.manage', 'store.expenses.approve',
        ])->pluck('id');
        $navigationId = DB::table('navigation_items')->where('name', 'store.expenses')->value('id');
        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('plan_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('module_permissions')->whereIn('permission_key', [
            'store.expenses.view', 'store.expenses.manage', 'store.expenses.approve',
        ])->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
