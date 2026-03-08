<?php
// app/Http/Controllers/Api/ProductCatalog/ProductController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\PricingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductController extends BaseController
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        try {
            $query = Product::byStore($this->getStoreId())
                           ->with(['category:id,category_name', 'subcategory:id,category_name'])
                           ->withCount(['variations', 'assets']);

            // Filters
            if ($request->has('category_id')) {
                $query->byCategory($request->category_id);
            }

            if ($request->boolean('featured_only')) {
                $query->featured();
            }

            if ($request->boolean('new_arrivals_only')) {
                $query->newArrivals();
            }

            if ($request->boolean('in_stock_only')) {
                $query->inStock();
            }

            if ($request->has('price_min') && $request->has('price_max')) {
                $query->priceRange($request->price_min, $request->price_max);
            }

            if ($request->has('brand')) {
                $query->where('brand', $request->brand);
            }

            // Search
            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('product_name', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = ['product_name', 'base_price', 'created_at', 'updated_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortOrder);
            }

            $products = $query->paginate($request->get('per_page', 15));

            return $this->successResponse($products, 'Products retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve products', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve products',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'sku' => 'required|string|max:50',
                'product_name' => 'required|string|max:200',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:categories,id',
                'subcategory_id' => 'nullable|exists:categories,id',
                'brand' => 'nullable|string|max:100',
                'collection_name' => 'nullable|string|max:100',
                'base_price' => 'required|numeric|min:0',
                'discounted_price' => 'nullable|numeric|min:0|lt:base_price',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'weight_kg' => 'nullable|numeric|min:0',
                'assembly_required' => 'boolean',
                'is_featured' => 'boolean',
                'is_new_arrival' => 'boolean',
                'is_bestseller' => 'boolean',
                'meta_title' => 'nullable|string|max:200',
                'meta_description' => 'nullable|string',
                'published_at' => 'nullable|date'
            ]);

            // Verify category belongs to this store
            $category = \App\Models\ProductCatalog\Category::byStore($this->getStoreId())
                        ->find($validated['category_id']);
            
            if (!$category) {
                return $this->errorResponse('Category not found or does not belong to this store', 422);
            }

            DB::beginTransaction();

            try {
                // Check if SKU is unique for this store
                $exists = Product::byStore($this->getStoreId())
                                ->where('sku', $validated['sku'])
                                ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->errorResponse('SKU already exists for this store', 422);
                }

                $data = $validated;
                $data['store_id'] = $this->getStoreId();
                $data['stock_status'] = 'In Stock';
                
                $product = Product::create($data);

                // Create pricing history entry
                PricingHistory::create([
                    'store_id' => $this->getStoreId(),
                    'product_id' => $product->id,
                    'old_price' => 0,
                    'new_price' => $product->base_price,
                    'price_type' => 'Base',
                    'reason' => 'Initial pricing',
                    'effective_date' => now(),
                    'created_by' => $this->getUserId()
                ]);

                DB::commit();

                return $this->successResponse(
                    $product->load('category'),
                    'Product created successfully',
                    201
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Failed to create product', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to create product: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        try {
            $product = Product::byStore($this->getStoreId())
                             ->with([
                                 'category',
                                 'subcategory',
                                 'attributes.attribute',
                                 'assets' => function($query) {
                                     $query->orderBy('display_order');
                                 },
                                 'variations' => function($query) {
                                     $query->active()->with('custom3dModel');
                                 },
                                 'tags'
                             ])
                             ->withCount(['variations', 'assets'])
                             ->findOrFail($id);

            // Get related products
            $product->related = $product->relatedProducts()
                                        ->with('relatedProduct')
                                        ->strongest()
                                        ->get();

            return $this->successResponse($product, 'Product retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve product', [
                'store_id' => $this->getStoreId(),
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve product',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Get product with 3D assets only (optimized for 3D viewer).
     */
    public function get3dData($id)
    {
        try {
            $product = Product::byStore($this->getStoreId())
                             ->select('id', 'sku', 'product_name', 'description', 'length_cm', 'width_cm', 'height_cm')
                             ->with(['assets' => function($query) {
                                 $query->whereIn('asset_type', ['3D_Model', '3D_Thumbnail'])
                                       ->orderBy('is_primary', 'desc')
                                       ->orderBy('display_order');
                             }])
                             ->findOrFail($id);

            // Format for 3D viewer
            $data = [
                'product_id' => $product->id,
                'name' => $product->product_name,
                'sku' => $product->sku,
                'description' => $product->description,
                'dimensions' => [
                    'length' => $product->length_cm,
                    'width' => $product->width_cm,
                    'height' => $product->height_cm,
                    'formatted' => $product->dimensions
                ],
                'primary_model' => $product->primary_3d_model ? [
                    'id' => $product->primary_3d_model->id,
                    'url' => $product->primary_3d_model->url,
                    'format' => $product->primary_3d_model->model_format,
                    'camera_settings' => $product->primary_3d_model->camera_settings,
                    'ar_compatible' => $product->primary_3d_model->is_ar_compatible
                ] : null,
                'all_models' => $product->all_3d_assets->map(function($asset) {
                    return [
                        'id' => $asset->id,
                        'type' => $asset->asset_type,
                        'url' => $asset->url,
                        'thumbnail' => $asset->thumbnail_url,
                        'format' => $asset->model_format,
                        'is_primary' => $asset->is_primary,
                        'ar_compatible' => $asset->is_ar_compatible,
                        'camera_settings' => $asset->camera_settings
                    ];
                })
            ];

            return $this->successResponse($data, '3D data retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve 3D data', [
                'store_id' => $this->getStoreId(),
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve 3D data',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::byStore($this->getStoreId())->findOrFail($id);

            $validated = $this->validateRequest($request, [
                'sku' => 'sometimes|string|max:50|unique:products,sku,' . $id . ',id,store_id,' . $this->getStoreId(),
                'product_name' => 'sometimes|string|max:200',
                'description' => 'nullable|string',
                'category_id' => 'sometimes|exists:categories,id',
                'subcategory_id' => 'nullable|exists:categories,id',
                'brand' => 'nullable|string|max:100',
                'collection_name' => 'nullable|string|max:100',
                'base_price' => 'sometimes|numeric|min:0',
                'discounted_price' => 'nullable|numeric|min:0|lt:base_price',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'is_active' => 'boolean',
                'stock_status' => 'in:In Stock,Low Stock,Out of Stock,Pre-order',
                'published_at' => 'nullable|date',
                'price_change_reason' => 'required_if:base_price,changed|string|nullable'
            ]);

            // If category changed, verify it belongs to store
            if (isset($validated['category_id'])) {
                $category = \App\Models\ProductCatalog\Category::byStore($this->getStoreId())
                            ->find($validated['category_id']);
                
                if (!$category) {
                    return $this->errorResponse('Category not found or does not belong to this store', 422);
                }
            }

            DB::beginTransaction();

            try {
                $oldPrice = $product->base_price;
                $data = $validated;
                
                $product->update($data);

                // Create pricing history if price changed
                if (isset($data['base_price']) && $data['base_price'] != $oldPrice) {
                    PricingHistory::create([
                        'store_id' => $this->getStoreId(),
                        'product_id' => $product->id,
                        'old_price' => $oldPrice,
                        'new_price' => $product->base_price,
                        'price_type' => 'Base',
                        'reason' => $validated['price_change_reason'] ?? 'Price update',
                        'effective_date' => now(),
                        'created_by' => $this->getUserId()
                    ]);
                }

                DB::commit();

                return $this->successResponse(
                    $product->fresh(['category', 'subcategory']),
                    'Product updated successfully'
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to update product', [
                'store_id' => $this->getStoreId(),
                'product_id' => $id,
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to update product: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        try {
            $product = Product::byStore($this->getStoreId())->findOrFail($id);

            DB::beginTransaction();

            try {
                // Soft delete related data
                $product->assets()->delete();
                $product->variations()->delete();
                $product->attributes()->delete();
                $product->delete();

                DB::commit();

                return $this->successResponse(null, 'Product deleted successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete product', [
                'store_id' => $this->getStoreId(),
                'product_id' => $id,
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete product: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Bulk update products status.
     */
    public function bulkStatus(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_ids' => 'required|array',
                'product_ids.*' => 'required|integer',
                'is_active' => 'required|boolean'
            ]);

            DB::beginTransaction();

            try {
                // Verify all products belong to this store
                $products = Product::byStore($this->getStoreId())
                                  ->whereIn('id', $validated['product_ids'])
                                  ->get();

                if ($products->count() !== count($validated['product_ids'])) {
                    DB::rollBack();
                    return $this->errorResponse('One or more products not found or do not belong to this store', 422);
                }

                Product::byStore($this->getStoreId())
                       ->whereIn('id', $validated['product_ids'])
                       ->update(['is_active' => $validated['is_active']]);

                DB::commit();

                return $this->successResponse(
                    ['updated_count' => count($validated['product_ids'])],
                    'Products updated successfully'
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Failed to bulk update products', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to update products: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_ids' => 'required|array',
                'product_ids.*' => 'required|integer'
            ]);

            DB::beginTransaction();

            try {
                $products = Product::byStore($this->getStoreId())
                                  ->whereIn('id', $validated['product_ids'])
                                  ->get();

                if ($products->count() !== count($validated['product_ids'])) {
                    DB::rollBack();
                    return $this->errorResponse('One or more products not found or do not belong to this store', 422);
                }

                $deleted = 0;
                foreach ($products as $product) {
                    $product->assets()->delete();
                    $product->variations()->delete();
                    $product->attributes()->delete();
                    $product->delete();
                    $deleted++;
                }

                DB::commit();

                return $this->successResponse(
                    ['deleted_count' => $deleted],
                    'Products deleted successfully'
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete products', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete products: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }
}