<?php
// backend/app/Http/Controllers/Inventory/StockTransferController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockTransferItem;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\BranchDistance;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Procurement\Config\ProcurementSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * List all stock transfers
     * GET /api/inventory/transfers
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockTransfer::with([
            'fromBranch',
            'toBranch',
            'requestedBy'
        ])->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('from_branch_id')) {
            $query->where('from_branch_id', $request->from_branch_id);
        }

        if ($request->has('to_branch_id')) {
            $query->where('to_branch_id', $request->to_branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }

    /**
     * Show single transfer
     * GET /api/inventory/transfers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $transfer = StockTransfer::with([
            'fromBranch',
            'toBranch',
            'items.product',
            'items.variation',
            'requestedBy',
            'senderApprovedBy',
            'receiverAcknowledgedBy',
            'financeApprovedBy',
            'shippedBy',
            'receivedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transfer,
        ]);
    }

    /**
     * Create new stock transfer
     * POST /api/inventory/transfers
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_branch_id' => 'required|exists:branches,id|different:to_branch_id',
            'to_branch_id' => 'required|exists:branches,id',
            'reason' => 'required|string',
            'expected_delivery_date' => 'required|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.requested_quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Get procurement settings
            $settings = ProcurementSettings::where('store_id', auth()->user()->store_id)->first();

            // Generate transfer number
            $lastTransfer = StockTransfer::latest()->first();
            $number = 'TRF-' . date('Y') . '-' . str_pad(($lastTransfer?->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            // Calculate goods value and distance
            $goodsValue = 0;
            foreach ($validated['items'] as $item) {
                $inventory = BranchInventory::where('branch_id', $validated['from_branch_id'])
                    ->where('product_id', $item['product_id'])
                    ->where('variation_id', $item['variation_id'] ?? null)
                    ->first();

                $goodsValue += ($inventory?->average_cost ?? 0) * $item['requested_quantity'];
            }

            // Get distance between branches
            $distance = BranchDistance::getDistance($validated['from_branch_id'], $validated['to_branch_id']);

            // Calculate transfer cost
            $transferCost = $settings?->calculateTransferCost($distance, $goodsValue) ?? 0;

            // Create transfer
            $transfer = StockTransfer::create([
                'transfer_number' => $number,
                'store_id' => auth()->user()->store_id,
                'from_branch_id' => $validated['from_branch_id'],
                'to_branch_id' => $validated['to_branch_id'],
                'status' => 'requested',
                'approval_policy_used' => $settings?->transfer_approval_policy ?? 'sender_only',
                'cost_method' => $settings?->transfer_cost_method ?? 'none',
                'distance_km' => $distance,
                'transfer_cost' => $transferCost,
                'goods_value' => $goodsValue,
                'cost_calculation_notes' => "Calculated using {$settings?->transfer_cost_method} method",
                'reason' => $validated['reason'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'requested_by' => auth()->id(),
                'requested_date' => now(),
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                $inventory = BranchInventory::where('branch_id', $validated['from_branch_id'])
                    ->where('product_id', $item['product_id'])
                    ->where('variation_id', $item['variation_id'] ?? null)
                    ->first();

                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'requested_quantity' => $item['requested_quantity'],
                    'unit_value' => $inventory?->average_cost ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock transfer created successfully',
                'data' => $transfer->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock transfer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve transfer (sender)
     * POST /api/inventory/transfers/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);

        if ($transfer->status !== 'requested') {
            return response()->json([
                'success' => false,
                'message' => 'Only requested transfers can be approved',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Check stock availability
            foreach ($transfer->items as $item) {
                $inventory = BranchInventory::where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->where('variation_id', $item->variation_id)
                    ->first();

                if (!$inventory || $inventory->quantity_available < $item->requested_quantity) {
                    throw new \Exception("Insufficient stock for {$item->product->product_name}");
                }

                // Update approved quantity
                $item->update(['approved_quantity' => $item->requested_quantity]);
            }

            $transfer->update([
                'status' => 'sender_approved',
                'sender_approved_by' => auth()->id(),
                'sender_approved_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer approved successfully',
                'data' => $transfer->fresh(['items.product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ship transfer
     * POST /api/inventory/transfers/{id}/ship
     */
    public function ship(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);

        $validated = $request->validate([
            'vehicle_type' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'driver_contact' => 'nullable|string',
            'tracking_number' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Deduct from source branch
            foreach ($transfer->items as $item) {
                $inventory = BranchInventory::where('branch_id', $transfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->where('variation_id', $item->variation_id)
                    ->firstOrFail();

                $quantityBefore = $inventory->quantity_on_hand;

                // Deduct stock
                $inventory->quantity_on_hand -= $item->approved_quantity;
                $inventory->quantity_available -= $item->approved_quantity;
                $inventory->save();
                $inventory->updateStockStatus();
                $inventory->calculateTotalValue();

                // Create transaction
                $transactionNumber = 'TXN-' . date('Y') . '-' . str_pad(InventoryTransaction::count() + 1, 5, '0', STR_PAD_LEFT);

                InventoryTransaction::create([
                    'transaction_number' => $transactionNumber,
                    'store_id' => $transfer->store_id,
                    'branch_id' => $transfer->from_branch_id,
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'transaction_type' => 'transfer_out',
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => -$item->approved_quantity,
                    'quantity_after' => $inventory->quantity_on_hand,
                    'related_branch_id' => $transfer->to_branch_id,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'created_by' => auth()->id(),
                    'transaction_date' => now(),
                ]);

                // Update shipped quantity
                $item->update(['shipped_quantity' => $item->approved_quantity]);
            }

            $transfer->update([
                'status' => 'in_transit',
                'shipped_by' => auth()->id(),
                'shipped_date' => now(),
                'vehicle_type' => $validated['vehicle_type'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_contact' => $validated['driver_contact'] ?? null,
                'tracking_number' => $validated['tracking_number'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer shipped successfully',
                'data' => $transfer->fresh(['items.product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to ship transfer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive transfer
     * POST /api/inventory/transfers/{id}/receive
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);

        if ($transfer->status !== 'in_transit') {
            return response()->json([
                'success' => false,
                'message' => 'Only in-transit transfers can be received',
            ], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.received_quantity' => 'required|integer|min:0',
            'items.*.damaged_quantity' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                $item = $transfer->items->firstWhere('id', $itemData['id']);

                // Update item
                $item->update([
                    'received_quantity' => $itemData['received_quantity'],
                    'damaged_quantity' => $itemData['damaged_quantity'] ?? 0,
                ]);

                // Add to destination branch inventory
                $inventory = BranchInventory::firstOrCreate(
                    [
                        'branch_id' => $transfer->to_branch_id,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                    ],
                    [
                        'store_id' => $transfer->store_id,
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
                $inventory->quantity_on_hand += $itemData['received_quantity'];
                $inventory->quantity_available += $itemData['received_quantity'];
                $inventory->quantity_damaged += ($itemData['damaged_quantity'] ?? 0);
                $inventory->save();
                $inventory->updateStockStatus();
                $inventory->calculateTotalValue();

                // Create transaction
                $transactionNumber = 'TXN-' . date('Y') . '-' . str_pad(InventoryTransaction::count() + 1, 5, '0', STR_PAD_LEFT);

                InventoryTransaction::create([
                    'transaction_number' => $transactionNumber,
                    'store_id' => $transfer->store_id,
                    'branch_id' => $transfer->to_branch_id,
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'transaction_type' => 'transfer_in',
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => $itemData['received_quantity'],
                    'quantity_after' => $inventory->quantity_on_hand,
                    'related_branch_id' => $transfer->from_branch_id,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'created_by' => auth()->id(),
                    'transaction_date' => now(),
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => auth()->id(),
                'received_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer received successfully',
                'data' => $transfer->fresh(['items.product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive transfer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel transfer
     * POST /api/inventory/transfers/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (!in_array($transfer->status, ['requested', 'sender_approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only requested or approved transfers can be cancelled',
            ], 422);
        }

        $transfer->update([
            'status' => 'cancelled',
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transfer cancelled successfully',
        ]);
    }
}