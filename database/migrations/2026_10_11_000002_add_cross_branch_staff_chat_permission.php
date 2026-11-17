<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'admin.chats.cross-branch'],
            [
                'display_name' => 'Chat Across Branches',
                'module' => 'admin',
                'description' => 'Message staff in other branches of the same store',
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'admin.chats.cross-branch')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
