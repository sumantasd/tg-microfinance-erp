<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->loan_scheme_id) {
            $scheme = \App\Models\LoanScheme::find($this->loan_scheme_id);
            if ($scheme) {
                $this->merge([
                    'tenure_months' => $scheme->min_tenure_months,
                    'repayment_frequency' => $this->repayment_frequency ?: $scheme->repayment_frequency,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'loan_scheme_id' => 'required|exists:loan_schemes,id',
            'requested_amount' => 'required|numeric|min:1',
            'tenure_months' => 'nullable|integer|min:1',
            'repayment_frequency' => 'nullable|string|in:weekly,bi_weekly,monthly',
            'purpose' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',

            // Group Member Allocations
            'members' => 'nullable|array',
            'members.*.customer_id' => 'required_with:members|exists:customers,id',
            'members.*.requested_amount' => 'required_with:members|numeric|min:1',

            // Product Line Items
            'products' => 'nullable|array',
            'products.*.category_id' => 'nullable|exists:product_categories,id',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
        ];
    }
}
