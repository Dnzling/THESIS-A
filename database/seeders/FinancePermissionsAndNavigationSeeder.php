<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancePermissionsAndNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $submodules = [
            'dashboard',
            'payables',
            'receivables',
            'expenses',
            'payroll',
            'budgets',
            'reports',
            'cashflow',
            'invoices',
            'purchase-orders',
            'price-approvals',
            'refunds',
        ];

        // Standardize finance permissions to a minimal, predictable set
        // - view: read-only access
        // - manage: create/update (non-final actions)
        // - approve: financial approvals/authorizations
        // - delete: destructive removal
        $actions = ['view', 'manage', 'approve', 'delete'];

        foreach ($submodules as $submodule) {
            foreach ($actions as $action) {
                $name = "finance.{$submodule}.{$action}";
                DB::table('permissions')->updateOrInsert(
                    ['name' => $name],
                    [
                        'display_name' => $this->displayName($submodule, $action),
                        'module' => 'finance',
                        'description' => "{$action} {$submodule} in finance module.",
                        'is_active' => 1,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        // Compatibility aliases still used in some legacy checks.
        $compat = [
            'finance.purchase_orders.view' => 'finance.purchase-orders.view',
            'finance.purchase_orders.manage' => 'finance.purchase-orders.manage',
            'finance.purchase_orders.approve' => 'finance.purchase-orders.approve',
        ];

        foreach ($compat as $legacyName => $sourceName) {
            $source = DB::table('permissions')->where('name', $sourceName)->first();
            if (!$source) {
                continue;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $legacyName],
                [
                    'display_name' => $source->display_name,
                    'module' => 'finance',
                    'description' => 'Legacy alias for backward compatibility.',
                    'is_active' => 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $roleNames = ['super_admin', 'store_admin', 'owner', 'accountant'];
        $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
        $financePermissionIds = DB::table('permissions')->where('name', 'like', 'finance.%')->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($financePermissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        // Ensure finance cashflow and price-approval navigation entries exist.
        $navItems = [
            [
                'name' => 'finance.refunds',
                'display_name' => 'Refunds',
                'module' => 'finance',
                'section' => 'refunds',
                'route_name' => 'finance.refunds',
                'route_path' => '/finance/refunds',
                'icon' => 'pi pi-replay',
                'display_order' => 8,
            ],
            [
                'name' => 'finance.cashflow',
                'display_name' => 'Cashflow',
                'module' => 'finance',
                'section' => 'cashflow',
                'route_name' => 'finance.cashflow',
                'route_path' => '/finance/cashflow',
                'icon' => 'pi pi-chart-line',
                'display_order' => 9,
            ],
            [
                'name' => 'finance.price-approvals',
                'display_name' => 'Price Approvals',
                'module' => 'finance',
                'section' => 'pricing',
                'route_name' => 'finance.price-approvals',
                'route_path' => '/finance/price-approvals',
                'icon' => 'pi pi-sliders-h',
                'display_order' => 10,
            ],
        ];

        foreach ($navItems as $item) {
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'display_name' => $item['display_name'],
                    'module' => $item['module'],
                    'section' => $item['section'],
                    'route_name' => $item['route_name'],
                    'route_path' => $item['route_path'],
                    'icon' => $item['icon'],
                    'display_order' => $item['display_order'],
                    'is_active' => 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $navPermissionMap = [
            'finance.dashboard' => 'finance.dashboard.view',
            'finance.payables' => 'finance.payables.view',
            'finance.receivables' => 'finance.receivables.view',
            'finance.expenses' => 'finance.expenses.view',
            'finance.payroll' => 'finance.payroll.view',
            'finance.budgets' => 'finance.budgets.view',
            'finance.reports' => 'finance.reports.view',
            'finance.purchase-orders' => 'finance.purchase-orders.view',
            'finance.price-approvals' => 'finance.price-approvals.view',
            'finance.cashflow' => 'finance.cashflow.view',
            'finance.refunds' => 'finance.refunds.view',
        ];

        foreach ($navPermissionMap as $navName => $permissionName) {
            $navigationItemId = DB::table('navigation_items')->where('name', $navName)->value('id');
            $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');

            if (!$navigationItemId || !$permissionId) {
                continue;
            }

            DB::table('navigation_permissions')->updateOrInsert(
                [
                    'navigation_item_id' => $navigationItemId,
                    'permission_id' => $permissionId,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function displayName(string $submodule, string $action): string
    {
        $normalized = str_replace('-', ' ', $submodule);
        $title = ucwords($normalized);
        return "Finance {$title} " . ucfirst($action);
    }
}
