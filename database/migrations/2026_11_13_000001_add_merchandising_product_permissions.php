<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'merchandising.products.view' => 'View Products',
        'merchandising.products.manage' => 'Manage Products',
        'merchandising.products.approve' => 'Approve Product Price Changes',
        'merchandising.products.delete' => 'Delete Products',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'module' => 'merchandising',
                'description' => $displayName . ' in the Product Catalog.',
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('module_permissions')) {
            $moduleId = DB::table('modules')->where('key', 'merchandising')->value('id');
            if ($moduleId) {
                foreach (self::PERMISSIONS as $name => $displayName) {
                    DB::table('module_permissions')->updateOrInsert(
                        ['module_id' => $moduleId, 'permission_key' => $name],
                        ['description' => $displayName, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }

        $approveId = DB::table('permissions')->where('name', 'merchandising.products.approve')->value('id');
        $manageId = DB::table('permissions')->where('name', 'merchandising.products.manage')->value('id');

        if ($approveId && $manageId) {
            foreach (DB::table('role_permissions')->where('permission_id', $manageId)->pluck('role_id') as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $approveId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }

            foreach (DB::table('plan_permissions')->where('permission_id', $manageId)->where('included', true)->pluck('plan_id') as $planId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $approveId],
                    ['included' => true, 'created_at' => $now, 'updated_at' => $now]
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
        $name = 'merchandising.products.approve';
        $id = DB::table('permissions')->where('name', $name)->value('id');

        if ($id) {
            DB::table('role_permissions')->where('permission_id', $id)->delete();
            DB::table('plan_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
        if (Schema::hasTable('module_permissions')) {
            DB::table('module_permissions')->where('permission_key', $name)->delete();
        }
    }
};
