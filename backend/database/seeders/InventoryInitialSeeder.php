<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InventoryInitialSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now): void {
            $roleId = $this->seedRole($now);

            [$storeId, $mainBranchId] = $this->seedStoreAndBranch($now);
            $inventoryUserId = $this->seedUser($roleId, $storeId, $mainBranchId, $now);
            $inventoryEmployeeId = $this->seedEmployee($inventoryUserId, $roleId, $storeId, $mainBranchId, $now);

            $categoryIds = $this->seedCategories($storeId, $now);
            $productIds = $this->seedProducts($storeId, $categoryIds, $now);
            $unitIds = $this->seedUnits($storeId, $inventoryUserId, $now);

            $warehouseId = $this->seedWarehouse($storeId, $mainBranchId, $now);
            $locationIds = $this->seedWarehouseLocations($warehouseId, $now);

            $this->seedInventoryConfiguration($storeId, $mainBranchId, $now);
            $this->seedBranchInventory($storeId, $mainBranchId, $productIds, $inventoryEmployeeId, $now);
            $this->seedInventoryTransactions($storeId, $mainBranchId, $productIds, $inventoryEmployeeId, $now);
            $this->seedStockAlerts($mainBranchId, $productIds, $inventoryEmployeeId, $now);
            $ruleIds = $this->seedReorderRules($mainBranchId, $productIds, $now);
            $this->seedReorderSuggestions($mainBranchId, $productIds, $ruleIds, $inventoryEmployeeId, $now);
            $this->seedNotifications($storeId, $mainBranchId, $inventoryUserId, $now);
            $this->seedSerialNumbers($mainBranchId, $productIds, $locationIds, $now);
            $this->seedBatches($mainBranchId, $productIds, $locationIds, $now);

            // Keep units seeded and referenced so API pages have initial lookup values.
            unset($unitIds);
        });
    }

    private function seedRole(Carbon $now): int
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'inventory_staff'],
            [
                'display_name' => 'Inventory Staff',
                'code' => 'INV',
                'description' => 'Handles stock movements and monitoring',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) DB::table('roles')->where('name', 'inventory_staff')->value('id');
    }

    private function seedStoreAndBranch(Carbon $now): array
    {
        DB::table('stores')->updateOrInsert(
            ['store_code' => 'FS-MAIN'],
            [
                'name' => 'Furniture Stores Platform',
                'province' => 'Cavite',
                'type' => 'retail_furniture',
                'city' => 'Dasmarinas',
                'address' => 'Governor\'s Drive, Dasmarinas, Cavite',
                'phone' => '09170000001',
                'email' => 'main@fsp.local',
                'status' => 'active',
                'subscription_tier' => 'premium',
                'settings' => json_encode([
                    'currency' => 'PHP',
                    'timezone' => 'Asia/Manila',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $storeId = (int) DB::table('stores')->where('store_code', 'FS-MAIN')->value('id');

        DB::table('branches')->updateOrInsert(
            ['branch_code' => 'FS-DASMA-01'],
            [
                'store_id' => $storeId,
                'name' => 'Dasmarinas Main Branch',
                'address' => 'Block 12, Governor\'s Drive',
                'city' => 'Dasmarinas',
                'province' => 'Cavite',
                'contact_number' => '09170000002',
                'is_main_branch' => true,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $mainBranchId = (int) DB::table('branches')->where('branch_code', 'FS-DASMA-01')->value('id');

        return [$storeId, $mainBranchId];
    }

    private function seedUser(int $roleId, int $storeId, int $branchId, Carbon $now): int
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'inventory.seed@fsp.local'],
            [
                'fname' => 'Inventory',
                'lname' => 'Seeder',
                'store_id' => $storeId,
                'branch_id' => $branchId,
                'password' => Hash::make('password123'),
                'email_verified_at' => $now,
                'role_id' => $roleId,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) DB::table('users')->where('email', 'inventory.seed@fsp.local')->value('id');
    }

    private function seedEmployee(int $userId, int $roleId, int $storeId, int $branchId, Carbon $now): int
    {
        DB::table('employees')->updateOrInsert(
            ['store_id' => $storeId, 'employee_number' => 'INV-SEED-0001'],
            [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'fname' => 'Inventory',
                'lname' => 'Seeder',
                'phone' => '09170000003',
                'hire_date' => $now->toDateString(),
                'role_id' => $roleId,
                'department' => 'Inventory',
                'employment_type' => 'full_time',
                'salary' => 25000,
                'status' => 'active',
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) DB::table('employees')
            ->where('store_id', $storeId)
            ->where('employee_number', 'INV-SEED-0001')
            ->value('id');
    }

    private function seedCategories(int $storeId, Carbon $now): array
    {
        $categories = [
            ['code' => 'SOFA', 'name' => 'Sofas'],
            ['code' => 'BED', 'name' => 'Beds'],
            ['code' => 'TABLE', 'name' => 'Tables'],
        ];

        $ids = [];
        foreach ($categories as $item) {
            DB::table('categories')->updateOrInsert(
                ['store_id' => $storeId, 'category_code' => $item['code']],
                [
                    'category_name' => $item['name'],
                    'description' => $item['name'] . ' catalog category',
                    'level' => 1,
                    'is_active' => true,
                    'display_order' => 1,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $ids[$item['code']] = (int) DB::table('categories')
                ->where('store_id', $storeId)
                ->where('category_code', $item['code'])
                ->value('id');
        }

        return $ids;
    }

    private function seedProducts(int $storeId, array $categoryIds, Carbon $now): array
    {
        $products = [
            [
                'sku' => 'SOFA-001',
                'product_name' => 'Luna 3-Seater Sofa',
                'category_code' => 'SOFA',
                'base_price' => 24999,
                'cost_price' => 18500,
                'brand' => 'HomeCraft',
            ],
            [
                'sku' => 'BED-001',
                'product_name' => 'Astra Queen Bed Frame',
                'category_code' => 'BED',
                'base_price' => 18999,
                'cost_price' => 13200,
                'brand' => 'SleepWell',
            ],
            [
                'sku' => 'TABLE-001',
                'product_name' => 'Mira Dining Table 6-Seater',
                'category_code' => 'TABLE',
                'base_price' => 15999,
                'cost_price' => 10800,
                'brand' => 'OakLine',
            ],
        ];

        $ids = [];
        foreach ($products as $item) {
            DB::table('products')->updateOrInsert(
                ['store_id' => $storeId, 'sku' => $item['sku']],
                [
                    'product_name' => $item['product_name'],
                    'description' => $item['product_name'] . ' seeded initial product',
                    'category_id' => $categoryIds[$item['category_code']],
                    'brand' => $item['brand'],
                    'base_price' => $item['base_price'],
                    'cost_price' => $item['cost_price'],
                    'tax_rate' => 12,
                    'is_featured' => false,
                    'is_new_arrival' => false,
                    'is_bestseller' => false,
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $ids[$item['sku']] = (int) DB::table('products')
                ->where('store_id', $storeId)
                ->where('sku', $item['sku'])
                ->value('id');
        }

        return $ids;
    }

    private function seedUnits(int $storeId, int $userId, Carbon $now): array
    {
        DB::table('units')->updateOrInsert(
            ['unit_code' => 'PCS'],
            [
                'store_id' => $storeId,
                'unit_name' => 'Pieces',
                'unit_symbol' => 'pc',
                'description' => 'Base quantity unit',
                'unit_type' => 'quantity',
                'conversion_factor' => 1,
                'base_unit_id' => null,
                'is_base_unit' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $pcsId = (int) DB::table('units')->where('unit_code', 'PCS')->value('id');

        DB::table('units')->updateOrInsert(
            ['unit_code' => 'BOX'],
            [
                'store_id' => $storeId,
                'unit_name' => 'Box',
                'unit_symbol' => 'box',
                'description' => 'Box of pieces (10 pcs)',
                'unit_type' => 'quantity',
                'conversion_factor' => 10,
                'base_unit_id' => $pcsId,
                'is_base_unit' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return [
            'PCS' => $pcsId,
            'BOX' => (int) DB::table('units')->where('unit_code', 'BOX')->value('id'),
        ];
    }

    private function seedWarehouse(int $storeId, int $branchId, Carbon $now): int
    {
        DB::table('warehouses')->updateOrInsert(
            ['warehouse_code' => 'WH-DASMA-01'],
            [
                'store_id' => $storeId,
                'branch_id' => $branchId,
                'name' => 'Dasmarinas Main Warehouse',
                'description' => 'Primary stock storage for main branch',
                'type' => 'main',
                'status' => 'active',
                'address_line_1' => 'Governor\'s Drive Warehouse Compound',
                'city' => 'Dasmarinas',
                'state' => 'Cavite',
                'postal_code' => '4114',
                'country' => 'Philippines',
                'phone' => '09170000004',
                'email' => 'warehouse@fsp.local',
                'manager_name' => 'Inventory Seeder',
                'manager_phone' => '09170000005',
                'total_area_sqm' => 1200,
                'usable_area_sqm' => 1000,
                'total_racks' => 60,
                'total_shelves' => 320,
                'max_capacity_units' => 10000,
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'operating_days' => json_encode([1, 2, 3, 4, 5, 6]),
                'requires_access_card' => false,
                'has_security_system' => true,
                'has_fire_system' => true,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) DB::table('warehouses')->where('warehouse_code', 'WH-DASMA-01')->value('id');
    }

    private function seedWarehouseLocations(int $warehouseId, Carbon $now): array
    {
        $locations = [
            [
                'code' => 'A1-R1-S1-B1',
                'name' => 'Aisle 1 Rack 1 Shelf 1 Bin 1',
                'type' => 'bin',
                'aisle' => 'A1',
                'rack' => 'R1',
                'shelf' => 'S1',
                'bin' => 'B1',
                'max_capacity_units' => 800,
                'current_stock_units' => 420,
            ],
            [
                'code' => 'A1-R2-S1-B1',
                'name' => 'Aisle 1 Rack 2 Shelf 1 Bin 1',
                'type' => 'bin',
                'aisle' => 'A1',
                'rack' => 'R2',
                'shelf' => 'S1',
                'bin' => 'B1',
                'max_capacity_units' => 600,
                'current_stock_units' => 260,
            ],
        ];

        $ids = [];
        foreach ($locations as $item) {
            DB::table('warehouse_locations')->updateOrInsert(
                ['location_code' => $item['code']],
                [
                    'warehouse_id' => $warehouseId,
                    'name' => $item['name'],
                    'description' => 'Seeded inventory location',
                    'type' => $item['type'],
                    'status' => 'active',
                    'aisle' => $item['aisle'],
                    'rack' => $item['rack'],
                    'shelf' => $item['shelf'],
                    'bin' => $item['bin'],
                    'max_capacity_units' => $item['max_capacity_units'],
                    'current_stock_units' => $item['current_stock_units'],
                    'max_weight_kg' => 1500,
                    'current_weight_kg' => 550,
                    'dimensions' => json_encode([
                        'width_cm' => 120,
                        'height_cm' => 220,
                        'depth_cm' => 90,
                    ]),
                    'is_temperature_controlled' => false,
                    'requires_special_handling' => false,
                    'last_inventory_check' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $ids[$item['code']] = (int) DB::table('warehouse_locations')
                ->where('location_code', $item['code'])
                ->value('id');
        }

        return $ids;
    }

    private function seedInventoryConfiguration(int $storeId, int $mainBranchId, Carbon $now): void
    {
        DB::table('inventory_configurations')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'model_type' => 'centralized',
                'enable_transfer_approvals' => true,
                'enable_finance_approval' => true,
                'enable_auto_alerts' => true,
                'enable_cost_tracking' => true,
                'enable_physical_counts' => true,
                'main_branch_id' => $mainBranchId,
                'warehouse_branch_ids' => json_encode([$mainBranchId]),
                'default_reorder_point' => 15,
                'default_reorder_quantity' => 40,
                'default_safety_stock' => 8,
                'default_maximum_stock' => 300,
                'require_finance_approval_above' => 50000,
                'allow_auto_transfer' => false,
                'transfer_cost_model' => 'distance_based',
                'cost_per_km' => 12.5,
                'reporting_frequency' => 'weekly',
                'include_sub_branches' => true,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function seedBranchInventory(
        int $storeId,
        int $branchId,
        array $productIds,
        int $employeeId,
        Carbon $now
    ): void {
        $items = [
            [
                'sku' => 'SOFA-001',
                'quantity_on_hand' => 35,
                'quantity_reserved' => 5,
                'quantity_available' => 30,
                'quantity_damaged' => 1,
                'reorder_point' => 10,
                'reorder_quantity' => 25,
                'maximum_stock' => 80,
                'safety_stock' => 8,
                'stock_status' => 'in_stock',
                'average_cost' => 18500,
            ],
            [
                'sku' => 'BED-001',
                'quantity_on_hand' => 9,
                'quantity_reserved' => 1,
                'quantity_available' => 8,
                'quantity_damaged' => 0,
                'reorder_point' => 10,
                'reorder_quantity' => 20,
                'maximum_stock' => 60,
                'safety_stock' => 6,
                'stock_status' => 'low_stock',
                'average_cost' => 13200,
            ],
            [
                'sku' => 'TABLE-001',
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => 0,
                'quantity_damaged' => 0,
                'reorder_point' => 8,
                'reorder_quantity' => 16,
                'maximum_stock' => 50,
                'safety_stock' => 5,
                'stock_status' => 'out_of_stock',
                'average_cost' => 10800,
            ],
        ];

        foreach ($items as $item) {
            $totalValue = $item['quantity_on_hand'] * $item['average_cost'];

            DB::table('branch_inventory')->updateOrInsert(
                [
                    'branch_id' => $branchId,
                    'product_id' => $productIds[$item['sku']],
                    'variation_id' => null,
                ],
                [
                    'store_id' => $storeId,
                    'quantity_on_hand' => $item['quantity_on_hand'],
                    'quantity_reserved' => $item['quantity_reserved'],
                    'quantity_available' => $item['quantity_available'],
                    'quantity_damaged' => $item['quantity_damaged'],
                    'quantity_incoming' => 0,
                    'warehouse_section' => 'A',
                    'aisle' => '1',
                    'rack' => '1',
                    'shelf' => '1',
                    'bin_code' => 'BIN-' . str_replace('-', '', $item['sku']),
                    'reorder_point' => $item['reorder_point'],
                    'reorder_quantity' => $item['reorder_quantity'],
                    'maximum_stock' => $item['maximum_stock'],
                    'safety_stock' => $item['safety_stock'],
                    'stock_status' => $item['stock_status'],
                    'unit_cost' => $item['average_cost'],
                    'average_cost' => $item['average_cost'],
                    'total_value' => $totalValue,
                    'last_stock_count_date' => $now->toDateString(),
                    'last_counted_quantity' => $item['quantity_on_hand'],
                    'last_counted_by' => $employeeId,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedInventoryTransactions(
        int $storeId,
        int $branchId,
        array $productIds,
        int $employeeId,
        Carbon $now
    ): void {
        $transactions = [
            [
                'number' => 'ITX-20260309-0001',
                'sku' => 'SOFA-001',
                'type' => 'purchase',
                'before' => 20,
                'change' => 15,
                'after' => 35,
                'unit_cost' => 18500,
                'notes' => 'Initial inbound stock for seed data',
            ],
            [
                'number' => 'ITX-20260309-0002',
                'sku' => 'BED-001',
                'type' => 'sale',
                'before' => 12,
                'change' => -3,
                'after' => 9,
                'unit_cost' => 13200,
                'notes' => 'Recent sales movement',
            ],
            [
                'number' => 'ITX-20260309-0003',
                'sku' => 'TABLE-001',
                'type' => 'sale',
                'before' => 5,
                'change' => -5,
                'after' => 0,
                'unit_cost' => 10800,
                'notes' => 'Sold out to trigger out-of-stock fetch views',
            ],
        ];

        foreach ($transactions as $item) {
            DB::table('inventory_transactions')->updateOrInsert(
                ['transaction_number' => $item['number']],
                [
                    'store_id' => $storeId,
                    'branch_id' => $branchId,
                    'product_id' => $productIds[$item['sku']],
                    'variation_id' => null,
                    'transaction_type' => $item['type'],
                    'quantity_before' => $item['before'],
                    'quantity_change' => $item['change'],
                    'quantity_after' => $item['after'],
                    'reference_type' => 'seed',
                    'reference_id' => null,
                    'notes' => $item['notes'],
                    'unit_cost' => $item['unit_cost'],
                    'total_value' => $item['unit_cost'] * abs($item['change']),
                    'requires_approval' => false,
                    'approval_status' => null,
                    'approval_workflow_id' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'created_by' => $employeeId,
                    'transaction_date' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedStockAlerts(int $branchId, array $productIds, int $employeeId, Carbon $now): void
    {
        DB::table('stock_alerts')->updateOrInsert(
            [
                'branch_id' => $branchId,
                'product_id' => $productIds['BED-001'],
                'alert_type' => 'low_stock',
                'status' => 'active',
            ],
            [
                'variation_id' => null,
                'current_quantity' => 8,
                'reorder_point' => 10,
                'recommended_order_quantity' => 20,
                'acknowledged_by' => null,
                'acknowledged_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('stock_alerts')->updateOrInsert(
            [
                'branch_id' => $branchId,
                'product_id' => $productIds['TABLE-001'],
                'alert_type' => 'out_of_stock',
                'status' => 'acknowledged',
            ],
            [
                'variation_id' => null,
                'current_quantity' => 0,
                'reorder_point' => 8,
                'recommended_order_quantity' => 16,
                'acknowledged_by' => $employeeId,
                'acknowledged_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function seedReorderRules(int $branchId, array $productIds, Carbon $now): array
    {
        $rules = [
            [
                'sku' => 'BED-001',
                'rule_type' => 'automatic',
                'trigger_type' => 'reorder_point',
                'reorder_point' => 10,
                'reorder_quantity' => 20,
                'safety_stock' => 6,
                'maximum_stock' => 60,
                'priority' => 'high',
            ],
            [
                'sku' => 'TABLE-001',
                'rule_type' => 'manual',
                'trigger_type' => 'safety_stock',
                'reorder_point' => 8,
                'reorder_quantity' => 16,
                'safety_stock' => 5,
                'maximum_stock' => 50,
                'priority' => 'critical',
            ],
        ];

        $ids = [];
        foreach ($rules as $item) {
            DB::table('reorder_rules')->updateOrInsert(
                [
                    'product_id' => $productIds[$item['sku']],
                    'branch_id' => $branchId,
                ],
                [
                    'rule_type' => $item['rule_type'],
                    'trigger_type' => $item['trigger_type'],
                    'reorder_point' => $item['reorder_point'],
                    'reorder_quantity' => $item['reorder_quantity'],
                    'lead_time_days' => 7,
                    'safety_stock' => $item['safety_stock'],
                    'maximum_stock' => $item['maximum_stock'],
                    'economic_order_quantity' => $item['reorder_quantity'],
                    'priority' => $item['priority'],
                    'auto_generate_po' => false,
                    'is_active' => true,
                    'next_review_date' => $now->copy()->addMonth(),
                    'notes' => 'Initial seeded reorder rule',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $ids[$item['sku']] = (int) DB::table('reorder_rules')
                ->where('product_id', $productIds[$item['sku']])
                ->where('branch_id', $branchId)
                ->value('id');
        }

        return $ids;
    }

    private function seedReorderSuggestions(
        int $branchId,
        array $productIds,
        array $ruleIds,
        int $employeeId,
        Carbon $now
    ): void {
        DB::table('reorder_suggestions')->updateOrInsert(
            [
                'reorder_rule_id' => $ruleIds['BED-001'],
                'product_id' => $productIds['BED-001'],
                'branch_id' => $branchId,
                'status' => 'pending',
            ],
            [
                'suggestion_type' => 'automatic',
                'current_stock' => 8,
                'suggested_quantity' => 20,
                'estimated_cost' => 264000,
                'priority' => 'high',
                'reason' => 'Current stock dropped below reorder point',
                'metadata' => json_encode(['source' => 'seed']),
                'suggested_at' => $now,
                'approved_at' => null,
                'implemented_at' => null,
                'approved_by' => null,
                'implemented_by' => null,
                'approval_notes' => null,
                'implementation_notes' => null,
                'valid_until' => $now->copy()->addDays(14),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('reorder_suggestions')->updateOrInsert(
            [
                'reorder_rule_id' => $ruleIds['TABLE-001'],
                'product_id' => $productIds['TABLE-001'],
                'branch_id' => $branchId,
                'status' => 'approved',
            ],
            [
                'suggestion_type' => 'emergency',
                'current_stock' => 0,
                'suggested_quantity' => 16,
                'estimated_cost' => 172800,
                'priority' => 'critical',
                'reason' => 'Item is out of stock',
                'metadata' => json_encode(['source' => 'seed']),
                'suggested_at' => $now,
                'approved_at' => $now,
                'implemented_at' => null,
                'approved_by' => $employeeId,
                'implemented_by' => null,
                'approval_notes' => 'Approved for urgent replenishment',
                'implementation_notes' => null,
                'valid_until' => $now->copy()->addDays(7),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function seedNotifications(int $storeId, int $branchId, int $userId, Carbon $now): void
    {
        DB::table('inventory_notifications')->updateOrInsert(
            [
                'store_id' => $storeId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'title' => 'Low stock detected for Astra Queen Bed Frame',
            ],
            [
                'notification_type' => 'low_stock_alert',
                'entity_type' => 'stock_alert',
                'entity_id' => null,
                'message' => 'Available quantity is below reorder point. Review reorder suggestion.',
                'action_required' => true,
                'is_read' => false,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );
    }

    private function seedSerialNumbers(int $branchId, array $productIds, array $locationIds, Carbon $now): void
    {
        DB::table('serial_numbers')->updateOrInsert(
            ['serial_number' => 'SN-SOFA-001-0001'],
            [
                'product_id' => $productIds['SOFA-001'],
                'branch_id' => $branchId,
                'warehouse_location_id' => $locationIds['A1-R1-S1-B1'],
                'status' => 'available',
                'condition' => 'new',
                'purchase_price' => 18500,
                'selling_price' => 24999,
                'purchase_date' => $now->toDateString(),
                'warranty_expiry' => $now->copy()->addYears(1)->toDateString(),
                'notes' => 'Seeded serial number for demo fetch',
                'metadata' => json_encode(['source' => 'seed']),
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function seedBatches(int $branchId, array $productIds, array $locationIds, Carbon $now): void
    {
        DB::table('batches')->updateOrInsert(
            ['batch_number' => 'BATCH-BED-001-202603'],
            [
                'product_id' => $productIds['BED-001'],
                'branch_id' => $branchId,
                'warehouse_location_id' => $locationIds['A1-R2-S1-B1'],
                'quantity_produced' => 20,
                'quantity_available' => 9,
                'quantity_sold' => 11,
                'quantity_reserved' => 0,
                'quantity_damaged' => 0,
                'quantity_returned' => 0,
                'unit_cost' => 13200,
                'unit_price' => 18999,
                'production_date' => $now->copy()->subDays(15)->toDateString(),
                'expiry_date' => null,
                'best_before_date' => null,
                'status' => 'active',
                'quality_status' => 'approved',
                'supplier_name' => 'Default Supplier',
                'supplier_batch_number' => 'SUP-BED-202603',
                'notes' => 'Seeded inventory batch for testing',
                'specifications' => json_encode(['material' => 'engineered_wood']),
                'quality_test_results' => json_encode(['inspection' => 'passed']),
                'metadata' => json_encode(['source' => 'seed']),
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
