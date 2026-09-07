<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ecommerce_orders', 'sales_pos_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('commission_percentage', 5, 2)->default(0)->after('tax_amount');
                $table->decimal('commission_base_amount', 12, 2)->default(0)->after('commission_percentage');
                $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_base_amount');
                $table->decimal('store_net_amount', 12, 2)->default(0)->after('commission_amount');
            });
        }
    }

    public function down(): void
    {
        foreach (['ecommerce_orders', 'sales_pos_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn([
                    'commission_percentage',
                    'commission_base_amount',
                    'commission_amount',
                    'store_net_amount',
                ]);
            });
        }
    }
};
