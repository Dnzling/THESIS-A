<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_inventory', 'warehouse_location_id')) {
                $table->foreignId('warehouse_location_id')
                    ->nullable()
                    ->after('warehouse_section')
                    ->constrained('warehouse_locations')
                    ->nullOnDelete();
                $table->index('warehouse_location_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            if (Schema::hasColumn('branch_inventory', 'warehouse_location_id')) {
                $table->dropForeign(['warehouse_location_id']);
                $table->dropIndex(['warehouse_location_id']);
                $table->dropColumn('warehouse_location_id');
            }
        });
    }
};
