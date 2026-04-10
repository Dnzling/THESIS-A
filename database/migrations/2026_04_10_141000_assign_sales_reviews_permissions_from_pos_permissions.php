<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $reviewsViewPermissionId = DB::table('permissions')->where('name', 'sales.reviews.view')->value('id');
        $reviewsManagePermissionId = DB::table('permissions')->where('name', 'sales.reviews.manage')->value('id');
        $salesPosViewPermissionId = DB::table('permissions')->where('name', 'sales.pos.view')->value('id');
        $salesPosManagePermissionId = DB::table('permissions')->where('name', 'sales.pos.manage')->value('id');
        $now = now();

        if ($reviewsViewPermissionId && $salesPosViewPermissionId) {
            $roleIds = DB::table('role_permissions')
                ->where('permission_id', $salesPosViewPermissionId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $reviewsViewPermissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        if ($reviewsManagePermissionId && $salesPosManagePermissionId) {
            $roleIds = DB::table('role_permissions')
                ->where('permission_id', $salesPosManagePermissionId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $reviewsManagePermissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $reviewsViewPermissionId = DB::table('permissions')->where('name', 'sales.reviews.view')->value('id');
        $reviewsManagePermissionId = DB::table('permissions')->where('name', 'sales.reviews.manage')->value('id');

        if ($reviewsViewPermissionId) {
            DB::table('role_permissions')->where('permission_id', $reviewsViewPermissionId)->delete();
        }
        if ($reviewsManagePermissionId) {
            DB::table('role_permissions')->where('permission_id', $reviewsManagePermissionId)->delete();
        }
    }
};

