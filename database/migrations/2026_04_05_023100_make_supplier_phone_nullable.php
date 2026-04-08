<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Make `phone` nullable and set default to empty string to avoid insert errors.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Use change() to modify existing column. Ensure your DB supports altering defaults.
            $table->string('phone', 50)->nullable()->default('')->change();
        });
    }

    /**
     * Reverse the migrations.
     * Revert `phone` to NOT NULL and remove default.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Revert back to not nullable; set a sensible default to avoid issues during rollback
            $table->string('phone', 50)->default('')->nullable(false)->change();
        });
    }
};
