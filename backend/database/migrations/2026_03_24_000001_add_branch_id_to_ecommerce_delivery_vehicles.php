<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_delivery_vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_delivery_vehicles', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('store_id')->constrained('branches')->nullOnDelete();
                $table->index(['store_id', 'branch_id', 'status'], 'idx_ecom_vehicle_store_branch_status');
            }
        });

        // Best-effort backfill for existing records: assign first branch in the store.
        if (Schema::hasColumn('ecommerce_delivery_vehicles', 'branch_id')) {
            $stores = DB::table('ecommerce_delivery_vehicles')
                ->select('store_id')
                ->whereNull('branch_id')
                ->distinct()
                ->pluck('store_id');

            foreach ($stores as $storeId) {
                $branchId = DB::table('branches')->where('store_id', $storeId)->orderBy('id')->value('id');
                if ($branchId) {
                    DB::table('ecommerce_delivery_vehicles')
                        ->where('store_id', $storeId)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $branchId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('ecommerce_delivery_vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_delivery_vehicles', 'branch_id')) {
                $table->dropIndex('idx_ecom_vehicle_store_branch_status');
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
