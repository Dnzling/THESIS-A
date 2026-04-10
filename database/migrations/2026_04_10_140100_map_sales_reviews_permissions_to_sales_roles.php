<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $viewPermissionId = DB::table('permissions')->where('name', 'sales.reviews.view')->value('id');
        $managePermissionId = DB::table('permissions')->where('name', 'sales.reviews.manage')->value('id');
        $ordersViewPermissionId = DB::table('permissions')->where('name', 'sales.orders.view')->value('id');
        $ordersManagePermissionId = DB::table('permissions')->where('name', 'sales.orders.manage')->value('id');
        $now = now();

        if ($viewPermissionId && $ordersViewPermissionId) {
            $roleIds = DB::table('role_permissions')
                ->where('permission_id', $ordersViewPermissionId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $viewPermissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        if ($managePermissionId && $ordersManagePermissionId) {
            $roleIds = DB::table('role_permissions')
                ->where('permission_id', $ordersManagePermissionId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $managePermissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $viewPermissionId = DB::table('permissions')->where('name', 'sales.reviews.view')->value('id');
        $managePermissionId = DB::table('permissions')->where('name', 'sales.reviews.manage')->value('id');

        if ($viewPermissionId) {
            DB::table('role_permissions')->where('permission_id', $viewPermissionId)->delete();
        }
        if ($managePermissionId) {
            DB::table('role_permissions')->where('permission_id', $managePermissionId)->delete();
        }
    }
};

