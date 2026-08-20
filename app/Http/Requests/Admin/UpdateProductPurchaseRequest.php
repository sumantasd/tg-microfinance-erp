<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|string|max:150',
            'supplier_reference' => 'nullable|string|max:100',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'discount_amount' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:product_categories,id',
            'items.*.brand_id' => 'required|exists:product_brands,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_purchase_cost' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
        ];
    }
}
