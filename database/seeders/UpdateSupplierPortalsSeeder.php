<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateSupplierPortalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapping of supplier_id to the new supplier user_id
        $supplierUserMapping = [
            1 => 22,  // Manila Furniture Manufacturing
            2 => 23,  // Cavite Wood Suppliers
            3 => 24,  // Asian Imports Trading
            4 => 25,  // Laguna Artisan Furniture
            5 => 26,  // Premium Home Distributors
            13 => 27, // 123
        ];

        foreach ($supplierUserMapping as $supplierId => $userId) {
            $updated = DB::table('supplier_portals')
                ->where('supplier_id', $supplierId)
                ->update(['user_id' => $userId]);
            
            if ($updated > 0) {
                echo "Updated supplier portal for supplier_id {$supplierId} -> user_id {$userId}\n";
            }
        }

        echo "\n✅ Supplier portals updated successfully!\n";
    }
}
