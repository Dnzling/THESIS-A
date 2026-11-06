<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $groups = [
            'sales.wholesale' => ['module' => 'sales', 'label' => 'Wholesale Quotations', 'navigation' => 'sales.wholesale-quotes'],
            'inventory.stock-requests' => ['module' => 'inventory', 'label' => 'Stock Requests', 'navigation' => 'inventory.stock-requests'],
        ];

        foreach ($groups as $prefix => $group) {
            foreach (['view', 'manage', 'approve'] as $action) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => "$prefix.$action"],
                    ['display_name' => ucfirst($action) . ' ' . $group['label'], 'module' => $group['module'], 'is_active' => true, 'deleted_at' => null, 'updated_at' => $now, 'created_at' => $now]
                );
                $permissionId = DB::table('permissions')->where('name', "$prefix.$action")->value('id');
                foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
                    DB::table('plan_permissions')->updateOrInsert(
                        ['plan_id' => $planId, 'permission_id' => $permissionId],
                        ['included' => true, 'updated_at' => $now, 'created_at' => $now]
                    );
                }
                foreach (DB::table('roles')->where('name', 'owner')->pluck('id') as $roleId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $permissionId],
                        ['updated_at' => $now, 'created_at' => $now]
                    );
                }
            }

            $navigationId = DB::table('navigation_items')->where('name', $group['navigation'])->value('id');
            $viewPermissionId = DB::table('permissions')->where('name', "$prefix.view")->value('id');
            if ($navigationId && $viewPermissionId) {
                DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
                DB::table('navigation_permissions')->insert([
                    'navigation_item_id' => $navigationId, 'permission_id' => $viewPermissionId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $ownerRoleIds = DB::table('roles')->where('name', 'owner')->pluck('id');
        DB::table('users')->whereIn('role_id', $ownerRoleIds)->select(['id', 'store_id'])
            ->orderBy('id')->chunkById(100, function ($owners): void {
                foreach ($owners as $owner) {
                    Cache::forget("permissions:user:{$owner->id}:store:global");
                    if ($owner->store_id) {
                        Cache::forget("permissions:user:{$owner->id}:store:{$owner->store_id}");
                    }
                }
            });
    }

    public function down(): void
    {
        // Preserve permissions and grants assigned after this migration ran.
    }
};
