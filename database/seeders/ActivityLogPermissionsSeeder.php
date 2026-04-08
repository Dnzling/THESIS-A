<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permission = DB::table('permissions')->where('name', 'hr.activity-logs.view')->first();
        if (!$permission) {
            return;
        }

        $roleNames = ['super_admin', 'store_admin', 'hr_manager', 'HR Manager'];
        $roles = DB::table('roles')->whereIn('name', $roleNames)->get();

        foreach ($roles as $role) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $role->id,
                'permission_id' => $permission->id
            ], [
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
