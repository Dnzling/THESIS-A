<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\ProductVariation;
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
                ['range' => '₱0 - ₱10,000', 'count' => Product::byStore($storeId)->whereBetween('base_price', [0, 10000])->count()],
                ['range' => '₱10,001 - ₱25,000', 'count' => Product::byStore($storeId)->whereBetween('base_price', [10001, 25000])->count()],
                ['range' => '₱25,001 - ₱50,000', 'count' => Product::byStore($storeId)->whereBetween('base_price', [25001, 50000])->count()],
                ['range' => '₱50,001+', 'count' => Product::byStore($storeId)->where('base_price', '>', 50000)->count()],
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

    public function activityLog()
    {
        try {
            $storeId = $this->getStoreId();
            $perPage = request('per_page', 10);

            // Get activity logs from audit trail or create mock data
            // This assumes you have an activity_logs table
            $activities = DB::table('activity_logs')
                ->where('store_id', $storeId)
                ->where('module', 'merchandising')
                ->orderBy('created_at', 'desc')
                ->limit($perPage)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'description' => $log->description,
                        'details' => $log->details,
                        'user' => $log->user_name ?? 'System',
                        'created_at' => $log->created_at
                    ];
                });

            return response()->json([
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            // If activity_logs table doesn't exist, return empty array
            return response()->json([
                'data' => []
            ]);
        }
    }
}