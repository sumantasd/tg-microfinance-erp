<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('users.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status' => ['required', 'in:active,inactive,suspended,locked'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'company_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }
}
