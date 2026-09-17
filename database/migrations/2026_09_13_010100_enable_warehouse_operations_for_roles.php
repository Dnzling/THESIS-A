<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('modules')->updateOrInsert(
            ['key' => 'warehouse'],
            [
                'name' => 'Warehouse Operations',
                'description' => 'Warehouse stock, receiving, and transfer operations.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $moduleId = (int) DB::table('modules')->where('key', 'warehouse')->value('id');

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
            DB::table('plan_modules')->updateOrInsert(
                ['plan_id' => $planId, 'module_id' => $moduleId],
                ['included' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        foreach (DB::table('stores')->pluck('id') as $storeId) {
            DB::table('store_modules')->updateOrInsert(
                ['store_id' => $storeId, 'module_id' => $moduleId],
                [
                    'status' => 'enabled',
                    'source' => 'plan',
                    'enabled_at' => $now,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->where('module', 'warehouse')
            ->where('is_active', true)
            ->pluck('id');
        $storeAdminRoleIds = DB::table('roles')->where('name', 'store_admin')->pluck('id');

        foreach ($storeAdminRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Non-destructive because stores and plans can customize module access after deployment.
    }
};
