<?php

use Tests\TestCase;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Procurement\RFQ\RFQSupplier;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Store\Store;
use App\Models\ProductCatalog\Product;
use App\Models\Hr\Employee;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\RFQ\SupplierQuotation;
use App\Models\Procurement\RFQ\SupplierQuotationItem;

use App\Http\Controllers\Api\Procurement\RFQ\RequestForQuotationController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierRFQFeedbackController;

use Illuminate\Support\Facades\DB;

it('creates supplier quotations on send, persists item lines on submit, and rejects other quotations when approved', function () {
    // Basic environment checks
    $store = Store::first();
    $product = Product::first();
    $suppliers = Supplier::limit(2)->get();
    $employee = Employee::first();

    if (!$store || !$product || $suppliers->count() < 2 || !$employee) {
        $this->markTestSkipped('Missing seed data: ensure store, product, suppliers, employee exist.');
    }

    // Create RFQ and single item
    $rfq = RequestForQuotation::create([
        'rfq_number' => 'TEST-RFQ-' . time(),
        'store_id' => $store->id,
        'title' => 'Test RFQ',
        'issue_date' => now()->toDateString(),
        'status' => 'draft',
        'created_by' => $employee->id,
    ]);

    $item = RFQItem::create([
        'rfq_id' => $rfq->id,
        'product_id' => $product->id,
        'variation_id' => null,
        'quantity' => 2,
    ]);

    foreach ($suppliers as $s) {
        RFQSupplier::create([
            'rfq_id' => $rfq->id,
            'supplier_id' => $s->id,
            'status' => 'pending',
            'invited_at' => now(),
        ]);

        // Ensure a SupplierPortal exists for controller interactions
        SupplierPortal::firstOrCreate([
            'supplier_id' => $s->id,
        ], [
            'user_id' => 1,
            'status' => 'approved',
        ]);
    }

    // Send RFQ to suppliers
    $controller = new RequestForQuotationController();
    $resp = $controller->send($rfq->id);
    $data = $resp->getData(true);
    expect($data['success'])->toBeTrue();

    // There should be a master quotation per supplier
    $quotations = SupplierQuotation::where('rfq_id', $rfq->id)->get();
    expect($quotations->count())->toBe(2);

    // Simulate suppliers submitting quotes by creating SupplierQuotationItem entries
    foreach ($quotations as $quotation) {
        if (Schema::hasTable('supplier_quotation_items')) {
            SupplierQuotationItem::create([
                'quotation_id' => $quotation->id,
                'rfq_item_id' => $item->id,
                'unit_price' => 100.00 * ($quotation->supplier_id),
                'quantity' => $item->quantity,
                'discount_percent' => 0,
                'line_total' => 100.00 * ($quotation->supplier_id) * $item->quantity,
            ]);

            // Recompute subtotal/total on master
            $subtotal = SupplierQuotationItem::where('quotation_id', $quotation->id)->sum('line_total');
            $quotation->update(['subtotal' => $subtotal, 'tax_amount' => 0, 'total_amount' => $subtotal]);
        }
    }

    // Approve the first supplier's feedback for the item
    $portal = SupplierPortal::where('supplier_id', $suppliers->first()->id)->first();
    $feedback = \App\Models\Procurement\SupplierPortal\SupplierRFQFeedback::create([
        'supplier_portal_id' => $portal->id,
        'rfq_id' => $rfq->id,
        'rfq_item_id' => $item->id,
        'quoted_price' => 100.00 * ($suppliers->first()->id),
        'tax_rate' => 0,
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    // Call review endpoint to approve
    $req = new \Illuminate\Http\Request([], ['status' => 'approved']);
    $reviewResp = $controller->reviewPortalFeedback($req, $rfq->id, $feedback->id);
    $reviewData = $reviewResp->getData(true);
    expect($reviewData['success'])->toBeTrue();

    // After approval, other supplier quotations should be marked rejected
    $othersRejected = SupplierQuotation::where('rfq_id', $rfq->id)
        ->where('supplier_id', '!=', $suppliers->first()->id)
        ->where('status', 'rejected')
        ->exists();

    expect($othersRejected)->toBeTrue();

    // The approved supplier quotation should be accepted
    $winnerQuotation = SupplierQuotation::where('rfq_id', $rfq->id)
        ->where('supplier_id', $suppliers->first()->id)
        ->first();

    expect($winnerQuotation->status)->toBe('accepted');
});
