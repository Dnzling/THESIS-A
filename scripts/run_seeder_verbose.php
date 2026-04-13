<?php
// Run with: php scripts/run_seeder_verbose.php
if (!defined('APP_BOOTSTRAPPED')) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    if ($app === false) {
        echo "bootstrap/app.php returned false\n";
        exit(1);
    }
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    define('APP_BOOTSTRAPPED', true);
}

use Illuminate\Support\Facades\DB;

echo "DB connection config:\n";
try {
    $conn = DB::connection()->getConfig();
    foreach ($conn as $k => $v) {
        echo "  {$k}: {$v}\n";
    }
} catch (Exception $e) {
    echo "  Failed to get DB config: " . $e->getMessage() . "\n";
}

echo "\nRunning seeder script with verbose exception capture:\n";
try {
    include __DIR__ . '/seed_demo_attendance_apr1_13.php';
    echo "Seeder script included successfully.\n";
} catch (Throwable $t) {
    echo "Seeder threw Throwable: " . get_class($t) . " - " . $t->getMessage() . "\n";
    echo "Trace:\n" . $t->getTraceAsString() . "\n";
}

// Also try the force seeder
echo "\nRunning force-insert seeder (verbose):\n";
try {
    include __DIR__ . '/seed_force_attendance_apr1_13.php';
    echo "Force seeder included successfully.\n";
} catch (Throwable $t) {
    echo "Force seeder threw Throwable: " . get_class($t) . " - " . $t->getMessage() . "\n";
    echo "Trace:\n" . $t->getTraceAsString() . "\n";
}
