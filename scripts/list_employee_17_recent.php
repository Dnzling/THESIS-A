<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Attendance;

$rows = Attendance::where('employee_id',17)->orderBy('id','desc')->limit(10)->get();
foreach($rows as $r){
    echo $r->id.' '.$r->attendance_date.' '.$r->clock_in."\n";
}
