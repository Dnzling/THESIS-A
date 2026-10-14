<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->nullable()->after('price_adjustment');
            $table->decimal('discounted_price', 10, 2)->nullable()->after('base_price');
            $table->decimal('cost_price', 10, 2)->nullable()->after('discounted_price');
            $table->unsignedInteger('reorder_point')->default(0)->after('cost_price');
            $table->string('supplier_name')->nullable()->after('reorder_point');
            $table->string('unit_of_measurement', 50)->nullable()->after('supplier_name');
            $table->boolean('is_baseline')->default(false)->after('unit_of_measurement');
        });
    }

    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn([
                'base_price',
                'discounted_price',
                'cost_price',
                'reorder_point',
                'supplier_name',
                'unit_of_measurement',
                'is_baseline',
            ]);
        });
    }
};
