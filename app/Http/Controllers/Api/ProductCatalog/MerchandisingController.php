<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\Category;
use Illuminate\Support\Facades\DB;

class MerchandisingController extends Controller
{
    public function dashboard(Request $request)
    {
        $storeId = $request->user()->store_id;
        
        $stats = [
            'totalProducts' => Product::where('store_id', $storeId)->count(),
            'totalCategories' => Category::where('store_id', $storeId)->count(),
            'totalValue' => Product::where('store_id', $storeId)
                ->sum(DB::raw('price')),
            'topCategories' => Category::where('categories.store_id', $storeId)
                ->leftJoin('products', 'categories.id', '=', 'products.category_id')
                ->select('categories.id', 'categories.name')
                ->selectRaw('COUNT(products.id) as count')
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('value')
                ->limit(4)
                ->get(),
            'recentProducts' => Product::where('store_id', $storeId)
                ->with('category')
                ->latest()
                ->limit(5)
                ->get(),
        ];
        
        return response()->json(['data' => $stats]);
    }
    
    public function products(Request $request)
    {
        $query = Product::where('store_id', $request->user()->store_id)
            ->with('category');
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        return $query->paginate($request->perPage ?? 10);
    }
    
    public function categories(Request $request)
    {
        return Category::where('store_id', $request->user()->store_id)
            ->paginate($request->perPage ?? 1000);
    }
}