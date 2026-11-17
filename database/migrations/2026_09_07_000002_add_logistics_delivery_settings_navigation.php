<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'logistics.settings.view' => 'View Delivery Settings',
            'logistics.settings.manage' => 'Manage Delivery Settings',
        ];

        foreach ($permissions as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'module' => 'logistics',
                    'description' => 'Configure store delivery pricing and operating limits.',
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'logistics.settings'],
            [
                'display_name' => 'Delivery Settings',
                'module' => 'logistics',
                'section' => null,
                'route_name' => 'logistics.delivery-fees',
                'route_path' => '/logistics/delivery-fees',
                'icon' => 'pi pi-cog',
                'parent_id' => null,
                'display_order' => 5,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Delivery pricing and operating limits']),
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // The old Delivery Zones item points to this same page. Hide it to avoid
        // showing two navigation entries for one MVP settings screen.
        DB::table('navigation_items')->where('name', 'logistics.zones')->update([
            'is_active' => false,
            'updated_at' => $now,
        ]);

        $navigationId = DB::table('navigation_items')->where('name', 'logistics.settings')->value('id');
        $viewPermissionId = DB::table('permissions')->where('name', 'logistics.settings.view')->value('id');
        if ($navigationId && $viewPermissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $viewPermissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $fullAccessRoleIds = DB::table('roles')
            ->whereIn('name', ['super_admin', 'store_admin', 'owner', 'warehouse_manager'])
            ->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($permissions))->pluck('id');

        foreach ($fullAccessRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $branchManagerIds = DB::table('roles')->where('name', 'branch_manager')->pluck('id');
        foreach ($branchManagerIds as $roleId) {
            if ($viewPermissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $viewPermissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        foreach (DB::table('users')->select('id', 'store_id')->get() as $user) {
            Cache::forget("permissions:user:{$user->id}:store:global");
            if ($user->store_id) {
                Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            }
        }
    }

    public function down(): void
    {
        DB::table('navigation_items')->where('name', 'logistics.zones')->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['logistics.settings.view', 'logistics.settings.manage'])
            ->pluck('id');
        $navigationId = DB::table('navigation_items')->where('name', 'logistics.settings')->value('id');

        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
