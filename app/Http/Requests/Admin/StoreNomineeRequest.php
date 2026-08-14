<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNomineeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|exists:customer_nominees,id',
            'nominee_name' => 'required|string|max:100',
            'relationship' => 'required|string|max:50',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'share_percentage' => 'required|numeric|min:0.01|max:100.00',
            'is_minor' => 'nullable|boolean',
            'guardian_name' => 'required_if:is_minor,1|nullable|string|max:100',
            'guardian_relationship' => 'required_if:is_minor,1|nullable|string|max:50',
            'guardian_contact' => 'required_if:is_minor,1|nullable|string|max:20',
            'guardian_address' => 'nullable|string',
        ];
    }
}
