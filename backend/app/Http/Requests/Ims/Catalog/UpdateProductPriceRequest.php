<?php

namespace App\Http\Requests\Ims\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'storeAdmin']);
    }

    public function rules(): array
    {
        return [
            'selling_price' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ];
    }
}