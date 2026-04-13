<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierPaymentController extends Controller
{
    public function getPaymentHistory($id, Request $request)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplierEmail = strtolower(trim((string) ($supplier->email ?? '')));
            $relatedSupplierIds = Supplier::query()
                ->when($supplierEmail !== '', function ($q) use ($supplierEmail) {
                    $q->whereRaw('LOWER(email) = ?', [$supplierEmail]);
                }, function ($q) use ($id) {
                    $q->where('id', $id);
                })
                ->pluck('id')
                ->map(fn ($sid) => (int) $sid)
                ->push((int) $id)
                ->unique()
                ->values()
                ->all();

            $this->reconcileSucceededPaymongoInvoices($relatedSupplierIds);

            $statusFilter = strtolower((string) $request->get('status', 'all'));
            $perPage = max(1, min(100, (int) $request->get('per_page', 15)));
            $page = max(1, (int) $request->get('page', 1));

            $supplierPaymentsHasStoreId = Schema::hasColumn('supplier_payments', 'store_id');
            $invoicesHasStoreId = Schema::hasColumn('invoices', 'store_id');

            $supplierPaymentInvoiceExpr = Schema::hasColumn('supplier_payments', 'invoice_number')
                ? "COALESCE(supplier_payments.invoice_number, '-')"
                : "'-'";
            $supplierPayerExpr = $supplierPaymentsHasStoreId
                ? "COALESCE(stores.name, 'Finance')"
                : "'Finance'";

            $supplierPaymentsQuery = DB::table('supplier_payments');
            if ($supplierPaymentsHasStoreId) {
                $supplierPaymentsQuery->leftJoin('stores', 'stores.id', '=', 'supplier_payments.store_id');
            }

            $supplierPayments = $supplierPaymentsQuery
                ->whereIn('supplier_id', $relatedSupplierIds)
                ->when($statusFilter !== 'all', function ($q) use ($statusFilter) {
                    $q->whereRaw('LOWER(supplier_payments.status) = ?', [$statusFilter]);
                })
                ->selectRaw("
                    supplier_payments.id,
                    supplier_payments.supplier_id,
                    {$supplierPaymentInvoiceExpr} as invoice_number,
                    supplier_payments.payment_method,
                    supplier_payments.payment_amount as amount,
                    supplier_payments.status,
                    supplier_payments.payment_date,
                    supplier_payments.created_at,
                    supplier_payments.updated_at,
                    'supplier_payment' as source,
                    {$supplierPayerExpr} as payer_name
                ")
                ->get()
                ->map(function ($row) {
                    $row->effective_date = $row->created_at ?: $row->updated_at ?: $row->payment_date;
                    return $row;
                });

            $invoiceStatuses = ['paid', 'completed'];
            if ($statusFilter === 'pending' || $statusFilter === 'pending_approval') {
                $invoiceStatuses = ['pending'];
            } elseif ($statusFilter === 'cancelled' || $statusFilter === 'failed' || $statusFilter === 'rejected') {
                $invoiceStatuses = [$statusFilter];
            }

            $invoicePayerExpr = $invoicesHasStoreId
                ? "COALESCE(stores.name, 'Finance')"
                : "'Finance'";

            $financeInvoicePaymentsQuery = DB::table('invoices');
            if ($invoicesHasStoreId) {
                $financeInvoicePaymentsQuery->leftJoin('stores', 'stores.id', '=', 'invoices.store_id');
            }

            $financeInvoicePayments = $financeInvoicePaymentsQuery
                ->whereIn('supplier_id', $relatedSupplierIds)
                ->whereNotNull('payment_date')
                ->whereIn(DB::raw('LOWER(COALESCE(invoices.payment_status, invoices.status))'), $invoiceStatuses)
                ->selectRaw("
                    invoices.id,
                    invoices.supplier_id,
                    COALESCE(invoices.invoice_number, '-') as invoice_number,
                    invoices.payment_method,
                    COALESCE(invoices.payment_amount, invoices.net_amount, invoices.invoice_amount, 0) as amount,
                    COALESCE(invoices.payment_status, invoices.status, 'paid') as status,
                    invoices.payment_date,
                    invoices.created_at,
                    invoices.updated_at,
                    'invoice_payment' as source,
                    {$invoicePayerExpr} as payer_name
                ")
                ->get()
                ->map(function ($row) {
                    $row->effective_date = $row->updated_at ?: $row->created_at ?: $row->payment_date;
                    return $row;
                });

            $merged = $supplierPayments
                ->concat($financeInvoicePayments)
                ->sortByDesc(function ($row) {
                    return strtotime((string) ($row->effective_date ?: $row->payment_date ?: $row->created_at ?: '1970-01-01 00:00:00'));
                })
                ->values();

            $total = $merged->count();
            $items = $merged->forPage($page, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return response()->json($paginator);
        } catch (\Exception $e) {
            Log::error('Supplier payment history fetch failed', [
                'supplier_id' => $id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment history',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function reconcileSucceededPaymongoInvoices(array $supplierIds): void
    {
        if (empty($supplierIds)) {
            return;
        }

        $invoiceIds = DB::table('invoices')
            ->whereIn('supplier_id', $supplierIds)
            ->whereRaw("LOWER(COALESCE(payment_status, status, 'pending')) NOT IN ('paid','completed')")
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($invoiceIds)) {
            return;
        }

        $paidIntents = DB::table('paymongo_intents')
            ->where('payable_type', 'invoice')
            ->whereIn('payable_id', $invoiceIds)
            ->whereIn(DB::raw('LOWER(status)'), ['succeeded', 'paid'])
            ->orderByDesc('id')
            ->get(['payable_id', 'amount'])
            ->groupBy('payable_id');

        if ($paidIntents->isEmpty()) {
            return;
        }

        foreach ($paidIntents as $invoiceId => $intents) {
            $invoice = DB::table('invoices')->where('id', (int) $invoiceId)->first([
                'id', 'supplier_id', 'payment_status', 'status', 'payment_amount', 'invoice_amount', 'net_amount'
            ]);

            if (!$invoice) {
                continue;
            }

            $alreadyPaid = in_array(strtolower((string) ($invoice->payment_status ?: $invoice->status)), ['paid', 'completed'], true);
            if ($alreadyPaid) {
                continue;
            }

            $latestIntent = $intents->first();
            $computedAmount = (float) ($latestIntent->amount ?? 0) / 100;
            $paymentAmount = $computedAmount > 0
                ? $computedAmount
                : (float) ($invoice->payment_amount ?: $invoice->net_amount ?: $invoice->invoice_amount ?: 0);

            DB::transaction(function () use ($invoice, $paymentAmount) {
                DB::table('invoices')
                    ->where('id', (int) $invoice->id)
                    ->update([
                        'payment_status' => 'paid',
                        'payment_method' => 'paymongo_gcash',
                        'payment_amount' => $paymentAmount,
                        'payment_date' => now()->toDateString(),
                        'status' => 'paid',
                        'updated_at' => now(),
                    ]);

                if ((int) ($invoice->supplier_id ?? 0) > 0 && $paymentAmount > 0) {
                    DB::table('suppliers')
                        ->where('id', (int) $invoice->supplier_id)
                        ->update([
                            'current_balance' => DB::raw('GREATEST(COALESCE(current_balance, 0), 0) + ' . $paymentAmount),
                            'updated_at' => now(),
                        ]);
                }
            });
        }
    }

    public function recordPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,check,bank_transfer,credit_card,online_payment',
            'payment_date' => 'required|date',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,partial,paid',
            'invoice_number' => 'nullable|string',
            'po_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            $supplier = Supplier::findOrFail($id);

            // Calculate days overdue
            $dueDate = \Carbon\Carbon::parse($validated['due_date']);
            $paymentDate = \Carbon\Carbon::parse($validated['payment_date']);
            $daysOverdue = $paymentDate->greaterThan($dueDate) 
                ? $paymentDate->diffInDays($dueDate)
                : 0;

            $validated['supplier_id'] = $id;
            $validated['days_overdue'] = $daysOverdue;

            $payment = DB::table('supplier_payments')->insert($validated);

            // Update supplier recent delay percentage
            $this->updateSupplierPaymentMetrics($id);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAgingReport($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $today = now();
            $payments = DB::table('supplier_payments')
                ->where('supplier_id', $id)
                ->where('status', '!=', 'paid')
                ->get();

            $aging = [
                'current' => $payments->filter(function ($p) use ($today) {
                    $due = \Carbon\Carbon::parse($p->due_date);
                    return $due->isToday() || $due->isFuture();
                })->count(),
                'days_30' => $payments->filter(function ($p) use ($today) {
                    $due = \Carbon\Carbon::parse($p->due_date);
                    $diff = $today->diffInDays($due);
                    return $diff > 0 && $diff <= 30;
                })->count(),
                'days_60' => $payments->filter(function ($p) use ($today) {
                    $due = \Carbon\Carbon::parse($p->due_date);
                    $diff = $today->diffInDays($due);
                    return $diff > 30 && $diff <= 60;
                })->count(),
                'days_90' => $payments->filter(function ($p) use ($today) {
                    $due = \Carbon\Carbon::parse($p->due_date);
                    $diff = $today->diffInDays($due);
                    return $diff > 60 && $diff <= 90;
                })->count(),
                'days_90_plus' => $payments->filter(function ($p) use ($today) {
                    $due = \Carbon\Carbon::parse($p->due_date);
                    $diff = $today->diffInDays($due);
                    return $diff > 90;
                })->count()
            ];

            $totalAmount = $payments->sum('payment_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'aging' => $aging,
                    'total_overdue_amount' => $totalAmount,
                    'overdue_invoices' => DB::table('supplier_payments')
                        ->where('supplier_id', $id)
                        ->where('status', '!=', 'paid')
                        ->where('due_date', '<', now())
                        ->get()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate aging report'
            ], 500);
        }
    }

    public function getPaymentStatus($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $totalDue = DB::table('supplier_payments')
                ->where('supplier_id', $id)
                ->where('status', '!=', 'paid')
                ->sum('payment_amount');

            $totalPaid = DB::table('supplier_payments')
                ->where('supplier_id', $id)
                ->where('status', 'paid')
                ->sum('payment_amount');

            $upcomingPayments = DB::table('supplier_payments')
                ->where('supplier_id', $id)
                ->where('status', 'pending')
                ->where('due_date', '>', now())
                ->get();

            $overduePayments = DB::table('supplier_payments')
                ->where('supplier_id', $id)
                ->where('status', '!=', 'paid')
                ->where('due_date', '<=', now())
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_due' => $totalDue,
                    'total_paid' => $totalPaid,
                    'upcoming_count' => $upcomingPayments->count(),
                    'overdue_count' => $overduePayments->count(),
                    'overdue_amount' => $overduePayments->sum('payment_amount'),
                    'payment_terms' => $supplier->payment_terms,
                    'status' => $overduePayments->isEmpty() ? 'current' : 'overdue'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment status'
            ], 500);
        }
    }

    private function updateSupplierPaymentMetrics($supplierId)
    {
        $overduePayments = DB::table('supplier_payments')
            ->where('supplier_id', $supplierId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', now())
            ->get();

        $totalPayments = DB::table('supplier_payments')
            ->where('supplier_id', $supplierId)
            ->get();

        if ($totalPayments->count() > 0) {
            $delayPercentage = round(($overduePayments->count() / $totalPayments->count()) * 100, 2);
            Supplier::where('id', $supplierId)->update([
                'recent_delay_percentage' => $delayPercentage
            ]);
        }
    }
}
