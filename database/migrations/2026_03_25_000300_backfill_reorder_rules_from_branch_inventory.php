<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reorder_rules') || !Schema::hasTable('branch_inventory')) {
            return;
        }

        $hasBasisType = Schema::hasColumn('reorder_rules', 'basis_type');

        // Create one rule per (branch_id, product_id) using the max values from branch_inventory (covers variants too).
        $rows = DB::table('branch_inventory')
            ->selectRaw('branch_id, product_id, MAX(COALESCE(reorder_point,0)) as reorder_point, MAX(COALESCE(reorder_quantity,0)) as reorder_quantity')
            ->whereNull('deleted_at')
            ->groupBy('branch_id', 'product_id')
            ->get();

        $now = now();

        foreach ($rows as $r) {
            $insert = [
                'product_id' => (int) $r->product_id,
                'branch_id' => (int) $r->branch_id,
                'rule_type' => 'manual',
                'trigger_type' => 'reorder_point',
                'reorder_point' => (float) ($r->reorder_point ?? 0),
                'reorder_quantity' => (float) ($r->reorder_quantity ?? 0),
                'priority' => 'medium',
                'auto_generate_po' => 0,
                'is_active' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if ($hasBasisType) {
                $insert['basis_type'] = 'reorder_point';
            }

            DB::table('reorder_rules')->updateOrInsert(
                [
                    'product_id' => (int) $r->product_id,
                    'branch_id' => (int) $r->branch_id,
                ],
                $insert
            );
        }
    }

    public function down(): void
    {
        // No-op: backfill only.
    }
};

