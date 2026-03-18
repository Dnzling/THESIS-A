<?php
// backend/app/Http/Controllers/Api/Procurement/ProductController.php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog\Product;
use App\Models\Procurement\Supplier\SupplierPrice;
use App\Models\Procurement\Inventory\ProcurementInventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Get products with supplier pricing for procurement
     * GET /api/procurement/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = $request->get('branch_id', auth()->user()->branch_id);

            $query = Product::where('store_id', $storeId)
                ->with([
                    'category:id,category_name',
                    'suppliers' => function($q) {
                        $q->active()
                          ->select('suppliers.id', 'suppliers.supplier_name', 'suppliers.rating')
                          ->with(['priceHistory' => function($sq) {
                              $sq->active()
                                ->orderBy('effective_date', 'desc')
                                ->limit(1);
                          }]);
                    }
                ])
                ->withCount(['variations']);

            // Filters
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('product_type')) {
                $query->where('product_type', $request->product_type);
            }

            if ($request->has('status')) {
                $statusMap = [
                    'in_stock' => 'in_stock',
                    'low_stock' => 'low_stock',
                    'out_of_stock' => 'out_of_stock',
                ];
                
                $status = $statusMap[$request->status] ?? null;
                if ($status) {
                    // Will need inventory join to filter by status
                    $query->whereHas('branchInventories', function($q) use ($branchId, $status) {
                        $q->where('branch_id', $branchId)
                          ->where('status', $status);
                    }, '>=', 0);
                }
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $allowedSorts = ['product_name', 'sku', 'base_price', 'created_at'];
            
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortOrder);
            }

            $products = $query->paginate($request->get('per_page', 15));

            // Enrich with procurement inventory and pricing data
            $products->getCollection()->transform(function($product) use ($storeId) {
                $procInventory = ProcurementInventory::where('store_id', $storeId)
                    ->where('product_id', $product->id)
                    ->first();

                // Enrich with procurement inventory data
                $product->available_qty = $procInventory?->available_qty ?? 0;
                $product->on_order_qty = $procInventory?->on_order_qty ?? 0;
                $product->received_qty = $procInventory?->received_qty ?? 0;
                $product->pending_receive_qty = $procInventory?->pending_receive_qty ?? 0;
                $product->total_qty_tracked = $procInventory?->total_qty_tracked ?? 0;

                // Get best supplier price
                $bestPrice = $product->suppliers()
                    ->orderBy(DB::raw('priceHistory.unit_price'))
                    ->first();

                $product->best_supplier = $bestPrice;
                $product->best_price = $bestPrice?->priceHistory?->first()?->unit_price ?? null;

                return $product;
            });

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Products retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve procurement products', [
                'store_id' => auth()->user()->store_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product detail with full supplier and pricing information
     * GET /api/procurement/products/{id}
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = $request->get('branch_id', auth()->user()->branch_id);

            $product = Product::where('store_id', $storeId)
                ->with([
                    'category:id,category_name',
                    'suppliers' => function($q) {
                        $q->select('suppliers.id', 'suppliers.supplier_name', 'suppliers.rating')
                          ->active();
                    },
                    'suppliers.priceHistory' => function($q) {
                        $q->orderBy('effective_date', 'desc')
                          ->limit(5); // Last 5 prices
                    }
                ])
                ->findOrFail($id);

            // Get inventory
            $inventory = $product->branchInventories()
                ->where('branch_id', $branchId)
                ->first();

            $product->current_stock = $inventory?->quantity_on_hand ?? 0;
            $product->quantity_on_orders = $inventory?->quantity_on_orders ?? 0;
            $product->reorder_point = $inventory?->reorder_point ?? 0;
            $product->last_purchase_date = $inventory?->last_purchase_date;
            $product->last_purchase_price = $inventory?->last_purchase_price;

            // Get purchase history from this branch
            $product->purchase_history = DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                ->where('purchase_order_items.product_id', $id)
                ->where('purchase_orders.branch_id', $branchId)
                ->select('purchase_orders.po_number', 'purchase_orders.supplier_id', 'purchase_orders.created_at',
                         'purchase_order_items.unit_cost', 'purchase_order_items.quantity_ordered')
                ->orderBy('purchase_orders.created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product detail retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve product detail', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product'
            ], 500);
        }
    }

    /**
     * Get all suppliers for a product with pricing
     * GET /api/procurement/products/{id}/suppliers
     */
    public function getSuppliers(int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            $suppliers = $product->suppliers()
                ->active()
                ->with(['priceHistory' => function($q) {
                    $q->where('product_id', $id)
                      ->active()
                      ->orderBy('effective_date', 'desc')
                      ->limit(1);
                }])
                ->get()
                ->map(function($supplier) {
                    return [
                        'id' => $supplier->id,
                        'supplier_name' => $supplier->supplier_name,
                        'supplier_code' => $supplier->supplier_code,
                        'rating' => $supplier->rating,
                        'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
                        'contact_person' => $supplier->contact_person,
                        'phone' => $supplier->phone,
                        'current_price' => $supplier->priceHistory?->first()?->unit_price,
                        'minimum_order_qty' => $supplier->priceHistory?->first()?->minimum_order_quantity,
                        'lead_time_days' => $supplier->priceHistory?->first()?->lead_time_days,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $suppliers,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve product suppliers', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve suppliers'
            ], 500);
        }
    }

    /**
     * Get purchase history for a product
     * GET /api/procurement/products/{id}/history
     */
    public function getHistory(int $id, Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id', auth()->user()->branch_id);
            $limit = $request->get('limit', 10);

            $history = DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->where('purchase_order_items.product_id', $id)
                ->where('purchase_orders.branch_id', $branchId)
                ->select(
                    'purchase_orders.po_number',
                    'suppliers.supplier_name',
                    'purchase_orders.created_at as order_date',
                    'purchase_order_items.unit_cost',
                    'purchase_order_items.quantity_ordered as quantity',
                    DB::raw('purchase_order_items.unit_cost * purchase_order_items.quantity_ordered as total_amount')
                )
                ->orderBy('purchase_orders.created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve product history', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve history'
            ], 500);
        }
    }
}
