<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecruitmentPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'hr.recruitment.view', 'display_name' => 'Recruitment View', 'description' => 'View recruitment pipeline', 'module' => 'hr'],
            ['name' => 'hr.recruitment.manage', 'display_name' => 'Recruitment Manage', 'description' => 'Create and update recruitment items', 'module' => 'hr'],
            ['name' => 'hr.recruitment.approve', 'display_name' => 'Recruitment Approve', 'description' => 'Approve/advance recruitment decisions', 'module' => 'hr'],
            ['name' => 'hr.recruitment.delete', 'display_name' => 'Recruitment Delete', 'description' => 'Delete recruitment records', 'module' => 'hr'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('id');

        $roleNames = ['HR Manager', 'hr_manager', 'store_admin', 'owner', 'super_admin'];
        $roles = DB::table('roles')->whereIn('name', $roleNames)->get();

        foreach ($roles as $role) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
