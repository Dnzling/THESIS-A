<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EcommerceCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $categories = Category::query()
            ->with('store:id,name')
            ->withCount('products')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($search) {
                    $nested->where('category_name', 'like', "%{$search}%")
                        ->orWhereHas('store', fn ($store) => $store->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('products_count')
            ->orderBy('category_name')
            ->get()
            ->map(fn (Category $category) => $this->categoryData($category));

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'is_ecommerce_quick_select' => ['required', 'boolean'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('icon')) {
            $newPath = $request->file('icon')->store('ecommerce/categories', 'public');
            $this->deleteManagedIcon($category->ecommerce_icon_path);
            $validated['ecommerce_icon_path'] = $newPath;
        }

        unset($validated['icon']);
        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ecommerce category updated.',
            'data' => $this->categoryData($category->fresh('store:id,name')),
        ]);
    }

    private function categoryData(Category $category): array
    {
        return [
            'id' => $category->id,
            'category_name' => $category->category_name,
            'category_code' => $category->category_code,
            'store_id' => $category->store_id,
            'store_name' => $category->store?->name ?? 'Unknown store',
            'products_count' => (int) ($category->products_count ?? $category->products()->count()),
            'is_active' => (bool) $category->is_active,
            'is_ecommerce_quick_select' => (bool) $category->is_ecommerce_quick_select,
            'ecommerce_icon_url' => $category->ecommerce_icon_path
                ? Storage::disk('public')->url($category->ecommerce_icon_path)
                : null,
        ];
    }

    private function deleteManagedIcon(?string $path): void
    {
        if ($path && str_starts_with($path, 'ecommerce/categories/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403, 'Super Admin access only.');
    }
}
