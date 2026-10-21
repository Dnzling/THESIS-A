<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'inventory.suppliers'],
            [
                'display_name' => 'Suppliers',
                'module' => 'inventory',
                'route_name' => 'inventory.suppliers',
                'route_path' => '/inventory/suppliers',
                'icon' => 'pi pi-truck',
                'parent_id' => null,
                'display_order' => 4,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'View inventory suppliers']),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $navigationId = DB::table('navigation_items')
            ->where('name', 'inventory.suppliers')
            ->value('id');
        $permissionId = DB::table('permissions')
            ->where('name', 'inventory.products.view')
            ->value('id');

        if ($navigationId && $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                [
                    'navigation_item_id' => $navigationId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')
            ->where('name', 'inventory.suppliers')
            ->value('id');

        if ($navigationId) {
            DB::table('navigation_permissions')
                ->where('navigation_item_id', $navigationId)
                ->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }
    }
};
