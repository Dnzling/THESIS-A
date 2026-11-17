<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'inventory.receiving.view' => [
            'View Goods Receipts',
            'View goods receipts and receiving records in Inventory.',
        ],
        'inventory.receiving.manage' => [
            'Manage Goods Receipts',
            'Create and manage goods receipts in Inventory.',
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name => [$displayName, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $displayName,
                'module' => 'inventory',
                'description' => $description,
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        DB::table('modules')->updateOrInsert(['key' => 'inventory'], [
            'name' => 'Inventory',
            'is_active' => true,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $moduleId = DB::table('modules')->where('key', 'inventory')->value('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSIONS))
            ->pluck('id', 'name');

        foreach ($permissionIds as $name => $permissionId) {
            DB::table('module_permissions')->updateOrInsert(
                ['module_id' => $moduleId, 'permission_key' => $name],
                [
                    'description' => self::PERMISSIONS[$name][1],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $permissionId],
                    ['included' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }

            foreach (DB::table('roles')->where('name', 'owner')->pluck('id') as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        $navigation = DB::table('navigation_items')
            ->where('name', 'inventory.goods-receipts')
            ->orWhere('route_name', 'inventory.goods-receipts')
            ->orWhere('route_path', '/inventory/goods-receipts')
            ->first();

        $navigationValues = [
            'display_name' => 'Goods Receipts',
            'module' => 'inventory',
            'section' => 'Inventory',
            'route_name' => 'inventory.goods-receipts',
            'route_path' => '/inventory/goods-receipts',
            'icon' => 'pi pi-arrow-down',
            'parent_id' => null,
            'display_order' => 6,
            'is_active' => true,
            'deleted_at' => null,
            'meta' => json_encode(['subtitle' => 'Review and manage received purchase order items']),
            'updated_at' => $now,
            'created_at' => $now,
        ];

        if ($navigation) {
            DB::table('navigation_items')->where('id', $navigation->id)->update($navigationValues);
            $navigationId = (int) $navigation->id;
        } else {
            DB::table('navigation_items')->insert([
                'name' => 'inventory.goods-receipts',
                ...$navigationValues,
            ]);
            $navigationId = (int) DB::table('navigation_items')
                ->where('name', 'inventory.goods-receipts')
                ->value('id');
        }

        DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        $viewPermissionId = $permissionIds->get('inventory.receiving.view');
        if ($viewPermissionId) {
            DB::table('navigation_permissions')->insert([
                'navigation_item_id' => $navigationId,
                'permission_id' => $viewPermissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')->whereNotNull('store_id')->get(['id', 'store_id'])->each(function ($user): void {
            Cache::forget("permissions:user:{$user->id}:store:{$user->store_id}");
            Cache::forget("permissions:user:{$user->id}:store:global");
        });
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')
            ->where('route_name', 'inventory.goods-receipts')
            ->orWhere('route_path', '/inventory/goods-receipts')
            ->value('id');

        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();

            $legacyPermissionId = DB::table('permissions')
                ->where('name', 'procurement.invoices.view')
                ->value('id');

            if ($legacyPermissionId) {
                DB::table('navigation_permissions')->insert([
                    'navigation_item_id' => $navigationId,
                    'permission_id' => $legacyPermissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
