<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Hr\Employee;

class ReorderSuggestionRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'reorder_rule_id' => [
                'required',
                'integer',
                'exists:reorder_rules,id',
            ],
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],
            'suggestion_type' => [
                'required',
                'string',
                Rule::in(['automatic', 'manual', 'emergency']),
            ],
            'current_stock' => [
                'required',
                'numeric',
                'min:0',
            ],
            'suggested_quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'estimated_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'priority' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'approved', 'rejected', 'implemented', 'cancelled']),
            ],
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
            'valid_until' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];

        // For updates, make some fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['reorder_rule_id'] = [
                'sometimes',
                'integer',
                'exists:reorder_rules,id',
            ];
            $rules['product_id'] = [
                'sometimes',
                'integer',
                'exists:products,id',
            ];
            $rules['branch_id'] = [
                'sometimes',
                'integer',
                'exists:branches,id',
            ];
            $rules['suggestion_type'] = [
                'sometimes',
                'string',
                Rule::in(['automatic', 'manual', 'emergency']),
            ];
            $rules['current_stock'] = [
                'sometimes',
                'numeric',
                'min:0',
            ];
            $rules['suggested_quantity'] = [
                'sometimes',
                'numeric',
                'min:0.01',
            ];
            $rules['priority'] = [
                'sometimes',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ];
            $rules['reason'] = [
                'sometimes',
                'string',
                'max:1000',
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reorder_rule_id.required' => 'Reorder rule is required.',
            'reorder_rule_id.exists' => 'Selected reorder rule does not exist.',
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'suggestion_type.required' => 'Suggestion type is required.',
            'suggestion_type.in' => 'Suggestion type must be automatic, manual, or emergency.',
            'current_stock.required' => 'Current stock level is required.',
            'current_stock.numeric' => 'Current stock must be a number.',
            'current_stock.min' => 'Current stock cannot be negative.',
            'suggested_quantity.required' => 'Suggested quantity is required.',
            'suggested_quantity.numeric' => 'Suggested quantity must be a number.',
            'suggested_quantity.min' => 'Suggested quantity must be greater than zero.',
            'estimated_cost.numeric' => 'Estimated cost must be a number.',
            'estimated_cost.min' => 'Estimated cost cannot be negative.',
            'priority.required' => 'Priority level is required.',
            'priority.in' => 'Priority must be low, medium, high, or critical.',
            'status.in' => 'Status must be pending, approved, rejected, implemented, or cancelled.',
            'reason.required' => 'Reason for suggestion is required.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'valid_until.after' => 'Valid until date must be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reorder_rule_id' => 'reorder rule',
            'product_id' => 'product',
            'branch_id' => 'branch',
            'suggestion_type' => 'suggestion type',
            'current_stock' => 'current stock',
            'suggested_quantity' => 'suggested quantity',
            'estimated_cost' => 'estimated cost',
            'valid_until' => 'valid until',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default branch from current user context when omitted
        if (!$this->filled('branch_id')) {
            $user = auth()->user();
            if ($user) {
                $branchId = (int) ($user->branch_id ?? 0);
                if ($branchId === 0) {
                    $branchId = (int) Employee::query()
                        ->where('user_id', $user->id)
                        ->value('branch_id');
                }

                if ($branchId > 0) {
                    $this->merge(['branch_id' => $branchId]);
                }
            }
        }

        // Ensure numeric fields are properly formatted
        if ($this->has('current_stock')) {
            $this->merge([
                'current_stock' => (float) $this->current_stock,
            ]);
        }

        if ($this->has('suggested_quantity')) {
            $this->merge([
                'suggested_quantity' => (float) $this->suggested_quantity,
            ]);
        }

        if ($this->has('estimated_cost') && $this->estimated_cost !== null) {
            $this->merge([
                'estimated_cost' => (float) $this->estimated_cost,
            ]);
        }
    }
}
