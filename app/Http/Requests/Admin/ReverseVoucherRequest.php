<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReverseVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reversal_reason' => 'required|string|max:500',
            'reversal_date' => 'nullable|date',
        ];
    }
}
