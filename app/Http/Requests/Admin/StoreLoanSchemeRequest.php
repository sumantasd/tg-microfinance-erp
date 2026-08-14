<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanSchemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'code' => 'nullable|string|max:50|unique:loan_schemes,code',
            'name' => 'required|string|max:100',
            'loan_type' => 'required|in:cash,product,both',
            'applicant_type' => 'required|in:individual,group,both',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gte:min_amount',
            'interest_type' => 'required|in:flat,reducing_balance',
            'interest_rate_per_annum' => 'required|numeric|min:0|max:100',
            'min_tenure_months' => 'required|integer|min:1',
            'max_tenure_months' => 'required|integer|gte:min_tenure_months',
            'repayment_frequency' => 'required|in:weekly,bi_weekly,monthly',
            'processing_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'insurance_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'grace_period_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ];
    }
}
