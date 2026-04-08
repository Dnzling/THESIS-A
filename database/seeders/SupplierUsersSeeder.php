<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SupplierUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create supplier role if it doesn't exist
        $supplierRole = DB::table('roles')->where('name', 'supplier')->first();
        if (!$supplierRole) {
            $supplierRole = DB::table('roles')->insertGetId([
                'name' => 'supplier',
                'display_name' => 'Supplier',
                'description' => 'Supplier portal user for RFQ and PO management',
                'code' => 'SUPP',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "Created 'supplier' role (ID: {$supplierRole})\n";
        } else {
            $supplierRole = $supplierRole->id;
        }

        // Get all suppliers
        $suppliers = DB::table('suppliers')->get();
        $now = Carbon::now();
        $createdCount = 0;

        foreach ($suppliers as $supplier) {
            // Check if user already exists for this supplier
            $email = 'supplier' . $supplier->id . '@' . strtolower(str_replace(' ', '', $supplier->supplier_name)) . '.local';
            
            $existingUser = DB::table('users')->where('email', $email)->first();
            
            if (!$existingUser) {
                // Create new supplier user
                $userId = DB::table('users')->insertGetId([
                    'fname' => explode(' ', $supplier->supplier_name)[0],
                    'lname' => $supplier->contact_person ?? 'Supplier',
                    'email' => $email,
                    'store_id' => $supplier->store_id,
                    'role_id' => $supplierRole,
                    'password' => Hash::make('Supplier@' . $supplier->id . '123'),
                    'is_active' => 1,
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                echo "Created supplier user for '{$supplier->supplier_name}' (User ID: {$userId}, Email: {$email})\n";
                $createdCount++;
            } else {
                echo "User '{$email}' already exists (User ID: {$existingUser->id})\n";
            }
        }

        echo "\n✅ Supplier users seeded successfully! Created {$createdCount} new users.\n";
    }
}
