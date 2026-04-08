<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockReturnRequest extends FormRequest
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
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'to_branch_id' => 'nullable|integer|exists:branches,id|different:from_branch_id',
            'return_type' => 'required|in:supplier_return,branch_return,damaged_return,expired_return,quality_return',
            'expected_return_date' => 'nullable|date|after:today',
            'return_reason' => 'nullable|in:damaged,expired,quality_issue,wrong_item,overstock,customer_return,supplier_policy,other',
            'reason_details' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variation_id' => 'nullable|integer|exists:product_variations,id',
            'items.*.branch_inventory_id' => 'required|integer|exists:branch_inventory,id',
            'items.*.quantity_returned' => 'required|integer|min:1',
            'items.*.condition' => 'nullable|in:new,good,fair,poor,damaged,expired',
            'items.*.return_reason' => 'nullable|string|max:500',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'from_branch_id.required' => 'From branch is required',
            'return_type.required' => 'Return type is required',
            'return_type.in' => 'Invalid return type selected',
            'expected_return_date.after' => 'Expected return date must be in the future',
            'return_reason.in' => 'Invalid return reason selected',
            'items.required' => 'At least one item must be selected for return',
            'items.*.product_id.required' => 'Product ID is required for each item',
            'items.*.branch_inventory_id.required' => 'Branch inventory ID is required for each item',
            'items.*.quantity_returned.required' => 'Quantity to return is required for each item',
            'items.*.quantity_returned.min' => 'Quantity to return must be at least 1',
            'items.*.condition.in' => 'Invalid item condition selected',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('return_type')) {
            $this->merge(['return_type' => 'supplier_return']);
        }

        if (!$this->has('condition')) {
            $this->merge(['condition' => 'good']);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $fromBranchId = $this->input('from_branch_id');
            $returnType = $this->input('return_type');

            // Validate that supplier_id is provided for supplier returns
            if ($returnType === 'supplier_return' && !$this->input('supplier_id')) {
                $validator->errors()->add('supplier_id', 'Supplier is required for supplier returns');
            }

            // Validate that to_branch_id is provided for branch returns
            if ($returnType === 'branch_return' && !$this->input('to_branch_id')) {
                $validator->errors()->add('to_branch_id', 'Destination branch is required for branch returns');
            }

            // Check inventory availability for each item
            foreach ($items as $index => $item) {
                $branchInventoryId = $item['branch_inventory_id'] ?? null;
                $quantityReturned = $item['quantity_returned'] ?? 0;

                if ($branchInventoryId && $quantityReturned > 0) {
                    // Here you could add additional validation to check if the quantity
                    // is available in the branch inventory, but for now we'll trust the frontend
                }
            }
        });
    }
}
