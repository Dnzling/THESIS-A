<?php

namespace Database\Seeders\Permission\Inventory;

use App\Models\Core\User;
use App\Services\Core\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockRequestsNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'inventory.stock-requests.view'],
            [
                'display_name' => 'View Stock Requests',
                'module' => 'inventory',
                'description' => 'View supplier purchase requests and warehouse stock requests.',
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
        $permissionId = DB::table('permissions')
            ->where('name', 'inventory.stock-requests.view')
            ->value('id');

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'inventory.stock-requests'],
            [
                'display_name' => 'Stock Requests',
                'module' => 'inventory',
                'section' => null,
                'route_name' => 'inventory.stock-requests',
                'route_path' => '/inventory/stock-requests',
                'icon' => 'pi pi-file-edit',
                'parent_id' => null,
                'display_order' => 5,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Request supplier replenishment or stock from a warehouse']),
                'deleted_at' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
        $navigationId = DB::table('navigation_items')
            ->where('name', 'inventory.stock-requests')
            ->value('id');

        DB::table('navigation_permissions')->updateOrInsert(
            ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
            ['updated_at' => $now, 'created_at' => $now]
        );

        foreach (DB::table('subscription_plans')->pluck('id') as $planId) {
            DB::table('plan_permissions')->updateOrInsert(
                ['plan_id' => $planId, 'permission_id' => $permissionId],
                ['included' => true, 'updated_at' => $now]
            );
        }

        $ownerRoleIds = DB::table('roles')
            ->where('name', 'owner')
            ->pluck('id');

        foreach ($ownerRoleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        if ($ownerRoleIds->isNotEmpty()) {
            $permissionService = app(PermissionService::class);
            User::query()
                ->whereIn('role_id', $ownerRoleIds)
                ->chunkById(100, function ($owners) use ($permissionService): void {
                    foreach ($owners as $owner) {
                        $permissionService->clearUserCache($owner);
                    }
                });
        }

        DB::table('navigation_items')
            ->whereIn('name', ['Purchase Requisitions', 'inventory.transfers'])
            ->update(['is_active' => false, 'deleted_at' => $now, 'updated_at' => $now]);
    }
}
