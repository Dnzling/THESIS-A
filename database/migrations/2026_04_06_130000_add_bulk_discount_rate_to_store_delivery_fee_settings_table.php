<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_delivery_fee_settings', function (Blueprint $table) {
            $table->decimal('bulk_discount_rate', 5, 2)->default(10)->after('max_delivery_distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('store_delivery_fee_settings', function (Blueprint $table) {
            $table->dropColumn('bulk_discount_rate');
        });
    }
};
