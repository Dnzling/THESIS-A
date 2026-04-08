<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Get the authenticated user's context (store & branch)
     */
    private function getUserContext(): array
    {
        return [
            'store_id' => auth()->user()->store_id,
            'branch_id' => auth()->user()->branch_id,
        ];
    }

    /**
     * Display categories
     * GET /api/inventory/categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $query = Category::where('store_id', $context['store_id'])
                ->withCount('products');

            // Filters - Using correct column names from your table
            if ($request->has('parent_id')) {
                // Your table uses 'parent_category_id', not 'parent_id'
                $query->where('parent_category_id', $request->parent_id);
            } else {
                // Root categories (no parent)
                $query->whereNull('parent_category_id');
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('category_name', 'like', "%{$search}%")
                        ->orWhere('category_code', 'like', "%{$search}%"); // Also search by code
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by level if provided
            if ($request->has('level')) {
                $query->where('level', $request->level);
            }

            // Sorting - Using 'display_order' instead of 'sort_order'
            $categories = $query->orderBy('display_order')
                ->orderBy('category_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Create new category
     * POST /api/inventory/categories
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $validated = $request->validate([
                'category_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:product_categories,id',
                'sort_order' => 'integer|min:0',
                'is_active' => 'boolean',
                'image_url' => 'nullable|url',
            ]);

            // Validate parent category belongs to same store
            if ($validated['parent_id']) {
                $parent = Category::where('store_id', $context['store_id'])
                    ->findOrFail($validated['parent_id']);
            }

            $category = Category::create([
                'store_id' => $context['store_id'],
                'category_name' => $validated['category_name'],
                'description' => $validated['description'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'image_url' => $validated['image_url'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $category->load('parent'),
                'message' => 'Category created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update category
     * PUT /api/inventory/categories/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $category = Category::where('store_id', $context['store_id'])->findOrFail($id);

            $validated = $request->validate([
                'category_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:product_categories,id',
                'sort_order' => 'integer|min:0',
                'is_active' => 'boolean',
                'image_url' => 'nullable|url',
            ]);

            // Prevent circular reference
            if ($validated['parent_id'] == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category cannot be its own parent',
                ], 422);
            }

            // Validate parent category belongs to same store
            if ($validated['parent_id']) {
                $parent = Category::where('store_id', $context['store_id'])
                    ->findOrFail($validated['parent_id']);
            }

            $category->update([
                'category_name' => $validated['category_name'],
                'description' => $validated['description'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $category->sort_order,
                'is_active' => $validated['is_active'] ?? $category->is_active,
                'image_url' => $validated['image_url'] ?? $category->image_url,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $category->load('parent'),
                'message' => 'Category updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete category
     * DELETE /api/inventory/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $category = Category::where('store_id', $context['store_id'])->findOrFail($id);

            // Check if category has subcategories
            $hasChildren = Category::where('parent_id', $id)->exists();
            if ($hasChildren) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with subcategories',
                ], 422);
            }

            // Check if category has products
            $hasProducts = $category->products()->exists();
            if ($hasProducts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with products',
                ], 422);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category products
     * GET /api/inventory/categories/{id}/products
     */
    public function getProducts(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getUserContext();

            $category = Category::where('store_id', $context['store_id'])->findOrFail($id);

            $query = $category->products()
                ->with(['variations', 'inventory' => function ($q) use ($context) {
                    $q->where('branch_id', $context['branch_id']);
                }]);

            // Include subcategories if requested
            if ($request->boolean('include_subcategories')) {
                $subcategoryIds = Category::where('parent_id', $id)
                    ->pluck('id')
                    ->push($id);

                $query = \App\Models\ProductCatalog\Product::whereIn('category_id', $subcategoryIds)
                    ->with(['variations', 'inventory' => function ($q) use ($context) {
                        $q->where('branch_id', $context['branch_id']);
                    }]);
            }

            $products = $query->where('is_active', true)
                ->orderBy('product_name')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
