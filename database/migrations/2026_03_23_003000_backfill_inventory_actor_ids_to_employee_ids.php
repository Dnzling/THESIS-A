<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables/columns where actor fields should point to employees.id.
     */
    private array $targets = [
        'stock_transfers' => [
            'requested_by',
            'sender_approved_by',
            'receiver_acknowledged_by',
            'finance_approved_by',
            'shipped_by',
            'received_by',
        ],
        'stock_adjustments' => ['created_by', 'approved_by'],
        'stock_issues' => ['requested_by', 'created_by', 'approved_by', 'updated_by'],
        'stock_returns' => ['requested_by', 'approved_by', 'shipped_by', 'received_by'],
        'inventory_transactions' => ['created_by', 'approved_by'],
        'stock_alerts' => ['acknowledged_by', 'resolved_by'],
        'stock_counts' => ['assigned_by', 'assigned_to', 'supervised_by', 'approved_by'],
        'count_sheets' => ['counted_by'],
        'reorder_suggestions' => ['approved_by', 'implemented_by'],
        'branch_inventory' => ['last_counted_by'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        foreach ($this->targets as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                // Convert legacy user_id values to employees.id values only when
                // current value is not already a valid employees.id.
                DB::statement("
                    UPDATE `{$table}` t
                    JOIN `employees` e ON t.`{$column}` = e.`user_id`
                    LEFT JOIN `employees` ee ON ee.`id` = t.`{$column}`
                    SET t.`{$column}` = e.`id`
                    WHERE t.`{$column}` IS NOT NULL
                      AND ee.`id` IS NULL
                ");
            }
        }
    }

    public function down(): void
    {
        // Irreversible backfill.
    }
};

