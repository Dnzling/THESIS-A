<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_configurations', 'enable_auto_reorder_suggestions')) {
                $table->boolean('enable_auto_reorder_suggestions')
                    ->default(true)
                    ->after('enable_auto_alerts');
            }

            if (!Schema::hasColumn('inventory_configurations', 'auto_reorder_suggestions_time')) {
                $table->string('auto_reorder_suggestions_time', 5)
                    ->default('08:00')
                    ->after('enable_auto_reorder_suggestions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_configurations', 'auto_reorder_suggestions_time')) {
                $table->dropColumn('auto_reorder_suggestions_time');
            }

            if (Schema::hasColumn('inventory_configurations', 'enable_auto_reorder_suggestions')) {
                $table->dropColumn('enable_auto_reorder_suggestions');
            }
        });
    }
};
