<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Invoice\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancePayablesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = auth()->user()->store_id;

        $invoices = Invoice::with('supplier')
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending_approval', 'approved'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($inv) {
                return [
                    'type' => 'invoice',
                    'id' => $inv->id,
                    'reference' => $inv->invoice_number ?? $inv->id,
                    'supplier' => $inv->supplier?->supplier_name ?? 'Supplier',
                    'amount' => $inv->net_amount ?? $inv->invoice_amount ?? 0,
                    'invoice_created_at' => $inv->created_at ?? null,
                    'due_date' => $inv->due_date ?? null,
                    'status' => $inv->status ?? 'pending',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $invoices->values(),
        ]);
    }
}
