<?php
// Force-insert attendance for Apr 1-13 for store 1 (idempotent: deletes then inserts)
if (!defined('APP_BOOTSTRAPPED')) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    define('APP_BOOTSTRAPPED', true);
}

use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;
use App\Models\Hr\EmployeeDeduction;
use Carbon\Carbon;

$storeId = 1;
$start = Carbon::parse('2026-04-01');
$end = Carbon::parse('2026-04-13');

$employees = Employee::where('store_id', $storeId)->get();
if ($employees->isEmpty()) {
    echo "No employees for store_id={$storeId}\n";
    exit(0);
}

$employeeIds = $employees->pluck('id')->toArray();
// remove any existing for the range
Attendance::whereIn('employee_id', $employeeIds)
    ->whereDate('attendance_date','>=',$start->toDateString())
    ->whereDate('attendance_date','<=',$end->toDateString())
    ->delete();

echo "Cleared existing attendance for {$start->toDateString()} - {$end->toDateString()}\n";

foreach ($employees as $employee) {
    $day = $start->copy();
    while ($day->lte($end)) {
        // create typical 8-hour present with occasional overtime
        $status = 'present';
        $startTime = Carbon::create($day->year, $day->month, $day->day, 8, rand(0,30), 0);
        $regular = 480;
        $extra = (rand(1,100) <= 25) ? rand(0,120) : 0;
        $workedMinutes = $regular + $extra;
        $clockIn = $startTime->toDateTimeString();
        $clockOut = $startTime->copy()->addMinutes($workedMinutes)->toDateTimeString();
        $overtime = max(0, $workedMinutes - 480);
        $lateMinutes = max(0, Carbon::parse($clockIn)->diffInMinutes(Carbon::create($day->year,$day->month,$day->day,8,0,0)));
        Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => $day->toDateString(),
            'status' => $status,
            'total_worked_minutes' => $workedMinutes,
            'overtime_minutes' => $overtime,
            'late_minutes' => min($lateMinutes, 180),
            'night_differential_minutes' => 0,
            'is_restday_work' => 0,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $day->addDay();
    }
    echo "Inserted attendance for employee {$employee->id}\n";
}

echo "Done force-insert for {$start->toDateString()} - {$end->toDateString()}\n";
