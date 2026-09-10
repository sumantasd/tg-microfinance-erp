<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('branch_id')) {
            $companyId = auth()->user()?->company_id ?? 1;
            $centralWarehouse = \App\Models\Branch::getCentralWarehouse($companyId);
            $this->merge(['branch_id' => $centralWarehouse->id]);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'new_stock_level' => 'required|integer|min:0',
            'remarks' => 'required|string|max:255',
        ];
    }
}
