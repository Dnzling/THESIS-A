<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'merchandising.vouchers.view' => 'View Vouchers',
        'merchandising.vouchers.manage' => 'Manage Vouchers',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name => $label) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $label,
                'module' => 'merchandising',
                'description' => $label . ' in the Product Catalog.',
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSIONS))
            ->pluck('id', 'name');
        $moduleId = DB::table('modules')->where('key', 'merchandising')->value('id');

        if ($moduleId && Schema::hasTable('module_permissions')) {
            foreach (self::PERMISSIONS as $name => $label) {
                DB::table('module_permissions')->updateOrInsert(
                    ['module_id' => $moduleId, 'permission_key' => $name],
                    ['description' => $label, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $navigation = [
            'name' => 'merchandising.vouchers',
            'display_name' => 'Vouchers',
            'module' => 'merchandising',
            'section' => null,
            'route_name' => 'merchandising.vouchers',
            'route_path' => '/merchandising/vouchers',
            'icon' => 'pi pi-ticket',
            'parent_id' => null,
            'display_order' => 5,
            'is_active' => true,
            'meta' => json_encode(['subtitle' => 'Manage voucher codes for customer orders']),
            'deleted_at' => null,
            'updated_at' => $now,
        ];
        DB::table('navigation_items')->updateOrInsert(
            ['name' => $navigation['name']],
            $navigation + ['created_at' => $now]
        );

        $navigationId = DB::table('navigation_items')->where('name', 'merchandising.vouchers')->value('id');
        if ($navigationId) {
            $pricingViewId = DB::table('permissions')->where('name', 'merchandising.pricing.view')->value('id');
            if ($pricingViewId) {
                DB::table('navigation_permissions')
                    ->where('navigation_item_id', $navigationId)
                    ->where('permission_id', $pricingViewId)
                    ->delete();
            }

            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionIds['merchandising.vouchers.view']],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('navigation_items')->where('name', 'merchandising.assets')->update(['display_order' => 6, 'updated_at' => $now]);
        DB::table('navigation_items')->where('name', 'merchandising.reports')->update(['display_order' => 7, 'updated_at' => $now]);

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
            if ($moduleId && Schema::hasTable('plan_modules')) {
                DB::table('plan_modules')->updateOrInsert(
                    ['plan_id' => $planId, 'module_id' => $moduleId],
                    ['included' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $permissionId],
                    ['included' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $ownerRoleIds = DB::table('roles')->where('name', 'owner')->whereNull('store_id')->pluck('id');
        foreach ($ownerRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
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
        $permissionIds = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');
        $navigationId = DB::table('navigation_items')->where('name', 'merchandising.vouchers')->value('id');

        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }

        DB::table('plan_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        if (Schema::hasTable('module_permissions')) {
            DB::table('module_permissions')->whereIn('permission_key', array_keys(self::PERMISSIONS))->delete();
        }
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        DB::table('navigation_items')->where('name', 'merchandising.assets')->update(['display_order' => 5]);
        DB::table('navigation_items')->where('name', 'merchandising.reports')->update(['display_order' => 6]);
    }
};
