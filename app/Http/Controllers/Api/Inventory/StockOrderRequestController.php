<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Procurement\StockOrder\StockOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOrderRequestController extends Controller
{
    private function resolveBranchId(): int
    {
        $user = Auth::user();
        return (int) ($user?->branch_id ?: $user?->employee?->branch_id ?: 0);
    }

    /**
     * List stock order requests for the current user's branch only.
     * GET /api/inventory/stock-order-requests
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) $user->store_id;
        $branchId = $this->resolveBranchId();

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'No branch is assigned to your user profile.',
            ], 422);
        }

        $query = StockOrderRequest::query()
            ->with([
                'branchInventory.branch',
                'branchInventory.product',
                'branchInventory.variation',
                'createdBy:id,fname,lname,email',
                'approvedBy:id,fname,lname,email',
                'purchaseOrders',
            ])
            ->where('store_id', $storeId)
            ->whereHas('branchInventory', fn($q) => $q->where('branch_id', $branchId));

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('product_id')) {
            $productId = (int) $request->input('product_id');
            $query->whereHas('branchInventory', fn($q) => $q->where('product_id', $productId));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('branchInventory.product', function ($pq) use ($search) {
                        $pq->where('product_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('branchInventory.branch', function ($bq) use ($search) {
                        $bq->where('name', 'like', "%{$search}%")
                            ->orWhere('branch_code', 'like', "%{$search}%");
                    });
            });
        }

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['created_at', 'status', 'requested_quantity'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $requests = $query->orderBy($sortBy, $sortOrder)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Show a single request, branch-scoped.
     * GET /api/inventory/stock-order-requests/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) $user->store_id;
        $branchId = $this->resolveBranchId();

        $requestModel = StockOrderRequest::query()
            ->with([
                'branchInventory.branch',
                'branchInventory.product.suppliers',
                'branchInventory.variation',
                'createdBy:id,fname,lname,email',
                'approvedBy:id,fname,lname,email',
                'purchaseOrders',
            ])
            ->where('store_id', $storeId)
            ->whereHas('branchInventory', fn($q) => $q->where('branch_id', $branchId))
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $requestModel->id,
                'uuid' => $requestModel->uuid,
                'store_id' => $requestModel->store_id,
                'branch_inventory_id' => $requestModel->branch_inventory_id,
                'requested_quantity' => $requestModel->requested_quantity,
                'notes' => $requestModel->notes,
                'rejection_reason' => $requestModel->rejection_reason,
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
            'available_suppliers' => method_exists($requestModel, 'getAvailableSuppliers')
                ? $requestModel->getAvailableSuppliers()
                : [],
        ]);
    }

    /**
     * Create a request for the current branch only.
     * POST /api/inventory/stock-order-requests
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_inventory_id' => 'required|exists:branch_inventory,id',
            'requested_quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $storeId = (int) $user->store_id;
        $branchId = $this->resolveBranchId();

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'No branch is assigned to your user profile.',
            ], 422);
        }

        // Ensure this inventory row belongs to this store + branch
        BranchInventory::query()
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->findOrFail((int) $validated['branch_inventory_id']);

        $stockOrderRequest = StockOrderRequest::query()->create([
            'store_id' => $storeId,
            'branch_inventory_id' => (int) $validated['branch_inventory_id'],
            'requested_quantity' => (int) $validated['requested_quantity'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'created_by' => (int) Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock order request created successfully',
            'data' => $stockOrderRequest->load([
                'branchInventory.branch',
                'branchInventory.product',
                'branchInventory.variation',
                'createdBy:id,fname,lname,email',
            ]),
        ], 201);
    }

    /**
     * Update a pending request, branch-scoped.
     * PUT /api/inventory/stock-order-requests/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) $user->store_id;
        $branchId = $this->resolveBranchId();

        $requestModel = StockOrderRequest::query()
            ->where('store_id', $storeId)
            ->whereHas('branchInventory', fn($q) => $q->where('branch_id', $branchId))
            ->findOrFail($id);

        if ($requestModel->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be updated',
            ], 400);
        }

        $validated = $request->validate([
            'branch_inventory_id' => 'nullable|exists:branch_inventory,id',
            'requested_quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (isset($validated['branch_inventory_id'])) {
            BranchInventory::query()
                ->where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->findOrFail((int) $validated['branch_inventory_id']);
        }

        $requestModel->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock order request updated successfully',
            'data' => $requestModel->fresh()->load([
                'branchInventory.branch',
                'branchInventory.product',
                'branchInventory.variation',
                'createdBy:id,fname,lname,email',
                'approvedBy:id,fname,lname,email',
                'purchaseOrders',
            ]),
        ]);
    }

    /**
     * Approve request (pending only), branch-scoped.
     * POST /api/inventory/stock-order-requests/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) $user->store_id;
        $branchId = $this->resolveBranchId();

        $requestModel = StockOrderRequest::query()
            ->where('store_id', $storeId)
            ->whereHas('branchInventory', fn($q) => $q->where('branch_id', $branchId))
            ->findOrFail($id);

        if ($requestModel->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Cannot approve request with status: {$requestModel->status}",
            ], 400);
        }

        $requestModel->approve((int) Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Stock order request approved',
            'data' => $requestModel->fresh()->load(['approvedBy:id,fname,lname,email']),
        ]);
    }

    /**
     * Reject request (pending only), branch-scoped.
     * POST /api/inventory/stock-order-requests/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) $user->store_id;
        $branchId = $this->resolveBranchId();

        $requestModel = StockOrderRequest::query()
            ->where('store_id', $storeId)
            ->whereHas('branchInventory', fn($q) => $q->where('branch_id', $branchId))
            ->findOrFail($id);

        if ($requestModel->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Cannot reject request with status: {$requestModel->status}",
            ], 400);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $requestModel->reject((string) $validated['rejection_reason']);

        return response()->json([
            'success' => true,
            'message' => 'Stock order request rejected',
            'data' => $requestModel->fresh(),
        ]);
    }

    /**
     * Summary stats for current branch.
     * GET /api/inventory/stock-order-requests/summary
     */
    public function summary(): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) $user->store_id;
        $branchId = $this->resolveBranchId();

        $base = StockOrderRequest::query()
            ->where('store_id', $storeId)
            ->whereHas('branchInventory', fn($q) => $q->where('branch_id', $branchId));

        $stats = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'converted' => (clone $base)->where('status', 'converted_to_po')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'total_quantity_pending' => (clone $base)->whereIn('status', ['pending', 'approved'])->sum('requested_quantity'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
