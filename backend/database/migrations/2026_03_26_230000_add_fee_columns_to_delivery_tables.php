<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('order_deliveries', 'distance_km')) {
                $table->decimal('distance_km', 10, 2)->nullable()->after('scheduled_delivery_at');
            }
            if (!Schema::hasColumn('order_deliveries', 'per_km_charge')) {
                $table->decimal('per_km_charge', 10, 2)->nullable()->after('distance_km');
            }
            if (!Schema::hasColumn('order_deliveries', 'estimated_fee')) {
                $table->decimal('estimated_fee', 12, 2)->nullable()->after('per_km_charge');
            }
        });

        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_order_deliveries', 'distance_km')) {
                $table->decimal('distance_km', 10, 2)->nullable()->after('estimated_delivery_at');
            }
            if (!Schema::hasColumn('ecommerce_order_deliveries', 'per_km_charge')) {
                $table->decimal('per_km_charge', 10, 2)->nullable()->after('distance_km');
            }
            if (!Schema::hasColumn('ecommerce_order_deliveries', 'estimated_fee')) {
                $table->decimal('estimated_fee', 12, 2)->nullable()->after('per_km_charge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('order_deliveries', 'estimated_fee')) {
                $dropColumns[] = 'estimated_fee';
            }
            if (Schema::hasColumn('order_deliveries', 'per_km_charge')) {
                $dropColumns[] = 'per_km_charge';
            }
            if (Schema::hasColumn('order_deliveries', 'distance_km')) {
                $dropColumns[] = 'distance_km';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('ecommerce_order_deliveries', 'estimated_fee')) {
                $dropColumns[] = 'estimated_fee';
            }
            if (Schema::hasColumn('ecommerce_order_deliveries', 'per_km_charge')) {
                $dropColumns[] = 'per_km_charge';
            }
            if (Schema::hasColumn('ecommerce_order_deliveries', 'distance_km')) {
                $dropColumns[] = 'distance_km';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
