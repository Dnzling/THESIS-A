<?php
// backend/app/Http/Controllers/Api/Procurement/ProductController.php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use App\Models\Inventory\ReorderRule;
use App\Models\Inventory\BranchInventory;
use App\Models\Procurement\Supplier\SupplierPrice;
use App\Models\Procurement\Inventory\ProcurementInventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    /**
     * Get products with supplier pricing for procurement
     * GET /api/procurement/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if ($denied = $this->productReadAccessResponse()) {
                return $denied;
            }
            $storeId = auth()->user()->store_id;
            // No branch filter by default: procurement lists every product
            // recorded in every branch of the current store.
            $branchId = $request->get('branch_id');
            $includeCost = $request->boolean('include_cost', false);
            $filterSupplierId = $request->get('supplier_id');

            $query = Product::where('store_id', $storeId)
                // Procurement's product catalog is limited to finished goods.
                ->where('product_type', 'finished_good')
                ->with([
                    'category:id,category_name',
                    'suppliers' => function($q) {
                        $q->active()
                          ->select('suppliers.id', 'suppliers.supplier_name', 'suppliers.rating');
                    },
                    'inventory' => function ($q) use ($branchId) {
                        $q->with('branch:id,name')
                          ->select('id', 'product_id', 'branch_id', 'quantity_on_hand', 'stock_status', 'reorder_point')
                          ->when($branchId, fn ($inventory) => $inventory->where('branch_id', $branchId));
                    },
                ])
                ->withCount(['variations']);

            if ($filterSupplierId) {
                $query->whereHas('suppliers', function($q) use ($filterSupplierId, $storeId) {
                    $q->where('suppliers.id', $filterSupplierId)
                      ->where('suppliers.store_id', $storeId);
                });
            }

            $query->whereHas('inventory', function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

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
                    $query->whereHas('inventory', function($q) use ($branchId, $status) {
                        $q->where('stock_status', $status)
                          ->when($branchId, fn ($inventory) => $inventory->where('branch_id', $branchId));
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

            $allProducts = $query->get();
            $productMap = $allProducts->keyBy('id');
            $inventoryRows = BranchInventory::with('branch:id,name')
                ->where('store_id', $storeId)
                ->whereIn('product_id', $productMap->keys())
                ->when($branchId, fn ($inventory) => $inventory->where('branch_id', $branchId))
                ->orderBy('branch_id')
                ->orderBy('product_id')
                ->get();

            // Build one response row for every branch_inventory record. This
            // intentionally keeps the same product separate per branch.
            $branchRows = $inventoryRows->map(function ($inventory) use ($productMap) {
                $product = $productMap->get($inventory->product_id);
                if (!$product) return null;

                $row = clone $product;
                $row->setRelation('inventory', collect([$inventory]));
                return $row;
            })->filter()->values();

            $perPage = max(1, (int) $request->get('per_page', 15));
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $products = new LengthAwarePaginator(
                $branchRows->forPage($currentPage, $perPage)->values(),
                $branchRows->count(),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
            $branchCount = Branch::where('store_id', $storeId)->count();
            $branchNames = Branch::where('store_id', $storeId)->pluck('name', 'id');

            // Enrich with procurement inventory and pricing data
            $products->getCollection()->transform(function($product) use ($storeId, $includeCost, $branchCount, $branchNames) {
                $branchInventory = $product->inventory->first();
                $product->current_stock = (int) ($branchInventory?->quantity_on_hand ?? 0);
                $product->stock_status = $branchInventory?->stock_status;
                $product->reorder_point = (int) ($branchInventory?->reorder_point ?? 0);
                $product->branch_name = $branchNames->get($branchInventory?->branch_id) ?: $branchInventory?->branch?->name;
                $product->branch_id = $branchInventory?->branch_id;
                $product->branch = [
                    'id' => $branchInventory?->branch_id,
                    'name' => $product->branch_name,
                ];
                $product->quantity_on_hand = $product->current_stock;
                $product->branch_count = $branchCount;
                $procInventory = ProcurementInventory::where('store_id', $storeId)
                    ->where('product_id', $product->id)
                    ->first();

                // Enrich with procurement inventory data
                $product->available_qty = $procInventory?->available_qty ?? 0;
                $product->on_order_qty = $procInventory?->on_order_qty ?? 0;
                $product->received_qty = $procInventory?->received_qty ?? 0;
                $product->pending_receive_qty = $procInventory?->pending_receive_qty ?? 0;
                $product->total_qty_tracked = $procInventory?->total_qty_tracked ?? 0;

                $product->best_supplier = $product->suppliers->first();
                if ($includeCost) {
                    $product->cost_price = $product->getRawOriginal('cost_price');
                }

                $product->cost_price = $product->getRawOriginal('cost_price');
                $product->best_price = null;

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
            if ($denied = $this->productReadAccessResponse()) {
                return $denied;
            }
            $storeId = auth()->user()->store_id;
            $branchId = $request->get('branch_id', auth()->user()->branch_id);

            $includeCost = $request->boolean('include_cost', false);
            $withSuppliers = $request->boolean('with_suppliers', false);

            $product = Product::where('store_id', $storeId)
                ->with([
                    'category:id,category_name',
                    'assets:id,product_id,asset_type,file_name,file_path,is_primary,display_order',
                    'suppliers' => function($q) use ($withSuppliers) {
                        if ($withSuppliers) {
                            $q->select('suppliers.id', 'suppliers.supplier_name', 'suppliers.rating', 'suppliers.company_name')
                              ->withPivot('supplier_sku', 'supplier_price', 'minimum_order_quantity', 'lead_time_days', 'is_preferred_supplier')
                              ->active();
                        } else {
                            $q->select('suppliers.id', 'suppliers.supplier_name', 'suppliers.rating')
                              ->active();
                        }
                    }
                ])
                ->findOrFail($id);

            // Get inventory
            $inventory = $product->inventory()
                ->where('branch_id', $branchId)
                ->first();

            if ($includeCost) {
                $product->cost_price = $product->getRawOriginal('cost_price');
            }
            $product->cost_price = $product->getRawOriginal('cost_price');
            $product->current_stock = $inventory?->quantity_on_hand ?? 0;
            $product->branch_id = $inventory?->branch_id;
            $product->branch_name = $inventory?->branch?->name;
            $product->quantity_on_orders = $inventory?->quantity_on_orders ?? 0;
            $rule = ReorderRule::query()
                ->where('product_id', $id)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();
            $product->reorder_point = $rule?->reorder_point ?? ($inventory?->reorder_point ?? 0);
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
     * Product data is shared by procurement, inventory, and merchandising.
     * Keep the API protected while allowing users with a read permission in
     * any of those owning modules to consume the shared catalog.
     */
    private function productReadAccessResponse(): ?JsonResponse
    {
        $user = auth()->user();
        $storeId = $user?->store_id;
        $allowed = collect([
            'procurement.products.view',
            'inventory.products.view',
            'merchandising.products.view',
        ])->contains(fn (string $permission) => $user?->hasPermissionTo($permission, $storeId));

        return $allowed
            ? null
            : response()->json([
                'success' => false,
                'message' => 'Unauthorized to view procurement products.',
            ], 403);
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
