<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RETIRED = [
        'admin.verification.manage',
        'store.ecommerce.view',
        'store.ecommerce.manage',
    ];

    private const EXPENSE_PERMISSIONS = [
        'store.expenses.view',
        'store.expenses.manage',
        'store.expenses.approve',
    ];

    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(['name' => 'admin.maintenance.manage'], [
            'display_name' => 'Manage Maintenance & Expenses',
            'module' => 'admin',
            'description' => 'Access and manage branch maintenance and expense requests.',
            'is_active' => true,
            'deleted_at' => null,
            'updated_at' => $now,
        ]);

        $moduleId = DB::table('modules')->where('key', 'admin')->value('id');
        if ($moduleId) {
            DB::table('module_permissions')->updateOrInsert(
                ['module_id' => $moduleId, 'permission_key' => 'admin.maintenance.manage'],
                ['description' => 'Manage Maintenance & Expenses', 'is_active' => true, 'updated_at' => $now]
            );
        }

        $maintenanceId = DB::table('permissions')->where('name', 'admin.maintenance.manage')->value('id');
        $expenseIds = DB::table('permissions')->whereIn('name', self::EXPENSE_PERMISSIONS)->pluck('id');

        // Preserve existing access while moving the page's permission to the Admin module.
        foreach (DB::table('role_permissions')->whereIn('permission_id', $expenseIds)->distinct()->pluck('role_id') as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $maintenanceId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
        foreach (DB::table('plan_permissions')->whereIn('permission_id', $expenseIds)->where('included', true)->distinct()->pluck('plan_id') as $planId) {
            DB::table('plan_permissions')->updateOrInsert(
                ['plan_id' => $planId, 'permission_id' => $maintenanceId],
                ['included' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $navigationId = DB::table('navigation_items')->where('name', 'store.expenses')->value('id');
        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_permissions')->insertOrIgnore([
                'navigation_item_id' => $navigationId,
                'permission_id' => $maintenanceId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $retiredIds = DB::table('permissions')->whereIn('name', self::RETIRED)->pluck('id');
        DB::table('permissions')->whereIn('id', $retiredIds)->update([
            'is_active' => false,
            'deleted_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('module_permissions')->whereIn('permission_key', self::RETIRED)->update([
            'is_active' => false,
            'updated_at' => $now,
        ]);

        $this->clearPermissionCaches();
    }

    public function down(): void
    {
        $now = now();
        DB::table('permissions')->whereIn('name', self::RETIRED)->update([
            'is_active' => true,
            'deleted_at' => null,
            'updated_at' => $now,
        ]);
        DB::table('module_permissions')->whereIn('permission_key', self::RETIRED)->update([
            'is_active' => true,
            'updated_at' => $now,
        ]);

        $navigationId = DB::table('navigation_items')->where('name', 'store.expenses')->value('id');
        $maintenanceId = DB::table('permissions')->where('name', 'admin.maintenance.manage')->value('id');
        if ($navigationId && $maintenanceId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->where('permission_id', $maintenanceId)->delete();
            foreach (DB::table('permissions')->whereIn('name', self::EXPENSE_PERMISSIONS)->pluck('id') as $permissionId) {
                DB::table('navigation_permissions')->insertOrIgnore([
                    'navigation_item_id' => $navigationId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($maintenanceId) {
            DB::table('role_permissions')->where('permission_id', $maintenanceId)->delete();
            DB::table('plan_permissions')->where('permission_id', $maintenanceId)->delete();
            DB::table('module_permissions')->where('permission_key', 'admin.maintenance.manage')->delete();
            DB::table('permissions')->where('id', $maintenanceId)->delete();
        }

        $this->clearPermissionCaches();
    }

    private function clearPermissionCaches(): void
    {
        DB::table('users')->select(['id', 'store_id'])->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                Cache::forget("permissions:user:{$user->id}:store:global");
                if ($user->store_id) {
                    Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
                }
            }
        });
    }
};
