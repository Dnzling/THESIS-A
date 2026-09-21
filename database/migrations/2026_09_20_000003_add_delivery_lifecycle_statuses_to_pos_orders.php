<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales_pos_orders MODIFY status ENUM(
            'pending_payment',
            'pending',
            'ready_for_dispatch',
            'completed',
            'voided',
            'refunded'
        ) NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        DB::table('sales_pos_orders')
            ->whereIn('status', ['pending', 'ready_for_dispatch'])
            ->update([
                'status' => DB::raw("CASE WHEN payment_status = 'paid' THEN 'completed' ELSE 'pending_payment' END"),
            ]);

        DB::statement("ALTER TABLE sales_pos_orders MODIFY status ENUM(
            'pending_payment',
            'completed',
            'voided',
            'refunded'
        ) NOT NULL DEFAULT 'pending_payment'");
    }
};
