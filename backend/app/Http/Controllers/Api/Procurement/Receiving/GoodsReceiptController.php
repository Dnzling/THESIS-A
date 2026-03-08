<?php
// backend/app/Http/Controllers/Procurement/Receiving/GoodsReceiptController.php

namespace App\Http\Controllers\Api\Procurement\Receiving;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Receiving\GoodsReceiptItem;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    /**
     * List all goods receipts
     * GET /api/procurement/goods-receipts
     */
    public function index(Request $request): JsonResponse
    {
        $query = GoodsReceipt::with(['purchaseOrder', 'branch', 'receivedBy']);

        // Filters
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('purchase_order_id')) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        if ($request->has('receipt_status')) {
            $query->where('receipt_status', $request->receipt_status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('receipt_date', [$request->start_date, $request->end_date]);
        }

        $receipts = $query->orderBy('receipt_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $receipts,
        ]);
    }

    /**
     * Show single goods receipt
     * GET /api/procurement/goods-receipts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $receipt = GoodsReceipt::with([
            'purchaseOrder.supplier',
            'branch',
            'items.product',
            'items.variation',
            'items.purchaseOrderItem',
            'receivedBy',
            'verifiedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }

    /**
     * Create goods receipt
     * POST /api/procurement/goods-receipts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'receipt_date' => 'required|date',
            'receipt_time' => 'required',
            'delivery_note_number' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'discrepancy_notes' => 'nullable|string',
            'quality_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity_expected' => 'required|integer|min:0',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.quantity_damaged' => 'nullable|integer|min:0',
            'items.*.condition' => 'required|in:good,damaged,defective',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $po = PurchaseOrder::with('items')->findOrFail($validated['purchase_order_id']);

            // Generate GRN number
            $lastGRN = GoodsReceipt::latest()->first();
            $grnNumber = 'GRN-' . date('Y') . '-' . str_pad(($lastGRN?->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            // Determine receipt status
            $receiptStatus = 'full';
            $hasDamaged = false;
            $hasPartial = false;

            foreach ($validated['items'] as $item) {
                if ($item['quantity_damaged'] > 0) {
                    $hasDamaged = true;
                }
                if ($item['quantity_received'] < $item['quantity_expected']) {
                    $hasPartial = true;
                }
            }

            if ($hasDamaged) {
                $receiptStatus = 'damaged';
            } elseif ($hasPartial) {
                $receiptStatus = 'partial';
            }

            // Create GRN
            $grn = GoodsReceipt::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $validated['purchase_order_id'],
                'branch_id' => $po->branch_id,
                'receipt_date' => $validated['receipt_date'],
                'receipt_time' => $validated['receipt_time'],
                'receipt_status' => $receiptStatus,
                'received_by' => auth()->id(),
                'delivery_note_number' => $validated['delivery_note_number'] ?? null,
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'discrepancy_notes' => $validated['discrepancy_notes'] ?? null,
                'quality_notes' => $validated['quality_notes'] ?? null,
            ]);

            // Create items and update inventory
            foreach ($validated['items'] as $itemData) {
                // Create GRN item
                $grnItem = GoodsReceiptItem::create([
                    'goods_receipt_id' => $grn->id,
                    'purchase_order_item_id' => $itemData['purchase_order_item_id'],
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'quantity_expected' => $itemData['quantity_expected'],
                    'quantity_received' => $itemData['quantity_received'],
                    'quantity_damaged' => $itemData['quantity_damaged'] ?? 0,
                    'condition' => $itemData['condition'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Update PO item
                $poItem = $po->items->firstWhere('id', $itemData['purchase_order_item_id']);
                $poItem->updateReceived($itemData['quantity_received'], $itemData['quantity_damaged'] ?? 0);

                // Update branch inventory
                $inventory = BranchInventory::firstOrCreate(
                    [
                        'branch_id' => $po->branch_id,
                        'product_id' => $itemData['product_id'],
                        'variation_id' => $itemData['variation_id'] ?? null,
                    ],
                    [
                        'store_id' => $po->store_id,
                        'quantity_on_hand' => 0,
                        'quantity_available' => 0,
                        'reorder_point' => 10,
                        'reorder_quantity' => 20,
                        'safety_stock' => 5,
                        'stock_status' => 'out_of_stock',
                    ]
                );

                $quantityBefore = $inventory->quantity_on_hand;

                // Add received stock
                $inventory->quantity_on_hand += $itemData['quantity_received'];
                $inventory->quantity_available += $itemData['quantity_received'];
                
                // Add damaged stock
                if ($itemData['quantity_damaged'] > 0) {
                    $inventory->quantity_damaged += $itemData['quantity_damaged'];
                }

                // Update costs
                $newCost = $poItem->unit_cost;
                $totalQuantity = $quantityBefore + $itemData['quantity_received'];
                
                if ($totalQuantity > 0) {
                    $inventory->average_cost = (
                        ($inventory->average_cost * $quantityBefore) + 
                        ($newCost * $itemData['quantity_received'])
                    ) / $totalQuantity;
                }

                $inventory->unit_cost = $newCost;
                $inventory->save();

                $inventory->updateStockStatus();
                $inventory->calculateTotalValue();

                // Create inventory transaction
                $transactionNumber = 'TXN-' . date('Y') . '-' . str_pad(InventoryTransaction::count() + 1, 5, '0', STR_PAD_LEFT);

                InventoryTransaction::create([
                    'transaction_number' => $transactionNumber,
                    'store_id' => $po->store_id,
                    'branch_id' => $po->branch_id,
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'transaction_type' => 'purchase',
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => $itemData['quantity_received'],
                    'quantity_after' => $inventory->quantity_on_hand,
                    'reference_type' => 'goods_receipt',
                    'reference_id' => $grn->id,
                    'notes' => "Received from PO {$po->po_number}",
                    'unit_cost' => $newCost,
                    'total_value' => $newCost * $itemData['quantity_received'],
                    'created_by' => auth()->id(),
                    'transaction_date' => now(),
                ]);

                // If damaged, create damage transaction
                if ($itemData['quantity_damaged'] > 0) {
                    $damageTransactionNumber = 'TXN-' . date('Y') . '-' . str_pad(InventoryTransaction::count() + 1, 5, '0', STR_PAD_LEFT);

                    InventoryTransaction::create([
                        'transaction_number' => $damageTransactionNumber,
                        'store_id' => $po->store_id,
                        'branch_id' => $po->branch_id,
                        'product_id' => $itemData['product_id'],
                        'variation_id' => $itemData['variation_id'] ?? null,
                        'transaction_type' => 'damage',
                        'quantity_before' => $inventory->quantity_on_hand,
                        'quantity_change' => $itemData['quantity_damaged'],
                        'quantity_after' => $inventory->quantity_on_hand,
                        'reference_type' => 'goods_receipt',
                        'reference_id' => $grn->id,
                        'notes' => "Damaged items received from PO {$po->po_number}",
                        'unit_cost' => $newCost,
                        'total_value' => $newCost * $itemData['quantity_damaged'],
                        'created_by' => auth()->id(),
                        'transaction_date' => now(),
                    ]);
                }
            }

            // Update PO status
            $allItemsReceived = $po->items->every(function ($item) {
                return $item->isFullyReceived();
            });

            if ($allItemsReceived) {
                $po->update([
                    'status' => 'received',
                    'actual_delivery_date' => $validated['receipt_date'],
                ]);

                // Update supplier performance
                $expectedDate = $po->expected_delivery_date;
                $actualDate = $validated['receipt_date'];
                $onTime = $actualDate <= $expectedDate;

                $po->supplier->recordDelivery($onTime);
            } else {
                $po->update(['status' => 'partially_received']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Goods receipt created successfully',
                'data' => $grn->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create goods receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify goods receipt
     * POST /api/procurement/goods-receipts/{id}/verify
     */
    public function verify(int $id): JsonResponse
    {
        $grn = GoodsReceipt::findOrFail($id);

        $grn->verify(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Goods receipt verified successfully',
            'data' => $grn->fresh(),
        ]);
    }

    /**
     * Get pending receipts for a PO
     * GET /api/procurement/purchase-orders/{poId}/pending-receipt
     */
    public function pendingForPO(int $poId): JsonResponse
    {
        $po = PurchaseOrder::with(['items.product', 'items.variation', 'supplier'])
            ->findOrFail($poId);

        if ($po->status !== 'ordered') {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order is not ready for receiving',
            ], 422);
        }

        $pendingItems = $po->items->map(function ($item) {
            return [
                'purchase_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->product_name,
                'variation_id' => $item->variation_id,
                'variation_name' => $item->variation?->variation_name,
                'quantity_ordered' => $item->quantity_ordered,
                'quantity_received' => $item->quantity_received,
                'quantity_pending' => $item->quantity_pending,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'purchase_order' => $po,
                'pending_items' => $pendingItems,
            ],
        ]);
    }

    /**
     * Get GRN summary
     * GET /api/procurement/goods-receipts/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $query = GoodsReceipt::query();

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('receipt_date', [$request->start_date, $request->end_date]);
        }

        $summary = [
            'total_receipts' => (clone $query)->count(),
            'full_receipts' => (clone $query)->where('receipt_status', 'full')->count(),
            'partial_receipts' => (clone $query)->where('receipt_status', 'partial')->count(),
            'damaged_receipts' => (clone $query)->where('receipt_status', 'damaged')->count(),
            'rejected_receipts' => (clone $query)->where('receipt_status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}