<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
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
     * Display units
     * GET /api/inventory/units
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $query = Unit::where('store_id', $context['store_id'])
                ->with(['baseUnit', 'creator']);

            // Filters
            if ($request->has('unit_type')) {
                $query->where('unit_type', $request->unit_type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('unit_name', 'like', "%{$search}%")
                      ->orWhere('unit_code', 'like', "%{$search}%")
                      ->orWhere('unit_symbol', 'like', "%{$search}%");
                });
            }

            $units = $query->orderBy('sort_order')
                ->orderBy('unit_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $units,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch units',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new unit
     * POST /api/inventory/units
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $validated = $request->validate([
                'unit_name' => 'required|string|max:100',
                'unit_code' => 'required|string|max:20|unique:units,unit_code,NULL,id,store_id,' . $context['store_id'],
                'unit_symbol' => 'nullable|string|max:10',
                'description' => 'nullable|string',
                'unit_type' => 'required|in:weight,volume,length,area,quantity,time,other',
                'conversion_factor' => 'numeric|min:0.000001',
                'base_unit_id' => 'nullable|exists:units,id',
                'is_base_unit' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            // If this is a base unit, it can't have a base_unit_id
            if ($validated['is_base_unit'] && $validated['base_unit_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base units cannot have a base unit reference',
                ], 422);
            }

            // If not a base unit, base_unit_id is required
            if (!$validated['is_base_unit'] && !$validated['base_unit_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non-base units must have a base unit reference',
                ], 422);
            }

            // Validate base unit belongs to same store
            if ($validated['base_unit_id']) {
                $baseUnit = Unit::where('store_id', $context['store_id'])
                    ->findOrFail($validated['base_unit_id']);
            }

            $unit = Unit::create([
                'store_id' => $context['store_id'],
                'unit_name' => $validated['unit_name'],
                'unit_code' => $validated['unit_code'],
                'unit_symbol' => $validated['unit_symbol'] ?? null,
                'description' => $validated['description'] ?? null,
                'unit_type' => $validated['unit_type'],
                'conversion_factor' => $validated['conversion_factor'] ?? 1,
                'base_unit_id' => $validated['base_unit_id'] ?? null,
                'is_base_unit' => $validated['is_base_unit'] ?? false,
                'sort_order' => $validated['sort_order'] ?? 0,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $unit->load(['baseUnit', 'creator']),
                'message' => 'Unit created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create unit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update unit
     * PUT /api/inventory/units/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $unit = Unit::where('store_id', $context['store_id'])->findOrFail($id);

            $validated = $request->validate([
                'unit_name' => 'required|string|max:100',
                'unit_code' => 'required|string|max:20|unique:units,unit_code,' . $id . ',id,store_id,' . $context['store_id'],
                'unit_symbol' => 'nullable|string|max:10',
                'description' => 'nullable|string',
                'unit_type' => 'required|in:weight,volume,length,area,quantity,time,other',
                'conversion_factor' => 'numeric|min:0.000001',
                'base_unit_id' => 'nullable|exists:units,id',
                'is_base_unit' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            // If this is a base unit, it can't have a base_unit_id
            if ($validated['is_base_unit'] && $validated['base_unit_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base units cannot have a base unit reference',
                ], 422);
            }

            // If not a base unit, base_unit_id is required
            if (!$validated['is_base_unit'] && !$validated['base_unit_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non-base units must have a base unit reference',
                ], 422);
            }

            // Validate base unit belongs to same store
            if ($validated['base_unit_id']) {
                $baseUnit = Unit::where('store_id', $context['store_id'])
                    ->findOrFail($validated['base_unit_id']);
            }

            // Prevent changing base unit if there are derived units
            if ($unit->is_base_unit && !$validated['is_base_unit'] && $unit->derivedUnits()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change base unit status when derived units exist',
                ], 422);
            }

            $unit->update([
                'unit_name' => $validated['unit_name'],
                'unit_code' => $validated['unit_code'],
                'unit_symbol' => $validated['unit_symbol'] ?? null,
                'description' => $validated['description'] ?? null,
                'unit_type' => $validated['unit_type'],
                'conversion_factor' => $validated['conversion_factor'] ?? $unit->conversion_factor,
                'base_unit_id' => $validated['base_unit_id'] ?? $unit->base_unit_id,
                'is_base_unit' => $validated['is_base_unit'] ?? $unit->is_base_unit,
                'sort_order' => $validated['sort_order'] ?? $unit->sort_order,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $unit->load(['baseUnit', 'creator']),
                'message' => 'Unit updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update unit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete unit
     * DELETE /api/inventory/units/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $unit = Unit::where('store_id', $context['store_id'])->findOrFail($id);

            // Check if unit is being used by products
            if ($unit->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete unit that is being used by products',
                ], 422);
            }

            // Check if unit has derived units
            if ($unit->derivedUnits()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete unit that has derived units',
                ], 422);
            }

            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete unit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
