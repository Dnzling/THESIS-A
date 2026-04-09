<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/');
$kernel->handle($request);

// Authenticate
auth()->onceUsingId(1);

// Get single contract with all details
$contract = \App\Models\Procurement\Supplier\SupplierContract::with(['supplier', 'createdBy'])->first();
if ($contract) {
    echo "SINGLE CONTRACT:\n";
    echo json_encode($contract->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo "No contract found\n";
}
