<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'from_branch_id' => 'required|integer|exists:branches,id',
            'to_branch_id' => 'required|integer|exists:branches,id|different:from_branch_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variation_id' => 'nullable|integer|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'from_branch_id.required' => 'From branch is required',
            'from_branch_id.different' => 'Cannot transfer to the same branch',
            'to_branch_id.required' => 'To branch is required',
            'items.required' => 'At least one item must be selected',
            'items.*.product_id.required' => 'Product ID is required for each item',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
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
