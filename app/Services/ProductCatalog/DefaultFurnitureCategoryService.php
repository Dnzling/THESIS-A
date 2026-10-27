<?php

namespace App\Services\ProductCatalog;

use App\Models\ProductCatalog\Category;
use Illuminate\Support\Str;

class DefaultFurnitureCategoryService
{
    public function populateForStore(int $storeId): int
    {
        $created = 0;
        $categoryOrder = 1;

        foreach ((array) config('default_furniture_categories.catalog', []) as $category => $subcategories) {
            [$parentCategory, $wasCreated] = $this->findOrCreate(
                $storeId,
                (string) $category,
                null,
                1,
                $categoryOrder++,
                [(string) $category]
            );
            $created += (int) $wasCreated;

            foreach (array_values((array) $subcategories) as $index => $subcategory) {
                [, $wasCreated] = $this->findOrCreate(
                    $storeId,
                    (string) $subcategory,
                    (int) $parentCategory->id,
                    2,
                    $index + 1,
                    [(string) $category, (string) $subcategory]
                );
                $created += (int) $wasCreated;
            }
        }

        return $created;
    }

    private function findOrCreate(
        int $storeId,
        string $name,
        ?int $parentId,
        int $level,
        int $displayOrder,
        array $path
    ): array {
        $code = $this->categoryCode($path);
        $existing = Category::withTrashed()
            ->where('store_id', $storeId)
            ->where('category_code', $code)
            ->first();

        // A rerun must not overwrite a store's edits or restore an archived category.
        if ($existing) {
            return [$existing, false];
        }

        return [Category::query()->create([
            'store_id' => $storeId,
            'category_code' => $code,
            'category_name' => $name,
            'description' => 'Starter furniture category',
            'parent_category_id' => $parentId,
            'level' => $level,
            'is_ecommerce_quick_select' => $level === 1,
            'is_active' => true,
            'display_order' => $displayOrder,
        ]), true];
    }

    private function categoryCode(array $path): string
    {
        $fullPath = implode(' > ', $path);
        $slug = Str::upper(Str::slug(implode('-', $path)));

        return 'FURN-' . Str::limit($slug, 72, '') . '-' . strtoupper(substr(sha1($fullPath), 0, 8));
    }
}
