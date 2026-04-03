<?php

namespace App\Http\Requests\Ims\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Ims\Catalog\Product;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'storeAdmin', 'inventoryManager']);
    }

    public function rules(): array
    {
        $productId = $this->route('product')->id;
        
        return [
            'sku' => "sometimes|string|max:100|unique:products,sku,{$productId}",
            'name' => 'sometimes|string|max:200',
            'description' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|exists:categories,id',
            'material_id' => 'sometimes|nullable|exists:materials,id',
            'color_id' => 'sometimes|nullable|exists:colors,id',
            'width_cm' => 'sometimes|nullable|numeric|min:0',
            'height_cm' => 'sometimes|nullable|numeric|min:0',
            'depth_cm' => 'sometimes|nullable|numeric|min:0',
            'weight_kg' => 'sometimes|nullable|numeric|min:0',
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'min_stock_level' => 'sometimes|nullable|integer|min:0',
            'reorder_point' => 'sometimes|nullable|integer|min:0',
            'has_3d_model' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'images' => 'sometimes|array|max:10',
            'images.*' => 'image|max:5120',
            'model_3d' => 'sometimes|file|mimes:glb,gltf,obj,fbx|max:10240',
            'remove_images' => 'sometimes|array',
            'remove_images.*' => 'exists:product_images,id',
        ];
    }
    
    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU already exists.',
            'selling_price.gte' => 'Selling price must be greater than or equal to cost price.',
        ];
    }
}