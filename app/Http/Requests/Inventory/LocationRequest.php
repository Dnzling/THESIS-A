<?php
// backend/app/Http\Requests/Inventory/LocationRequest.php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Inventory\Warehouse;
use App\Models\Store\Branch;

class LocationRequest extends FormRequest
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
        $locationId = $this->route('location')?->id;
        $isUpdate = $this->route('location') !== null;

        return [
            'warehouse_id' => [
                Rule::requiredIf($isUpdate),
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id'),
            ],
            'location_code' => [
                Rule::requiredIf($isUpdate),
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('warehouse_locations', 'location_code')->ignore($locationId),
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
                Rule::in(['rack', 'shelf', 'bin', 'floor', 'cold_storage', 'secure', 'bulk']),
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive', 'maintenance', 'full']),
            ],
            'aisle' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
            ],
            'rack' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
            ],
            'shelf' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
            ],
            'bin' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
            ],
            'max_capacity_units' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'current_stock_units' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'max_weight_kg' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'current_weight_kg' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'dimensions' => [
                'nullable',
                'array',
            ],
            'dimensions.width' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'dimensions.height' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'dimensions.depth' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'is_temperature_controlled' => [
                'boolean',
            ],
            'min_temperature_c' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'required_if:is_temperature_controlled,true',
            ],
            'max_temperature_c' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'required_if:is_temperature_controlled,true',
                'gte:min_temperature_c',
            ],
            'requires_special_handling' => [
                'boolean',
            ],
            'special_handling_instructions' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:requires_special_handling,true',
            ],
            'last_inventory_check' => [
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * Branch users do not choose a warehouse when creating a location.
     * Resolve the active warehouse for their branch before validation runs.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->isMethod('post')) {
            return;
        }

        $user = $this->user();
        $branchId = (int) ($user?->branch_id ?: $user?->employee?->branch_id ?: 0);
        $storeId = (int) ($user?->store_id ?: $user?->employee?->store_id ?: 0);

        if ($branchId <= 0) {
            return;
        }

        $branch = Branch::query()
            ->whereKey($branchId)
            ->when($storeId > 0, fn ($query) => $query->where('store_id', $storeId))
            ->where('branch_type', 'warehouse')
            ->first();

        if (!$branch) {
            return;
        }

        $warehouseQuery = Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->orderBy('id');

        if ($storeId > 0) {
            $warehouseQuery->where('store_id', $storeId);
        }

        if ($warehouse = $warehouseQuery->first()) {
            $this->merge(['warehouse_id' => $warehouse->id]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'warehouse_id' => 'warehouse',
            'location_code' => 'location code',
            'max_capacity_units' => 'maximum capacity (units)',
            'current_stock_units' => 'current stock (units)',
            'max_weight_kg' => 'maximum weight (kg)',
            'current_weight_kg' => 'current weight (kg)',
            'min_temperature_c' => 'minimum temperature (°C)',
            'max_temperature_c' => 'maximum temperature (°C)',
            'is_temperature_controlled' => 'temperature controlled',
            'requires_special_handling' => 'requires special handling',
            'special_handling_instructions' => 'special handling instructions',
            'last_inventory_check' => 'last inventory check',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'location_code.regex' => 'Location code may only contain uppercase letters, numbers, underscores, and hyphens.',
            'location_code.unique' => 'This location code is already in use.',
            'aisle.regex' => 'Aisle may only contain uppercase letters and numbers.',
            'rack.regex' => 'Rack may only contain uppercase letters and numbers.',
            'shelf.regex' => 'Shelf may only contain uppercase letters and numbers.',
            'bin.regex' => 'Bin may only contain uppercase letters and numbers.',
            'max_temperature_c.gte' => 'Maximum temperature must be greater than or equal to minimum temperature.',
            'min_temperature_c.required_if' => 'Minimum temperature is required when temperature control is enabled.',
            'max_temperature_c.required_if' => 'Maximum temperature is required when temperature control is enabled.',
            'special_handling_instructions.required_if' => 'Special handling instructions are required when special handling is required.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: ensure warehouse exists and is active
            if ($this->warehouse_id) {
                $warehouse = Warehouse::find($this->warehouse_id);
                if ($warehouse && $warehouse->status !== 'active') {
                    $validator->errors()->add('warehouse_id', 'Cannot create locations in inactive warehouses.');
                }

                // A branch user may only create locations in that branch's warehouse.
                $user = $this->user();
                $userBranchId = (int) ($user?->branch_id ?: $user?->employee?->branch_id ?: 0);
                $userStoreId = (int) ($user?->store_id ?: $user?->employee?->store_id ?: 0);
                if ($warehouse && $user) {
                    if ($userStoreId > 0 && (int) $warehouse->store_id !== $userStoreId) {
                        $validator->errors()->add('warehouse_id', 'This warehouse does not belong to your store.');
                    }
                    if ($userBranchId > 0 && (int) $warehouse->branch_id !== $userBranchId) {
                        $validator->errors()->add('warehouse_id', 'This warehouse does not belong to your branch.');
                    }
                }

                if ($warehouse && $warehouse->branch && $warehouse->branch->branch_type !== 'warehouse') {
                    $validator->errors()->add('warehouse_id', 'Locations can only be created for warehouse branches.');
                }
            }

            // Custom validation: ensure current stock doesn't exceed max capacity
            if ($this->max_capacity_units && $this->current_stock_units > $this->max_capacity_units) {
                $validator->errors()->add('current_stock_units', 'Current stock cannot exceed maximum capacity.');
            }

            // Custom validation: ensure current weight doesn't exceed max weight
            if ($this->max_weight_kg && $this->current_weight_kg > $this->max_weight_kg) {
                $validator->errors()->add('current_weight_kg', 'Current weight cannot exceed maximum weight.');
            }
        });
    }
}
