<?php
// backend/app/Http/Controllers/Api/Inventory/StockCountController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockCountRequest;
use App\Models\Inventory\StockCount;
use App\Models\Inventory\CountSheet;
use App\Models\Inventory\BranchInventory;
use App\Services\Inventory\StockCountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class StockCountController extends Controller
{
    public function __construct(
        protected StockCountService $stockCountService
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
     * Display a listing of stock counts
     * GET /api/inventory/counts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $query = StockCount::with([
                'branch',
                'assignedTo',
                'assignedBy',
                'supervisedBy',
                'approvedBy'
            ])
            ->where('store_id', $context['store_id']);

            // Filter by branch if specified
            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('count_type')) {
                $query->where('count_type', $request->count_type);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('scheduled_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            // Search by count number
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('count_number', 'LIKE', "%{$search}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'scheduled_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $counts = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $counts->items(),
                'meta' => [
                    'total' => $counts->total(),
                    'per_page' => $counts->perPage(),
                    'current_page' => $counts->currentPage(),
                    'last_page' => $counts->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock counts: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Store a newly created stock count
     * POST /api/inventory/counts
     */
    public function store(StockCountRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $context = $this->getUserContext();
            $data = $request->validated();
            $data['store_id'] = $context['store_id'];
            $data['assigned_by'] = auth()->id();

            $count = $this->stockCountService->createStockCount($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock count created successfully',
                'data' => $count->load(['branch', 'assignedTo', 'assignedBy']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock count: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified stock count
     * GET /api/inventory/counts/{id}
     */
    public function show(StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            $count->load([
                'branch',
                'assignedTo',
                'assignedBy',
                'supervisedBy',
                'approvedBy',
                'countSheets.product',
                'countSheets.variation',
                'countSheets.countedBy'
            ]);

            return response()->json([
                'success' => true,
                'data' => $count,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stock count not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock count: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update the specified stock count
     * PUT /api/inventory/counts/{id}
     */
    public function update(StockCountRequest $request, StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            // Only allow updates for scheduled counts
            if (!$count->isEditable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update count that is already in progress or completed',
                ], 400);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $count = $this->stockCountService->updateStockCount($count, $data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock count updated successfully',
                'data' => $count->load(['branch', 'assignedTo', 'assignedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock count: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified stock count
     * DELETE /api/inventory/counts/{id}
     */
    public function destroy(StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            // Only allow deletion for scheduled counts
            if ($count->status !== 'scheduled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete count that is already in progress or completed',
                ], 400);
            }

            $count->delete();

            return response()->json([
                'success' => true,
                'message' => 'Stock count deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stock count: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start a stock count
     * POST /api/inventory/counts/{id}/start
     */
    public function start(Request $request, StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            if (!$count->canBeStarted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock count cannot be started',
                ], 400);
            }

            $count = $this->stockCountService->startStockCount($count);

            return response()->json([
                'success' => true,
                'message' => 'Stock count started successfully',
                'data' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start stock count: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete a stock count
     * POST /api/inventory/counts/{id}/complete
     */
    public function complete(Request $request, StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            if (!$count->canBeCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock count cannot be completed',
                ], 400);
            }

            $count = $this->stockCountService->completeStockCount($count);

            return response()->json([
                'success' => true,
                'message' => 'Stock count completed successfully',
                'data' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete stock count: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve a stock count
     * POST /api/inventory/counts/{id}/approve
     */
    public function approve(Request $request, StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            $request->validate([
                'approval_notes' => 'nullable|string|max:1000',
            ]);

            $count = $this->stockCountService->approveStockCount($count, [
                'approved_by' => auth()->id(),
                'approval_notes' => $request->approval_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock count approved successfully',
                'data' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve stock count: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get count sheets for a stock count
     * GET /api/inventory/counts/{id}/sheets
     */
    public function getSheets(StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            $sheets = $count->countSheets()
                ->with(['product', 'variation', 'countedBy'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sheets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve count sheets: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update count sheet quantities
     * POST /api/inventory/counts/{id}/update-counts
     */
    public function updateCounts(Request $request, StockCount $count): JsonResponse
    {
        try {
            // Check if user has access to this count
            $context = $this->getUserContext();
            if ($count->store_id !== $context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to stock count',
                ], 403);
            }

            $request->validate([
                'counts' => 'required|array',
                'counts.*.count_sheet_id' => 'required|integer|exists:count_sheets,id',
                'counts.*.counted_quantity' => 'required|integer|min:0',
                'counts.*.notes' => 'nullable|string|max:500',
            ]);

            $result = $this->stockCountService->updateCountSheets($count, $request->counts, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Count quantities updated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update count quantities: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get count types
     * GET /api/inventory/counts/types
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'full_inventory' => 'Full Inventory Count',
                'partial_count' => 'Partial Count',
                'cycle_count' => 'Cycle Count',
                'spot_check' => 'Spot Check',
            ],
        ]);
    }

    /**
     * Get count statuses
     * GET /api/inventory/counts/statuses
     */
    public function getStatuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'scheduled' => 'Scheduled',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'approved' => 'Approved',
                'cancelled' => 'Cancelled',
            ],
        ]);
    }
}
