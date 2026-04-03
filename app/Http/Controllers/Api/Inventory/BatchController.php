<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\BatchRequest;
use App\Models\Inventory\Batch;
use App\Services\Inventory\BatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class BatchController extends Controller
{
    protected BatchService $batchService;

    public function __construct(BatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    /**
     * Display a listing of batches.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'quality_status', 'branch_id', 'product_id', 'location_id',
                'expiring_soon', 'best_before_soon', 'has_stock', 'search'
            ]);

            $perPage = $request->get('per_page', 15);
            $batches = $this->batchService->getBatches($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $batches,
                'message' => 'Batches retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batches.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created batch.
     */
    public function store(BatchRequest $request): JsonResponse
    {
        try {
            $batch = $this->batchService->createBatch($request->validated());

            return response()->json([
                'success' => true,
                'data' => $batch,
                'message' => 'Batch created successfully.',
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create batch.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch): JsonResponse
    {
        try {
            $batch->load(['product', 'branch', 'warehouseLocation']);

            return response()->json([
                'success' => true,
                'data' => $batch,
                'message' => 'Batch retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batch.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified batch.
     */
    public function update(BatchRequest $request, Batch $batch): JsonResponse
    {
        try {
            $updatedBatch = $this->batchService->updateBatch($batch, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedBatch,
                'message' => 'Batch updated successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update batch.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified batch.
     */
    public function destroy(Batch $batch): JsonResponse
    {
        try {
            // Only allow deletion of active batches with no stock movements
            if (!$batch->isActive() || $batch->quantity_sold > 0 || $batch->quantity_reserved > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active batches with no stock movements can be deleted.',
                ], 422);
            }

            $batch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Batch deleted successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete batch.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reserve stock from a batch.
     */
    public function reserveStock(Request $request, Batch $batch): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $quantity = (int) $request->get('quantity');

            if (!$this->batchService->reserveStock($batch, $quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock available for reservation.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Stock reserved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reserve stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unreserve stock from a batch.
     */
    public function unreserveStock(Request $request, Batch $batch): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $quantity = (int) $request->get('quantity');

            if (!$this->batchService->unreserveStock($batch, $quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot unreserve more stock than currently reserved.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Stock unreserved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unreserve stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sell stock from a batch.
     */
    public function sellStock(Request $request, Batch $batch): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $quantity = (int) $request->get('quantity');

            if (!$this->batchService->sellStock($batch, $quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock available for sale.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Stock sold successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sell stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return stock to a batch.
     */
    public function returnStock(Request $request, Batch $batch): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $quantity = (int) $request->get('quantity');

            if (!$this->batchService->returnStock($batch, $quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot return more stock than sold.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Stock returned successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to return stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark stock as damaged in a batch.
     */
    public function markAsDamaged(Request $request, Batch $batch): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $quantity = (int) $request->get('quantity');

            if (!$this->batchService->markAsDamaged($batch, $quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot mark more stock as damaged than available.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Stock marked as damaged successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark stock as damaged.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move batch to a different location.
     */
    public function moveToLocation(Request $request, Batch $batch): JsonResponse
    {
        try {
            $request->validate([
                'warehouse_location_id' => 'nullable|integer|exists:warehouse_locations,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $locationId = $request->get('warehouse_location_id');
            $notes = $request->get('notes');

            if (!$this->batchService->moveToLocation($batch, $locationId, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to move batch.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Batch moved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move batch.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve batch quality.
     */
    public function approveQuality(Batch $batch): JsonResponse
    {
        try {
            if (!$this->batchService->approveQuality($batch)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch quality cannot be approved.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Batch quality approved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve batch quality.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject batch quality.
     */
    public function rejectQuality(Batch $batch): JsonResponse
    {
        try {
            if (!$this->batchService->rejectQuality($batch)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch quality cannot be rejected.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Batch quality rejected successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject batch quality.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quarantine batch.
     */
    public function quarantineBatch(Batch $batch): JsonResponse
    {
        try {
            if (!$this->batchService->quarantineBatch($batch)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch cannot be quarantined.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $batch->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Batch quarantined successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to quarantine batch.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batch statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $branchId = $request->get('branch_id');
            $stats = $this->batchService->getBatchStats($branchId);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Batch statistics retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batch statistics.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get expiring batches.
     */
    public function getExpiringBatches(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days_ahead' => 'nullable|integer|min:1|max:365',
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $daysAhead = $request->get('days_ahead', 30);
            $branchId = $request->get('branch_id');

            $batches = $this->batchService->getExpiringBatches($daysAhead, $branchId);

            return response()->json([
                'success' => true,
                'data' => $batches,
                'message' => 'Expiring batches retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expiring batches.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batches reaching best before date.
     */
    public function getBestBeforeBatches(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days_ahead' => 'nullable|integer|min:1|max:365',
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $daysAhead = $request->get('days_ahead', 30);
            $branchId = $request->get('branch_id');

            $batches = $this->batchService->getBestBeforeBatches($daysAhead, $branchId);

            return response()->json([
                'success' => true,
                'data' => $batches,
                'message' => 'Best before batches retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve best before batches.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate next batch number.
     */
    public function generateNextBatchNumber(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'prefix' => 'nullable|string|max:10',
                'product_id' => 'nullable|integer|exists:products,id',
            ]);

            $prefix = $request->get('prefix');
            $productId = $request->get('product_id');

            $batchNumber = $this->batchService->generateNextBatchNumber($prefix, $productId);

            return response()->json([
                'success' => true,
                'data' => ['batch_number' => $batchNumber],
                'message' => 'Next batch number generated successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate next batch number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if batch number exists.
     */
    public function checkBatchNumber(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'batch_number' => 'required|string|max:255',
                'exclude_id' => 'nullable|integer',
            ]);

            $batchNumber = $request->get('batch_number');
            $excludeId = $request->get('exclude_id');

            $exists = $this->batchService->batchNumberExists($batchNumber, $excludeId);

            return response()->json([
                'success' => true,
                'data' => ['exists' => $exists],
                'message' => $exists ? 'Batch number already exists.' : 'Batch number is available.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check batch number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batches by product.
     */
    public function getByProduct(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $productId = $request->get('product_id');
            $branchId = $request->get('branch_id');

            $batches = $this->batchService->getBatchesByProduct($productId, $branchId);

            return response()->json([
                'success' => true,
                'data' => $batches,
                'message' => 'Batches retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batches.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transfer batches between branches.
     */
    public function transferBatches(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'batch_ids' => 'required|array|min:1',
                'batch_ids.*' => 'integer|exists:batches,id',
                'to_branch_id' => 'required|integer|exists:branches,id',
                'to_location_id' => 'nullable|integer|exists:warehouse_locations,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $batchIds = $request->get('batch_ids');
            $toBranchId = $request->get('to_branch_id');
            $toLocationId = $request->get('to_location_id');
            $notes = $request->get('notes');

            $result = $this->batchService->transferBatches($batchIds, $toBranchId, $toLocationId, $notes);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Transferred {$result['total_transferred']} batches.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer batches.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk import batches.
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'batches' => 'required|array|min:1',
                'batches.*.batch_number' => 'required|string|max:255',
                'batches.*.product_id' => 'required|integer|exists:products,id',
                'batches.*.branch_id' => 'required|integer|exists:branches,id',
                'batches.*.warehouse_location_id' => 'nullable|integer|exists:warehouse_locations,id',
                'batches.*.quantity_produced' => 'required|integer|min:1',
                'batches.*.quantity_available' => 'nullable|integer|min:0',
                'batches.*.unit_cost' => 'nullable|numeric|min:0',
                'batches.*.unit_price' => 'nullable|numeric|min:0',
                'batches.*.production_date' => 'required|date|before_or_equal:today',
                'batches.*.expiry_date' => 'nullable|date|after:production_date',
                'batches.*.best_before_date' => 'nullable|date|after:production_date',
                'batches.*.status' => 'nullable|string|in:active,expired,depleted,discontinued',
                'batches.*.quality_status' => 'nullable|string|in:pending,approved,rejected,quarantined',
                'batches.*.supplier_name' => 'nullable|string|max:255',
                'batches.*.supplier_batch_number' => 'nullable|string|max:255',
                'batches.*.notes' => 'nullable|string|max:1000',
            ]);

            $batchesData = $request->get('batches');
            $result = $this->batchService->bulkImport($batchesData);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Imported {$result['total_imported']} batches.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import batches.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update batch statuses based on expiry and stock levels.
     */
    public function updateBatchStatuses(): JsonResponse
    {
        try {
            $result = $this->batchService->updateBatchStatuses();

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Updated {$result['total_updated']} batch statuses.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update batch statuses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batch types and statuses.
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'expired', 'label' => 'Expired'],
                    ['value' => 'depleted', 'label' => 'Depleted'],
                    ['value' => 'discontinued', 'label' => 'Discontinued'],
                ],
                'quality_statuses' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'approved', 'label' => 'Approved'],
                    ['value' => 'rejected', 'label' => 'Rejected'],
                    ['value' => 'quarantined', 'label' => 'Quarantined'],
                ],
            ],
            'message' => 'Batch types retrieved successfully.',
        ]);
    }
}
