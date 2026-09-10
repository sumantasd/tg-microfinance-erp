<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PayExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expense.pay');
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,upi,cheque,other'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ];
    }
}
