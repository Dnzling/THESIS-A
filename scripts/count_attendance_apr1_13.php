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
$count = Attendance::whereDate('attendance_date','>=',$start)
    ->whereDate('attendance_date','<=',$end)
    ->whereIn('employee_id',$employeeIds)
    ->count();

echo $count . "\n";