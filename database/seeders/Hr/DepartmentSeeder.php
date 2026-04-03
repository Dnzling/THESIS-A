<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Core Operations Departments
        Department::create([
            'store_id' => 1,
            'name' => 'Store Operations',
            'code' => 'OPS',
            'location' => 'Main Showroom',
            'description' => 'Manages daily store operations and customer service',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Sales & Showroom',
            'code' => 'SALES',
            'location' => 'Showroom Floor',
            'description' => 'Handles in-store sales, customer consultations, and showroom displays',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Inventory & Warehouse',
            'code' => 'INV',
            'location' => 'Warehouse',
            'description' => 'Manages furniture inventory, stock levels, and warehouse organization',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Delivery & Logistics',
            'code' => 'LOG',
            'location' => 'Loading Bay',
            'description' => 'Coordinates furniture delivery, assembly services, and fleet management',
            'created_by' => 1,
        ]);

        // Department::create([
        //     'store_id' => 1,
        //     'name' => 'Interior Design Services',
        //     'code' => 'DESIGN',
        //     'location' => 'Design Studio',
        //     'description' => 'Provides interior design consultations and space planning',
        //     'created_by' => 1,
        // ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Procurement & Merchandising',
            'code' => 'PROC',
            'location' => 'Head Office',
            'description' => 'Handles supplier relationships, product sourcing, and merchandise planning',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Customer Service',
            'code' => 'CS',
            'location' => 'Customer Service Desk',
            'description' => 'Manages customer inquiries, complaints, and post-purchase support',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Marketing & E-commerce',
            'code' => 'MKT',
            'location' => 'Marketing Office',
            'description' => 'Handles online store, digital marketing, and promotional campaigns',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Finance & Accounting',
            'code' => 'FIN',
            'location' => 'Finance Office',
            'description' => 'Manages financial transactions, payroll, and budgeting',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Human Resources',
            'code' => 'HR',
            'location' => 'HR Office',
            'description' => 'Handles recruitment, training, and employee relations',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Quality Assurance & Assembly',
            'code' => 'QA',
            'location' => 'Assembly Area',
            'description' => 'Ensures furniture quality, handles assembly services, and repairs',
            'created_by' => 1,
        ]);

        Department::create([
            'store_id' => 1,
            'name' => 'Administration',
            'code' => 'ADMIN',
            'location' => 'Admin Office',
            'description' => 'Manages administrative tasks and facility maintenance',
            'created_by' => 1,
        ]);
    }
}
