<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stores MODIFY status ENUM('active', 'inactive', 'suspended', 'pending', 'unverified', 'deactivated') NOT NULL DEFAULT 'unverified'");
    }

    public function down(): void
    {
        DB::statement("UPDATE stores SET status = 'inactive' WHERE status = 'deactivated'");
        DB::statement("UPDATE stores SET status = 'pending' WHERE status = 'unverified'");
        DB::statement("ALTER TABLE stores MODIFY status ENUM('active', 'inactive', 'suspended', 'pending') NOT NULL DEFAULT 'pending'");
    }
};
