<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Invoice\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinancePayablesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = auth()->user()->store_id;

        $invoices = Invoice::with('supplier')
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending_approval', 'approved', 'paid'])
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
                    'payment_status' => $inv->payment_status ?? 'pending',
                    'payment_date' => $inv->payment_date ?? null,
                    'payment_amount' => $inv->payment_amount ?? null,
                ];
            });

        $today = Carbon::today();
        $currentStart = $today->copy()->subDays(27);
        $previousStart = $currentStart->copy()->subDays(28);
        $previousEnd = $currentStart->copy()->subSecond();
        $remainingBalance = static fn (array $invoice): float => max(
            0,
            (float) ($invoice['amount'] ?? 0) - (float) ($invoice['payment_amount'] ?? 0)
        );
        $sumOutstanding = static function (Carbon $start, Carbon $end) use ($invoices, $remainingBalance): float {
            return (float) $invoices
                ->filter(function (array $invoice) use ($start, $end): bool {
                    if (in_array(strtolower((string) ($invoice['payment_status'] ?? '')), ['paid', 'refunded', 'cancelled'], true)
                        || strtolower((string) ($invoice['status'] ?? '')) === 'paid'
                        || empty($invoice['invoice_created_at'])) {
                        return false;
                    }

                    return Carbon::parse($invoice['invoice_created_at'])->betweenIncluded($start, $end);
                })
                ->sum($remainingBalance);
        };
        $totalOutstanding = (float) $invoices
            ->filter(fn (array $invoice): bool => !in_array(strtolower((string) ($invoice['payment_status'] ?? '')), ['paid', 'refunded', 'cancelled'], true)
                && strtolower((string) ($invoice['status'] ?? '')) !== 'paid')
            ->sum($remainingBalance);

        return response()->json([
            'success' => true,
            'data' => $invoices->values(),
            'summary' => [
                'current' => [
                    'outstanding_amount' => $totalOutstanding,
                    'period_outstanding_amount' => $sumOutstanding($currentStart, $today->copy()->endOfDay()),
                ],
                'previous' => [
                    'period_outstanding_amount' => $sumOutstanding($previousStart, $previousEnd),
                ],
            ],
        ]);
    }
}
