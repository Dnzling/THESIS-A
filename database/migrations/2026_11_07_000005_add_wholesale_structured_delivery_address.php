<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->string('delivery_province', 150)->nullable();
            $table->string('delivery_city', 150)->nullable();
            $table->string('delivery_barangay', 150)->nullable();
            $table->string('delivery_address_line', 255)->nullable();
            $table->text('delivery_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->dropColumn(['delivery_province', 'delivery_city', 'delivery_barangay', 'delivery_address_line', 'delivery_notes']);
        });
    }
};
