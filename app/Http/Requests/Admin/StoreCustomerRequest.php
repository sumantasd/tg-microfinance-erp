<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code',
            'member_number' => 'nullable|string|max:50|unique:customers,member_number',
            'customer_type' => 'required|in:individual,group_member,micro_enterprise,corporate',
            'status' => 'nullable|in:active,inactive,blacklisted,deceased,closed',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'father_husband_guardian_name' => 'nullable|string|max:100',
            'mobile_number' => 'required|string|max:20',
            'alternate_contact' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'dob' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'occupation' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0',
            'registration_date' => 'required|date',
            'remarks' => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // Addresses
            'addresses' => 'required|array',
            'addresses.present.address_line' => 'required|string|max:255',
            'addresses.present.village_area' => 'nullable|string|max:100',
            'addresses.present.post_office' => 'nullable|string|max:100',
            'addresses.present.police_station' => 'nullable|string|max:100',
            'addresses.present.district' => 'required|string|max:100',
            'addresses.present.state' => 'required|string|max:100',
            'addresses.present.pin_code' => 'required|string|max:10',

            'addresses.permanent.address_line' => 'nullable|string|max:255',
            'addresses.permanent.village_area' => 'nullable|string|max:100',
            'addresses.permanent.post_office' => 'nullable|string|max:100',
            'addresses.permanent.police_station' => 'nullable|string|max:100',
            'addresses.permanent.district' => 'nullable|string|max:100',
            'addresses.permanent.state' => 'nullable|string|max:100',
            'addresses.permanent.pin_code' => 'nullable|string|max:10',

            // Optional Initial KYC
            'kyc' => 'nullable|array',
            'kyc.*.type' => 'nullable|string|max:50',
            'kyc.*.number' => 'nullable|string|max:50',
            'kyc.*.file' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',

            // Optional Initial Guarantor
            'guarantors' => 'nullable|array',
            'guarantors.*.full_name' => 'nullable|string|max:100',
            'guarantors.*.relationship' => 'nullable|string|max:50',
            'guarantors.*.mobile' => 'nullable|string|max:20',
            'guarantors.*.address' => 'nullable|string',

            // Optional Initial Nominee
            'nominees' => 'nullable|array',
            'nominees.*.nominee_name' => 'nullable|string|max:100',
            'nominees.*.relationship' => 'nullable|string|max:50',
            'nominees.*.share_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
