<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sales_pos_orders', 'shipping_fee')) {
            return;
        }

        Schema::table('sales_pos_orders', function (Blueprint $table) {
            $table->decimal('shipping_fee', 12, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sales_pos_orders', 'shipping_fee')) {
            return;
        }

        Schema::table('sales_pos_orders', function (Blueprint $table) {
            $table->dropColumn('shipping_fee');
        });
    }
};
