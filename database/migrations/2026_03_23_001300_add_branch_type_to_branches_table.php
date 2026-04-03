<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'branch_type')) {
                $table->enum('branch_type', ['storefront', 'warehouse'])
                    ->default('storefront')
                    ->after('is_main_branch');
                $table->index(['store_id', 'branch_type'], 'idx_branches_store_type');
            }
        });

        // Mark branches used by warehouses as warehouse branches.
        DB::statement("
            UPDATE branches b
            INNER JOIN warehouses w ON w.branch_id = b.id
            SET b.branch_type = 'warehouse'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'branch_type')) {
                $table->dropIndex('idx_branches_store_type');
                $table->dropColumn('branch_type');
            }
        });
    }
};

