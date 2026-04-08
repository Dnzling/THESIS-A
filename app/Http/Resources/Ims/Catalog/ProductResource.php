<?php

namespace App\Http\Resources\Ims\Catalog;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            
            // Category info
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return $this->category ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ] : null;
            }),
            
            // Material info
            'material_id' => $this->material_id,
            'material' => $this->whenLoaded('material', function () {
                return $this->material ? [
                    'id' => $this->material->id,
                    'name' => $this->material->name,
                    'code' => $this->material->code,
                ] : null;
            }),
            
            // Color info
            'color_id' => $this->color_id,
            'color' => $this->whenLoaded('color', function () {
                return $this->color ? [
                    'id' => $this->color->id,
                    'name' => $this->color->name,
                    'hex_code' => $this->color->hex_code,
                ] : null;
            }),
            
            // Dimensions
            'width_cm' => $this->width_cm,
            'height_cm' => $this->height_cm,
            'depth_cm' => $this->depth_cm,
            'weight_kg' => $this->weight_kg,
            'dimensions' => $this->formatted_dimensions,
            
            // Pricing
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'formatted_cost_price' => $this->formatted_cost_price,
            'formatted_selling_price' => $this->formatted_selling_price,
            'profit_margin' => $this->profit_margin,
            'profit_amount' => $this->profit_amount,
            'formatted_profit_amount' => $this->formatted_profit_amount,
            
            // Stock management
            'min_stock_level' => $this->min_stock_level,
            'reorder_point' => $this->reorder_point,
            
            // Status
            'is_active' => $this->is_active,
            'has_3d_model' => $this->has_3d_model,
            'model_3d_url' => $this->model_3d_url,
            
            // Images
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->image_url,
                        'is_primary' => $image->is_primary,
                        'display_order' => $image->display_order,
                    ];
                });
            }),
            
            // Main image (convenience)
            'main_image' => $this->whenLoaded('images', function () {
                $mainImage = $this->images->where('is_primary', true)->first() 
                    ?? $this->images->sortBy('display_order')->first();
                
                return $mainImage ? [
                    'id' => $mainImage->id,
                    'url' => $mainImage->image_url,
                ] : null;
            }),
            
            // Stock info
            'total_quantity' => $this->total_quantity,
            'total_available_quantity' => $this->total_available_quantity,
            'stock_status' => $this->stock_status,
            'is_low_stock' => $this->is_low_stock,
            'is_out_of_stock' => $this->is_out_of_stock,
            
            // Sales info
            'total_sold_quantity' => $this->when($request->user()->hasRole('admin'), $this->total_sold_quantity),
            'total_sales_revenue' => $this->when($request->user()->hasRole('admin'), $this->total_sales_revenue),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Links
            'links' => [
                'self' => route('products.show', $this->id),
                'category' => $this->category_id ? route('categories.show', $this->category_id) : null,
                // 'images' => route('products.images', $this->id),
            ]
        ];
    }
    
    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse($request, $response)
    {
        $response->header('X-Product-ID', $this->id);
        $response->header('X-Product-SKU', $this->sku);
    }
}