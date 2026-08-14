<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('group.edit') ?? false;
    }

    public function rules(): array
    {
        $groupId = $this->route('group')?->id ?? $this->route('group');

        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'group_code' => ['required', 'string', 'max:50', Rule::unique('customer_groups', 'group_code')->ignore($groupId)],
            'name' => 'required|string|max:150',
            'leader_customer_id' => 'nullable|exists:customers,id',
            'meeting_day' => 'nullable|string|max:30',
            'meeting_time' => 'nullable|string|max:20',
            'meeting_location' => 'nullable|string|max:255',
            'formation_date' => 'required|date',
            'status' => 'required|in:active,inactive,closed',
            'remarks' => 'nullable|string|max:1000',
        ];
    }
}
