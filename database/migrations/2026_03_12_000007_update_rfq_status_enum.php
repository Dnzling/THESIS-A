<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE request_for_quotations 
             MODIFY status ENUM('draft','sent','receiving','partially_approved','awarded','completed','rejected','cancelled') 
             NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE request_for_quotations 
             MODIFY status ENUM('draft','sent','receiving','awarded','completed','cancelled') 
             NOT NULL DEFAULT 'draft'"
        );
    }
};
