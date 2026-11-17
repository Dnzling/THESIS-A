<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ecommerce_orders', 'voucher_code')) {
            Schema::table('ecommerce_orders', function (Blueprint $table) {
                $table->string('voucher_code', 40)->nullable()->after('discount_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecommerce_orders', 'voucher_code')) {
            Schema::table('ecommerce_orders', function (Blueprint $table) {
                $table->dropColumn('voucher_code');
            });
        }
    }
};
