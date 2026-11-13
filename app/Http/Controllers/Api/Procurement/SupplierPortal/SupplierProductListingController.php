<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\ProductCatalog\Category;
use App\Models\Procurement\SupplierPortal\SupplierProductCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupplierProductListingController extends Controller
{
    private const UNITS = ['pcs', 'set', 'pair', 'box', 'kg', 'g', 'lb', 'm', 'cm', 'mm', 'L', 'mL', 'pack', 'roll', 'ft', 'in'];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:active,inactive'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $portal = SupplierPortal::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $supplierIds = $portal->linkedSupplierIds();
        $search = trim((string) ($validated['search'] ?? ''));

        $query = DB::table('supplier_products as supplier_product')
            ->join('suppliers as supplier', 'supplier.id', '=', 'supplier_product.supplier_id')
            ->join('products as product', 'product.id', '=', 'supplier_product.product_id')
            ->leftJoin('stores as store', 'store.id', '=', 'supplier.store_id')
            ->leftJoin('categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('categories as subcategory', 'subcategory.id', '=', 'product.subcategory_id')
            ->whereIn('supplier_product.supplier_id', $supplierIds)
            ->whereNull('supplier.deleted_at')
            ->whereNull('product.deleted_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('product.product_name', 'like', "%{$search}%")
                        ->orWhere('product.sku', 'like', "%{$search}%")
                        ->orWhere('supplier_product.supplier_sku', 'like', "%{$search}%")
                        ->orWhere('store.name', 'like', "%{$search}%");
                });
            })
            ->when(!empty($validated['store_id']), fn ($query) => $query->where('supplier.store_id', (int) $validated['store_id']))
            ->when(($validated['status'] ?? null) === 'active', fn ($query) => $query->where('product.is_active', true))
            ->when(($validated['status'] ?? null) === 'inactive', fn ($query) => $query->where('product.is_active', false))
            ->select([
                'supplier_product.id',
                'supplier_product.supplier_id',
                'supplier_product.product_id',
                'supplier_product.supplier_sku',
                'supplier_product.supplier_price',
                'supplier_product.availability_status',
                'supplier_product.minimum_order_quantity',
                'supplier_product.lead_time_days',
                'supplier_product.is_preferred_supplier',
                'supplier.store_id',
                'supplier.default_tax_rate',
                'supplier.is_tax_exempt',
                'store.name as store_name',
                'product.product_name',
                'product.sku',
                'product.product_type',
                'product.unit_of_measurement',
                'product.is_active',
                'category.category_name',
                'subcategory.category_name as subcategory_name',
            ])
            ->orderBy('product.product_name')
            ->orderBy('store.name')
            ->get();

        $grouped = $query->groupBy(fn ($row) => $row->supplier_sku ?: 'product-' . $row->product_id)
            ->map(function ($offers) {
                $first = $offers->first();
                return [
                    'id' => (int) $first->id,
                    'supplier_id' => (int) $first->supplier_id,
                    'product_id' => (int) $first->product_id,
                    'supplier_sku' => $first->supplier_sku ?: $first->sku,
                    'product_sku' => $first->sku,
                    'product_name' => $first->product_name,
                    'product_type' => $first->product_type,
                    'category_name' => $first->category_name,
                    'subcategory_name' => $first->subcategory_name,
                    'unit_of_measurement' => $first->unit_of_measurement,
                    'is_active' => $offers->contains(fn ($offer) => (bool) $offer->is_active),
                    '_offers' => $offers,
                ];
            })->values();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $currentPage = max(1, (int) $request->input('page', 1));
        $pageRows = $grouped->forPage($currentPage, $perPage)->values();

        $assets = ProductAsset::query()
            ->whereIn('product_id', $pageRows->pluck('product_id')->unique())
            ->whereNull('variation_id')
            ->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])
            ->orderByDesc('is_primary')
            ->orderBy('display_order')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        $pageRows = $pageRows->map(function (array $product) use ($assets) {
            $product['image_url'] = $assets->get($product['product_id'])?->thumbnail_url;
            $product['offers'] = $product['_offers']->map(function ($row) {
                $baseCost = round((float) $row->supplier_price, 2);
                $taxRate = $row->is_tax_exempt ? 0.0 : (float) ($row->default_tax_rate ?? 12);
                $taxAmount = round($baseCost * $taxRate / 100, 2);
                return [
                    'id' => (int) $row->id,
                    'store_id' => (int) $row->store_id,
                    'store_name' => $row->store_name,
                    'minimum_order_quantity' => (int) $row->minimum_order_quantity,
                    'lead_time_days' => (int) $row->lead_time_days,
                    'availability_status' => $row->availability_status ?: 'available',
                    'is_active' => (bool) $row->is_active,
                    'is_tax_exempt' => (bool) $row->is_tax_exempt,
                    'base_cost' => $baseCost,
                    'tax_rate' => round($taxRate, 2),
                    'tax_amount' => $taxAmount,
                    'cost_including_tax' => round($baseCost + $taxAmount, 2),
                ];
            })->all();
            unset($product['_offers']);
            return $product;
        });
        $paginated = new LengthAwarePaginator($pageRows, $grouped->count(), $perPage, $currentPage, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $paginated,
            'stores' => DB::table('suppliers as supplier')
                ->join('stores as store', 'store.id', '=', 'supplier.store_id')
                ->whereIn('supplier.id', $supplierIds)
                ->whereNull('supplier.deleted_at')
                ->select('store.id', 'store.name as store_name')
                ->distinct()
                ->orderBy('store.name')
                ->get(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $portal = SupplierPortal::query()->where('user_id', $request->user()->id)->firstOrFail();
        $suppliers = $this->linkedSuppliers($portal);
        $storeIds = $suppliers->pluck('store_id')->unique()->values();

        $categories = SupplierProductCategory::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('supplier_id')->orWhereIn('supplier_id', $suppliers->pluck('id')))
            ->whereNull('parent_category_id')
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('category_name')])
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'supplier_id'])
            ->map(fn ($category) => [
                'value' => $category->category_name,
                'label' => $category->category_name,
                'custom' => $category->supplier_id !== null,
                'subcategories' => $category->children->pluck('category_name')->values(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'units' => self::UNITS,
                'stores' => $suppliers->map(fn ($supplier) => [
                    'id' => (int) $supplier->store_id,
                    'name' => $supplier->store_name,
                    'is_tax_exempt' => (bool) $supplier->is_tax_exempt,
                    'tax_rate' => $supplier->is_tax_exempt ? 0 : (float) ($supplier->default_tax_rate ?? 12),
                ])->values(),
            ],
        ]);
    }

    public function createCategory(Request $request): JsonResponse
    {
        $validated = $request->validate(['category_name' => ['required', 'string', 'max:120']]);
        $portal = SupplierPortal::query()->where('user_id', $request->user()->id)->firstOrFail();
        $supplierId = $portal->linkedSupplierIds()->first();
        abort_unless($supplierId, 422, 'Link a store before adding supplier categories.');
        $name = trim($validated['category_name']);

        $category = SupplierProductCategory::query()->firstOrCreate(
            ['supplier_id' => $supplierId, 'category_name' => $name],
            ['is_active' => true],
        );

        return response()->json(['success' => true, 'message' => 'Category saved.', 'data' => [
            'value' => $category->category_name, 'label' => $category->category_name, 'custom' => true, 'subcategories' => [],
        ]], 201);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', Rule::in(['finished_good', 'supply', 'raw_material', 'other'])],
            'category' => ['required', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'unit_of_measurement' => ['required', Rule::in(self::UNITS)],
            'base_cost' => ['required', 'numeric', 'min:0.01'],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'availability_status' => ['required', Rule::in(['available', 'limited', 'out_of_stock'])],
            'is_active' => ['required', 'boolean'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'depth_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'additional_measurements' => ['nullable', 'json'],
            'variation_attributes' => ['nullable', 'json'],
            'variants' => ['nullable', 'json'],
            'product_images' => ['nullable', 'array', 'max:5'],
            'product_images.*' => ['image', 'max:5120'],
            'variant_images' => ['nullable', 'array'],
            'variant_images.*' => ['nullable', 'array', 'max:5'],
            'variant_images.*.*' => ['image', 'max:5120'],
        ]);

        $portal = SupplierPortal::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($portal->status === 'approved', 403, 'Your supplier account must be approved before creating products.');

        $suppliers = $this->linkedSuppliers($portal);
        abort_if($suppliers->isEmpty(), 422, 'Link at least one store before creating a product.');

        $sharedSku = 'SUP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));

        $categoryName = trim($validated['category']);
        $subcategoryName = trim((string) ($validated['subcategory'] ?? ''));
        if ($subcategoryName !== '') {
            $subcategoryExists = SupplierProductCategory::query()
                ->where('category_name', $subcategoryName)
                ->where('is_active', true)
                ->whereHas('parent', fn ($query) => $query->where('category_name', $categoryName)->where('is_active', true))
                ->where(fn ($query) => $query->whereNull('supplier_id')->orWhereIn('supplier_id', $suppliers->pluck('id')))
                ->exists();
            if (!$subcategoryExists) {
                throw ValidationException::withMessages(['subcategory' => ['Choose a subcategory under the selected category.']]);
            }
        }
        $additionalMeasurements = $this->decodeMeasurements($validated['additional_measurements'] ?? null, 'additional_measurements');
        $variationAttributes = $this->decodeVariationAttributes($validated['variation_attributes'] ?? null);
        $variants = $this->decodeVariants($validated['variants'] ?? null);
        $this->validateSupplierVariantAttributes($variationAttributes, $variants);
        $this->validateVariantImages($request, $variants);

        $productImagePaths = collect($request->file('product_images', []))
            ->map(fn ($file) => ['file' => $file, 'path' => $file->store("supplier-portals/{$portal->id}/products", 'public')]);
        $variantImagePaths = collect($variants)->map(fn ($variant, $index) => collect($request->file("variant_images.{$index}", []))
            ->map(fn ($file) => ['file' => $file, 'path' => $file->store("supplier-portals/{$portal->id}/products/variants", 'public')])->all())->all();

        $created = DB::transaction(function () use ($validated, $suppliers, $sharedSku, $portal, $request, $categoryName, $subcategoryName, $additionalMeasurements, $variationAttributes, $variants, $productImagePaths, $variantImagePaths) {
            return $suppliers->map(function ($supplier) use ($validated, $sharedSku, $portal, $categoryName, $subcategoryName, $additionalMeasurements, $variationAttributes, $variants, $productImagePaths, $variantImagePaths) {
                $category = $this->storeCategory((int) $supplier->store_id, $categoryName);
                $subcategory = $subcategoryName !== '' ? $this->storeSubcategory((int) $supplier->store_id, $category, $subcategoryName) : null;
                $product = Product::create([
                    'store_id' => $supplier->store_id,
                    'sku' => $sharedSku,
                    'product_name' => trim($validated['product_name']),
                    'description' => $validated['description'] ?? null,
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory?->id,
                    'product_type' => $validated['product_type'],
                    'unit_of_measurement' => $validated['unit_of_measurement'],
                    'base_price' => 0,
                    'cost_price' => (float) $validated['base_cost'],
                    'initial_stock' => 0,
                    'reorder_point' => 10,
                    'length_cm' => $validated['length_cm'] ?? null,
                    'width_cm' => $validated['width_cm'] ?? null,
                    'height_cm' => $validated['height_cm'] ?? null,
                    'depth_cm' => $validated['depth_cm'] ?? null,
                    'weight_kg' => $validated['weight_kg'] ?? null,
                    'additional_measurements' => $additionalMeasurements,
                    'variation_attributes' => $variationAttributes,
                    'is_active' => (bool) $validated['is_active'],
                ]);

                $listingId = DB::table('supplier_products')->insertGetId([
                    'supplier_id' => $supplier->id,
                    'product_id' => $product->id,
                    'supplier_sku' => $sharedSku,
                    'supplier_price' => (float) $validated['base_cost'],
                    'minimum_order_quantity' => (int) $validated['minimum_order_quantity'],
                    'lead_time_days' => (int) $validated['lead_time_days'],
                    'availability_status' => $validated['availability_status'],
                    'is_preferred_supplier' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($productImagePaths as $imageIndex => $asset) {
                    $this->createImageAsset($product, null, $asset['file'], $asset['path'], $imageIndex, $validated['product_name']);
                }

                foreach ($variants as $variantIndex => $variantData) {
                    $variation = ProductVariation::create([
                        'store_id' => $supplier->store_id,
                        'product_id' => $product->id,
                        'variation_sku' => substr($sharedSku . '-V' . str_pad((string) ($variantIndex + 1), 2, '0', STR_PAD_LEFT), 0, 100),
                        'variation_name' => $variantData['variation_name'],
                        'color' => $variantData['color'] ?? null,
                        'size' => $variantData['size'] ?? null,
                        'material' => $variantData['material'] ?? null,
                        'texture' => $variantData['texture'] ?? null,
                        'finish' => $variantData['finish'] ?? null,
                        'unit_of_measurement' => $validated['unit_of_measurement'],
                        'cost_price' => (float) ($variantData['base_cost'] ?? $validated['base_cost']),
                        'length_cm' => $variantData['length_cm'] ?? null,
                        'width_cm' => $variantData['width_cm'] ?? null,
                        'height_cm' => $variantData['height_cm'] ?? null,
                        'depth_cm' => $variantData['depth_cm'] ?? null,
                        'weight_kg' => $variantData['weight_kg'] ?? null,
                        'additional_measurements' => $this->decodeMeasurements($variantData['additional_measurements'] ?? null, "variants.{$variantIndex}.additional_measurements"),
                        'is_active' => true,
                    ]);

                    foreach ($variantImagePaths[$variantIndex] ?? [] as $imageIndex => $asset) {
                        $this->createImageAsset($product, $variation, $asset['file'], $asset['path'], $imageIndex, $variantData['variation_name']);
                    }
                }

                return ['listing_id' => $listingId, 'product_id' => $product->id, 'store_id' => (int) $supplier->store_id];
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Supplier product and store offers created.',
            'data' => ['id' => $created->first()['listing_id'], 'supplier_sku' => $sharedSku, 'stores' => $created],
        ], 201);
    }

    public function show(Request $request, int $listingId): JsonResponse
    {
        $portal = SupplierPortal::query()->where('user_id', $request->user()->id)->firstOrFail();
        $supplierIds = $portal->linkedSupplierIds();
        $selected = DB::table('supplier_products')->whereIn('supplier_id', $supplierIds)->where('id', $listingId)->first();
        abort_unless($selected, 404);

        $rows = DB::table('supplier_products as supplier_product')
            ->join('suppliers as supplier', 'supplier.id', '=', 'supplier_product.supplier_id')
            ->join('stores as store', 'store.id', '=', 'supplier.store_id')
            ->join('products as product', 'product.id', '=', 'supplier_product.product_id')
            ->leftJoin('categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('categories as subcategory', 'subcategory.id', '=', 'product.subcategory_id')
            ->whereIn('supplier_product.supplier_id', $supplierIds)
            ->where('supplier_product.supplier_sku', $selected->supplier_sku)
            ->select('supplier_product.*', 'supplier.store_id', 'supplier.default_tax_rate', 'supplier.is_tax_exempt', 'store.name as store_name', 'product.product_name', 'product.sku', 'product.description', 'product.product_type', 'product.unit_of_measurement', 'product.is_active', 'product.length_cm', 'product.width_cm', 'product.height_cm', 'product.depth_cm', 'product.weight_kg', 'product.additional_measurements', 'category.category_name', 'subcategory.category_name as subcategory_name')
            ->orderBy('store.name')
            ->get();

        $first = $rows->first();
        $offers = $rows->map(function ($row) {
            $base = (float) $row->supplier_price;
            $rate = $row->is_tax_exempt ? 0 : (float) ($row->default_tax_rate ?? 12);
            return [
                'id' => (int) $row->id,
                'store_id' => (int) $row->store_id,
                'store_name' => $row->store_name,
                'minimum_order_quantity' => (int) $row->minimum_order_quantity,
                'lead_time_days' => (int) $row->lead_time_days,
                'availability_status' => $row->availability_status ?: 'available',
                'is_active' => (bool) $row->is_active,
                'base_cost' => round($base, 2),
                'tax_rate' => round($rate, 2),
                'tax_amount' => round($base * $rate / 100, 2),
                'cost_including_tax' => round($base * (1 + $rate / 100), 2),
            ];
        });

        $product = Product::query()->with(['assets' => function ($query) {
            $query->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])
                ->whereNull('variation_id')
                ->orderByDesc('is_primary')->orderBy('display_order');
        }, 'variations' => function ($query) {
            $query->with(['assets' => function ($assetQuery) {
                $assetQuery->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])
                    ->orderByDesc('is_primary')->orderBy('display_order');
            }])->orderBy('id');
        }])->findOrFail($first->product_id);

        return response()->json(['success' => true, 'data' => [
            'id' => $listingId,
            'supplier_sku' => $selected->supplier_sku ?: $first->sku,
            'product_name' => $first->product_name,
            'description' => $first->description,
            'product_type' => $first->product_type,
            'category_name' => $first->category_name,
            'subcategory_name' => $first->subcategory_name,
            'unit_of_measurement' => $first->unit_of_measurement,
            'is_active' => (bool) $first->is_active,
            'dimensions' => [
                'length_cm' => $first->length_cm,
                'width_cm' => $first->width_cm,
                'height_cm' => $first->height_cm,
                'depth_cm' => $first->depth_cm,
                'weight_kg' => $first->weight_kg,
                'additional_measurements' => $first->additional_measurements ?: [],
            ],
            'images' => $product->assets->map(fn ($asset) => ['url' => $asset->thumbnail_url, 'alt_text' => $asset->alt_text ?: $first->product_name])->values(),
            'variants' => $product->variations->map(fn ($variation) => [
                'id' => (int) $variation->id,
                'sku' => $variation->variation_sku,
                'name' => $variation->variation_name,
                'color' => $variation->color,
                'size' => $variation->size,
                'material' => $variation->material,
                'base_cost' => (float) $variation->cost_price,
                'dimensions' => [
                    'length_cm' => $variation->length_cm,
                    'width_cm' => $variation->width_cm,
                    'height_cm' => $variation->height_cm,
                    'depth_cm' => $variation->depth_cm,
                    'weight_kg' => $variation->weight_kg,
                    'additional_measurements' => $variation->additional_measurements ?: [],
                ],
                'images' => $variation->assets->map(fn ($asset) => ['url' => $asset->thumbnail_url, 'alt_text' => $asset->alt_text ?: $variation->variation_name])->values(),
            ])->values(),
            'offers' => $offers,
        ]]);
    }

    public function update(Request $request, int $listingId): JsonResponse
    {
        $validated = $request->validate([
            'supplier_price' => ['required', 'numeric', 'min:0.01'],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'availability_status' => ['required', Rule::in(['available', 'limited', 'out_of_stock'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $portal = SupplierPortal::query()->where('user_id', $request->user()->id)->firstOrFail();
        $listing = DB::table('supplier_products')->whereIn('supplier_id', $portal->linkedSupplierIds())->where('id', $listingId)->first();
        abort_unless($listing, 404);

        DB::transaction(function () use ($listing, $validated): void {
            DB::table('supplier_products')->where('id', $listing->id)->update([
                'supplier_price' => $validated['supplier_price'],
                'minimum_order_quantity' => $validated['minimum_order_quantity'],
                'lead_time_days' => $validated['lead_time_days'],
                'availability_status' => $validated['availability_status'],
                'updated_at' => now(),
            ]);
            Product::query()->whereKey($listing->product_id)
                ->update(['is_active' => (bool) $validated['is_active'], 'updated_at' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'Store offer updated.']);
    }

    private function decodeVariants(?string $json): array
    {
        if (!$json) return [];
        $variants = json_decode($json, true);
        if (!is_array($variants) || count($variants) > 30) {
            throw ValidationException::withMessages(['variants' => ['Add no more than 30 valid variants.']]);
        }

        foreach ($variants as $index => $variant) {
            if (!is_array($variant) || trim((string) ($variant['variation_name'] ?? '')) === '') {
                throw ValidationException::withMessages(["variants.{$index}.variation_name" => ['Variant name is required.']]);
            }
            foreach (['length_cm', 'width_cm', 'height_cm', 'depth_cm', 'weight_kg', 'base_cost'] as $field) {
                if (isset($variant[$field]) && (!is_numeric($variant[$field]) || (float) $variant[$field] < 0)) {
                    throw ValidationException::withMessages(["variants.{$index}.{$field}" => ['Enter a valid non-negative number.']]);
                }
            }
        }

        return $variants;
    }

    private function decodeVariationAttributes(?string $json): array
    {
        if (!$json) return [];
        $attributes = json_decode($json, true);
        if (!is_array($attributes)) {
            throw ValidationException::withMessages(['variation_attributes' => ['Variation attributes must be an object.']]);
        }

        $allowed = ['color', 'size', 'material', 'texture', 'finish'];
        foreach ($attributes as $key => $value) {
            if (!in_array($key, $allowed, true) || (!is_string($value) && $value !== null) || mb_strlen((string) $value) > 100) {
                throw ValidationException::withMessages(['variation_attributes' => ['Enter valid values for the variation attribute fields.']]);
            }
        }

        return $this->normalizeVariationAttributes($attributes);
    }

    private function validateSupplierVariantAttributes(array $standard, array $variants): void
    {
        $seen = [$this->variationAttributeSignature($standard) => 'standard product'];
        foreach ($variants as $index => $variant) {
            $attributes = [];
            foreach (['color', 'size', 'material', 'texture', 'finish'] as $field) {
                $value = $variant[$field] ?? '';
                if (!is_string($value) || mb_strlen($value) > 100) {
                    throw ValidationException::withMessages(["variants.{$index}.{$field}" => ['Enter a valid variation attribute (maximum 100 characters).']]);
                }
                $attributes[$field] = $value;
            }
            $signature = $this->variationAttributeSignature($attributes);
            if (isset($seen[$signature])) {
                throw ValidationException::withMessages(["variants.{$index}.attributes" => ["These attributes duplicate the {$seen[$signature]}. Change at least one attribute value."]]);
            }
            $seen[$signature] = 'another variant';
        }
    }

    private function normalizeVariationAttributes(array $attributes): array
    {
        $normalized = [];
        foreach (['color', 'size', 'material', 'texture', 'finish'] as $field) {
            $normalized[$field] = trim((string) ($attributes[$field] ?? ''));
        }
        return $normalized;
    }

    private function variationAttributeSignature(array $attributes): string
    {
        return json_encode(array_map(fn ($value) => mb_strtolower(trim((string) $value)), $this->normalizeVariationAttributes($attributes)), JSON_UNESCAPED_UNICODE);
    }

    private function decodeMeasurements($value, string $field): array
    {
        if (is_string($value)) $value = json_decode($value, true);
        if ($value === null || $value === '') return [];
        if (!is_array($value)) throw ValidationException::withMessages([$field => ['Measurements must be a list.']]);
        foreach ($value as $index => $measurement) {
            if (!is_array($measurement) || trim((string) ($measurement['label'] ?? '')) === ''
                || !is_numeric($measurement['value'] ?? null) || (float) $measurement['value'] <= 0
                || trim((string) ($measurement['unit'] ?? '')) === '') {
                throw ValidationException::withMessages(["{$field}.{$index}" => ['Enter a label, positive value, and unit for each measurement.']]);
            }
        }
        return array_values($value);
    }

    private function validateVariantImages(Request $request, array $variants): void
    {
        foreach ($variants as $index => $_variant) {
            if (count($request->file("variant_images.{$index}", [])) > 5) {
                throw ValidationException::withMessages(["variant_images.{$index}" => ['Each variant can have up to 5 images.']]);
            }
        }
    }

    private function storeCategory(int $storeId, string $name): Category
    {
        $category = Category::withTrashed()->where('store_id', $storeId)->whereNull('parent_category_id')->whereRaw('LOWER(category_name) = ?', [Str::lower($name)])->first();
        if ($category) {
            if ($category->trashed()) $category->restore();
            return $category;
        }

        $baseCode = strtoupper(trim(preg_replace('/[^A-Z0-9]+/i', '-', $name), '-')) ?: 'SUPPLIER-CATEGORY';
        $code = substr($baseCode, 0, 45);
        $suffix = 1;
        while (Category::withTrashed()->where('store_id', $storeId)->where('category_code', $code)->exists()) {
            $code = substr($baseCode, 0, 40) . '-' . $suffix++;
        }

        return Category::create([
            'store_id' => $storeId,
            'category_code' => $code,
            'category_name' => $name,
            'level' => 1,
            'is_active' => true,
        ]);
    }

    private function storeSubcategory(int $storeId, Category $parent, string $name): Category
    {
        $subcategory = Category::withTrashed()
            ->where('store_id', $storeId)
            ->where('parent_category_id', $parent->id)
            ->whereRaw('LOWER(category_name) = ?', [Str::lower($name)])
            ->first();
        if ($subcategory) {
            if ($subcategory->trashed()) $subcategory->restore();
            return $subcategory;
        }

        $baseCode = strtoupper(trim(preg_replace('/[^A-Z0-9]+/i', '-', $name), '-')) ?: 'SUPPLIER-SUBCATEGORY';
        $code = substr($baseCode, 0, 45);
        $suffix = 1;
        while (Category::withTrashed()->where('store_id', $storeId)->where('category_code', $code)->exists()) {
            $code = substr($baseCode, 0, 40) . '-' . $suffix++;
        }

        return Category::create([
            'store_id' => $storeId,
            'category_code' => $code,
            'category_name' => $name,
            'parent_category_id' => $parent->id,
            'level' => 2,
            'is_active' => true,
        ]);
    }

    private function createImageAsset(Product $product, ?ProductVariation $variation, $file, string $path, int $index, string $altText): void
    {
        ProductAsset::create([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'variation_id' => $variation?->id,
            'asset_type' => $index === 0 ? 'Image_Main' : 'Image_Gallery',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size_kb' => (int) round($file->getSize() / 1024),
            'mime_type' => $file->getMimeType(),
            'is_primary' => $index === 0,
            'display_order' => $index,
            'alt_text' => $altText,
        ]);
    }

    private function linkedSuppliers(SupplierPortal $portal)
    {
        return DB::table('suppliers as supplier')
            ->join('stores as store', 'store.id', '=', 'supplier.store_id')
            ->whereIn('supplier.id', $portal->linkedSupplierIds())
            ->whereNull('supplier.deleted_at')
            ->select('supplier.id', 'supplier.store_id', 'supplier.default_tax_rate', 'supplier.is_tax_exempt', 'store.name as store_name')
            ->orderBy('store.name')
            ->get()
            ->unique('store_id')
            ->values();
    }
}
