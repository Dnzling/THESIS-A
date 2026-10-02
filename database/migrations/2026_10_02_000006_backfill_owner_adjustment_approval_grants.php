<?php

use App\Models\Core\User;
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

        $roleIds = DB::table('roles as r')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'r.id')
            ->whereIn('r.name', ['owner', 'super_admin'])
            ->where('rp.permission_id', $manageId)
            ->pluck('r.id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $approveId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $permissions = app(PermissionService::class);
        User::query()->whereIn('role_id', $roleIds)->get()->each(fn (User $user) => $permissions->clearUserCache($user));
    }

    public function down(): void
    {
        // Preserve any owner approval grants assigned after this migration.
    }
};
