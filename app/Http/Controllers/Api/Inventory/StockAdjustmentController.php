<?php
// backend/app/Http/Controllers/Inventory/StockAdjustmentController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockAdjustmentItem;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Core\ActivityLog;
use App\Models\Hr\Employee;
use App\Support\EmployeeContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
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
     * List all stock adjustments
     * GET /api/inventory/adjustments
     */
    public function index(Request $request): JsonResponse
    {
        $context = $this->getUserContext($request);
        $storeId = $context['store_id'];
        $branchId = $context['branch_id'];

        if (!$this->hasGlobalAccess() && $storeId <= 0 && $branchId <= 0) {
            $perPage = $request->get('per_page', 15);
            $empty = StockAdjustment::query()->whereRaw('1 = 0')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $empty,
            ]);
        }

        $query = StockAdjustment::with(['branch', 'createdBy', 'approvedBy'])
            ->when($storeId > 0, fn($q) => $q->where('store_id', $storeId));

        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        // Filters
        if ($branchId <= 0 && $request->has('branch_id')) {
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
            // Generate adjustment number using datetime for uniqueness
            $number = 'ADJ-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

            // Create adjustment
            $adjustment = StockAdjustment::create([
                'adjustment_number' => $number,
                'store_id' => Auth::user()->store_id,
                'branch_id' => $validated['branch_id'],
                'type' => $validated['type'],
                'status' => 'draft',
                'reason' => $validated['reason'],
                'adjustment_date' => $validated['adjustment_date'],
                'created_by' => EmployeeContext::currentEmployeeId(),
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

            $this->recordLog(
                'inventory.stock_adjustment.created',
                "Created stock adjustment {$adjustment->adjustment_number}",
                $adjustment,
                ['status' => $adjustment->status]
            );

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
            $this->applyApprovedAdjustment($adjustment, $validated['approval_notes'] ?? null, EmployeeContext::currentEmployeeId());

            DB::commit();

            $this->recordLog(
                'inventory.stock_adjustment.approved',
                "Approved stock adjustment {$adjustment->adjustment_number}",
                $adjustment,
                ['status' => $adjustment->status]
            );

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

        $this->recordLog(
            'inventory.stock_adjustment.rejected',
            "Rejected stock adjustment {$adjustment->adjustment_number}",
            $adjustment,
            [
                'status' => $adjustment->status,
                'reason' => $validated['rejection_reason'],
            ]
        );

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

                $this->applyApprovedAdjustment($adjustment, 'Auto-approved (inventory + finance permissions)', EmployeeContext::currentEmployeeId());

                DB::commit();

                $this->recordLog(
                    'inventory.stock_adjustment.auto_approved',
                    "Auto-approved stock adjustment {$adjustment->adjustment_number}",
                    $adjustment,
                    ['status' => $adjustment->status]
                );

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

        $this->recordLog(
            'inventory.stock_adjustment.submitted',
            "Submitted stock adjustment {$adjustment->adjustment_number} for approval",
            $adjustment,
            ['status' => $adjustment->status]
        );

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

            // Create inventory transaction with unique datetime-based number
            $transactionNumber = 'TXN-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);

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

    private function recordLog(string $action, string $description, StockAdjustment $adjustment, array $meta = []): void
    {
        ActivityLog::record(
            $action,
            $description,
            array_merge([
                'adjustment_number' => $adjustment->adjustment_number,
                'branch_id' => $adjustment->branch_id,
                'type' => $adjustment->type,
                'status' => $adjustment->status,
            ], $meta),
            'inventory.stock_adjustment',
            (int) $adjustment->id
        );
    }

    protected function userHasPermissions(array $permissions, $user = null): bool
    {
        $user = $user ?? Auth::user();
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
