<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'product_type')) {
            return;
        }

        DB::statement("ALTER TABLE products MODIFY COLUMN product_type VARCHAR(100) NOT NULL DEFAULT 'finished_good'");
    }

    public function down(): void
    {
        // Custom product types cannot safely be converted back to the old ENUM.
        // Keep the column as a string during rollback to avoid data loss.
    }
};
