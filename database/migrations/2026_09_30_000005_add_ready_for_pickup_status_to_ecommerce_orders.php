<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ecommerce_orders MODIFY status ENUM(
            'pending','processing','ready_for_dispatch','ready_for_pickup','packed','shipped','in_transit',
            'out_for_delivery','delivered','pending_cancellation','cancelled','return_pending','return_approved',
            'return_received','refund_pending','refunded','replaced'
        ) DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('ecommerce_orders')
            ->where('status', 'ready_for_pickup')
            ->update(['status' => 'processing']);

        DB::statement("ALTER TABLE ecommerce_orders MODIFY status ENUM(
            'pending','processing','ready_for_dispatch','packed','shipped','in_transit','out_for_delivery',
            'delivered','pending_cancellation','cancelled','return_pending','return_approved',
            'return_received','refund_pending','refunded','replaced'
        ) DEFAULT 'pending'");
    }
};
