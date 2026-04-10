<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_vouchers', 'voucher_name')) {
                $table->string('voucher_name', 120)->nullable()->after('store_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_vouchers', 'voucher_name')) {
                $table->dropColumn('voucher_name');
            }
        });
    }
};

