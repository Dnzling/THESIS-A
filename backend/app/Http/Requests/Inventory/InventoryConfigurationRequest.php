<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'store_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'model_type' => 'required|in:single_store,centralized,distributed,multi_store',
            'main_branch_id' => 'nullable|integer|exists:branches,id',
            'warehouse_branch_ids' => 'nullable|array',
            'warehouse_branch_ids.*' => 'integer|exists:branches,id',
            'enable_transfer_approvals' => 'boolean',
            'enable_finance_approval' => 'boolean',
            'enable_auto_alerts' => 'boolean',
            'enable_cost_tracking' => 'boolean',
            'enable_physical_counts' => 'boolean',
            'default_reorder_point' => 'nullable|integer|min:0',
            'default_reorder_quantity' => 'nullable|integer|min:1',
            'default_safety_stock' => 'nullable|integer|min:0',
            'default_maximum_stock' => 'nullable|integer|min:0',
            'require_finance_approval_above' => 'nullable|numeric|min:0',
            'allow_auto_transfer' => 'boolean',
            'auto_transfer_threshold' => 'nullable|integer|min:0',
            'transfer_cost_model' => 'nullable|in:fixed,distance_based,weighted,none',
            'fixed_transfer_cost' => 'nullable|numeric|min:0',
            'cost_per_km' => 'nullable|numeric|min:0',
            'reporting_frequency' => 'nullable|in:daily,weekly,monthly',
            'include_sub_branches' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'model_type.required' => 'Inventory model type is required',
            'default_reorder_quantity.min' => 'Reorder quantity must be at least 1',
            'require_finance_approval_above.numeric' => 'Finance approval threshold must be numeric',
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
