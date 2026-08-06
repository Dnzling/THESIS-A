<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = App\Models\Core\Role::where('name', 'super_admin')->first();
if (!$role) {
    throw new RuntimeException('Super admin role not found.');
}

$user = App\Models\Core\User::where('email', 'superadmin@example.com')->first();
if (!$user) {
    $user = new App\Models\Core\User();
    $user->fname = 'Super';
    $user->lname = 'Admin';
    $user->email = 'superadmin@example.com';
    $user->email_verified_at = now();
    $user->password = Illuminate\Support\Facades\Hash::make('password123');
    $user->role_id = $role->id;
    $user->store_id = null;
    $user->branch_id = null;
    $user->is_active = 1;
    $user->save();
}

echo 'EMAIL=' . $user->email . PHP_EOL;
echo 'PASSWORD=password123' . PHP_EOL;
echo 'ROLE_ID=' . $user->role_id . PHP_EOL;
echo 'HASH_CHECK=' . (Illuminate\Support\Facades\Hash::check('password123', $user->password) ? 'true' : 'false') . PHP_EOL;
echo 'AUTH_ATTEMPT=' . (Illuminate\Support\Facades\Auth::attempt(['email' => $user->email, 'password' => 'password123']) ? 'true' : 'false') . PHP_EOL;
