<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds `user_id` column and backfills existing users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_id', 20)->nullable()->unique()->after('id');
        });

        // Backfill user_id for existing users using created_at year and id as sequence
        // Format: YYYY-00001 (year + zero-padded id to 5 digits)
        DB::statement("
            UPDATE users
            SET user_id = CONCAT(YEAR(COALESCE(created_at, NOW())), '-', LPAD(id, 5, '0'))
            WHERE user_id IS NULL
        ");

        // Ensure future inserts require non-null user_id via application logic; leave DB nullable for safety.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
