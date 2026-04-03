<?php

namespace App\Http\Controllers\Api\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Logistics\DeliveryZone;
use App\Models\Logistics\DeliveryZoneRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryZoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DeliveryZone::query()->with(['rates' => fn ($q) => $q->orderBy('min_distance_km')]);
        $this->applyTenantScope($request, $query);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $zones = $query->orderBy('name')->paginate((int) $request->input('per_page', 20));
        return response()->json(['success' => true, 'data' => $zones]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = DeliveryZone::query()->with(['rates' => fn ($q) => $q->orderBy('min_distance_km')]);
        $this->applyTenantScope($request, $query);
        $zone = $query->findOrFail($id);

        return response()->json(['success' => true, 'data' => $zone]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = $this->resolveStoreId($request);
        $branchId = $this->resolveBranchId($request);

        if (!$storeId && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }
        if (!$branchId && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'No branch assigned.'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'service_areas' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        $zone = DeliveryZone::create([
            'store_id' => $storeId ?: (int) $user->store_id,
            'branch_id' => $branchId ?: (int) ($request->input('branch_id') ?? 0),
            'name' => trim((string) $validated['name']),
            'service_areas' => $validated['service_areas'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Zone created.', 'data' => $zone], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $query = DeliveryZone::query();
        $this->applyTenantScope($request, $query);
        $zone = $query->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'service_areas' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        $zone->update([
            'name' => trim((string) $validated['name']),
            'service_areas' => $validated['service_areas'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? $zone->is_active),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Zone updated.', 'data' => $zone->fresh('rates')]);
    }

    public function rates(Request $request, int $zoneId): JsonResponse
    {
        $zoneQuery = DeliveryZone::query();
        $this->applyTenantScope($request, $zoneQuery);
        $zone = $zoneQuery->findOrFail($zoneId);

        $rates = $zone->rates()->orderBy('min_distance_km')->get();
        return response()->json(['success' => true, 'data' => $rates]);
    }

    public function addRate(Request $request, int $zoneId): JsonResponse
    {
        $zoneQuery = DeliveryZone::query();
        $this->applyTenantScope($request, $zoneQuery);
        $zone = $zoneQuery->findOrFail($zoneId);

        $validated = $request->validate([
            'min_distance_km' => 'required|numeric|min:0',
            'max_distance_km' => 'nullable|numeric|min:0',
            'min_weight_kg' => 'required|numeric|min:0',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'base_fee' => 'required|numeric|min:0',
            'per_km_fee' => 'required|numeric|min:0',
            'per_kg_fee' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'nullable|boolean',
        ]);

        $rate = DeliveryZoneRate::create([
            'zone_id' => $zone->id,
            'min_distance_km' => $validated['min_distance_km'],
            'max_distance_km' => $validated['max_distance_km'] ?? null,
            'min_weight_kg' => $validated['min_weight_kg'],
            'max_weight_kg' => $validated['max_weight_kg'] ?? null,
            'base_fee' => $validated['base_fee'],
            'per_km_fee' => $validated['per_km_fee'],
            'per_kg_fee' => $validated['per_kg_fee'],
            'currency' => strtoupper((string) ($validated['currency'] ?? 'PHP')),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json(['success' => true, 'message' => 'Rate added.', 'data' => $rate], 201);
    }

    public function updateRate(Request $request, int $zoneId, int $rateId): JsonResponse
    {
        $zoneQuery = DeliveryZone::query();
        $this->applyTenantScope($request, $zoneQuery);
        $zone = $zoneQuery->findOrFail($zoneId);

        $rate = DeliveryZoneRate::query()->where('zone_id', $zone->id)->findOrFail($rateId);

        $validated = $request->validate([
            'min_distance_km' => 'required|numeric|min:0',
            'max_distance_km' => 'nullable|numeric|min:0',
            'min_weight_kg' => 'required|numeric|min:0',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'base_fee' => 'required|numeric|min:0',
            'per_km_fee' => 'required|numeric|min:0',
            'per_kg_fee' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'nullable|boolean',
        ]);

        $rate->update([
            ...$validated,
            'currency' => strtoupper((string) ($validated['currency'] ?? $rate->currency ?? 'PHP')),
            'is_active' => (bool) ($validated['is_active'] ?? $rate->is_active),
        ]);

        return response()->json(['success' => true, 'message' => 'Rate updated.', 'data' => $rate]);
    }

    public function deleteRate(Request $request, int $zoneId, int $rateId): JsonResponse
    {
        $zoneQuery = DeliveryZone::query();
        $this->applyTenantScope($request, $zoneQuery);
        $zone = $zoneQuery->findOrFail($zoneId);

        $rate = DeliveryZoneRate::query()->where('zone_id', $zone->id)->findOrFail($rateId);
        $rate->delete();

        return response()->json(['success' => true, 'message' => 'Rate deleted.']);
    }

    private function applyTenantScope(Request $request, $query): void
    {
        $user = $request->user();

        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
            if ($user->employee?->branch_id) {
                $query->where('branch_id', (int) $user->employee->branch_id);
            }
            return;
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
    }

    private function resolveStoreId(Request $request): ?int
    {
        $user = $request->user();
        if ($user->hasRole('super_admin') && $request->filled('store_id')) {
            return (int) $request->input('store_id');
        }
        return $user->store_id ? (int) $user->store_id : null;
    }

    private function resolveBranchId(Request $request): ?int
    {
        $user = $request->user();
        if ($user->hasRole('super_admin') && $request->filled('branch_id')) {
            return (int) $request->input('branch_id');
        }
        return $user->employee?->branch_id ? (int) $user->employee->branch_id : null;
    }
}

