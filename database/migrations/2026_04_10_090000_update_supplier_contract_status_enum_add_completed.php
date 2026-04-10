<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY status ENUM('draft','active','completed','expired','terminated') NOT NULL DEFAULT 'draft'
        ");

        DB::statement("
            UPDATE supplier_contracts
            SET status = 'completed'
            WHERE status = 'expired'
        ");

        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY status ENUM('draft','active','completed','terminated') NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY status ENUM('draft','active','completed','terminated','expired') NOT NULL DEFAULT 'draft'
        ");

        DB::statement("
            UPDATE supplier_contracts
            SET status = 'expired'
            WHERE status = 'completed'
        ");

        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY status ENUM('draft','active','expired','terminated') NOT NULL DEFAULT 'draft'
        ");
    }
};

