<?php
// backend/database/migrations/2026_03_04_100001_add_procurement_config_to_stores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Procurement Mode
            $table->enum('procurement_mode', [
                'centralized',    // All procurement managed at store level
                'distributed',    // Each branch manages own procurement
                'hybrid'          // Mix: Small orders by branch, large orders centralized
            ])->default('hybrid')->after('settings');
            
            // Hybrid threshold (if hybrid mode)
            $table->decimal('procurement_threshold', 12, 2)->default(50000.00)
                ->comment('Orders above this need centralized procurement')
                ->after('procurement_mode');
            
            // Who manages centralized procurement
            $table->enum('central_procurement_managed_by', [
                'store_admin',
                'procurement_department',
                'warehouse_manager'
            ])->default('procurement_department')->nullable()->after('procurement_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'procurement_mode',
                'procurement_threshold',
                'central_procurement_managed_by'
            ]);
        });
    }
};