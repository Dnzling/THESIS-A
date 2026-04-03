<?php

namespace App\Http\Requests\Ims\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Ims\Catalog\Category;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'storeAdmin']);
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id;
        
        return [
            'name' => "sometimes|string|max:100|unique:categories,name,{$categoryId}",
            'slug' => "sometimes|string|max:100|unique:categories,slug,{$categoryId}",
            'parent_id' => 'sometimes|nullable|exists:categories,id',
            'description' => 'sometimes|nullable|string|max:500',
            'image_path' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.unique' => 'Category name already exists.',
            'parent_id.exists' => 'Selected parent category does not exist.',
        ];
    }
}