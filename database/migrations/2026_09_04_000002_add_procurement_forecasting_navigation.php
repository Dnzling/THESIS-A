<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'procurement.forecasting.view'],
            [
                'display_name' => 'View Procurement Forecasting',
                'module' => 'procurement',
                'description' => 'View multi-branch replenishment forecasts',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'procurement.analytics.forecasting'],
            [
                'display_name' => 'Replenishment Forecast',
                'module' => 'procurement',
                'section' => 'Analytics',
                'route_name' => 'procurement.analytics.forecasting',
                'route_path' => '/procurement/analytics/forecasting',
                'icon' => 'pi pi-chart-line',
                'parent_id' => null,
                'display_order' => 16,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Compare branch replenishment needs']),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $navId = DB::table('navigation_items')->where('name', 'procurement.analytics.forecasting')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'procurement.forecasting.view')->value('id');

        if ($navId && $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $navId = DB::table('navigation_items')->where('name', 'procurement.analytics.forecasting')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'procurement.forecasting.view')->value('id');

        if ($navId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navId)->delete();
            DB::table('navigation_items')->where('id', $navId)->delete();
        }
        if ($permissionId) {
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
