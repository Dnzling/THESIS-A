<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ecommerce_cart_items', 'customization_request')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->text('customization_request')->nullable()->after('tax_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecommerce_cart_items', 'customization_request')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->dropColumn('customization_request');
            });
        }
    }
};
