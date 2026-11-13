<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'merchandising.tags.manage'],
            [
                'display_name' => 'Manage Collections',
                'module' => 'merchandising',
                'description' => 'Create, update, and remove storefront product collections.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );

        $permissionId = DB::table('permissions')->where('name', 'merchandising.tags.manage')->value('id');
        $roleIds = DB::table('roles')->whereIn('name', ['owner', 'super_admin', 'store_manager'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'merchandising.tags.manage')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
