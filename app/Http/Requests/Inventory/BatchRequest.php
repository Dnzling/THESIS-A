<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()?->can('inventory.master_data.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'batch_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('batches')->ignore($this->route('batch')),
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
            'warehouse_location_id' => [
                'nullable',
                'integer',
                'exists:warehouse_locations,id',
            ],
            'quantity_produced' => [
                'required',
                'integer',
                'min:1',
            ],
            'quantity_available' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'production_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'expiry_date' => [
                'nullable',
                'date',
                'after:production_date',
            ],
            'best_before_date' => [
                'nullable',
                'date',
                'after:production_date',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'expired', 'depleted', 'discontinued']),
            ],
            'quality_status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'approved', 'rejected', 'quarantined']),
            ],
            'supplier_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'supplier_batch_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'specifications' => [
                'nullable',
                'array',
            ],
            'quality_test_results' => [
                'nullable',
                'array',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];

        // For updates, make some fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['batch_number'] = [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('batches')->ignore($this->route('batch')),
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
            $rules['quantity_produced'] = [
                'sometimes',
                'integer',
                'min:1',
            ];
            $rules['production_date'] = [
                'sometimes',
                'date',
                'before_or_equal:today',
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
            'batch_number.required' => 'Batch number is required.',
            'batch_number.unique' => 'This batch number already exists.',
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'warehouse_location_id.exists' => 'Selected warehouse location does not exist.',
            'quantity_produced.required' => 'Quantity produced is required.',
            'quantity_produced.integer' => 'Quantity produced must be a whole number.',
            'quantity_produced.min' => 'Quantity produced must be at least 1.',
            'quantity_available.integer' => 'Quantity available must be a whole number.',
            'quantity_available.min' => 'Quantity available cannot be negative.',
            'unit_cost.numeric' => 'Unit cost must be a number.',
            'unit_cost.min' => 'Unit cost cannot be negative.',
            'unit_price.numeric' => 'Unit price must be a number.',
            'unit_price.min' => 'Unit price cannot be negative.',
            'production_date.required' => 'Production date is required.',
            'production_date.before_or_equal' => 'Production date cannot be in the future.',
            'expiry_date.after' => 'Expiry date must be after production date.',
            'best_before_date.after' => 'Best before date must be after production date.',
            'status.in' => 'Status must be active, expired, depleted, or discontinued.',
            'quality_status.in' => 'Quality status must be pending, approved, rejected, or quarantined.',
            'supplier_name.max' => 'Supplier name cannot exceed 255 characters.',
            'supplier_batch_number.max' => 'Supplier batch number cannot exceed 255 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'batch_number' => 'batch number',
            'product_id' => 'product',
            'branch_id' => 'branch',
            'warehouse_location_id' => 'warehouse location',
            'quantity_produced' => 'quantity produced',
            'quantity_available' => 'quantity available',
            'unit_cost' => 'unit cost',
            'unit_price' => 'unit price',
            'production_date' => 'production date',
            'expiry_date' => 'expiry date',
            'best_before_date' => 'best before date',
            'supplier_name' => 'supplier name',
            'supplier_batch_number' => 'supplier batch number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure numeric fields are properly formatted
        if ($this->has('quantity_produced')) {
            $this->merge([
                'quantity_produced' => (int) $this->quantity_produced,
            ]);
        }

        if ($this->has('quantity_available')) {
            $this->merge([
                'quantity_available' => (int) $this->quantity_available,
            ]);
        }

        if ($this->has('unit_cost') && $this->unit_cost !== null) {
            $this->merge([
                'unit_cost' => (float) $this->unit_cost,
            ]);
        }

        if ($this->has('unit_price') && $this->unit_price !== null) {
            $this->merge([
                'unit_price' => (float) $this->unit_price,
            ]);
        }

        // Set default quantity_available if not provided
        if (!$this->has('quantity_available') && $this->has('quantity_produced')) {
            $this->merge([
                'quantity_available' => $this->quantity_produced,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: expiry_date should be after best_before_date if both are provided
            if ($this->has('expiry_date') && $this->has('best_before_date') &&
                $this->expiry_date && $this->best_before_date) {
                $expiryDate = \Carbon\Carbon::parse($this->expiry_date);
                $bestBeforeDate = \Carbon\Carbon::parse($this->best_before_date);

                if ($expiryDate->lt($bestBeforeDate)) {
                    $validator->errors()->add('expiry_date', 'Expiry date must be after best before date.');
                }
            }

            // Custom validation: quantity_available cannot exceed quantity_produced
            if ($this->has('quantity_available') && $this->has('quantity_produced') &&
                $this->quantity_available > $this->quantity_produced) {
                $validator->errors()->add('quantity_available', 'Quantity available cannot exceed quantity produced.');
            }
        });
    }
}
