<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:product_purchases,id',
            'branch_id' => 'nullable|exists:branches,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank,bank_transfer,upi,cheque,other',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'allocations' => 'nullable|array',
            'allocations.*.purchase_id' => 'required_with:allocations|exists:product_purchases,id',
            'allocations.*.amount' => 'required_with:allocations|numeric|min:0',
        ];
    }
}
