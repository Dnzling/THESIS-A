<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobHiringPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Job Hiring Permissions
        $permissions = [
            ['name' => 'view-job-postings', 'display_name' => 'View Job Postings', 'description' => 'View all job postings and details', 'module' => 'job-hiring'],
            ['name' => 'create-job-postings', 'display_name' => 'Create Job Postings', 'description' => 'Create new job postings', 'module' => 'job-hiring'],
            ['name' => 'edit-job-postings', 'display_name' => 'Edit Job Postings', 'description' => 'Edit existing job postings', 'module' => 'job-hiring'],
            ['name' => 'delete-job-postings', 'display_name' => 'Delete Job Postings', 'description' => 'Delete job postings', 'module' => 'job-hiring'],
            
            ['name' => 'view-job-applications', 'display_name' => 'View Job Applications', 'description' => 'View all job applications', 'module' => 'job-hiring'],
            ['name' => 'update-application-status', 'display_name' => 'Update Application Status', 'description' => 'Update status of job applications', 'module' => 'job-hiring'],
            ['name' => 'delete-job-applications', 'display_name' => 'Delete Job Applications', 'description' => 'Delete job applications', 'module' => 'job-hiring'],
            
            ['name' => 'view-interviews', 'display_name' => 'View Interviews', 'description' => 'View interview records', 'module' => 'job-hiring'],
            ['name' => 'schedule-interviews', 'display_name' => 'Schedule Interviews', 'description' => 'Schedule and create interviews', 'module' => 'job-hiring'],
            ['name' => 'update-interviews', 'display_name' => 'Update Interviews', 'description' => 'Update interview feedback and scores', 'module' => 'job-hiring'],
            ['name' => 'delete-interviews', 'display_name' => 'Delete Interviews', 'description' => 'Delete interview records', 'module' => 'job-hiring'],
            
            ['name' => 'view-job-offers', 'display_name' => 'View Job Offers', 'description' => 'View job offers', 'module' => 'job-hiring'],
            ['name' => 'create-job-offers', 'display_name' => 'Create Job Offers', 'description' => 'Create job offers for candidates', 'module' => 'job-hiring'],
            ['name' => 'edit-job-offers', 'display_name' => 'Edit Job Offers', 'description' => 'Edit job offers', 'module' => 'job-hiring'],
            ['name' => 'delete-job-offers', 'display_name' => 'Delete Job Offers', 'description' => 'Delete job offers', 'module' => 'job-hiring'],
            ['name' => 'accept-offers', 'display_name' => 'Accept Job Offers', 'description' => 'Accept offers (triggers employee creation)', 'module' => 'job-hiring'],
            ['name' => 'decline-offers', 'display_name' => 'Decline Job Offers', 'description' => 'Decline job offers', 'module' => 'job-hiring'],
        ];

        // Insert permissions
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'module' => $permission['module'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Assign all permissions to HR Manager + Store Admin + Owner roles
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('id');

        $roleNames = ['HR Manager', 'hr_manager', 'store_admin', 'owner'];
        $roles = DB::table('roles')->whereIn('name', $roleNames)->get();

        foreach ($roles as $role) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Assign view-job-postings only to Employee role
        $employeeRole = DB::table('roles')->where('name', 'Employee')->first();
        if ($employeeRole) {
            $viewJobPostingsId = DB::table('permissions')
                ->where('name', 'view-job-postings')
                ->first()?->id;

            if ($viewJobPostingsId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $employeeRole->id, 'permission_id' => $viewJobPostingsId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        echo "✅ Job Hiring Permissions seeded successfully!\n";
    }
}
