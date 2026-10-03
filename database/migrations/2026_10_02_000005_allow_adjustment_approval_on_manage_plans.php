<?php

use App\Services\Core\PermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'inventory.adjustments.approve'],
            [
                'display_name' => 'Approve Stock Adjustments',
                'module' => 'inventory',
                'description' => 'Approve stock adjustments.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        $approveId = DB::table('permissions')->where('name', 'inventory.adjustments.approve')->value('id');
        $managementPermissionIds = DB::table('permissions')
            ->whereIn('name', ['inventory.adjustments.manage', 'inventory.adjustments.create'])
            ->pluck('id');
        if ($managementPermissionIds->isEmpty()) {
            return;
        }

        $planIds = DB::table('plan_permissions')
            ->whereIn('permission_id', $managementPermissionIds)
            ->where('included', true)
            ->distinct()
            ->pluck('plan_id');

        foreach ($planIds as $planId) {
            DB::table('plan_permissions')->updateOrInsert(
                ['plan_id' => $planId, 'permission_id' => $approveId],
                ['included' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $permissions = app(PermissionService::class);
        DB::table('stores')->whereIn('subscription_tier', $planIds)->pluck('id')
            ->each(fn ($storeId) => $permissions->clearStoreCache((int) $storeId));
    }

    public function down(): void
    {
        // Preserve grants, which may have been edited after this migration ran.
    }
};
