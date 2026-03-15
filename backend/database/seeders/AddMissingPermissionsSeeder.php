<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AddMissingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $insertedCount = 0;

        // ========== SUPPLIER PORTAL PERMISSIONS ==========
        $supplierPortalPermissions = [
            [
                'name' => 'supplier-portal.dashboard.view',
                'display_name' => 'Supplier Portal Dashboard View',
                'module' => 'supplier',
                'description' => 'View supplier portal dashboard',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-portal.registration.view',
                'display_name' => 'Supplier Registration View',
                'module' => 'supplier',
                'description' => 'View registration form',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-portal.registration.submit',
                'display_name' => 'Supplier Registration Submit',
                'module' => 'supplier',
                'description' => 'Submit supplier registration',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-portal.rfqs.view',
                'display_name' => 'Supplier RFQs View',
                'module' => 'supplier',
                'description' => 'View available RFQs',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-portal.rfqs.respond',
                'display_name' => 'Supplier RFQ Respond',
                'module' => 'supplier',
                'description' => 'Submit quotes to RFQs',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-portal.pos.view',
                'display_name' => 'Supplier POs View',
                'module' => 'supplier',
                'description' => 'View purchase orders',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-portal.pos.respond',
                'display_name' => 'Supplier PO Respond',
                'module' => 'supplier',
                'description' => 'Accept or reject purchase orders',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-verifications.view',
                'display_name' => 'Supplier Verifications View',
                'module' => 'supplier',
                'description' => 'View supplier verification dashboard',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-verifications.approve',
                'display_name' => 'Supplier Verifications Approve',
                'module' => 'supplier',
                'description' => 'Approve supplier registrations',
                'is_active' => 1,
            ],
            [
                'name' => 'supplier-verifications.reject',
                'display_name' => 'Supplier Verifications Reject',
                'module' => 'supplier',
                'description' => 'Reject supplier registrations',
                'is_active' => 1,
            ],
        ];

        // ========== HR PERMISSIONS ==========
        $hrPermissions = [
            [
                'name' => 'hr.dashboard.view',
                'display_name' => 'HR Dashboard View',
                'module' => 'hr',
                'description' => 'View HR dashboard',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.employees.view',
                'display_name' => 'Employees View',
                'module' => 'hr',
                'description' => 'View employee list',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.employees.create',
                'display_name' => 'Employees Create',
                'module' => 'hr',
                'description' => 'Create new employee',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.employees.edit',
                'display_name' => 'Employees Edit',
                'module' => 'hr',
                'description' => 'Edit employee information',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.shifts.view',
                'display_name' => 'Shifts View',
                'module' => 'hr',
                'description' => 'View shift management',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.shifts.create',
                'display_name' => 'Shifts Create',
                'module' => 'hr',
                'description' => 'Create shifts',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.shifts.edit',
                'display_name' => 'Shifts Edit',
                'module' => 'hr',
                'description' => 'Edit shifts',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.attendance.view',
                'display_name' => 'Attendance View',
                'module' => 'hr',
                'description' => 'View attendance records',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.attendance.record',
                'display_name' => 'Attendance Record',
                'module' => 'hr',
                'description' => 'Record attendance',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.departments.view',
                'display_name' => 'Departments View',
                'module' => 'hr',
                'description' => 'View departments',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.departments.manage',
                'display_name' => 'Departments Manage',
                'module' => 'hr',
                'description' => 'Manage departments',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.leave.view',
                'display_name' => 'Leave View',
                'module' => 'hr',
                'description' => 'View leave requests',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.leave.request',
                'display_name' => 'Leave Request',
                'module' => 'hr',
                'description' => 'Request leave',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.leave.approve',
                'display_name' => 'Leave Approve',
                'module' => 'hr',
                'description' => 'Approve leave requests',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.analytics.view',
                'display_name' => 'Analytics View',
                'module' => 'hr',
                'description' => 'View HR analytics',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.settings.manage',
                'display_name' => 'Settings Manage',
                'module' => 'hr',
                'description' => 'Manage HR settings',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.payroll.view',
                'display_name' => 'Payroll View',
                'module' => 'hr',
                'description' => 'View payroll',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.payroll.create',
                'display_name' => 'Payroll Create',
                'module' => 'hr',
                'description' => 'Generate payroll',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.payroll.edit',
                'display_name' => 'Payroll Edit',
                'module' => 'hr',
                'description' => 'Edit payroll',
                'is_active' => 1,
            ],
            [
                'name' => 'hr.payroll.approve',
                'display_name' => 'Payroll Approve',
                'module' => 'hr',
                'description' => 'Approve payroll',
                'is_active' => 1,
            ],
        ];

        // ========== MERCHANDISING PERMISSIONS ==========
        $merchandisingPermissions = [
            [
                'name' => 'merchandising.tags.view',
                'display_name' => 'View Tags & Collections',
                'module' => 'merchandising',
                'description' => 'View product tags and collections',
                'is_active' => 1,
            ],
        ];

        // ========== ADMIN CUSTOMER MANAGEMENT PERMISSIONS ==========
        $adminCustomerPermissions = [
            [
                'name' => 'admin.customers.view',
                'display_name' => 'Admin Customers View',
                'module' => 'admin',
                'description' => 'View customer management page',
                'is_active' => 1,
            ],
            [
                'name' => 'admin.customers.require-verification',
                'display_name' => 'Admin Customers Require Verification',
                'module' => 'admin',
                'description' => 'Force customer verification',
                'is_active' => 1,
            ],
        ];

        // Insert supplier portal permissions
        foreach ($supplierPortalPermissions as $perm) {
            if (!DB::table('permissions')->where('name', $perm['name'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added permission: {$perm['name']}\n";
            }
        }

        // Insert HR permissions
        foreach ($hrPermissions as $perm) {
            if (!DB::table('permissions')->where('name', $perm['name'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added permission: {$perm['name']}\n";
            }
        }

        // Insert Merchandising permissions
        foreach ($merchandisingPermissions as $perm) {
            if (!DB::table('permissions')->where('name', $perm['name'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added permission: {$perm['name']}\n";
            }
        }

        // Insert Admin customer permissions
        foreach ($adminCustomerPermissions as $perm) {
            if (!DB::table('permissions')->where('name', $perm['name'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $insertedCount++;
                echo "✓ Added permission: {$perm['name']}\n";
            }
        }

        echo "\n✅ Added {$insertedCount} missing permissions!\n";
    }
}
