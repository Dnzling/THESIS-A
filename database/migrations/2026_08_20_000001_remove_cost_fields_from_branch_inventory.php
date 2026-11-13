<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            foreach (['unit_cost', 'average_cost', 'total_value'] as $column) {
                if (Schema::hasColumn('branch_inventory', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_inventory', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('branch_inventory', 'average_cost')) {
                $table->decimal('average_cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('branch_inventory', 'total_value')) {
                $table->decimal('total_value', 12, 2)->nullable();
            }
        });
    }
};
