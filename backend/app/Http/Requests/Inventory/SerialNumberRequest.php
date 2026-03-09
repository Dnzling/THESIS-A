<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SerialNumberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage inventory');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'serial_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('serial_numbers')->ignore($this->route('serial_number')),
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
            'status' => [
                'sometimes',
                'string',
                Rule::in(['available', 'sold', 'reserved', 'damaged', 'returned', 'in_transit']),
            ],
            'condition' => [
                'sometimes',
                'string',
                Rule::in(['new', 'used', 'refurbished', 'damaged']),
            ],
            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'selling_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'purchase_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'sold_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'warranty_expiry' => [
                'nullable',
                'date',
                'after:today',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];

        // For updates, make some fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['serial_number'] = [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('serial_numbers')->ignore($this->route('serial_number')),
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
        }

        // Additional validation for selling
        if ($this->has('action') && $this->get('action') === 'sell') {
            $rules['selling_price'] = [
                'required',
                'numeric',
                'min:0',
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
            'serial_number.required' => 'Serial number is required.',
            'serial_number.unique' => 'This serial number already exists.',
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'warehouse_location_id.exists' => 'Selected warehouse location does not exist.',
            'status.in' => 'Status must be available, sold, reserved, damaged, returned, or in_transit.',
            'condition.in' => 'Condition must be new, used, refurbished, or damaged.',
            'purchase_price.numeric' => 'Purchase price must be a number.',
            'purchase_price.min' => 'Purchase price cannot be negative.',
            'selling_price.numeric' => 'Selling price must be a number.',
            'selling_price.min' => 'Selling price cannot be negative.',
            'selling_price.required' => 'Selling price is required when selling.',
            'purchase_date.before_or_equal' => 'Purchase date cannot be in the future.',
            'sold_date.before_or_equal' => 'Sold date cannot be in the future.',
            'warranty_expiry.after' => 'Warranty expiry must be in the future.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'serial_number' => 'serial number',
            'product_id' => 'product',
            'branch_id' => 'branch',
            'warehouse_location_id' => 'warehouse location',
            'purchase_price' => 'purchase price',
            'selling_price' => 'selling price',
            'purchase_date' => 'purchase date',
            'sold_date' => 'sold date',
            'warranty_expiry' => 'warranty expiry',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure numeric fields are properly formatted
        if ($this->has('purchase_price')) {
            $this->merge([
                'purchase_price' => $this->purchase_price ? (float) $this->purchase_price : null,
            ]);
        }

        if ($this->has('selling_price')) {
            $this->merge([
                'selling_price' => $this->selling_price ? (float) $this->selling_price : null,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: sold_date should be after purchase_date
            if ($this->has('sold_date') && $this->has('purchase_date') &&
                $this->sold_date && $this->purchase_date) {
                $soldDate = \Carbon\Carbon::parse($this->sold_date);
                $purchaseDate = \Carbon\Carbon::parse($this->purchase_date);

                if ($soldDate->lt($purchaseDate)) {
                    $validator->errors()->add('sold_date', 'Sold date must be after purchase date.');
                }
            }

            // Custom validation: warranty_expiry should be after purchase_date
            if ($this->has('warranty_expiry') && $this->has('purchase_date') &&
                $this->warranty_expiry && $this->purchase_date) {
                $warrantyExpiry = \Carbon\Carbon::parse($this->warranty_expiry);
                $purchaseDate = \Carbon\Carbon::parse($this->purchase_date);

                if ($warrantyExpiry->lt($purchaseDate)) {
                    $validator->errors()->add('warranty_expiry', 'Warranty expiry must be after purchase date.');
                }
            }
        });
    }
}
