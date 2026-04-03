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
        if (Schema::hasTable('reorder_rules') && !Schema::hasColumn('reorder_rules', 'deleted_at')) {
            Schema::table('reorder_rules', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('reorder_suggestions') && !Schema::hasColumn('reorder_suggestions', 'deleted_at')) {
            Schema::table('reorder_suggestions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reorder_suggestions') && Schema::hasColumn('reorder_suggestions', 'deleted_at')) {
            Schema::table('reorder_suggestions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('reorder_rules') && Schema::hasColumn('reorder_rules', 'deleted_at')) {
            Schema::table('reorder_rules', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

