<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\Core\ActivityLog;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Core\PermissionService;

class DashboardController extends BaseController
{
    public function reports(Request $request, PermissionService $permissions)
    {
        $storeId = (int) $this->getStoreId();
        abort_unless($permissions->userHasPermission($request->user(), 'merchandising.reports.view', $storeId), 403);

        $filters = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90'],
            'branch_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ]);

        $days = (int) ($filters['days'] ?? 30);
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subSecond();
        $branches = DB::table('branches')->where('store_id', $storeId)
            ->where('branch_type', '!=', 'warehouse')->orderBy('name')
            ->get(['id', 'name', 'branch_type']);
        $branchIds = $branches->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (!empty($filters['branch_id'])) {
            abort_unless(in_array((int) $filters['branch_id'], $branchIds, true), 404);
            $branchIds = [(int) $filters['branch_id']];
        }

        $posSales = DB::table('sales_pos_order_items as items')
            ->join('sales_pos_orders as orders', 'orders.id', '=', 'items.order_id')
            ->leftJoin('products as catalog_products', function ($join) use ($storeId) {
                $join->on('catalog_products.id', '=', 'items.product_id')->where('catalog_products.store_id', '=', $storeId);
            })
            ->where('orders.store_id', $storeId)->whereIn('orders.branch_id', $branchIds)
            ->where(fn ($query) => $query->where('orders.status', 'completed')->orWhereIn('orders.payment_status', ['paid', 'succeeded', 'completed']))
            ->whereNotIn('orders.status', ['cancelled', 'voided', 'refunded', 'rejected'])
            ->selectRaw("orders.branch_id, items.product_id, catalog_products.category_id, items.product_name as name, items.sku, items.quantity as units, items.line_total as sales, orders.created_at as sold_at, CONCAT('pos-', orders.id) as order_key");

        $onlineSales = DB::table('ecommerce_order_items as items')
            ->join('ecommerce_orders as orders', 'orders.id', '=', 'items.order_id')
            ->join('branch_inventory as inventory', 'inventory.id', '=', 'items.branch_inventory_id')
            ->leftJoin('products as catalog_products', function ($join) use ($storeId) {
                $join->on('catalog_products.id', '=', 'items.product_id')->where('catalog_products.store_id', '=', $storeId);
            })
            ->where('orders.store_id', $storeId)->whereIn('inventory.branch_id', $branchIds)
            ->whereIn('orders.payment_status', ['paid', 'succeeded', 'completed'])
            ->whereNotIn('orders.status', ['cancelled', 'refunded', 'rejected'])
            ->selectRaw("inventory.branch_id, items.product_id, catalog_products.category_id, items.product_name as name, items.sku, items.quantity as units, items.line_total as sales, orders.created_at as sold_at, CONCAT('ecommerce-', orders.id) as order_key");

        $salesRows = $posSales->unionAll($onlineSales);
        $current = DB::query()->fromSub(clone $salesRows, 'product_sales')->whereBetween('sold_at', [$start, $end]);
        $previous = DB::query()->fromSub(clone $salesRows, 'product_sales')->whereBetween('sold_at', [$previousStart, $previousEnd]);
        foreach ([$current, $previous] as $salesQuery) {
            if (!empty($filters['category_id'])) {
                $salesQuery->where('category_id', (int) $filters['category_id']);
            }
            if (!empty($filters['search'])) {
                $term = '%' . trim($filters['search']) . '%';
                $salesQuery->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            }
        }
        $currentTotals = (clone $current)->selectRaw('COALESCE(SUM(sales), 0) as sales, COALESCE(SUM(units), 0) as units, COUNT(DISTINCT product_id) as products, COUNT(DISTINCT order_key) as orders')->first();
        $previousTotals = (clone $previous)->selectRaw('COALESCE(SUM(sales), 0) as sales, COALESCE(SUM(units), 0) as units, COUNT(DISTINCT product_id) as products, COUNT(DISTINCT order_key) as orders')->first();

        $dailyRows = (clone $current)->selectRaw('DATE(sold_at) as day, SUM(sales) as sales, SUM(units) as units')->groupByRaw('DATE(sold_at)')->get()->keyBy('day');
        $trend = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $row = $dailyRows->get($date->toDateString());
            $trend[] = ['date' => $date->toDateString(), 'label' => $date->format('M j'), 'sales' => (float) ($row->sales ?? 0), 'units' => (int) ($row->units ?? 0)];
        }

