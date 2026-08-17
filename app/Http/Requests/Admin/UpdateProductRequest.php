<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product') ? $this->route('product')->id : null;

        return [
            'company_id' => 'nullable|exists:companies,id',
            'sku' => 'required|string|max:50|unique:products,sku,' . $id,
            'name' => 'required|string|max:150',
            'brand_id' => 'nullable|exists:product_brands,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'brand' => 'nullable|string|max:100',
            'model_number' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
