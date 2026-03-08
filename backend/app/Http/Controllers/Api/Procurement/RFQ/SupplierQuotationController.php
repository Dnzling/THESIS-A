<?php
// backend/app/Http/Controllers/Procurement/RFQ/SupplierQuotationController.php

namespace App\Http\Controllers\Api\Procurement\RFQ;

use App\Http\Controllers\Controller;
use App\Models\Procurement\RFQ\SupplierQuotation;
use App\Models\Procurement\RFQ\SupplierQuotationItem;
use App\Models\Procurement\RFQ\RequestForQuotation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SupplierQuotationController extends Controller
{
    /**
     * List all quotations
     * GET /api/procurement/quotations
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupplierQuotation::with(['rfq', 'supplier']);

        // Filters
        if ($request->has('rfq_id')) {
            $query->where('rfq_id', $request->rfq_id);
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $quotations = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $quotations,
        ]);
    }

    /**
     * Show single quotation
     * GET /api/procurement/quotations/{id}
     */
    public function show(int $id): JsonResponse
    {
        $quotation = SupplierQuotation::with([
            'rfq',
            'supplier',
            'items.rfqItem.product'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $quotation,
        ]);
    }

    /**
     * Create quotation (supplier submits)
     * POST /api/procurement/quotations
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_id' => 'required|exists:request_for_quotations,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'payment_terms' => 'required|in:cash_on_delivery,net_7,net_15,net_30,net_60',
            'delivery_days' => 'required|integer|min:1',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rfq_item_id' => 'required|exists:rfq_items,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate quotation number
            $lastQuotation = SupplierQuotation::latest()->first();
            $quotationNumber = 'QUO-' . date('Y') . '-' . str_pad(($lastQuotation?->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                if (isset($item['discount_percent'])) {
                    $lineTotal -= $lineTotal * ($item['discount_percent'] / 100);
                }
                $subtotal += $lineTotal;
            }

            $taxAmount = $subtotal * 0.12; // 12% VAT
            $shippingCost = $validated['shipping_cost'] ?? 0;
            $totalAmount = $subtotal + $taxAmount + $shippingCost;

            // Create quotation
            $quotation = SupplierQuotation::create([
                'quotation_number' => $quotationNumber,
                'rfq_id' => $validated['rfq_id'],
                'supplier_id' => $validated['supplier_id'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'payment_terms' => $validated['payment_terms'],
                'delivery_days' => $validated['delivery_days'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'submitted',
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                if (isset($item['discount_percent'])) {
                    $lineTotal -= $lineTotal * ($item['discount_percent'] / 100);
                }

                SupplierQuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'rfq_item_id' => $item['rfq_item_id'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'line_total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Update RFQ supplier status
            DB::table('rfq_suppliers')
                ->where('rfq_id', $validated['rfq_id'])
                ->where('supplier_id', $validated['supplier_id'])
                ->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quotation submitted successfully',
                'data' => $quotation->load('items'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quotation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Evaluate quotation
     * POST /api/procurement/quotations/{id}/evaluate
     */
    public function evaluate(Request $request, int $id): JsonResponse
    {
        $quotation = SupplierQuotation::findOrFail($id);

        $validated = $request->validate([
            'evaluation_score' => 'required|numeric|min:0|max:100',
            'evaluation_notes' => 'nullable|string',
        ]);

        $quotation->evaluate(
            $validated['evaluation_score'],
            $validated['evaluation_notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Quotation evaluated successfully',
            'data' => $quotation->fresh(),
        ]);
    }

    /**
     * Accept quotation
     * POST /api/procurement/quotations/{id}/accept
     */
    public function accept(int $id): JsonResponse
    {
        $quotation = SupplierQuotation::with('rfq')->findOrFail($id);

        if ($quotation->status === 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Quotation has already been accepted',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $quotation->accept();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quotation accepted successfully',
                'data' => $quotation->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept quotation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject quotation
     * POST /api/procurement/quotations/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $quotation = SupplierQuotation::findOrFail($id);

        $validated = $request->validate([
            'evaluation_notes' => 'nullable|string',
        ]);

        $quotation->reject($validated['evaluation_notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Quotation rejected',
        ]);
    }

    /**
     * Compare quotations for an RFQ
     * GET /api/procurement/rfqs/{rfqId}/quotations/compare
     */
    public function compare(int $rfqId): JsonResponse
    {
        $quotations = SupplierQuotation::with(['supplier', 'items'])
            ->where('rfq_id', $rfqId)
            ->where('status', 'submitted')
            ->get();

        if ($quotations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No quotations available for comparison',
            ], 404);
        }

        $comparison = $quotations->map(function ($quotation) {
            return [
                'quotation_id' => $quotation->id,
                'supplier_name' => $quotation->supplier->supplier_name,
                'total_amount' => $quotation->total_amount,
                'delivery_days' => $quotation->delivery_days,
                'payment_terms' => $quotation->payment_terms,
                'supplier_rating' => $quotation->supplier->rating,
                'evaluation_score' => $quotation->evaluation_score,
                'items' => $quotation->items->map(function ($item) {
                    return [
                        'product' => $item->rfqItem->product->product_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => $item->quantity,
                        'line_total' => $item->line_total,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $comparison,
        ]);
    }
}