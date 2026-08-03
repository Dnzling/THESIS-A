<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unit_of_measurement')) {
                $table->string('unit_of_measurement')->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('products', 'supplier_name')) {
                $table->string('supplier_name')->nullable()->after('unit_of_measurement');
            }

            if (!Schema::hasColumn('products', 'initial_stock')) {
                $table->decimal('initial_stock', 12, 2)->nullable()->default(0)->after('supplier_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'initial_stock')) {
                $table->dropColumn('initial_stock');
            }

            if (Schema::hasColumn('products', 'supplier_name')) {
                $table->dropColumn('supplier_name');
            }

            if (Schema::hasColumn('products', 'unit_of_measurement')) {
                $table->dropColumn('unit_of_measurement');
            }
        });
    }
};
