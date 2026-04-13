<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;

$storeId = 1;
$employeeIds = Employee::where('store_id', $storeId)->pluck('id')->toArray();
$rows = Attendance::whereIn('employee_id', $employeeIds)->orderBy('id', 'desc')->limit(20)->get();

if ($rows->isEmpty()) {
    echo "No attendance rows found for store_id={$storeId}\n";
    exit;
}

foreach ($rows as $r) {
    echo "id={$r->id} employee_id={$r->employee_id} attendance_date={$r->attendance_date} total_worked_minutes={$r->total_worked_minutes} clock_in={$r->clock_in} clock_out={$r->clock_out}\n";
}
