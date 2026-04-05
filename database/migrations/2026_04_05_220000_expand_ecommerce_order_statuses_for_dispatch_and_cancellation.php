<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ecommerce_orders MODIFY status ENUM(
            'pending',
            'processing',
            'ready_for_dispatch',
            'packed',
            'shipped',
            'in_transit',
            'out_for_delivery',
            'delivered',
            'pending_cancellation',
            'cancelled'
        ) DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ecommerce_orders MODIFY status ENUM(
            'pending',
            'processing',
            'packed',
            'shipped',
            'in_transit',
            'out_for_delivery',
            'delivered',
            'cancelled'
        ) DEFAULT 'pending'");
    }
};
