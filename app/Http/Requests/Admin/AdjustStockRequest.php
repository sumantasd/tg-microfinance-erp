<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
