<?php
// app/Http/Controllers/Api/ProductCatalog/ProductVariationController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\ProductCatalog\PricingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductVariationController extends BaseController
{
    /**
     * Display a listing of variations.
     */
    public function index(Request $request)
    {
        try {
            $query = ProductVariation::byStore($this->getStoreId())
                                    ->with('product:id,product_name,sku,base_price');

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by in stock
            if ($request->boolean('in_stock_only')) {
                $query->inStock();
            }

            // Search
            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('variation_name', 'like', '%' . $request->search . '%')
                      ->orWhere('variation_sku', 'like', '%' . $request->search . '%')
                      ->orWhere('color', 'like', '%' . $request->search . '%')
                      ->orWhere('size', 'like', '%' . $request->search . '%')
                      ->orWhere('material', 'like', '%' . $request->search . '%');
                });
            }

            $variations = $query->orderBy('created_at', 'desc')
                               ->paginate($request->get('per_page', 15));

            return $this->successResponse($variations, 'Variations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve variations', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve variations',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Store a newly created variation.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_id' => 'required|exists:products,id',
                'variation_sku' => 'required|string|max:50',
                'variation_name' => 'required|string|max:200',
                'color' => 'nullable|string|max:50',
                'color_hex' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'size' => 'nullable|string|max:50',
                'material' => 'nullable|string|max:100',
                'price_adjustment' => 'required|numeric',
                'custom_3d_model_id' => 'nullable|exists:product_assets,id',
                'is_active' => 'boolean'
            ]);

            // Verify product belongs to this store
            $product = Product::byStore($this->getStoreId())->find($validated['product_id']);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            DB::beginTransaction();

            try {
                // Check if variation SKU is unique for this store
                $exists = ProductVariation::byStore($this->getStoreId())
                                         ->where('variation_sku', $validated['variation_sku'])
                                         ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->errorResponse('Variation SKU already exists for this store', 422);
                }

                $data = $validated;
                $data['store_id'] = $this->getStoreId();
                
                $variation = ProductVariation::create($data);

                // Create pricing history entry for variation
                if ($data['price_adjustment'] != 0) {
                    PricingHistory::create([
                        'store_id' => $this->getStoreId(),
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'old_price' => 0,
                        'new_price' => $product->base_price + $data['price_adjustment'],
                        'price_type' => 'Variation',
                        'reason' => 'Initial variation pricing',
                        'effective_date' => now(),
                        'created_by' => $this->getUserId()
                    ]);
                }

                DB::commit();

                return $this->successResponse(
                    $variation->load('product'),
                    'Variation created successfully',
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
            Log::error('Failed to create variation', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to create variation: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Display the specified variation.
     */
    public function show($id)
    {
        try {
            $variation = ProductVariation::byStore($this->getStoreId())
                                        ->with([
                                            'product',
                                            'custom3dModel',
                                            'pricingHistory' => function($query) {
                                                $query->orderBy('effective_date', 'desc')->limit(10);
                                            }
                                        ])
                                        ->findOrFail($id);

            // Add computed attributes
            $variationData = $variation->toArray();
            $variationData['final_price'] = $variation->final_price;
            $variationData['display_name'] = $variation->display_name;

            return $this->successResponse($variationData, 'Variation retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Variation not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve variation', [
                'store_id' => $this->getStoreId(),
                'variation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve variation',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Update the specified variation.
     */
    public function update(Request $request, $id)
    {
        try {
            $variation = ProductVariation::byStore($this->getStoreId())->findOrFail($id);

            $validated = $this->validateRequest($request, [
                'variation_sku' => 'sometimes|string|max:50|unique:product_variations,variation_sku,' . $id . ',id,store_id,' . $this->getStoreId(),
                'variation_name' => 'sometimes|string|max:200',
                'color' => 'nullable|string|max:50',
                'color_hex' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'size' => 'nullable|string|max:50',
                'material' => 'nullable|string|max:100',
                'price_adjustment' => 'sometimes|numeric',
                'custom_3d_model_id' => 'nullable|exists:product_assets,id',
                'is_active' => 'boolean',
                'price_change_reason' => 'required_if:price_adjustment,changed|string|nullable'
            ]);

            DB::beginTransaction();

            try {
                $oldPriceAdjustment = $variation->price_adjustment;
                
                $variation->update($validated);

                // Create pricing history if price adjustment changed
                if (isset($validated['price_adjustment']) && $validated['price_adjustment'] != $oldPriceAdjustment) {
                    PricingHistory::create([
                        'store_id' => $this->getStoreId(),
                        'product_id' => $variation->product_id,
                        'variation_id' => $variation->id,
                        'old_price' => $variation->product->base_price + $oldPriceAdjustment,
                        'new_price' => $variation->product->base_price + $validated['price_adjustment'],
                        'price_type' => 'Variation',
                        'reason' => $validated['price_change_reason'] ?? 'Price adjustment update',
                        'effective_date' => now(),
                        'created_by' => $this->getUserId()
                    ]);
                }

                DB::commit();

                return $this->successResponse(
                    $variation->fresh(['product', 'custom3dModel']),
                    'Variation updated successfully'
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
            return $this->errorResponse('Variation not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to update variation', [
                'store_id' => $this->getStoreId(),
                'variation_id' => $id,
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to update variation: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove the specified variation.
     */
    public function destroy($id)
    {
        try {
            $variation = ProductVariation::byStore($this->getStoreId())->findOrFail($id);

            DB::beginTransaction();

            try {
                $variation->delete();

                DB::commit();

                return $this->successResponse(null, 'Variation deleted successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Variation not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete variation', [
                'store_id' => $this->getStoreId(),
                'variation_id' => $id,
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete variation: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Get all variations for a specific product.
     */
    public function getByProduct($productId)
    {
        try {
            // Verify product belongs to store
            $product = Product::byStore($this->getStoreId())->find($productId);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            $variations = ProductVariation::byStore($this->getStoreId())
                                         ->where('product_id', $productId)
                                         ->with('custom3dModel')
                                         ->orderBy('is_active', 'desc')
                                         ->orderBy('created_at', 'desc')
                                         ->get();

            // Add computed fields
            $variationsData = $variations->map(function($variation) {
                $data = $variation->toArray();
                $data['final_price'] = $variation->final_price;
                $data['display_name'] = $variation->display_name;
                return $data;
            });

            return $this->successResponse([
                'product_id' => (int) $productId,
                'product_name' => $product->product_name,
                'base_price' => $product->base_price,
                'total_variations' => $variations->count(),
                'variations' => $variationsData
            ], 'Product variations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve product variations', [
                'store_id' => $this->getStoreId(),
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve variations: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }
}