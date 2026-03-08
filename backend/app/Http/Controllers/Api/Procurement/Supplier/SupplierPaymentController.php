<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierPaymentController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\SupplierPayment;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    /**
     * List all supplier payments
     * GET /api/procurement/payments
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupplierPayment::with(['purchaseOrder', 'supplier', 'approvedBy', 'processedBy'])
            ->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('purchase_order_id')) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $payments = $query->orderBy('payment_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Show single payment
     * GET /api/procurement/payments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $payment = SupplierPayment::with([
            'purchaseOrder',
            'supplier',
            'approvedBy',
            'processedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Create payment
     * POST /api/procurement/payments
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,check,bank_transfer,credit_card,debit_card,online_payment',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $po = PurchaseOrder::with('supplier')->findOrFail($validated['purchase_order_id']);

        // Generate payment number
        $lastPayment = SupplierPayment::latest()->first();
        $paymentNumber = 'PAY-' . date('Y') . '-' . str_pad(($lastPayment?->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

        $payment = SupplierPayment::create([
            'payment_number' => $paymentNumber,
            'store_id' => auth()->user()->store_id,
            'purchase_order_id' => $validated['purchase_order_id'],
            'supplier_id' => $po->supplier_id,
            'payment_amount' => $validated['payment_amount'],
            'payment_method' => $validated['payment_method'],
            'status' => 'pending_approval',
            'payment_date' => $validated['payment_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => $payment->load('supplier'),
        ], 201);
    }

    /**
     * Approve payment
     * POST /api/procurement/payments/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        $payment = SupplierPayment::findOrFail($id);

        if ($payment->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payments can be approved',
            ], 422);
        }

        $payment->approve(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Payment approved successfully',
            'data' => $payment->fresh(),
        ]);
    }

    /**
     * Process payment
     * POST /api/procurement/payments/{id}/process
     */
    public function process(int $id): JsonResponse
    {
        $payment = SupplierPayment::with(['purchaseOrder', 'supplier'])->findOrFail($id);

        if ($payment->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved payments can be processed',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $payment->process(auth()->id());

            // Update PO payment status
            $payment->purchaseOrder->update([
                'payment_status' => 'paid',
            ]);

            // Update supplier balance
            $payment->supplier->decrement('current_balance', $payment->payment_amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $payment->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel payment
     * POST /api/procurement/payments/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $payment = SupplierPayment::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed payment',
            ], 422);
        }

        $payment->cancel();
        $payment->update([
            'notes' => ($payment->notes ?? '') . "\n\nCancellation reason: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully',
        ]);
    }

    /**
     * Get pending payments (due soon)
     * GET /api/procurement/payments/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);

        $pendingPayments = PurchaseOrder::with(['supplier', 'branch'])
            ->where('store_id', auth()->user()->store_id)
            ->where('payment_status', 'pending')
            ->whereBetween('payment_due_date', [now(), now()->addDays($days)])
            ->orderBy('payment_due_date', 'asc')
            ->get()
            ->map(function ($po) {
                return [
                    'po_number' => $po->po_number,
                    'supplier_name' => $po->supplier->supplier_name,
                    'total_amount' => $po->total_amount,
                    'payment_due_date' => $po->payment_due_date,
                    'days_until_due' => now()->diffInDays($po->payment_due_date, false),
                    'is_overdue' => $po->payment_due_date < now(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pendingPayments,
        ]);
    }

    /**
     * Get payment summary
     * GET /api/procurement/payments/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $query = SupplierPayment::where('store_id', auth()->user()->store_id);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $summary = [
            'total_payments' => (clone $query)->count(),
            'total_amount_paid' => (clone $query)->where('status', 'completed')->sum('payment_amount'),
            'pending_approval' => (clone $query)->where('status', 'pending_approval')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Delete payment
     * DELETE /api/procurement/payments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $payment = SupplierPayment::findOrFail($id);

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed payment',
            ], 422);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
        ]);
    }
}