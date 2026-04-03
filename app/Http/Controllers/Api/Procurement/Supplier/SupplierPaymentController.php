<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierPaymentController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\SupplierPayment;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Services\Finance\FinanceExpenseService;
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
        $storeId = (int) ($request->user()?->store_id ?? 0);
        $query = SupplierPayment::with(['purchaseOrder', 'supplier', 'approvedBy', 'processedBy']);

        if ($storeId > 0) {
            $query->where('store_id', $storeId);
        }

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

        // Generate payment number using datetime for uniqueness
        $paymentNumber = 'PAY-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

        $payment = SupplierPayment::create([
            'payment_number' => $paymentNumber,
            'store_id' => $po->store_id,
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

        $financeService = new FinanceExpenseService();
        $requiresFinance = $financeService->requiresFinanceApproval($po->store_id, (float) $validated['payment_amount']);

        $financeService->ensureExpense([
            'store_id' => $po->store_id,
            'department' => 'procurement',
            'category' => 'supplier_payment',
            'amount' => $validated['payment_amount'],
            'expense_date' => $validated['payment_date'],
            'status' => 'pending_approval',
            'reference_number' => $paymentNumber,
            'reference_type' => 'supplier_payment',
            'reference_id' => $payment->id,
            'currency' => 'PHP',
            'description' => "Supplier payment {$paymentNumber} for PO {$po->po_number}",
            'notes' => $validated['notes'] ?? null,
            'requested_by' => auth()->id(),
        ], !$requiresFinance, auth()->id());

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

        $po = $payment->purchaseOrder;
        $creatorUserId = $po?->createdBy?->user_id;
        if ($creatorUserId && $po) {
            $this->notify($creatorUserId, [
                'store_id' => $po->store_id,
                'branch_id' => $po->branch_id,
                'module' => 'finance',
                'entity_type' => 'supplier_payment',
                'entity_id' => $payment->id,
                'action' => 'approved',
                'title' => 'Payment Approved',
                'message' => "Payment {$payment->payment_number} approved for PO {$po->po_number}.",
                'severity' => 'success',
                'link' => "/system/finance/payments/{$payment->id}",
            ]);
        }

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

        $financeService = new FinanceExpenseService();
        $requiresFinance = $financeService->requiresFinanceApproval($payment->purchaseOrder->store_id, (float) $payment->payment_amount);

        $expense = $financeService->ensureExpense([
            'store_id' => $payment->purchaseOrder->store_id,
            'department' => 'procurement',
            'category' => 'supplier_payment',
            'amount' => $payment->payment_amount,
            'expense_date' => $payment->payment_date,
            'status' => 'pending_approval',
            'reference_number' => $payment->payment_number,
            'reference_type' => 'supplier_payment',
            'reference_id' => $payment->id,
            'currency' => 'PHP',
            'description' => "Supplier payment {$payment->payment_number} for PO {$payment->purchaseOrder->po_number}",
            'notes' => $payment->notes,
            'requested_by' => auth()->id(),
        ], !$requiresFinance, auth()->id());

        if ($requiresFinance && $expense->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Finance approval is required before processing this payment.',
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

            if ($expense->status !== 'paid') {
                $expense->update([
                    'status' => 'paid',
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'reference_number' => $payment->reference_number,
                    'paid_by' => auth()->id(),
                    'paid_at' => now(),
                ]);
            }

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

        $po = $payment->purchaseOrder;
        $creatorUserId = $po?->createdBy?->user_id;
        if ($creatorUserId && $po) {
            $this->notify($creatorUserId, [
                'store_id' => $po->store_id,
                'branch_id' => $po->branch_id,
                'module' => 'finance',
                'entity_type' => 'supplier_payment',
                'entity_id' => $payment->id,
                'action' => 'rejected',
                'title' => 'Payment Rejected',
                'message' => "Payment {$payment->payment_number} rejected for PO {$po->po_number}.",
                'severity' => 'danger',
                'link' => "/system/finance/payments/{$payment->id}",
            ]);
        }

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
        $storeId = (int) ($request->user()?->store_id ?? 0);

        $pendingPayments = PurchaseOrder::with(['supplier', 'branch'])
            ->when($storeId > 0, fn($q) => $q->where('store_id', $storeId))
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
        $storeId = (int) ($request->user()?->store_id ?? 0);
        $query = SupplierPayment::query();

        if ($storeId > 0) {
            $query->where('store_id', $storeId);
        }

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
