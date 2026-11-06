<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->boolean('delivery_required')->default(false);
            $table->string('delivery_address', 1000)->nullable();
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();
            $table->decimal('shipping_fee', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->dropColumn(['delivery_required', 'delivery_address', 'delivery_latitude', 'delivery_longitude', 'shipping_fee']);
        });
    }
};
