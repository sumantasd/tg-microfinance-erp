<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expense.category.manage');
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'category_code' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'exists:expense_categories,id'],
            'chart_of_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
