<?php

namespace App\Http\Resources\Ims\Catalog;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return new CategoryResource($this->parent);
            }),
            'children' => $this->whenLoaded('children', function () {
                return CategoryResource::collection($this->children);
            }),
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'is_active' => $this->is_active,
            'product_count' => $this->when($this->product_count !== null, $this->product_count),
            'has_products' => $this->when($this->relationLoaded('products'), $this->products->count() > 0),
            'full_path' => $this->full_path,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'links' => [
                'self' => route('categories.show', $this->id),
                'products' => route('products.index', ['category_id' => $this->id]),
                'children' => route('categories.index', ['parent_id' => $this->id]),
            ]
        ];
    }
}