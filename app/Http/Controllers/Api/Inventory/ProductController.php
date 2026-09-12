<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
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
        $user = auth()->user();
        $storeId = (int) ($user?->store_id ?? 0);
        $branchId = (int) ($user?->branch_id ?? 0);

        // Some store-level users are not assigned to a branch. Inventory still
        // needs a concrete branch, so use the store's main active branch first.
        if ($storeId && (!$branchId || !Branch::query()
            ->where('id', $branchId)
            ->where('store_id', $storeId)
            ->exists())) {
            $branchId = (int) (Branch::query()
                ->where('store_id', $storeId)
                ->where('status', 'active')
                ->orderByDesc('is_main_branch')
                ->orderBy('id')
                ->value('id') ?? 0);
        }

        return [
            'store_id' => $storeId,
            'branch_id' => $branchId,
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

        return $prefix . '-' . strtoupper(Str::random(4)) . '-' . now()->format('YmdHis');
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

            if (!$context['store_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not assigned to a store.',
                ], 422);
            }

            if (!$context['branch_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active branch is configured for this store. Assign a branch before creating inventory products.',
                ], 422);
            }
            if ($request->filled('branch_id')) {
                $context['branch_id'] = (int) $request->branch_id;
            }

            $query = Product::select([
                    'id',
                    'store_id',
                    'sku',
                    'product_name',
                    'description',
                    'category_id',
                    'product_type',
                    'base_price',
                    'cost_price',
                    'cost_price as inventory_cost_price',
                    'unit_of_measurement',
                    'supplier_name',
                    'initial_stock',
                    'is_active',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'category:id,category_name',
                    'suppliers:id,supplier_code,supplier_name,company_name',
                    'assets:id,product_id,asset_type,file_name,file_path,is_primary,display_order',
                    'inventory' => function ($q) use ($context) {
                        $q->select([
                            'id',
                            'product_id',
                            'branch_id',
                            'quantity_available',
                            'reorder_point',
                        ]);
                        if (!empty($context['branch_id'])) {
                            $q->where('branch_id', $context['branch_id']);
                        }
                    },
                ])
                ->where('store_id', $context['store_id'])
                ->where('is_active', true);

            // Filters
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('product_type')) {
                if ($request->product_type === 'others') {
                    $query->whereNotIn('product_type', ['finished_good', 'supply', 'raw_material']);
                } else {
                    $query->byProductType($request->product_type);
                }
            }

            if ($request->filled('status')) {
                $status = (string) $request->status;
                if ($status === 'no_supplier') {
                    $query->where(function ($q) {
                        $q->whereNull('supplier_name')
                          ->orWhere('supplier_name', '');
                    })->whereDoesntHave('suppliers');
                } elseif (in_array($status, ['1', '0', 'true', 'false'], true)) {
                    $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
                }
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

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = [
                'product_name' => 'product_name',
                'base_price' => 'base_price',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ];

            if ($sortBy === 'reorder_point') {
                $query->leftJoin('branch_inventory as bi', function ($join) use ($context) {
                    $join->on('bi.product_id', '=', 'products.id');
                    if (!empty($context['branch_id'])) {
                        $join->where('bi.branch_id', '=', (int) $context['branch_id']);
                    }
                })
                ->select('products.*')
                ->orderByRaw('COALESCE(bi.reorder_point, 0) ' . $sortOrder);
            } else {
                $query->orderBy($allowedSorts[$sortBy] ?? 'created_at', $sortOrder);
            }

            $products = $query
                ->paginate($request->get('per_page', 15));

            $products->getCollection()->transform(function (Product $product) {
                // cost_price is hidden on the Product model for general API
                // responses. Inventory screens need the raw cost through a
                // deliberately named, inventory-only alias.
                $product->setAttribute(
                    'inventory_cost_price',
                    $product->getRawOriginal('cost_price')
                );

                $supplierNames = $product->suppliers
                    ->map(fn ($supplier) => $supplier->supplier_name ?: $supplier->company_name)
                    ->filter()
                    ->unique()
                    ->values();

                $product->setAttribute('supplier_names', $supplierNames);

                // Keep paginated index rows compatible with screens that read the
                // legacy flat field while the relationship remains authoritative.
                if ($supplierNames->isNotEmpty()) {
                    $product->setAttribute('supplier_name', $supplierNames->implode(', '));
                }

                return $product;
            });

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
                'suppliers:id,supplier_code,supplier_name,company_name',
                'tags:id,tag_name',
                'variations.inventory' => function ($query) use ($context) {
                    $query->where('branch_id', $context['branch_id']);
                },
                'variations.custom3dModel',
                'variations.customImage',
                'assets',
                'inventory' => function ($query) use ($context) {
                    $query->where('branch_id', $context['branch_id']);
                }
            ])
            ->where('store_id', $context['store_id'])
            ->findOrFail($id);

            // The Product model protects cost_price through an accessor. Return a scoped
            // inventory field so product editing can still load the saved cost.
            $product->setAttribute('inventory_cost_price', $product->getRawOriginal('cost_price'));
            $product->variations->each(function (ProductVariation $variation) use ($product) {
                $finalPrice = $variation->discounted_price
                    ?? $variation->base_price
                    ?? ((float) ($product->discounted_price ?? $product->base_price ?? 0) + (float) $variation->price_adjustment);
                $variation->setAttribute('final_price', round((float) $finalPrice, 2));
            });
            $employee = Employee::with('user:id,fname,lname')->find($product->getRawOriginal('created_by'));
            $creatorName = trim(($employee?->user?->fname ?? '') . ' ' . ($employee?->user?->lname ?? ''));
            $product->setAttribute('created_by_name', $creatorName !== '' ? $creatorName : null);

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
                'category_id' => 'nullable|exists:categories,id',
                'product_type' => 'nullable|string|max:100',
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
                'product_image' => 'nullable|image|max:5120',
            ]);

            $initialStock = (int) ($validated['initial_stock'] ?? 0);
            $branchId = (int) $context['branch_id'];
            $employeeId = Employee::query()
                ->where('user_id', auth()->id())
                ->value('id');

            if (!$employeeId) {
                $employeeId = Employee::query()
                    ->where('store_id', $context['store_id'])
                    ->value('id');
            }

            if (!$employeeId) {
                $employeeId = config('app.system_employee_id');
            }

            if ($initialStock > 0 && !$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No employee record is available to record opening stock.',
                ], 422);
            }

            DB::beginTransaction();

            $product = Product::create($this->buildProductPayload($validated, $context['store_id']));

            $inventory = BranchInventory::query()->firstOrCreate(
                [
                    'store_id' => $context['store_id'],
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'variation_id' => null,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                    'quantity_damaged' => 0,
                    'quantity_incoming' => 0,
                    'reorder_point' => (int) ($product->reorder_point ?? 0),
                    'reorder_quantity' => 0,
                    'maximum_stock' => 0,
                    'safety_stock' => 0,
                    'stock_status' => 'out_of_stock',
                ]
            );

            if ($initialStock > 0) {
                $inventory->quantity_on_hand = $initialStock;
                $inventory->quantity_available = $initialStock;
                $inventory->stock_status = $initialStock <= (int) ($inventory->reorder_point ?? 0)
                    ? 'low_stock'
                    : 'in_stock';
                $inventory->save();

                InventoryTransaction::create([
                    'transaction_number' => 'TXN-' . strtoupper(Str::random(10)),
                    'store_id' => $context['store_id'],
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'variation_id' => null,
                    'transaction_type' => 'adjustment',
                    'quantity_before' => 0,
                    'quantity_change' => $initialStock,
                    'quantity_after' => $initialStock,
                    'notes' => 'Opening stock seeded during product creation',
                    'unit_cost' => $product->cost_price ?? 0,
                    'total_value' => $initialStock * (float) ($product->cost_price ?? 0),
                    'requires_approval' => false,
                    'approval_status' => 'not_required',
                    'created_by' => (int) $employeeId,
                    'transaction_date' => now(),
                ]);
            }

            if ($request->hasFile('product_image')) {
                $file = $request->file('product_image');
                $path = $file->store("stores/{$context['store_id']}/products/{$product->id}/images", 'public');

                ProductAsset::create([
                    'store_id' => $context['store_id'],
                    'product_id' => $product->id,
                    'asset_type' => 'Image_Main',
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size_kb' => round($file->getSize() / 1024),
                    'mime_type' => $file->getMimeType(),
                    'is_primary' => true,
                    'display_order' => 0,
                    'alt_text' => $validated['product_name'] ?? null,
                ]);
            }

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
                'data' => $product->load(['category', 'variations', 'assets', 'inventory']),
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
                'category_id' => 'nullable|exists:categories,id',
                'product_type' => 'nullable|string|max:100',
                'base_price' => 'nullable|numeric|min:0',
                'unit_cost' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'unit_of_measurement' => 'nullable|string|max:50',
                'supplier_name' => 'nullable|string|max:255',
                'initial_stock' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
                'product_image' => 'nullable|image|max:5120',
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

            if ($request->hasFile('product_image')) {
                $file = $request->file('product_image');
                $path = $file->store("stores/{$context['store_id']}/products/{$product->id}/images", 'public');

                ProductAsset::where('store_id', $context['store_id'])
                    ->where('product_id', $product->id)
                    ->where('asset_type', 'Image_Main')
                    ->update(['is_primary' => false]);

                ProductAsset::create([
                    'store_id' => $context['store_id'],
                    'product_id' => $product->id,
                    'asset_type' => 'Image_Main',
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size_kb' => round($file->getSize() / 1024),
                    'mime_type' => $file->getMimeType(),
                    'is_primary' => true,
                    'display_order' => 0,
                    'alt_text' => $validated['product_name'] ?? null,
                ]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load(['category', 'variations', 'assets']),
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
