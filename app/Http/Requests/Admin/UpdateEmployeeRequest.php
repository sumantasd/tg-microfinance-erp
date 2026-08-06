<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee') ? $this->route('employee')->id : null;

        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employeeId)],
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'reporting_manager_id' => 'nullable|exists:employees,id',
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
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
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            'status' => 'nullable|in:active,resigned,terminated,on_leave',
            'login_enabled' => 'nullable|boolean',
            'role' => 'nullable|string|exists:roles,name',
            'documents' => 'nullable|array',
            'documents.*.file' => 'nullable|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:5120',
            'documents.*.type' => 'nullable|string',
            'documents.*.title' => 'nullable|string|max:150',
        ];
    }
}
