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
        $targetUser = $this->route('user') instanceof \App\Models\User
            ? $this->route('user')
            : \App\Models\User::find($this->route('user') ?? $this->route('id'));
        $userId = $targetUser?->id;
        $branchId = $this->input('branch_id') ?? $targetUser?->branch_id;
        $isBranchManager = $this->input('role') === 'Branch Manager';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($userId)],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status' => ['required', 'in:active,inactive,suspended,locked'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => [
                $isBranchManager ? 'required' : 'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'employee_record_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
                function ($attribute, $value, $fail) use ($userId, $branchId) {
                    if ($value) {
                        $employee = \App\Models\Employee::find($value);
                        if ($employee && !empty($employee->user_id) && (int) $employee->user_id !== (int) $userId) {
                            $fail('The selected employee profile is already linked to another user account.');
                        }
                        if ($employee && $branchId && (int) $employee->branch_id !== (int) $branchId) {
                            $fail('The selected employee profile belongs to a different branch than the user.');
                        }
                    }
                },
            ],
            'unlink_employee' => ['nullable', 'boolean'],
            'auto_create_employee' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Please select a branch for this Branch Manager.',
            'branch_id.required_if' => 'Please select a branch for this Branch Manager.',
            'branch_id.exists' => 'Selected branch is invalid or inactive.',
        ];
    }
}
