<?php
// backend/app/Http/Controllers/Api/Procurement/InvoiceController.php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Invoice\InvoiceItem;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Services\Finance\CashflowService;
use App\Services\Finance\FinanceExpenseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * List all invoices
     * GET /api/procurement/invoices
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            
            $query = Invoice::where('store_id', $storeId)
                ->with(['supplier:id,supplier_name', 'purchaseOrder:id,po_number'])
                ->latest('invoice_date');

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('match_status')) {
                $query->where('match_status', $request->match_status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('invoice_date', [$request->date_from, $request->date_to]);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('purchaseOrder', function($pq) use ($search) {
                          $pq->where('po_number', 'like', "%{$search}%");
                      });
                });
            }

            $invoices = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Invoices retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve invoices', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices'
            ], 500);
        }
    }

    /**
     * Show invoice detail
     * GET /api/procurement/invoices/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $invoice = Invoice::with([
                'supplier:id,supplier_name,contact_person,email,phone',
                'purchaseOrder.items.product:id,product_name,sku',
                'goodsReceipt.items',
                'items.product:id,product_name,sku'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }
    }

    /**
     * Create invoice
     * POST /api/procurement/invoices
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'goods_receipt_id' => 'nullable|exists:goods_receipts,id',
                'invoice_number' => 'required|string|max:100|unique:invoices,invoice_number',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'invoice_amount' => 'required|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'shipping_cost' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'currency' => 'required|string|max:3',
                'invoice_file_path' => 'nullable|string|max:500',
                'remarks' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|exists:products,id',
                'items.*.quantity_invoiced' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.line_amount' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            try {
                $po = PurchaseOrder::where('store_id', auth()->user()->store_id)
                    ->findOrFail($validated['purchase_order_id']);

                if (!in_array($po->status, ['sent_to_supplier', 'supplier_accepted', 'in_transit', 'delivered'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice can only be created for sent or delivered purchase orders',
                    ], 422);
                }

                $tolerance = 0.01;
                if (abs(((float) $validated['invoice_amount']) - ((float) $po->subtotal)) > $tolerance) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice amount must match PO subtotal.',
                    ], 422);
                }

                $contract = $this->getActiveContract($po->store_id, (int) $validated['supplier_id']);
                if (!$contract) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active supplier contract found for this store. Please activate a contract before creating an invoice.',
                    ], 422);
                }

                if (!empty($validated['goods_receipt_id'])) {
                    $grnValid = \App\Models\Procurement\Receiving\GoodsReceipt::where('id', $validated['goods_receipt_id'])
                        ->where('purchase_order_id', $po->id)
                        ->exists();

                    if (!$grnValid) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Selected goods receipt does not belong to this purchase order',
                        ], 422);
                    }
                }

                // Calculate amounts from active supplier contract (header-level rates).
                $shippingCost = $validated['shipping_cost'] ?? 0;
                $itemsSubtotal = collect($validated['items'])->sum(function ($item) {
                    return (float) ($item['line_amount'] ?? 0);
                });
                if (abs(((float) $validated['invoice_amount']) - $itemsSubtotal) > $tolerance) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice amount must equal the sum of item line amounts (product subtotal).',
                    ], 422);
                }

                $taxRate = $contract->is_tax_exempt ? 0.0 : (float) ($contract->tax_rate ?? 0);
                $discountRate = (float) ($contract->discount_percentage ?? 0);
                $invoiceAmount = (float) $validated['invoice_amount'];
                $taxAmount = round(($invoiceAmount * $taxRate) / 100, 2);
                $discountAmount = round(($invoiceAmount * $discountRate) / 100, 2);
                $netAmount = $invoiceAmount + (float) $taxAmount + (float) $shippingCost - (float) $discountAmount;

                // Create invoice
                $invoice = Invoice::create([
                    'store_id' => auth()->user()->store_id,
                    'invoice_number' => $validated['invoice_number'],
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_order_id' => $validated['purchase_order_id'],
                    'goods_receipt_id' => $validated['goods_receipt_id'],
                    'invoice_date' => $validated['invoice_date'],
                    'due_date' => $validated['due_date'],
                    'invoice_amount' => $invoiceAmount,
                    'tax_amount' => $taxAmount,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount,
                    'currency' => $validated['currency'],
                    'invoice_file_path' => $validated['invoice_file_path'],
                    'status' => 'pending_approval',
                    'match_status' => 'pending',
                    'payment_status' => 'pending',
                    'remarks' => $validated['remarks'],
                ]);

                // Create invoice items
                foreach ($validated['items'] as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product_id'],
                        'quantity_invoiced' => $item['quantity_invoiced'],
                        'unit_price' => $item['unit_price'],
                        'line_amount' => $item['line_amount'],
                    ]);
                }

                // Perform 3-way matching
                $invoice->performThreeWayMatch();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice created successfully',
                    'data' => $invoice->load('items'),
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to create invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-create invoice from GRN
     * POST /api/procurement/invoices/from-grn
     */
    public function createFromGoodsReceipt(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'goods_receipt_id' => 'nullable|exists:goods_receipts,id',
                'submitted_by_supplier' => 'nullable|boolean',
            ]);

            DB::beginTransaction();

            $po = PurchaseOrder::where('store_id', auth()->user()->store_id)
                ->with('items')
                ->findOrFail($validated['purchase_order_id']);

            $contract = $this->getActiveContract($po->store_id, (int) $po->supplier_id);
            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active supplier contract found for this store. Please activate a contract before creating an invoice.',
                ], 422);
            }

            if (!in_array((string) $po->status, ['approved', 'sent_to_supplier', 'supplier_accepted', 'in_transit', 'delivered', 'goods_received'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice can only be generated for approved or active supplier purchase orders.',
                ], 422);
            }

            $grnQuery = \App\Models\Procurement\Receiving\GoodsReceipt::with('items')
                ->where('purchase_order_id', $po->id);

            if (!empty($validated['goods_receipt_id'])) {
                $grn = $grnQuery->findOrFail($validated['goods_receipt_id']);
            } else {
                $grn = $grnQuery->latest('id')->first();
            }

            if (!$grn) {
                $shipment = \App\Models\Procurement\Shipping\PurchaseOrderShipment::where('purchase_order_id', $po->id)
                    ->whereIn('status', ['in_transit', 'delivered'])
                    ->latest('id')
                    ->first();

                if (!$shipment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No goods receipt or shipment found for this PO yet.',
                    ], 422);
                }
            }

            $invoiceNumber = 'INV-' . now()->format('YmdHis') . '-' . strtoupper(\Str::random(4));
            $paymentDays = $this->getPaymentDays($po->payment_terms);
            $dueDate = now()->addDays($paymentDays)->toDateString();

            $itemsPayload = [];
            $invoiceAmount = 0;

            if ($grn) {
                foreach ($grn->items as $item) {
                    $poItem = $po->items->firstWhere('id', $item->purchase_order_item_id);
                    if (!$poItem || $item->quantity_received === 0) {
                        continue;
                    }

                    $lineAmount = round($poItem->unit_cost * $item->quantity_received, 2);
                    $itemsPayload[] = [
                        'product_id' => $item->product_id,
                        'quantity_invoiced' => $item->quantity_received,
                        'unit_price' => $poItem->unit_cost,
                        'line_amount' => $lineAmount,
                    ];

                    $invoiceAmount += $lineAmount;
                }
            } else {
                foreach ($po->items as $poItem) {
                    $qty = (int) ($poItem->quantity_ordered ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $lineAmount = round($poItem->unit_cost * $qty, 2);
                    $itemsPayload[] = [
                        'product_id' => $poItem->product_id,
                        'quantity_invoiced' => $qty,
                        'unit_price' => $poItem->unit_cost,
                        'line_amount' => $lineAmount,
                    ];

                    $invoiceAmount += $lineAmount;
                }
            }

            $taxRate = $contract->is_tax_exempt ? 0.0 : (float) ($contract->tax_rate ?? 0);
            $discountRate = (float) ($contract->discount_percentage ?? 0);
            $taxAmountTotal = round(($invoiceAmount * $taxRate) / 100, 2);
            $shippingCost = $po->shipping_cost;
            $discountAmount = round(($invoiceAmount * $discountRate) / 100, 2);
            $netAmount = $invoiceAmount + $taxAmountTotal + $shippingCost - $discountAmount;

            $invoice = Invoice::create([
                'store_id' => auth()->user()->store_id,
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $po->supplier_id,
                'purchase_order_id' => $po->id,
                'goods_receipt_id' => $grn?->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'invoice_amount' => $invoiceAmount,
                'tax_amount' => $taxAmountTotal,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'currency' => $po->currency ?? 'PHP',
                'status' => !empty($validated['submitted_by_supplier']) ? 'pending_approval' : 'draft',
                'match_status' => 'pending',
                'payment_status' => 'pending',
                'remarks' => $grn
                    ? "Auto-created from GRN {$grn->grn_number}"
                    : 'Auto-created from shipment confirmation',
            ]);

            foreach ($itemsPayload as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }

            $invoice->performThreeWayMatch();

            // Supplier balance is recognized when Finance actually pays the invoice.

            DB::commit();

            if (!empty($validated['submitted_by_supplier'])) {
                $notificationPayload = [
                    'store_id' => (int) $po->store_id,
                    'branch_id' => (int) $po->branch_id,
                    'module' => 'finance',
                    'entity_type' => 'invoice',
                    'entity_id' => (int) $invoice->id,
                    'action' => 'supplier_invoice_submitted',
                    'title' => 'Supplier Invoice Submitted',
                    'message' => "Supplier submitted invoice {$invoice->invoice_number} for PO {$po->po_number}.",
                    'severity' => 'info',
                    'link' => "/finance/invoices/{$invoice->id}",
                    'data' => [
                        'invoice_id' => (int) $invoice->id,
                        'invoice_number' => (string) $invoice->invoice_number,
                        'purchase_order_id' => (int) $po->id,
                        'purchase_order_number' => (string) ($po->po_number ?? ''),
                    ],
                ];

                $this->notifyUsersByPermissions(
                    (int) $po->store_id,
                    [
                        'finance.invoices.view',
                        'finance.invoices.manage',
                        'finance.invoices.approve',
                        'finance.payables.view',
                        'finance.payables.manage',
                        'finance.payables.approve',
                    ],
                    $notificationPayload
                );
            }

            return response()->json([
                'success' => true,
                'message' => !empty($validated['submitted_by_supplier'])
                    ? 'Invoice submitted to finance successfully'
                    : 'Invoice draft created from GRN',
                'data' => $invoice->load('items'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create invoice from GRN', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice from receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getPaymentDays(?string $term): int
    {
        return match ($term) {
            'net_7' => 7,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'advance_payment' => 0,
            'cash_on_delivery' => 0,
            default => 30,
        };
    }

    /**
     * Update invoice
     * PUT /api/procurement/invoices/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update invoice that is not in draft status'
                ], 422);
            }

            $validated = $request->validate([
                'invoice_number' => 'nullable|string|max:100|unique:invoices,invoice_number,' . $id,
                'due_date' => 'nullable|date',
                'invoice_amount' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'shipping_cost' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'remarks' => 'nullable|string',
            ]);

            $invoice->update($validated);

            // Recalculate net amount when any relevant amount changes.
            if (
                array_key_exists('invoice_amount', $validated) ||
                array_key_exists('tax_amount', $validated) ||
                array_key_exists('shipping_cost', $validated) ||
                array_key_exists('discount_amount', $validated)
            ) {
                $netAmount = ((float) ($validated['invoice_amount'] ?? $invoice->invoice_amount))
                    + ((float) ($validated['tax_amount'] ?? $invoice->tax_amount ?? 0))
                    + ((float) ($validated['shipping_cost'] ?? $invoice->shipping_cost ?? 0))
                    - ((float) ($validated['discount_amount'] ?? $invoice->discount_amount ?? 0));
                $invoice->update(['net_amount' => $netAmount]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice'
            ], 500);
        }
    }

    /**
     * Perform 3-way matching
     * POST /api/procurement/invoices/{id}/match
     */
    public function performMatch(int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);
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
            Log::error('Failed to perform matching', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform matching'
            ], 500);
        }
    }

    /**
     * Approve invoice
     * POST /api/procurement/invoices/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if (!$invoice->canApprove()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice cannot be approved. It must be in draft status and have matched status.'
                ], 422);
            }

            $financeService = new FinanceExpenseService();
            $requiresFinance = $financeService->requiresFinanceApproval($invoice->store_id, (float) $invoice->net_amount);

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
            ], !$requiresFinance, auth()->id());

            if ($requiresFinance && $expense->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Finance approval is required before approving this invoice.',
                ], 422);
            }

            if ($invoice->approve()) {
                Log::info("Invoice approved: {$invoice->invoice_number}", [
                    'invoice_id' => $id,
                    'approved_by' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice approved successfully',
                    'data' => $invoice,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve invoice'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to approve invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve invoice'
            ], 500);
        }
    }

    /**
     * Schedule payment for invoice
     * POST /api/procurement/invoices/{id}/schedule-payment
     */
    public function schedulePayment(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            $validated = $request->validate([
                'payment_date' => 'required|date|after_or_equal:today',
            ]);

            if ($invoice->schedulePayment(new \DateTime($validated['payment_date']))) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment scheduled successfully',
                    'data' => $invoice,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule payment'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to schedule payment', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule payment'
            ], 500);
        }
    }

    /**
     * Mark invoice as paid
     * POST /api/procurement/invoices/{id}/mark-paid
     */
    public function markPaid(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

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

            DB::transaction(function () use ($invoice, $validated) {
                // Record finance cashflow out when invoice payment is posted.
                $alreadyRecorded = DB::table('finance_cashflow_transactions')
                    ->where('store_id', (int) $invoice->store_id)
                    ->where('direction', 'out')
                    ->where('reference_type', 'invoice')
                    ->where('reference_id', (int) $invoice->id)
                    ->exists();

                if (!$alreadyRecorded) {
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
                }

                if (!$invoice->markAsPaid($validated['payment_method'], (float) $validated['payment_amount'])) {
                    throw new \RuntimeException('Failed to mark invoice as paid');
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
            });

            if ($invoice->fresh()) {
                Log::info("Invoice marked as paid: {$invoice->invoice_number}", [
                    'invoice_id' => $id,
                    'paid_by' => auth()->id(),
                    'payment_method' => $validated['payment_method'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice marked as paid',
                    'data' => $invoice->fresh(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as paid'
            ], 500);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to mark invoice as paid', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as paid'
            ], 500);
        }
    }

    /**
     * Get pending invoices for 3-way matching
     * GET /api/procurement/invoices/pending/match
     */
    public function getPendingMatch(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            
            $invoices = Invoice::where('store_id', $storeId)
                ->where('match_status', 'pending')
                ->with(['supplier:id,supplier_name', 'purchaseOrder:id,po_number'])
                ->orderBy('invoice_date', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $invoices,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get pending invoices', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending invoices'
            ], 500);
        }
    }

    /**
     * Get invoice exceptions
     * GET /api/procurement/invoices/exceptions
     */
    public function getExceptions(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $invoices = Invoice::where('store_id', $storeId)
                ->where('match_status', 'exception')
                ->with(['supplier:id,supplier_name', 'purchaseOrder:id,po_number'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $invoices,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get exceptions', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exceptions'
            ], 500);
        }
    }

    private function getActiveContract(int $storeId, int $supplierId): ?SupplierContract
    {
        return SupplierContract::where('store_id', $storeId)
            ->where('supplier_id', $supplierId)
            ->active()
            ->orderBy('end_date', 'desc')
            ->first();
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
}
