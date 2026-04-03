<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_province')) {
                $table->string('delivery_province', 150)->nullable()->after('delivery_notes');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_city')) {
                $table->string('delivery_city', 150)->nullable()->after('delivery_province');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_barangay')) {
                $table->string('delivery_barangay', 150)->nullable()->after('delivery_city');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_address_line')) {
                $table->string('delivery_address_line', 255)->nullable()->after('delivery_barangay');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_latitude')) {
                $table->decimal('delivery_latitude', 10, 6)->nullable()->after('delivery_address_line');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_longitude')) {
                $table->decimal('delivery_longitude', 10, 6)->nullable()->after('delivery_latitude');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'delivery_email')) {
                $table->string('delivery_email', 150)->nullable()->after('delivery_longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_pos_orders', 'delivery_email')) {
                $table->dropColumn('delivery_email');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_longitude')) {
                $table->dropColumn('delivery_longitude');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_latitude')) {
                $table->dropColumn('delivery_latitude');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_address_line')) {
                $table->dropColumn('delivery_address_line');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_barangay')) {
                $table->dropColumn('delivery_barangay');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_city')) {
                $table->dropColumn('delivery_city');
            }
            if (Schema::hasColumn('sales_pos_orders', 'delivery_province')) {
                $table->dropColumn('delivery_province');
            }
        });
    }
};
