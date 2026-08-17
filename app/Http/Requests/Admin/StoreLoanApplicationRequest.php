<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanApplicationRequest extends FormRequest
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

        if ($this->borrower_type === 'individual') {
            $this->request->remove('customer_group_id');
            $this->request->remove('members');
        } else {
            $this->request->remove('customer_id');
        }

        if ($this->loan_type === 'cash') {
            $this->request->remove('products');
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'loan_scheme_id' => 'required|exists:loan_schemes,id',
            'loan_type' => 'required|in:cash,product',
            'borrower_type' => 'required|in:individual,group',
            'customer_id' => 'required_if:borrower_type,individual|nullable|exists:customers,id',
            'customer_group_id' => 'required_if:borrower_type,group|nullable|exists:customer_groups,id',
            'application_date' => 'required|date',
            'requested_amount' => 'required|numeric|min:1',
            'tenure_months' => 'nullable|integer|min:1',
            'repayment_frequency' => 'nullable|string|in:weekly,bi_weekly,monthly',
            'purpose' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            
            // Group Member Allocations
            'members' => 'required_if:borrower_type,group|nullable|array',
            'members.*.customer_id' => 'required_with:members|exists:customers,id',
            'members.*.requested_amount' => 'required_with:members|numeric|min:1',
            'members.*.remarks' => 'nullable|string|max:255',

            // Product Line Items
            'products' => 'required_if:loan_type,product|nullable|array',
            'products.*.category_id' => 'nullable|exists:product_categories,id',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'products.*.unit_price' => 'nullable|numeric|min:0',
            'products.*.remarks' => 'nullable|string|max:255',
        ];
    }
}
