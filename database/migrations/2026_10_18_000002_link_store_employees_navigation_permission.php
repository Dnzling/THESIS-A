<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'store.employees.view'],
            [
                'display_name' => 'View Employees',
                'module' => 'admin',
                'description' => 'View the store employee directory.',
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
            ]
        );

        $permissionId = DB::table('permissions')->where('name', 'store.employees.view')->value('id');
        $navigationId = DB::table('navigation_items')->where('name', 'store.employees')->value('id');

        if ($navigationId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                ['updated_at' => $now]
            );
        }

        foreach (DB::table('roles')->where('name', 'owner')->whereNull('store_id')->pluck('id') as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['updated_at' => $now]
            );
        }

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
            DB::table('plan_permissions')->updateOrInsert(
                ['plan_id' => $planId, 'permission_id' => $permissionId],
                ['included' => true, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        // Keep grants on rollback because plans and roles may have been edited since migration.
    }
};
