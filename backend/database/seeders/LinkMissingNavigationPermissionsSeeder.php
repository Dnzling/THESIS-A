<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LinkMissingNavigationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $insertedCount = 0;

        // ========== SUPPLIER PORTAL PERMISSION MAPPINGS ==========
        $supplierPortalMappings = [
            'supplier-portal.dashboard' => ['supplier-portal.dashboard.view'],
            'supplier-portal.registration' => ['supplier-portal.registration.view', 'supplier-portal.registration.submit'],
            'supplier-portal.rfqs' => ['supplier-portal.rfqs.view'],
            'supplier-portal.rfqs.detail' => ['supplier-portal.rfqs.view', 'supplier-portal.rfqs.respond'],
            'supplier-portal.pos' => ['supplier-portal.pos.view'],
            'supplier-portal.pos.detail' => ['supplier-portal.pos.view', 'supplier-portal.pos.respond'],
            'supplier-verifications' => ['supplier-verifications.view', 'supplier-verifications.approve', 'supplier-verifications.reject'],
        ];

        // ========== HR PERMISSION MAPPINGS ==========
        $hrMappings = [
            'hr.dashboard' => ['hr.dashboard.view'],
            'hr.employees' => ['hr.employees.view', 'hr.employees.create'],
            'hr.employees.view' => ['hr.employees.view'],
            'hr.shifts' => ['hr.shifts.view', 'hr.shifts.create'],
            'hr.shifts.employees' => ['hr.shifts.view'],
            'hr.shifts.create' => ['hr.shifts.create'],
            'hr.attendance' => ['hr.attendance.view', 'hr.attendance.record'],
            'hr.departments' => ['hr.departments.view', 'hr.departments.manage'],
            'hr.leave' => ['hr.leave.view', 'hr.leave.request', 'hr.leave.approve'],
            'hr.leave.balances' => ['hr.leave.view'],
            'hr.analytics' => ['hr.analytics.view'],
            'hr.settings' => ['hr.settings.manage', 'hr.activity-logs.view'],
            'hr.payroll' => ['hr.payroll.view', 'hr.payroll.create'],
            'hr.payroll.overview' => ['hr.payroll.view'],
            'hr.payroll.periods' => ['hr.payroll.view'],
            'hr.payroll.list' => ['hr.payroll.view'],
            'hr.payroll.create' => ['hr.payroll.create'],
            'hr.payroll.view' => ['hr.payroll.view'],
            'hr.payroll.edit' => ['hr.payroll.edit'],
            'hr.job-postings' => ['view-job-postings'],
            'hr.screening-pipeline' => ['view-job-applications'],
            'hr.apply-job' => ['view-job-applications'],
        ];

        // ========== MERCHANDISING PERMISSION MAPPINGS ==========
        $merchandisingMappings = [
            'merchandising.assets' => ['merchandising.assets.view'],
            'merchandising.assets.upload' => ['merchandising.assets.upload'],
            'merchandising.tags' => ['merchandising.tags.view'],
            'merchandising.pricing' => ['merchandising.pricing.view'],
            'merchandising.pricing.bulk' => ['merchandising.pricing.edit'],
            'merchandising.pricing-history' => ['merchandising.reports.view'],
        ];

        // ========== FINANCE PERMISSION MAPPINGS ==========
        $financeMappings = [
            'finance.dashboard' => ['finance.transactions.view.store'],
            'finance.payables' => ['finance.documents.view.store'],
            'finance.receivables' => ['finance.documents.view.store'],
            'finance.expenses' => ['finance.transactions.view.store'],
            'finance.payroll' => ['finance.workflows.view.store'],
            'finance.budgets' => ['finance.settings.view.store'],
            'finance.reports' => ['finance.documents.view.store'],
            'finance.purchase-orders' => ['finance.purchase-orders.view'],
            'finance.purchase-orders.detail' => [
                'finance.purchase-orders.view',
                'finance.purchase-orders.approve',
                'finance.purchase-orders.reject'
            ],
        ];

        // ========== ADMIN CUSTOMER MANAGEMENT ==========
        $adminMappings = [
            'admin.customer-management' => ['admin.customers.view', 'admin.customers.require-verification'],
        ];

        // Merge all mappings
        $allMappings = array_merge(
            $supplierPortalMappings,
            $hrMappings,
            $merchandisingMappings,
            $financeMappings,
            $adminMappings
        );

        foreach ($allMappings as $navItemName => $permissions) {
            // Get navigation item
            $navItem = DB::table('navigation_items')->where('name', $navItemName)->first();
            
            if (!$navItem) {
                echo "⚠️  Navigation item not found: {$navItemName}\n";
                continue;
            }

            // Get permissions
            foreach ($permissions as $permName) {
                $permission = DB::table('permissions')->where('name', $permName)->first();
                
                if (!$permission) {
                    echo "⚠️  Permission not found: {$permName}\n";
                    continue;
                }

                // Check if link already exists
                if (!DB::table('navigation_permissions')->where([
                    'navigation_item_id' => $navItem->id,
                    'permission_id' => $permission->id,
                ])->exists()) {
                    DB::table('navigation_permissions')->insert([
                        'navigation_item_id' => $navItem->id,
                        'permission_id' => $permission->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $insertedCount++;
                    echo "✓ Linked {$navItemName} -> {$permName}\n";
                }
            }
        }

        echo "\n✅ Added {$insertedCount} navigation-permission links!\n";
    }
}
