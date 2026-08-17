<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_name' => 'required|string|max:150',
            'account_group' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }
}
