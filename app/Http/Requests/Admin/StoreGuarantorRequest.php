<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuarantorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|exists:customer_guarantors,id',
            'full_name' => 'required|string|max:100',
            'relationship' => 'required|string|max:50',
            'mobile' => 'required|string|max:20',
            'alternate_contact' => 'nullable|string|max:20',
            'address' => 'required|string',
            'occupation' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0',
            'kyc_type' => 'nullable|string|max:50',
            'kyc_number' => 'nullable|string|max:50',
            'kyc_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'remarks' => 'nullable|string',
        ];
    }
}
