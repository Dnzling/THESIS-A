<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('inventory:reorder-suggestions:auto-run')->everyMinute();

Schedule::call(function () {
    \App\Models\Hr\Employee::query()
        ->whereNotNull('resignation_date')
        ->whereDate('last_working_day', '<', now('Asia/Manila')->toDateString())
        ->where('status', '!=', 'terminated')
        ->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($employee) {
                    $employee->update([
                        'status' => 'terminated',
                        'termination_date' => $employee->last_working_day,
                        'termination_reason' => $employee->resignation_reason,
                    ]);
                    $employee->user?->update(['is_active' => false]);
                });
            }
        });
})->dailyAt('00:05')->timezone('Asia/Manila');
