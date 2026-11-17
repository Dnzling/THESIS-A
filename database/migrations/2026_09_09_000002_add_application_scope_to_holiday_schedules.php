<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holiday_schedules', function (Blueprint $table): void {
            $table->string('applies_to', 30)->default('all')->after('is_working_holiday');
            $table->string('city', 120)->nullable()->after('applies_to');
            $table->json('branch_ids')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('holiday_schedules', function (Blueprint $table): void {
            $table->dropColumn(['applies_to', 'city', 'branch_ids']);
        });
    }
};
