<?php
// backend/app/Http/Controllers/Inventory/StockAlertController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockAlert;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\ReorderRule;
use App\Models\Hr\Employee;
use App\Support\EmployeeContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockAlertController extends Controller
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
     * List all stock alerts
     * GET /api/inventory/alerts
     */
    public function index(Request $request): JsonResponse
    {
        $context = $this->getUserContext($request);
        $storeId = $context['store_id'];
        $branchId = $context['branch_id'];

        if (!$this->hasGlobalAccess() && $storeId <= 0 && $branchId <= 0) {
            $perPage = $request->get('per_page', 15);
            $empty = StockAlert::query()->whereRaw('1 = 0')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $empty,
            ]);
        }

        $query = StockAlert::with([
            'branch',
            'product',
            'variation',
            'acknowledgedBy'
        ]);

        if ($storeId > 0) {
            $query->whereHas('branch', fn($q) => $q->where('store_id', $storeId));
        }
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

        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        // Default to active alerts
        if (!$request->has('status')) {
            $query->active();
        }

        $alerts = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Show single alert
     * GET /api/inventory/alerts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $alert = StockAlert::with([
            'branch',
            'product',
            'variation',
            'acknowledgedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $alert,
        ]);
    }

    /**
     * Get alert summary/counts
     * GET /api/inventory/alerts/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $context = $this->getUserContext($request);
        $storeId = $context['store_id'];
        $branchId = $context['branch_id'];

        if (!$this->hasGlobalAccess() && $storeId <= 0 && $branchId <= 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_active' => 0,
                    'low_stock' => 0,
                    'out_of_stock' => 0,
                    'overstock' => 0,
                    'reorder_needed' => 0,
                    'acknowledged' => 0,
                    'resolved' => 0,
                ],
            ]);
        }

        $query = StockAlert::query();

        if ($storeId > 0) {
            $query->whereHas('branch', fn($q) => $q->where('store_id', $storeId));
        }

        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        } elseif ($request->get('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        $summary = [
            'total_active' => (clone $query)->active()->count(),
            'low_stock' => (clone $query)->active()->lowStock()->count(),
            'out_of_stock' => (clone $query)->active()->outOfStock()->count(),
            'overstock' => (clone $query)->active()->where('alert_type', 'overstock')->count(),
            'reorder_needed' => (clone $query)->active()->where('alert_type', 'reorder_needed')->count(),
            'acknowledged' => (clone $query)->where('status', 'acknowledged')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Acknowledge alert
     * POST /api/inventory/alerts/{id}/acknowledge
     */
    public function acknowledge(int $id): JsonResponse
    {
        $alert = StockAlert::findOrFail($id);

        if ($alert->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active alerts can be acknowledged',
            ], 422);
        }

        $alert->acknowledge(EmployeeContext::currentEmployeeId());

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully',
            'data' => $alert->fresh(),
        ]);
    }

    /**
     * Resolve alert
     * POST /api/inventory/alerts/{id}/resolve
     */
    public function resolve(int $id): JsonResponse
    {
        $alert = StockAlert::with('product', 'variation')->findOrFail($id);

        // Check current inventory status
        $inventory = BranchInventory::where('branch_id', $alert->branch_id)
            ->where('product_id', $alert->product_id)
            ->where('variation_id', $alert->variation_id)
            ->first();

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory record not found',
            ], 404);
        }

        // Check if alert condition is still valid
        $canResolve = match($alert->alert_type) {
            'low_stock' => $inventory->quantity_available > $inventory->effectiveReorderPoint(),
            'out_of_stock' => $inventory->quantity_available > 0,
            'overstock' => $inventory->quantity_on_hand <= ($inventory->maximum_stock ?? PHP_INT_MAX),
            'reorder_needed' => $inventory->quantity_incoming > 0,
            default => false,
        };

        if (!$canResolve) {
            return response()->json([
                'success' => false,
                'message' => 'Alert condition has not been resolved yet',
                'current_quantity' => $inventory->quantity_available,
                'reorder_point' => $inventory->effectiveReorderPoint(),
            ], 422);
        }

        $alert->resolve();

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully',
            'data' => $alert->fresh(),
        ]);
    }

    /**
     * Bulk acknowledge alerts
     * POST /api/inventory/alerts/bulk-acknowledge
     */
    public function bulkAcknowledge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'exists:stock_alerts,id',
        ]);

        $updated = StockAlert::whereIn('id', $validated['alert_ids'])
            ->where('status', 'active')
            ->update([
                'status' => 'acknowledged',
                'acknowledged_by' => EmployeeContext::currentEmployeeId(),
                'acknowledged_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} alerts acknowledged successfully",
        ]);
    }

    /**
     * Bulk resolve alerts
     * POST /api/inventory/alerts/bulk-resolve
     */
    public function bulkResolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'exists:stock_alerts,id',
        ]);

        $updated = StockAlert::whereIn('id', $validated['alert_ids'])
            ->update(['status' => 'resolved']);

        return response()->json([
            'success' => true,
            'message' => "{$updated} alerts resolved successfully",
        ]);
    }

    /**
     * Generate alerts based on current inventory
     * POST /api/inventory/alerts/generate
     */
    public function generateAlerts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branchId = $validated['branch_id'];
        $alertsCreated = 0;

        // Get all inventory items for the branch
        $inventoryItems = BranchInventory::where('branch_id', $branchId)->get();
        $productIds = $inventoryItems->pluck('product_id')->filter()->unique()->values()->all();
        $rulesByProduct = ReorderRule::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('product_id');

        foreach ($inventoryItems as $item) {
            $rule = $rulesByProduct->get((int) $item->product_id);
            $reorderPoint = (float) ($rule?->reorder_point ?? $item->reorder_point ?? 0);
            $reorderQty = (float) ($rule?->reorder_quantity ?? $item->reorder_quantity ?? 0);

            // Check for low stock
            if ($item->quantity_available > 0 && (float) $item->quantity_available <= $reorderPoint) {
                $existing = StockAlert::where('branch_id', $branchId)
                    ->where('product_id', $item->product_id)
                    ->where('variation_id', $item->variation_id)
                    ->where('alert_type', 'low_stock')
                    ->where('status', 'active')
                    ->first();

                if (!$existing) {
                    StockAlert::create([
                        'branch_id' => $branchId,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'alert_type' => 'low_stock',
                        'current_quantity' => $item->quantity_available,
                        'reorder_point' => $reorderPoint,
                        'recommended_order_quantity' => $reorderQty,
                        'status' => 'active',
                    ]);
                    $alertsCreated++;
                }
            }

            // Check for out of stock
            if ($item->isOutOfStock()) {
                $existing = StockAlert::where('branch_id', $branchId)
                    ->where('product_id', $item->product_id)
                    ->where('variation_id', $item->variation_id)
                    ->where('alert_type', 'out_of_stock')
                    ->where('status', 'active')
                    ->first();

                if (!$existing) {
                    StockAlert::create([
                        'branch_id' => $branchId,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'alert_type' => 'out_of_stock',
                        'current_quantity' => $item->quantity_available,
                        'reorder_point' => $reorderPoint,
                        'recommended_order_quantity' => $reorderQty,
                        'status' => 'active',
                    ]);
                    $alertsCreated++;
                }
            }

            // Check for overstock
            if ($item->maximum_stock && $item->quantity_on_hand > $item->maximum_stock) {
                $existing = StockAlert::where('branch_id', $branchId)
                    ->where('product_id', $item->product_id)
                    ->where('variation_id', $item->variation_id)
                    ->where('alert_type', 'overstock')
                    ->where('status', 'active')
                    ->first();

                if (!$existing) {
                    StockAlert::create([
                        'branch_id' => $branchId,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'alert_type' => 'overstock',
                        'current_quantity' => $item->quantity_on_hand,
                        'status' => 'active',
                    ]);
                    $alertsCreated++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$alertsCreated} new alerts generated",
            'alerts_created' => $alertsCreated,
        ]);
    }

    /**
     * Delete/dismiss alert
     * DELETE /api/inventory/alerts/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $alert = StockAlert::findOrFail($id);
        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert deleted successfully',
        ]);
    }
}
