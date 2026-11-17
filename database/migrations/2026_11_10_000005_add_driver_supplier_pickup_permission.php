<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSION = 'logistics.driver.supplier-pickups';

    public function up(): void
    {
        $now = now();
        $description = 'Update status and submit pickup proof for supplier pickups assigned to the driver.';

        DB::table('permissions')->updateOrInsert(
            ['name' => self::PERMISSION],
            [
                'display_name' => 'Process Assigned Supplier Pickups',
                'module' => 'logistics',
                'description' => $description,
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $permissionId = (int) DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        $moduleId = DB::table('modules')->where('key', 'logistics')->value('id');

        if ($moduleId && Schema::hasTable('module_permissions')) {
            DB::table('module_permissions')->updateOrInsert(
                ['module_id' => $moduleId, 'permission_key' => self::PERMISSION],
                ['description' => $description, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
            DB::table('plan_permissions')->updateOrInsert(
                ['plan_id' => $planId, 'permission_id' => $permissionId],
                ['included' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        foreach (DB::table('roles')->where('name', 'owner')->pluck('id') as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        DB::table('users')->select('id', 'store_id')->orderBy('id')->chunk(100, function ($users): void {
            foreach ($users as $user) {
                Cache::forget("permissions:user:{$user->id}:store:global");
                if ($user->store_id) {
                    Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
                }
            }
        });
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        if (!$permissionId) {
            return;
        }

        DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('plan_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('module_permissions')->where('permission_key', self::PERMISSION)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
