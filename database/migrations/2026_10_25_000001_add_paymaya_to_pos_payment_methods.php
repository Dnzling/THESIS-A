<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales_pos_orders MODIFY payment_method ENUM('cash','card','gcash','bank_transfer','mixed','cod','paymongo','paymaya') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE sales_payments MODIFY payment_method ENUM('cash','card','gcash','cod','bank_transfer','paymongo','paymaya') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::table('sales_pos_orders')->where('payment_method', 'paymaya')->update(['payment_method' => 'card']);
        DB::table('sales_payments')->where('payment_method', 'paymaya')->update(['payment_method' => 'card']);

        DB::statement("ALTER TABLE sales_payments MODIFY payment_method ENUM('cash','card','gcash','cod','bank_transfer','paymongo') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE sales_pos_orders MODIFY payment_method ENUM('cash','card','gcash','bank_transfer','mixed','cod','paymongo') NOT NULL DEFAULT 'cash'");
    }
};
