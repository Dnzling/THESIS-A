<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Invoice\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancePayablesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = auth()->user()->store_id;

        $purchaseOrders = PurchaseOrder::with(['supplier', 'branch'])
            ->where('store_id', $storeId)
            ->where('payment_status', 'pending')
            ->orderBy('payment_due_date')
            ->get()
            ->map(function ($po) {
                return [
                    'type' => 'purchase_order',
                    'reference' => $po->po_number,
                    'supplier' => $po->supplier?->supplier_name,
                    'amount' => $po->total_amount,
                    'due_date' => $po->payment_due_date,
                    'status' => $po->payment_status,
                ];
            });

        $invoices = Invoice::with('supplier')
            ->where('store_id', $storeId)
            ->orderBy('due_date')
            ->get()
            ->map(function ($inv) {
                return [
                    'type' => 'invoice',
                    'reference' => $inv->invoice_number ?? $inv->id,
                    'supplier' => $inv->supplier?->supplier_name ?? 'Supplier',
                    'amount' => $inv->net_amount ?? $inv->invoice_amount ?? 0,
                    'due_date' => $inv->due_date ?? null,
                    'status' => $inv->status ?? 'pending',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $purchaseOrders->merge($invoices)->values(),
        ]);
    }
}
