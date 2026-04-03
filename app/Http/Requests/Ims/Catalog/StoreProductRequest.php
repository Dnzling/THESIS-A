<?php

namespace App\Http\Requests\Ims\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'storeAdmin', 'inventoryManager']);
    }

    public function rules(): array
    {
        return [
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'material_id' => 'nullable|exists:materials,id',
            'color_id' => 'nullable|exists:colors,id',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'depth_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|gte:cost_price',
            'min_stock_level' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'has_3d_model' => 'boolean',
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|max:5120', // 5MB per image
            'model_3d' => 'nullable|file|mimes:glb,gltf,obj,fbx|max:10240', // 10MB max
        ];
    }
    
    public function messages(): array
    {
        return [
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'material_id.exists' => 'Selected material does not exist.',
            'color_id.exists' => 'Selected color does not exist.',
            'selling_price.gte' => 'Selling price must be greater than or equal to cost price.',
            'images.max' => 'Maximum 10 images allowed.',
            'images.*.max' => 'Each image must not exceed 5MB.',
            'model_3d.max' => '3D model must not exceed 10MB.',
        ];
    }
}