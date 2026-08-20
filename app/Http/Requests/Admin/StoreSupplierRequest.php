<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'supplier_code' => 'nullable|string|max:50',
            'supplier_type' => 'required|in:individual,company,distributor,manufacturer,other',
            'supplier_name' => 'required|string|max:150',
            'company_name' => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'required|string|max:20',
            'alternate_mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:15',
            'country' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_balance_type' => 'required|in:payable,receivable',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'branch_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ];
    }
}
