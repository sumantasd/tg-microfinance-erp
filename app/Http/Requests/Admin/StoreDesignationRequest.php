<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'title' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
