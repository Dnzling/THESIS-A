<?php
// backend/app/Http/Controllers/Inventory/StockTransferController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockTransferItem;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\BranchDistance;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Core\ActivityLog;
use App\Models\Hr\Employee;
use App\Models\Procurement\Config\ProcurementSettings;
use App\Support\EmployeeContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    private function hasGlobalAccess(): bool
    {
        $roleName = strtolower(auth()->user()?->role?->name ?? '');
        return in_array($roleName, ['super_admin', 'owner'], true);
    }

    private function getUserContext(Request $request): array
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?? 0);
        $branchId = (int) ($user?->branch_id ?? 0);

        if ($user && ($storeId === 0 || $branchId === 0)) {
            $employee = Employee::query()
                ->where('user_id', $user->id)
                ->first(['store_id', 'branch_id']);

            $storeId = $storeId ?: (int) ($employee?->store_id ?? 0);
            $branchId = $branchId ?: (int) ($employee?->branch_id ?? 0);
        }

        return [
            'store_id' => $storeId,
            'branch_id' => $branchId,
        ];
    }

    /**
     * List all stock transfers
     * GET /api/inventory/transfers
     */
    public function index(Request $request): JsonResponse
    {
        $context = $this->getUserContext($request);
        $storeId = $context['store_id'];
        $branchId = $context['branch_id'];

        if (!$this->hasGlobalAccess() && $storeId <= 0 && $branchId <= 0) {
            $perPage = $request->get('per_page', 15);
            $empty = StockTransfer::query()->whereRaw('1 = 0')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $empty,
            ]);
        }

        $query = StockTransfer::with([
            'fromBranch',
            'toBranch',
            'requestedBy'
        ]);

        if ($storeId > 0) {
            $query->where('store_id', $storeId);
        }
        if ($branchId > 0) {
            $query->where(function ($builder) use ($branchId) {
                $builder->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            });
        }

        // Filters
        if ($branchId <= 0 && $request->has('from_branch_id')) {
            $query->where('from_branch_id', $request->from_branch_id);
        }

        if ($branchId <= 0 && $request->has('to_branch_id')) {
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
            $settings = ProcurementSettings::where('store_id', Auth::user()->store_id)->first();

            // Generate transfer number using datetime for uniqueness
            $number = 'TRF-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

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
                'store_id' => Auth::user()->store_id,
                'from_branch_id' => $validated['from_branch_id'],
                'to_branch_id' => $validated['to_branch_id'],
                // Keep persisted status compatible with existing enum/schema.
                // UI/UX treats this as "Pending Approval".
                'status' => 'requested',
                'approval_policy_used' => $settings?->transfer_approval_policy ?? 'sender_only',
                'cost_method' => $settings?->transfer_cost_method ?? 'none',
                'distance_km' => $distance,
                'transfer_cost' => $transferCost,
                'goods_value' => $goodsValue,
                'cost_calculation_notes' => "Calculated using {$settings?->transfer_cost_method} method",
                'reason' => $validated['reason'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'requested_by' => EmployeeContext::currentEmployeeId(),
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

            $this->recordLog(
                'inventory.stock_transfer.created',
                "Created stock transfer {$transfer->transfer_number}",
                $transfer,
                [
                    'status' => $transfer->status,
                    'from_branch_id' => $transfer->from_branch_id,
                    'to_branch_id' => $transfer->to_branch_id,
                ]
            );

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
        $user = Auth::user();
        if (!$user || !$user->hasPermissionTo('inventory.transfers.approve', (int) $user->store_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Approval permission is required.',
            ], 403);
        }

        $transfer = StockTransfer::with('items')->findOrFail($id);

        if (!in_array($transfer->status, ['requested', 'pending_approval'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending transfers can be approved',
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
                'sender_approved_by' => EmployeeContext::currentEmployeeId(),
                'sender_approved_date' => now(),
            ]);

            DB::commit();

            $this->recordLog(
                'inventory.stock_transfer.approved',
                "Approved stock transfer {$transfer->transfer_number}",
                $transfer,
                ['status' => $transfer->status]
            );

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

                // Create transaction with unique datetime-based number
                $transactionNumber = 'TXN-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

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
                    'created_by' => EmployeeContext::currentEmployeeId(),
                    'transaction_date' => now(),
                ]);

                // Update shipped quantity
                $item->update(['shipped_quantity' => $item->approved_quantity]);
            }

            $transfer->update([
                'status' => 'in_transit',
                'shipped_by' => EmployeeContext::currentEmployeeId(),
                'shipped_date' => now(),
                'vehicle_type' => $validated['vehicle_type'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_contact' => $validated['driver_contact'] ?? null,
                'tracking_number' => $validated['tracking_number'] ?? null,
            ]);

            DB::commit();

            $this->recordLog(
                'inventory.stock_transfer.shipped',
                "Shipped stock transfer {$transfer->transfer_number}",
                $transfer,
                [
                    'status' => $transfer->status,
                    'tracking_number' => $transfer->tracking_number,
                ]
            );

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

                // Create transaction with unique datetime-based number
                $transactionNumber = 'TXN-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

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
                    'created_by' => EmployeeContext::currentEmployeeId(),
                    'transaction_date' => now(),
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => EmployeeContext::currentEmployeeId(),
                'received_date' => now(),
            ]);

            DB::commit();

            $this->recordLog(
                'inventory.stock_transfer.received',
                "Received stock transfer {$transfer->transfer_number}",
                $transfer,
                ['status' => $transfer->status]
            );

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

        $this->recordLog(
            'inventory.stock_transfer.cancelled',
            "Cancelled stock transfer {$transfer->transfer_number}",
            $transfer,
            [
                'status' => $transfer->status,
                'reason' => $validated['reason'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Transfer cancelled successfully',
        ]);
    }

    private function recordLog(string $action, string $description, StockTransfer $transfer, array $meta = []): void
    {
        ActivityLog::record(
            $action,
            $description,
            array_merge([
                'transfer_number' => $transfer->transfer_number,
                'branch_id' => $transfer->from_branch_id,
                'from_branch_id' => $transfer->from_branch_id,
                'to_branch_id' => $transfer->to_branch_id,
            ], $meta),
            'inventory.stock_transfer',
            (int) $transfer->id
        );
    }
}
