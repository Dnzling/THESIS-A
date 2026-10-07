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
    public function overview(Request $request)
    {
        try {
            $storeId = $this->getStoreId();
            $days = in_array((int) $request->integer('days', 30), [7, 30, 90], true)
                ? (int) $request->integer('days', 30) : 30;
            $start = now()->subDays($days - 1)->startOfDay();
            $previousStart = $start->copy()->subDays($days);
            $validOrder = fn ($query) => $query->whereNotIn('status', ['cancelled', 'rejected']);
            $orders = DB::table('ecommerce_orders')->where('store_id', $storeId);
            $currentOrders = (clone $orders)->where('created_at', '>=', $start);
            $previousOrders = (clone $orders)->where('created_at', '>=', $previousStart)->where('created_at', '<', $start);
            $orderCount = (int) (clone $currentOrders)->count();
            $validOrderCount = (int) $validOrder(clone $currentOrders)->count();
            $sales = (float) $validOrder(clone $currentOrders)->sum('total_amount');
            $previousSales = (float) $validOrder(clone $previousOrders)->sum('total_amount');
            $previousOrderCount = (int) (clone $previousOrders)->count();

            $daily = (clone $currentOrders)
                ->selectRaw("DATE(created_at) as day, COUNT(*) as orders, SUM(CASE WHEN status NOT IN ('cancelled', 'rejected') THEN total_amount ELSE 0 END) as sales")
                ->groupByRaw('DATE(created_at)')
                ->get()
                ->keyBy('day');
            $salesTrend = [];
            for ($offset = 0; $offset < $days; $offset++) {
                $date = $start->copy()->addDays($offset);
                $row = $daily->get($date->toDateString());
                $salesTrend[] = [
                    'date' => $date->toDateString(),
                    'label' => $date->format('M j'),
                    'sales' => (float) ($row->sales ?? 0),
                    'orders' => (int) ($row->orders ?? 0),
                ];
            }

            $statusBreakdown = (clone $currentOrders)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => ['status' => (string) $row->status, 'count' => (int) $row->total]);

            $topProducts = DB::table('ecommerce_order_items as items')
                ->join('ecommerce_orders as orders', 'orders.id', '=', 'items.order_id')
                ->where('orders.store_id', $storeId)
                ->where('orders.created_at', '>=', $start)
                ->whereNotIn('orders.status', ['cancelled', 'rejected'])
                ->selectRaw('items.product_id, items.product_name, items.sku, SUM(items.quantity) as units, SUM(items.line_total) as sales')
                ->groupBy('items.product_id', 'items.product_name', 'items.sku')
                ->orderByDesc('sales')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->product_id,
                    'name' => (string) $row->product_name,
                    'sku' => $row->sku,
                    'units' => (int) $row->units,
                    'sales' => (float) $row->sales,
                ]);

            $unitsSold = (int) DB::table('ecommerce_order_items as items')
                ->join('ecommerce_orders as orders', 'orders.id', '=', 'items.order_id')
                ->where('orders.store_id', $storeId)
                ->where('orders.created_at', '>=', $start)
                ->whereNotIn('orders.status', ['cancelled', 'rejected'])
                ->sum('items.quantity');

            $activeProducts = Product::query()->where('store_id', $storeId)
                ->where('product_type', 'finished_good')->where('is_active', true);
            $listedProducts = (int) (clone $activeProducts)->count();
            $missingImage = (clone $activeProducts)->whereDoesntHave('assets', fn ($q) => $q->where('asset_type', 'Image_Main'));
            $missingImageCount = (int) (clone $missingImage)->count();
            $with3d = (int) (clone $activeProducts)->whereHas('assets', fn ($q) => $q->where('asset_type', '3D_Model'))->count();
            $outOfStock = (clone $activeProducts)->whereDoesntHave('inventory', fn ($q) => $q
                ->where('quantity_available', '>', 0)->where('stock_status', '!=', 'out_of_stock'));
            $outOfStockCount = (int) (clone $outOfStock)->count();

            $mapProduct = fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->product_name,
                'sku' => $product->sku,
                'category' => $product->category?->category_name,
                'base_price' => (float) ($product->base_price ?? 0),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period_days' => $days,
                    'summary' => [
                        'order_value' => $sales,
                        'orders' => $orderCount,
                        'average_order_value' => $validOrderCount ? round($sales / $validOrderCount, 2) : 0,
                        'units_sold' => $unitsSold,
                        'previous_order_value' => $previousSales,
                        'previous_orders' => $previousOrderCount,
                        'active_listings' => $listedProducts,
                        'image_ready_products' => max(0, $listedProducts - $missingImageCount),
                        'missing_main_images' => $missingImageCount,
                        'products_with_3d' => $with3d,
                        'out_of_stock_products' => $outOfStockCount,
                    ],
                    'sales_trend' => $salesTrend,
                    'status_breakdown' => $statusBreakdown,
                    'top_products' => $topProducts,
                    'missing_images' => (clone $missingImage)
                        ->with('category:id,category_name')
                        ->oldest('created_at')
                        ->limit(5)
                        ->get()
                        ->map($mapProduct),
                    'out_of_stock_products' => (clone $outOfStock)
                        ->with('category:id,category_name')
                        ->orderBy('product_name')
                        ->limit(5)
                        ->get()
                        ->map($mapProduct),
                    'recent_orders' => (clone $currentOrders)
                        ->latest('created_at')
                        ->limit(6)
                        ->get(['id', 'order_number', 'shipping_name', 'status', 'payment_status', 'total_amount', 'created_at']),
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('E-Commerce dashboard overview failed', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load E-Commerce dashboard.',
            ], 500);
        }
    }

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
