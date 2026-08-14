<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RecordLoanRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,bank_transfer,upi,cheque,card',
            'reference_number' => 'nullable|string|max:100',
            'adjustment_mode' => 'required|string|in:reduce_tenure,reduce_emi,none',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
        ];
    }
}
