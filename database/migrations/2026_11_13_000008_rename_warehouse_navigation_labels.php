<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update only the labels shown in the warehouse sidebar. Route names and
     * permission links remain unchanged so existing access configuration holds.
     */
    public function up(): void
    {
        $this->updateLabels([
            'warehouse.stock' => 'Inventory',
            'warehouse.purchase-requisitions' => 'Stock Requests',
            'warehouse.reorder' => 'Reorder Suggestions',
            'warehouse.returns' => 'Returns & Damages',
            'warehouse.transfer-requests' => 'Transfers',
            'warehouse.transfer-history' => 'Transfer History',
        ]);
    }

    public function down(): void
    {
        $this->updateLabels([
            'warehouse.stock' => 'Stock',
            'warehouse.purchase-requisitions' => 'Replenishment Requests',
            'warehouse.reorder' => 'Reorder',
            'warehouse.returns' => 'Returns & Damage',
            'warehouse.transfer-requests' => 'Transfer Requests',
            'warehouse.transfer-history' => 'History',
        ]);
    }

    private function updateLabels(array $labels): void
    {
        foreach ($labels as $routeName => $label) {
            DB::table('navigation_items')
                ->where('module', 'warehouse')
                ->where('route_name', $routeName)
                ->whereNull('deleted_at')
                ->update([
                    'display_name' => $label,
                    'updated_at' => now(),
                ]);
        }
    }
};
