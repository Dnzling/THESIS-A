<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AddMissingNavigationItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $insertedCount = 0;

        // ========== SUPPLIER PORTAL NAVIGATION ITEMS ==========
        $supplierPortalItems = [
            [
                'name' => 'supplier-portal.dashboard',
                'display_name' => 'Portal Dashboard',
                'module' => 'supplier',
                'section' => 'portal',
                'route_name' => 'supplier-portal.dashboard',
                'route_path' => '/supplier-portal/dashboard',
                'icon' => 'pi pi-chart-line',
                'display_order' => 1,
            ],
            [
                'name' => 'supplier-portal.registration',
                'display_name' => 'Registration',
                'module' => 'supplier',
                'section' => 'portal',
                'route_name' => 'supplier-portal.registration',
                'route_path' => '/supplier-portal/registration',
                'icon' => 'pi pi-user-plus',
                'display_order' => 2,
            ],
            [
                'name' => 'supplier-portal.rfqs',
                'display_name' => 'RFQ Requests',
                'module' => 'supplier',
                'section' => 'portal',
                'route_name' => 'supplier-portal.rfqs',
                'route_path' => '/supplier-portal/rfqs',
                'icon' => 'pi pi-file-edit',
                'display_order' => 3,
            ],
            [
                'name' => 'supplier-portal.rfqs.detail',
                'display_name' => 'RFQ Details',
                'module' => 'supplier',
                'section' => 'portal',
                'route_name' => 'supplier-portal.rfqs.detail',
                'route_path' => '/supplier-portal/rfqs/:id',
                'icon' => null,
                'display_order' => 4,
            ],
            [
                'name' => 'supplier-portal.pos',
                'display_name' => 'Purchase Orders',
                'module' => 'supplier',
                'section' => 'portal',
                'route_name' => 'supplier-portal.pos',
                'route_path' => '/supplier-portal/pos',
                'icon' => 'pi pi-shopping-cart',
                'display_order' => 5,
            ],
            [
                'name' => 'supplier-portal.pos.detail',
                'display_name' => 'PO Details',
                'module' => 'supplier',
                'section' => 'portal',
                'route_name' => 'supplier-portal.pos.detail',
                'route_path' => '/supplier-portal/pos/:id',
                'icon' => null,
                'display_order' => 6,
            ],
            [
                'name' => 'supplier-verifications',
                'display_name' => 'Supplier Verifications',
                'module' => 'supplier',
                'section' => 'admin',
                'route_name' => 'supplier-verifications',
                'route_path' => '/admin/supplier-verifications',
                'icon' => 'pi pi-check-circle',
                'display_order' => 7,
            ],
        ];

        // ========== HR MISSING NAVIGATION ITEMS ==========
        $hrItems = [
            [
                'name' => 'hr.dashboard',
                'display_name' => 'HR Dashboard',
                'module' => 'hr',
                'section' => null,
                'route_name' => 'hr.dashboard',
                'route_path' => '/hr/index',
                'icon' => 'pi pi-chart-bar',
                'display_order' => 1,
            ],
            [
                'name' => 'hr.employees',
                'display_name' => 'Employees',
                'module' => 'hr',
                'section' => 'employees',
                'route_name' => 'hr.employees',
                'route_path' => '/hr/employees',
                'icon' => 'pi pi-users',
                'display_order' => 2,
            ],
            [
                'name' => 'hr.employees.view',
                'display_name' => 'View Employee',
                'module' => 'hr',
                'section' => 'employees',
                'route_name' => 'hr.employees.view',
                'route_path' => '/hr/employees/view/:id',
                'icon' => null,
                'display_order' => 3,
            ],
            [
                'name' => 'hr.shifts',
                'display_name' => 'Shift Management',
                'module' => 'hr',
                'section' => 'shifts',
                'route_name' => 'hr.shifts',
                'route_path' => '/hr/shifts',
                'icon' => 'pi pi-clock',
                'display_order' => 4,
            ],
            [
                'name' => 'hr.shifts.employees',
                'display_name' => 'Employee Shifts',
                'module' => 'hr',
                'section' => 'shifts',
                'route_name' => 'hr.shifts.employees',
                'route_path' => '/hr/shifts/employees',
                'icon' => null,
                'display_order' => 5,
            ],
            [
                'name' => 'hr.shifts.create',
                'display_name' => 'Create Shift',
                'module' => 'hr',
                'section' => 'shifts',
                'route_name' => 'hr.shifts.create',
                'route_path' => '/hr/shifts/create',
                'icon' => null,
                'display_order' => 6,
            ],
            [
                'name' => 'hr.attendance',
                'display_name' => 'Attendance',
                'module' => 'hr',
                'section' => null,
                'route_name' => 'hr.attendance',
                'route_path' => '/hr/attendance',
                'icon' => 'pi pi-calendar-times',
                'display_order' => 7,
            ],
            [
                'name' => 'hr.departments',
                'display_name' => 'Departments',
                'module' => 'hr',
                'section' => null,
                'route_name' => 'hr.departments',
                'route_path' => '/hr/departments',
                'icon' => 'pi pi-sitemap',
                'display_order' => 8,
            ],
            [
                'name' => 'hr.leave',
                'display_name' => 'Leave Management',
                'module' => 'hr',
                'section' => 'leave',
                'route_name' => 'hr.leave',
                'route_path' => '/hr/leave-management',
                'icon' => 'pi pi-calendar',
                'display_order' => 9,
            ],
            [
                'name' => 'hr.leave.balances',
                'display_name' => 'Leave Balances',
                'module' => 'hr',
                'section' => 'leave',
                'route_name' => 'hr.leave.balances',
                'route_path' => '/hr/leave-balances',
                'icon' => null,
                'display_order' => 10,
            ],
            [
                'name' => 'hr.analytics',
                'display_name' => 'Analytics',
                'module' => 'hr',
                'section' => null,
                'route_name' => 'hr.analytics',
                'route_path' => '/hr/analytics',
                'icon' => 'pi pi-chart-pie',
                'display_order' => 11,
            ],
            [
                'name' => 'hr.settings',
                'display_name' => 'Settings',
                'module' => 'hr',
                'section' => null,
                'route_name' => 'hr.settings',
                'route_path' => '/hr/settings',
                'icon' => 'pi pi-cog',
                'display_order' => 12,
            ],
            [
                'name' => 'hr.payroll',
                'display_name' => 'Payroll',
                'module' => 'hr',
                'section' => 'payroll',
                'route_name' => 'hr.payroll',
                'route_path' => '/hr/payroll',
                'icon' => 'pi pi-money-bill',
                'display_order' => 13,
            ],
            [
                'name' => 'hr.payroll.overview',
                'display_name' => 'Payroll Overview',
                'module' => 'hr',
                'section' => 'payroll',
                'route_name' => 'hr.payroll.overview',
                'route_path' => '/hr/payroll/overview',
                'icon' => null,
                'display_order' => 14,
            ],
            [
                'name' => 'hr.payroll.periods',
                'display_name' => 'Pay Periods',
                'module' => 'hr',
                'section' => 'payroll',
                'route_name' => 'hr.payroll.periods',
                'route_path' => '/hr/payroll/periods',
                'icon' => null,
                'display_order' => 15,
            ],
            [
                'name' => 'hr.payroll.list',
                'display_name' => 'Payroll List',
                'module' => 'hr',
                'section' => 'payroll',
                'route_name' => 'hr.payroll.list',
                'route_path' => '/hr/payroll/lists',
                'icon' => null,
                'display_order' => 16,
            ],
            [
                'name' => 'hr.payroll.create',
                'display_name' => 'Generate Payroll',
                'module' => 'hr',
                'section' => 'payroll',
                'route_name' => 'hr.payroll.create',
                'route_path' => '/hr/payroll/create',
                'icon' => null,
                'display_order' => 17,
            ],
            [
                'name' => 'hr.payroll.view',
                'display_name' => 'View Payroll',
                'module' => 'hr',
                'section' => 'payroll',
                'route_name' => 'hr.payroll.view',
                'route_path' => '/hr/payroll/view/:id',
                'icon' => null,
                'display_order' => 18,
            ],
            [
                'name' => 'hr.payroll.edit',
                'display_name' => 'Edit Payroll',
                'module' => 'hr',
                'section' => 'payroll',
                'route_name' => 'hr.payroll.edit',
                'route_path' => '/hr/payroll/edit/:id',
                'icon' => null,
                'display_order' => 19,
            ],
            [
                'name' => 'hr.job-postings',
                'display_name' => 'Job Postings',
                'module' => 'hr',
                'section' => 'job_hiring',
                'route_name' => 'hr.job-postings',
                'route_path' => '/hr/job-hiring',
                'icon' => 'pi pi-briefcase',
                'display_order' => 20,
            ],
            [
                'name' => 'hr.screening-pipeline',
                'display_name' => 'Screening Pipeline',
                'module' => 'hr',
                'section' => 'job_hiring',
                'route_name' => 'hr.screening-pipeline',
                'route_path' => '/hr/job-hiring/postings/:postingId/screening',
                'icon' => null,
                'display_order' => 21,
            ],
            [
                'name' => 'hr.apply-job',
                'display_name' => 'Apply Job',
                'module' => 'hr',
                'section' => 'job_hiring',
                'route_name' => 'hr.apply-job',
                'route_path' => '/hr/job-hiring/postings/:postingId/apply',
                'icon' => null,
                'display_order' => 22,
            ],
        ];

        // ========== STORE ADMIN BRANCHES ==========
        $storeAdminItems = [
            [
                'name' => 'store.branches',
                'display_name' => 'Branches',
                'module' => 'store',
                'section' => 'settings',
                'route_name' => 'store.branches',
                'route_path' => '/store/branches',
                'icon' => 'pi pi-map',
                'display_order' => 20,
            ],
            [
                'name' => 'store.branches.show',
                'display_name' => 'Branch Detail',
                'module' => 'store',
                'section' => 'settings',
                'route_name' => 'store.branches.show',
                'route_path' => '/store/branches/:id',
                'icon' => null,
                'display_order' => 21,
            ],
        ];

        // ========== FINANCE NAVIGATION ITEMS ==========
        $financeItems = [
            [
                'name' => 'finance.dashboard',
                'display_name' => 'Finance Dashboard',
                'module' => 'finance',
                'section' => null,
                'route_name' => 'finance.dashboard',
                'route_path' => '/finance/dashboard',
                'icon' => 'pi pi-chart-line',
                'display_order' => 1,
            ],
            [
                'name' => 'finance.payables',
                'display_name' => 'Accounts Payable',
                'module' => 'finance',
                'section' => 'ap',
                'route_name' => 'finance.payables',
                'route_path' => '/finance/payables',
                'icon' => 'pi pi-wallet',
                'display_order' => 2,
            ],
            [
                'name' => 'finance.receivables',
                'display_name' => 'Accounts Receivable',
                'module' => 'finance',
                'section' => 'ar',
                'route_name' => 'finance.receivables',
                'route_path' => '/finance/receivables',
                'icon' => 'pi pi-credit-card',
                'display_order' => 3,
            ],
            [
                'name' => 'finance.expenses',
                'display_name' => 'Expenses',
                'module' => 'finance',
                'section' => 'expenses',
                'route_name' => 'finance.expenses',
                'route_path' => '/finance/expenses',
                'icon' => 'pi pi-receipt',
                'display_order' => 4,
            ],
            [
                'name' => 'finance.payroll',
                'display_name' => 'Payroll',
                'module' => 'finance',
                'section' => 'payroll',
                'route_name' => 'finance.payroll',
                'route_path' => '/finance/payroll',
                'icon' => 'pi pi-users',
                'display_order' => 5,
            ],
            [
                'name' => 'finance.budgets',
                'display_name' => 'Budgets',
                'module' => 'finance',
                'section' => 'budgets',
                'route_name' => 'finance.budgets',
                'route_path' => '/finance/budgets',
                'icon' => 'pi pi-chart-bar',
                'display_order' => 6,
            ],
            [
                'name' => 'finance.reports',
                'display_name' => 'Reports',
                'module' => 'finance',
                'section' => 'reports',
                'route_name' => 'finance.reports',
                'route_path' => '/finance/reports',
                'icon' => 'pi pi-file',
                'display_order' => 7,
            ],
        ];

        // ========== ADMIN CUSTOMER MANAGEMENT ==========
        $adminCustomerItems = [
            [
                'name' => 'admin.customer-management',
                'display_name' => 'Customer Management',
                'module' => 'admin',
                'section' => 'customers',
                'route_name' => 'admin.customer-management',
                'route_path' => '/admin/customer-management',
                'icon' => 'pi pi-users',
                'display_order' => 30,
            ],
        ];

        // ========== MERCHANDISING MISSING NAVIGATION ITEMS ==========
        $merchandisingItems = [
            [
                'name' => 'merchandising.assets.upload',
                'display_name' => 'Upload Asset',
                'module' => 'merchandising',
                'section' => 'assets',
                'route_name' => 'merchandising.assets.upload',
                'route_path' => '/merchandising/assets/upload',
                'icon' => null,
                'display_order' => 21,
            ],
            [
                'name' => 'merchandising.pricing.bulk',
                'display_name' => 'Bulk Price Update',
                'module' => 'merchandising',
                'section' => 'pricing',
                'route_name' => 'merchandising.pricing.bulk',
                'route_path' => '/merchandising/pricing/bulk-update',
                'icon' => null,
                'display_order' => 22,
            ],
            [
                'name' => 'merchandising.pricing-history',
                'display_name' => 'Pricing History',
                'module' => 'merchandising',
                'section' => 'reports',
                'route_name' => 'merchandising.pricing-history',
                'route_path' => '/merchandising/pricing-history',
                'icon' => null,
                'display_order' => 23,
            ],
            [
                'name' => 'merchandising.tags',
                'display_name' => 'Tags & Collections',
                'module' => 'merchandising',
                'section' => 'catalog',
                'route_name' => 'merchandising.tags',
                'route_path' => '/merchandising/tags',
                'icon' => 'pi pi-tags',
                'display_order' => 24,
            ],
        ];

        // Insert supplier portal items
        foreach ($supplierPortalItems as $item) {
            if (!DB::table('navigation_items')->where('name', $item['name'])->exists()) {
                DB::table('navigation_items')->insert(array_merge($item, [
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added navigation item: {$item['name']}\n";
            }
        }

        // Insert HR items
        foreach ($hrItems as $item) {
            if (!DB::table('navigation_items')->where('name', $item['name'])->exists()) {
                DB::table('navigation_items')->insert(array_merge($item, [
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added navigation item: {$item['name']}\n";
            }
        }

        // Insert Finance items
        foreach ($financeItems as $item) {
            if (!DB::table('navigation_items')->where('name', $item['name'])->exists()) {
                DB::table('navigation_items')->insert(array_merge($item, [
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added navigation item: {$item['name']}\n";
            }
        }

        // Insert Merchandising items
        foreach ($merchandisingItems as $item) {
            if (!DB::table('navigation_items')->where('name', $item['name'])->exists()) {
                DB::table('navigation_items')->insert(array_merge($item, [
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added navigation item: {$item['name']}\n";
            }
        }

        // Insert Store Admin items
        foreach ($storeAdminItems as $item) {
            if (!DB::table('navigation_items')->where('name', $item['name'])->exists()) {
                DB::table('navigation_items')->insert(array_merge($item, [
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added navigation item: {$item['name']}\n";
            }
        }

        // Insert Admin customer items
        foreach ($adminCustomerItems as $item) {
            if (!DB::table('navigation_items')->where('name', $item['name'])->exists()) {
                DB::table('navigation_items')->insert(array_merge($item, [
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added navigation item: {$item['name']}\n";
            }
        }

        echo "\n✅ Added {$insertedCount} missing navigation items!\n";
    }
}
