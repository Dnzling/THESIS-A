<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    private const CACHE_TTL = 300;
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_PER_PAGE = 20;

    private const ALLOWED_SORT_FIELDS = ['created_at', 'vehicle_name', 'plate_number', 'status', 'vehicle_type'];
    private const SEARCHABLE_FIELDS = ['vehicle_name', 'plate_number', 'brand', 'model'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = $this->getStoreId($user, $request);
        if ($storeId === null) {
            return $this->errorResponse('No store assigned.', 422);
        }

        $branchId = $this->resolveBranchId($request);
        if (!$branchId && !$user->hasRole('super_admin')) {
            return $this->errorResponse('No branch assigned.', 422);
        }

        $query = $this->buildIndexQuery($storeId, $branchId, $user, $request);
        $this->applySearchFilter($query, $request);
        $this->applyStatusFilter($query, $request);
        $this->applySorting($query, $request);

        $perPage = $this->getPerPage($request);
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

        $branchId = $this->resolveBranchId($request);
        if (!$branchId && !$user->hasRole('super_admin')) {
            return $this->errorResponse('No branch assigned.', 422);
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
        $duplicateCacheKey = "logistics_vehicle_plate_exists:{$storeId}:{$normalizedPlate}";

        if (Cache::remember($duplicateCacheKey, 60, function () use ($storeId, $normalizedPlate) {
            return EcommerceDeliveryVehicle::where('store_id', $storeId)
                ->where('plate_number', $normalizedPlate)
                ->exists();
        })) {
            return $this->errorResponse('Plate number already exists for this store.', 422);
        }

        $vehicle = DB::transaction(function () use ($validated, $storeId, $branchId, $user, $normalizedPlate) {
            return EcommerceDeliveryVehicle::create([
                ...$validated,
                'store_id' => $storeId,
                'branch_id' => $branchId,
                'plate_number' => $normalizedPlate,
                'is_active' => $validated['status'] === 'active',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });

        $this->clearVehicleCache($storeId);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle registered successfully.',
            'data' => $vehicle,
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
        $exists = EcommerceDeliveryVehicle::where('store_id', $vehicle->store_id)
            ->where('plate_number', $normalizedPlate)
            ->where('id', '!=', $vehicle->id)
            ->exists();
        if ($exists) {
            return $this->errorResponse('Plate number already exists for this store.', 422);
        }

        $vehicle = DB::transaction(function () use ($vehicle, $validated, $normalizedPlate, $user) {
            $vehicle->update([
                ...$validated,
                'plate_number' => $normalizedPlate,
                'is_active' => $validated['status'] === 'active',
                'updated_by' => $user->id,
            ]);
            return $vehicle->fresh();
        });

        $this->clearVehicleCache((int) $vehicle->store_id);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully.',
            'data' => $vehicle,
        ]);
    }

    private function getStoreId($user, Request $request): ?int
    {
        if (!$user->hasRole('super_admin')) {
            return $user->store_id ? (int) $user->store_id : null;
        }

        return $request->filled('store_id') ? (int) $request->input('store_id') : ($user->store_id ? (int) $user->store_id : null);
    }

    private function resolveBranchId(Request $request): ?int
    {
        $user = $request->user();
        if ($user->hasRole('super_admin') && $request->filled('branch_id')) {
            return (int) $request->input('branch_id');
        }
        return $user->employee?->branch_id ? (int) $user->employee->branch_id : null;
    }

    private function buildIndexQuery(int $storeId, ?int $branchId, $user, Request $request): Builder
    {
        $query = EcommerceDeliveryVehicle::query()
            ->select([
                'id',
                'store_id',
                'branch_id',
                'vehicle_name',
                'vehicle_type',
                'plate_number',
                'brand',
                'model',
                'color',
                'capacity_kg',
                'max_orders_per_trip',
                'status',
                'is_active',
                'notes',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ])
            ->where('store_id', $storeId);

        if (!$user->hasRole('super_admin')) {
            $query->where('branch_id', $branchId);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function applySearchFilter($query, Request $request): void
    {
        if (!$request->filled('search')) return;

        $search = trim((string) $request->input('search'));
        $query->where(function ($q) use ($search) {
            foreach (self::SEARCHABLE_FIELDS as $field) {
                $q->orWhere($field, 'LIKE', "{$search}%");
            }
        });
    }

    private function applyStatusFilter($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
    }

    private function applySorting($query, Request $request): void
    {
        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortBy = in_array($sortBy, self::ALLOWED_SORT_FIELDS, true) ? $sortBy : 'created_at';
        $sortOrder = $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);
        if ($sortBy !== 'id') {
            $query->orderBy('id', $sortOrder);
        }
    }

    private function getPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);
        return min(max($perPage, 1), self::MAX_PER_PAGE);
    }

    private function resolveVehicle(Request $request, int $id): EcommerceDeliveryVehicle
    {
        $user = $request->user();

        $query = EcommerceDeliveryVehicle::query()
            ->select([
                'id',
                'store_id',
                'branch_id',
                'vehicle_name',
                'vehicle_type',
                'plate_number',
                'brand',
                'model',
                'color',
                'capacity_kg',
                'max_orders_per_trip',
                'status',
                'is_active',
                'notes',
                'created_at',
                'updated_at',
            ]);

        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
            $branchId = $this->resolveBranchId($request);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }

        return $query->findOrFail($id);
    }

    private function clearVehicleCache(int $storeId): void
    {
        Cache::delete("vehicles:index:{$storeId}:*");
        Cache::delete("logistics_vehicle_plate_exists:{$storeId}:*");
    }

    private function errorResponse(string $message, int $statusCode = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $statusCode);
    }
}

