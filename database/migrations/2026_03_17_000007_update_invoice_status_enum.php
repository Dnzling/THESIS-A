<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY status ENUM(
            'draft',
            'pending_approval',
            'approved',
            'paid'
        ) DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY status ENUM(
            'draft',
            'approved',
            'paid'
        ) DEFAULT 'draft'");
    }
};
