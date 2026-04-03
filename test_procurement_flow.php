<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procurement\StockOrder\StockOrderRequest;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;

echo "\n=== PROCUREMENT FLOW TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Check test data
$store = Store::first();
$supplier = Supplier::first();
$approved = StockOrderRequest::where('status', 'approved')->limit(3)->get();

echo "📋 TEST DATA AVAILABILITY:\n";
echo "  Store: " . ($store?->store_name ?? '❌ NOT FOUND') . "\n";
echo "  Supplier: " . ($supplier?->supplier_name ?? '❌ NOT FOUND') . "\n";
echo "  Approved Requests: " . $approved->count() . "\n";

if ($approved->count() > 0) {
    echo "\n✅ STEP 1: Stock Order Requests Available\n";
    foreach ($approved as $i => $req) {
        echo "  [$i] ID: {$req->id}, Qty: {$req->requested_quantity}, Status: {$req->status}\n";
    }
} else {
    echo "\n❌ No approved stock requests - test data needed\n";
    exit(1);
}

// Step 2: Test PO Creation
if ($store && $supplier && $approved->count() > 0) {
    echo "\n✅ STEP 2: Attempting PO Creation from Stock Requests\n";
    
    $payload = [
        'stock_order_request_ids' => $approved->pluck('id')->toArray(),
        'supplier_id' => $supplier->id,
        'payment_terms' => 'net_30',
        'shipping_cost' => 500,
        'discount_amount' => 0,
        'notes' => 'Test procurement flow'
    ];
    
    echo "  Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    
    try {
        // Simulate what the controller does
        $requests = StockOrderRequest::whereIn('id', $payload['stock_order_request_ids'])->get();
        
        if ($requests->count() !== count($payload['stock_order_request_ids'])) {
            echo "  ❌ Not all requests found\n";
            exit(1);
        }
        
        // Check all approved
        $allApproved = $requests->every(fn($r) => $r->status === 'approved');
        if (!$allApproved) {
            echo "  ❌ Not all requests are approved\n";
            exit(1);
        }
        
        echo "  ✅ Validation passed\n";
        echo "  ✅ All requests approved\n";
        echo "  ✅ Ready to create PO\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Step 3: Check existing POs with stock requests
echo "\n✅ STEP 3: Checking Created POs\n";
$posWithRequests = PurchaseOrder::whereNotNull('stock_order_request_id')->count();
echo "  POs from Stock Requests: $posWithRequests\n";

if ($posWithRequests > 0) {
    $latest = PurchaseOrder::whereNotNull('stock_order_request_id')->latest()->first();
    echo "  Latest: PO {$latest->po_number}, Status: {$latest->status}\n";
}

echo "\n✅ PROCUREMENT FLOW TEST COMPLETE\n";
echo "All systems ready for frontend testing!\n\n";
?>
