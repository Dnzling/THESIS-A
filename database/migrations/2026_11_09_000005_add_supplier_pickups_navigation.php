<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAVIGATION_NAME = 'logistics.supplier-pickups';
    private const ROUTE_PATH = '/logistics/supplier-pickups';
    private const PERMISSIONS = [
        'logistics.supplier-pickups.view' => [
            'View Supplier Pickups',
            'View supplier pickup schedules, suppliers, statuses, and delivery details.',
        ],
        'logistics.supplier-pickups.manage' => [
            'Manage Supplier Pickups',
            'Assign drivers and vehicles to supplier pickups.',
        ],
        'logistics.driver.supplier-pickups' => [
            'Process Assigned Supplier Pickups',
            'Update status and submit pickup proof for supplier pickups assigned to the driver.',
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name => [$displayName, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'module' => 'logistics',
                'description' => $description,
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $moduleId = DB::table('modules')->where('key', 'logistics')->value('id');
        if ($moduleId) {
            foreach (self::PERMISSIONS as $name => [$displayName, $description]) {
                DB::table('module_permissions')->updateOrInsert(
                    ['module_id' => $moduleId, 'permission_key' => $name],
                    ['description' => $description, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                );
            }
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id', 'name');
        foreach ($permissionIds as $name => $permissionId) {
            foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $permissionId],
                    ['included' => true, 'created_at' => $now, 'updated_at' => $now],
                );
            }

            $sourcePermission = match ($name) {
                'logistics.supplier-pickups.view' => 'logistics.deliveries.view',
                'logistics.supplier-pickups.manage' => 'logistics.deliveries.manage',
                default => null,
            };
            $roleIds = $sourcePermission
                ? DB::table('role_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                    ->where('permissions.name', $sourcePermission)
                    ->pluck('role_permissions.role_id')
                : collect();
            $ownerRoleIds = DB::table('roles')->where('name', 'owner')->pluck('id');

            foreach ($roleIds->merge($ownerRoleIds)->unique() as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }

        $navigation = DB::table('navigation_items')
            ->where('name', self::NAVIGATION_NAME)
            ->orWhere('route_name', self::NAVIGATION_NAME)
            ->orWhere('route_path', self::ROUTE_PATH)
            ->first();

        $values = [
            'display_name' => 'Supplier Pickups',
            'module' => 'logistics',
            'section' => 'Logistics',
            'route_name' => self::NAVIGATION_NAME,
            'route_path' => self::ROUTE_PATH,
            'icon' => 'pi pi-box',
            'parent_id' => null,
            'display_order' => 2,
            'is_active' => true,
            'deleted_at' => null,
            'meta' => json_encode(['subtitle' => 'Track supplier collections and delivery to the store']),
            'updated_at' => $now,
            'created_at' => $now,
        ];

        if ($navigation) {
            DB::table('navigation_items')->where('id', $navigation->id)->update($values);
            $navigationId = (int) $navigation->id;
        } else {
            DB::table('navigation_items')->insert(['name' => self::NAVIGATION_NAME, ...$values]);
            $navigationId = (int) DB::table('navigation_items')->where('name', self::NAVIGATION_NAME)->value('id');
        }

        $permissionId = $permissionIds->get('logistics.supplier-pickups.view');
        DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        if ($permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        DB::table('users')->select('id', 'store_id')->get()->each(function ($user): void {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) {
                Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            }
        });
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')
            ->where('name', self::NAVIGATION_NAME)
            ->orWhere('route_name', self::NAVIGATION_NAME)
            ->orWhere('route_path', self::ROUTE_PATH)
            ->value('id');

        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('plan_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('module_permissions')->whereIn('permission_key', array_keys(self::PERMISSIONS))->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
