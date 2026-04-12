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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StockTransferController extends Controller
{
    private function userHasAnyPermission(array $permissionNames, int $storeId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        foreach ($permissionNames as $permission) {
            $normalized = (string) $permission;
            $aliases = array_values(array_unique([
                $normalized,
                Str::contains($normalized, '_') ? str_replace('_', '-', $normalized) : $normalized,
                Str::contains($normalized, '-') ? str_replace('-', '_', $normalized) : $normalized,
            ]));

            foreach ($aliases as $candidate) {
                if ($candidate && $user->hasPermissionTo($candidate, $storeId)) {
                    return true;
                }
            }
        }

        return false;
    }

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
        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);

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
            $settings = ProcurementSettings::where('store_id', $storeId)->first();

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
                'store_id' => $storeId,
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

            if ($user && $this->userHasAnyPermission(['inventory.transfers.approve'], $storeId)) {
                $transfer->load(['items.product']);

                foreach ($transfer->items as $item) {
                    $inventory = BranchInventory::where('branch_id', $transfer->from_branch_id)
                        ->where('product_id', $item->product_id)
                        ->where('variation_id', $item->variation_id)
                        ->first();

                    if (!$inventory || $inventory->quantity_available < $item->requested_quantity) {
                        throw new \Exception("Insufficient stock for {$item->product->product_name}");
                    }

                    $item->update(['approved_quantity' => $item->requested_quantity]);
                }

                $transfer->update([
                    'status' => 'sender_approved',
                    'sender_approved_by' => EmployeeContext::currentEmployeeId(),
                    'sender_approved_date' => now(),
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

            if ($transfer->status === 'sender_approved') {
                $this->recordLog(
                    'inventory.stock_transfer.auto_approved',
                    "Auto-approved stock transfer {$transfer->transfer_number}",
                    $transfer,
                    ['status' => $transfer->status]
                );
            }

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
        if (!$user || !$this->userHasAnyPermission(['inventory.transfers.approve'], (int) $user->store_id)) {
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
     * Send transfer to logistics
     * POST /api/inventory/transfers/{id}/send-to-logistics
     */
    public function sendToLogistics(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::with('items.product')->findOrFail($id);

        if (!in_array($transfer->status, ['receiver_acknowledge', 'receiver_acknowledged'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only receiver acknowledged transfers can be sent to logistics.',
            ], 422);
        }

        $transfer->update([
            'status' => 'in_transit',
            'shipped_by' => EmployeeContext::currentEmployeeId(),
            'shipped_date' => now(),
            'notes' => trim((string) ($transfer->notes ?? '') . "\nSent to logistics on " . now()->toDateTimeString()),
        ]);

        $this->recordLog(
            'inventory.stock_transfer.sent_to_logistics',
            "Sent stock transfer {$transfer->transfer_number} to logistics",
            $transfer,
            ['status' => $transfer->status]
        );

        return response()->json([
            'success' => true,
            'message' => 'Transfer sent to logistics successfully.',
            'data' => $transfer->fresh(['items.product']),
        ]);
    }

    /**
     * Create delivery assignment for logistics stock transfer
     * POST /api/inventory/transfers/{id}/create-delivery
     */
    public function createDelivery(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::with('items.product')->findOrFail($id);

        if ($transfer->status !== 'in_transit') {
            return response()->json([
                'success' => false,
                'message' => 'Delivery can only be created when transfer is in transit.',
            ], 422);
        }

        // Validation: prevent duplicate delivery assignment for the same transfer.
        if (!empty($transfer->driver_name) || !empty($transfer->vehicle_type) || !empty($transfer->tracking_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery already exists for this stock transfer.',
            ], 422);
        }

        $validated = $request->validate([
            'vehicle_type' => 'required|string|max:100',
            'driver_name' => 'required|string|max:100',
            'driver_contact' => 'required|string|max:50',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $extraNotes = trim((string) ($validated['notes'] ?? ''));
        $noteParts = array_filter([
            trim((string) ($transfer->notes ?? '')),
            'Delivery created by logistics on ' . now()->toDateTimeString(),
            $extraNotes !== '' ? $extraNotes : null,
            'Delivery fee: 0.00 (stock transfer)',
        ]);

        $transfer->update([
            'vehicle_type' => $validated['vehicle_type'],
            'driver_name' => $validated['driver_name'],
            'driver_contact' => $validated['driver_contact'],
            'tracking_number' => $validated['tracking_number'] ?? $transfer->tracking_number,
            'notes' => implode("\n", $noteParts),
            // No delivery charges for stock transfer logistics.
            'transfer_cost' => 0,
        ]);

        $this->recordLog(
            'inventory.stock_transfer.delivery_created',
            "Created logistics delivery for stock transfer {$transfer->transfer_number}",
            $transfer,
            [
                'status' => $transfer->status,
                'tracking_number' => $transfer->tracking_number,
                'delivery_fee' => 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Delivery created successfully (no delivery charge for stock transfers).',
            'data' => $transfer->fresh(['items.product']),
        ]);
    }

    /**
     * Add delivery log for stock transfer shipment overview
     * POST /api/inventory/transfers/{id}/delivery-log
     */
    public function addDeliveryLog(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        if (!in_array($transfer->status, ['in_transit', 'received'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery logs can only be added when transfer is In Transit or Received.',
            ], 422);
        }

        $validated = $request->validate([
            'event' => 'required|string|in:arrived_at_location,unloading_started,unloading_completed,delivery_delay,delivery_issue,received_by_branch,custom_note',
            'notes' => 'nullable|string|max:1000',
        ]);

        $actor = auth()->user();
        $actorName = trim((string) (($actor?->fname ?? '') . ' ' . ($actor?->lname ?? ''))) ?: 'Logistics';
        $timestamp = now()->toDateTimeString();

        $eventLabels = [
            'arrived_at_location' => 'Arrived at Location',
            'unloading_started' => 'Unloading Started',
            'unloading_completed' => 'Unloading Completed',
            'delivery_delay' => 'Delivery Delay',
            'delivery_issue' => 'Delivery Issue',
            'received_by_branch' => 'Received by Branch',
            'custom_note' => 'Custom Note',
        ];

        $event = (string) $validated['event'];
        $label = $eventLabels[$event] ?? 'Delivery Log';
        $notes = trim((string) ($validated['notes'] ?? ''));
        $message = $notes !== '' ? "{$label} - {$notes}" : $label;
        $safeNotes = str_replace(["\r", "\n", '|'], [' ', ' ', '/'], $notes);
        // LOG2 format keeps selected event key to ensure UI shows exact chosen event.
        $entry = sprintf(
            'LOG2|%s|%s|%s|%s',
            $timestamp,
            str_replace('|', '/', $actorName),
            str_replace('|', '/', $event),
            $safeNotes
        );

        $parts = array_filter([
            trim((string) ($transfer->notes ?? '')),
            $entry,
        ]);

        $transfer->update([
            'notes' => implode("\n", $parts),
        ]);

        $this->recordLog(
            'inventory.stock_transfer.delivery_log_added',
            "Added delivery log for stock transfer {$transfer->transfer_number}",
            $transfer,
            [
                'status' => $transfer->status,
                'message' => $message,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Delivery log recorded successfully.',
            'data' => $transfer->fresh(['items.product']),
        ], 201);
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

                $quantityBefore = $inventory->quantity_available;

                // Deduct stock
                $inventory->quantity_available -= $item->approved_quantity;
                // Keep on-hand synchronized to available as single operational stock value.
                $inventory->quantity_on_hand = $inventory->quantity_available;
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
                    'quantity_after' => $inventory->quantity_available,
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

        if (!$request->has('items') && $request->filled('items_json')) {
            $decoded = json_decode((string) $request->input('items_json'), true);
            if (is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.received_quantity' => 'required|integer|min:0',
            'items.*.damaged_quantity' => 'nullable|integer|min:0',
            'photos' => 'nullable|array',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,webp,bmp|max:10240',
            'notes' => 'nullable|string|max:5000',
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

                $quantityBefore = $inventory->quantity_available;

                // Add received stock
                $inventory->quantity_available += $itemData['received_quantity'];
                // Keep on-hand synchronized to available as single operational stock value.
                $inventory->quantity_on_hand = $inventory->quantity_available;
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
                    'quantity_after' => $inventory->quantity_available,
                    'related_branch_id' => $transfer->from_branch_id,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'created_by' => EmployeeContext::currentEmployeeId(),
                    'transaction_date' => now(),
                ]);
            }

            $podEntries = [];
            if ($request->hasFile('photos')) {
                foreach ((array) $request->file('photos') as $photo) {
                    if (!$photo) {
                        continue;
                    }
                    $path = $photo->store('inventory/stock-transfers/pod', 'public');
                    // Fallback for environments where public/storage is not a symlink.
                    // Mirror uploaded file to public/storage so browser URLs still resolve.
                    $publicMirrorPath = public_path('storage/' . $path);
                    $publicMirrorDir = dirname($publicMirrorPath);
                    if (!is_dir($publicMirrorDir)) {
                        @mkdir($publicMirrorDir, 0777, true);
                    }
                    $sourcePath = storage_path('app/public/' . $path);
                    if (is_file($sourcePath) && !is_file($publicMirrorPath)) {
                        @copy($sourcePath, $publicMirrorPath);
                    }
                    $podEntries[] = sprintf(
                        'POD|%s|%s',
                        Storage::disk('public')->url($path),
                        str_replace(['|', "\n", "\r"], ['/', ' ', ' '], (string) $photo->getClientOriginalName())
                    );
                }
            }

            $extraNotes = trim((string) ($validated['notes'] ?? ''));
            $noteParts = array_filter([
                trim((string) ($transfer->notes ?? '')),
                $extraNotes !== '' ? $extraNotes : null,
                ...$podEntries,
            ]);

            $transfer->update([
                'status' => 'received',
                'received_by' => EmployeeContext::currentEmployeeId(),
                'received_date' => now(),
                'notes' => implode("\n", $noteParts),
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
