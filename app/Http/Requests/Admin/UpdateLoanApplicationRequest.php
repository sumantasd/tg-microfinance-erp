<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_scheme_id' => 'required|exists:loan_schemes,id',
            'requested_amount' => 'required|numeric|min:1',
            'tenure_months' => 'required|integer|min:1',
            'repayment_frequency' => 'nullable|string|in:weekly,bi_weekly,monthly',
            'purpose' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',

            // Group Member Allocations
            'members' => 'nullable|array',
            'members.*.customer_id' => 'required_with:members|exists:customers,id',
            'members.*.requested_amount' => 'required_with:members|numeric|min:1',

            // Product Line Items
            'products' => 'nullable|array',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
        ];
    }
}
