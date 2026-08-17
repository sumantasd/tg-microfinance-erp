<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'account_code' => 'required|string|max:30',
            'account_name' => 'required|string|max:150',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'account_group' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }
}
