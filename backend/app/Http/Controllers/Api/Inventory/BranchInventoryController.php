<?php
// backend/app/Http/Controllers/Inventory/BranchInventoryController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Models\Inventory\BranchInventory;
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
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            
            $query = BranchInventory::with(['product', 'variation', 'branch'])
                ->where('store_id', $context['store_id'])
                ->where('branch_id', $context['branch_id']);

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

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $inventory = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $inventory->items(),
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
                'product',
                'variation',
                'branch',
                'store',
            ])
            ->where('store_id', $context['store_id'])
            ->where('branch_id', $context['branch_id'])
            ->findOrFail($id);

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

            // Generate alerts if needed
            $this->alertService->generateAlerts($context['store_id'], $context['branch_id']);

            return response()->json([
                'success' => true,
                'message' => 'Inventory record created successfully',
                'data' => $inventory->load(['product', 'variation']),
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

            $inventory->update($request->validated());
            $inventory->calculateTotalValue();

            return response()->json([
                'success' => true,
                'message' => 'Inventory updated successfully',
                'data' => $inventory->load(['product', 'variation']),
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
}