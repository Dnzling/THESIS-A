<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Core\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin (if needed)
        // User::create([
        //     'fname' => 'Denz',
        //     'lname' => 'Declarado',
        //     'email' => 'superadmin@example.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('password123'),
        //     'role_id' => 1, // Super admin role
        //     'store_id' => null,
        //     'branch_id' => null,
        //     'is_active' => 1,
        //     // 'remember_token' => Str::random(10),
        // ]);

        // // Create HR managers
        // User::factory()->count(3)->hrManager()->create();

        // // Create store admins
        // User::factory()->count(5)->storeAdmin()->create();

        // // Create regular users/employees (without employee records yet)
        // User::factory()->count(20)->employee()->create();

        // // Create some inactive users
        // User::factory()->count(5)->employee()->inactive()->create();

        // // Create some users with OTP
        // User::factory()->count(3)->employee()->withOtp()->create();

        // Create test users with specific emails
        User::create([
            'fname' => 'Ahbram',
            'lname' => 'Carra',
            'email' => 'store.admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 2, // Store admin role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'Adrian',
            'lname' => 'Lacea',
            'email' => 'store.manager@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 3, //Store Manager
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'Edwin',
            'lname' => 'Vasquez',
            'email' => 'hr.manager@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 4, // HR manager role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'MC',
            'lname' => 'Mendoza',
            'email' => 'accountant@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 5, // Accountant role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'Cash',
            'lname' => 'Gshock',
            'email' => 'cashier@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 6, // Cashier role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'Ware',
            'lname' => 'House',
            'email' => 'warehouse@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 7, // Warehouse Manager role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'Invent',
            'lname' => 'Ory',
            'email' => 'inventory@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 8, // Inventory role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'Supp',
            'lname' => 'Coor',
            'email' => 'supplier.coor@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 9, // Supplier Coor 
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create([
            'fname' => 'Sales',
            'lname' => 'Domingo',
            'email' => 'sales@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 10, // Sales Manager role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);

        User::create(attributes: [
            'fname' => 'Delivery',
            'lname' => 'Padala',
            'email' => 'delivery@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'role_id' => 11, // Delivery Manager role
            'store_id' => 1,
            'branch_id' => 1,
            'is_active' => 1,
            // 'remember_token' => Str::random(10),
        ]);
    }
}
