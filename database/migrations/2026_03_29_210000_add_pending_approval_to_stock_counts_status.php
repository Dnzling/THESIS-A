<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE stock_counts
            MODIFY COLUMN status
            ENUM('pending_approval','scheduled','in_progress','completed','cancelled','approved')
            NOT NULL DEFAULT 'scheduled'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE stock_counts
            MODIFY COLUMN status
            ENUM('scheduled','in_progress','completed','cancelled','approved')
            NOT NULL DEFAULT 'scheduled'
        ");
    }
};
