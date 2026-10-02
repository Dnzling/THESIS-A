<?php

use App\Services\Core\PermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $manageId = DB::table('permissions')->where('name', 'inventory.adjustments.manage')->value('id');
        $approveId = DB::table('permissions')->where('name', 'inventory.adjustments.approve')->value('id');
        if (!$manageId || !$approveId) {
            throw new RuntimeException('Adjustment management or approval permission is missing.');
        }

        $planIds = DB::table('plan_permissions')
            ->where('permission_id', $manageId)
            ->where('included', true)
            ->pluck('plan_id');

        foreach ($planIds as $planId) {
            DB::table('plan_permissions')->updateOrInsert(
                ['plan_id' => $planId, 'permission_id' => $approveId],
                ['included' => true, 'created_at' => now(), 'updated_at' => now()]
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
