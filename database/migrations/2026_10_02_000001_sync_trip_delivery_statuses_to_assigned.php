<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ecommerce_order_deliveries')
            ->whereNotNull('trip_id')
            ->whereIn('status', ['pending', 'ready_for_dispatch'])
            ->update(['status' => 'assigned', 'updated_at' => now()]);

        DB::table('order_deliveries')
            ->whereNotNull('trip_id')
            ->whereIn('status', ['pending', 'ready_for_dispatch'])
            ->update(['status' => 'assigned', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Status progression is intentionally not reversed.
    }
};
