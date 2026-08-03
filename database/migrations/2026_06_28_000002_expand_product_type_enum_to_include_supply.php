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
        if (!Schema::hasTable('products')) {
            return;
        }

        DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('raw_material','finished_good','supply') DEFAULT 'finished_good'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('raw_material','finished_good') DEFAULT 'finished_good'");
    }
};
