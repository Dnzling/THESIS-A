<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Supply;
use App\Models\ProductCatalog\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplyController extends Controller
{
    private function getUserContext(): array
    {
        return [
            'store_id' => auth()->user()->store_id,
            'branch_id' => auth()->user()->branch_id,
        ];
    }

    private function resolveDefaultCategoryId(int $storeId): ?int
    {
        return Category::query()
            ->where('store_id', $storeId)
            ->value('id');
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $query = Supply::with(['category', 'assets'])
                ->where('store_id', $context['store_id'])
                ->where('is_active', true);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%")
                        ->orWhere('unit_of_measurement', 'like', "%{$search}%");
                });
            }

            $supplies = $query->orderBy('product_name')->paginate($request->get('per_page', 15));

            return response()->json(['success' => true, 'data' => $supplies]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch supplies', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'unit_of_measurement' => 'nullable|string|max:50',
                'unit_cost' => 'required|numeric|min:0',
                'supplier_name' => 'nullable|string|max:255',
                'initial_stock' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();

            $defaultCategoryId = $this->resolveDefaultCategoryId($context['store_id']);
            $payload = [
                'store_id' => $context['store_id'],
                'product_name' => $validated['product_name'],
                'sku' => $request->input('sku') ?: 'SUP-' . strtoupper(Str::random(6)) . '-' . now()->format('YmdHis'),
                'product_type' => 'supply',
                'base_price' => (float) $validated['unit_cost'],
                'cost_price' => (float) $validated['unit_cost'],
                'is_active' => true,
                'unit_of_measurement' => $validated['unit_of_measurement'] ?? null,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'initial_stock' => $validated['initial_stock'] ?? 0,
                'created_by' => auth()->id(),
            ];

            if ($defaultCategoryId) {
                $payload['category_id'] = $defaultCategoryId;
            }

            $supply = Supply::create($payload);

            DB::commit();

            return response()->json(['success' => true, 'data' => $supply->load(['category']), 'message' => 'Supply created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create supply', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $supply = Supply::where('store_id', $context['store_id'])->findOrFail($id);
            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'unit_of_measurement' => 'nullable|string|max:50',
                'unit_cost' => 'required|numeric|min:0',
                'supplier_name' => 'nullable|string|max:255',
                'initial_stock' => 'nullable|numeric|min:0',
            ]);

            $payload = [
                'product_name' => $validated['product_name'],
                'base_price' => (float) $validated['unit_cost'],
                'cost_price' => (float) $validated['unit_cost'],
                'unit_of_measurement' => $validated['unit_of_measurement'] ?? null,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'initial_stock' => $validated['initial_stock'] ?? 0,
                'updated_by' => auth()->id(),
            ];

            if ($request->filled('sku')) {
                $payload['sku'] = $request->input('sku');
            }

            $supply->update($payload);

            return response()->json(['success' => true, 'data' => $supply->fresh()->load(['category']), 'message' => 'Supply updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update supply', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            $supply = Supply::where('store_id', $context['store_id'])->findOrFail($id);
            $supply->delete();

            return response()->json(['success' => true, 'message' => 'Supply deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete supply', 'error' => $e->getMessage()], 500);
        }
    }
}
