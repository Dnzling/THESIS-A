<?php
// backend/app/Http\Requests/Inventory/WarehouseRequest.php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Store\Branch;
use App\Models\Store\Store;

class WarehouseRequest extends FormRequest
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
        $warehouseId = $this->route('warehouse')?->id;

        return [
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id'),
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id'),
                function ($attribute, $value, $fail) {
                    $store = Store::find($this->store_id);
                    if ($store && !$store->branches()->where('id', $value)->exists()) {
                        $fail('The selected branch does not belong to the specified store.');
                    }
                },
            ],
            'warehouse_code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('warehouses', 'warehouse_code')->ignore($warehouseId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['main', 'branch', 'distribution', 'storage', 'retail']),
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive', 'maintenance']),
            ],
            'address_line_1' => [
                'required',
                'string',
                'max:255',
            ],
            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'required',
                'string',
                'max:100',
            ],
            'state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'country' => [
                'required',
                'string',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\-\(\)\s]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'manager_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'manager_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\-\(\)\s]+$/',
            ],
            'total_area_sqm' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'usable_area_sqm' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
                'lte:total_area_sqm',
            ],
            'total_racks' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'total_shelves' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'max_capacity_units' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'opening_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'closing_time' => [
                'nullable',
                'date_format:H:i',
                'after:opening_time',
            ],
            'operating_days' => [
                'nullable',
                'array',
                'min:1',
                'max:7',
            ],
            'operating_days.*' => [
                'integer',
                'between:0,6', // 0 = Sunday, 6 = Saturday
            ],
            'requires_access_card' => [
                'boolean',
            ],
            'has_security_system' => [
                'boolean',
            ],
            'has_fire_system' => [
                'boolean',
            ],
            'access_instructions' => [
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
            'store_id' => 'store',
            'branch_id' => 'branch',
            'warehouse_code' => 'warehouse code',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
            'manager_name' => 'manager name',
            'manager_phone' => 'manager phone',
            'total_area_sqm' => 'total area (sqm)',
            'usable_area_sqm' => 'usable area (sqm)',
            'total_racks' => 'total racks',
            'total_shelves' => 'total shelves',
            'max_capacity_units' => 'maximum capacity (units)',
            'opening_time' => 'opening time',
            'closing_time' => 'closing time',
            'operating_days' => 'operating days',
            'requires_access_card' => 'requires access card',
            'has_security_system' => 'has security system',
            'has_fire_system' => 'has fire system',
            'access_instructions' => 'access instructions',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'warehouse_code.regex' => 'Warehouse code may only contain uppercase letters, numbers, underscores, and hyphens.',
            'warehouse_code.unique' => 'This warehouse code is already in use.',
            'usable_area_sqm.lte' => 'Usable area cannot be greater than total area.',
            'closing_time.after' => 'Closing time must be after opening time.',
            'operating_days.*.between' => 'Operating days must be between 0 (Sunday) and 6 (Saturday).',
            'phone.regex' => 'Please enter a valid phone number.',
            'manager_phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
