<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\Shipping\PurchaseOrderShipment;
use App\Services\Finance\CashflowService;
use App\Services\Finance\FinanceExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'supplier:id,supplier_name,contact_person,email,phone,bank_name,bank_account_name,bank_account_number,bank_account_type,bank_branch',
                'purchaseOrder.items.product:id,product_name,sku',
                'goodsReceipt.items',
                'items.product:id,product_name,sku',
            ])
                ->where('store_id', $storeId)
                ->findOrFail($id);

            // Fallback for supplier-submitted invoices that were created without direct goods_receipt_id.
            // Attach latest GRN for the same PO so 3-way match UI can display GRN details.
            if (!$invoice->relationLoaded('goodsReceipt') || !$invoice->goodsReceipt) {
                $fallbackReceipt = GoodsReceipt::with('items')
                    ->where('purchase_order_id', (int) $invoice->purchase_order_id)
                    ->latest('id')
                    ->first();

                if ($fallbackReceipt) {
                    $invoice->setRelation('goodsReceipt', $fallbackReceipt);
                    if (!$invoice->goods_receipt_id) {
                        $invoice->goods_receipt_id = (int) $fallbackReceipt->id;
                        $invoice->saveQuietly();
                    }
                }
            }

            $payload = $invoice->toArray();
            if (empty($payload['goods_receipt']) && $invoice->goodsReceipt) {
                $payload['goods_receipt'] = $invoice->goodsReceipt->toArray();
                $payload['goods_receipt_id'] = (int) ($invoice->goods_receipt_id ?: $invoice->goodsReceipt->id);
            }

            // Last-resort display fallback: if no GRN exists yet, surface delivered shipment as receipt-like context
            // so Finance 3-way tab does not show an empty Goods Receipt card.
            if (empty($payload['goods_receipt'])) {
                $shipment = PurchaseOrderShipment::where('purchase_order_id', (int) $invoice->purchase_order_id)
                    ->whereIn('status', ['delivered', 'in_transit'])
                    ->latest('id')
                    ->first();

                if ($shipment) {
                    $poItems = $invoice->purchaseOrder?->items ?? collect();
                    $payload['goods_receipt'] = [
                        'id' => null,
                        'grn_number' => 'From Shipment #' . $shipment->id,
                        'receipt_date' => $shipment->delivered_at ?: $shipment->dispatched_at ?: $shipment->created_at,
                        'receipt_status' => (string) $shipment->status,
                        'items' => $poItems->map(function ($item) {
                            $qty = (int) ($item->quantity_ordered ?? 0);
                            return [
                                'id' => null,
                                'product_id' => (int) ($item->product_id ?? 0),
                                'quantity_expected' => $qty,
                                'quantity_received' => $qty,
                                'quantity_damaged' => 0,
                            ];
                        })->values()->all(),
                    ];
                    $payload['goods_receipt_id'] = null;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $payload,
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

            if (!$this->hasCompleteSupplierPaymentAccount((int) $invoice->supplier_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier payment account is incomplete. Please ask supplier to set bank details before payment.',
                ], 422);
            }

            $validated = $request->validate([
                'payment_method' => 'required|in:cash,check,bank_transfer,credit_card,paymongo_gcash,gcash',
                'payment_amount' => 'required|numeric|min:0',
            ]);
            $supplier = $invoice->supplier_id ? Supplier::query()->find((int) $invoice->supplier_id) : null;

            try {
                DB::transaction(function () use ($invoice, $validated, $supplier) {
                    $cashflow = new CashflowService();
                    $cashflow->debit(
                        (int) $invoice->store_id,
                        (float) $validated['payment_amount'],
                        'invoice',
                        (int) $invoice->id,
                        auth()->id(),
                        'Invoice payment ' . ($invoice->invoice_number ?? ('#' . $invoice->id)),
                        $validated['payment_method']
                    );

                    if (!$invoice->markAsPaid($validated['payment_method'], (float) $validated['payment_amount'])) {
                        throw new \RuntimeException('Failed to mark invoice as paid');
                    }

                    $invoice->update([
                        'status' => 'paid',
                        'payment_date' => now()->toDateString(),
                        'paid_to_bank_name' => $supplier?->bank_name,
                        'paid_to_account_name' => $supplier?->bank_account_name,
                        'paid_to_account_number_masked' => $this->maskAccountNumber($supplier?->bank_account_number),
                        'paid_to_account_type' => $supplier?->bank_account_type,
                        'paid_to_bank_branch' => $supplier?->bank_branch,
                    ]);

                    $financeService = new FinanceExpenseService();
                    $expense = $financeService->ensureExpense([
                        'store_id' => $invoice->store_id,
                        'department' => 'procurement',
                        'category' => 'supplier_invoice',
                        'amount' => (float) $validated['payment_amount'],
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

                    if ($invoice->supplier_id) {
                        \App\Models\Procurement\Supplier\Supplier::where('id', (int) $invoice->supplier_id)
                            ->update([
                                'current_balance' => DB::raw('GREATEST(COALESCE(current_balance, 0), 0) + ' . ((float) $validated['payment_amount']))
                            ]);
                    }
                });
            } catch (\RuntimeException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            $this->notifySupplierUsersInvoicePaid($invoice->fresh() ?? $invoice);

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

    private function notifySupplierUsersInvoicePaid(Invoice $invoice): void
    {
        $supplier = $invoice->supplier;
        $supplierEmail = strtolower(trim((string) ($supplier?->email ?? '')));
        if ($supplierEmail === '') {
            return;
        }

        $portalUserIds = SupplierPortal::query()
            ->whereHas('supplier', function ($q) use ($supplierEmail) {
                $q->whereRaw('LOWER(email) = ?', [$supplierEmail]);
            })
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($portalUserIds)) {
            return;
        }

        $payload = [
            'store_id' => (int) $invoice->store_id,
            'module' => 'supplier',
            'entity_type' => 'invoice',
            'entity_id' => (int) $invoice->id,
            'action' => 'invoice_paid',
            'title' => 'Invoice Paid',
            'message' => "Your invoice {$invoice->invoice_number} has been paid by Finance.",
            'severity' => 'success',
            'link' => "/supplier-portal/pos/{$invoice->purchase_order_id}/invoice-view",
            'data' => [
                'invoice_id' => (int) $invoice->id,
                'invoice_number' => (string) $invoice->invoice_number,
                'payment_amount' => (float) ($invoice->payment_amount ?? $invoice->net_amount ?? 0),
                'payment_date' => (string) ($invoice->payment_date ?? now()->toDateString()),
            ],
        ];

        foreach ($portalUserIds as $userId) {
            $this->notify($userId, $payload);
        }
    }

    private function hasCompleteSupplierPaymentAccount(int $supplierId): bool
    {
        if ($supplierId <= 0) {
            return false;
        }

        $supplier = Supplier::query()->find($supplierId);
        if (!$supplier) {
            return false;
        }

        return filled($supplier->bank_name)
            && filled($supplier->bank_account_name)
            && filled($supplier->bank_account_number);
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        $raw = trim((string) $accountNumber);
        if ($raw === '') {
            return null;
        }
        $len = strlen($raw);
        if ($len <= 4) {
            return str_repeat('*', max(0, $len - 1)) . substr($raw, -1);
        }
        return str_repeat('*', $len - 4) . substr($raw, -4);
    }
}
