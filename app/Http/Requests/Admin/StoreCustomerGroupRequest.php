<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('group.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'group_code' => 'nullable|string|max:50|unique:customer_groups,group_code',
            'name' => 'required|string|max:150',
            'meeting_day' => 'nullable|string|max:30',
            'meeting_time' => 'nullable|string|max:20',
            'meeting_location' => 'nullable|string|max:255',
            'formation_date' => 'required|date',
            'status' => 'required|in:active,inactive,closed',
            'remarks' => 'nullable|string|max:1000',
        ];
    }
}
