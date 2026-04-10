<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_contracts', function ($table) {
            $table->dropForeign(['created_by']);
        });

        DB::statement('ALTER TABLE supplier_contracts MODIFY created_by BIGINT UNSIGNED NULL');

        Schema::table('supplier_contracts', function ($table) {
            $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_contracts', function ($table) {
            $table->dropForeign(['created_by']);
        });

        // Backfill NULLs before making the column non-null again.
        DB::statement('UPDATE supplier_contracts SET created_by = 1 WHERE created_by IS NULL');
        DB::statement('ALTER TABLE supplier_contracts MODIFY created_by BIGINT UNSIGNED NOT NULL');

        Schema::table('supplier_contracts', function ($table) {
            $table->foreign('created_by')->references('id')->on('employees');
        });
    }
};

