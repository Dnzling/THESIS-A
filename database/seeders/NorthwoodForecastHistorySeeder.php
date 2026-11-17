<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NorthwoodForecastHistorySeeder extends Seeder
{
    private const STORE_ID = 1;

    /**
     * Synthetic demand totals for the 60-day forecasting window.
     *
     * @var array<int, array{branch_id: int, product_id: int, variation_id: int|null, employee_id: int, units: int}>
     */
    private const DEMAND = [
        ['branch_id' => 1, 'product_id' => 140, 'variation_id' => null, 'employee_id' => 3, 'units' => 18],
        ['branch_id' => 1, 'product_id' => 141, 'variation_id' => null, 'employee_id' => 3, 'units' => 30],
        ['branch_id' => 1, 'product_id' => 141, 'variation_id' => 115, 'employee_id' => 3, 'units' => 14],
        ['branch_id' => 1, 'product_id' => 141, 'variation_id' => 116, 'employee_id' => 3, 'units' => 20],
        ['branch_id' => 1, 'product_id' => 144, 'variation_id' => null, 'employee_id' => 3, 'units' => 8],
        ['branch_id' => 3, 'product_id' => 140, 'variation_id' => null, 'employee_id' => 9, 'units' => 22],
        ['branch_id' => 3, 'product_id' => 141, 'variation_id' => 115, 'employee_id' => 9, 'units' => 18],
        ['branch_id' => 3, 'product_id' => 141, 'variation_id' => 116, 'employee_id' => 9, 'units' => 10],
        ['branch_id' => 3, 'product_id' => 144, 'variation_id' => null, 'employee_id' => 9, 'units' => 6],
    ];

    /** @var list<string> */
    private const MOVEMENT_DATES = [
        '2026-09-14 10:15:00',
        '2026-09-18 14:30:00',
        '2026-09-23 11:20:00',
        '2026-09-28 15:10:00',
        '2026-10-03 10:40:00',
        '2026-10-08 13:25:00',
        '2026-10-13 16:05:00',
        '2026-10-18 11:45:00',
        '2026-10-23 14:15:00',
        '2026-10-28 10:30:00',
        '2026-11-02 15:20:00',
        '2026-11-07 12:10:00',
    ];

    public function run(): void
    {
        $this->assertTargetStore();

        DB::transaction(function (): void {
            foreach (self::DEMAND as $demand) {
                $inventory = $this->inventoryRow($demand);
                $this->assertEmployee($demand['employee_id'], $demand['branch_id']);
                $this->seedDemand($inventory, $demand);
            }
        });

        $this->command?->info('Northwood 60-day forecasting history seeded for 2026-09-12 through 2026-11-10.');
    }

    private function assertTargetStore(): void
    {
        $store = DB::table('stores as s')
            ->leftJoin('subscription_plans as p', 'p.id', '=', 's.subscription_tier')
            ->where('s.id', self::STORE_ID)
            ->first(['s.name', 'p.plan_key']);

        if (!$store || $store->name !== 'Northwood Living') {
            throw new RuntimeException('Store 1 is not Northwood Living; forecasting history was not seeded.');
        }

        if (!in_array(strtolower((string) $store->plan_key), ['enterprise', 'unlimited'], true)) {
            throw new RuntimeException('Northwood Living is not on an Enterprise-compatible plan.');
        }
    }

    private function inventoryRow(array $demand): object
    {
        $query = DB::table('branch_inventory')
            ->where('store_id', self::STORE_ID)
            ->where('branch_id', $demand['branch_id'])
            ->where('product_id', $demand['product_id'])
            ->whereNull('deleted_at');

        $query = $demand['variation_id'] === null
            ? $query->whereNull('variation_id')
            : $query->where('variation_id', $demand['variation_id']);

        $inventory = $query->first([
            'id',
            'quantity_on_hand',
        ]);

        if (!$inventory) {
            throw new RuntimeException(sprintf(
                'Missing branch inventory for branch %d, product %d, variation %s.',
                $demand['branch_id'],
                $demand['product_id'],
                $demand['variation_id'] ?? 'none',
            ));
        }

        return $inventory;
    }

    private function assertEmployee(int $employeeId, int $branchId): void
    {
        $valid = DB::table('employees')
            ->where('id', $employeeId)
            ->where('store_id', self::STORE_ID)
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->exists();

        if (!$valid) {
            throw new RuntimeException("Employee {$employeeId} is not an active employee of branch {$branchId}.");
        }
    }

    private function seedDemand(object $inventory, array $demand): void
    {
        $movementCount = min($demand['units'], count(self::MOVEMENT_DATES));
        $quantities = $this->distributedQuantities($demand['units'], $movementCount);
        $dates = $this->distributedDates($movementCount);
        $balance = (int) $inventory->quantity_on_hand + $demand['units'];
        $unitCost = 0.0;
        $variationKey = $demand['variation_id'] ?? 0;

        foreach ($quantities as $index => $quantity) {
            $before = $balance;
            $balance -= $quantity;
            $transactionDate = CarbonImmutable::parse($dates[$index], 'Asia/Manila');
            $number = sprintf(
                'NFH26-%d-%d-%d-%02d',
                $demand['branch_id'],
                $demand['product_id'],
                $variationKey,
                $index + 1,
            );

            DB::table('inventory_transactions')->updateOrInsert(
                ['transaction_number' => $number],
                [
                    'store_id' => self::STORE_ID,
                    'branch_id' => $demand['branch_id'],
                    'product_id' => $demand['product_id'],
                    'variation_id' => $demand['variation_id'],
                    'transaction_type' => 'sale',
                    'quantity_before' => $before,
                    'quantity_change' => -$quantity,
                    'quantity_after' => $balance,
                    'related_branch_id' => null,
                    'reference_type' => 'forecast_history_seed',
                    'reference_id' => null,
                    'notes' => 'Synthetic historical sale for the Northwood 60-day forecasting demo.',
                    'unit_cost' => $unitCost,
                    'total_value' => round($unitCost * $quantity, 2),
                    'requires_approval' => false,
                    'approval_status' => null,
                    'approval_workflow_id' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'created_by' => $demand['employee_id'],
                    'transaction_date' => $transactionDate,
                    'created_at' => $transactionDate,
                    'updated_at' => now(),
                ],
            );
        }
    }

    /** @return list<int> */
    private function distributedQuantities(int $units, int $count): array
    {
        $base = intdiv($units, $count);
        $remainder = $units % $count;

        return array_map(
            fn (int $index): int => $base + ($index < $remainder ? 1 : 0),
            range(0, $count - 1),
        );
    }

    /** @return list<string> */
    private function distributedDates(int $count): array
    {
        if ($count === 1) {
            return [self::MOVEMENT_DATES[array_key_last(self::MOVEMENT_DATES)]];
        }

        $lastIndex = count(self::MOVEMENT_DATES) - 1;

        return array_map(
            fn (int $index): string => self::MOVEMENT_DATES[(int) round($index * $lastIndex / ($count - 1))],
            range(0, $count - 1),
        );
    }
}
