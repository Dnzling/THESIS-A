<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function getPaymentHistory($id, Request $request)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $query = DB::table('supplier_payments')
                ->where('supplier_id', $id);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $payments = $query
                ->orderBy('due_date', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json($payments);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
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
