<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_pos_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_pos_order_items', 'customization_request')) {
                $table->text('customization_request')->nullable();
            }
            if (!Schema::hasColumn('sales_pos_order_items', 'customization_fee')) {
                $table->decimal('customization_fee', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('sales_pos_order_items', 'customization_lead_time_days')) {
                $table->unsignedSmallInteger('customization_lead_time_days')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_filter([
            Schema::hasColumn('sales_pos_order_items', 'customization_request') ? 'customization_request' : null,
            Schema::hasColumn('sales_pos_order_items', 'customization_fee') ? 'customization_fee' : null,
            Schema::hasColumn('sales_pos_order_items', 'customization_lead_time_days') ? 'customization_lead_time_days' : null,
        ]);

        if ($columns) {
            Schema::table('sales_pos_order_items', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
