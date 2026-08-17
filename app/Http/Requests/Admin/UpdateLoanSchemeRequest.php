<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanSchemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('loan_scheme') ? $this->route('loan_scheme')->id : null;

        return [
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'code' => 'required|string|max:50|unique:loan_schemes,code,' . $id,
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
            'penalty_type' => 'nullable|in:none,percentage_one_time,percentage_per_day,flat_one_time,flat_per_day',
            'flat_penalty_amount' => 'nullable|numeric|min:0',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'grace_period_days' => 'nullable|integer|min:0',
            'max_penalty_amount' => 'nullable|numeric|min:0',
            'max_penalty_percentage' => 'nullable|numeric|min:0|max:100',
            'allow_foreclosure' => 'nullable|boolean',
            'foreclosure_fee_type' => 'nullable|in:none,percentage,flat',
            'foreclosure_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'foreclosure_flat_fee' => 'nullable|numeric|min:0',
            'min_months_before_foreclosure' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ];
    }
}
