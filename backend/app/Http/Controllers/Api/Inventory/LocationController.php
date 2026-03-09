<?php
// backend/app/Http/Controllers/Api/Inventory/LocationController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\WarehouseLocation;
use App\Http\Requests\Inventory\LocationRequest;
use App\Services\Inventory\LocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Display a listing of warehouse locations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WarehouseLocation::with(['warehouse.store', 'warehouse.branch']);

            // Filter by warehouse if provided
            if ($request->has('warehouse_id') && $request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Filter by type if provided
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by status if provided
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by availability
            if ($request->has('available') && $request->boolean('available')) {
                $query->available();
            }

            // Search by name or code
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('location_code', 'like', "%{$search}%");
                });
            }

            $locations = $query->orderBy('warehouse_id')->orderBy('location_code')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $locations,
                'message' => 'Warehouse locations retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving warehouse locations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created warehouse location.
     */
    public function store(LocationRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $location = $this->locationService->createLocation($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $location->load(['warehouse.store', 'warehouse.branch']),
                'message' => 'Warehouse location created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating warehouse location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create warehouse location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified warehouse location.
     */
    public function show(WarehouseLocation $location): JsonResponse
    {
        try {
            $location->load(['warehouse.store', 'warehouse.branch']);

            return response()->json([
                'success' => true,
                'data' => $location,
                'message' => 'Warehouse location retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving warehouse location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified warehouse location.
     */
    public function update(LocationRequest $request, WarehouseLocation $location): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updatedLocation = $this->locationService->updateLocation($location, $request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $updatedLocation->load(['warehouse.store', 'warehouse.branch']),
                'message' => 'Warehouse location updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating warehouse location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update warehouse location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified warehouse location.
     */
    public function destroy(WarehouseLocation $location): JsonResponse
    {
        try {
            // Check if location has inventory
            if ($location->inventory()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete location with existing inventory'
                ], 422);
            }

            DB::beginTransaction();

            $location->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse location deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting warehouse location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete warehouse location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get location types.
     */
    public function getTypes(): JsonResponse
    {
        try {
            $types = [
                ['value' => 'rack', 'label' => 'Rack'],
                ['value' => 'shelf', 'label' => 'Shelf'],
                ['value' => 'bin', 'label' => 'Bin'],
                ['value' => 'floor', 'label' => 'Floor Storage'],
                ['value' => 'cold_storage', 'label' => 'Cold Storage'],
                ['value' => 'secure', 'label' => 'Secure Storage'],
                ['value' => 'bulk', 'label' => 'Bulk Storage'],
            ];

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Location types retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving location types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve location types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available locations for a warehouse.
     */
    public function getAvailable(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'quantity' => 'nullable|numeric|min:0',
                'weight' => 'nullable|numeric|min:0',
                'type' => 'nullable|string|in:rack,shelf,bin,floor,cold_storage,secure,bulk',
                'temperature_controlled' => 'nullable|boolean',
            ]);

            $locations = WarehouseLocation::where('warehouse_id', $request->warehouse_id)
                ->available()
                ->when($request->type, fn($q) => $q->where('type', $request->type))
                ->when($request->boolean('temperature_controlled'), fn($q) => $q->temperatureControlled())
                ->get()
                ->filter(function ($location) use ($request) {
                    return $location->canAccommodate(
                        $request->quantity ?? 0,
                        $request->weight ?? 0
                    );
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $locations,
                'message' => 'Available locations retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving available locations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock levels for a location.
     */
    public function updateStock(Request $request, WarehouseLocation $location): JsonResponse
    {
        try {
            $request->validate([
                'quantity_change' => 'required|numeric',
                'weight_change' => 'nullable|numeric|default:0',
            ]);

            DB::beginTransaction();

            $this->locationService->updateStockLevels(
                $location,
                $request->quantity_change,
                $request->weight_change ?? 0
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $location->fresh(),
                'message' => 'Location stock levels updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating location stock levels: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location stock levels',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get locations needing inventory check.
     */
    public function getNeedingCheck(Request $request): JsonResponse
    {
        try {
            $locations = WarehouseLocation::with(['warehouse.store', 'warehouse.branch'])
                ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
                ->active()
                ->get()
                ->filter(fn($location) => $location->needsInventoryCheck())
                ->values();

            return response()->json([
                'success' => true,
                'data' => $locations,
                'message' => 'Locations needing inventory check retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving locations needing check: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve locations needing check',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
