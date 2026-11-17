<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ecommerce_orders', 'sales_pos_orders'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'commission_percentage')) {
                    $table->decimal('commission_percentage', 5, 2)->default(0);
                }
                if (! Schema::hasColumn($tableName, 'commission_base_amount')) {
                    $table->decimal('commission_base_amount', 12, 2)->default(0);
                }
                if (! Schema::hasColumn($tableName, 'commission_amount')) {
                    $table->decimal('commission_amount', 12, 2)->default(0);
                }
                if (! Schema::hasColumn($tableName, 'store_net_amount')) {
                    $table->decimal('store_net_amount', 12, 2)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        // Non-destructive repair migration: retained to protect financial snapshots.
    }
};
