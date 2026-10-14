<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $items = [
            [
                'name' => 'sales.wholesale-quotes',
                'display_name' => 'Wholesale Quotes',
                'route_name' => 'sales.wholesale-quotes',
                'route_path' => '/sales/wholesale-quotes',
                'icon' => 'pi pi-file-edit',
                'display_order' => 4,
            ],
            [
                'name' => 'sales.transactions',
                'display_name' => 'Transactions',
                'route_name' => 'sales.transactions',
                'route_path' => '/sales/transactions',
                'icon' => 'pi pi-receipt',
                'display_order' => 5,
            ],
        ];

        foreach ($items as $item) {
            DB::table('navigation_items')->updateOrInsert(
                ['name' => $item['name']],
                [...$item, 'module' => 'sales', 'parent_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );

            $navigationId = DB::table('navigation_items')->where('name', $item['name'])->value('id');
            $permissionIds = DB::table('permissions')
                ->whereIn('name', ['sales.orders.view', 'sales.pos.view', 'sales.ecommerce-orders.view'])
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('navigation_permissions')->updateOrInsert(
                    ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('navigation_items')
            ->whereIn('name', ['sales.wholesale-quotes', 'sales.transactions'])
            ->pluck('id');
        DB::table('navigation_permissions')->whereIn('navigation_item_id', $ids)->delete();
        DB::table('navigation_items')->whereIn('id', $ids)->delete();
    }
};
