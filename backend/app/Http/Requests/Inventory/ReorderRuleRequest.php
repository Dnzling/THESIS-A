<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasPermission('inventory.manage_reorder_rules');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'reorder_point' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:1',
            'maximum_stock' => 'nullable|integer|min:0',
            'safety_stock' => 'nullable|integer|min:0',
            'auto_reorder_enabled' => 'boolean',
            'auto_reorder_days' => 'nullable|integer|min:1|max:365',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'reorder_point.required' => 'Reorder point is required',
            'reorder_quantity.required' => 'Reorder quantity is required',
            'reorder_quantity.min' => 'Reorder quantity must be at least 1',
            'auto_reorder_days.max' => 'Auto-reorder interval cannot exceed 365 days',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'store_id' => auth()->user()->store_id,
        ]);
    }
}
