<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EcommerceDeliveryVehicleController extends Controller
{
    // Cache keys and durations
    private const CACHE_TTL = 300; // 5 minutes
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_PER_PAGE = 20;
    
    // Allowed sort fields for security
    private const ALLOWED_SORT_FIELDS = ['created_at', 'vehicle_name', 'plate_number', 'status', 'vehicle_type'];
    
    // Searchable fields
    private const SEARCHABLE_FIELDS = ['vehicle_name', 'plate_number', 'brand', 'model'];
    
    public function index(Request $request): JsonResponse
    {
        // Early validation
        $user = $request->user();
        $storeId = $this->getStoreId($user, $request);
        
        if ($storeId === null) {
            return $this->errorResponse('No store assigned.', 422);
        }
        
        // Build query efficiently
        $query = $this->buildIndexQuery($storeId, $user, $request);
        
        // Apply filters
        $this->applySearchFilter($query, $request);
        $this->applyStatusFilter($query, $request);
        
        // Apply sorting with validation
        $this->applySorting($query, $request);
        
        // Get paginated results
        $perPage = $this->getPerPage($request);
        
        // Use cursor pagination for better performance with large datasets
        $vehicles = $request->boolean('cursor_pagination')
            ? $query->cursorPaginate($perPage)
            : $query->paginate($perPage);
        
        return response()->json(['success' => true, 'data' => $vehicles]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = $this->getStoreId($user, $request);
        
        if ($storeId === null) {
            return $this->errorResponse('No store assigned.', 422);
        }
        
        $validated = $request->validate([
            'vehicle_name' => 'required|string|max:120',
            'vehicle_type' => 'required|in:motorcycle,van,truck,car,other',
            'plate_number' => 'required|string|max:50',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'capacity_kg' => 'nullable|numeric|min:0',
            'max_orders_per_trip' => 'required|integer|min:1|max:999',
            'status' => 'required|in:active,maintenance,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $normalizedPlate = strtoupper(trim($validated['plate_number']));
        
        // Use cache for duplicate check to reduce DB hits
        $duplicateCacheKey = "vehicle_plate_exists:{$storeId}:{$normalizedPlate}";
        
        if (Cache::remember($duplicateCacheKey, 60, function () use ($storeId, $normalizedPlate) {
            return EcommerceDeliveryVehicle::where('store_id', $storeId)
                ->where('plate_number', $normalizedPlate)
                ->exists();
        })) {
            return $this->errorResponse('Plate number already exists for this store.', 422);
        }
        
        // Use database transaction for consistency
        $vehicle = DB::transaction(function () use ($validated, $storeId, $user, $normalizedPlate) {
            return EcommerceDeliveryVehicle::create([
                ...$validated,
                'store_id' => $storeId,
                'plate_number' => $normalizedPlate,
                'is_active' => $validated['status'] === 'active',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
        
        // Clear relevant cache
        $this->clearVehicleCache($storeId);
        
        return response()->json([
            'success' => true,
            'message' => 'Vehicle registered successfully.',
            'data' => $vehicle
        ], 201);
    }
    
    public function show(Request $request, int $id): JsonResponse
    {
        $vehicle = $this->resolveVehicle($request, $id);
        return response()->json(['success' => true, 'data' => $vehicle]);
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = $this->resolveVehicle($request, $id);
        $user = $request->user();
        
        $validated = $request->validate([
            'vehicle_name' => 'required|string|max:120',
            'vehicle_type' => 'required|in:motorcycle,van,truck,car,other',
            'plate_number' => 'required|string|max:50',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'capacity_kg' => 'nullable|numeric|min:0',
            'max_orders_per_trip' => 'required|integer|min:1|max:999',
            'status' => 'required|in:active,maintenance,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $normalizedPlate = strtoupper(trim($validated['plate_number']));
        
        // Optimized duplicate check with single query
        $exists = EcommerceDeliveryVehicle::where('store_id', $vehicle->store_id)
            ->where('plate_number', $normalizedPlate)
            ->where('id', '!=', $vehicle->id)
            ->exists();
        
        if ($exists) {
            return $this->errorResponse('Plate number already exists for this store.', 422);
        }
        
        // Use transaction for update
        DB::transaction(function () use ($vehicle, $validated, $user, $normalizedPlate) {
            $vehicle->update([
                ...$validated,
                'plate_number' => $normalizedPlate,
                'is_active' => $validated['status'] === 'active',
                'updated_by' => $user->id,
            ]);
        });
        
        // Clear cache
        $this->clearVehicleCache($vehicle->store_id);
        
        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully.',
            'data' => $vehicle->fresh(['createdBy', 'updatedBy']) // Eager load if needed
        ]);
    }
    
    /**
     * Get store ID with super_admin override
     */
    private function getStoreId($user, Request $request): ?int
    {
        if ($user->hasRole('super_admin') && $request->filled('store_id')) {
            return (int) $request->input('store_id');
        }
        
        return $user->store_id ? (int) $user->store_id : null;
    }
    
    /**
     * Build base query with optimized select
     */
    private function buildIndexQuery(int $storeId, $user, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = EcommerceDeliveryVehicle::query()
            ->select(['id', 'store_id', 'vehicle_name', 'vehicle_type', 'plate_number', 
                      'brand', 'model', 'color', 'capacity_kg', 'max_orders_per_trip', 
                      'status', 'is_active', 'notes', 'created_at', 'updated_at', 
                      'created_by', 'updated_by']) // Explicit select for performance
            ->where('store_id', $storeId);
        
        // Optionally eager load relationships only if needed
        if ($request->boolean('with_relations')) {
            $query->with([
                'createdBy:id,name',
                'updatedBy:id,name'
            ]);
        }
        
        return $query;
    }
    
    /**
     * Apply search filter with optimized LIKE queries
     */
    private function applySearchFilter($query, Request $request): void
    {
        if (!$request->filled('search')) {
            return;
        }
        
        $search = trim($request->input('search'));
        
        // Use whereFullText if available (MySQL 5.7+)
        if (config('database.default') === 'mysql' && $this->hasFullTextIndex()) {
            $query->whereFullText(self::SEARCHABLE_FIELDS, $search);
            return;
        }
        
        // Fallback to optimized LIKE queries
        $query->where(function ($q) use ($search) {
            foreach (self::SEARCHABLE_FIELDS as $field) {
                $q->orWhere($field, 'LIKE', "{$search}%"); // Prefix match is faster than full wildcard
            }
        });
    }
    
    /**
     * Apply status filter
     */
    private function applyStatusFilter($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
    }
    
    /**
     * Apply sorting with validation
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $sortBy = in_array($sortBy, self::ALLOWED_SORT_FIELDS) ? $sortBy : 'created_at';
        $sortOrder = $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortOrder);
        
        // Secondary sort for consistent pagination
        if ($sortBy !== 'id') {
            $query->orderBy('id', $sortOrder);
        }
    }
    
    /**
     * Get validated per page value
     */
    private function getPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);
        return min(max($perPage, 1), self::MAX_PER_PAGE);
    }
    
    /**
     * Resolve vehicle with proper authorization
     */
    private function resolveVehicle(Request $request, int $id): EcommerceDeliveryVehicle
    {
        $user = $request->user();
        
        $query = EcommerceDeliveryVehicle::query()
            ->select(['id', 'store_id', 'vehicle_name', 'vehicle_type', 'plate_number', 
                      'brand', 'model', 'color', 'capacity_kg', 'max_orders_per_trip', 
                      'status', 'is_active', 'notes', 'created_at', 'updated_at']);
        
        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
        }
        
        return $query->findOrFail($id);
    }
    
    /**
     * Clear vehicle-related cache
     */
    private function clearVehicleCache(int $storeId): void
    {
        $patterns = [
            "vehicles:index:{$storeId}:*",
            "vehicle_plate_exists:{$storeId}:*"
        ];
        
        foreach ($patterns as $pattern) {
            Cache::delete($pattern);
        }
        
        // If using Redis, you can use scan for pattern deletion
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }
    
    /**
     * Check if table has fulltext index
     */
    private function hasFullTextIndex(): bool
    {
        static $hasIndex = null;
        
        if ($hasIndex === null) {
            try {
                $indexes = DB::select('SHOW INDEX FROM ecommerce_delivery_vehicles WHERE Index_type = "FULLTEXT"');
                $hasIndex = count($indexes) > 0;
            } catch (\Exception $e) {
                $hasIndex = false;
            }
        }
        
        return $hasIndex;
    }
    
    /**
     * Standardized error response
     */
    private function errorResponse(string $message, int $statusCode = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $statusCode);
    }
}