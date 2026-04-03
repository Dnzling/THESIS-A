<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SerialNumberRequest;
use App\Models\Inventory\SerialNumber;
use App\Services\Inventory\SerialNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SerialNumberController extends Controller
{
    protected SerialNumberService $serialNumberService;

    public function __construct(SerialNumberService $serialNumberService)
    {
        $this->serialNumberService = $serialNumberService;
    }

    /**
     * Display a listing of serial numbers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'condition', 'branch_id', 'product_id', 'location_id',
                'warranty_status', 'search'
            ]);

            $perPage = $request->get('per_page', 15);
            $serialNumbers = $this->serialNumberService->getSerialNumbers($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $serialNumbers,
                'message' => 'Serial numbers retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve serial numbers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created serial number.
     */
    public function store(SerialNumberRequest $request): JsonResponse
    {
        try {
            $serialNumber = $this->serialNumberService->createSerialNumber($request->validated());

            return response()->json([
                'success' => true,
                'data' => $serialNumber,
                'message' => 'Serial number created successfully.',
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified serial number.
     */
    public function show(SerialNumber $serialNumber): JsonResponse
    {
        try {
            $serialNumber->load(['product', 'branch', 'warehouseLocation']);

            return response()->json([
                'success' => true,
                'data' => $serialNumber,
                'message' => 'Serial number retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified serial number.
     */
    public function update(SerialNumberRequest $request, SerialNumber $serialNumber): JsonResponse
    {
        try {
            $updatedSerialNumber = $this->serialNumberService->updateSerialNumber($serialNumber, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedSerialNumber,
                'message' => 'Serial number updated successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified serial number.
     */
    public function destroy(SerialNumber $serialNumber): JsonResponse
    {
        try {
            // Only allow deletion of available serial numbers
            if (!$serialNumber->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only available serial numbers can be deleted.',
                ], 422);
            }

            $serialNumber->delete();

            return response()->json([
                'success' => true,
                'message' => 'Serial number deleted successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sell a serial number.
     */
    public function sell(Request $request, SerialNumber $serialNumber): JsonResponse
    {
        try {
            $request->validate([
                'selling_price' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            $sellingPrice = (float) $request->get('selling_price');
            $notes = $request->get('notes');

            if (!$this->serialNumberService->sellSerialNumber($serialNumber, $sellingPrice, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serial number cannot be sold.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $serialNumber->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Serial number sold successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sell serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reserve a serial number.
     */
    public function reserve(Request $request, SerialNumber $serialNumber): JsonResponse
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $notes = $request->get('notes');

            if (!$this->serialNumberService->reserveSerialNumber($serialNumber, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serial number cannot be reserved.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $serialNumber->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Serial number reserved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reserve serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unreserve a serial number.
     */
    public function unreserve(Request $request, SerialNumber $serialNumber): JsonResponse
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $notes = $request->get('notes');

            if (!$this->serialNumberService->unreserveSerialNumber($serialNumber, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serial number cannot be unreserved.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $serialNumber->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Serial number unreserved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unreserve serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark serial number as damaged.
     */
    public function markAsDamaged(Request $request, SerialNumber $serialNumber): JsonResponse
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $notes = $request->get('notes');

            if (!$this->serialNumberService->markAsDamaged($serialNumber, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serial number cannot be marked as damaged.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $serialNumber->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Serial number marked as damaged successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark serial number as damaged.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return a serial number.
     */
    public function returnSerialNumber(Request $request, SerialNumber $serialNumber): JsonResponse
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            $notes = $request->get('notes');

            if (!$this->serialNumberService->returnSerialNumber($serialNumber, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serial number cannot be returned.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $serialNumber->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Serial number returned successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to return serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move serial number to a different location.
     */
    public function moveToLocation(Request $request, SerialNumber $serialNumber): JsonResponse
    {
        try {
            $request->validate([
                'warehouse_location_id' => 'nullable|integer|exists:warehouse_locations,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $locationId = $request->get('warehouse_location_id');
            $notes = $request->get('notes');

            if (!$this->serialNumberService->moveToLocation($serialNumber, $locationId, $notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to move serial number.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $serialNumber->fresh(['product', 'branch', 'warehouseLocation']),
                'message' => 'Serial number moved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get serial number statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $branchId = $request->get('branch_id');
            $stats = $this->serialNumberService->getSerialNumberStats($branchId);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Serial number statistics retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve serial number statistics.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get expiring warranties.
     */
    public function getExpiringWarranties(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days_ahead' => 'nullable|integer|min:1|max:365',
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $daysAhead = $request->get('days_ahead', 30);
            $branchId = $request->get('branch_id');

            $warranties = $this->serialNumberService->getExpiringWarranties($daysAhead, $branchId);

            return response()->json([
                'success' => true,
                'data' => $warranties,
                'message' => 'Expiring warranties retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expiring warranties.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get expired warranties.
     */
    public function getExpiredWarranties(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id',
            ]);

            $branchId = $request->get('branch_id');
            $warranties = $this->serialNumberService->getExpiredWarranties($branchId);

            return response()->json([
                'success' => true,
                'data' => $warranties,
                'message' => 'Expired warranties retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expired warranties.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate next serial number.
     */
    public function generateNextSerialNumber(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'prefix' => 'nullable|string|max:10',
                'product_id' => 'nullable|integer|exists:products,id',
            ]);

            $prefix = $request->get('prefix');
            $productId = $request->get('product_id');

            $serialNumber = $this->serialNumberService->generateNextSerialNumber($prefix, $productId);

            return response()->json([
                'success' => true,
                'data' => ['serial_number' => $serialNumber],
                'message' => 'Next serial number generated successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate next serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if serial number exists.
     */
    public function checkSerialNumber(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'serial_number' => 'required|string|max:255',
                'exclude_id' => 'nullable|integer',
            ]);

            $serialNumber = $request->get('serial_number');
            $excludeId = $request->get('exclude_id');

            $exists = $this->serialNumberService->serialNumberExists($serialNumber, $excludeId);

            return response()->json([
                'success' => true,
                'data' => ['exists' => $exists],
                'message' => $exists ? 'Serial number already exists.' : 'Serial number is available.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check serial number.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get serial numbers by product.
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

            $serialNumbers = $this->serialNumberService->getSerialNumbersByProduct($productId, $branchId);

            return response()->json([
                'success' => true,
                'data' => $serialNumbers,
                'message' => 'Serial numbers retrieved successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve serial numbers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transfer serial numbers between branches.
     */
    public function transferSerialNumbers(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'serial_number_ids' => 'required|array|min:1',
                'serial_number_ids.*' => 'integer|exists:serial_numbers,id',
                'to_branch_id' => 'required|integer|exists:branches,id',
                'to_location_id' => 'nullable|integer|exists:warehouse_locations,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $serialNumberIds = $request->get('serial_number_ids');
            $toBranchId = $request->get('to_branch_id');
            $toLocationId = $request->get('to_location_id');
            $notes = $request->get('notes');

            $result = $this->serialNumberService->transferSerialNumbers($serialNumberIds, $toBranchId, $toLocationId, $notes);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Transferred {$result['total_transferred']} serial numbers.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer serial numbers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk import serial numbers.
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'serial_numbers' => 'required|array|min:1',
                'serial_numbers.*.serial_number' => 'required|string|max:255',
                'serial_numbers.*.product_id' => 'required|integer|exists:products,id',
                'serial_numbers.*.branch_id' => 'required|integer|exists:branches,id',
                'serial_numbers.*.warehouse_location_id' => 'nullable|integer|exists:warehouse_locations,id',
                'serial_numbers.*.status' => 'nullable|string|in:available,sold,reserved,damaged,returned,in_transit',
                'serial_numbers.*.condition' => 'nullable|string|in:new,used,refurbished,damaged',
                'serial_numbers.*.purchase_price' => 'nullable|numeric|min:0',
                'serial_numbers.*.selling_price' => 'nullable|numeric|min:0',
                'serial_numbers.*.purchase_date' => 'nullable|date|before_or_equal:today',
                'serial_numbers.*.warranty_expiry' => 'nullable|date|after:today',
                'serial_numbers.*.notes' => 'nullable|string|max:1000',
            ]);

            $serialNumbersData = $request->get('serial_numbers');
            $result = $this->serialNumberService->bulkImport($serialNumbersData);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Imported {$result['total_imported']} serial numbers.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import serial numbers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get serial number types and statuses.
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    ['value' => 'available', 'label' => 'Available'],
                    ['value' => 'sold', 'label' => 'Sold'],
                    ['value' => 'reserved', 'label' => 'Reserved'],
                    ['value' => 'damaged', 'label' => 'Damaged'],
                    ['value' => 'returned', 'label' => 'Returned'],
                    ['value' => 'in_transit', 'label' => 'In Transit'],
                ],
                'conditions' => [
                    ['value' => 'new', 'label' => 'New'],
                    ['value' => 'used', 'label' => 'Used'],
                    ['value' => 'refurbished', 'label' => 'Refurbished'],
                    ['value' => 'damaged', 'label' => 'Damaged'],
                ],
                'warranty_statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'expired', 'label' => 'Expired'],
                ],
            ],
            'message' => 'Serial number types retrieved successfully.',
        ]);
    }
}
