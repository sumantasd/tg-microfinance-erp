<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CmsLoanProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('loan_product') ? $this->route('loan_product')->id : null;

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cms_loan_products', 'slug')->ignore($productId),
            ],
            'description' => 'nullable|string',
            'min_amount' => 'nullable|string|max:100',
            'max_amount' => 'nullable|string|max:100',
            'interest_rate' => 'nullable|string|max:100',
            'tenure' => 'nullable|string|max:100',
            'repayment_frequency' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072',
            'icon' => 'nullable|string|max:100',
            'badge_color' => 'required|string|in:primary,success,info,warning,danger,secondary,dark',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer|min:0',
        ];
    }
}
