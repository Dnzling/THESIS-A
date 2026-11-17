<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subscription_plans', 'max_warehouses')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->unsignedInteger('max_warehouses')->nullable()->after('max_products');
            });
        }
        if (!Schema::hasColumn('subscription_plans', 'max_trucks')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->unsignedInteger('max_trucks')->nullable()->after('max_warehouses');
            });
        }
        if (!Schema::hasColumn('subscription_plans', 'max_suppliers')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->unsignedInteger('max_suppliers')->nullable()->after('max_trucks');
            });
        }
    }

    public function down(): void
    {
        foreach (['max_suppliers', 'max_trucks', 'max_warehouses'] as $column) {
            if (Schema::hasColumn('subscription_plans', $column)) {
                Schema::table('subscription_plans', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
