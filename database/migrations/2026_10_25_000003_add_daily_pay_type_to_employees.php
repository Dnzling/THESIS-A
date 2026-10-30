<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE employees MODIFY pay_type ENUM('monthly', 'hourly', 'daily', 'hybrid') NOT NULL DEFAULT 'monthly'");
        }
    }

    public function down(): void
    {
        if (DB::table('employees')->where('pay_type', 'daily')->exists()) {
            throw new RuntimeException('Cannot remove daily pay type while employees are using it. Update those employee records first.');
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE employees MODIFY pay_type ENUM('monthly', 'hourly', 'hybrid') NOT NULL DEFAULT 'monthly'");
        }
    }
};
