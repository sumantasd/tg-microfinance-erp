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
                    'repayment_frequency' => $scheme->repayment_frequency,
                ]);
            }
        }

        $application = $this->route('loanApplication');
        $loanType = $application ? $application->loan_type : $this->loan_type;

        if ($loanType === 'product' && is_array($this->products)) {
            $totalProductPrice = 0;
            foreach ($this->products as $p) {
                if (!empty($p['product_id'])) {
                    $prod = \App\Models\Product::find($p['product_id']);
                    if ($prod) {
                        $qty = max(1, (int) ($p['quantity'] ?? 1));
                        $totalProductPrice += ((float) $prod->unit_price * $qty);
                    }
                }
            }
            if ($totalProductPrice > 0) {
                $this->merge([
                    'requested_amount' => round($totalProductPrice, 2),
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
            'products.*.category_id' => 'required_with:products|nullable|exists:product_categories,id',
            'products.*.brand_id' => 'required_with:products|nullable|exists:product_brands,id',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
        ];
    }
}
