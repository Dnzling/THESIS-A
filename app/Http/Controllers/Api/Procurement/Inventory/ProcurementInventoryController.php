<?php

namespace App\Http\Controllers\Api\Procurement\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Inventory\ProcurementInventory;
use App\Models\ProductCatalog\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProcurementInventoryController extends Controller
{
    /**
     * Get procurement inventory for store
     * GET /api/procurement/inventory
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $query = ProcurementInventory::with(['product', 'product.category', 'variation'])
                ->where('store_id', $storeId)
                ->active();

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('low_stock')) {
                $threshold = $request->get('low_stock', 10);
                $query->where('available_qty', '<', $threshold);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('product_name', 'LIKE', "%{$search}%")
                      ->orWhere('sku', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('category_id')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
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
                'message' => 'Failed to retrieve procurement inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get single procurement inventory item
     * GET /api/procurement/inventory/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $inventory = ProcurementInventory::where('store_id', $storeId)
                ->with(['product', 'product.category', 'variation'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $inventory,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Procurement inventory item not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve item: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Initialize procurement inventory for products
     * POST /api/procurement/inventory/init
     */
    public function initialize(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $userId = auth()->id();

            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'integer|exists:products,id',
                'initial_qty' => 'nullable|integer|min:0',
            ]);

            $created = 0;
            $skipped = 0;

            foreach ($request->product_ids as $productId) {
                $exists = ProcurementInventory::where('store_id', $storeId)
                    ->where('product_id', $productId)
                    ->exists();

                if (!$exists) {
                    ProcurementInventory::create([
                        'store_id' => $storeId,
                        'product_id' => $productId,
                        'available_qty' => $request->initial_qty ?? 0,
                        'status' => 'active',
                        'created_by' => $userId,
                    ]);
                    $created++;
                } else {
                    $skipped++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Initialized {$created} procurement inventory items ({$skipped} already exist)",
                'created' => $created,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update procurement inventory quantity
     * PUT /api/procurement/inventory/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $userId = auth()->id();

            $inventory = ProcurementInventory::where('store_id', $storeId)->findOrFail($id);

            $request->validate([
                'available_qty' => 'nullable|integer|min:0',
                'status' => 'nullable|in:active,inactive,discontinued',
                'notes' => 'nullable|string',
            ]);

            $inventory->update([
                'available_qty' => $request->available_qty ?? $inventory->available_qty,
                'status' => $request->status ?? $inventory->status,
                'notes' => $request->notes ?? $inventory->notes,
                'updated_by' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $inventory->refresh(),
                'message' => 'Procurement inventory updated',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Procurement inventory item not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get low stock items
     * GET /api/procurement/inventory/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $threshold = $request->get('threshold', 10);

            $inventory = ProcurementInventory::with(['product', 'product.category', 'variation'])
                ->where('store_id', $storeId)
                ->where('available_qty', '<', $threshold)
                ->active()
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $inventory->items(),
                'meta' => [
                    'total' => $inventory->total(),
                    'threshold' => $threshold,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve low stock items: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get summary statistics
     * GET /api/procurement/inventory/summary
     */
    public function summary(): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $summary = ProcurementInventory::where('store_id', $storeId)
                ->selectRaw('
                    COUNT(*) as total_items,
                    SUM(available_qty) as total_available,
                    SUM(on_order_qty) as total_on_order,
                    SUM(received_qty) as total_received,
                    SUM(pending_receive_qty) as total_pending
                ')
                ->first();

            $lowStockCount = ProcurementInventory::where('store_id', $storeId)
                ->where('available_qty', '<', 10)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_items' => $summary->total_items ?? 0,
                    'total_available' => $summary->total_available ?? 0,
                    'total_on_order' => $summary->total_on_order ?? 0,
                    'total_received' => $summary->total_received ?? 0,
                    'total_pending' => $summary->total_pending ?? 0,
                    'low_stock_count' => $lowStockCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get summary: ' . $e->getMessage(),
            ], 400);
        }
    }
}
