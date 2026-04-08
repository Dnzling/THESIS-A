<?php

namespace Database\Seeders;

use App\Models\Core\NavigationItem;
use App\Models\Core\Permission;
use Illuminate\Database\Seeder;

class JobHiringNavigationSeeder extends Seeder
{
    public function run(): void
    {
        // Get the last display order for HR module
        $lastOrder = NavigationItem::where('module', 'hr')->max('display_order') ?? 0;

        // Create Job Hiring parent item
        $jobHiringParent = NavigationItem::firstOrCreate(
            ['name' => 'hr.job-hiring'],
            [
                'display_name' => 'Job Hiring',
                'module' => 'hr',
                'route_name' => 'hr.job-hiring',
                'route_path' => '/hr/job-hiring',
                'icon' => 'pi pi-briefcase',
                'parent_id' => null,
                'display_order' => $lastOrder + 1,
                'is_active' => true,
                'meta' => [
                    'subtitle' => 'Manage job postings and applications',
                ],
            ]
        );

        // Create Job Postings child item
        $jobPostings = NavigationItem::firstOrCreate(
            ['name' => 'hr.job-hiring.job-postings'],
            [
                'display_name' => 'Job Postings',
                'module' => 'hr',
                'route_name' => 'hr.job-hiring.job-postings',
                'route_path' => '/hr/job-hiring/job-postings',
                'icon' => 'pi pi-list',
                'parent_id' => $jobHiringParent->id,
                'display_order' => 1,
                'is_active' => true,
                'meta' => [
                    'subtitle' => 'Manage and view job postings',
                ],
            ]
        );

        // Consolidated recruitment permissions
        $recruitView = Permission::where('name', 'hr.recruitment.view')->first();
        $recruitManage = Permission::where('name', 'hr.recruitment.manage')->first();
        $recruitApprove = Permission::where('name', 'hr.recruitment.approve')->first();
        $recruitDelete = Permission::where('name', 'hr.recruitment.delete')->first();

        // Link permissions to Job Postings navigation item
        $permissionsToLink = array_filter([
            $recruitView?->id,
            $recruitManage?->id,
            $recruitApprove?->id,
            $recruitDelete?->id,
        ]);

        if ($permissionsToLink) {
            $jobPostings->permissions()->syncWithoutDetaching($permissionsToLink);
        }

        // Create Screening Pipeline child item
        $screeningPipeline = NavigationItem::firstOrCreate(
            ['name' => 'hr.job-hiring.screening-pipeline'],
            [
                'display_name' => 'Screening Pipeline',
                'module' => 'hr',
                'route_name' => 'hr.job-hiring.screening-pipeline',
                'route_path' => '/hr/job-hiring/postings/:postingId/screening',
                'icon' => 'pi pi-sitemap',
                'parent_id' => $jobHiringParent->id,
                'display_order' => 2,
                'is_active' => true,
                'meta' => [
                    'subtitle' => 'View and manage candidate screening pipeline',
                ],
            ]
        );

        // Link permissions to Screening Pipeline navigation item
        $screeningPermissions = array_filter([
            $recruitView?->id,
            $recruitManage?->id,
            $recruitApprove?->id,
        ]);

        if ($screeningPermissions) {
            $screeningPipeline->permissions()->syncWithoutDetaching($screeningPermissions);
        }

        echo "✅ Job Hiring Navigation Items seeded successfully!\n";
    }
}
