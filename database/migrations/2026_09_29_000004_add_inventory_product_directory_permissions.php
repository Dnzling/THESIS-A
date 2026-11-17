<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $definitions = [
            'inventory.product.view' => 'View Inventory Product Directory',
            'inventory.product.manage' => 'Manage Inventory Product Directory',
        ];

        foreach ($definitions as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'module' => 'inventory',
                    'description' => 'Access the simplified Inventory product catalog and its category, tag, variation, and import tools.',
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $viewId = DB::table('permissions')->where('name', 'inventory.product.view')->value('id');
        $manageId = DB::table('permissions')->where('name', 'inventory.product.manage')->value('id');
        $existingViewId = DB::table('permissions')->where('name', 'inventory.products.view')->value('id');
        $existingManageIds = DB::table('permissions')
            ->whereIn('name', ['inventory.products.create', 'inventory.products.update', 'inventory.products.delete'])
            ->pluck('id');

        if ($viewId && $existingViewId) {
            foreach (DB::table('role_permissions')->where('permission_id', $existingViewId)->pluck('role_id') as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $viewId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
            foreach (DB::table('plan_permissions')->where('permission_id', $existingViewId)->where('included', true)->pluck('plan_id') as $planId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $viewId],
                    ['included' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        if ($manageId && $existingManageIds->isNotEmpty()) {
            foreach (DB::table('role_permissions')->whereIn('permission_id', $existingManageIds)->distinct()->pluck('role_id') as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $manageId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
            foreach (DB::table('plan_permissions')->whereIn('permission_id', $existingManageIds)->where('included', true)->distinct()->pluck('plan_id') as $planId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $manageId],
                    ['included' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', ['inventory.product.view', 'inventory.product.manage'])
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('plan_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
