<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->foreignId('replacement_trip_id')
                ->nullable()
                ->after('replacement_inventory_id')
                ->constrained('logistics_delivery_trips')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_trip_id');
        });
    }
};
