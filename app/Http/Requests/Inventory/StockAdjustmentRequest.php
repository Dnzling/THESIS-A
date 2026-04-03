<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
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
            'adjustment_type' => 'required|in:physical_count,damage,loss,correction',
            'reference_number' => 'nullable|string|max:50|unique:stock_adjustments',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variation_id' => 'nullable|integer|exists:product_variations,id',
            'items.*.expected_quantity' => 'required|integer|min:0',
            'items.*.actual_quantity' => 'required|integer|min:0',
            'items.*.variance_reason' => 'nullable|string|max:255',
            'items.*.photo_path' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
            'counted_by' => 'nullable|integer|exists:users,id',
            'counted_date' => 'required|date',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'adjustment_type.required' => 'Adjustment type is required',
            'adjustment_type.in' => 'Invalid adjustment type',
            'items.required' => 'At least one item must be included',
            'items.*.product_id.required' => 'Product ID is required',
            'items.*.expected_quantity.required' => 'Expected quantity is required',
            'items.*.actual_quantity.required' => 'Actual quantity is required',
            'counted_date.required' => 'Count date is required',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'store_id' => auth()->user()->store_id,
            'branch_id' => auth()->user()->branch_id,
            'created_by' => auth()->id(),
        ]);
    }
}
