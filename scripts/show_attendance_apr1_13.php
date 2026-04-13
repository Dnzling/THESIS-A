<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;

$storeId = 1;
$start = '2026-04-01';
$end = '2026-04-13';
$employeeIds = Employee::where('store_id', $storeId)->pluck('id')->toArray();
$rows = Attendance::whereIn('employee_id', $employeeIds)
    ->whereDate('attendance_date','>=',$start)
    ->whereDate('attendance_date','<=',$end)
    ->orderBy('employee_id')
    ->orderBy('attendance_date')
    ->get();

if ($rows->isEmpty()) {
    echo "No attendance rows found for store_id={$storeId} between {$start} and {$end}\n";
    exit;
}

foreach ($rows as $r) {
    echo "id={$r->id} employee_id={$r->employee_id} attendance_date={$r->attendance_date} total_worked_minutes={$r->total_worked_minutes} clock_in={$r->clock_in} clock_out={$r->clock_out}\n";
}
