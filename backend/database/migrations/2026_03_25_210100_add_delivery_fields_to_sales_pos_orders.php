<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_required')) {
                $table->boolean('delivery_required')->default(false)->after('notes');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('delivery_required');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable()->after('delivery_address');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'scheduled_delivery_at')) {
                $table->timestamp('scheduled_delivery_at')->nullable()->after('delivery_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_pos_orders', 'scheduled_delivery_at')) {
                $table->dropColumn('scheduled_delivery_at');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_notes')) {
                $table->dropColumn('delivery_notes');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_address')) {
                $table->dropColumn('delivery_address');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_required')) {
                $table->dropColumn('delivery_required');
            }
        });
    }
};
