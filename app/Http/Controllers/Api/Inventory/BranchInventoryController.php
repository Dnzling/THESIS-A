<?php
// backend/app/Http/Controllers/Inventory/BranchInventoryController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Models\Core\ActivityLog;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\ReorderRule;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BranchInventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AlertService $alertService
    ) {
    }

    /**
     * Get the authenticated user's branch ID
     */
    private function getUserBranchId(): int
    {
        return auth()->user()->branch_id;
    }

    /**
     * Get the authenticated user's context (store & branch)
     */
    private function getUserContext(): array
    {
        return [
            'store_id' => auth()->user()->store_id,
            'branch_id' => auth()->user()->branch_id,
        ];
    }

    /**
     * Display inventory for the authenticated user's branch
     * GET /api/inventory
     */
    public function index(Request $request, ?int $branchId = null): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            $user = auth()->user();
            $targetBranchId = (int) ($branchId ?? $context['branch_id']);

            if ($targetBranchId !== (int) $context['branch_id']) {
                $canViewAll = $user?->hasPermissionTo('inventory.branch_inventory.view_all', (int) $context['store_id']) ?? false;
                if (!$canViewAll) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to view other branch inventory',
                    ], 403);
                }
            }
            
            $query = BranchInventory::with(['product.suppliers', 'variation', 'branch'])
                ->where('store_id', $context['store_id'])
                ->where('branch_id', $targetBranchId);

            // Filters
            if ($request->has('stock_status')) {
                $query->where('stock_status', $request->stock_status);
            }

            if ($request->has('low_stock')) {
                $query->lowStock();
            }

            if ($request->has('out_of_stock')) {
                $query->outOfStock();
            }

            if ($request->has('warehouse_section')) {
                $query->where('warehouse_section', $request->warehouse_section);
            }

            if ($request->has('product_type')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('product_type', $request->product_type);
                });
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($builder) use ($search) {
                    $builder->whereHas('product', function ($q) use ($search) {
                        $q->where('product_name', 'LIKE', "%{$search}%")
                            ->orWhere('sku', 'LIKE', "%{$search}%");
                    })->orWhereHas('variation', function ($q) use ($search) {
                        $q->where('variation_name', 'LIKE', "%{$search}%")
                            ->orWhere('variation_sku', 'LIKE', "%{$search}%")
                            ->orWhere('color', 'LIKE', "%{$search}%")
                            ->orWhere('size', 'LIKE', "%{$search}%")
                            ->orWhere('material', 'LIKE', "%{$search}%");
                    });
                });
            }

            // Sorting
            $sortBy = (string) $request->get('sort_by', 'created_at');
            $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = [
                'created_at',
                'updated_at',
                'quantity_on_hand',
                'quantity_available',
                'stock_status',
                'reorder_point',
                'reorder_quantity',
            ];
            if (!in_array($sortBy, $allowedSorts, true)) {
                $sortBy = 'created_at';
            }
            $query->orderBy($sortBy, $sortOrder);

            $inventory = $query->paginate($request->get('per_page', 15));

            $itemsRaw = collect($inventory->items());
            $productIds = $itemsRaw->pluck('product_id')->filter()->unique()->values()->all();
            $rulesByProduct = ReorderRule::query()
                ->where('branch_id', $targetBranchId)
                ->whereIn('product_id', $productIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('product_id');

            $items = $itemsRaw->map(function (BranchInventory $row) use ($rulesByProduct) {
                $rule = $rulesByProduct->get((int) $row->product_id);
                $row->reorder_point = $rule?->reorder_point ?? $row->reorder_point ?? 0;
                $row->reorder_quantity = $rule?->reorder_quantity ?? $row->reorder_quantity ?? 0;

                // Ensure UI sees correct status even if stock_status wasn't recalculated after rule changes
                if ((int) $row->quantity_available <= 0) {
                    $row->stock_status = 'out_of_stock';
                } elseif ((float) $row->quantity_available <= (float) $row->reorder_point) {
                    $row->stock_status = 'low_stock';
                } else {
                    $row->stock_status = 'in_stock';
                }

                return $row;
            })->values();

            return response()->json([
                'success' => true,
                'data' => $items,
                'meta' => [
                    'total' => $inventory->total(),
                    'per_page' => $inventory->perPage(),
                    'page' => $inventory->currentPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display a specific inventory item (must belong to user's branch)
     * GET /api/inventory/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $inventory = BranchInventory::with([
                'product.suppliers',
                'variation',
                'branch',
                'store',
            ])
            ->where('store_id', $context['store_id'])
            ->where('branch_id', $context['branch_id'])
            ->findOrFail($id);

            $rule = ReorderRule::query()
                ->where('branch_id', (int) $inventory->branch_id)
                ->where('product_id', (int) $inventory->product_id)
                ->where('is_active', true)
                ->first();
            $inventory->reorder_point = $rule?->reorder_point ?? $inventory->reorder_point ?? 0;
            $inventory->reorder_quantity = $rule?->reorder_quantity ?? $inventory->reorder_quantity ?? 0;

            return response()->json([
                'success' => true,
                'data' => $inventory,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory record not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create new inventory record (automatically uses user's branch)
     * POST /api/inventory
     */
    public function store(StoreInventoryRequest $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            $validated = $request->validated();

            $reorderPoint = array_key_exists('reorder_point', $validated) ? $validated['reorder_point'] : null;
            $reorderQuantity = array_key_exists('reorder_quantity', $validated) ? $validated['reorder_quantity'] : null;
            unset($validated['reorder_point'], $validated['reorder_quantity']);
            
            // Add branch and store context
            $validated['store_id'] = $context['store_id'];
            $validated['branch_id'] = $context['branch_id'];
            $validated['quantity_on_hand'] = 0;
            $validated['quantity_available'] = 0;
            $validated['stock_status'] = 'out_of_stock';

            // Check if inventory record already exists
            $existingInventory = BranchInventory::where('branch_id', $context['branch_id'])
                ->where('product_id', $validated['product_id'])
                ->when(isset($validated['variation_id']), function ($query) use ($validated) {
                    return $query->where('variation_id', $validated['variation_id']);
                }, function ($query) {
                    return $query->whereNull('variation_id');
                })
                ->first();

            if ($existingInventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory record already exists for this product in your branch',
                ], 422);
            }

            $inventory = BranchInventory::create($validated);

            // Reorder settings live in reorder_rules (primary). Create the rule if missing.
            ReorderRule::query()->firstOrCreate(
                [
                    'product_id' => (int) $inventory->product_id,
                    'branch_id' => (int) $inventory->branch_id,
                ],
                [
                    'rule_type' => 'manual',
                    'trigger_type' => 'reorder_point',
                    'basis_type' => 'reorder_point',
                    'reorder_point' => $reorderPoint ?? 10,
                    'reorder_quantity' => $reorderQuantity ?? 10,
                    'priority' => 'medium',
                    'auto_generate_po' => false,
                    'is_active' => true,
                ]
            );

            // Generate alerts if needed
            $this->alertService->generateAlerts($context['store_id'], $context['branch_id']);

            $this->recordLog(
                'inventory.branch_inventory.created',
                'Created branch inventory record',
                $inventory
            );

            return response()->json([
                'success' => true,
                'message' => 'Inventory record created successfully',
                'data' => tap($inventory->load(['product', 'variation']), function (BranchInventory $row) {
                    $rule = ReorderRule::query()
                        ->where('branch_id', (int) $row->branch_id)
                        ->where('product_id', (int) $row->product_id)
                        ->where('is_active', true)
                        ->first();
                    $row->reorder_point = $rule?->reorder_point ?? $row->reorder_point ?? 0;
                    $row->reorder_quantity = $rule?->reorder_quantity ?? $row->reorder_quantity ?? 0;
                }),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update inventory settings (must belong to user's branch)
     * PUT /api/inventory/{id}
     */
    public function update(int $id, StoreInventoryRequest $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $inventory = BranchInventory::where('store_id', $context['store_id'])
                ->where('branch_id', $context['branch_id'])
                ->findOrFail($id);

            $validated = $request->validated();

            $reorderPoint = array_key_exists('reorder_point', $validated) ? $validated['reorder_point'] : null;
            $reorderQuantity = array_key_exists('reorder_quantity', $validated) ? $validated['reorder_quantity'] : null;
            unset($validated['reorder_point'], $validated['reorder_quantity']);

            // Protect ledger integrity: on-hand quantity should only change through transactions.
            if (array_key_exists('quantity_on_hand', $validated) &&
                (int) $validated['quantity_on_hand'] !== (int) $inventory->quantity_on_hand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Direct on-hand edits are disabled. Use Stock Adjustment, Stock Count, or Stock Transfer to change quantity.',
                    'errors' => [
                        'quantity_on_hand' => [
                            'Direct on-hand edits are disabled. Use Stock Adjustment, Stock Count, or Stock Transfer to change quantity.'
                        ]
                    ]
                ], 422);
            }

            unset($validated['quantity_on_hand']);

            $inventory->update($validated);
            $inventory->calculateTotalValue();

            // If reorder fields were included, update/create the rule instead of branch_inventory columns.
            if ($reorderPoint !== null || $reorderQuantity !== null) {
                $rule = ReorderRule::query()->firstOrNew([
                    'product_id' => (int) $inventory->product_id,
                    'branch_id' => (int) $inventory->branch_id,
                ]);

                if (!$rule->exists) {
                    $rule->rule_type = 'manual';
                    $rule->trigger_type = 'reorder_point';
                    $rule->basis_type = 'reorder_point';
                    $rule->priority = 'medium';
                    $rule->auto_generate_po = false;
                    $rule->is_active = true;
                }

                if ($reorderPoint !== null) $rule->reorder_point = $reorderPoint;
                if ($reorderQuantity !== null) $rule->reorder_quantity = $reorderQuantity;
                $rule->save();
            }

            $this->recordLog(
                'inventory.branch_inventory.updated',
                'Updated branch inventory settings',
                $inventory
            );

            return response()->json([
                'success' => true,
                'message' => 'Inventory updated successfully',
                'data' => tap($inventory->load(['product', 'variation']), function (BranchInventory $row) {
                    $rule = ReorderRule::query()
                        ->where('branch_id', (int) $row->branch_id)
                        ->where('product_id', (int) $row->product_id)
                        ->where('is_active', true)
                        ->first();
                    $row->reorder_point = $rule?->reorder_point ?? $row->reorder_point ?? 0;
                    $row->reorder_quantity = $rule?->reorder_quantity ?? $row->reorder_quantity ?? 0;
                }),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory record not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete inventory record (must belong to user's branch)
     * DELETE /api/inventory/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $inventory = BranchInventory::where('store_id', $context['store_id'])
                ->where('branch_id', $context['branch_id'])
                ->findOrFail($id);

            if ($inventory->quantity_on_hand > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete inventory with existing stock',
                ], 422);
            }

            $inventory->delete();

            $this->recordLog(
                'inventory.branch_inventory.deleted',
                'Deleted branch inventory record',
                $inventory
            );

            return response()->json([
                'success' => true,
                'message' => 'Inventory record deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory record not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get inventory summary for dashboard
     * GET /api/inventory/summary
     */
    public function summary(): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $summary = [
                'total_items' => BranchInventory::where('branch_id', $context['branch_id'])->count(),
                'in_stock' => BranchInventory::where('branch_id', $context['branch_id'])->inStock()->count(),
                'low_stock' => BranchInventory::where('branch_id', $context['branch_id'])->lowStock()->count(),
                'out_of_stock' => BranchInventory::where('branch_id', $context['branch_id'])->outOfStock()->count(),
                'total_value' => $this->inventoryService->calculateBranchValue($context['store_id'], $context['branch_id']),
                'total_quantity' => BranchInventory::where('branch_id', $context['branch_id'])->sum('quantity_on_hand'),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve summary: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get low stock alerts
     * GET /api/inventory/low-stock
     */
    public function lowStock(): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $lowStock = $this->inventoryService->getLowStockItems($context['store_id'], $context['branch_id']);

            return response()->json([
                'success' => true,
                'data' => $lowStock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve low stock items: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get out of stock alerts
     * GET /api/inventory/out-of-stock
     */
    public function outOfStock(): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $outOfStock = $this->inventoryService->getOutOfStockItems($context['store_id'], $context['branch_id']);

            return response()->json([
                'success' => true,
                'data' => $outOfStock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve out of stock items: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update stock status manually
     * POST /api/inventory/{id}/update-status
     */
    public function updateStatus(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $inventory = BranchInventory::where('store_id', $context['store_id'])
                ->where('branch_id', $context['branch_id'])
                ->findOrFail($id);
                
            $inventory->updateStockStatus();

            $this->recordLog(
                'inventory.branch_inventory.status_updated',
                'Updated inventory stock status',
                $inventory
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock status updated successfully',
                'data' => $inventory,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory record not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock status: ' . $e->getMessage(),
            ], 400);
        }
    }

    private function recordLog(string $action, string $description, BranchInventory $inventory): void
    {
        ActivityLog::record(
            $action,
            $description,
            [
                'branch_id' => $inventory->branch_id,
                'product_id' => $inventory->product_id,
                'variation_id' => $inventory->variation_id,
                'stock_status' => $inventory->stock_status,
            ],
            'inventory.branch_inventory',
            (int) $inventory->id
        );
    }
}
