<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplierPortalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all suppliers
        $suppliers = DB::table('suppliers')->get();
        
        // Get available user IDs (excluding superadmin)
        $availableUserIds = DB::table('users')->where('id', '!=', 1)->pluck('id')->toArray();
        
        $userIndex = 0;
        $portalCode = 1;
        $now = Carbon::now();

        foreach ($suppliers as $supplier) {
            // Assign user_id from available users (cycle through)
            $userId = $availableUserIds[$userIndex % count($availableUserIds)];
            $userIndex++;

            // Create supplier portal entry
            DB::table('supplier_portals')->insert([
                'supplier_id' => $supplier->id,
                'user_id' => $userId,
                'status' => 'pending',
                'last_submission_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            echo "Created portal for Supplier #{$supplier->id} ({$supplier->supplier_name}) - User ID: {$userId}\n";
            $portalCode++;
        }

        echo "\nSupplier portals seeded successfully!\n";
    }
}
