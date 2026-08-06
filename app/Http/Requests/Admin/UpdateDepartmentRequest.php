<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department') ? $this->route('department')->id : null;

        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:100',
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('departments')->where(function ($query) {
                    return $query->where('company_id', $this->input('company_id'));
                })->ignore($departmentId),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
