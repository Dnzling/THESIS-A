<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\Core\ActivityLog;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    public function stats()
    {
        try {
            $storeId = $this->getStoreId();

            // Product statistics
            $totalProducts = Product::byStore($storeId)->count();
            $activeProducts = Product::byStore($storeId)->where('is_active', true)->count();
            $inactiveProducts = $totalProducts - $activeProducts;

            // Category statistics
            $totalCategories = Category::byStore($storeId)->whereNull('parent_category_id')->count();
            $totalSubcategories = Category::byStore($storeId)->whereNotNull('parent_category_id')->count();

            // Stock statistics
            $inStockProducts = Product::byStore($storeId)->where('stock_status', 'In Stock')->count();
            $lowStockProducts = Product::byStore($storeId)->where('stock_status', 'Low Stock')->count();
            $outOfStockProducts = Product::byStore($storeId)->where('stock_status', 'Out of Stock')->count();

            // Variation statistics
            $totalVariations = ProductVariation::byStore($storeId)->count();
            $activeVariations = ProductVariation::byStore($storeId)->where('is_active', true)->count();

            // Asset statistics
            $assets3D = ProductAsset::byStore($storeId)->where('asset_type', '3D_Model')->get();
            $assetsImages = ProductAsset::byStore($storeId)->whereIn('asset_type', ['Image_Main', 'Image_Gallery'])->get();
            
            $total3DModels = $assets3D->count();
            $totalImages = $assetsImages->count();
            $total3DSize = $assets3D->sum(fn($asset) => $asset->file_size_kb * 1024);
            $totalImageSize = $assetsImages->sum(fn($asset) => $asset->file_size_kb * 1024);

            // Price statistics
            $averagePrice = Product::byStore($storeId)->avg('base_price') ?? 0;
            $totalInventoryValue = Product::byStore($storeId)->sum('base_price');

            // Feature counts
            $featuredCount = Product::byStore($storeId)->where('is_featured', true)->count();
            $newArrivalCount = Product::byStore($storeId)->where('is_new_arrival', true)->count();
            $bestsellerCount = Product::byStore($storeId)->where('is_bestseller', true)->count();

            // Products by category
            $productsByCategory = Product::byStore($storeId)
                ->select('category_id', DB::raw('count(*) as count'))
                ->with('category:id,category_name')
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category_name' => $item->category->category_name ?? 'Uncategorized',
                        'count' => $item->count
                    ];
                });

            // Stock status distribution
            $stockStatusDistribution = Product::byStore($storeId)
                ->select('stock_status', DB::raw('count(*) as count'))
                ->groupBy('stock_status')
                ->get()
                ->map(function ($item) {
                    return [
                        'stock_status' => $item->stock_status,
                        'count' => $item->count
                    ];
                });

            // Price range distribution
            $priceRangeDistribution = [
                ['range' => 'PHP 0 - PHP 10,000', 'count' => Product::byStore($storeId)->whereBetween('base_price', [0, 10000])->count()],
                ['range' => 'PHP 10,001 - PHP 25,000', 'count' => Product::byStore($storeId)->whereBetween('base_price', [10001, 25000])->count()],
                ['range' => 'PHP 25,001 - PHP 50,000', 'count' => Product::byStore($storeId)->whereBetween('base_price', [25001, 50000])->count()],
                ['range' => 'PHP 50,001+', 'count' => Product::byStore($storeId)->where('base_price', '>', 50000)->count()],
            ];

            return response()->json([
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'inactive_products' => $inactiveProducts,
                'total_categories' => $totalCategories,
                'total_subcategories' => $totalSubcategories,
                'in_stock_products' => $inStockProducts,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'total_3d_models' => $total3DModels,
                'total_images' => $totalImages,
                'total_variations' => $totalVariations,
                'active_variations' => $activeVariations,
                'total_3d_size' => $total3DSize,
                'total_image_size' => $totalImageSize,
                'total_inventory_value' => $totalInventoryValue,
                'average_price' => round($averagePrice, 2),
                'featured_count' => $featuredCount,
                'new_arrival_count' => $newArrivalCount,
                'bestseller_count' => $bestsellerCount,
                'products_by_category' => $productsByCategory,
                'stock_status_distribution' => $stockStatusDistribution,
                'price_range_distribution' => $priceRangeDistribution
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activityLog(Request $request)
    {
        try {
            $storeId = $this->getStoreId();
            $perPage = (int) $request->input('per_page', 20);

            $query = ActivityLog::query()
                ->with(['user:id,fname,lname,email'])
                ->where('store_id', $storeId)
                ->where(function ($q) {
                    $q->where('entity_type', 'product_catalog')
                        ->orWhere('action', 'like', 'merchandising.%');
                });

            if ($request->filled('action')) {
                $query->where('action', 'like', '%' . trim((string) $request->input('action')) . '%');
            }

            if ($request->filled('entity_id')) {
                $query->where('entity_id', (int) $request->input('entity_id'));
            }

            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->input('from'));
            }

            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->input('to'));
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('fname', 'like', "%{$search}%")
                                ->orWhere('lname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $activities = $query->orderByDesc('created_at')->paginate($perPage);
            $activities->getCollection()->transform(function (ActivityLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'meta' => $log->meta,
                    'user' => trim(($log->user?->fname ?? '') . ' ' . ($log->user?->lname ?? '')) ?: ($log->user?->email ?? 'System'),
                    'created_at' => $log->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load activity logs',
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
