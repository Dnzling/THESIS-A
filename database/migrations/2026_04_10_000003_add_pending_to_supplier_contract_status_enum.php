<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY status ENUM('draft','pending','active','completed','terminated','rejected')
            NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY status ENUM('draft','active','completed','terminated','rejected')
            NOT NULL DEFAULT 'draft'
        ");
    }
};
