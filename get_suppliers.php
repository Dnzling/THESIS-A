<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/');
$kernel->handle($request);

// Authenticate
auth()->onceUsingId(1);

// Get suppliers
$suppliers = \App\Models\Procurement\Supplier\Supplier::with(['contracts', 'products', 'purchaseOrders'])->paginate(15, ['*'], 'page', 1);
echo "SUPPLIERS LIST:\n";
echo json_encode($suppliers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Get single supplier
$supplier = \App\Models\Procurement\Supplier\Supplier::with(['contracts', 'products', 'purchaseOrders'])->first();
if ($supplier) {
    echo "SINGLE SUPPLIER:\n";
    echo json_encode($supplier, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
echo "\n\n";

// Get contracts
$contracts = \App\Models\Procurement\Supplier\SupplierContract::with(['supplier', 'createdBy'])->paginate(15, ['*'], 'page', 1);
echo "CONTRACTS LIST:\n";
echo json_encode($contracts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Get single contract
$contract = \App\Models\Procurement\Supplier\SupplierContract::with(['supplier', 'createdBy'])->first();
if ($contract) {
    echo "SINGLE CONTRACT:\n";
    echo json_encode($contract, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
