<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CmsSavingsProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('savings_product') ? $this->route('savings_product')->id : null;

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cms_savings_products', 'slug')->ignore($productId),
            ],
            'description' => 'nullable|string',
            'interest_rate' => 'nullable|string|max:100',
            'min_balance' => 'nullable|string|max:100',
            'tenure' => 'nullable|string|max:100',
            'features' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072',
            'icon' => 'nullable|string|max:100',
            'badge_color' => 'required|string|in:primary,success,info,warning,danger,secondary,dark',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer|min:0',
        ];
    }
}
