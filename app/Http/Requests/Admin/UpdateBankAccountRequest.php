<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'nullable|exists:branches,id',
            'chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'branch_name' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
        ];
    }
}
