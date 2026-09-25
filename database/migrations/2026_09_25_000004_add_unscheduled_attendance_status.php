<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const STATUSES = "'present', 'absent', 'late', 'half_day', 'on_leave', 'holiday', 'unscheduled'";

    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE attendances MODIFY status ENUM(" . self::STATUSES . ") NOT NULL DEFAULT 'absent'");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('attendances')->where('status', 'unscheduled')->update(['status' => 'present']);
            DB::statement("ALTER TABLE attendances MODIFY status ENUM('present', 'absent', 'late', 'half_day', 'on_leave', 'holiday') NOT NULL DEFAULT 'absent'");
        }
    }
};
