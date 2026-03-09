<?php
// backend/app/Http\Requests/Inventory/ReorderRuleRequest.php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Inventory\ReorderRule;

class ReorderRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $ruleId = $this->route('reorder_rule')?->id;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id'),
            ],
            'rule_type' => [
                'required',
                'string',
                Rule::in(['manual', 'automatic', 'demand_based']),
            ],
            'trigger_type' => [
                'required',
                'string',
                Rule::in(['reorder_point', 'safety_stock', 'forecast', 'seasonal']),
            ],
            'reorder_point' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
                'required_if:trigger_type,reorder_point',
            ],
            'reorder_quantity' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'lead_time_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'safety_stock' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
                'required_if:trigger_type,safety_stock',
            ],
            'maximum_stock' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
                'gte:reorder_point',
            ],
            'economic_order_quantity' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'priority' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'auto_generate_po' => [
                'boolean',
            ],
            'supplier_preferences' => [
                'nullable',
                'array',
            ],
            'supplier_preferences.*.supplier_id' => [
                'integer',
                'exists:suppliers,id',
            ],
            'supplier_preferences.*.priority' => [
                'integer',
                'min:1',
                'max:10',
            ],
            'seasonal_adjustments' => [
                'nullable',
                'array',
            ],
            'seasonal_adjustments.*' => [
                'numeric',
                'min:0.1',
                'max:5.0',
            ],
            'is_active' => [
                'boolean',
            ],
            'next_review_date' => [
                'nullable',
                'date',
                'after:today',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'branch_id' => 'branch',
            'rule_type' => 'rule type',
            'trigger_type' => 'trigger type',
            'reorder_point' => 'reorder point',
            'reorder_quantity' => 'reorder quantity',
            'lead_time_days' => 'lead time (days)',
            'safety_stock' => 'safety stock',
            'maximum_stock' => 'maximum stock',
            'economic_order_quantity' => 'economic order quantity',
            'auto_generate_po' => 'auto generate PO',
            'supplier_preferences' => 'supplier preferences',
            'seasonal_adjustments' => 'seasonal adjustments',
            'is_active' => 'active status',
            'next_review_date' => 'next review date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reorder_point.required_if' => 'Reorder point is required when trigger type is reorder point.',
            'safety_stock.required_if' => 'Safety stock is required when trigger type is safety stock.',
            'maximum_stock.gte' => 'Maximum stock must be greater than or equal to reorder point.',
            'supplier_preferences.*.supplier_id.exists' => 'Selected supplier does not exist.',
            'supplier_preferences.*.priority.min' => 'Supplier priority must be at least 1.',
            'supplier_preferences.*.priority.max' => 'Supplier priority cannot exceed 10.',
            'seasonal_adjustments.*.min' => 'Seasonal adjustment must be at least 0.1.',
            'seasonal_adjustments.*.max' => 'Seasonal adjustment cannot exceed 5.0.',
            'next_review_date.after' => 'Next review date must be in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: ensure unique product-branch combination
            if ($this->product_id && $this->branch_id) {
                $query = ReorderRule::where('product_id', $this->product_id)
                    ->where('branch_id', $this->branch_id);

                if ($this->route('reorder_rule')) {
                    $query->where('id', '!=', $this->route('reorder_rule')->id);
                }

                if ($query->exists()) {
                    $validator->errors()->add('product_id', 'A reorder rule already exists for this product and branch combination.');
                }
            }

            // Custom validation: ensure reorder point is less than maximum stock
            if ($this->reorder_point && $this->maximum_stock && $this->reorder_point >= $this->maximum_stock) {
                $validator->errors()->add('maximum_stock', 'Maximum stock must be greater than reorder point.');
            }

            // Custom validation: ensure safety stock is less than reorder point
            if ($this->safety_stock && $this->reorder_point && $this->safety_stock >= $this->reorder_point) {
                $validator->errors()->add('safety_stock', 'Safety stock should be less than reorder point.');
            }

            // Custom validation: validate seasonal adjustments structure
            if ($this->seasonal_adjustments) {
                $months = range(1, 12);
                $providedMonths = array_keys($this->seasonal_adjustments);

                if (count($providedMonths) !== 12 || array_diff($months, $providedMonths) !== array_diff($providedMonths, $months)) {
                    $validator->errors()->add('seasonal_adjustments', 'Seasonal adjustments must include all 12 months (1-12).');
                }
            }
        });
    }
}
