<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'crm.customers'],
            [
                'display_name' => 'Customers',
                'module' => 'crm',
                'route_name' => 'crm.customers',
                'route_path' => '/crm/customers',
                'icon' => 'pi pi-users',
                'parent_id' => null,
                'display_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $navigationId = DB::table('navigation_items')->where('name', 'crm.customers')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'crm.leads.view')->value('id');
        if ($navigationId && $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')->where('name', 'crm.customers')->value('id');
        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }
    }
};
