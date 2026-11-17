<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simple_employee_attendances', function (Blueprint $table) {
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->timestamp('scheduled_close_at')->nullable();
            $table->unsignedInteger('worked_minutes')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('simple_employee_attendances', function (Blueprint $table) {
            $table->dropColumn(['clock_in_at', 'clock_out_at', 'scheduled_close_at', 'worked_minutes', 'late_minutes', 'overtime_minutes']);
        });
    }
};
