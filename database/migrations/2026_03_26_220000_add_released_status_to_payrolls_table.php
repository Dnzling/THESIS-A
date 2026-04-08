<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('draft','calculated','processing','approved','released','paid','cancelled') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE payrolls SET status = 'approved' WHERE status = 'released'");
        DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('draft','calculated','processing','approved','paid','cancelled') NOT NULL DEFAULT 'draft'");
    }
};
