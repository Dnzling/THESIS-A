<?php
// backend/app/Http/Controllers/Api/Inventory/WarehouseController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Warehouse;
use App\Http\Requests\Inventory\WarehouseRequest;
use App\Services\Inventory\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Warehouse::with(['store', 'branch']);

            // Filter by branch if provided
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by type if provided
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by status if provided
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Search by name or code
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('warehouse_code', 'like', "%{$search}%");
                });
            }

            $warehouses = $query->orderBy('name')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $warehouses,
                'message' => 'Warehouses retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving warehouses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(WarehouseRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $warehouse = $this->warehouseService->createWarehouse($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $warehouse->load(['store', 'branch']),
                'message' => 'Warehouse created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating warehouse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        try {
            $warehouse->load(['store', 'branch', 'locations']);

            return response()->json([
                'success' => true,
                'data' => $warehouse,
                'message' => 'Warehouse retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving warehouse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified warehouse.
     */
    public function update(WarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updatedWarehouse = $this->warehouseService->updateWarehouse($warehouse, $request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $updatedWarehouse->load(['store', 'branch']),
                'message' => 'Warehouse updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating warehouse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        try {
            // Check if warehouse has inventory or locations
            if ($warehouse->inventory()->exists() || $warehouse->locations()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete warehouse with existing inventory or locations'
                ], 422);
            }

            DB::beginTransaction();

            $warehouse->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting warehouse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warehouse types.
     */
    public function getTypes(): JsonResponse
    {
        try {
            $types = [
                ['value' => 'main', 'label' => 'Main Warehouse'],
                ['value' => 'branch', 'label' => 'Branch Warehouse'],
                ['value' => 'distribution', 'label' => 'Distribution Center'],
                ['value' => 'storage', 'label' => 'Storage Facility'],
                ['value' => 'retail', 'label' => 'Retail Warehouse'],
            ];

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Warehouse types retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving warehouse types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warehouse statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $query = Warehouse::query();

            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            $stats = [
                'total_warehouses' => $query->count(),
                'active_warehouses' => (clone $query)->where('status', 'active')->count(),
                'inactive_warehouses' => (clone $query)->where('status', 'inactive')->count(),
                'by_type' => (clone $query)->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type')
                    ->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Warehouse statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving warehouse statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warehouse capacity utilization.
     */
    public function getCapacityUtilization(Request $request): JsonResponse
    {
        try {
            $warehouses = Warehouse::with(['store', 'branch'])
                ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
                ->active()
                ->get()
                ->map(function ($warehouse) {
                    return [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'code' => $warehouse->warehouse_code,
                        'capacity_utilization' => $warehouse->getCapacityUtilization(),
                        'current_stock' => $warehouse->inventory()->sum('quantity_on_hand'),
                        'max_capacity' => $warehouse->max_capacity_units,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $warehouses,
                'message' => 'Warehouse capacity utilization retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving warehouse capacity utilization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse capacity utilization',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
