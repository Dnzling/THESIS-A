<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('driver_user_id')
                    ->constrained('logistics_delivery_trips')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('trip_id')
                    ->constrained('ecommerce_delivery_vehicles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfers', 'vehicle_id')) {
                $table->dropConstrainedForeignId('vehicle_id');
            }
            if (Schema::hasColumn('stock_transfers', 'trip_id')) {
                $table->dropConstrainedForeignId('trip_id');
            }
        });
    }
};
