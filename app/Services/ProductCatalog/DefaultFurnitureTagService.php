<?php

namespace App\Services\ProductCatalog;

use App\Models\ProductCatalog\Tag;

class DefaultFurnitureTagService
{
    private const TAGS = [
        'New' => 'Feature',
        'Featured' => 'Feature',
        'Best Seller' => 'Feature',
        'Sale' => 'Promotion',
        'Modern' => 'Style',
        'Classic' => 'Style',
        'Minimalist' => 'Style',
    ];

    public function populateForStore(int $storeId): void
    {
        foreach (self::TAGS as $name => $type) {
            // Preserve renamed or archived tags when onboarding is retried.
            if (Tag::withTrashed()->where('store_id', $storeId)->where('tag_name', $name)->exists()) {
                continue;
            }

            Tag::create([
                'store_id' => $storeId,
                'tag_name' => $name,
                'tag_type' => $type,
                'is_active' => true,
            ]);
        }
    }
}
