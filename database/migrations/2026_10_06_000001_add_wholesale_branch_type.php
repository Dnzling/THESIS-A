<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE branches MODIFY branch_type ENUM('storefront','warehouse','wholesale') NOT NULL DEFAULT 'storefront'");
    }

    public function down(): void
    {
        // Do not discard existing wholesale branches during a rollback.
        if (DB::table('branches')->where('branch_type', 'wholesale')->exists()) {
            return;
        }

        DB::statement("ALTER TABLE branches MODIFY branch_type ENUM('storefront','warehouse') NOT NULL DEFAULT 'storefront'");
    }
};
