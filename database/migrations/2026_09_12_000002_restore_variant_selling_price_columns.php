<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variations', 'base_price')) {
                $table->decimal('base_price', 10, 2)->nullable()->after('price_adjustment');
            }
            if (!Schema::hasColumn('product_variations', 'discounted_price')) {
                $table->decimal('discounted_price', 10, 2)->nullable()->after('base_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            if (Schema::hasColumn('product_variations', 'discounted_price')) {
                $table->dropColumn('discounted_price');
            }
            if (Schema::hasColumn('product_variations', 'base_price')) {
                $table->dropColumn('base_price');
            }
        });
    }
};
