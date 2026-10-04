<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(['name' => 'driver.trips.view'], [
            'display_name' => 'View Assigned Trips',
            'module' => 'logistics',
            'description' => 'View and complete trips assigned to the signed-in driver.',
            'is_active' => true,
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->where('name', 'driver.trips.view')->value('id');
        $navigationId = DB::table('navigation_items')->where('name', 'logistics.trips')->value('id');
        if ($permissionId && $navigationId) {
            DB::table('navigation_permissions')->updateOrInsert([
                'navigation_item_id' => $navigationId,
                'permission_id' => $permissionId,
            ], ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'driver.trips.view')->value('id');
        if ($permissionId) {
            DB::table('navigation_permissions')->where('permission_id', $permissionId)->delete();
        }
    }
};
