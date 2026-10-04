<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach ([
            'inventory.purchase_orders.view' => 'View Inventory Purchase Orders',
            'inventory.purchase_orders.manage' => 'Manage Inventory Purchase Orders',
        ] as $name => $label) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $label,
                'module' => 'inventory',
                'description' => 'Direct purchase orders in Inventory.',
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $viewId = DB::table('permissions')->where('name', 'inventory.purchase_orders.view')->value('id');
        $manageId = DB::table('permissions')->where('name', 'inventory.purchase_orders.manage')->value('id');
        $productViewId = DB::table('permissions')->where('name', 'inventory.products.view')->value('id');
        $productCreateId = DB::table('permissions')->where('name', 'inventory.products.manage')->value('id')
            ?? DB::table('permissions')->where('name', 'inventory.products.create')->value('id');

        foreach ([[$productViewId, $viewId], [$productCreateId, $manageId]] as [$sourceId, $targetId]) {
            if (!$sourceId || !$targetId) continue;
            foreach (DB::table('role_permissions')->where('permission_id', $sourceId)->pluck('role_id') as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $targetId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
            foreach (DB::table('plan_permissions')->where('permission_id', $sourceId)->where('included', true)->pluck('plan_id') as $planId) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $planId, 'permission_id' => $targetId],
                    ['included' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        DB::table('navigation_items')->updateOrInsert(['name' => 'inventory.purchase-orders'], [
            'display_name' => 'Purchase Orders',
            'module' => 'inventory',
            'route_name' => 'inventory.purchase-orders.index',
            'route_path' => '/inventory/purchase-orders',
            'icon' => 'pi pi-file-edit',
            'parent_id' => null,
            'display_order' => 5,
            'is_active' => true,
            'meta' => json_encode(['subtitle' => 'Create and track supplier purchase orders']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $navId = DB::table('navigation_items')->where('name', 'inventory.purchase-orders')->value('id');
        if ($navId && $viewId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navId, 'permission_id' => $viewId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $navId = DB::table('navigation_items')->where('name', 'inventory.purchase-orders')->value('id');
        if ($navId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navId)->delete();
            DB::table('navigation_items')->where('id', $navId)->delete();
        }
        $ids = DB::table('permissions')->whereIn('name', ['inventory.purchase_orders.view', 'inventory.purchase_orders.manage'])->pluck('id');
        DB::table('plan_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
