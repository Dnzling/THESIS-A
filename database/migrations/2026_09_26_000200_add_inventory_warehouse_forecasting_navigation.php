<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach (['inventory', 'warehouse'] as $module) {
            $name = $module.'.forecasting';
            $permission = $module === 'inventory' ? 'inventory.reorder_suggestions.view' : 'warehouse.reorder.view';
            DB::table('navigation_items')->updateOrInsert(['name' => $name], [
                'display_name' => 'Forecasting',
                'module' => $module,
                'section' => null,
                'route_name' => $name,
                'route_path' => '/'.$module.'/forecasting',
                'icon' => 'pi pi-chart-line',
                'parent_id' => null,
                'display_order' => 6,
                'is_active' => true,
                'meta' => json_encode(['group_label' => $module === 'warehouse' ? 'Warehouse Operations' : 'Inventory']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $navigationId = DB::table('navigation_items')->where('name', $name)->value('id');
            $permissionId = DB::table('permissions')->where('name', $permission)->value('id');
            if ($permissionId) {
                DB::table('navigation_permissions')->updateOrInsert(
                    ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Preserve customized navigation permissions.
    }
};
