<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('employee.edit');
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee ? $employee->id : null;
        $authUser = $this->user();
        $isSuperAdmin = $authUser && $authUser->isSuperAdmin();
        $companyId = $isSuperAdmin ? (int) $this->input('company_id', $employee?->company_id) : (int) $authUser?->company_id;

        return [
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
                function ($attribute, $value, $fail) use ($isSuperAdmin, $authUser) {
                    if (!$isSuperAdmin && $authUser && (int) $value !== (int) $authUser->company_id) {
                        $fail('You cannot reassign an employee to a different company.');
                    }
                },
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId);
                    }
                    $q->where('is_active', true);
                }),
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId);
                    }
                    $q->where('is_active', true);
                }),
            ],
            'designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId);
                    }
                    $q->where('is_active', true);
                }),
            ],
            'reporting_manager_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where(function ($q) use ($companyId, $employeeId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId);
                    }
                    if ($employeeId) {
                        $q->where('id', '!=', $employeeId);
                    }
                    $q->where('status', 'active');
                }),
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::unique('employees', 'user_id')->ignore($employeeId),
                function ($attribute, $value, $fail) use ($companyId, $employee) {
                    if ($value) {
                        $selectedUser = User::find($value);
                        if ($selectedUser && $companyId && (int) $selectedUser->company_id !== (int) $companyId) {
                            $fail('The selected user belongs to a different company.');
                        }
                        if ($selectedUser && $selectedUser->employee && $selectedUser->employee->id !== $employee?->id) {
                            $fail('The selected user is already linked to another employee.');
                        }
                    }
                },
            ],
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female,other',
            'dob' => 'nullable|date',
            'blood_group' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'aadhaar_number' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:20',
            'voter_id' => 'nullable|string|max:20',
            'driving_license' => 'nullable|string|max:30',
            'passport_number' => 'nullable|string|max:30',
            'joining_date' => 'required|date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,probationary',
            'probation_end_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:monthly,daily,hourly,commission',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            'status' => 'nullable|in:active,resigned,terminated,on_leave',
            'login_enabled' => 'nullable|boolean',
            'user_mode' => 'nullable|string|in:create,link,none',
            'user_email' => 'nullable|email|max:100|unique:users,email',
            'user_password' => [
                'nullable',
                'required_if:user_mode,create',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role' => [
                'nullable',
                'string',
                'exists:roles,name',
                function ($attribute, $value, $fail) use ($isSuperAdmin) {
                    if ($value === 'Super Admin' && !$isSuperAdmin) {
                        $fail('You are not authorized to assign the Super Admin role.');
                    }
                },
            ],
            'documents' => 'nullable|array',
            'documents.*.file' => 'nullable|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:5120',
            'documents.*.type' => 'nullable|string',
            'documents.*.title' => 'nullable|string|max:150',
        ];
    }
}
