<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'Platform owner with full system access', 'code' => 'ADM'],
            ['name' => 'store_admin', 'display_name' => 'Store Administrator', 'description' => 'Manages store configuration and operations', 'code' => 'SADM'],
            ['name' => 'driver', 'display_name' => 'Driver', 'description' => 'Handles assigned supplier pickups and customer deliveries', 'code' => 'DRV'],
            ['name' => 'applicant', 'display_name' => 'Applicant', 'description' => 'Public applicant for the job portal', 'code' => 'APP'],
            ['name' => 'supplier', 'display_name' => 'Supplier', 'description' => 'Manages supplier portal activities', 'code' => 'SUP'],
            ['name' => 'customer', 'display_name' => 'Customer', 'description' => 'Furniture store customer', 'code' => 'CUS'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name'], 'store_id' => null],
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
