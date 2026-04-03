<?php
// app/Http/Controllers/Api/ProductCatalog/CategoryController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Models\ProductCatalog\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CategoryController extends BaseController
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        try {
            $query = Category::byStore($this->getStoreId())
                            ->with('parent')
                            ->withCount('products');

            // Filter by parent
            if ($request->has('parent_id')) {
                if ($request->parent_id === 'null') {
                    $query->whereNull('parent_category_id');
                } else {
                    $query->where('parent_category_id', $request->parent_id);
                }
            }

            // Filter active only
            if ($request->boolean('active_only')) {
                $query->active();
            }

            // Search
            if ($request->has('search')) {
                $query->where('category_name', 'like', '%' . $request->search . '%');
            }

            $categories = $query->orderBy('display_order')
                               ->orderBy('category_name')
                               ->paginate($request->get('per_page', 15));

            return $this->successResponse($categories, 'Categories retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve categories', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve categories',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Get category tree (hierarchical).
     */
    public function tree()
    {
        try {
            $categories = Category::byStore($this->getStoreId())
                                  ->with(['children' => function($query) {
                                      $query->orderBy('display_order')
                                            ->orderBy('category_name');
                                  }])
                                  ->whereNull('parent_category_id')
                                  ->orderBy('display_order')
                                  ->orderBy('category_name')
                                  ->get();

            return $this->successResponse($categories, 'Category tree retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve category tree', [
                'store_id' => $this->getStoreId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve category tree',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'category_code' => 'required|string|max:20',
                'category_name' => 'required|string|max:100',
                'description' => 'nullable|string',
                'parent_category_id' => 'nullable|exists:categories,id',
                'icon_path' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'display_order' => 'integer'
            ]);

            DB::beginTransaction();

            try {
                // Check if code is unique for this store
                $exists = Category::byStore($this->getStoreId())
                                 ->where('category_code', $validated['category_code'])
                                 ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->errorResponse('Category code already exists for this store', 422);
                }

                $data = $validated;
                $data['store_id'] = $this->getStoreId();
                
                // Auto-calculate level
                if ($request->parent_category_id) {
                    $parent = Category::find($request->parent_category_id);
                    $data['level'] = $parent ? $parent->level + 1 : 1;
                } else {
                    $data['level'] = 1;
                }

                $category = Category::create($data);

                DB::commit();

                return $this->successResponse(
                    $category->load('parent'),
                    'Category created successfully',
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
            Log::error('Failed to create category', [
                'store_id' => $this->getStoreId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to create category: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {
        try {
            $category = Category::byStore($this->getStoreId())
                               ->with(['parent', 'children' => function($query) {
                                   $query->orderBy('display_order')
                                         ->orderBy('category_name');
                               }])
                               ->withCount('products')
                               ->findOrFail($id);

            return $this->successResponse($category, 'Category retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Category not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve category', [
                'store_id' => $this->getStoreId(),
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to retrieve category',
                500,
                [],
                $e
            );
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        try {
            $category = Category::byStore($this->getStoreId())->findOrFail($id);

            $validated = $this->validateRequest($request, [
                'category_code' => 'sometimes|string|max:20|unique:categories,category_code,' . $id . ',id,store_id,' . $this->getStoreId(),
                'category_name' => 'sometimes|string|max:100',
                'description' => 'nullable|string',
                'parent_category_id' => 'nullable|exists:categories,id',
                'icon_path' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'display_order' => 'integer'
            ]);

            DB::beginTransaction();

            try {
                $data = $validated;
                
                // Recalculate level if parent changed
                if ($request->has('parent_category_id') && 
                    $request->parent_category_id != $category->parent_category_id) {
                    
                    if ($request->parent_category_id) {
                        $parent = Category::find($request->parent_category_id);
                        $data['level'] = $parent ? $parent->level + 1 : 1;
                    } else {
                        $data['level'] = 1;
                    }
                }

                $category->update($data);

                DB::commit();

                return $this->successResponse(
                    $category->fresh('parent'),
                    'Category updated successfully'
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
            return $this->errorResponse('Category not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to update category', [
                'store_id' => $this->getStoreId(),
                'category_id' => $id,
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to update category: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id)
    {
        try {
            $category = Category::byStore($this->getStoreId())->findOrFail($id);

            // Check if has products
            if ($category->products()->count() > 0) {
                return $this->errorResponse(
                    'Cannot delete category with associated products',
                    422
                );
            }

            // Check if has children
            if ($category->children()->count() > 0) {
                return $this->errorResponse(
                    'Cannot delete category with subcategories',
                    422
                );
            }

            DB::beginTransaction();

            try {
                $category->delete();
                DB::commit();

                return $this->successResponse(null, 'Category deleted successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Category not found', 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete category', [
                'store_id' => $this->getStoreId(),
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete category: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Bulk update display order.
     */
    public function reorder(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'categories' => 'required|array',
                'categories.*.id' => 'required|exists:categories,id',
                'categories.*.display_order' => 'required|integer'
            ]);

            DB::beginTransaction();

            try {
                foreach ($validated['categories'] as $item) {
                    $category = Category::byStore($this->getStoreId())
                                       ->find($item['id']);
                    
                    if ($category) {
                        $category->update(['display_order' => $item['display_order']]);
                    }
                }

                DB::commit();

                return $this->successResponse(null, 'Categories reordered successfully');

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
            Log::error('Failed to reorder categories', [
                'store_id' => $this->getStoreId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to reorder categories: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    /**
     * Bulk delete categories.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'category_ids' => 'required|array',
                'category_ids.*' => 'required|integer'
            ]);

            DB::beginTransaction();

            try {
                $categories = Category::byStore($this->getStoreId())
                                     ->whereIn('id', $validated['category_ids'])
                                     ->get();

                if ($categories->count() !== count($validated['category_ids'])) {
                    DB::rollBack();
                    return $this->errorResponse('One or more categories not found or do not belong to this store', 422);
                }

                // Check if any has products
                foreach ($categories as $category) {
                    if ($category->products()->count() > 0) {
                        DB::rollBack();
                        return $this->errorResponse("Category '{$category->category_name}' has products and cannot be deleted", 422);
                    }
                }

                $deleted = 0;
                foreach ($categories as $category) {
                    $category->delete();
                    $deleted++;
                }

                DB::commit();

                return $this->successResponse(
                    ['deleted_count' => $deleted],
                    'Categories deleted successfully'
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
            Log::error('Failed to bulk delete categories', [
                'store_id' => $this->getStoreId(),
                'user_id' => $this->getUserId(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse(
                'Failed to delete categories: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }
}