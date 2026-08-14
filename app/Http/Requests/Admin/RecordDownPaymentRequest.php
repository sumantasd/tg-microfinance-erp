<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RecordDownPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bank_transfer,upi,cheque,card',
            'reference_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:255',
        ];
    }
}
