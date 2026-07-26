<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('users.edit');
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($userId)],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status' => ['required', 'in:active,inactive,suspended,locked'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'company_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }
}
