<?php

namespace App\Services\ProductCatalog;

use App\Models\ProductCatalog\Category;
use Illuminate\Support\Str;

class DefaultFurnitureCategoryService
{
    public function populateForStore(int $storeId): int
    {
        $created = 0;
        $rootOrder = 1;

        foreach ((array) config('default_furniture_categories.catalog', []) as $department => $groups) {
            [$departmentCategory, $wasCreated] = $this->findOrCreate(
                $storeId,
                (string) $department,
                null,
                1,
                $rootOrder++,
                [(string) $department]
            );
            $created += (int) $wasCreated;

            $groupOrder = 1;
            foreach ((array) $groups as $group => $categories) {
                [$groupCategory, $wasCreated] = $this->findOrCreate(
                    $storeId,
                    (string) $group,
                    (int) $departmentCategory->id,
                    2,
                    $groupOrder++,
                    [(string) $department, (string) $group]
                );
                $created += (int) $wasCreated;

                foreach (array_values((array) $categories) as $index => $category) {
                    [, $wasCreated] = $this->findOrCreate(
                        $storeId,
                        (string) $category,
                        (int) $groupCategory->id,
                        3,
                        $index + 1,
                        [(string) $department, (string) $group, (string) $category]
                    );
                    $created += (int) $wasCreated;
                }
            }
        }

        [$roomRoot, $wasCreated] = $this->findOrCreate(
            $storeId,
            'Shop by Room',
            null,
            1,
            $rootOrder,
            ['Shop by Room']
        );
        $created += (int) $wasCreated;

        foreach (array_values((array) config('default_furniture_categories.rooms', [])) as $index => $room) {
            [, $wasCreated] = $this->findOrCreate(
                $storeId,
                (string) $room,
                (int) $roomRoot->id,
                2,
                $index + 1,
                ['Shop by Room', (string) $room]
            );
            $created += (int) $wasCreated;
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
            'is_ecommerce_quick_select' => $level <= 2,
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
