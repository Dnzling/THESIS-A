<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stores') || !Schema::hasColumn('stores', 'central_procurement_managed_by')) {
            return;
        }

        DB::statement("ALTER TABLE stores MODIFY central_procurement_managed_by ENUM('store_admin', 'owner', 'procurement_department', 'warehouse_manager') NULL DEFAULT 'procurement_department'");
        DB::table('stores')
            ->where('central_procurement_managed_by', 'store_admin')
            ->update(['central_procurement_managed_by' => 'owner']);
        DB::statement("ALTER TABLE stores MODIFY central_procurement_managed_by ENUM('owner', 'procurement_department', 'warehouse_manager') NULL DEFAULT 'procurement_department'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('stores') || !Schema::hasColumn('stores', 'central_procurement_managed_by')) {
            return;
        }

        DB::statement("ALTER TABLE stores MODIFY central_procurement_managed_by ENUM('store_admin', 'owner', 'procurement_department', 'warehouse_manager') NULL DEFAULT 'procurement_department'");
        DB::table('stores')
            ->where('central_procurement_managed_by', 'owner')
            ->update(['central_procurement_managed_by' => 'store_admin']);
        DB::statement("ALTER TABLE stores MODIFY central_procurement_managed_by ENUM('store_admin', 'procurement_department', 'warehouse_manager') NULL DEFAULT 'procurement_department'");
    }
};
