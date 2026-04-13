<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$storeId = 1;
$start = '2026-04-01';
$end = '2026-04-13';

// Delete attendance for employees of store 1 in date range using DATE()
$deleted = DB::delete("DELETE a FROM attendances a JOIN employees e ON e.id = a.employee_id WHERE e.store_id = ? AND DATE(a.attendance_date) BETWEEN ? AND ?", [ $storeId, $start, $end ]);

echo "Deleted {$deleted} attendance rows for store {$storeId} between {$start} and {$end}\n";
