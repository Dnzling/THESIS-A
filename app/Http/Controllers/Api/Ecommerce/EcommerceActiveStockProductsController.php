<?php

namespace App\Http\Controllers\Api\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceProductReview;
use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class EcommerceActiveStockProductsController extends Controller
{
    private static ?bool $hasProductTaxRateColumn = null;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 16), 1), 100);

        $selectColumns = [
            'products.id',
            'products.store_id',
            'products.sku',
            'products.product_name',
            'products.product_type',
            'products.description',
            'products.category_id',
            'products.base_price',
            'products.discounted_price',
            'products.created_at',
        ];

        if ($this->productHasTaxRateColumn()) {
            $selectColumns[] = 'products.tax_rate';
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlySales = DB::table('ecommerce_order_items as monthly_items')
            ->join('ecommerce_orders as monthly_orders', 'monthly_orders.id', '=', 'monthly_items.order_id')
            ->whereIn('monthly_orders.status', ['delivered', 'completed'])
            ->whereBetween(DB::raw('COALESCE(monthly_orders.placed_at, monthly_orders.created_at)'), [$monthStart, $monthEnd])
            ->groupBy('monthly_items.product_id')
            ->selectRaw('monthly_items.product_id, SUM(monthly_items.quantity) as monthly_sales');

        $query = Product::query()
            ->select($selectColumns)
            ->addSelect(DB::raw('COALESCE(monthly_product_sales.monthly_sales, 0) as monthly_sales'))
            ->leftJoinSub($monthlySales, 'monthly_product_sales', function ($join) {
                $join->on('monthly_product_sales.product_id', '=', 'products.id');
            })
            ->with([
                'category:id,category_name',
                'assets:id,product_id,file_path,asset_type,is_primary,display_order,created_at',
            ])
            ->where('products.product_type', 'finished_good')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->whereHas('store', function ($storeQuery) {
                $storeQuery->whereIn('status', ['active', 'verified']);
            })
            ->whereHas('inventory', function ($inventoryQuery) {
                $inventoryQuery
                    ->where('quantity_available', '>', 0)
                    ->whereIn('stock_status', ['in_stock', 'low_stock']);
            });

        if ($request->filled('store_id')) {
            $query->where('products.store_id', (int) $request->input('store_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('products.product_name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->whereIn('products.category_id', $this->relatedCategoryIds((int) $request->input('category_id')));
        }

        if ($request->boolean('discounted_only')) {
            $query
                ->whereNotNull('products.discounted_price')
                ->where('products.discounted_price', '>', 0)
                ->whereColumn('products.discounted_price', '<', 'products.base_price');
        }

        $sort = (string) $request->input('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderByRaw('COALESCE(discounted_price, base_price, 0) ASC');
        } elseif ($sort === 'price_desc') {
            $query->orderByRaw('COALESCE(discounted_price, base_price, 0) DESC');
        } elseif ($sort === 'discount') {
            $query->orderByRaw('((products.base_price - products.discounted_price) / NULLIF(products.base_price, 0)) DESC');
        } elseif ($sort === 'latest') {
            $query->orderByDesc('products.id');
        } else {
            $query->orderByDesc('monthly_sales')->orderByDesc('products.id');
        }

        $products = $query->paginate($perPage);
        $productIds = $products->getCollection()->pluck('id')->values();

        $inventoryMap = BranchInventory::query()
            ->select(['product_id', 'quantity_available', 'stock_status'])
            ->whereIn('product_id', $productIds)
            ->where('quantity_available', '>', 0)
            ->whereIn('stock_status', ['in_stock', 'low_stock'])
            ->orderByDesc('quantity_available')
            ->get()
            ->groupBy('product_id')
            ->map(fn($group) => $group->first());

        $ratingMap = EcommerceProductReview::query()
            ->selectRaw('product_id, COALESCE(AVG(rating), 0) as rating_avg, COUNT(*) as rating_count')
            ->whereIn('product_id', $productIds)
            ->where('status', 'published')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $products->getCollection()->transform(function (Product $product) use ($inventoryMap, $ratingMap) {
            $inventory = $inventoryMap->get($product->id);
            $rating = $ratingMap->get($product->id);
            $imageAsset = $this->selectBestImage($product);
            $basePrice = round((float) ($product->base_price ?? 0), 2);
            $discountedPrice = is_null($product->discounted_price)
                ? null
                : round((float) $product->discounted_price, 2);
            $hasDiscount = $basePrice > 0 && !is_null($discountedPrice) && $discountedPrice > 0 && $discountedPrice < $basePrice;
            $discountPercentage = $hasDiscount
                ? (int) round((($basePrice - $discountedPrice) / $basePrice) * 100)
                : 0;

            return [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'sku' => $product->sku,
                'product_name' => $product->product_name,
                'product_type' => $product->product_type,
                'description' => $product->description,
                'category_id' => $product->category_id,
                'category' => $product->category?->category_name,
                'price' => $hasDiscount ? $discountedPrice : $basePrice,
                'base_price' => $basePrice,
                'discounted_price' => $hasDiscount ? $discountedPrice : null,
                'has_discount' => $hasDiscount,
                'discount_percentage' => $discountPercentage,
                'tax_rate' => (float) ($product->tax_rate ?? 0),
                // Prefer the signed/served asset URL to avoid relying on /storage symlinks in production.
                'image' => $imageAsset?->url ? (string) $imageAsset->url : $this->toAssetUrl($imageAsset?->file_path),
                'rating_avg' => round((float) ($rating?->rating_avg ?? 0), 2),
                'rating_count' => (int) ($rating?->rating_count ?? 0),
                'quantity_available' => (int) ($inventory?->quantity_available ?? 0),
                'stock_status' => (string) ($inventory?->stock_status ?? 'out_of_stock'),
                'monthly_sales' => (int) ($product->monthly_sales ?? 0),
                'created_at' => optional($product->created_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
            'meta' => [
                'allowed_stock_statuses' => ['in_stock', 'low_stock'],
                'active_only' => true,
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->select(['id', 'category_name', 'ecommerce_icon_path'])
            ->where('is_active', true)
            ->where('is_ecommerce_quick_select', true)
            ->whereNull('deleted_at')
            ->whereHas('products', function ($productQuery) {
                $productQuery
                    ->where('product_type', 'finished_good')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->whereHas('store', function ($storeQuery) {
                        $storeQuery->whereIn('status', ['active', 'verified']);
                    })
                    ->whereHas('inventory', function ($inventoryQuery) {
                        $inventoryQuery
                            ->where('quantity_available', '>', 0)
                            ->whereIn('stock_status', ['in_stock', 'low_stock']);
                    });
            })
            ->withCount(['products as available_products_count' => function ($productQuery) {
                $productQuery
                    ->where('product_type', 'finished_good')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->whereHas('store', function ($storeQuery) {
                        $storeQuery->whereIn('status', ['active', 'verified']);
                    })
                    ->whereHas('inventory', function ($inventoryQuery) {
                        $inventoryQuery
                            ->where('quantity_available', '>', 0)
                            ->whereIn('stock_status', ['in_stock', 'low_stock']);
                    });
            }])
            ->orderBy('category_name')
            ->get();

        $grouped = collect();
        foreach ($categories as $category) {
            $groupIndex = $grouped->search(fn ($group) => $this->categoryNamesAreRelated(
                $group['representative']->category_name,
                $category->category_name
            ));

            if ($groupIndex === false) {
                $grouped->push([
                    'representative' => $category,
                    'category_ids' => [$category->id],
                    'available_products_count' => (int) $category->available_products_count,
                ]);
                continue;
            }

            $group = $grouped[$groupIndex];
            $group['category_ids'][] = $category->id;
            $group['available_products_count'] += (int) $category->available_products_count;
            $grouped[$groupIndex] = $group;
        }

        $categories = $grouped->map(function ($group) {
            $category = $group['representative'];
            return [
                'id' => $category->id,
                'category_ids' => $group['category_ids'],
                'category_name' => $category->category_name,
                'available_products_count' => $group['available_products_count'],
                'icon_url' => $this->toAssetUrl($category->ecommerce_icon_path),
            ];
        })->sortBy('category_name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function topStores(int $categoryId): JsonResponse
    {
        $category = Category::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->findOrFail($categoryId);

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlySales = DB::table('ecommerce_order_items as monthly_items')
            ->join('ecommerce_orders as monthly_orders', 'monthly_orders.id', '=', 'monthly_items.order_id')
            ->whereIn('monthly_orders.status', ['delivered', 'completed'])
            ->whereBetween(DB::raw('COALESCE(monthly_orders.placed_at, monthly_orders.created_at)'), [$monthStart, $monthEnd])
            ->groupBy('monthly_items.product_id')
            ->selectRaw('monthly_items.product_id, SUM(monthly_items.quantity) as monthly_sales');

        $rankedStores = Product::query()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoinSub($monthlySales, 'category_monthly_sales', function ($join) {
                $join->on('category_monthly_sales.product_id', '=', 'products.id');
            })
            ->whereIn('products.category_id', $this->relatedCategoryIds($category->id))
            ->where('products.product_type', 'finished_good')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->whereHas('inventory', function ($inventoryQuery) {
                $inventoryQuery
                    ->where('quantity_available', '>', 0)
                    ->whereIn('stock_status', ['in_stock', 'low_stock']);
            })
            ->groupBy('products.store_id')
            ->selectRaw('products.store_id, COUNT(DISTINCT products.id) as matching_products_count, COALESCE(SUM(category_monthly_sales.monthly_sales), 0) as monthly_sales')
            ->orderByDesc('monthly_sales')
            ->orderByDesc('matching_products_count')
            ->limit(10)
            ->get()
            ->keyBy('store_id');

        $stores = Store::query()
            ->select(['id', 'name', 'city', 'settings'])
            ->whereIn('id', $rankedStores->keys())
            ->whereIn('status', ['active', 'verified'])
            ->get()
            ->sortBy(function (Store $store) use ($rankedStores) {
                $rank = $rankedStores->get($store->id);
                return sprintf('%012d-%012d', PHP_INT_MAX - (int) ($rank?->monthly_sales ?? 0), PHP_INT_MAX - (int) ($rank?->matching_products_count ?? 0));
            })
            ->values()
            ->map(function (Store $store) use ($rankedStores) {
                $rank = $rankedStores->get($store->id);
                $settings = is_array($store->settings) ? $store->settings : [];
                $logo = $settings['logo'] ?? $settings['logo_path'] ?? null;

                return [
                    'id' => $store->id,
                    'store_name' => $store->name,
                    'city' => $store->city,
                    'store_logo' => $this->toAssetUrl($logo),
                    'matching_products_count' => (int) ($rank?->matching_products_count ?? 0),
                    'monthly_sales' => (int) ($rank?->monthly_sales ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'category' => ['id' => $category->id, 'name' => $category->category_name],
                'stores' => $stores,
            ],
        ]);
    }

    private function productHasTaxRateColumn(): bool
    {
        if (self::$hasProductTaxRateColumn !== null) {
            return self::$hasProductTaxRateColumn;
        }

        self::$hasProductTaxRateColumn = Schema::hasColumn('products', 'tax_rate');
        return self::$hasProductTaxRateColumn;
    }

    private function relatedCategoryIds(int $categoryId): array
    {
        $selected = Category::query()->where('is_active', true)->whereNull('deleted_at')->find($categoryId);
        if (!$selected) {
            return [$categoryId];
        }

        return Category::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'category_name'])
            ->filter(fn (Category $candidate) => $this->categoryNamesAreRelated($selected->category_name, $candidate->category_name))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function categoryNamesAreRelated(string $first, string $second): bool
    {
        $a = $this->normalizeCategoryName($first);
        $b = $this->normalizeCategoryName($second);
        if ($a === $b || levenshtein($a, $b) <= 2) {
            return true;
        }

        $aWords = array_values(array_unique(explode(' ', $a)));
        $bWords = array_values(array_unique(explode(' ', $b)));
        $shared = count(array_intersect($aWords, $bWords));
        return min(count($aWords), count($bWords)) >= 2
            && ($shared / max(count($aWords), count($bWords))) >= 0.67;
    }

    private function normalizeCategoryName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/u', ' ', $name) ?: '';
        $words = array_filter(explode(' ', trim($name)));
        $words = array_map(function (string $word) {
            if (strlen($word) > 3 && str_ends_with($word, 'ies')) {
                return substr($word, 0, -3).'y';
            }
            if (strlen($word) > 3 && str_ends_with($word, 's') && !str_ends_with($word, 'ss')) {
                return substr($word, 0, -1);
            }
            return $word;
        }, $words);
        sort($words);
        return implode(' ', $words);
    }

    private function selectBestImage(Product $product)
    {
        $imageAssets = $product->assets
            ->filter(fn($asset) => $this->isImageAsset($asset->asset_type) && !empty($asset->file_path));

        if ($imageAssets->isEmpty()) {
            return null;
        }

        return $imageAssets
            ->sortBy([['is_primary', 'desc'], ['display_order', 'asc'], ['created_at', 'desc']])
            ->first();
    }

    private function isImageAsset(?string $assetType): bool
    {
        return in_array($assetType, ['Image_Main', 'Image_Gallery', 'Image_360'], true);
    }

    private function toAssetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalized = ltrim((string) $path, '/');
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = preg_replace('#^storage/#', '', $normalized);
        }

        $normalized = ltrim((string) $normalized, '/');
        if ($normalized === '') {
            return null;
        }

        if (Storage::disk('public')->exists($normalized)) {
            return asset('storage/' . $normalized);
        }

        if (Storage::disk('local')->exists($normalized)) {
            return Storage::disk('local')->url($normalized);
        }

        $publicStoragePath = public_path('storage/' . $normalized);
        if (is_file($publicStoragePath)) {
            return '/storage/' . $normalized;
        }

        $publicDirectPath = public_path($normalized);
        if (is_file($publicDirectPath)) {
            return '/' . $normalized;
        }

        return null;
    }
}
