<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;

$storeId = 1;
$employeeIds = Employee::where('store_id', $storeId)->pluck('id')->toArray();
$rows = Attendance::whereIn('employee_id', $employeeIds)
    ->where('attendance_date', 'like', '2026-04-%')
    ->orderBy('attendance_date')
    ->get();

if ($rows->isEmpty()) {
    echo "No attendance rows with attendance_date like '2026-04-%' for store_id={$storeId}\n";
    exit;
}

foreach ($rows as $r) {
    echo "id={$r->id} emp={$r->employee_id} date={$r->attendance_date} created={$r->created_at}\n";
}
