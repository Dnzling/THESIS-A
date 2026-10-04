<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'warehouse.transfers.approve' => 'Approve Warehouse Transfers',
            'warehouse.transfers.reject' => 'Reject Warehouse Transfers',
        ];

        foreach ($permissions as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'module' => 'warehouse',
                'description' => 'Warehouse transfer request action.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')->whereIn('name', ['super_admin', 'store_admin'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($permissions))->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        // Non-destructive because stores may assign these permissions to custom roles.
    }
};
