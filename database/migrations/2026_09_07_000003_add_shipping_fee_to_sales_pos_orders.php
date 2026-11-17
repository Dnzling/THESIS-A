<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table): void {
            $table->decimal('shipping_fee', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('delivery_distance_km', 10, 2)->nullable()->after('shipping_fee');
            $table->decimal('delivery_weight_kg', 12, 2)->default(0)->after('delivery_distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table): void {
            $table->dropColumn(['shipping_fee', 'delivery_distance_km', 'delivery_weight_kg']);
        });
    }
};
