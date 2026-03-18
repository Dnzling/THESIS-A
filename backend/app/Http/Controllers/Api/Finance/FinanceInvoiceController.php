<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Invoice\Invoice;
use App\Services\Finance\FinanceExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FinanceInvoiceController extends Controller
{
    /**
     * List invoices for finance
     * GET /api/finance/invoices
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $query = Invoice::where('store_id', $storeId)
                ->with(['supplier:id,supplier_name,supplier_code', 'purchaseOrder:id,po_number'])
                ->latest('invoice_date');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('match_status')) {
                $query->where('match_status', $request->match_status);
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('invoice_date', [$request->date_from, $request->date_to]);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', function ($pq) use ($search) {
                            $pq->where('po_number', 'like', "%{$search}%");
                        });
                });
            }

            $invoices = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $invoices,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve finance invoices', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices',
            ], 500);
        }
    }

    /**
     * Show invoice detail for finance
     * GET /api/finance/invoices/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $invoice = Invoice::with([
                'supplier:id,supplier_name,contact_person,email,phone',
                'purchaseOrder.items.product:id,product_name,sku',
                'goodsReceipt.items',
                'items.product:id,product_name,sku',
            ])
                ->where('store_id', $storeId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve finance invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }
    }

    /**
     * Perform 3-way matching
     * POST /api/finance/invoices/{id}/match
     */
    public function match(int $id): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $invoice = Invoice::where('store_id', $storeId)->findOrFail($id);
            $matchResult = $invoice->performThreeWayMatch();

            return response()->json([
                'success' => true,
                'message' => 'Invoice matching completed',
                'data' => [
                    'invoice' => $invoice,
                    'match_result' => $matchResult,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to perform finance invoice matching', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform matching',
            ], 500);
        }
    }

    /**
     * Approve invoice
     * POST /api/finance/invoices/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $invoice = Invoice::where('store_id', $storeId)->findOrFail($id);

            if ($invoice->match_status === 'pending') {
                $invoice->performThreeWayMatch();
            }

            if ($invoice->match_status === 'exception') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice has matching exceptions and cannot be approved.',
                ], 422);
            }

            if (!$invoice->canApprove()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice cannot be approved.',
                ], 422);
            }

            if (!$invoice->approve()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve invoice',
                ], 500);
            }

            $financeService = new FinanceExpenseService();
            $financeService->ensureExpense([
                'store_id' => $invoice->store_id,
                'department' => 'procurement',
                'category' => 'supplier_invoice',
                'amount' => $invoice->net_amount ?? $invoice->invoice_amount,
                'expense_date' => $invoice->invoice_date,
                'status' => 'pending_approval',
                'reference_number' => $invoice->invoice_number,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'currency' => $invoice->currency ?? 'PHP',
                'description' => "Supplier invoice {$invoice->invoice_number}",
                'notes' => $invoice->remarks,
                'requested_by' => auth()->id(),
            ], true, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Invoice approved successfully',
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve finance invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve invoice',
            ], 500);
        }
    }

    /**
     * Mark invoice as paid
     * POST /api/finance/invoices/{id}/mark-paid
     */
    public function markPaid(Request $request, int $id): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $invoice = Invoice::where('store_id', $storeId)->findOrFail($id);

            if ($invoice->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice is already marked as paid.',
                ], 422);
            }

            if ($invoice->status !== 'approved' && $invoice->status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice must be approved before payment can be recorded.',
                ], 422);
            }

            $validated = $request->validate([
                'payment_method' => 'required|in:cash,check,bank_transfer,credit_card',
                'payment_amount' => 'required|numeric|min:0',
            ]);

            if (!$invoice->markAsPaid($validated['payment_method'], (float) $validated['payment_amount'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark invoice as paid',
                ], 500);
            }

            $invoice->update([
                'status' => 'paid',
                'payment_date' => now()->toDateString(),
            ]);

            $financeService = new FinanceExpenseService();
            $expense = $financeService->ensureExpense([
                'store_id' => $invoice->store_id,
                'department' => 'procurement',
                'category' => 'supplier_invoice',
                'amount' => $invoice->net_amount ?? $invoice->invoice_amount,
                'expense_date' => $invoice->invoice_date,
                'status' => 'pending_approval',
                'reference_number' => $invoice->invoice_number,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'currency' => $invoice->currency ?? 'PHP',
                'description' => "Supplier invoice {$invoice->invoice_number}",
                'notes' => $invoice->remarks,
                'requested_by' => auth()->id(),
            ], true, auth()->id());

            if ($expense->status !== 'paid') {
                $expense->update([
                    'status' => 'paid',
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => now()->toDateString(),
                    'paid_by' => auth()->id(),
                    'paid_at' => now(),
                ]);
            }

            if ($invoice->purchaseOrder) {
                $invoice->purchaseOrder->update(['payment_status' => 'paid']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as paid',
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark finance invoice paid', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as paid',
            ], 500);
        }
    }
}
