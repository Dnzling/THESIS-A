<?php
// backend/app/Http/Controllers/Inventory/StockAdjustmentController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockAdjustmentItem;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    /**
     * List all stock adjustments
     * GET /api/inventory/adjustments
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockAdjustment::with(['branch', 'createdBy', 'approvedBy'])
            ->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $adjustments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $adjustments,
        ]);
    }

    /**
     * Show single adjustment
     * GET /api/inventory/adjustments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $adjustment = StockAdjustment::with([
            'branch',
            'items.product',
            'items.variation',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $adjustment,
        ]);
    }

    /**
     * Create new stock adjustment
     * POST /api/inventory/adjustments
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:physical_count,cycle_count,spot_check,damage,loss,found,correction,writeoff',
            'reason' => 'required|string',
            'adjustment_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.system_quantity' => 'required|integer',
            'items.*.actual_quantity' => 'required|integer',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate adjustment number
            $lastAdjustment = StockAdjustment::latest()->first();
            $number = 'ADJ-' . date('Y') . '-' . str_pad(($lastAdjustment?->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            // Create adjustment
            $adjustment = StockAdjustment::create([
                'adjustment_number' => $number,
                'store_id' => auth()->user()->store_id,
                'branch_id' => $validated['branch_id'],
                'type' => $validated['type'],
                'status' => 'draft',
                'reason' => $validated['reason'],
                'adjustment_date' => $validated['adjustment_date'],
                'created_by' => auth()->id(),
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                $difference = $item['actual_quantity'] - $item['system_quantity'];

                // Get unit cost from inventory
                $inventory = BranchInventory::where('branch_id', $validated['branch_id'])
                    ->where('product_id', $item['product_id'])
                    ->where('variation_id', $item['variation_id'] ?? null)
                    ->first();

                $unitCost = $inventory?->average_cost ?? 0;
                $valueDifference = $difference * $unitCost;

                StockAdjustmentItem::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'system_quantity' => $item['system_quantity'],
                    'actual_quantity' => $item['actual_quantity'],
                    'difference' => $difference,
                    'unit_cost' => $unitCost,
                    'value_difference' => $valueDifference,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment created successfully',
                'data' => $adjustment->load('items.product'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock adjustment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve stock adjustment
     * POST /api/inventory/adjustments/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $adjustment = StockAdjustment::with('items')->findOrFail($id);

        if ($adjustment->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending adjustments can be approved',
            ], 422);
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $this->applyApprovedAdjustment($adjustment, $validated['approval_notes'] ?? null, auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment approved and applied successfully',
                'data' => $adjustment->fresh(['items.product']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve adjustment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject stock adjustment
     * POST /api/inventory/adjustments/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $adjustment = StockAdjustment::findOrFail($id);

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $adjustment->update([
            'status' => 'rejected',
            'approval_notes' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment rejected',
        ]);
    }

    /**
     * Submit for approval
     * POST /api/inventory/adjustments/{id}/submit
     */
    public function submit(int $id): JsonResponse
    {
        $adjustment = StockAdjustment::findOrFail($id);

        if ($adjustment->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft adjustments can be submitted',
            ], 422);
        }

        $shouldAutoApprove = $this->userHasPermissions([
            'inventory.adjustments.approve',
            'finance.approvals.approve',
        ]);

        if ($shouldAutoApprove) {
            DB::beginTransaction();
            try {
                $adjustment->update(['status' => 'pending_approval']);
                $adjustment->load('items');

                $this->applyApprovedAdjustment($adjustment, 'Auto-approved (inventory + finance permissions)', auth()->id());

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Stock adjustment auto-approved and applied successfully',
                    'data' => $adjustment->fresh(['items.product']),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to auto-approve adjustment',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        $adjustment->update(['status' => 'pending_approval']);

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment submitted for approval',
        ]);
    }

    private function applyApprovedAdjustment(StockAdjustment $adjustment, ?string $notes, int $approvedBy): void
    {
        // Approve adjustment
        $adjustment->approve($approvedBy, $notes);

        // Apply to inventory
        foreach ($adjustment->items as $item) {
            $inventory = BranchInventory::where('branch_id', $adjustment->branch_id)
                ->where('product_id', $item->product_id)
                ->where('variation_id', $item->variation_id)
                ->firstOrFail();

            $quantityBefore = $inventory->quantity_on_hand;

            // Update inventory
            $inventory->quantity_on_hand = $item->actual_quantity;
            $inventory->quantity_available = $item->actual_quantity - $inventory->quantity_reserved;
            $inventory->last_stock_count_date = $adjustment->adjustment_date;
            $inventory->last_counted_quantity = $item->actual_quantity;
            $inventory->last_counted_by = $approvedBy;
            $inventory->save();

            $inventory->updateStockStatus();
            $inventory->calculateTotalValue();

            // Create inventory transaction
            $transactionNumber = 'TXN-' . date('Y') . '-' . str_pad(InventoryTransaction::count() + 1, 5, '0', STR_PAD_LEFT);

            InventoryTransaction::create([
                'transaction_number' => $transactionNumber,
                'store_id' => $adjustment->store_id,
                'branch_id' => $adjustment->branch_id,
                'product_id' => $item->product_id,
                'variation_id' => $item->variation_id,
                'transaction_type' => 'adjustment',
                'quantity_before' => $quantityBefore,
                'quantity_change' => $item->difference,
                'quantity_after' => $item->actual_quantity,
                'reference_type' => 'stock_adjustment',
                'reference_id' => $adjustment->id,
                'notes' => $adjustment->reason,
                'unit_cost' => $item->unit_cost,
                'total_value' => $item->value_difference,
                'created_by' => $approvedBy,
                'transaction_date' => now(),
            ]);
        }

        $adjustment->update(['status' => 'applied']);
    }

    protected function userHasPermissions(array $permissions, $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user || !$user->role_id) {
            return false;
        }

        $rolePermissions = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $user->role_id)
            ->whereIn('permissions.name', $permissions)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->pluck('permissions.name')
            ->toArray();

        $userGrants = DB::table('user_permissions')
            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
            ->where('user_permissions.user_id', $user->id)
            ->where('user_permissions.type', 'grant')
            ->whereIn('permissions.name', $permissions)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->pluck('permissions.name')
            ->toArray();

        $userRevokes = DB::table('user_permissions')
            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
            ->where('user_permissions.user_id', $user->id)
            ->where('user_permissions.type', 'revoke')
            ->whereIn('permissions.name', $permissions)
            ->pluck('permissions.name')
            ->toArray();

        $allPermissions = array_merge($rolePermissions, $userGrants);
        $finalPermissions = array_diff($allPermissions, $userRevokes);

        return count(array_diff($permissions, $finalPermissions)) === 0;
    }
}
