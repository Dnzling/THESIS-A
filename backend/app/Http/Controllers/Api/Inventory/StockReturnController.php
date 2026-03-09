<?php
// backend/app/Http/Controllers/Api/Inventory/StockReturnController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockReturnRequest;
use App\Models\Inventory\StockReturn;
use App\Models\Inventory\StockReturnItem;
use App\Services\Inventory\StockReturnService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class StockReturnController extends Controller
{
    public function __construct(
        protected StockReturnService $stockReturnService
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
     * Display a listing of stock returns
     * GET /api/inventory/returns
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $query = StockReturn::with([
                'fromBranch',
                'toBranch',
                'supplier',
                'requestedBy',
                'approvedBy',
                'items.product'
            ])
            ->where('store_id', $context['store_id']);

            // Filter by branch if specified
            if ($request->has('branch_id')) {
                $branchId = $request->branch_id;
                $query->where(function ($q) use ($branchId) {
                    $q->where('from_branch_id', $branchId)
                      ->orWhere('to_branch_id', $branchId);
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by return type
            if ($request->has('return_type')) {
                $query->where('return_type', $request->return_type);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('requested_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            // Search by return number
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('return_number', 'LIKE', "%{$search}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $returns = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $returns->items(),
                'meta' => [
                    'total' => $returns->total(),
                    'per_page' => $returns->perPage(),
                    'current_page' => $returns->currentPage(),
                    'last_page' => $returns->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock returns: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Store a newly created stock return
     * POST /api/inventory/returns
     */
    public function store(StockReturnRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $context = $this->getUserContext();
            $data = $request->validated();
            $data['store_id'] = $context['store_id'];
            $data['requested_by'] = auth()->id();

            $return = $this->stockReturnService->createReturn($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock return created successfully',
                'data' => $return->load(['items.product', 'fromBranch', 'supplier']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified stock return
     * GET /api/inventory/returns/{id}
     */
    public function show(StockReturn $return): JsonResponse
    {
        try {
            // Check if user has access to this return
            $context = $this->getUserContext();
            if ($return->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock return',
                ], 403);
            }

            $return->load([
                'fromBranch',
                'toBranch',
                'supplier',
                'requestedBy',
                'approvedBy',
                'shippedBy',
                'receivedBy',
                'items.product',
                'items.variation',
                'items.branchInventory'
            ]);

            return response()->json([
                'success' => true,
                'data' => $return,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock return not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update the specified stock return
     * PUT /api/inventory/returns/{id}
     */
    public function update(StockReturnRequest $request, StockReturn $return): JsonResponse
    {
        try {
            // Check if user has access to this return
            $context = $this->getUserContext();
            if ($return->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock return',
                ], 403);
            }

            // Only allow updates for draft or requested returns
            if (!in_array($return->status, ['draft', 'requested'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update return that is already approved or processed',
                ], 400);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $return = $this->stockReturnService->updateReturn($return, $data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock return updated successfully',
                'data' => $return->load(['items.product', 'fromBranch', 'supplier']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified stock return
     * DELETE /api/inventory/returns/{id}
     */
    public function destroy(StockReturn $return): JsonResponse
    {
        try {
            // Check if user has access to this return
            $context = $this->getUserContext();
            if ($return->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock return',
                ], 403);
            }

            // Only allow deletion for draft returns
            if ($return->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete return that is already processed',
                ], 400);
            }

            $return->delete();

            return response()->json([
                'success' => true,
                'message' => 'Stock return deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve a stock return
     * POST /api/inventory/returns/{id}/approve
     */
    public function approve(Request $request, StockReturn $return): JsonResponse
    {
        try {
            // Check if user has access to this return
            $context = $this->getUserContext();
            if ($return->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock return',
                ], 403);
            }

            $request->validate([
                'notes' => 'nullable|string|max:500',
            ]);

            $return = $this->stockReturnService->approveReturn($return, [
                'approved_by' => auth()->id(),
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock return approved successfully',
                'data' => $return,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a stock return
     * POST /api/inventory/returns/{id}/reject
     */
    public function reject(Request $request, StockReturn $return): JsonResponse
    {
        try {
            // Check if user has access to this return
            $context = $this->getUserContext();
            if ($return->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock return',
                ], 403);
            }

            $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);

            $return = $this->stockReturnService->rejectReturn($return, [
                'rejection_reason' => $request->rejection_reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock return rejected',
                'data' => $return,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark return as shipped
     * POST /api/inventory/returns/{id}/ship
     */
    public function ship(Request $request, StockReturn $return): JsonResponse
    {
        try {
            // Check if user has access to this return
            $context = $this->getUserContext();
            if ($return->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock return',
                ], 403);
            }

            $request->validate([
                'vehicle_type' => 'nullable|string|max:100',
                'driver_name' => 'nullable|string|max:100',
                'driver_contact' => 'nullable|string|max:50',
                'tracking_number' => 'nullable|string|max:100',
            ]);

            $return = $this->stockReturnService->shipReturn($return, [
                'shipped_by' => auth()->id(),
                ...$request->only(['vehicle_type', 'driver_name', 'driver_contact', 'tracking_number']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock return marked as shipped',
                'data' => $return,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to ship stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark return as received
     * POST /api/inventory/returns/{id}/receive
     */
    public function receive(Request $request, StockReturn $return): JsonResponse
    {
        try {
            // Check if user has access to this return
            $context = $this->getUserContext();
            if ($return->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock return',
                ], 403);
            }

            $return = $this->stockReturnService->receiveReturn($return, [
                'received_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock return marked as received',
                'data' => $return,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive stock return: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get return reasons
     * GET /api/inventory/returns/reasons
     */
    public function getReasons(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'damaged' => 'Damaged goods',
                'expired' => 'Expired items',
                'quality_issue' => 'Quality issues',
                'wrong_item' => 'Wrong item received',
                'overstock' => 'Overstock/Excess inventory',
                'customer_return' => 'Customer return',
                'supplier_policy' => 'Supplier return policy',
                'other' => 'Other',
            ],
        ]);
    }

    /**
     * Get return types
     * GET /api/inventory/returns/types
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'supplier_return' => 'Return to Supplier',
                'branch_return' => 'Return to Branch',
                'damaged_return' => 'Damaged Goods Return',
                'expired_return' => 'Expired Items Return',
                'quality_return' => 'Quality Issue Return',
            ],
        ]);
    }
}
