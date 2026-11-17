<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->decimal('original_unit_price', 12, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropColumn('original_unit_price');
        });
    }
};
