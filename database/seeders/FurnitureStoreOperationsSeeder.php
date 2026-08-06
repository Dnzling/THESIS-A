<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class FurnitureStoreOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $store = DB::table('stores')->where('store_code', 'CDC-003')->first();

        if (! $store) {
            throw new RuntimeException('Store CDC-003 does not exist. Run the main store seeders first.');
        }

        $branch = DB::table('branches')
            ->where('store_id', $store->id)
            ->where('branch_code', 'FS-DASMA-01')
            ->first();

        if (! $branch) {
            throw new RuntimeException('Branch FS-DASMA-01 does not exist. Run InventoryInitialSeeder first.');
        }

        $accounts = [
            [
                'email' => 'operations.admin@fsp.local',
                'role' => 'store_admin',
                'employee_number' => 'OPS-ADMIN-0001',
                'fname' => 'Operations',
                'lname' => 'Admin',
                'phone' => '09170000004',
                'department' => 'Operations',
                'salary' => 35000,
            ],
            [
                'email' => 'delivery.staff@fsp.local',
                'role' => 'delivery_staff',
                'employee_number' => 'DEL-STAFF-0001',
                'fname' => 'Delivery',
                'lname' => 'Staff',
                'phone' => '09170000005',
                'department' => 'Logistics',
                'salary' => 25000,
            ],
        ];

        DB::transaction(function () use ($accounts, $store, $branch, $now): void {
            $operationsUserId = null;

            foreach ($accounts as $account) {
                $role = DB::table('roles')->where('name', $account['role'])->first();

                if (! $role) {
                    throw new RuntimeException("Role {$account['role']} does not exist.");
                }

                DB::table('users')->updateOrInsert(
                    ['email' => $account['email']],
                    [
                        'fname' => $account['fname'],
                        'lname' => $account['lname'],
                        'store_id' => $store->id,
                        'branch_id' => $branch->id,
                        'password' => Hash::make('password123'),
                        'email_verified_at' => $now,
                        'role_id' => $role->id,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $userId = DB::table('users')->where('email', $account['email'])->value('id');
                if ($account['role'] === 'store_admin') {
                    $operationsUserId = (int) $userId;
                }

                DB::table('employees')->updateOrInsert(
                    ['store_id' => $store->id, 'employee_number' => $account['employee_number']],
                    [
                        'user_id' => $userId,
                        'branch_id' => $branch->id,
                        'fname' => $account['fname'],
                        'lname' => $account['lname'],
                        'phone' => $account['phone'],
                        'hire_date' => $now->toDateString(),
                        'role_id' => $role->id,
                        'department' => $account['department'],
                        'employment_type' => 'full_time',
                        'salary' => $account['salary'],
                        'status' => 'active',
                        'deleted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $vehicles = [
                ['vehicle_name' => 'Cedar Delivery Van 1', 'vehicle_type' => 'van', 'plate_number' => 'CDC-VAN-01', 'brand' => 'Toyota', 'model' => 'Hiace', 'color' => 'White', 'capacity_kg' => 1200, 'max_orders_per_trip' => 10],
                ['vehicle_name' => 'Cedar Delivery Truck 1', 'vehicle_type' => 'truck', 'plate_number' => 'CDC-TRK-01', 'brand' => 'Isuzu', 'model' => 'N-Series', 'color' => 'White', 'capacity_kg' => 3500, 'max_orders_per_trip' => 18],
                ['vehicle_name' => 'Cedar Delivery Motorcycle 1', 'vehicle_type' => 'motorcycle', 'plate_number' => 'CDC-MC-01', 'brand' => 'Honda', 'model' => 'TMX', 'color' => 'Red', 'capacity_kg' => 80, 'max_orders_per_trip' => 3],
            ];

            foreach ($vehicles as $vehicle) {
                DB::table('ecommerce_delivery_vehicles')->updateOrInsert(
                    ['store_id' => $store->id, 'plate_number' => $vehicle['plate_number']],
                    [
                        ...$vehicle,
                        'branch_id' => $branch->id,
                        'status' => 'active',
                        'is_active' => true,
                        'notes' => 'Default vehicle for Cedar and Co. delivery operations.',
                        'created_by' => $operationsUserId,
                        'updated_by' => $operationsUserId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $this->ensureRolePermissions('store_admin', [
                'sales.ecommerce-orders.view',
                'sales.ecommerce-orders.manage',
                'sales.order.approve',
                'logistics.deliveries.view',
                'logistics.deliveries.manage',
                'logistics.fleet.view',
                'logistics.fleet.manage',
            ], $now);

            $this->ensureRolePermissions('delivery_staff', [
                'logistics.deliveries.view',
                'logistics.deliveries.manage',
                'logistics.fleet.view',
            ], $now);
        });
    }

    private function ensureRolePermissions(string $roleName, array $permissionNames, $now): void
    {
        $roleId = DB::table('roles')->where('name', $roleName)->value('id');
        $permissions = DB::table('permissions')->whereIn('name', $permissionNames)->get(['id', 'name']);

        $missing = array_diff($permissionNames, $permissions->pluck('name')->all());
        if ($missing !== []) {
            throw new RuntimeException('Missing permissions: '.implode(', ', $missing));
        }

        foreach ($permissions as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permission->id],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
