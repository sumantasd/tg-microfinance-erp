<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SanctionLoanAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_application_id' => 'required|exists:loan_applications,id',
            'down_payment_amount' => 'nullable|numeric|min:0',
            'other_charges_amount' => 'nullable|numeric|min:0',
            'sanction_date' => 'nullable|date',
        ];
    }
}
