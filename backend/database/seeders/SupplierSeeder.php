<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            // Raw Materials Suppliers
            [
                'supplier_name' => 'Acme Materials Inc',
                'company_name' => 'Acme Inc',
                'contact_person' => 'John Doe',
                'email' => 'john@acme.com',
                'phone' => '(555) 123-4567',
                'address' => '789 Industrial Ave',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60601',
                'country' => 'USA',
                'category' => 'Raw Materials',
                'payment_terms' => 'Net 30',
                'status' => 'active',
                'tax_id' => '98-7654321',
                'bank_details' => 'Account: 987654321, Bank: First Trust',
                'rating' => 4.8,
                'quality_score' => 4.7,
                'on_time_percentage' => 98.5,
                'avg_delivery_days' => 3,
                'risk_score' => 5,
            ],
            [
                'supplier_name' => 'Global Supplies Ltd',
                'company_name' => 'Global Supplies',
                'contact_person' => 'Jane Smith',
                'email' => 'jane@globalsupplies.com',
                'phone' => '(555) 987-6543',
                'address' => '123 Business Park',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'USA',
                'category' => 'Raw Materials',
                'payment_terms' => 'Net 60',
                'status' => 'active',
                'tax_id' => '12-3456789',
                'bank_details' => 'Account: 123456789, Bank: Second Bank',
                'rating' => 4.5,
                'quality_score' => 4.3,
                'on_time_percentage' => 94.2,
                'avg_delivery_days' => 5,
                'risk_score' => 18,
            ],
            // Furniture Suppliers
            [
                'supplier_name' => 'Furniture Plus',
                'company_name' => 'Furniture Plus Inc',
                'contact_person' => 'Robert Johnson',
                'email' => 'robert@furnitureplus.com',
                'phone' => '(555) 234-5678',
                'address' => '456 Wood St',
                'city' => 'Atlanta',
                'state' => 'GA',
                'postal_code' => '30303',
                'country' => 'USA',
                'category' => 'Furniture',
                'payment_terms' => 'Net 45',
                'status' => 'active',
                'tax_id' => '45-6789012',
                'bank_details' => 'Account: 456789012, Bank: Commerce Bank',
                'rating' => 4.7,
                'quality_score' => 4.6,
                'on_time_percentage' => 96.8,
                'avg_delivery_days' => 7,
                'risk_score' => 12,
            ],
            [
                'supplier_name' => 'Premium Furniture Co',
                'company_name' => 'Premium Furniture',
                'contact_person' => 'Sarah Williams',
                'email' => 'sarah@premiumfurniture.com',
                'phone' => '(555) 345-6789',
                'address' => '789 Luxury Lane',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90001',
                'country' => 'USA',
                'category' => 'Furniture',
                'payment_terms' => 'Net 90',
                'status' => 'active',
                'tax_id' => '78-9012345',
                'bank_details' => 'Account: 789012345, Bank: West Coast Financial',
                'rating' => 4.9,
                'quality_score' => 4.8,
                'on_time_percentage' => 99.1,
                'avg_delivery_days' => 4,
                'risk_score' => 2,
            ],
            // Accessories Suppliers
            [
                'supplier_name' => 'Accessories World',
                'company_name' => 'Accessories World Ltd',
                'contact_person' => 'Michael Brown',
                'email' => 'michael@accessoriesworld.com',
                'phone' => '(555) 456-7890',
                'address' => '321 Fashion Ave',
                'city' => 'Miami',
                'state' => 'FL',
                'postal_code' => '33101',
                'country' => 'USA',
                'category' => 'Accessories',
                'payment_terms' => 'Net 30',
                'status' => 'active',
                'tax_id' => '56-7890123',
                'bank_details' => 'Account: 567890123, Bank: Sunshine Bank',
                'rating' => 4.3,
                'quality_score' => 4.1,
                'on_time_percentage' => 91.5,
                'avg_delivery_days' => 6,
                'risk_score' => 32,
            ],
            [
                'supplier_name' => 'Elite Accessories Inc',
                'company_name' => 'Elite Accessories',
                'contact_person' => 'Lisa Anderson',
                'email' => 'lisa@eliteaccessories.com',
                'phone' => '(555) 567-8901',
                'address' => '654 Trend Blvd',
                'city' => 'Boston',
                'state' => 'MA',
                'postal_code' => '02101',
                'country' => 'USA',
                'category' => 'Accessories',
                'payment_terms' => 'Net 60',
                'status' => 'active',
                'tax_id' => '34-5678901',
                'bank_details' => 'Account: 345678901, Bank: Northeast Bank',
                'rating' => 4.6,
                'quality_score' => 4.5,
                'on_time_percentage' => 95.3,
                'avg_delivery_days' => 5,
                'risk_score' => 15,
            ],
            // Services Suppliers
            [
                'supplier_name' => 'Quality Services Group',
                'company_name' => 'Quality Services',
                'contact_person' => 'David Martinez',
                'email' => 'david@qualityservices.com',
                'phone' => '(555) 678-9012',
                'address' => '987 Service Dr',
                'city' => 'Denver',
                'state' => 'CO',
                'postal_code' => '80202',
                'country' => 'USA',
                'category' => 'Services',
                'payment_terms' => 'Net 30',
                'status' => 'active',
                'tax_id' => '67-8901234',
                'bank_details' => 'Account: 678901234, Bank: Mountain Bank',
                'rating' => 4.4,
                'quality_score' => 4.2,
                'on_time_percentage' => 92.7,
                'avg_delivery_days' => 2,
                'risk_score' => 28,
            ],
            // At-Risk Supplier
            [
                'supplier_name' => 'Risky Supplies Inc',
                'company_name' => 'Risky Supplies',
                'contact_person' => 'Tom Wilson',
                'email' => 'tom@riskysupplies.com',
                'phone' => '(555) 789-0123',
                'address' => '111 Uncertain Ave',
                'city' => 'Detroit',
                'state' => 'MI',
                'postal_code' => '48201',
                'country' => 'USA',
                'category' => 'Raw Materials',
                'payment_terms' => 'COD',
                'status' => 'active',
                'tax_id' => '89-0123456',
                'bank_details' => 'Account: 890123456, Bank: Midwest Bank',
                'rating' => 2.8,
                'quality_score' => 2.9,
                'on_time_percentage' => 75.2,
                'avg_delivery_days' => 14,
                'risk_score' => 68,
            ],
            // Inactive Supplier
            [
                'supplier_name' => 'Old Suppliers Ltd',
                'company_name' => 'Old Suppliers',
                'contact_person' => 'Edward Lee',
                'email' => 'edward@oldsuppliers.com',
                'phone' => '(555) 890-1234',
                'address' => '222 Legacy Lane',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'postal_code' => '19101',
                'country' => 'USA',
                'category' => 'Furniture',
                'payment_terms' => 'Net 90',
                'status' => 'inactive',
                'tax_id' => '23-4567890',
                'bank_details' => 'Account: 234567890, Bank: Heritage Bank',
                'rating' => 3.5,
                'quality_score' => 3.3,
                'on_time_percentage' => 85.0,
                'avg_delivery_days' => 10,
                'risk_score' => 45,
            ],
            // Additional suppliers for dashboard
            [
                'supplier_name' => 'Quality Goods Ltd',
                'company_name' => 'Quality Goods',
                'contact_person' => 'Nancy Taylor',
                'email' => 'nancy@qualitygoods.com',
                'phone' => '(555) 901-2345',
                'address' => '333 Excellence Way',
                'city' => 'Seattle',
                'state' => 'WA',
                'postal_code' => '98101',
                'country' => 'USA',
                'category' => 'Raw Materials',
                'payment_terms' => 'Net 60',
                'status' => 'active',
                'tax_id' => '01-2345678',
                'bank_details' => 'Account: 012345678, Bank: Pacific Bank',
                'rating' => 4.7,
                'quality_score' => 4.6,
                'on_time_percentage' => 97.2,
                'avg_delivery_days' => 4,
                'risk_score' => 8,
            ],
            [
                'supplier_name' => 'Budget Furniture Ltd',
                'company_name' => 'Budget Furniture',
                'contact_person' => 'Chris Garcia',
                'email' => 'chris@budgetfurniture.com',
                'phone' => '(555) 012-3456',
                'address' => '444 Economy St',
                'city' => 'Phoenix',
                'state' => 'AZ',
                'postal_code' => '85001',
                'country' => 'USA',
                'category' => 'Furniture',
                'payment_terms' => 'Net 45',
                'status' => 'active',
                'tax_id' => '12-3456789',
                'bank_details' => 'Account: 123456789, Bank: Desert Bank',
                'rating' => 4.2,
                'quality_score' => 3.9,
                'on_time_percentage' => 93.5,
                'avg_delivery_days' => 8,
                'risk_score' => 35,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        // Seed performance metrics history (last 12 months)
        $this->seedPerformanceHistory();

        // Seed payment records
        $this->seedPaymentRecords();

        // Seed supplier ratings
        $this->seedSupplierRatings();
    }

    /**
     * Seed performance metrics history
     */
    private function seedPerformanceHistory(): void
    {
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i)->toDateString();

                // Add variation to metrics over time
                $variation = rand(-5, 5);

                \DB::table('supplier_performance_metrics')->insert([
                    'supplier_id' => $supplier->id,
                    'on_time_percentage' => max(50, min(100, $supplier->on_time_percentage + $variation)),
                    'quality_score' => max(1, min(5, $supplier->quality_score + $variation / 10)),
                    'avg_delivery_days' => max(1, $supplier->avg_delivery_days + rand(-2, 2)),
                    'risk_score' => max(0, min(100, $supplier->risk_score + $variation)),
                    'risk_level' => $this->getRiskLevel($supplier->risk_score + $variation),
                    'date' => $date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Seed payment records
     */
    private function seedPaymentRecords(): void
    {
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            for ($i = 0; $i < rand(5, 15); $i++) {
                $daysAgo = rand(0, 90);
                $dueDate = now()->subDays($daysAgo);
                $paymentDate = $daysAgo < 70 ? $dueDate->addDays(rand(-5, 15))->toDateString() : null;
                $status = $paymentDate ? 'paid' : ($daysAgo > 5 ? 'overdue' : 'pending');

                \DB::table('supplier_payments')->insert([
                    'supplier_id' => $supplier->id,
                    'amount' => rand(500, 10000),
                    'payment_date' => $paymentDate,
                    'due_date' => $dueDate->toDateString(),
                    'status' => $status,
                    'days_overdue' => $status === 'overdue' ? $daysAgo - 5 : 0,
                    'payment_method' => $paymentDate ? collect(['Bank Transfer', 'Card', 'Check', 'Cash'])->random() : null,
                    'created_at' => $dueDate->subDays(30),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Seed supplier ratings
     */
    private function seedSupplierRatings(): void
    {
        $suppliers = Supplier::all();
        $categories = ['Delivery', 'Quality', 'Communication', 'Price'];

        foreach ($suppliers as $supplier) {
            foreach ($categories as $category) {
                \DB::table('supplier_ratings')->insert([
                    'supplier_id' => $supplier->id,
                    'category' => $category,
                    'rating' => rand(2, 5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get risk level from score
     */
    private function getRiskLevel(int $score): string
    {
        if ($score < 20) return 'Low';
        if ($score < 50) return 'Medium';
        if ($score < 75) return 'High';
        return 'Critical';
    }
}
