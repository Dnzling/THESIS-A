<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Platform owner with full system access',
                'code' => 'ADM'
            ],
            [
                'name' => 'store_admin',
                'display_name' => 'Store Administrator',
                'description' => 'Manages store configuration and administrators',
                'code' => 'SADM'
            ],
            [
                'name' => 'store_manager',
                'display_name' => 'Store Manager',
                'description' => 'Oversees daily store operations',
                'code' => 'SM'
            ],
            [
                'name' => 'hr_manager',
                'display_name' => 'HR Manager',
                'description' => 'Handles employee records and HR processes',
                'code' => 'HR'
            ],
            [
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'description' => 'Manages financial records and reports',
                'code' => 'ACT'
            ],
            [
                'name' => 'cashier',
                'display_name' => 'Cashier',
                'description' => 'Handles POS transactions and payments',
                'code' => 'CSH'
            ],
            [
                'name' => 'warehouse_manager',
                'display_name' => 'Warehouse Manager',
                'description' => 'Supervises warehouse and stock storage',
                'code' => 'WHM'
            ],
            [
                'name' => 'inventory_staff',
                'display_name' => 'Inventory Staff',
                'description' => 'Handles stock movements and monitoring',
                'code' => 'INV'
            ],
            [
                'name' => 'supplier_coordinator',
                'display_name' => 'Supplier Coordinator',
                'description' => 'Manages suppliers and purchase orders',
                'code' => 'SUP'
            ],
            [
                'name' => 'sales_staff',
                'display_name' => 'Sales Staff',
                'description' => 'Handles product sales and customer assistance',
                'code' => 'SAL'
            ],
            [
                'name' => 'delivery_staff',
                'display_name' => 'Delivery Staff',
                'description' => 'Responsible for product deliveries',
                'code' => 'DLV'
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']], // unique key
                [
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'code' => $role['code'],
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
