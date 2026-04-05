<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Procurement\RFQ\RFQSupplier;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Store;
use App\Models\Hr\Employee;
use App\Http\Controllers\Api\Procurement\RFQ\RequestForQuotationController;
use Illuminate\Support\Facades\DB;

class TestSendRfq extends Command
{
    protected $signature = 'test:send-rfq';
    protected $description = 'Create a test RFQ with two suppliers and run send(), then report supplier_quotations rows';

    public function handle()
    {
        $this->info('Starting test: create RFQ with 2 suppliers and send');

        $store = Store::first();
        $product = Product::first();
        $suppliers = Supplier::limit(2)->get();

        if (!$store || !$product || $suppliers->count() < 1) {
            $this->error('Missing seed data (store/product/suppliers).');
            return 1;
        }

        $employee = Employee::first();
        if (!$employee) {
            $this->error('No employee found for created_by.');
            return 1;
        }

        DB::beginTransaction();
        try {
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
                'quantity' => 1,
            ]);

            foreach ($suppliers as $s) {
                RFQSupplier::create([
                    'rfq_id' => $rfq->id,
                    'supplier_id' => $s->id,
                    'status' => 'pending',
                    'invited_at' => now(),
                ]);
            }

            DB::commit();

            $this->info('RFQ created: ' . $rfq->id . ' with ' . $suppliers->count() . ' suppliers.');

            $cols = DB::select("SHOW COLUMNS FROM supplier_quotations");
            $this->info('supplier_quotations columns: ' . json_encode($cols));

            $this->info('Calling send...');

            $controller = new RequestForQuotationController();
            $response = $controller->send($rfq->id);

            $data = $response->getData(true);
            $this->info('Send response: ' . json_encode($data));

            // Query supplier_quotations count for this rfq
            $count = DB::table('supplier_quotations')->where('rfq_id', $rfq->id)->count();
            $this->info('supplier_quotations rows created: ' . $count);

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Test failed: ' . $e->getMessage());
            return 1;
        }
    }
}
