<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use Carbon\Carbon;

$storeId = 1;
$start = Carbon::parse('2026-04-01');
$end = Carbon::parse('2026-04-13');

$employeeIds = Employee::where('store_id', $storeId)->pluck('id')->toArray();
$count = Attendance::whereIn('employee_id', $employeeIds)
    ->whereDate('attendance_date', '>=', $start->toDateString())
    ->whereDate('attendance_date', '<=', $end->toDateString())
    ->delete();

echo "Deleted {$count} attendance records for store_id={$storeId} between {$start->toDateString()} and {$end->toDateString()}\n";