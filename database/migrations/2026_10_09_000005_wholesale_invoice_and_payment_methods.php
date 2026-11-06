<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->string('invoice_number', 60)->nullable()->unique()->after('sales_order_id');
        });

        DB::statement("ALTER TABLE sales_pos_orders MODIFY payment_method ENUM('cash','card','gcash','bank_transfer','mixed','cod','paymongo') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE sales_payments MODIFY payment_method ENUM('cash','card','gcash','cod','bank_transfer','paymongo') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        if (DB::table('sales_payments')->whereIn('payment_method', ['bank_transfer', 'paymongo'])->exists()
            || DB::table('sales_pos_orders')->where('payment_method', 'paymongo')->exists()) {
            throw new RuntimeException('Cannot roll back wholesale payment methods while those payments exist.');
        }

        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn('invoice_number');
        });

        DB::statement("ALTER TABLE sales_payments MODIFY payment_method ENUM('cash','card','gcash','cod') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE sales_pos_orders MODIFY payment_method ENUM('cash','card','gcash','bank_transfer','mixed','cod') NOT NULL DEFAULT 'cash'");
    }
};
