<?php
// backend/app/Http/Controllers/Api/Inventory/StockCountController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockCountRequest;
use App\Models\Core\ActivityLog;
use App\Models\Inventory\StockCount;
use App\Models\Inventory\CountSheet;
use App\Models\Inventory\BranchInventory;
use App\Models\Hr\Employee;
use App\Services\Inventory\StockCountService;
use App\Support\EmployeeContext;
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
        $user = auth()->user();
        if (!$user) {
            return 0;
        }

        if (!empty($user->branch_id)) {
            return (int) $user->branch_id;
        }

        return (int) Employee::query()
            ->where('user_id', $user->id)
            ->value('branch_id');
    }

    /**
     * Resolve current user's employee id (required by stock count assigned fields)
     */
    private function getCurrentEmployeeId(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        return Employee::query()
            ->where('user_id', $user->id)
            ->value('id');
    }

    /**
     * Get the authenticated user's context (store & branch)
     */
    private function getUserContext(): array
    {
        $user = auth()->user();
        $storeId = (int) ($user?->store_id ?? 0);
        if ($storeId === 0 && $user) {
            $storeId = (int) Employee::query()
                ->where('user_id', $user->id)
                ->value('store_id');
        }

        return [
            'store_id' => $storeId,
            'branch_id' => $this->getUserBranchId(),
        ];
    }

    private function hasGlobalAccess(): bool
    {
        $roleName = strtolower(auth()->user()?->role?->name ?? '');
        return in_array($roleName, ['super_admin', 'owner'], true);
    }

    /**
     * Normalize selected item rows from UI into unique product_ids.
     */
    private function extractProductIdsFromItems(Request $request): array
    {
        $items = $request->input('items', []);
        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }
                $productId = $item['product_id'] ?? null;
                return is_numeric($productId) ? (int) $productId : null;
            })
            ->filter(fn ($id) => !empty($id))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Display a listing of stock counts
     * GET /api/inventory/counts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            if (!$this->hasGlobalAccess() && empty($context['store_id']) && empty($context['branch_id'])) {
                $perPage = $request->get('per_page', 15);
                $empty = StockCount::query()->whereRaw('1 = 0')->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'total' => $empty->total(),
                        'per_page' => $empty->perPage(),
                        'current_page' => $empty->currentPage(),
                        'last_page' => $empty->lastPage(),
                    ],
                ]);
            }

            $query = StockCount::with([
                'branch',
                'assignedTo',
                'assignedBy',
                'supervisedBy',
                'approvedBy'
            ])
            ->where('store_id', $context['store_id']);

            // Filter by branch if specified
            if ($request->filled('branch_id') || $request->filled('warehouse_id')) {
                $query->where('branch_id', (int) ($request->branch_id ?? $request->warehouse_id));
            } elseif (!empty($context['branch_id'])) {
                // Default to current user's branch
                $query->where('branch_id', (int) $context['branch_id']);
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
            $employeeId = $this->getCurrentEmployeeId();
            $data = $request->validated();
            $data['items'] = is_array($request->input('items')) ? $request->input('items') : [];
            $itemProductIds = $this->extractProductIdsFromItems($request);
            if (!empty($itemProductIds)) {
                $data['product_ids'] = $itemProductIds;
                $data['count_type'] = 'partial_count';
            }
            $data['branch_id'] = (int) ($data['branch_id'] ?? $context['branch_id']);
            $data['assigned_to'] = (int) ($data['assigned_to'] ?? $employeeId ?? 0);
            $data['store_id'] = $context['store_id'];
            $data['assigned_by'] = (int) ($employeeId ?? 0);

            if (empty($data['branch_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No branch is assigned to your account. Please contact admin.',
                ], 422);
            }

            if (empty($data['assigned_by']) || empty($data['assigned_to'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No employee profile found for your account. Please contact HR/admin.',
                ], 422);
            }

            $count = $this->stockCountService->createStockCount($data);

            DB::commit();

            $this->recordLog(
                'inventory.stock_count.created',
                "Created stock count {$count->count_number}",
                $count
            );

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
            $itemProductIds = $this->extractProductIdsFromItems($request);
            if (!empty($itemProductIds)) {
                $data['product_ids'] = $itemProductIds;
                $data['count_type'] = 'partial_count';
            }
            $employeeId = $this->getCurrentEmployeeId();
            $data['branch_id'] = (int) ($data['branch_id'] ?? $count->branch_id ?? $context['branch_id']);
            $data['assigned_to'] = (int) ($data['assigned_to'] ?? $count->assigned_to ?? $employeeId ?? 0);
            $count = $this->stockCountService->updateStockCount($count, $data);

            DB::commit();

            $this->recordLog(
                'inventory.stock_count.updated',
                "Updated stock count {$count->count_number}",
                $count
            );

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

            $this->recordLog(
                'inventory.stock_count.deleted',
                "Deleted stock count {$count->count_number}",
                $count
            );

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

            $this->recordLog(
                'inventory.stock_count.started',
                "Started stock count {$count->count_number}",
                $count
            );

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

            $this->recordLog(
                'inventory.stock_count.completed',
                "Completed stock count {$count->count_number}",
                $count
            );

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
            $user = auth()->user();
            if (!$user || !$user->hasPermissionTo('inventory.stock_counts.approve', (int) $user->store_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Approval permission is required.',
                ], 403);
            }

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
                'approved_by' => EmployeeContext::currentEmployeeId(),
                'approval_notes' => $request->approval_notes,
            ]);

            $this->recordLog(
                'inventory.stock_count.approved',
                "Approved stock count {$count->count_number}",
                $count,
                ['approval_notes' => $request->approval_notes]
            );

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

            $result = $this->stockCountService->updateCountSheets($count, $request->counts, (int) EmployeeContext::currentEmployeeId());

            $this->recordLog(
                'inventory.stock_count.counts_updated',
                "Updated counted quantities for {$count->count_number}",
                $count,
                ['updated_items' => count($request->counts)]
            );

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
                'pending_approval' => 'Pending Approval',
                'scheduled' => 'Scheduled',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'approved' => 'Approved',
                'cancelled' => 'Cancelled',
            ],
        ]);
    }

    /**
     * Suggest items for cycle counts
     * GET /api/inventory/counts/suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
                'limit' => 'nullable|integer|min:1|max:200',
                'days' => 'nullable|integer|min:7|max:365',
            ]);

        $context = $this->getUserContext();

        $branchId = (int) ($request->branch_id ?? $request->warehouse_id ?? $context['branch_id']);
        if ($branchId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Branch is required for stock count suggestions.',
            ], 422);
        }

        $result = $this->stockCountService->getCycleCountSuggestions(
            $context['store_id'],
            $branchId,
            (int) ($request->limit ?? 50),
            (int) ($request->days ?? 90)
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Auto-schedule weekly cycle counts
     * POST /api/inventory/counts/auto-schedule
     */
    public function autoSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'weeks' => 'nullable|integer|min:1|max:12',
            'per_count' => 'nullable|integer|min:10|max:200',
            'start_date' => 'nullable|date|after:today',
        ]);

        $context = $this->getUserContext();
        $assignedBy = $this->getCurrentEmployeeId();
        $assignedTo = $assignedBy;
        $branchId = (int) ($request->branch_id ?? $request->warehouse_id ?? $context['branch_id']);

        if ($branchId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No branch is assigned to your account. Please contact admin.',
            ], 422);
        }

        if (empty($assignedBy)) {
            return response()->json([
                'success' => false,
                'message' => 'No employee profile found for your account. Please contact HR/admin.',
            ], 422);
        }

        $result = $this->stockCountService->autoScheduleCycleCounts(
            $context['store_id'],
            $branchId,
            $assignedBy,
            $assignedTo,
            (int) ($request->weeks ?? 4),
            (int) ($request->per_count ?? 50),
            $request->start_date
        );

        return response()->json([
            'success' => true,
            'message' => "Scheduled {$result['total_scheduled']} cycle counts.",
            'data' => $result,
        ]);
    }

    private function recordLog(string $action, string $description, StockCount $count, array $meta = []): void
    {
        ActivityLog::record(
            $action,
            $description,
            array_merge([
                'branch_id' => $count->branch_id,
                'count_number' => $count->count_number,
                'status' => $count->status,
                'count_type' => $count->count_type,
            ], $meta),
            'inventory.stock_count',
            (int) $count->id
        );
    }
}
