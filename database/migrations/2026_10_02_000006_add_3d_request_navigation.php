<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('navigation_items')->updateOrInsert(['name' => 'merchandising.3d-requests'], [
            'display_name' => '3D Model Requests',
            'module' => 'merchandising',
            'route_name' => 'merchandising.3d-requests',
            'route_path' => '/merchandising/3d-requests',
            'icon' => 'pi pi-box',
            'parent_id' => null,
            'display_order' => 7,
            'meta' => json_encode(['subtitle' => 'Request and review store-owned 3D models']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $navigationId = DB::table('navigation_items')->where('name', 'merchandising.3d-requests')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'merchandising.products.view')->value('id');
        if ($navigationId && $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert([
                'navigation_item_id' => $navigationId,
                'permission_id' => $permissionId,
            ], ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')->where('name', 'merchandising.3d-requests')->value('id');
        if ($navigationId) DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        DB::table('navigation_items')->where('name', 'merchandising.3d-requests')->delete();
    }
};
