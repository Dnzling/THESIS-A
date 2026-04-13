<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Attendance;

$count = Attendance::where('employee_id', 17)
    ->whereDate('attendance_date','>=','2026-04-01')
    ->whereDate('attendance_date','<=','2026-04-13')
    ->count();

echo "Employee 17 attendance count Apr1-13: {$count}\n";