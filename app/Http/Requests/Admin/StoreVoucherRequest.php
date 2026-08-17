<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'voucher_type' => 'required|in:journal,receipt,payment,contra',
            'voucher_date' => 'required|date',
            'narration' => 'nullable|string|max:1000',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:chart_of_accounts,id',
            'entries.*.debit' => 'nullable|numeric|min:0',
            'entries.*.credit' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string|max:255',
        ];
    }
}
