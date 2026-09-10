<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expense.edit');
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['sometimes', 'required', 'exists:branches,id'],
            'expense_date' => ['sometimes', 'required', 'date'],
            'expense_category_id' => ['sometimes', 'required', 'exists:expense_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'requested_by' => ['nullable', 'exists:users,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ];
    }
}
