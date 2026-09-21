<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_refunds') && !Schema::hasTable('finance_refunds')) {
            Schema::rename('sales_refunds', 'finance_refunds');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('finance_refunds') && !Schema::hasTable('sales_refunds')) {
            Schema::rename('finance_refunds', 'sales_refunds');
        }
    }
};
