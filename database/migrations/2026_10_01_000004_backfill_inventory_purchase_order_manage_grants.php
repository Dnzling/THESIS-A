<?php

use App\Services\Core\PermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sourceId = DB::table('permissions')->where('name', 'inventory.products.manage')->value('id');
        $targetId = DB::table('permissions')->where('name', 'inventory.purchase_orders.manage')->value('id');
        if (!$sourceId || !$targetId) {
            return;
        }

        $now = now();
        foreach (DB::table('role_permissions')->where('permission_id', $sourceId)->pluck('role_id') as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $targetId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $planIds = DB::table('plan_permissions')
            ->where('permission_id', $sourceId)
            ->where('included', true)
            ->pluck('plan_id');
        foreach ($planIds as $planId) {
            DB::table('plan_permissions')->updateOrInsert(
                ['plan_id' => $planId, 'permission_id' => $targetId],
                ['included' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $permissions = app(PermissionService::class);
        DB::table('stores')->whereIn('subscription_tier', $planIds)->pluck('id')
            ->each(fn ($storeId) => $permissions->clearStoreCache((int) $storeId));
    }

    public function down(): void
    {
        // Keep grants on rollback: they may have been assigned independently after this backfill.
    }
};
