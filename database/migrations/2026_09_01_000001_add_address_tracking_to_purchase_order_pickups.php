<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_shipments', function (Blueprint $table): void {
            $table->text('current_address')->nullable()->after('current_longitude');
        });

        Schema::table('purchase_order_delivery_logs', function (Blueprint $table): void {
            $table->text('location_address')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_delivery_logs', fn (Blueprint $table) => $table->dropColumn('location_address'));
        Schema::table('purchase_order_shipments', fn (Blueprint $table) => $table->dropColumn('current_address'));
    }
};
