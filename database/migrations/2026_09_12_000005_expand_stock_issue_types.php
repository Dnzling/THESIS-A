<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_issues MODIFY issue_type ENUM('damaged','lost','expired','theft','production_use','office_consumption','store_usage','stock_correction','goods_received','stock_return','inventory_correction','opening_balance','transfer_in','other') NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_issues MODIFY issue_type ENUM('damaged','lost','expired','theft','other') NOT NULL DEFAULT 'other'");
    }
};
