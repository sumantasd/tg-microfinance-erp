<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class InterestRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'product_type' => 'required|in:loan,savings',
            'amount_range' => 'nullable|string|max:255',
            'tenure_options' => 'nullable|string|max:255',
            'interest_rate' => 'required|string|max:100',
            'interest_method' => 'required|in:Flat,Reducing Balance,Daily Reducing',
            'processing_fee' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
