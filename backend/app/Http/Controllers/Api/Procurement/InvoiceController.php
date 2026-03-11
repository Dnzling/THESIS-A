<?php
// backend/app/Http/Controllers/Api/Procurement/InvoiceController.php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Invoice\InvoiceItem;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                'items.*.tax_amount' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();

            try {
                // Calculate net amount
                $taxAmount = $validated['tax_amount'] ?? 0;
                $shippingCost = $validated['shipping_cost'] ?? 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $netAmount = $validated['invoice_amount'] + $taxAmount + $shippingCost - $discountAmount;

                // Create invoice
                $invoice = Invoice::create([
                    'store_id' => auth()->user()->store_id,
                    'invoice_number' => $validated['invoice_number'],
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_order_id' => $validated['purchase_order_id'],
                    'goods_receipt_id' => $validated['goods_receipt_id'],
                    'invoice_date' => $validated['invoice_date'],
                    'due_date' => $validated['due_date'],
                    'invoice_amount' => $validated['invoice_amount'],
                    'tax_amount' => $taxAmount,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount,
                    'currency' => $validated['currency'],
                    'invoice_file_path' => $validated['invoice_file_path'],
                    'status' => 'draft',
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
                        'tax_rate' => $item['tax_rate'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
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

            // Recalculate net amount
            if (isset($validated['invoice_amount'], $validated['tax_amount'], $validated['shipping_cost'], $validated['discount_amount'])) {
                $netAmount = $validated['invoice_amount'] + ($validated['tax_amount'] ?? 0) + 
                             ($validated['shipping_cost'] ?? 0) - ($validated['discount_amount'] ?? 0);
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

            $validated = $request->validate([
                'payment_method' => 'required|in:cash,check,bank_transfer,credit_card',
                'payment_amount' => 'required|numeric|min:0',
            ]);

            if ($invoice->markAsPaid($validated['payment_method'], $validated['payment_amount'])) {
                Log::info("Invoice marked as paid: {$invoice->invoice_number}", [
                    'invoice_id' => $id,
                    'paid_by' => auth()->id(),
                    'payment_method' => $validated['payment_method'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice marked as paid',
                    'data' => $invoice,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as paid'
            ], 500);

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
}
