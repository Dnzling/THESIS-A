<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $simplePlanIds = DB::table('subscription_plans')
            ->whereIn('name', ['Free', 'Simple'])
            ->pluck('id');

        DB::table('purchase_orders')
            ->where('status', 'sent_to_supplier')
            ->where('rfq_required', false)
            ->whereNull('purchase_requisition_id')
            ->whereNull('rfq_id')
            ->whereNull('supplier_quotation_id')
            ->whereNull('supplier_contract_id')
            ->whereIn('store_id', DB::table('stores')->whereIn('subscription_tier', $simplePlanIds)->select('id'))
            ->whereNotIn('id', DB::table('goods_receipts')->select('purchase_order_id'))
            ->update(['status' => 'pending_receipt', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Do not reverse order statuses: a PO may already have been received.
    }
};
