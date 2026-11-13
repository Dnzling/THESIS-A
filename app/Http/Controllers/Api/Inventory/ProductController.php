<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\ReorderRule;
use App\Services\Inventory\ReorderSuggestionNotificationService;
use App\Services\Inventory\ReorderSuggestionService;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use App\Services\Core\PermissionService;
use App\Services\Inventory\ForecastPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        // Merchandising resolves store ownership through the employee profile
        // when users.store_id is not populated. Inventory must use the same
        // source so products created in Merchandising are visible here.
        $employee = null;
        if ($user && (!$storeId || !$branchId)) {
            $employee = Employee::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->first(['store_id', 'branch_id']);
            $storeId = $storeId ?: (int) ($employee?->store_id ?? 0);
            $branchId = $branchId ?: (int) ($employee?->branch_id ?? 0);
        }

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
        $discountedPrice = $data['discounted_price'] ?? null;
        if ($productType === 'finished_good' && $discountedPrice !== null && (float) $discountedPrice > (float) $basePrice) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discounted_price' => 'Discounted price cannot be greater than the selling price.',
            ]);
        }

        $payload = [
            'store_id' => $storeId,
            'product_name' => trim((string) ($data['product_name'] ?? '')),
            'sku' => $data['sku'] ?? $this->generateSku($productType, $storeId),
            'description' => $data['description'] ?? null,
            'product_type' => $productType,
            'brand' => $data['brand'] ?? null,
            'base_price' => (float) $basePrice,
            'length_cm' => $data['length_cm'] ?? null,
            'width_cm' => $data['width_cm'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'depth_cm' => $data['depth_cm'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,
            'additional_measurements' => $data['additional_measurements'] ?? [],
            'is_bulky' => $data['is_bulky'] ?? null,
            'assembly_required' => $data['assembly_required'] ?? false,
            'discounted_price' => $productType === 'finished_good' && $discountedPrice !== '' && $discountedPrice !== null
                ? (float) $discountedPrice
                : null,
            'cost_price' => $costPrice !== null ? (float) $costPrice : null,
            'is_active' => $data['is_active'] ?? true,
            'unit_of_measurement' => $data['unit_of_measurement'] ?? null,
            'supplier_name' => $data['supplier_name'] ?? null,
            'initial_stock' => $data['initial_stock'] ?? null,
            'created_by' => auth()->id(),
        ];

        if ($productType !== 'finished_good') {
            $payload['category_id'] = null;
            $payload['subcategory_id'] = null;
        } else {
            $payload['category_id'] = $data['category_id'] ?? $this->resolveDefaultCategoryId($storeId);
            $payload['subcategory_id'] = $data['subcategory_id'] ?? null;
        }

        return $payload;
    }

    private function setPreferredSupplier(Product $product, ?int $supplierId, float $supplierPrice): void
    {
        DB::table('supplier_products')
            ->where('product_id', $product->id)
            ->update(['is_preferred_supplier' => false, 'updated_at' => now()]);

        if (!$supplierId) {
            return;
        }

        $product->suppliers()->syncWithoutDetaching([
            $supplierId => [
                'supplier_price' => $supplierPrice,
                'minimum_order_quantity' => 1,
                'lead_time_days' => 7,
                'is_preferred_supplier' => true,
            ],
        ]);
    }

    private function syncSupplierLinks(Product $product, array $links): void
    {
        $preferredCount = collect($links)->filter(fn ($link) => (bool) ($link['is_preferred_supplier'] ?? false))->count();
        if ($preferredCount > 1) {
            throw ValidationException::withMessages([
                'supplier_links' => 'Choose only one preferred supplier for this product.',
            ]);
        }

        if ($links && $preferredCount === 0) {
            $links[0]['is_preferred_supplier'] = true;
        }

        $sync = [];
        foreach ($links as $link) {
            $sync[(int) $link['supplier_id']] = [
                'supplier_price' => (float) $link['supplier_price'],
                'minimum_order_quantity' => (int) ($link['minimum_order_quantity'] ?? 1),
                'lead_time_days' => (int) ($link['lead_time_days'] ?? 7),
                'is_preferred_supplier' => (bool) ($link['is_preferred_supplier'] ?? false),
            ];
        }

        $product->suppliers()->sync($sync);
    }

    private function normalizeSupplierLinks(Request $request): void
    {
        $value = $request->input('supplier_links');
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['supplier_links' => $decoded]);
            }
        }
    }

    private function validateMerchandisingFields(Request $request, array &$validated, int $storeId): void
    {
        $user = $request->user();
        if (!$user || !$user->hasPermissionTo('merchandising.products.manage', $storeId)) {
            return;
        }

        if (($validated['product_type'] ?? null) === 'finished_good'
            && $request->isMethod('post') && empty($validated['category_id'])) {
            $defaultCategoryId = $this->resolveDefaultCategoryId($storeId);
            if (!$defaultCategoryId) {
                throw ValidationException::withMessages(['category_id' => 'Select a category for finished goods.']);
            }
            $validated['category_id'] = $defaultCategoryId;
        }

        if (isset($validated['category_id']) && !Category::query()
            ->where('store_id', $storeId)->whereKey($validated['category_id'])->exists()) {
            throw ValidationException::withMessages(['category_id' => 'The selected category is invalid.']);
        }

        if (!empty($validated['subcategory_id'])) {
            $subcategory = Category::query()->where('store_id', $storeId)->find($validated['subcategory_id']);
            if (!$subcategory || (isset($validated['category_id']) && (int) $subcategory->parent_category_id !== (int) $validated['category_id'])) {
                throw ValidationException::withMessages(['subcategory_id' => 'The selected subcategory does not belong to the selected category.']);
            }
        }

        if (array_key_exists('tag_ids', $validated)) {
            $tagIds = $validated['tag_ids'] ?? [];
            $validTagCount = DB::table('tags')->where('store_id', $storeId)
                ->whereNull('deleted_at')->whereIn('id', $tagIds)->count();
            if ($validTagCount !== count($tagIds)) {
                throw ValidationException::withMessages(['tag_ids' => 'One or more selected tags are invalid.']);
            }
        }
    }

    private function syncProductTags(Product $product, array $tagIds, int $storeId): void
    {
        $syncPayload = collect($tagIds)->mapWithKeys(fn ($tagId) => [
            (int) $tagId => ['store_id' => $storeId],
        ])->all();
        $product->tags()->sync($syncPayload);
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
                    'subcategory_id',
                    'product_type',
                    'brand',
                    'base_price',
                    'cost_price',
                    'cost_price as inventory_cost_price',
                    'length_cm',
                    'width_cm',
                    'height_cm',
                    'depth_cm',
                    'weight_kg',
                    'additional_measurements',
                    'unit_of_measurement',
                    'supplier_name',
                    'initial_stock',
                    'is_active',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'category:id,category_name',
                    'subcategory:id,category_name,parent_category_id',
                    'suppliers:id,supplier_code,supplier_name,company_name,logo_path',
                    'assets:id,product_id,asset_type,file_name,file_path,is_primary,display_order',
                    'inventory' => function ($q) use ($context) {
                        $q->select([
                            'id',
                            'product_id',
                            'branch_id',
                            'warehouse_location_id',
                            'quantity_available',
                            'reorder_point',
                        ]);
                        if (!empty($context['branch_id'])) {
                            $q->where('branch_id', $context['branch_id']);
                        }
                    },
                ])
                ->where('store_id', $context['store_id']);

            if ($request->filled('location_id')) {
                $locationId = (int) $request->input('location_id');
                $query->whereHas('inventory', function ($q) use ($locationId, $context) {
                    $q->where('warehouse_location_id', $locationId)
                        ->where('branch_id', $context['branch_id']);
                });
            }

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
                'suppliers:id,supplier_code,supplier_name,company_name,logo_path',
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
    public function store(
        Request $request,
        PermissionService $permissions,
        ReorderSuggestionService $reorderSuggestions,
        ReorderSuggestionNotificationService $reorderNotifications,
    ): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            $user = auth()->user();
            $canManage = $user && (
                $permissions->userHasPermission($user, 'inventory.products.manage', $context['store_id'])
                || $permissions->userHasPermission($user, 'merchandising.products.manage', $context['store_id'])
            );
            if (!$canManage) {
                return response()->json(['success' => false, 'message' => 'You cannot create products.'], 403);
            }

            if ($request->input('product_type', 'finished_good') === 'finished_good'
                && !$permissions->userHasPermission($user, 'merchandising.products.manage', $context['store_id'])) {
                throw ValidationException::withMessages([
                    'product_type' => 'Finished goods must be created from the store-wide Product Catalog.',
                ]);
            }

            $this->normalizeSupplierLinks($request);
            $this->normalizeAdditionalMeasurements($request);

            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'sku' => 'nullable|string|max:100|unique:products,sku,NULL,id,store_id,' . $context['store_id'],
                'description' => 'nullable|string',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:categories,id',
                'product_type' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'base_price' => 'nullable|numeric|min:0',
                'discounted_price' => 'nullable|numeric|min:0',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'depth_cm' => 'nullable|numeric|min:0',
                'weight_kg' => 'nullable|numeric|min:0',
                'additional_measurements' => 'nullable|array|max:40',
                'additional_measurements.*.label' => 'required|string|max:80',
                'additional_measurements.*.value' => 'required|numeric|gt:0',
                'additional_measurements.*.unit' => 'required|string|in:cm,mm,m,in,ft,kg,g,lb',
                'is_bulky' => 'nullable|boolean',
                'assembly_required' => 'nullable|boolean',
                'tag_ids' => 'nullable|array|max:3',
                'tag_ids.*' => 'integer|distinct|exists:tags,id',
                'unit_cost' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'unit_of_measurement' => 'nullable|string|max:50',
                'supplier_name' => 'nullable|string|max:255',
                'supplier_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('store_id', $context['store_id'])),
                ],
                'supplier_links' => 'sometimes|array',
                'supplier_links.*.supplier_id' => [
                    'required', 'integer', 'distinct',
                    Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('store_id', $context['store_id'])),
                ],
                'supplier_links.*.supplier_price' => 'required|numeric|min:0',
                'supplier_links.*.minimum_order_quantity' => 'nullable|integer|min:1',
                'supplier_links.*.lead_time_days' => 'nullable|integer|min:0',
                'supplier_links.*.is_preferred_supplier' => 'nullable|boolean',
                'has_variants' => 'sometimes|boolean',
                'add_initial_stock' => 'sometimes|boolean',
                'initial_stock' => [
                    Rule::requiredIf(fn () => $request->boolean('add_initial_stock') && !$request->boolean('has_variants')),
                    'nullable',
                    'numeric',
                    'min:1',
                ],
                'reorder_point' => 'nullable|integer|min:0',
                'reorder_quantity' => 'nullable|integer|min:1',
                'reorder_scope' => 'nullable|in:branch,store',
                'is_active' => 'boolean',
                'variations' => 'prohibited',
                'product_image' => 'nullable|image|max:5120',
            ]);

            $this->validateMerchandisingFields($request, $validated, $context['store_id']);

            if (($validated['product_type'] ?? 'finished_good') === 'finished_good'
                && isset($validated['discounted_price'])
                && (float) $validated['discounted_price'] > (float) ($validated['base_price'] ?? $validated['unit_cost'] ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discounted price cannot be greater than the selling price.',
                    'errors' => ['discounted_price' => ['Discounted price cannot be greater than the selling price.']],
                ], 422);
            }

            $hasVariants = ($validated['product_type'] ?? 'finished_good') === 'finished_good'
                && (bool) ($validated['has_variants'] ?? false);
            $reorderScope = $validated['reorder_scope'] ?? 'branch';
            if ($reorderScope === 'store' && !$permissions->userHasPermission($user, 'merchandising.products.manage', $context['store_id'])) {
                throw ValidationException::withMessages([
                    'reorder_scope' => 'Store-wide reorder defaults require product catalog management access.',
                ]);
            }
            $addInitialStock = (bool) ($validated['add_initial_stock'] ?? false);
            $branchCount = Branch::query()->where('store_id', $context['store_id'])->count();
            if ($addInitialStock && ($hasVariants || $branchCount !== 1)) {
                throw ValidationException::withMessages([
                    'add_initial_stock' => 'Initial stock can only be added during creation when the store has exactly one branch and the product has no variants.',
                ]);
            }
            $initialStock = $addInitialStock && !$hasVariants ? (int) ($validated['initial_stock'] ?? 0) : 0;
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

            if (($validated['product_type'] ?? 'finished_good') === 'finished_good'
                && empty($validated['category_id'])
                && !$this->resolveDefaultCategoryId($context['store_id'])) {
                throw ValidationException::withMessages(['category_id' => 'Select a category for finished goods.']);
            }

            DB::beginTransaction();

            $product = Product::create($this->buildProductPayload($validated, $context['store_id']));
            if (array_key_exists('tag_ids', $validated)) {
                $this->syncProductTags($product, $validated['tag_ids'] ?? [], $context['store_id']);
            }

            if (array_key_exists('supplier_links', $validated)) {
                $this->syncSupplierLinks($product, $validated['supplier_links'] ?? []);
            } elseif (!empty($validated['supplier_id'])) {
                $this->setPreferredSupplier(
                    $product,
                    (int) $validated['supplier_id'],
                    (float) ($validated['cost_price'] ?? $validated['unit_cost'] ?? 0)
                );
            }

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
                    'reorder_point' => (int) ($validated['reorder_point'] ?? 10),
                    'reorder_quantity' => 0,
                    'maximum_stock' => 0,
                    'safety_stock' => 0,
                    'stock_status' => 'out_of_stock',
                ]
            );

            // Product creation observers may have already created this row. Keep
            // the submitted branch default instead of retaining the schema fallback.
            $submittedReorderPoint = (int) ($validated['reorder_point'] ?? 10);
            $submittedReorderQuantity = (int) ($validated['reorder_quantity'] ?? 25);
            $reorderBranchIds = $reorderScope === 'store'
                ? Branch::query()->where('store_id', $context['store_id'])->where('status', 'active')->pluck('id')
                : collect([$branchId]);

            foreach ($reorderBranchIds as $reorderBranchId) {
                $branchInventory = BranchInventory::query()->firstOrCreate(
                    [
                        'store_id' => $context['store_id'],
                        'branch_id' => (int) $reorderBranchId,
                        'product_id' => $product->id,
                        'variation_id' => null,
                    ],
                    [
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'quantity_available' => 0,
                        'quantity_damaged' => 0,
                        'quantity_incoming' => 0,
                        'reorder_point' => $submittedReorderPoint,
                        'reorder_quantity' => $submittedReorderQuantity,
                        'maximum_stock' => 0,
                        'safety_stock' => 0,
                        'stock_status' => 'out_of_stock',
                    ],
                );

                $branchInventory->reorder_point = $submittedReorderPoint;
                $branchInventory->reorder_quantity = $submittedReorderQuantity;
                $branchInventory->save();

                ReorderRule::query()->updateOrCreate(
                    ['product_id' => (int) $product->id, 'branch_id' => (int) $reorderBranchId],
                    [
                        'rule_type' => 'automatic',
                        'trigger_type' => 'reorder_point',
                        'basis_type' => 'reorder_point',
                        'reorder_point' => $submittedReorderPoint,
                        'reorder_quantity' => $submittedReorderQuantity,
                        'priority' => 'medium',
                        'auto_generate_po' => false,
                        'is_active' => true,
                    ],
                );

                $suggestionResult = $reorderSuggestions->generateSuggestions(
                    (int) $context['store_id'],
                    (int) $reorderBranchId,
                    (int) $branchInventory->id,
                );
                foreach (($suggestionResult['suggestions'] ?? []) as $suggestion) {
                    $reorderNotifications->notify($suggestion, 'product_created');
                }
            }

            if ($initialStock > 0 && (int) $inventory->quantity_on_hand === 0) {
                $quantityBefore = (int) $inventory->quantity_on_hand;
                $inventory->quantity_on_hand = $quantityBefore + $initialStock;
                $inventory->quantity_available = (int) $inventory->quantity_available + $initialStock;
                $inventory->stock_status = $inventory->quantity_on_hand <= (int) ($inventory->reorder_point ?? 0)
                    ? 'low_stock'
                    : 'in_stock';
                $inventory->suppressAutomaticTransactionLog();
                $inventory->save();

                InventoryTransaction::create([
                    'transaction_number' => 'TXN-' . strtoupper(Str::random(10)),
                    'store_id' => $context['store_id'],
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'variation_id' => null,
                    'transaction_type' => 'adjustment',
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => $initialStock,
                    'quantity_after' => $inventory->quantity_on_hand,
                    'reference_type' => 'product_opening_stock',
                    'reference_id' => $product->id,
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

            if ($hasVariants) {
                $this->createInitialStandardVariation($product, (int) $context['store_id']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load(['category', 'suppliers', 'variations', 'assets', 'inventory']),
                'message' => 'Product created successfully',
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create inventory product', [
                'store_id' => $context['store_id'] ?? null,
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);
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
    public function update(Request $request, int $id, PermissionService $permissions, ForecastPlanService $forecastPlans): JsonResponse
    {
        try {
            $context = $this->getUserContext();
            $user = auth()->user();
            $canManage = $user && (
                $permissions->userHasPermission($user, 'inventory.products.manage', $context['store_id'])
                || $permissions->userHasPermission($user, 'merchandising.products.manage', $context['store_id'])
            );
            if (!$canManage) {
                return response()->json(['success' => false, 'message' => 'You cannot update products.'], 403);
            }

            $product = Product::where('store_id', $context['store_id'])->findOrFail($id);
            $warehouseEnabled = $forecastPlans->allowsWarehouse((int) $context['store_id']);

            $canManageCatalog = $permissions->userHasPermission($user, 'merchandising.products.manage', $context['store_id']);
            if (!$canManageCatalog || $request->boolean('inventory_settings_only')) {
                $this->normalizeSupplierLinks($request);
                $rules = [
                    'inventory_settings_only' => 'sometimes|boolean',
                    'reorder_point' => 'sometimes|nullable|integer|min:0',
                ];
                if (!$warehouseEnabled) {
                    $rules = array_merge($rules, [
                        'cost_price' => 'sometimes|nullable|numeric|min:0',
                        'supplier_links' => 'sometimes|array',
                        'supplier_links.*.supplier_id' => [
                            'required', 'integer', 'distinct',
                            Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('store_id', $context['store_id'])),
                        ],
                        'supplier_links.*.supplier_price' => 'required|numeric|min:0',
                        'supplier_links.*.minimum_order_quantity' => 'nullable|integer|min:1',
                        'supplier_links.*.lead_time_days' => 'nullable|integer|min:0',
                        'supplier_links.*.is_preferred_supplier' => 'nullable|boolean',
                    ]);
                }
                $validated = $request->validate($rules);

                DB::beginTransaction();
                if (!$warehouseEnabled && array_key_exists('cost_price', $validated)) {
                    $product->cost_price = $validated['cost_price'];
                }
                if (array_key_exists('reorder_point', $validated) && $context['branch_id']) {
                    $this->syncBranchReorderPoint(
                        (int) $context['store_id'],
                        (int) $context['branch_id'],
                        (int) $product->id,
                        (int) $validated['reorder_point']
                    );
                }
                if (!$warehouseEnabled && array_key_exists('supplier_links', $validated)) {
                    $this->syncSupplierLinks($product, $validated['supplier_links'] ?? []);
                }
                $product->save();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => $product->load(['category', 'suppliers', 'variations', 'assets', 'inventory']),
                    'message' => 'Inventory settings updated successfully',
                ]);
            }

            $this->normalizeSupplierLinks($request);
            $this->normalizeAdditionalMeasurements($request);

            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'sku' => 'nullable|string|max:100|unique:products,sku,' . $id . ',id,store_id,' . $context['store_id'],
                'description' => 'nullable|string',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:categories,id',
                'product_type' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'base_price' => 'nullable|numeric|min:0',
                'discounted_price' => 'nullable|numeric|min:0',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'depth_cm' => 'nullable|numeric|min:0',
                'weight_kg' => 'nullable|numeric|min:0',
                'additional_measurements' => 'nullable|array|max:40',
                'additional_measurements.*.label' => 'required|string|max:80',
                'additional_measurements.*.value' => 'required|numeric|gt:0',
                'additional_measurements.*.unit' => 'required|string|in:cm,mm,m,in,ft,kg,g,lb',
                'is_bulky' => 'nullable|boolean',
                'assembly_required' => 'nullable|boolean',
                'tag_ids' => 'sometimes|array|max:3',
                'tag_ids.*' => 'integer|distinct|exists:tags,id',
                'unit_cost' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'unit_of_measurement' => 'nullable|string|max:50',
                'supplier_name' => 'nullable|string|max:255',
                'supplier_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('store_id', $context['store_id'])),
                ],
                'supplier_links' => 'sometimes|array',
                'supplier_links.*.supplier_id' => [
                    'required', 'integer', 'distinct',
                    Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('store_id', $context['store_id'])),
                ],
                'supplier_links.*.supplier_price' => 'required|numeric|min:0',
                'supplier_links.*.minimum_order_quantity' => 'nullable|integer|min:1',
                'supplier_links.*.lead_time_days' => 'nullable|integer|min:0',
                'supplier_links.*.is_preferred_supplier' => 'nullable|boolean',
                'initial_stock' => 'nullable|numeric|min:0',
                'reorder_point' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'product_image' => 'nullable|image|max:5120',
            ]);

            $this->validateMerchandisingFields($request, $validated, $context['store_id']);

            if (($validated['product_type'] ?? $product->product_type) === 'finished_good'
                && isset($validated['discounted_price'])
                && (float) $validated['discounted_price'] > (float) ($validated['base_price'] ?? $validated['unit_cost'] ?? $product->base_price ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discounted price cannot be greater than the selling price.',
                    'errors' => ['discounted_price' => ['Discounted price cannot be greater than the selling price.']],
                ], 422);
            }

            $productType = $validated['product_type'] ?? $product->product_type;
            if ($productType === 'finished_good'
                && $product->product_type !== 'finished_good'
                && !$permissions->userHasPermission($user, 'merchandising.products.manage', $context['store_id'])) {
                throw ValidationException::withMessages([
                    'product_type' => 'Finished goods must be created from the store-wide Product Catalog.',
                ]);
            }
            if ($productType === 'finished_good' && empty($validated['category_id']) && !$product->category_id) {
                throw ValidationException::withMessages(['category_id' => 'Select a category for finished goods.']);
            }

            DB::beginTransaction();

            $updates = [
                'product_name' => $validated['product_name'],
                'sku' => $validated['sku'] ?? $product->sku,
                'description' => array_key_exists('description', $validated)
                    ? $validated['description']
                    : $product->description,
                'category_id' => $productType === 'finished_good'
                    ? ($validated['category_id'] ?? $product->category_id)
                    : null,
                'subcategory_id' => $productType === 'finished_good'
                    ? ($validated['subcategory_id'] ?? $product->subcategory_id)
                    : null,
                'product_type' => $productType,
                'brand' => array_key_exists('brand', $validated) ? $validated['brand'] : $product->brand,
                'base_price' => (float) ($validated['base_price'] ?? $validated['unit_cost'] ?? $product->base_price ?? 0),
                'discounted_price' => array_key_exists('discounted_price', $validated)
                    ? $validated['discounted_price']
                    : $product->discounted_price,
                'length_cm' => $validated['length_cm'] ?? $product->length_cm,
                'width_cm' => $validated['width_cm'] ?? $product->width_cm,
                'height_cm' => $validated['height_cm'] ?? $product->height_cm,
                'depth_cm' => array_key_exists('depth_cm', $validated) ? $validated['depth_cm'] : $product->depth_cm,
                'weight_kg' => array_key_exists('weight_kg', $validated) ? $validated['weight_kg'] : $product->weight_kg,
                'additional_measurements' => array_key_exists('additional_measurements', $validated)
                    ? ($validated['additional_measurements'] ?? [])
                    : $product->additional_measurements,
                'is_bulky' => array_key_exists('is_bulky', $validated) ? $validated['is_bulky'] : $product->is_bulky,
                'assembly_required' => $validated['assembly_required'] ?? $product->assembly_required,
                'cost_price' => $validated['cost_price'] ?? $validated['unit_cost'] ?? $product->cost_price,
                'is_active' => $validated['is_active'] ?? $product->is_active,
                'unit_of_measurement' => $validated['unit_of_measurement'] ?? $product->unit_of_measurement,
                'supplier_name' => $validated['supplier_name'] ?? $product->supplier_name,
                'initial_stock' => $validated['initial_stock'] ?? $product->initial_stock,
                'updated_by' => auth()->id(),
            ];

            $product->update($updates);
            if (array_key_exists('reorder_point', $validated)) {
                $this->syncBranchReorderPoint(
                    (int) $context['store_id'],
                    (int) $context['branch_id'],
                    (int) $product->id,
                    (int) $validated['reorder_point']
                );
            }
            if (array_key_exists('tag_ids', $validated)) {
                $this->syncProductTags($product, $validated['tag_ids'] ?? [], $context['store_id']);
            }

            if (array_key_exists('supplier_links', $validated)) {
                $this->syncSupplierLinks($product, $validated['supplier_links'] ?? []);
            } elseif (array_key_exists('supplier_id', $validated)) {
                $this->setPreferredSupplier(
                    $product,
                    isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
                    (float) ($validated['cost_price'] ?? $validated['unit_cost'] ?? $product->cost_price ?? 0)
                );
            }

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
                'data' => $product->load(['category', 'suppliers', 'variations', 'assets']),
                'message' => 'Product updated successfully',
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function syncBranchReorderPoint(int $storeId, int $branchId, int $productId, int $reorderPoint): void
    {
        BranchInventory::query()
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->whereNull('variation_id')
            ->update(['reorder_point' => $reorderPoint]);

        $rule = ReorderRule::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->first();

        if ($rule && ($rule->basis_type ?? 'reorder_point') === 'reorder_point') {
            $rule->update(['reorder_point' => $reorderPoint]);
        }
    }

    private function normalizeAdditionalMeasurements(Request $request): void
    {
        if (!$request->exists('additional_measurements')) {
            return;
        }

        $measurements = $request->input('additional_measurements');
        if (is_string($measurements)) {
            if (trim($measurements) === '') {
                $measurements = [];
            } else {
                $measurements = json_decode($measurements, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw ValidationException::withMessages([
                        'additional_measurements' => 'Additional measurements contain invalid data.',
                    ]);
                }
            }
        }

        if ($measurements !== null && !is_array($measurements)) {
            throw ValidationException::withMessages([
                'additional_measurements' => 'Additional measurements must be a list of measurement values.',
            ]);
        }

        $request->merge(['additional_measurements' => $measurements]);
    }

    public function archive(int $id, PermissionService $permissions): JsonResponse
    {
        $context = $this->getUserContext();
        $user = auth()->user();

        $canArchive = $user && (
            $permissions->userHasPermission($user, 'inventory.products.manage', $context['store_id'])
            || $permissions->userHasPermission($user, 'merchandising.products.delete', $context['store_id'])
        );
        if (!$canArchive) {
            return response()->json(['success' => false, 'message' => 'You cannot archive products.'], 403);
        }

        $product = DB::transaction(function () use ($context, $id): Product {
            $product = Product::query()
                ->where('store_id', $context['store_id'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($this->productHasStockAcrossStore((int) $context['store_id'], $id, true)) {
                throw ValidationException::withMessages([
                    'product' => 'This product cannot be archived while it has stock in any branch. Reduce all branch and variant stock to zero first.',
                ]);
            }

            $product->delete();

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Product archived. Its stock and history were preserved.',
            'data' => ['id' => $product->id, 'deleted_at' => $product->deleted_at],
        ]);
    }

    public function archived(Request $request, PermissionService $permissions): JsonResponse
    {
        $context = $this->getUserContext();
        $user = auth()->user();
        $canView = $user && (
            $permissions->userHasPermission($user, 'inventory.products.manage', $context['store_id'])
            || $permissions->userHasPermission($user, 'merchandising.products.view', $context['store_id'])
            || $permissions->userHasPermission($user, 'merchandising.products.manage', $context['store_id'])
            || $permissions->userHasPermission($user, 'merchandising.products.approve', $context['store_id'])
            || $permissions->userHasPermission($user, 'merchandising.products.delete', $context['store_id'])
        );
        if (!$canView) {
            return response()->json(['success' => false, 'message' => 'You cannot view archived products.'], 403);
        }

        $query = Product::onlyTrashed()
            ->where('store_id', $context['store_id'])
            ->with(['category:id,category_name', 'subcategory:id,category_name,parent_category_id']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(fn ($builder) => $builder
                ->where('product_name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('deleted_at')->paginate(min(max((int) $request->input('per_page', 15), 1), 100)),
        ]);
    }

    public function restore(int $id, PermissionService $permissions): JsonResponse
    {
        $context = $this->getUserContext();
        $user = auth()->user();
        $canRestore = $user && (
            $permissions->userHasPermission($user, 'inventory.products.manage', $context['store_id'])
            || $permissions->userHasPermission($user, 'merchandising.products.delete', $context['store_id'])
        );
        if (!$canRestore) {
            return response()->json(['success' => false, 'message' => 'You cannot restore products.'], 403);
        }

        $product = Product::onlyTrashed()
            ->where('store_id', $context['store_id'])
            ->findOrFail($id);
        $product->restore();

        return response()->json([
            'success' => true,
            'message' => 'Product restored successfully.',
            'data' => $product->fresh(['category', 'subcategory']),
        ]);
    }

    public function purgeArchived(int $id, PermissionService $permissions): JsonResponse
    {
        $context = $this->getUserContext();
        $user = auth()->user();
        $canDelete = $user && (
            $permissions->userHasPermission($user, 'inventory.products.manage', $context['store_id'])
            || $permissions->userHasPermission($user, 'merchandising.products.delete', $context['store_id'])
        );
        if (!$canDelete) {
            return response()->json(['success' => false, 'message' => 'You cannot permanently delete products.'], 403);
        }

        try {
            DB::transaction(function () use ($context, $id): void {
                $product = Product::onlyTrashed()
                    ->where('store_id', $context['store_id'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($this->productHasStockAcrossStore((int) $context['store_id'], $id, true)) {
                    throw ValidationException::withMessages([
                        'product' => 'This product has stock in at least one branch and cannot be permanently deleted.',
                    ]);
                }

                // Remove only empty balance rows. Historical or transactional references
                // remain protected by their foreign keys and roll the delete back.
                BranchInventory::query()
                    ->where('store_id', $context['store_id'])
                    ->where('product_id', $id)
                    ->where('quantity_on_hand', '<=', 0)
                    ->where('quantity_available', '<=', 0)
                    ->where('quantity_reserved', '<=', 0)
                    ->where('quantity_damaged', '<=', 0)
                    ->delete();

                $product->forceDelete();
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Illuminate\Database\QueryException $exception) {
            if (str_starts_with((string) $exception->getCode(), '23')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product is referenced by inventory or business history and cannot be permanently deleted. It can remain archived.',
                ], 422);
            }
            throw $exception;
        }

        return response()->json(['success' => true, 'message' => 'Archived product permanently deleted.']);
    }

    private function productHasStockAcrossStore(int $storeId, int $productId, bool $lock = false): bool
    {
        $query = BranchInventory::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get(['quantity_on_hand', 'quantity_available', 'quantity_reserved', 'quantity_damaged'])
            ->contains(fn (BranchInventory $stock): bool =>
                (float) $stock->quantity_on_hand > 0
                || (float) $stock->quantity_available > 0
                || (float) $stock->quantity_reserved > 0
                || (float) $stock->quantity_damaged > 0
            );
    }

    /**
     * Delete product
     * DELETE /api/inventory/products/{id}
     */
    public function destroy(int $id, PermissionService $permissions): JsonResponse
    {
        $context = $this->getUserContext();
        $user = auth()->user();

        if (!$user || !$permissions->userHasPermission($user, 'inventory.products.manage', $context['store_id'])) {
            return response()->json(['success' => false, 'message' => 'You cannot delete products.'], 403);
        }

        try {
            $product = Product::where('store_id', $context['store_id'])->findOrFail($id);

            // Check if product has inventory
            $hasInventory = BranchInventory::where('product_id', $id)
                ->where('store_id', $context['store_id'])
                ->where(function ($query) {
                    $query->where('quantity_on_hand', '>', 0)
                        ->orWhere('quantity_reserved', '>', 0);
                })
                ->exists();

            if ($hasInventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item still has stock or reserved units in a branch. Adjust stock to zero or archive it instead.',
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

    private function createInitialStandardVariation(Product $product, int $storeId): void
    {
        $variationSku = (string) $product->sku;
        if (ProductVariation::withTrashed()->where('store_id', $storeId)->where('variation_sku', $variationSku)->exists()) {
            $variationSku .= '-STD';
            $suffix = 2;
            while (ProductVariation::withTrashed()->where('store_id', $storeId)->where('variation_sku', $variationSku)->exists()) {
                $variationSku = (string) $product->sku . '-STD-' . $suffix++;
            }
        }

        $assets = ProductAsset::query()
            ->where('store_id', $storeId)
            ->where('product_id', $product->id)
            ->orderByDesc('is_primary')
            ->orderBy('display_order')
            ->get();
        $image = $assets->first(fn (ProductAsset $asset) => in_array($asset->asset_type, ['Image_Main', 'Image_Gallery'], true));
        $model = $assets->firstWhere('asset_type', '3D_Model');

        $variationData = [
            'store_id' => $storeId,
            'product_id' => $product->id,
            'variation_sku' => $variationSku,
            'variation_name' => 'Standard',
            'price_adjustment' => 0,
            'base_price' => $product->base_price,
            'discounted_price' => $product->discounted_price,
            'cost_price' => $product->getRawOriginal('cost_price'),
            'reorder_point' => (int) ($product->reorder_point ?? 0),
            'supplier_name' => $product->supplier_name,
            'unit_of_measurement' => $product->unit_of_measurement,
            'length_cm' => $product->length_cm,
            'width_cm' => $product->width_cm,
            'height_cm' => $product->height_cm,
            'depth_cm' => $product->depth_cm,
            'weight_kg' => $product->weight_kg,
            'additional_measurements' => $product->additional_measurements,
            'custom_image_id' => $image?->id,
            'custom_3d_model_id' => $model?->id,
            'is_active' => true,
        ];
        $variationColumns = array_flip(Schema::getColumnListing('product_variations'));
        $variation = ProductVariation::create(array_intersect_key($variationData, $variationColumns));

        BranchInventory::query()
            ->where('store_id', $storeId)
            ->where('product_id', $product->id)
            ->whereNull('variation_id')
            ->update(['variation_id' => $variation->id]);

        $branches = Branch::query()->where('store_id', $storeId)->where('status', 'active')->get(['id']);
        foreach ($branches as $branch) {
            BranchInventory::query()->firstOrCreate(
                [
                    'store_id' => $storeId,
                    'branch_id' => (int) $branch->id,
                    'product_id' => (int) $product->id,
                    'variation_id' => (int) $variation->id,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                    'quantity_damaged' => 0,
                    'quantity_incoming' => 0,
                    'reorder_point' => (int) ($variation->reorder_point ?? 0),
                    'reorder_quantity' => 0,
                    'maximum_stock' => 0,
                    'safety_stock' => 0,
                    'stock_status' => 'out_of_stock',
                ],
            );
        }
    }
}