        $categories = DB::table('categories')->where('store_id', $storeId)->whereNull('deleted_at')->orderBy('category_name')->get(['id', 'category_name']);
        $productsQuery = Product::query()->with('category:id,category_name')
            ->where('store_id', $storeId)->where('product_type', 'finished_good')->where('is_active', true);
        if (!empty($filters['category_id'])) {
            abort_unless($categories->contains(fn ($category) => (int) $category->id === (int) $filters['category_id']), 404);
            $productsQuery->where('category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $productsQuery->where(fn ($query) => $query->where('product_name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $productPage = $productsQuery->orderBy('product_name')->paginate($perPage);
        $pageIds = $productPage->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $currentProductStats = (clone $current)->whereIn('product_id', $pageIds)
            ->selectRaw('product_id, SUM(units) as units, SUM(sales) as sales, COUNT(DISTINCT branch_id) as branches_selling')
            ->groupBy('product_id')->get()->keyBy('product_id');
        $previousProductStats = (clone $previous)->whereIn('product_id', $pageIds)
            ->selectRaw('product_id, SUM(units) as units, SUM(sales) as sales')
            ->groupBy('product_id')->get()->keyBy('product_id');
        $productPage->getCollection()->transform(function (Product $product) use ($currentProductStats, $previousProductStats) {
            $stats = $currentProductStats->get($product->id);
            $previousStats = $previousProductStats->get($product->id);
            $sales = (float) ($stats?->sales ?? 0);
            $previousSales = (float) ($previousStats?->sales ?? 0);
            return [
                'id' => (int) $product->id,
                'name' => (string) $product->product_name,
                'sku' => $product->sku,
                'category' => $product->category?->category_name,
                'units_sold' => (int) ($stats?->units ?? 0),
                'previous_units_sold' => (int) ($previousStats?->units ?? 0),
                'sales' => $sales,
                'previous_sales' => $previousSales,
                'sales_change_percent' => $previousSales > 0 ? (($sales - $previousSales) / $previousSales) * 100 : null,
                'branches_selling' => (int) ($stats?->branches_selling ?? 0),
            ];
        });

        $categorySales = (clone $current)->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(sales) as sales')->groupBy('category_id')->get()->keyBy('category_id');
        $previousCategorySales = (clone $previous)->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(sales) as sales')->groupBy('category_id')->get()->keyBy('category_id');
        $categoryBreakdown = $categories->map(function ($category) use ($categorySales, $previousCategorySales) {
            $sales = (float) ($categorySales->get($category->id)?->sales ?? 0);
            $previousSales = (float) ($previousCategorySales->get($category->id)?->sales ?? 0);
            return [
                'name' => (string) $category->category_name,
                'sales' => $sales,
                'previous_sales' => $previousSales,
                'change_percent' => $previousSales > 0 ? (($sales - $previousSales) / $previousSales) * 100 : null,
            ];
        })->filter(fn ($row) => $row['sales'] > 0 || $row['previous_sales'] > 0)->values();
        $branchSales = (clone $current)->selectRaw('branch_id, SUM(sales) as sales')->groupBy('branch_id')->get()->keyBy('branch_id');
        $previousBranchSales = (clone $previous)->selectRaw('branch_id, SUM(sales) as sales')->groupBy('branch_id')->get()->keyBy('branch_id');
        $activeProducts = Product::query()->where('store_id', $storeId)->where('product_type', 'finished_good')->where('is_active', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'summary' => [
                    'sales' => (float) ($currentTotals->sales ?? 0),
                    'previous_sales' => (float) ($previousTotals->sales ?? 0),
                    'units_sold' => (int) ($currentTotals->units ?? 0),
                    'previous_units_sold' => (int) ($previousTotals->units ?? 0),
                    'products_with_sales' => (int) ($currentTotals->products ?? 0),
                    'previous_products_with_sales' => (int) ($previousTotals->products ?? 0),
                    'previous_orders' => (int) ($previousTotals->orders ?? 0),
                    'active_products' => $activeProducts,
                    'orders' => (int) ($currentTotals->orders ?? 0),
                ],
                'trend' => $trend,
                'category_sales' => $categoryBreakdown,
                'branch_sales' => $branches->map(function ($branch) use ($branchSales, $previousBranchSales) {
                    $sales = (float) ($branchSales->get($branch->id)?->sales ?? 0);
                    $previousSales = (float) ($previousBranchSales->get($branch->id)?->sales ?? 0);
                    return [
                        'id' => (int) $branch->id,
                        'name' => (string) $branch->name,
                        'sales' => $sales,
                        'previous_sales' => $previousSales,
                        'change_percent' => $previousSales > 0 ? (($sales - $previousSales) / $previousSales) * 100 : null,
                    ];
                })->values(),
                'branches' => $branches->map(fn ($branch) => ['id' => (int) $branch->id, 'name' => (string) $branch->name])->values(),
                'categories' => $categories->map(fn ($category) => ['id' => (int) $category->id, 'name' => (string) $category->category_name])->values(),
                'products' => $productPage,
            ],
        ]);
    }

    public function overview(Request $request)
    {
        try {
            $storeId = $this->getStoreId();
            $days = in_array((int) $request->integer('days', 30), [7, 30, 90], true)
                ? (int) $request->integer('days', 30) : 30;
            $start = now()->subDays($days - 1)->startOfDay();
            $previousStart = $start->copy()->subDays($days);
            $end = now()->endOfDay();
            $branches = DB::table('branches')
                ->where('store_id', $storeId)
                ->where('branch_type', '!=', 'warehouse')
                ->orderBy('name')
                ->get(['id', 'name', 'branch_type']);
            $branchIds = $branches->pluck('id')->all();

            $posSales = DB::table('sales_pos_order_items as items')
                ->join('sales_pos_orders as orders', 'orders.id', '=', 'items.order_id')
                ->where('orders.store_id', $storeId)
                ->whereIn('orders.branch_id', $branchIds)
                ->where(function ($query) {
                    $query->where('orders.status', 'completed')
                        ->orWhereIn('orders.payment_status', ['paid', 'succeeded', 'completed']);
                })
                ->whereNotIn('orders.status', ['cancelled', 'voided', 'refunded', 'rejected'])
                ->selectRaw("orders.branch_id, items.product_id, items.variation_id, items.product_name as name, items.sku, items.quantity as units, items.line_total as sales, orders.created_at as sold_at, CONCAT('pos-', orders.id) as order_key, 'POS' as source");

            $ecommerceSales = DB::table('ecommerce_order_items as items')
                ->join('ecommerce_orders as orders', 'orders.id', '=', 'items.order_id')
                ->join('branch_inventory as inventory', 'inventory.id', '=', 'items.branch_inventory_id')
                ->where('orders.store_id', $storeId)
                ->whereIn('inventory.branch_id', $branchIds)
                ->whereIn('orders.payment_status', ['paid', 'succeeded', 'completed'])
                ->whereNotIn('orders.status', ['cancelled', 'refunded', 'rejected'])
                ->selectRaw("inventory.branch_id, items.product_id, inventory.variation_id, items.product_name as name, items.sku, items.quantity as units, items.line_total as sales, orders.created_at as sold_at, CONCAT('ecommerce-', orders.id) as order_key, 'Online' as source");

            $salesRows = $posSales->unionAll($ecommerceSales);
            $currentSales = DB::query()->fromSub(clone $salesRows, 'product_sales')
                ->whereBetween('sold_at', [$start, $end]);
            $previousSalesRows = DB::query()->fromSub(clone $salesRows, 'product_sales')
                ->whereBetween('sold_at', [$previousStart, $start->copy()->subSecond()]);

            $totals = (clone $currentSales)->selectRaw('COALESCE(SUM(sales), 0) as sales, COALESCE(SUM(units), 0) as units, COUNT(DISTINCT order_key) as orders')->first();
            $previousTotals = (clone $previousSalesRows)->selectRaw('COALESCE(SUM(sales), 0) as sales, COALESCE(SUM(units), 0) as units, COUNT(DISTINCT order_key) as orders')->first();
            $daily = (clone $currentSales)->selectRaw('DATE(sold_at) as day, SUM(sales) as sales, SUM(units) as units, COUNT(DISTINCT order_key) as orders')
                ->groupByRaw('DATE(sold_at)')->get()->keyBy('day');
            $salesTrend = [];
            for ($offset = 0; $offset < $days; $offset++) {
                $date = $start->copy()->addDays($offset);
                $row = $daily->get($date->toDateString());
                $salesTrend[] = ['date' => $date->toDateString(), 'label' => $date->format('M j'), 'sales' => (float) ($row->sales ?? 0), 'units' => (int) ($row->units ?? 0), 'orders' => (int) ($row->orders ?? 0)];
            }

            $topProducts = (clone $currentSales)
                ->selectRaw('product_id as id, name, sku, SUM(units) as units, SUM(sales) as sales, COUNT(DISTINCT branch_id) as branches_selling')
                ->groupBy('product_id', 'name', 'sku')->orderByDesc('sales')->limit(10)->get()
                ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name, 'sku' => $row->sku, 'units' => (int) $row->units, 'sales' => (float) $row->sales, 'branches_selling' => (int) $row->branches_selling]);

            $branchSales = (clone $currentSales)
                ->selectRaw('branch_id, SUM(sales) as sales, SUM(units) as units, COUNT(DISTINCT order_key) as orders, COUNT(DISTINCT product_id) as products_sold')
                ->groupBy('branch_id')->get()->keyBy('branch_id');
            $branchPreviousSales = (clone $previousSalesRows)
                ->selectRaw('branch_id, SUM(sales) as sales')->groupBy('branch_id')->get()->keyBy('branch_id');
            $branchStock = DB::table('branch_inventory as inventory')
                ->join('products', 'products.id', '=', 'inventory.product_id')
                ->where('inventory.store_id', $storeId)->whereIn('inventory.branch_id', $branchIds)
                ->whereNull('inventory.deleted_at')->whereNull('products.deleted_at')
                ->where('products.product_type', 'finished_good')->where('products.is_active', true)
                ->selectRaw('inventory.branch_id, COUNT(DISTINCT inventory.product_id) as stocked_products, SUM(inventory.quantity_available) as available_units')
                ->groupBy('inventory.branch_id')->get()->keyBy('branch_id');

            $branchPerformance = $branches->map(function ($branch) use ($branchSales, $branchPreviousSales, $branchStock) {
                $current = $branchSales->get($branch->id);
                $previous = (float) ($branchPreviousSales->get($branch->id)?->sales ?? 0);
                return [
                    'id' => (int) $branch->id,
                    'name' => (string) $branch->name,
                    'branch_type' => (string) $branch->branch_type,
                    'sales' => (float) ($current?->sales ?? 0),
                    'previous_sales' => $previous,
                    'sales_change_percent' => $previous > 0 ? round((((float) ($current?->sales ?? 0) - $previous) / $previous) * 100, 1) : null,
                    'units_sold' => (int) ($current?->units ?? 0),
                    'orders' => (int) ($current?->orders ?? 0),
                    'products_sold' => (int) ($current?->products_sold ?? 0),
                    'stocked_products' => (int) ($branchStock->get($branch->id)?->stocked_products ?? 0),
                    'available_units' => (int) ($branchStock->get($branch->id)?->available_units ?? 0),
                ];
            })->sortByDesc('sales')->values();

            $activeProducts = Product::query()->where('store_id', $storeId)
                ->where('product_type', 'finished_good')->where('is_active', true);
            $listedProducts = (int) (clone $activeProducts)->count();
            $outOfStockCount = (int) (clone $activeProducts)->whereDoesntHave('inventory', fn ($q) => $q
                ->whereIn('branch_id', $branchIds)->where('quantity_available', '>', 0)->where('stock_status', '!=', 'out_of_stock'))->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'period_days' => $days,
                    'summary' => [
                        'sales' => (float) ($totals->sales ?? 0),
                        'orders' => (int) ($totals->orders ?? 0),
                        'average_order_value' => !empty($totals->orders) ? round((float) $totals->sales / (int) $totals->orders, 2) : 0,
                        'units_sold' => (int) ($totals->units ?? 0),
                        'previous_sales' => (float) ($previousTotals->sales ?? 0),
                        'previous_units_sold' => (int) ($previousTotals->units ?? 0),
                        'previous_orders' => (int) ($previousTotals->orders ?? 0),
                        'branches_with_sales' => $branchPerformance->where('orders', '>', 0)->count(),
                        'branch_count' => $branches->count(),
                        'active_listings' => $listedProducts,
                        'out_of_stock_products' => $outOfStockCount,
                    ],
                    'sales_trend' => $salesTrend,
                    'top_products' => $topProducts,
                    'branch_performance' => $branchPerformance,
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Product Catalog dashboard overview failed', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load Product Catalog performance.',
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
