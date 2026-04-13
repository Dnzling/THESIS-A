<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$employee = 17;
$dateTimes = [
    '2026-04-01 00:00:00',
    '2026-04-01 08:00:00',
];
foreach ($dateTimes as $dt) {
    $rows = DB::select("select id, employee_id, attendance_date, created_at from attendances where employee_id = ? and attendance_date = ?", [$employee, $dt]);
    echo "Query for employee={$employee} attendance_date={$dt}: found " . count($rows) . " rows\n";
    foreach ($rows as $r) {
        echo json_encode($r) . "\n";
    }
}

// Also check index entries via grouping
$rows = DB::select("select employee_id, DATE(attendance_date) as d, count(*) as c from attendances where employee_id = ? group by employee_id, DATE(attendance_date) having c>1", [$employee]);
if (empty($rows)) {
    echo "No duplicate date-group rows for employee {$employee}\n";
} else {
    foreach ($rows as $r) echo json_encode($r) . "\n";
}
