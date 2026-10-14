<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ecommerce_order_deliveries MODIFY status ENUM(
            'pending',
            'ready_for_dispatch',
            'assigned',
            'packed',
            'in_transit',
            'out_for_delivery',
            'delivered',
            'failed_delivery',
            'cancelled'
        ) NOT NULL DEFAULT 'assigned'");
    }

    public function down(): void
    {
        DB::table('ecommerce_order_deliveries')
            ->where('status', 'ready_for_dispatch')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE ecommerce_order_deliveries MODIFY status ENUM(
            'pending',
            'assigned',
            'packed',
            'in_transit',
            'out_for_delivery',
            'delivered',
            'failed_delivery',
            'cancelled'
        ) NOT NULL DEFAULT 'assigned'");
    }
};
