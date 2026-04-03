<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reorder_rules')) {
            return;
        }

        Schema::table('reorder_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('reorder_rules', 'basis_type')) {
                $table->enum('basis_type', ['reorder_point', 'demand_lead_time'])
                    ->default('reorder_point')
                    ->after('trigger_type');
            }

            if (!Schema::hasColumn('reorder_rules', 'avg_daily_demand')) {
                $table->decimal('avg_daily_demand', 12, 4)
                    ->nullable()
                    ->after('economic_order_quantity');
            }

            if (!Schema::hasColumn('reorder_rules', 'review_period_days')) {
                $table->integer('review_period_days')
                    ->nullable()
                    ->after('lead_time_days');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('reorder_rules')) {
            return;
        }

        Schema::table('reorder_rules', function (Blueprint $table) {
            if (Schema::hasColumn('reorder_rules', 'basis_type')) {
                $table->dropColumn('basis_type');
            }
            if (Schema::hasColumn('reorder_rules', 'avg_daily_demand')) {
                $table->dropColumn('avg_daily_demand');
            }
            if (Schema::hasColumn('reorder_rules', 'review_period_days')) {
                $table->dropColumn('review_period_days');
            }
        });
    }
};

