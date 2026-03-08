<?php

namespace Database\Seeders;

use App\Models\Core\ApprovalRule;
use App\Models\Core\Permission;
use App\Models\Core\Role;
use App\Models\Core\RolePermission;
use App\Models\Core\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Seed permission atoms, default roles, role mappings, and baseline approval rules.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $permissions = $this->generatePermissionAtoms();

            foreach ($permissions as $permission) {
                Permission::query()->updateOrCreate(
                    ['name' => $permission['name']],
                    [
                        'display_name' => $permission['display_name'],
                        'module' => $permission['module'],
                        'description' => $permission['description'],
                        'is_active' => true,
                    ]
                );
            }

            $roles = [
                'owner' => Role::query()->updateOrCreate(
                    ['name' => 'owner'],
                    ['display_name' => 'Owner', 'description' => 'Business owner with complete store access', 'is_active' => true]
                ),
                'store_manager' => Role::query()->updateOrCreate(
                    ['name' => 'store_manager'],
                    ['display_name' => 'Store Manager', 'description' => 'Branch/store operations manager', 'is_active' => true]
                ),
                'staff' => Role::query()->updateOrCreate(
                    ['name' => 'staff'],
                    ['display_name' => 'Staff', 'description' => 'Operational user with limited permissions', 'is_active' => true]
                ),
            ];

            $ownerPermissions = Permission::query()->pluck('id')->all();

            $managerPermissions = Permission::query()
                ->where(function ($q): void {
                    $q->where('name', 'like', 'inventory.%')
                        ->orWhere('name', 'like', 'procurement.%')
                        ->orWhere('name', 'like', 'supplier.%')
                        ->orWhere('name', 'like', 'finance.%');
                })
                ->where(function ($q): void {
                    $q->where('name', 'not like', '%.all.%')
                        ->orWhere('name', 'like', '%.store.%');
                })
                ->pluck('id')
                ->all();

            $staffPermissions = Permission::query()
                ->where(function ($q): void {
                    $q->where('name', 'like', 'inventory.%')
                        ->orWhere('name', 'like', 'procurement.%')
                        ->orWhere('name', 'like', 'supplier.%');
                })
                ->where(function ($q): void {
                    $q->where('name', 'like', '%.view.own')
                        ->orWhere('name', 'like', '%.create.own')
                        ->orWhere('name', 'like', '%.edit.own');
                })
                ->pluck('id')
                ->all();

            $this->syncRolePermissions($roles['owner']->id, $ownerPermissions);
            $this->syncRolePermissions($roles['store_manager']->id, $managerPermissions);
            $this->syncRolePermissions($roles['staff']->id, $staffPermissions);

            $creatorId = User::query()->value('id') ?? 1;

            $defaultRules = [
                [
                    'name' => 'Self-created PO under 50k auto-approve',
                    'trigger_event' => 'po.create',
                    'conditions' => [
                        ['field' => 'amount', 'operator' => '<=', 'value' => 50000],
                        ['field' => 'is_dual_role', 'operator' => '==', 'value' => true],
                    ],
                    'actions' => [
                        ['type' => 'auto_approve', 'message' => 'Auto-approved: creator can also approve and amount is under threshold.'],
                    ],
                    'priority' => 10,
                ],
                [
                    'name' => 'High-value transaction requires manager',
                    'trigger_event' => 'inventory.adjust',
                    'conditions' => [
                        ['field' => 'amount', 'operator' => '>', 'value' => 50000],
                    ],
                    'actions' => [
                        ['type' => 'assign_to_role', 'role' => 'store_manager'],
                    ],
                    'priority' => 20,
                ],
                [
                    'name' => 'Inter-store transfer needs both managers',
                    'trigger_event' => 'inventory.transfer',
                    'conditions' => [
                        ['field' => 'transaction_type', 'operator' => 'in', 'value' => ['transfer_in', 'transfer_out']],
                    ],
                    'actions' => [
                        ['type' => 'assign_to_role', 'role' => 'store_manager'],
                    ],
                    'priority' => 30,
                ],
                [
                    'name' => 'Write-off over 10k needs manager and finance',
                    'trigger_event' => 'inventory.writeoff',
                    'conditions' => [
                        ['field' => 'amount', 'operator' => '>', 'value' => 10000],
                    ],
                    'actions' => [
                        ['type' => 'assign_to_role', 'role' => 'store_manager'],
                        ['type' => 'assign_to_role', 'role' => 'accountant'],
                    ],
                    'priority' => 40,
                ],
                [
                    'name' => 'Dual-role detection auto-approval',
                    'trigger_event' => 'inventory.adjust',
                    'conditions' => [
                        ['field' => 'is_dual_role', 'operator' => '==', 'value' => true],
                    ],
                    'actions' => [
                        ['type' => 'auto_approve', 'message' => 'Auto-approved via dual-role policy.'],
                    ],
                    'priority' => 50,
                ],
            ];

            foreach ($defaultRules as $rule) {
                ApprovalRule::query()->updateOrCreate(
                    ['name' => $rule['name']],
                    [
                        'trigger_event' => $rule['trigger_event'],
                        'conditions' => $rule['conditions'],
                        'actions' => $rule['actions'],
                        'priority' => $rule['priority'],
                        'store_id' => null,
                        'is_active' => true,
                        'created_by' => $creatorId,
                        'description' => 'Default approval policy seeded by PermissionSeeder.',
                    ]
                );
            }
        });
    }

    protected function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        RolePermission::query()->where('role_id', $roleId)->delete();

        foreach ($permissionIds as $permissionId) {
            RolePermission::query()->create([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    protected function generatePermissionAtoms(): array
    {
        $modules = ['inventory', 'procurement', 'supplier', 'finance'];
        $resources = ['transactions', 'settings', 'documents', 'workflows'];
        $actions = ['view', 'create', 'edit', 'delete', 'approve'];
        $scopes = ['own', 'store', 'all'];

        $atoms = [];

        foreach ($modules as $module) {
            foreach ($resources as $resource) {
                foreach ($actions as $action) {
                    foreach ($scopes as $scope) {
                        $name = sprintf('%s.%s.%s.%s', $module, $resource, $action, $scope);
                        $atoms[] = [
                            'name' => $name,
                            'display_name' => strtoupper($module) . ' ' . ucfirst($resource) . ' ' . ucfirst($action) . ' (' . strtoupper($scope) . ')',
                            'module' => $module,
                            'description' => 'Auto-generated permission atom for granular authorization.',
                        ];
                    }
                }
            }

            // Include global module-level approval atom used by default behavior fallback.
            $atoms[] = [
                'name' => sprintf('%s.all.approve', $module),
                'display_name' => strtoupper($module) . ' Global Approve',
                'module' => $module,
                'description' => 'Global approval permission fallback for module.',
            ];
        }

        return $atoms;
    }
}
