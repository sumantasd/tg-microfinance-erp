<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:150',
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('branches')->where(function ($query) {
                    return $query->where('company_id', $this->input('company_id'));
                }),
            ],
            'manager_id' => 'nullable|exists:users,id',
            'email' => 'nullable|email|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:50',
            'pincode' => 'required|string|max:10',
            'vault_cash_limit' => 'nullable|numeric|min:0',
            'current_vault_balance' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
