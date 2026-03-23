<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceDeliveryVehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);

        if (!$storeId && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
        }

        $query = EcommerceDeliveryVehicle::query()
            ->where('store_id', $user->hasRole('super_admin') && $request->filled('store_id')
                ? (int) $request->input('store_id')
                : $storeId);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_name', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $vehicles = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $vehicles]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);

        if (!$storeId && !$user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'No store assigned.'], 422);
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

        $exists = EcommerceDeliveryVehicle::query()
            ->where('store_id', $storeId)
            ->whereRaw('UPPER(plate_number) = ?', [$normalizedPlate])
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Plate number already exists for this store.'], 422);
        }

        $vehicle = EcommerceDeliveryVehicle::create([
            ...$validated,
            'store_id' => $storeId,
            'plate_number' => $normalizedPlate,
            'is_active' => $validated['status'] === 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Vehicle registered successfully.', 'data' => $vehicle], 201);
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

        $exists = EcommerceDeliveryVehicle::query()
            ->where('store_id', $vehicle->store_id)
            ->whereRaw('UPPER(plate_number) = ?', [$normalizedPlate])
            ->where('id', '!=', $vehicle->id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Plate number already exists for this store.'], 422);
        }

        $vehicle->update([
            ...$validated,
            'plate_number' => $normalizedPlate,
            'is_active' => $validated['status'] === 'active',
            'updated_by' => $user->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Vehicle updated successfully.', 'data' => $vehicle->fresh()]);
    }

    private function resolveVehicle(Request $request, int $id): EcommerceDeliveryVehicle
    {
        $user = $request->user();
        $query = EcommerceDeliveryVehicle::query();

        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', $user->store_id);
        }

        return $query->findOrFail($id);
    }
}

