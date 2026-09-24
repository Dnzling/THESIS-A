<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_periods', function (Blueprint $table) {
            $table->dropUnique('pay_periods_start_date_end_date_unique');
            $table->unique(['store_id', 'start_date', 'end_date'], 'pay_periods_store_dates_unique');
        });
    }

    public function down(): void
    {
        $sharedDates = DB::table('pay_periods')
            ->select('start_date', 'end_date')
            ->groupBy('start_date', 'end_date')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($sharedDates) {
            throw new \RuntimeException('Cannot restore global pay-period date uniqueness while multiple stores share a date range.');
        }

        Schema::table('pay_periods', function (Blueprint $table) {
            $table->dropUnique('pay_periods_store_dates_unique');
            $table->unique(['start_date', 'end_date'], 'pay_periods_start_date_end_date_unique');
        });
    }
};
