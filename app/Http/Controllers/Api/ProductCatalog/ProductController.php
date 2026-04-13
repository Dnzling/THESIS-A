<?php
// app/Http/Controllers/Api/ProductCatalog/ProductController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\Hr\Employee;
use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
use App\Models\ProductCatalog\PricingHistory;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Store\Branch;
use App\Models\Inventory\ReorderRule;
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

            if ($request->has('product_type')) {
                $query->where('product_type', $request->product_type);
            }

            if ($request->has('stock_status')) {
                $query->where('stock_status', $request->stock_status);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $request->boolean('is_active'));
            }

            if ($request->has('price_approval_status') && $request->price_approval_status) {
                $query->where('price_approval_status', $request->price_approval_status);
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
                'unit_id' => 'nullable|exists:units,id',
                'product_type' => 'nullable|in:raw_material,finished_good',
                'brand' => 'nullable|string|max:100',
                'collection_name' => 'nullable|string|max:100',
                'base_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'discounted_price' => 'nullable|numeric|min:0',
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

            if (!empty($validated['discounted_price']) && !empty($validated['base_price']) && $validated['discounted_price'] >= $validated['base_price']) {
                DB::rollBack();
                return $this->errorResponse('Discounted price must be less than base price', 422);
            }

            $data = $validated;
            $data['store_id'] = $this->getStoreId();
            $data['stock_status'] = 'In Stock';
            $data['product_type'] = $validated['product_type'] ?? 'finished_good';
            $data = $this->applyTypeSpecificDefaults($data);
                
                $product = Product::create($data);

                // Auto-register the new product in branch inventory for all active branches (zero stock).
                $this->ensureBranchInventoryRowsForProduct($product->id, (int) $this->getStoreId());

                // Create pricing history entry
            if (!is_null($product->base_price)) {
                PricingHistory::create([
                    'store_id' => $this->getStoreId(),
                    'product_id' => $product->id,
                    'old_price' => 0,
                    'new_price' => $product->base_price,
                    'price_type' => 'Base',
                    'reason' => 'Initial pricing',
                    'effective_date' => now(),
                    'created_by' => $this->getActorEmployeeId()
                ]);
            }

                DB::commit();

                try {
                    $product->loadMissing('suppliers');
                    if ($product->suppliers->count() === 0) {
                        $rfqNumber = 'RFQ-' . date('YmdHis') . '-' . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
                        $rfq = RequestForQuotation::create([
                            'rfq_number' => $rfqNumber,
                            'store_id' => $this->getStoreId(),
                            'purchase_requisition_id' => null,
                            'title' => "New Product Sourcing: {$product->product_name}",
                            'description' => 'Auto-created RFQ for new product without suppliers.',
                            'rfq_type' => 'purchase',
                            'currency' => 'PHP',
                            'issue_date' => now()->toDateString(),
                            'deadline_date' => now()->addDays(7)->toDateString(),
                            'status' => 'draft',
                            'created_by' => auth()->user()?->employee?->id ?? $this->getEmployeeId(),
                        ]);

                        RFQItem::create([
                            'rfq_id' => $rfq->id,
                            'product_id' => $product->id,
                            'variation_id' => null,
                            'quantity' => 1,
                            'specifications' => null,
                            'requirements' => null,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Auto RFQ creation failed', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                }

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
                                 'unit',
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

            $product->cost_price = $product->getRawOriginal('cost_price');
            $product->makeVisible(['cost_price']);

            // Get related products
            $product->related = $product->relatedProducts()
                                        ->with('relatedProduct')
                                        ->strongest()
                                        ->get();

            $payload = $product->toArray();
            $payload['cost_price'] = $product->getRawOriginal('cost_price');

            return $this->successResponse($payload, 'Product retrieved successfully');

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
                'unit_id' => 'nullable|exists:units,id',
                'product_type' => 'nullable|in:raw_material,finished_good',
                'brand' => 'nullable|string|max:100',
                'collection_name' => 'nullable|string|max:100',
                'base_price' => 'sometimes|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'discounted_price' => 'nullable|numeric|min:0|lt:base_price',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'weight_kg' => 'nullable|numeric|min:0',
                'assembly_required' => 'boolean',
                'is_featured' => 'boolean',
                'is_new_arrival' => 'boolean',
                'is_bestseller' => 'boolean',
                'is_active' => 'boolean',
                'stock_status' => 'in:In Stock,Low Stock,Out of Stock,Pre-order',
                'meta_title' => 'nullable|string|max:200',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|string',
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
                $isPriceUpdateRequested = $this->isPriceUpdateRequested($product, $data);
                if ($isPriceUpdateRequested) {
                    $data = $this->removeLivePriceFields($data);
                    $data['pending_base_price'] = array_key_exists('base_price', $validated)
                        ? $validated['base_price']
                        : $product->pending_base_price;
                    $data['pending_discounted_price'] = array_key_exists('discounted_price', $validated)
                        ? $validated['discounted_price']
                        : $product->pending_discounted_price;
                    $data['price_approval_status'] = 'pending';
                    $data['price_proposed_by'] = $this->getUserId();
                    $data['price_proposed_at'] = now();
                    $data['price_approved_by'] = null;
                    $data['price_approved_at'] = null;
                    $data['price_rejected_by'] = null;
                    $data['price_rejected_at'] = null;
                    $data['price_approval_notes'] = $validated['price_change_reason'] ?? null;
                }

                $data = $this->applyTypeSpecificDefaults($data, $product);

                $product->update($data);

                // If the acting user can approve pricing, auto-approve immediately
                if ($this->isFinancePriceApprover()) {
                    DB::beginTransaction();
                    try {
                        $oldPrice = $product->base_price;

                        $product->update([
                            'base_price' => $product->pending_base_price ?? $product->base_price,
                            'discounted_price' => $product->pending_discounted_price,
                            'price_approval_status' => 'approved',
                            'price_approved_by' => $this->getUserId(),
                            'price_approved_at' => now(),
                            'price_rejected_by' => null,
                            'price_rejected_at' => null,
                            'price_approval_notes' => $data['price_approval_notes'] ?? $product->price_approval_notes,
                            'pending_base_price' => null,
                            'pending_discounted_price' => null,
                        ]);

                        if (!is_null($product->base_price) && $oldPrice != $product->base_price) {
                            \App\Models\ProductCatalog\PricingHistory::create([
                                'store_id' => $this->getStoreId(),
                                'product_id' => $product->id,
                                'old_price' => $oldPrice ?? 0,
                                'new_price' => $product->base_price,
                                'price_type' => 'Base',
                                'reason' => $data['price_approval_notes'] ?? 'Auto-approved by finance permission',
                                'effective_date' => now(),
                                'created_by' => $this->getActorEmployeeId()
                            ]);
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                }

                DB::commit();

                $fresh = $product->fresh(['category', 'subcategory']);
                $message = $isPriceUpdateRequested
                    ? 'Price change submitted for finance approval'
                    : 'Product updated successfully';

                return $this->successResponse(
                    $fresh,
                    $message
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

    public function approvePrice(Request $request, $id)
    {
        try {
            if (!$this->isFinancePriceApprover()) {
                return $this->errorResponse('You do not have permission to approve product pricing.', 403);
            }

            $validated = $this->validateRequest($request, [
                'notes' => 'nullable|string|max:500',
            ]);

            $product = Product::byStore($this->getStoreId())->findOrFail($id);
            if ($product->price_approval_status !== 'pending') {
                return $this->errorResponse('No pending price request to approve.', 422);
            }

            DB::beginTransaction();
            try {
                $oldPrice = $product->base_price;

                $product->update([
                    'base_price' => $product->pending_base_price ?? $product->base_price,
                    'discounted_price' => $product->pending_discounted_price,
                    'price_approval_status' => 'approved',
                    'price_approved_by' => $this->getUserId(),
                    'price_approved_at' => now(),
                    'price_rejected_by' => null,
                    'price_rejected_at' => null,
                    'price_approval_notes' => $validated['notes'] ?? $product->price_approval_notes,
                    'pending_base_price' => null,
                    'pending_discounted_price' => null,
                ]);

                if (!is_null($product->base_price) && $oldPrice != $product->base_price) {
                    PricingHistory::create([
                        'store_id' => $this->getStoreId(),
                        'product_id' => $product->id,
                        'old_price' => $oldPrice ?? 0,
                        'new_price' => $product->base_price,
                        'price_type' => 'Base',
                        'reason' => $validated['notes'] ?? 'Finance approved price change',
                        'effective_date' => now(),
                        'created_by' => $this->getActorEmployeeId()
                    ]);
                }

                DB::commit();

                return $this->successResponse($product->fresh(), 'Price change approved');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve price change: ' . $e->getMessage(), 500, [], $e);
        }
    }

    public function rejectPrice(Request $request, $id)
    {
        try {
            if (!$this->isFinancePriceApprover()) {
                return $this->errorResponse('You do not have permission to reject product pricing.', 403);
            }

            $validated = $this->validateRequest($request, [
                'reason' => 'required|string|max:500',
            ]);

            $product = Product::byStore($this->getStoreId())->findOrFail($id);
            if ($product->price_approval_status !== 'pending') {
                return $this->errorResponse('No pending price request to reject.', 422);
            }

            $product->update([
                'price_approval_status' => 'rejected',
                'price_rejected_by' => $this->getUserId(),
                'price_rejected_at' => now(),
                'price_approval_notes' => $validated['reason'],
                'pending_base_price' => null,
                'pending_discounted_price' => null,
            ]);

            return $this->successResponse($product->fresh(), 'Price change rejected');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reject price change: ' . $e->getMessage(), 500, [], $e);
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

    private function isPriceUpdateRequested(Product $product, array $data): bool
    {
        $requestedBase = array_key_exists('base_price', $data);
        $requestedDiscount = array_key_exists('discounted_price', $data);
        if (!$requestedBase && !$requestedDiscount) {
            return false;
        }

        if ($requestedBase && (float) $data['base_price'] !== (float) $product->base_price) {
            return true;
        }

        if ($requestedDiscount) {
            $incoming = $data['discounted_price'];
            $current = $product->discounted_price;
            if (($incoming === null && $current !== null) || ($incoming !== null && (float) $incoming !== (float) $current)) {
                return true;
            }
        }

        return false;
    }

    private function removeLivePriceFields(array $data): array
    {
        unset($data['base_price'], $data['discounted_price'], $data['price_change_reason']);
        return $data;
    }

    private function isFinancePriceApprover(): bool
    {
        return $this->userHasAnyPermission([
            'finance.all.approve',
            'finance.settings.approve.store',
            'finance.settings.approve.all',
            'finance.purchase-orders.approve',
            'finance.pricing.approve',
            'finance.price-approvals.approve',
        ]);
    }

    /**
     * Keep raw materials non-promotional and non-discounted.
     */
    private function applyTypeSpecificDefaults(array $data, ?Product $existing = null): array
    {
        $type = $data['product_type'] ?? $existing?->product_type ?? 'finished_good';
        if ($type !== 'raw_material') {
            return $data;
        }

        $data['discounted_price'] = null;
        $data['is_featured'] = false;
        $data['is_new_arrival'] = false;
        $data['is_bestseller'] = false;

        return $data;
    }

    /**
     * Ensure each active branch has a base branch_inventory row for this product.
     * Stock starts at 0 and must move through inventory transactions.
     */
    private function ensureBranchInventoryRowsForProduct(int $productId, int $storeId): void
    {
        $branches = Branch::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->pluck('id');

        foreach ($branches as $branchId) {
            $inventory = BranchInventory::query()->firstOrCreate(
                [
                    'store_id' => $storeId,
                    'branch_id' => (int) $branchId,
                    'product_id' => $productId,
                    'variation_id' => null,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                    'quantity_damaged' => 0,
                    'quantity_incoming' => 0,
                    // Reorder settings now live in reorder_rules (primary).
                    // Keep these columns as legacy/fallback only.
                    'reorder_point' => 0,
                    'reorder_quantity' => 0,
                    'maximum_stock' => 1000,
                    'safety_stock' => 5,
                    'stock_status' => 'out_of_stock',
                    'unit_cost' => 0,
                    'average_cost' => 0,
                    'total_value' => 0,
                ]
            );

            ReorderRule::query()->firstOrCreate(
                [
                    'product_id' => $productId,
                    'branch_id' => (int) $branchId,
                ],
                [
                    'rule_type' => 'manual',
                    'trigger_type' => 'reorder_point',
                    'basis_type' => 'reorder_point',
                    'reorder_point' => 20,
                    'reorder_quantity' => 25,
                    'priority' => 'medium',
                    'auto_generate_po' => false,
                    'is_active' => true,
                ]
            );
        }
    }

    private function getActorEmployeeId(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        if (!empty($user->employee?->id)) {
            return (int) $user->employee->id;
        }

        $employeeId = Employee::query()
            ->where('user_id', (int) $user->id)
            ->where('store_id', (int) $this->getStoreId())
            ->value('id');

        return $employeeId ? (int) $employeeId : null;
    }
}
