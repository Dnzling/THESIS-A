<?php
// app/Http/Controllers/Api/ProductCatalog/TagController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\ProductCatalog\Tag;
use App\Models\ProductCatalog\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TagController extends BaseController
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request)
    {
        try {
            $query = Tag::byStore($this->getStoreId());

            // Filter by type
            if ($request->has('type')) {
                $query->ofType($request->type);
            }

            // Filter active only
            if ($request->boolean('active_only')) {
                $query->active();
            }

            // Search
            if ($request->has('search')) {
                $query->where('tag_name', 'like', '%' . $request->search . '%');
            }

            $tags = $query->withCount('products')
                         ->orderBy('tag_name')
                         ->paginate($request->get('per_page', 15));

            return $this->successResponse($tags, 'Tags retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve tags', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve tags',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'tag_name' => 'required|string|max:50',
                'tag_type' => 'required|in:Style,Room,Promotion,Feature',
                'is_active' => 'boolean'
            ]);

            DB::beginTransaction();

            try {
                // Check if name is unique for this store
                $exists = Tag::byStore($this->getStoreId())
                            ->where('tag_name', $validated['tag_name'])
                            ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->errorResponse('Tag name already exists for this store', 422);
                }

                $data = $validated;
                $data['store_id'] = $this->getStoreId();
                
                $tag = Tag::create($data);

                DB::commit();

                return $this->successResponse($tag, 'Tag created successfully', 201);

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
            Log::error('Failed to create tag', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to create tag: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Display the specified tag.
     */
    public function show($id)
    {
        try {
            $tag = Tag::byStore($this->getStoreId())
                     ->with(['products' => function($query) {
                         $query->select('products.id', 'products.product_name', 'products.sku')
                               ->limit(10);
                     }])
                     ->withCount('products')
                     ->findOrFail($id);

            return $this->successResponse($tag, 'Tag retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Tag not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tag', [
                'store_id' => $this->getStoreId(),
                'tag_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve tag',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, $id)
    {
        try {
            $tag = Tag::byStore($this->getStoreId())->findOrFail($id);

            $validated = $this->validateRequest($request, [
                'tag_name' => 'sometimes|string|max:50|unique:tags,tag_name,' . $id . ',id,store_id,' . $this->getStoreId(),
                'tag_type' => 'sometimes|in:Style,Room,Promotion,Feature',
                'is_active' => 'boolean'
            ]);

            DB::beginTransaction();

            try {
                $tag->update($validated);
                DB::commit();

                return $this->successResponse($tag, 'Tag updated successfully');

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
            return $this->errorResponse('Tag not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to update tag', [
                'store_id' => $this->getStoreId(),
                'tag_id' => $id,
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to update tag: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove the specified tag.
     */
    public function destroy($id)
    {
        try {
            $tag = Tag::byStore($this->getStoreId())->findOrFail($id);

            DB::beginTransaction();

            try {
                // Detach from all products first
                $tag->products()->detach();
                $tag->delete();

                DB::commit();

                return $this->successResponse(null, 'Tag deleted successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Tag not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete tag', [
                'store_id' => $this->getStoreId(),
                'tag_id' => $id,
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete tag: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Assign tags to product.
     */
    public function assignToProduct(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_id' => 'required|exists:products,id',
                'tag_ids' => 'required|array',
                'tag_ids.*' => 'required|exists:tags,id'
            ]);

            // Verify product belongs to store
            $product = Product::byStore($this->getStoreId())
                      ->find($validated['product_id']);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            // Verify all tags belong to store
            $tags = Tag::byStore($this->getStoreId())
                     ->whereIn('id', $validated['tag_ids'])
                     ->get();

            if ($tags->count() !== count($validated['tag_ids'])) {
                return $this->errorResponse('One or more tags not found or do not belong to this store', 422);
            }

            DB::beginTransaction();

            try {
                // Sync tags (attach only new ones)
                $product->tags()->syncWithoutDetaching($validated['tag_ids']);

                DB::commit();

                return $this->successResponse([
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'tags' => $product->tags()->get()
                ], 'Tags assigned to product successfully');

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
            Log::error('Failed to assign tags to product', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to assign tags: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove tag from product.
     */
    public function removeFromProduct(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_id' => 'required|exists:products,id',
                'tag_id' => 'required|exists:tags,id'
            ]);

            // Verify product belongs to store
            $product = Product::byStore($this->getStoreId())
                      ->find($validated['product_id']);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            // Verify tag belongs to store
            $tag = Tag::byStore($this->getStoreId())
                   ->find($validated['tag_id']);
            
            if (!$tag) {
                return $this->errorResponse('Tag not found or does not belong to this store', 404);
            }

            DB::beginTransaction();

            try {
                $product->tags()->detach($validated['tag_id']);
                DB::commit();

                return $this->successResponse(null, 'Tag removed from product successfully');

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
            Log::error('Failed to remove tag from product', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to remove tag: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Get tags for a specific product.
     */
    public function getProductTags($productId)
    {
        try {
            // Verify product belongs to store
            $product = Product::byStore($this->getStoreId())
                      ->find($productId);
            
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            $tags = $product->tags()->get();

            return $this->successResponse([
                'product_id' => (int) $productId,
                'product_name' => $product->product_name,
                'tags' => $tags
            ], 'Product tags retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve product tags', [
                'store_id' => $this->getStoreId(),
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve product tags: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Get popular tags with usage count.
     */
    public function popular()
    {
        try {
            $tags = Tag::byStore($this->getStoreId())
                      ->active()
                      ->withCount('products')
                      ->having('products_count', '>', 0)
                      ->orderBy('products_count', 'desc')
                      ->limit(20)
                      ->get();

            return $this->successResponse($tags, 'Popular tags retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve popular tags', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve popular tags: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Bulk delete tags.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'tag_ids' => 'required|array',
                'tag_ids.*' => 'required|integer'
            ]);

            DB::beginTransaction();

            try {
                $tags = Tag::byStore($this->getStoreId())
                          ->whereIn('id', $validated['tag_ids'])
                          ->get();

                if ($tags->count() !== count($validated['tag_ids'])) {
                    DB::rollBack();
                    return $this->errorResponse('One or more tags not found or do not belong to this store', 422);
                }

                $deleted = 0;
                foreach ($tags as $tag) {
                    $tag->products()->detach();
                    $tag->delete();
                    $deleted++;
                }

                DB::commit();

                return $this->successResponse(
                    ['deleted_count' => $deleted],
                    'Tags deleted successfully'
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
            Log::error('Failed to bulk delete tags', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete tags: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }
}