<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_orders', 'fulfillment_method')) {
                $table->string('fulfillment_method', 20)->default('delivery')->after('shipping_address');
            }
            if (!Schema::hasColumn('ecommerce_orders', 'pickup_date')) {
                $table->date('pickup_date')->nullable()->after('fulfillment_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_orders', 'pickup_date')) {
                $table->dropColumn('pickup_date');
            }
            if (Schema::hasColumn('ecommerce_orders', 'fulfillment_method')) {
                $table->dropColumn('fulfillment_method');
            }
        });
    }
};
