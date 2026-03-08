<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'variation_id' => 'nullable|integer|exists:product_variations,id',
            'quantity_on_hand' => 'required|integer|min:0',
            'quantity_reserved' => 'nullable|integer|min:0',
            'quantity_damaged' => 'nullable|integer|min:0',
            'warehouse_section' => 'nullable|string|max:50',
            'aisle' => 'nullable|string|max:20',
            'rack' => 'nullable|string|max:20',
            'shelf' => 'nullable|string|max:20',
            'bin_code' => 'nullable|string|max:50|unique:branch_inventory',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'safety_stock' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'average_cost' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Selected product does not exist',
            'quantity_on_hand.required' => 'Quantity on hand is required',
            'quantity_on_hand.min' => 'Quantity cannot be negative',
            'bin_code.unique' => 'Bin code must be unique',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Store and branch from authenticated user
        $this->merge([
            'store_id' => auth()->user()->store_id,
            'branch_id' => auth()->user()->branch_id,
        ]);
    }
}
