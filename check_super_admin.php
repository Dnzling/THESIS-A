<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\Core\User::where('email', 'superadmin@example.com')->first();
if ($user) {
    echo 'FOUND' . PHP_EOL;
    echo $user->email . PHP_EOL;
    echo $user->role_id . PHP_EOL;
} else {
    echo 'NOT_FOUND' . PHP_EOL;
}
