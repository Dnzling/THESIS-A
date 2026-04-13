<?php

namespace App\Http\Controllers\Api\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class EcommerceActiveStockProductsController extends Controller
{
    private static ?bool $hasProductTaxRateColumn = null;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 16), 1), 100);

        $selectColumns = [
            'id',
            'store_id',
            'sku',
            'product_name',
            'description',
            'category_id',
            'base_price',
            'discounted_price',
        ];

        if ($this->productHasTaxRateColumn()) {
            $selectColumns[] = 'tax_rate';
        }

        $query = Product::query()
            ->select($selectColumns)
            ->with([
                'category:id,category_name',
                'assets:id,product_id,file_path,asset_type,is_primary,display_order,created_at',
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('store', function ($storeQuery) {
                $storeQuery->where(function ($query) {
                    $query->whereNull('subscription_tier')
                        ->orWhere('subscription_tier', '!=', 'free');
                });
            })
            ->whereHas('inventory', function ($inventoryQuery) {
                $inventoryQuery
                    ->where('quantity_available', '>', 0)
                    ->whereIn('stock_status', ['in_stock', 'low_stock']);
            });

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        $sort = (string) $request->input('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderByRaw('COALESCE(discounted_price, base_price, 0) ASC');
        } elseif ($sort === 'price_desc') {
            $query->orderByRaw('COALESCE(discounted_price, base_price, 0) DESC');
        } else {
            $query->orderByDesc('id');
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

        $products->getCollection()->transform(function (Product $product) use ($inventoryMap) {
            $inventory = $inventoryMap->get($product->id);
            $imageAsset = $this->selectBestImage($product);

            return [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'sku' => $product->sku,
                'product_name' => $product->product_name,
                'description' => $product->description,
                'category_id' => $product->category_id,
                'category' => $product->category?->category_name,
                'price' => round((float) ($product->discounted_price ?? $product->base_price ?? 0), 2),
                'tax_rate' => (float) ($product->tax_rate ?? 0),
                // Prefer the signed/served asset URL to avoid relying on /storage symlinks in production.
                'image' => $imageAsset?->url ? (string) $imageAsset->url : $this->toAssetUrl($imageAsset?->file_path),
                'quantity_available' => (int) ($inventory?->quantity_available ?? 0),
                'stock_status' => (string) ($inventory?->stock_status ?? 'out_of_stock'),
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

    private function productHasTaxRateColumn(): bool
    {
        if (self::$hasProductTaxRateColumn !== null) {
            return self::$hasProductTaxRateColumn;
        }

        self::$hasProductTaxRateColumn = Schema::hasColumn('products', 'tax_rate');
        return self::$hasProductTaxRateColumn;
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
