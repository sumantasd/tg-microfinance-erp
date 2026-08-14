<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifyKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:verification_status,rejected|nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ];
    }
}
