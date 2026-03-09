<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockCountRequest extends FormRequest
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
            'branch_id' => 'required|integer|exists:branches,id',
            'count_type' => 'required|in:full_inventory,partial_count,cycle_count,spot_check',
            'scheduled_date' => 'required|date|after:today',
            'assigned_to' => 'required|integer|exists:employees,id',
            'supervised_by' => 'nullable|integer|exists:employees,id|different:assigned_to',
            'warehouse_section' => 'nullable|string|max:100',
            'aisle' => 'nullable|string|max:100',
            'rack' => 'nullable|string|max:100',
            'shelf' => 'nullable|string|max:100',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'instructions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch is required',
            'count_type.required' => 'Count type is required',
            'count_type.in' => 'Invalid count type selected',
            'scheduled_date.required' => 'Scheduled date is required',
            'scheduled_date.after' => 'Scheduled date must be in the future',
            'assigned_to.required' => 'Employee to assign count to is required',
            'supervised_by.different' => 'Supervisor cannot be the same as the assigned employee',
            'category_ids.*.exists' => 'Selected category does not exist',
            'product_ids.*.exists' => 'Selected product does not exist',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('count_type')) {
            $this->merge(['count_type' => 'full_inventory']);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryIds = $this->input('category_ids', []);
            $productIds = $this->input('product_ids', []);

            // Validate that at least one filter is provided for partial counts
            $countType = $this->input('count_type');
            if ($countType === 'partial_count') {
                if (empty($categoryIds) && empty($productIds) &&
                    !$this->input('warehouse_section') && !$this->input('aisle') &&
                    !$this->input('rack') && !$this->input('shelf')) {
                    $validator->errors()->add('filters', 'At least one filter (categories, products, or location) must be specified for partial counts');
                }
            }

            // For spot checks, ensure products are specified
            if ($countType === 'spot_check' && empty($productIds)) {
                $validator->errors()->add('product_ids', 'Products must be specified for spot checks');
            }
        });
    }
}
