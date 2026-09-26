<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM purchase_requisitions LIKE 'status'");
        preg_match_all("/'([^']*)'/", (string) ($column->Type ?? ''), $matches);

        // Keep values already present in deployed schemas (some installations use
        // `pending` while newer migrations use `submitted`) to avoid truncating rows.
        $statuses = array_values(array_unique(array_merge($matches[1] ?? [], [
            'draft',
            'pending',
            'submitted',
            'warehouse_approved',
            'branch_manager_approved',
            'pending_central_review',
            'procurement_processing',
            'rfq_sent',
            'quotes_received',
            'supplier_selected',
            'po_created',
            'in_transit',
            'delivered',
            'rejected',
            'cancelled',
        ])));

        $enumValues = implode(',', array_map(
            fn (string $status): string => DB::connection()->getPdo()->quote($status),
            $statuses
        ));

        DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM({$enumValues}) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Keep delivery lifecycle values: existing requisitions may already use them,
        // and shrinking the enum could discard valid operational statuses.
    }
};
