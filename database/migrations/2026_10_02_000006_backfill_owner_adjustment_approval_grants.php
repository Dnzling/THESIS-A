<?php

use App\Models\Core\User;
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

        $roleIds = DB::table('roles as r')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'r.id')
            ->whereIn('r.name', ['owner', 'super_admin'])
            ->whereIn('rp.permission_id', $managementPermissionIds)
            ->distinct()
            ->pluck('r.id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $approveId],
                ['created_at' => $now, 'updated_at' => $now]
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
