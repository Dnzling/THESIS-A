<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
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
                'supplier_product.available_stock',
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
            ])
            ->orderBy('product.product_name')
            ->orderBy('store.name')
            ->paginate((int) ($validated['per_page'] ?? 25));

        $assets = ProductAsset::query()
            ->whereIn('product_id', collect($query->items())->pluck('product_id')->unique())
            ->whereIn('asset_type', ['Image_Main', 'Image_Gallery', 'Image_360'])
            ->orderByDesc('is_primary')
            ->orderBy('display_order')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        $query->getCollection()->transform(function ($row) use ($assets) {
            $baseCost = round((float) $row->supplier_price, 2);
            $taxRate = $row->is_tax_exempt ? 0.0 : (float) ($row->default_tax_rate ?? 12);
            $taxAmount = round($baseCost * ($taxRate / 100), 2);

            return [
                'id' => (int) $row->id,
                'supplier_id' => (int) $row->supplier_id,
                'product_id' => (int) $row->product_id,
                'store_id' => (int) $row->store_id,
                'store_name' => $row->store_name,
                'product_name' => $row->product_name,
                'product_sku' => $row->sku,
                'supplier_sku' => $row->supplier_sku,
                'product_type' => $row->product_type,
                'category_name' => $row->category_name,
                'unit_of_measurement' => $row->unit_of_measurement,
                'minimum_order_quantity' => (int) $row->minimum_order_quantity,
                'available_stock' => (int) $row->available_stock,
                'lead_time_days' => (int) $row->lead_time_days,
                'is_preferred_supplier' => (bool) $row->is_preferred_supplier,
                'is_active' => (bool) $row->is_active,
                'is_tax_exempt' => (bool) $row->is_tax_exempt,
                'base_cost' => $baseCost,
                'tax_rate' => round($taxRate, 2),
                'tax_amount' => $taxAmount,
                'cost_including_tax' => round($baseCost + $taxAmount, 2),
                'image_url' => $assets->get($row->product_id)?->thumbnail_url,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $query,
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

        $categoriesByStore = DB::table('categories')
            ->whereIn('store_id', $storeIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNull('parent_category_id')
            ->get(['store_id', 'category_name'])
            ->groupBy(fn ($category) => Str::lower(trim($category->category_name)));

        $categories = $categoriesByStore
            ->filter(fn ($rows) => $rows->pluck('store_id')->unique()->count() === $storeIds->count())
            ->map(fn ($rows, $key) => ['value' => $key, 'label' => $rows->first()->category_name])
            ->sortBy('label')
            ->values();

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', Rule::in(['finished_good', 'supply', 'raw_material', 'other'])],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'unit_of_measurement' => ['required', Rule::in(self::UNITS)],
            'base_cost' => ['required', 'numeric', 'min:0.01'],
            'minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'available_stock' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'product_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $portal = SupplierPortal::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($portal->status === 'approved', 403, 'Your supplier account must be approved before creating products.');

        $suppliers = $this->linkedSuppliers($portal);
        abort_if($suppliers->isEmpty(), 422, 'Link at least one store before creating a product.');

        $categoryKey = Str::lower(trim($validated['category']));
        $categories = DB::table('categories')
            ->whereIn('store_id', $suppliers->pluck('store_id'))
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNull('parent_category_id')
            ->get(['id', 'store_id', 'category_name'])
            ->filter(fn ($category) => Str::lower(trim($category->category_name)) === $categoryKey)
            ->keyBy('store_id');

        if ($categories->count() !== $suppliers->pluck('store_id')->unique()->count()) {
            return response()->json([
                'success' => false,
                'message' => 'The selected category is not available in every linked store.',
                'errors' => ['category' => ['Choose a category shared by every linked store.']],
            ], 422);
        }

        $sharedSku = 'SUP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));
        $imagePath = $request->hasFile('product_image')
            ? $request->file('product_image')->store("supplier-portals/{$portal->id}/products", 'public')
            : null;

        $created = DB::transaction(function () use ($validated, $suppliers, $categories, $sharedSku, $imagePath, $portal, $request) {
            return $suppliers->map(function ($supplier) use ($validated, $categories, $sharedSku, $imagePath, $portal, $request) {
                $product = Product::create([
                    'store_id' => $supplier->store_id,
                    'sku' => $sharedSku,
                    'product_name' => trim($validated['product_name']),
                    'description' => $validated['description'] ?? null,
                    'category_id' => $categories->get($supplier->store_id)->id,
                    'subcategory_id' => null,
                    'product_type' => $validated['product_type'],
                    'unit_of_measurement' => $validated['unit_of_measurement'],
                    'base_price' => 0,
                    'cost_price' => (float) $validated['base_cost'],
                    'initial_stock' => 0,
                    'reorder_point' => 10,
                    'is_active' => (bool) $validated['is_active'],
                ]);

                $listingId = DB::table('supplier_products')->insertGetId([
                    'supplier_id' => $supplier->id,
                    'product_id' => $product->id,
                    'supplier_sku' => $sharedSku,
                    'supplier_price' => (float) $validated['base_cost'],
                    'minimum_order_quantity' => (int) $validated['minimum_order_quantity'],
                    'lead_time_days' => (int) $validated['lead_time_days'],
                    'available_stock' => (int) $validated['available_stock'],
                    'is_preferred_supplier' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($imagePath) {
                    ProductAsset::create([
                        'store_id' => $supplier->store_id,
                        'product_id' => $product->id,
                        'asset_type' => 'Image_Main',
                        'file_name' => $request->file('product_image')->getClientOriginalName(),
                        'file_path' => $imagePath,
                        'file_size_kb' => round($request->file('product_image')->getSize() / 1024),
                        'mime_type' => $request->file('product_image')->getMimeType(),
                        'is_primary' => true,
                        'display_order' => 0,
                        'alt_text' => $validated['product_name'],
                    ]);
                }

                return ['listing_id' => $listingId, 'product_id' => $product->id, 'store_id' => (int) $supplier->store_id];
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Product created for all linked stores.',
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
            ->whereIn('supplier_product.supplier_id', $supplierIds)
            ->where('supplier_product.supplier_sku', $selected->supplier_sku)
            ->select('supplier_product.*', 'supplier.default_tax_rate', 'supplier.is_tax_exempt', 'store.name as store_name', 'product.product_name', 'product.description', 'product.product_type', 'product.unit_of_measurement', 'product.is_active', 'category.category_name')
            ->orderBy('store.name')
            ->get();

        $first = $rows->first();
        $stores = $rows->map(function ($row) {
            $base = (float) $row->supplier_price;
            $rate = $row->is_tax_exempt ? 0 : (float) ($row->default_tax_rate ?? 12);
            return [
                'store_name' => $row->store_name,
                'base_cost' => round($base, 2),
                'tax_rate' => round($rate, 2),
                'tax_amount' => round($base * $rate / 100, 2),
                'cost_including_tax' => round($base * (1 + $rate / 100), 2),
            ];
        });

        return response()->json(['success' => true, 'data' => [
            'id' => $listingId,
            'product_name' => $first->product_name,
            'description' => $first->description,
            'product_type' => $first->product_type,
            'category_name' => $first->category_name,
            'unit_of_measurement' => $first->unit_of_measurement,
            'minimum_order_quantity' => (int) $first->minimum_order_quantity,
            'lead_time_days' => (int) $first->lead_time_days,
            'available_stock' => (int) $first->available_stock,
            'is_active' => (bool) $first->is_active,
            'stores' => $stores,
        ]]);
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
