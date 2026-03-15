<?php

namespace App\Http\Controllers\Api\Procurement\StockOrder;

use App\Http\Controllers\Controller;
use App\Models\Procurement\StockOrder\StockOrderRequest;
use App\Models\Inventory\BranchInventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOrderRequestController extends Controller
{
    /**
     * List stock order requests
     * GET /api/procurement/stock-order-requests
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $storeId = $user->store_id;

        $query = StockOrderRequest::with([
            'branchInventory.branch',
            'branchInventory.product',
            'branchInventory.variation',
            'createdBy',
            'approvedBy',
            'purchaseOrders'
        ])->where('store_id', $storeId);

        // Filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && $request->branch_id) {
            $query->whereHas('branchInventory', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->has('product_id') && $request->product_id) {
            $query->whereHas('branchInventory', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Show single stock order request with available suppliers
     * GET /api/procurement/stock-order-requests/{id}
     */
    public function show(int $id): JsonResponse
    {
        $request = StockOrderRequest::with([
            'branchInventory.branch',
            'branchInventory.product',
            'branchInventory.variation',
            'branchInventory.product.suppliers',
            'createdBy',
            'approvedBy',
            'purchaseOrders'
        ])->where('store_id', auth()->user()->store_id)
          ->findOrFail($id);

        $product = $request->branchInventory?->product;
        $branch = $request->branchInventory?->branch;
        $variation = $request->branchInventory?->variation;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $request->id,
                'uuid' => $request->uuid,
                'store_id' => $request->store_id,
                'branch_inventory_id' => $request->branch_inventory_id,
                'requested_quantity' => $request->requested_quantity,
                'notes' => $request->notes,
                'status' => $request->status,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
                'approved_date' => $request->approved_date,
                'conversion_date' => $request->conversion_date,
                'created_by' => $request->createdBy,
                'approved_by' => $request->approvedBy,
                'branch' => $branch,
                'product' => $product,
                'variation' => $variation,
                'branch_inventory' => $request->branchInventory,
                'purchase_orders' => $request->purchaseOrders,
            ],
            'available_suppliers' => $request->getAvailableSuppliers(),
        ]);
    }

    /**
     * Create stock order request from low stock branch inventory
     * POST /api/procurement/stock-order-requests
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_inventory_id' => 'required|exists:branch_inventory,id',
            'requested_quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();
        $storeId = Auth::user()->store_id;

        // Verify the branch inventory belongs to this store
        $branchInventory = BranchInventory::where('store_id', $storeId)
            ->findOrFail($validated['branch_inventory_id']);

        // Create the stock order request
        $stockOrderRequest = StockOrderRequest::create([
            'store_id' => $storeId,
            'branch_inventory_id' => $validated['branch_inventory_id'],
            'requested_quantity' => $validated['requested_quantity'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'created_by' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock order request created successfully',
            'data' => $stockOrderRequest->load([
                'branchInventory.branch',
                'branchInventory.product',
                'createdBy'
            ]),
        ], 201);
    }

    /**
     * Update stock order request (pending only)
     * PUT /api/procurement/stock-order-requests/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $requestModel = StockOrderRequest::where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        if ($requestModel->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Only pending requests can be updated",
            ], 400);
        }

        $validated = $request->validate([
            'branch_inventory_id' => 'nullable|exists:branch_inventory,id',
            'requested_quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (isset($validated['branch_inventory_id'])) {
            BranchInventory::where('store_id', auth()->user()->store_id)
                ->findOrFail($validated['branch_inventory_id']);
        }

        $requestModel->update($validated);

        $requestModel->load([
            'branchInventory.branch',
            'branchInventory.product',
            'branchInventory.variation',
            'branchInventory.product.suppliers',
            'createdBy',
            'approvedBy',
            'purchaseOrders',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock order request updated successfully',
            'data' => [
                'id' => $requestModel->id,
                'uuid' => $requestModel->uuid,
                'store_id' => $requestModel->store_id,
                'branch_inventory_id' => $requestModel->branch_inventory_id,
                'requested_quantity' => $requestModel->requested_quantity,
                'notes' => $requestModel->notes,
                'status' => $requestModel->status,
                'created_at' => $requestModel->created_at,
                'updated_at' => $requestModel->updated_at,
                'approved_date' => $requestModel->approved_date,
                'conversion_date' => $requestModel->conversion_date,
                'created_by' => $requestModel->createdBy,
                'approved_by' => $requestModel->approvedBy,
                'branch' => $requestModel->branchInventory?->branch,
                'product' => $requestModel->branchInventory?->product,
                'variation' => $requestModel->branchInventory?->variation,
                'branch_inventory' => $requestModel->branchInventory,
                'purchase_orders' => $requestModel->purchaseOrders,
            ],
        ]);
    }

    /**
     * Create multiple stock order requests from low stock items
     * POST /api/procurement/stock-order-requests/bulk/create-from-low-stock
     */
    public function createFromLowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'reorder_automatically' => 'boolean',
        ]);

        $userId = Auth::id();
        $storeId = Auth::user()->store_id;

        $query = BranchInventory::where('store_id', $storeId)
            ->where('stock_status', 'low_stock');

        if ($validated['branch_id'] ?? null) {
            $query->where('branch_id', $validated['branch_id']);
        }

        $lowStockItems = $query->get();

        if ($lowStockItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No low stock items found',
                'data' => [],
            ]);
        }

        $created = [];
        foreach ($lowStockItems as $item) {
            // Check if request already exists for this item
            $existing = StockOrderRequest::where('branch_inventory_id', $item->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();

            if (!$existing) {
                $stockOrderRequest = StockOrderRequest::create([
                    'store_id' => $storeId,
                    'branch_inventory_id' => $item->id,
                    'requested_quantity' => $item->reorder_quantity,
                    'notes' => "Auto-generated from low stock alert. Current: {$item->quantity_available}, Reorder Point: {$item->reorder_point}",
                    'status' => 'pending',
                    'created_by' => $userId,
                ]);

                $created[] = $stockOrderRequest->load([
                    'branchInventory.branch',
                    'branchInventory.product'
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' stock order requests created',
            'data' => $created,
        ], 201);
    }

    /**
     * Approve stock order request
     * POST /api/procurement/stock-order-requests/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        $request = StockOrderRequest::where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Cannot approve request with status: {$request->status}",
            ], 400);
        }

        $request->approve(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Stock order request approved',
            'data' => $request->load(['approvedBy']),
        ]);
    }

    /**
     * Reject stock order request
     * POST /api/procurement/stock-order-requests/{id}/reject
     */
    public function reject(int $id): JsonResponse
    {
        $request = StockOrderRequest::where('store_id', auth()->user()->store_id)
            ->findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Cannot reject request with status: {$request->status}",
            ], 400);
        }

        $request->reject();

        return response()->json([
            'success' => true,
            'message' => 'Stock order request rejected',
            'data' => $request,
        ]);
    }

    /**
     * Get pending requests ready for PO conversion
     * GET /api/procurement/stock-order-requests/pending/for-conversion
     */
    public function pendingForConversion(): JsonResponse
    {
        $requests = StockOrderRequest::with([
            'branchInventory.branch',
            'branchInventory.product',
            'branchInventory.variation',
            'branchInventory.product.suppliers',
        ])->where('store_id', auth()->user()->store_id)
          ->where('status', 'approved')
          ->whereDoesntHave('purchaseOrders') // Only those without PO yet
          ->orderBy('created_at')
          ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
            'count' => $requests->count(),
        ]);
    }

    /**
     * Get summary stats
     * GET /api/procurement/stock-order-requests/summary
     */
    public function summary(): JsonResponse
    {
        $storeId = auth()->user()->store_id;

        $stats = [
            'pending' => StockOrderRequest::where('store_id', $storeId)->where('status', 'pending')->count(),
            'approved' => StockOrderRequest::where('store_id', $storeId)->where('status', 'approved')->count(),
            'converted' => StockOrderRequest::where('store_id', $storeId)->where('status', 'converted_to_po')->count(),
            'rejected' => StockOrderRequest::where('store_id', $storeId)->where('status', 'rejected')->count(),
            'cancelled' => StockOrderRequest::where('store_id', $storeId)->where('status', 'cancelled')->count(),
            'total_quantity_pending' => StockOrderRequest::where('store_id', $storeId)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('requested_quantity'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
