<?php
// app/Http/Controllers/Api/ProductCatalog/AttributeController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\ProductCatalog\ProductAttribute;
use App\Models\ProductCatalog\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AttributeController extends BaseController
{
    /**
     * Display a listing of attributes.
     */
    public function index(Request $request)
    {
        try {
            $query = ProductAttribute::byStore($this->getStoreId());

            // Filter by filterable
            if ($request->boolean('filterable_only')) {
                $query->filterable();
            }

            // Search
            if ($request->has('search')) {
                $query->where('attribute_name', 'like', '%' . $request->search . '%');
            }

            $attributes = $query->orderBy('display_order')
                               ->orderBy('attribute_name')
                               ->paginate($request->get('per_page', 15));

            return $this->successResponse($attributes, 'Attributes retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attributes', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve attributes',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Store a newly created attribute.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'attribute_name' => 'required|string|max:50',
                'attribute_type' => 'required|in:Text,Number,Select,Color,Multi-select',
                'is_filterable' => 'boolean',
                'display_order' => 'integer'
            ]);

            DB::beginTransaction();

            try {
                // Check if name is unique for this store
                $exists = ProductAttribute::byStore($this->getStoreId())
                                         ->where('attribute_name', $validated['attribute_name'])
                                         ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->errorResponse('Attribute name already exists for this store', 422);
                }

                $data = $validated;
                $data['store_id'] = $this->getStoreId();
                
                $attribute = ProductAttribute::create($data);

                DB::commit();

                return $this->successResponse($attribute, 'Attribute created successfully', 201);

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
            Log::error('Failed to create attribute', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to create attribute: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Display the specified attribute with its values.
     */
    public function show($id)
    {
        try {
            $attribute = ProductAttribute::byStore($this->getStoreId())
                                        ->with(['values' => function($query) {
                                            $query->with('product:id,product_name,sku')
                                                  ->orderBy('attribute_value');
                                        }])
                                        ->findOrFail($id);

            // Group values by product
            $values = $attribute->values->groupBy('product_id')->map(function($items) {
                return [
                    'product' => $items->first()->product,
                    'values' => $items
                ];
            });

            $response = $attribute->toArray();
            $response['values_by_product'] = $values;

            return $this->successResponse($response, 'Attribute retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Attribute not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribute', [
                'store_id' => $this->getStoreId(),
                'attribute_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve attribute',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Update the specified attribute.
     */
    public function update(Request $request, $id)
    {
        try {
            $attribute = ProductAttribute::byStore($this->getStoreId())->findOrFail($id);

            $validated = $this->validateRequest($request, [
                'attribute_name' => 'sometimes|string|max:50|unique:product_attributes,attribute_name,' . $id . ',id,store_id,' . $this->getStoreId(),
                'attribute_type' => 'sometimes|in:Text,Number,Select,Color,Multi-select',
                'is_filterable' => 'boolean',
                'display_order' => 'integer'
            ]);

            DB::beginTransaction();

            try {
                $attribute->update($validated);
                DB::commit();

                return $this->successResponse($attribute, 'Attribute updated successfully');

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
            return $this->errorResponse('Attribute not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to update attribute', [
                'store_id' => $this->getStoreId(),
                'attribute_id' => $id,
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to update attribute: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove the specified attribute.
     */
    public function destroy($id)
    {
        try {
            $attribute = ProductAttribute::byStore($this->getStoreId())->findOrFail($id);

            // Check if has values
            if ($attribute->values()->count() > 0) {
                return $this->errorResponse(
                    'Cannot delete attribute with associated values',
                    422
                );
            }

            DB::beginTransaction();

            try {
                $attribute->delete();
                DB::commit();

                return $this->successResponse(null, 'Attribute deleted successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Attribute not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete attribute', [
                'store_id' => $this->getStoreId(),
                'attribute_id' => $id,
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete attribute: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Assign value to product.
     */
    public function assignValue(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_id' => 'required|exists:products,id',
                'attribute_id' => 'required|exists:product_attributes,id',
                'attribute_value' => 'required|string|max:255',
                'color_hex_code' => 'nullable|string|size:7|starts_with:#',
                'texture_map_url' => 'nullable|url'
            ]);

            // Verify product belongs to store
            $product = \App\Models\ProductCatalog\Product::byStore($this->getStoreId())
                      ->find($validated['product_id']);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            // Verify attribute belongs to store
            $attribute = ProductAttribute::byStore($this->getStoreId())
                        ->find($validated['attribute_id']);
            
            if (!$attribute) {
                return $this->errorResponse('Attribute not found or does not belong to this store', 404);
            }

            DB::beginTransaction();

            try {
                // Check if value already exists
                $exists = ProductAttributeValue::byStore($this->getStoreId())
                          ->where('product_id', $validated['product_id'])
                          ->where('attribute_id', $validated['attribute_id'])
                          ->first();

                if ($exists) {
                    // Update existing
                    $exists->update([
                        'attribute_value' => $validated['attribute_value'],
                        'color_hex_code' => $validated['color_hex_code'] ?? null,
                        'texture_map_url' => $validated['texture_map_url'] ?? null
                    ]);
                    $value = $exists;
                } else {
                    // Create new
                    $data = $validated;
                    $data['store_id'] = $this->getStoreId();
                    $value = ProductAttributeValue::create($data);
                }

                DB::commit();

                return $this->successResponse(
                    $value->load(['product:id,product_name,sku', 'attribute']),
                    'Attribute value assigned successfully',
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
            Log::error('Failed to assign attribute value', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to assign attribute value: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove attribute value from product.
     */
    public function removeValue($id)
    {
        try {
            $value = ProductAttributeValue::byStore($this->getStoreId())->findOrFail($id);

            DB::beginTransaction();

            try {
                $value->delete();
                DB::commit();

                return $this->successResponse(null, 'Attribute value removed successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Attribute value not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to remove attribute value', [
                'store_id' => $this->getStoreId(),
                'value_id' => $id,
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to remove attribute value: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Get all values for a specific product.
     */
    public function getProductValues($productId)
    {
        try {
            // Verify product belongs to store
            $product = \App\Models\ProductCatalog\Product::byStore($this->getStoreId())
                      ->find($productId);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            $values = ProductAttributeValue::byStore($this->getStoreId())
                      ->where('product_id', $productId)
                      ->with('attribute')
                      ->get();

            return $this->successResponse([
                'product_id' => (int) $productId,
                'product_name' => $product->product_name,
                'attributes' => $values
            ], 'Product attribute values retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve product attribute values', [
                'store_id' => $this->getStoreId(),
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve attribute values: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }
}