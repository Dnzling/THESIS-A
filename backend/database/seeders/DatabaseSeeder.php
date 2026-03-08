<?php

namespace Database\Seeders;

use App\Models\Core\User;
use Database\Seeders\Hr\AttendanceSeeder;
use Database\Seeders\Hr\DeductionTypesSeeder;
use Database\Seeders\Hr\DepartmentSeeder;
use Database\Seeders\Hr\EmployeeDeductionsSeeder;
use Database\Seeders\Hr\LeaveBalanceSeeder;
use Database\Seeders\Hr\LeaveSeeder;
use Database\Seeders\Hr\PayPeriodSeeder;
use Database\Seeders\Hr\PayrollItemSeeder;
use Database\Seeders\Hr\PayrollItemsSeeder;
use Database\Seeders\Hr\PayrollSeeder;
use Database\Seeders\Hr\ShiftScheduleSeeder;
use Database\Seeders\Hr\ShiftSeeder;
use Database\Seeders\Permission\Inventory\InventoryNavigationSeeder;
use Database\Seeders\Permission\Inventory\InventoryPermissionsSeeder;
use Database\Seeders\Permission\Inventory\InventoryRolePermissionsSeeder;
use Database\Seeders\Permission\Merchandise\MerchandisingPermissionsSeeder;
use Database\Seeders\Permission\Merchandise\NavigationItemsSeeder;
use Database\Seeders\Permission\Merchandise\RolePermissionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RoleSeeder::class,
            // UserSeeder::class,
            // EmployeeSeeder::class,
            // DeductionTypesSeeder::class,
            // ShiftScheduleSeeder::class,
            // ShiftSeeder::class,
            // PayPeriodSeeder::class,
            // PayrollSeeder::class,
            // PayrollItemSeeder::class,
            // LeaveSeeder::class,
            // AttendanceSeeder::class,
            // LeaveBalanceSeeder::class,
            // DepartmentSeeder::class,
            // EmployeeDeductionsSeeder::class
            // MerchandisingPermissionsSeeder::class,
            // RolePermissionSeeder::class,
            // NavigationItemsSeeder::class,
            // InventoryPermissionsSeeder::class,
            PermissionSeeder::class,
            InventoryRolePermissionsSeeder::class,
            InventoryNavigationSeeder::class,
        ]);
    }
}
