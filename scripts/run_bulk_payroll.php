<?php
// Run with: php scripts/run_bulk_payroll.php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Models\Hr\PayPeriod;
use App\Http\Controllers\Api\Hr\PayrollController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Find pay period that matches Apr 1 - Apr 15
// Find or create pay period for Apr 1 - Apr 15
$payPeriod = PayPeriod::where('start_date','2026-04-01')->where('cutoff_date','2026-04-15')->first();
if (!$payPeriod) {
    echo "Creating pay period for 2026-04-01 to 2026-04-15\n";
    $payPeriod = PayPeriod::create([
        'store_id' => 1,
        'name' => 'Pay Period 2026-04-01 to 2026-04-15',
        'start_date' => '2026-04-01',
        'cutoff_date' => '2026-04-15',
        'end_date' => '2026-04-15',
        'status' => 'draft'
    ]);
}

echo "Found pay period id={$payPeriod->id} name={$payPeriod->name}\n";

// Ensure an authenticated user exists for store context
$authUser = User::where('store_id', 1)->first();
if ($authUser) {
    Auth::setUser($authUser);
    echo "Authenticated as user id={$authUser->id}\n";
} else {
    echo "No user found for store 1; generation may fail due to missing auth context\n";
}

// Build a fake request
$req = Request::create('/', 'POST', [
    'pay_period_id' => $payPeriod->id,
]);

$controller = new PayrollController();
$response = $controller->generateBulk($req);

// If response is a JsonResponse, dump
if (method_exists($response, 'getContent')) {
    echo $response->getContent() . PHP_EOL;
} else {
    var_export($response);
}
