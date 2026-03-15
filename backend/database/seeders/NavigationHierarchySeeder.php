<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NavigationHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $groups = [
            // HR groups
            [
                'name' => 'hr.group.overview',
                'display_name' => 'Overview',
                'module' => 'hr',
                'route_name' => 'hr.group.overview',
                'route_path' => '#hr-overview',
                'icon' => 'pi pi-chart-bar',
                'display_order' => 1,
            ],
            [
                'name' => 'hr.group.people',
                'display_name' => 'People',
                'module' => 'hr',
                'route_name' => 'hr.group.people',
                'route_path' => '#hr-people',
                'icon' => 'pi pi-users',
                'display_order' => 2,
            ],
            [
                'name' => 'hr.group.time',
                'display_name' => 'Time & Attendance',
                'module' => 'hr',
                'route_name' => 'hr.group.time',
                'route_path' => '#hr-time',
                'icon' => 'pi pi-clock',
                'display_order' => 3,
            ],
            [
                'name' => 'hr.group.leave',
                'display_name' => 'Leave',
                'module' => 'hr',
                'route_name' => 'hr.group.leave',
                'route_path' => '#hr-leave',
                'icon' => 'pi pi-calendar',
                'display_order' => 4,
            ],
            [
                'name' => 'hr.group.payroll',
                'display_name' => 'Payroll',
                'module' => 'hr',
                'route_name' => 'hr.group.payroll',
                'route_path' => '#hr-payroll',
                'icon' => 'pi pi-money-bill',
                'display_order' => 5,
            ],
            [
                'name' => 'hr.group.hiring',
                'display_name' => 'Hiring',
                'module' => 'hr',
                'route_name' => 'hr.group.hiring',
                'route_path' => '#hr-hiring',
                'icon' => 'pi pi-briefcase',
                'display_order' => 6,
            ],
            [
                'name' => 'hr.group.settings',
                'display_name' => 'Settings',
                'module' => 'hr',
                'route_name' => 'hr.group.settings',
                'route_path' => '#hr-settings',
                'icon' => 'pi pi-cog',
                'display_order' => 7,
            ],

            // Inventory groups
            [
                'name' => 'inventory.group.overview',
                'display_name' => 'Overview',
                'module' => 'inventory',
                'route_name' => 'inventory.group.overview',
                'route_path' => '#inventory-overview',
                'icon' => 'pi pi-chart-line',
                'display_order' => 1,
            ],
            [
                'name' => 'inventory.group.catalog',
                'display_name' => 'Catalog',
                'module' => 'inventory',
                'route_name' => 'inventory.group.catalog',
                'route_path' => '#inventory-catalog',
                'icon' => 'pi pi-box',
                'display_order' => 2,
            ],
            [
                'name' => 'inventory.group.stock',
                'display_name' => 'Stock Movements',
                'module' => 'inventory',
                'route_name' => 'inventory.group.stock',
                'route_path' => '#inventory-stock',
                'icon' => 'pi pi-arrows-h',
                'display_order' => 3,
            ],
            [
                'name' => 'inventory.group.storage',
                'display_name' => 'Storage',
                'module' => 'inventory',
                'route_name' => 'inventory.group.storage',
                'route_path' => '#inventory-storage',
                'icon' => 'pi pi-building',
                'display_order' => 4,
            ],
            [
                'name' => 'inventory.group.reorder',
                'display_name' => 'Reorder',
                'module' => 'inventory',
                'route_name' => 'inventory.group.reorder',
                'route_path' => '#inventory-reorder',
                'icon' => 'pi pi-exclamation-triangle',
                'display_order' => 5,
            ],
            [
                'name' => 'inventory.group.tracking',
                'display_name' => 'Tracking',
                'module' => 'inventory',
                'route_name' => 'inventory.group.tracking',
                'route_path' => '#inventory-tracking',
                'icon' => 'pi pi-hashtag',
                'display_order' => 6,
            ],

            // Merchandising groups
            [
                'name' => 'merchandising.group.overview',
                'display_name' => 'Overview',
                'module' => 'merchandising',
                'route_name' => 'merchandising.group.overview',
                'route_path' => '#merchandising-overview',
                'icon' => 'pi pi-home',
                'display_order' => 1,
            ],
            [
                'name' => 'merchandising.group.catalog',
                'display_name' => 'Catalog',
                'module' => 'merchandising',
                'route_name' => 'merchandising.group.catalog',
                'route_path' => '#merchandising-catalog',
                'icon' => 'pi pi-tags',
                'display_order' => 2,
            ],
            [
                'name' => 'merchandising.group.inventory',
                'display_name' => 'Inventory',
                'module' => 'merchandising',
                'route_name' => 'merchandising.group.inventory',
                'route_path' => '#merchandising-inventory',
                'icon' => 'pi pi-database',
                'display_order' => 3,
            ],
        ];

        foreach ($groups as $group) {
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $group['name']],
                array_merge($group, [
                    'section' => 'group',
                    'is_active' => true,
                    'parent_id' => null,
                    'meta' => json_encode(['is_group' => true]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->updateDisplayNames();
        $this->assignHrParents();
        $this->assignInventoryParents();
        $this->assignMerchandisingParents();

        $this->command?->info('? Navigation hierarchy seeded successfully.');
    }

    private function updateDisplayNames(): void
    {
        DB::table('navigation_items')->where('name', 'hr.dashboard')->update(['display_name' => 'Overview']);
        DB::table('navigation_items')->where('name', 'inventory.dashboard')->update(['display_name' => 'Overview']);
        DB::table('navigation_items')->where('name', 'merchandising.dashboard')->update(['display_name' => 'Overview']);
    }

    private function assignHrParents(): void
    {
        $overview = $this->navId('hr.group.overview');
        $people = $this->navId('hr.group.people');
        $time = $this->navId('hr.group.time');
        $leave = $this->navId('hr.group.leave');
        $payroll = $this->navId('hr.group.payroll');
        $hiring = $this->navId('hr.group.hiring');
        $settings = $this->navId('hr.group.settings');

        $this->assignParent($overview, ['hr.dashboard']);
        $this->assignParent($people, ['hr.employees', 'hr.departments']);
        $this->assignParent($time, ['hr.shifts', 'hr.shifts.employees', 'hr.shifts.create', 'hr.attendance']);
        $this->assignParent($leave, ['hr.leave', 'hr.leave.balances']);
        $this->assignParent($payroll, ['hr.payroll', 'hr.payroll.overview', 'hr.payroll.periods', 'hr.payroll.list', 'hr.payroll.create']);
        $this->assignParent($settings, ['hr.settings']);

        // If job-hiring parent exists, attach it under Hiring group
        $jobHiringId = $this->navId('hr.job-hiring');
        if ($jobHiringId && $hiring) {
            DB::table('navigation_items')
                ->where('id', $jobHiringId)
                ->update(['parent_id' => $hiring]);
        }
    }

    private function assignInventoryParents(): void
    {
        $overview = $this->navId('inventory.group.overview');
        $catalog = $this->navId('inventory.group.catalog');
        $stock = $this->navId('inventory.group.stock');
        $storage = $this->navId('inventory.group.storage');
        $reorder = $this->navId('inventory.group.reorder');
        $tracking = $this->navId('inventory.group.tracking');

        $this->assignParent($overview, ['inventory.dashboard']);
        $this->assignParent($catalog, ['inventory.products', 'inventory.categories', 'inventory.units']);
        $this->assignParent($stock, ['inventory.stock-issues', 'inventory.stock-returns', 'inventory.stock-counts']);
        $this->assignParent($storage, ['inventory.warehouses', 'inventory.locations']);
        $this->assignParent($reorder, ['inventory.reorder-rules', 'inventory.reorder-suggestions']);
        $this->assignParent($tracking, ['inventory.serial-numbers', 'inventory.batches']);
    }

    private function assignMerchandisingParents(): void
    {
        $overview = $this->navId('merchandising.group.overview');
        $catalog = $this->navId('merchandising.group.catalog');
        $inventory = $this->navId('merchandising.group.inventory');

        $this->assignParent($overview, ['merchandising.dashboard']);
        $this->assignParent($catalog, ['merchandising.products', 'merchandising.products.create', 'merchandising.categories', 'merchandising.attributes']);
        $this->assignParent($inventory, ['merchandising.inventory']);
    }

    private function assignParent(?int $parentId, array $childNames): void
    {
        if (!$parentId) {
            return;
        }

        DB::table('navigation_items')
            ->whereIn('name', $childNames)
            ->whereNull('parent_id')
            ->update(['parent_id' => $parentId]);
    }

    private function navId(string $name): ?int
    {
        return DB::table('navigation_items')->where('name', $name)->value('id');
    }
}
