<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_bulky')->nullable()->after('weight_kg');
        });
        Schema::table('store_delivery_fee_settings', function (Blueprint $table) {
            $table->decimal('bulky_weight_threshold_kg', 10, 2)->nullable()->after('bulky_item_surcharge');
            $table->decimal('bulky_longest_side_threshold_cm', 10, 2)->nullable()->after('bulky_weight_threshold_kg');
            $table->decimal('bulky_volume_threshold_cbm', 10, 3)->nullable()->after('bulky_longest_side_threshold_cm');
        });
    }

    public function down(): void
    {
        Schema::table('store_delivery_fee_settings', fn (Blueprint $table) => $table->dropColumn(['bulky_weight_threshold_kg', 'bulky_longest_side_threshold_cm', 'bulky_volume_threshold_cbm']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('is_bulky'));
    }
};
