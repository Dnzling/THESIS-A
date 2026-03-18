<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_shipments', function (Blueprint $table) {
            $table->decimal('tax_rate', 6, 2)->nullable()->after('shipping_cost');
            $table->date('expected_delivery_date')->nullable()->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_shipments', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'expected_delivery_date']);
        });
    }
};
