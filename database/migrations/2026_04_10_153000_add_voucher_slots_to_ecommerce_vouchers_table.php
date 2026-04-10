<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_vouchers', 'voucher_slots')) {
                $table->unsignedInteger('voucher_slots')->default(0)->after('voucher_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_vouchers', 'voucher_slots')) {
                $table->dropColumn('voucher_slots');
            }
        });
    }
};

