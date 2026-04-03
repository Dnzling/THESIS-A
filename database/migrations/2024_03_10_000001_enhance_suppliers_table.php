<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns to suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'category')) {
                $table->enum('category', ['raw_materials', 'furniture', 'accessories', 'services'])
                    ->nullable()
                    ->after('status');
            }
            if (!Schema::hasColumn('suppliers', 'average_delivery_days')) {
                $table->integer('average_delivery_days')->default(7)->after('category');
            }
            if (!Schema::hasColumn('suppliers', 'recent_delay_percentage')) {
                $table->integer('recent_delay_percentage')->default(0)->after('average_delivery_days');
            }
            if (!Schema::hasColumn('suppliers', 'quality_score')) {
                $table->decimal('quality_score', 3, 2)->default(5.00)->after('recent_delay_percentage');
            }
            if (!Schema::hasColumn('suppliers', 'total_orders')) {
                $table->integer('total_orders')->default(0)->after('quality_score');
            }
            if (!Schema::hasColumn('suppliers', 'on_time_deliveries')) {
                $table->integer('on_time_deliveries')->default(0)->after('total_orders');
            }
            if (!Schema::hasColumn('suppliers', 'late_deliveries')) {
                $table->integer('late_deliveries')->default(0)->after('on_time_deliveries');
            }
            if (!Schema::hasColumn('suppliers', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('supplier_name');
            }
            if (!Schema::hasColumn('suppliers', 'bank_details')) {
                $table->text('bank_details')->nullable()->after('tax_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columns = ['category', 'average_delivery_days', 'recent_delay_percentage', 
                       'quality_score', 'total_orders', 'on_time_deliveries', 'late_deliveries',
                       'tax_id', 'bank_details'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
