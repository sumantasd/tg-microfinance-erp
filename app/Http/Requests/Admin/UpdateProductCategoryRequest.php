<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
    }
}
