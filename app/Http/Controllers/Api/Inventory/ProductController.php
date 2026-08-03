<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
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

    private function resolveDefaultCategoryId(int $storeId): ?int
    {
        return Category::query()
            ->where('store_id', $storeId)
            ->value('id');
    }

    private function generateSku(string $productType, int $storeId): string
    {
        $prefix = match ($productType) {
            'raw_material' => 'RM',
            'supply' => 'SP',
            default => 'FG',
        };

        return $prefix . '-' . strtoupper(Str::random(4)) . '-' . now()->format('YmdHis') . '-' . $storeId;
    }

    private function buildProductPayload(array $data, int $storeId): array
    {
        $productType = $data['product_type'] ?? 'finished_good';
        $basePrice = $data['base_price'] ?? $data['unit_cost'] ?? 0;
        $costPrice = $data['cost_price'] ?? $data['unit_cost'] ?? null;

        $payload = [
            'store_id' => $storeId,
            'product_name' => trim((string) ($data['product_name'] ?? '')),
            'sku' => $data['sku'] ?? $this->generateSku($productType, $storeId),
            'description' => $data['description'] ?? null,
            'product_type' => $productType,
            'base_price' => (float) $basePrice,
            'cost_price' => $costPrice !== null ? (float) $costPrice : null,
            'is_active' => $data['is_active'] ?? true,
            'unit_of_measurement' => $data['unit_of_measurement'] ?? null,
            'supplier_name' => $data['supplier_name'] ?? null,
            'initial_stock' => $data['initial_stock'] ?? null,
            'created_by' => auth()->id(),
        ];

        if (!empty($data['category_id'])) {
            $payload['category_id'] = $data['category_id'];
        } else {
            $defaultCategoryId = $this->resolveDefaultCategoryId($storeId);
            if ($defaultCategoryId) {
                $payload['category_id'] = $defaultCategoryId;
            }
        }

        return $payload;
    }

    /**
     * Display products available in inventory
     * GET /api/inventory/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $query = Product::with([
                    'category',
                    'variations',
                    'assets',
                    'inventory' => function ($q) use ($context) {
                        if (!empty($context['branch_id'])) {
                            $q->where('branch_id', $context['branch_id']);
                        }
                    },
                ])
                ->withCount('variations')
                ->where('store_id', $context['store_id'])
                ->where('is_active', true);

            // Filters
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('product_type')) {
                $query->byProductType($request->product_type);
            }

            if ($request->boolean('available_only', false)) {
                $query->availableInBranch($context['branch_id']);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->has('has_inventory')) {
                $query->whereHas('inventory', function ($q) use ($context) {
                    $q->where('branch_id', $context['branch_id'])
                      ->where('quantity_on_hand', '>', 0);
                });
            }

            $products = $query->orderBy('product_name')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show single product with inventory details
     * GET /api/inventory/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $product = Product::with([
                'category',
                'variations',
                'assets',
                'inventory' => function ($query) use ($context) {
                    $query->where('branch_id', $context['branch_id']);
                }
            ])
            ->where('store_id', $context['store_id'])
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create new product
     * POST /api/inventory/products
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'sku' => 'nullable|string|max:100|unique:products,sku,NULL,id,store_id,' . $context['store_id'],
                'description' => 'nullable|string',
                'category_id' => 'nullable|exists:product_categories,id',
                'product_type' => 'nullable|in:raw_material,finished_good,supply',
                'base_price' => 'nullable|numeric|min:0',
                'unit_cost' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'unit_of_measurement' => 'nullable|string|max:50',
                'supplier_name' => 'nullable|string|max:255',
                'initial_stock' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
                'variations' => 'nullable|array',
                'variations.*.variation_name' => 'required|string|max:255',
                'variations.*.sku' => 'required|string|max:100',
                'variations.*.price_modifier' => 'numeric',
            ]);

            DB::beginTransaction();

            $product = Product::create($this->buildProductPayload($validated, $context['store_id']));

            // Create variations if provided
            if (!empty($validated['variations'])) {
                foreach ($validated['variations'] as $variationData) {
                    ProductVariation::create([
                        'product_id' => $product->id,
                        'variation_name' => $variationData['variation_name'],
                        'sku' => $variationData['sku'],
                        'price_modifier' => $variationData['price_modifier'] ?? 0,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load(['category', 'variations']),
                'message' => 'Product created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update product
     * PUT /api/inventory/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $product = Product::where('store_id', $context['store_id'])->findOrFail($id);

            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'sku' => 'nullable|string|max:100|unique:products,sku,' . $id . ',id,store_id,' . $context['store_id'],
                'description' => 'nullable|string',
                'category_id' => 'nullable|exists:product_categories,id',
                'product_type' => 'nullable|in:raw_material,finished_good,supply',
                'base_price' => 'nullable|numeric|min:0',
                'unit_cost' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'unit_of_measurement' => 'nullable|string|max:50',
                'supplier_name' => 'nullable|string|max:255',
                'initial_stock' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            DB::beginTransaction();

            $incomingBasePrice = (float) ($validated['base_price'] ?? $validated['unit_cost'] ?? $product->base_price ?? 0);
            $currentBasePrice = (float) $product->base_price;
            $isPriceChanged = bccomp((string) $incomingBasePrice, (string) $currentBasePrice, 2) !== 0;

            $updates = [
                'product_name' => $validated['product_name'],
                'sku' => $validated['sku'] ?? $product->sku,
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'product_type' => $validated['product_type'] ?? $product->product_type ?? 'finished_good',
                'base_price' => (float) ($validated['base_price'] ?? $validated['unit_cost'] ?? $product->base_price ?? 0),
                'cost_price' => $validated['cost_price'] ?? $validated['unit_cost'] ?? $product->cost_price,
                'is_active' => $validated['is_active'] ?? $product->is_active,
                'unit_of_measurement' => $validated['unit_of_measurement'] ?? $product->unit_of_measurement,
                'supplier_name' => $validated['supplier_name'] ?? $product->supplier_name,
                'initial_stock' => $validated['initial_stock'] ?? $product->initial_stock,
                'updated_by' => auth()->id(),
            ];

            if ($isPriceChanged) {
                // Inventory price updates must pass Finance approval first.
                // Keep current base_price unchanged until finance approves.
                $updates['pending_base_price'] = $incomingBasePrice;
                $updates['price_approval_status'] = 'pending';
                $updates['price_proposed_by'] = auth()->id();
                $updates['price_proposed_at'] = now();
                $updates['price_approved_by'] = null;
                $updates['price_approved_at'] = null;
                $updates['price_rejected_by'] = null;
                $updates['price_rejected_at'] = null;
                $updates['price_approval_notes'] = 'Price update requested from Inventory All Products';
            }

            $product->update($updates);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load(['category', 'variations']),
                'message' => $isPriceChanged
                    ? 'Price change submitted for finance approval. Live price will update after approval.'
                    : 'Product updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete product
     * DELETE /api/inventory/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $product = Product::where('store_id', $context['store_id'])->findOrFail($id);

            // Check if product has inventory
            $hasInventory = BranchInventory::where('product_id', $id)
                ->where('store_id', $context['store_id'])
                ->where('quantity_on_hand', '>', 0)
                ->exists();

            if ($hasInventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with existing inventory',
                ], 422);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product variations
     * GET /api/inventory/products/{id}/variations
     */
    public function getVariations(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $product = Product::where('store_id', $context['store_id'])->findOrFail($id);

            $variations = ProductVariation::where('product_id', $id)
                ->with(['inventory' => function ($query) use ($context) {
                    $query->where('branch_id', $context['branch_id']);
                }])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $variations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch variations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product stock history
     * GET /api/inventory/products/{id}/stock-history
     */
    public function getStockHistory(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $product = Product::where('store_id', $context['store_id'])->findOrFail($id);

            $query = InventoryTransaction::with(['adjustment', 'transfer', 'createdBy'])
                ->where('store_id', $context['store_id'])
                ->where('product_id', $id);

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            } else {
                $query->where('branch_id', $context['branch_id']);
            }

            $history = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stock history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
