<?php
// Run with: php scripts/seed_demo_attendance_apr1_13.php
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
    echo "No employees found for store_id={$storeId}\n";
    exit(0);
}

foreach ($employees as $employee) {
    echo "Employee {$employee->id}: {$employee->fname} {$employee->lname}\n";
    $deductions = EmployeeDeduction::where('employee_id', $employee->id)->active()->with('deductionType')->get();
    if ($deductions->isEmpty()) {
        echo "  No active deductions\n";
    } else {
        foreach ($deductions as $d) {
            echo "  Deduction: {$d->deductionType->name} amount: {$d->amount}\n";
        }
    }

    $day = $start->copy();
    while ($day->lte($end)) {
        $rand = rand(1,100);
        if ($rand <= 5) {
            $status = 'absent';
            $clockIn = null;
            $clockOut = null;
            $workedMinutes = 0;
            $overtimeMinutes = 0;
        } elseif ($rand <= 20) {
            $status = 'half_day';
            $startTime = Carbon::create($day->year, $day->month, $day->day, 8, rand(0,30), 0);
            $workedMinutes = rand(240,300);
            $clockIn = $startTime->toDateTimeString();
            $clockOut = $startTime->copy()->addMinutes($workedMinutes)->toDateTimeString();
            $overtimeMinutes = max(0, $workedMinutes - 480);
        } else {
            $status = 'present';
            $startTime = Carbon::create($day->year, $day->month, $day->day, 8, rand(0,30), 0);
            // regular 8 hours plus occasional overtime up to 120 minutes
            $regular = 480;
            $extra = (rand(1,100) <= 25) ? rand(0, 120) : 0;
            $workedMinutes = $regular + $extra;
            $clockIn = $startTime->toDateTimeString();
            $clockOut = $startTime->copy()->addMinutes($workedMinutes)->toDateTimeString();
            $overtimeMinutes = max(0, $workedMinutes - 480);
        }

        $lateMinutes = 0;
        if ($clockIn) {
            $ci = Carbon::parse($clockIn);
            $scheduled = Carbon::create($day->year, $day->month, $day->day, 8, 0, 0);
            $lateMinutes = max(0, $ci->diffInMinutes($scheduled));
            if ($lateMinutes > 180) $lateMinutes = 180;
        }

        try {
            Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'attendance_date' => $day->toDateString()],
                [
                    'status' => $status,
                    'total_worked_minutes' => $workedMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                    'late_minutes' => $lateMinutes,
                    'night_differential_minutes' => 0,
                    'is_restday_work' => 0,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // On duplicate key race, fall back to update
            Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $day->toDateString())
                ->update([
                    'status' => $status,
                    'total_worked_minutes' => $workedMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                    'late_minutes' => $lateMinutes,
                    'night_differential_minutes' => 0,
                    'is_restday_work' => 0,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'updated_at' => now(),
                ]);
        }

        $day->addDay();
    }

    echo "  Attendance inserted for {$start->toDateString()} to {$end->toDateString()}\n";
}

echo "Done.\n";
