<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('group.manage_members') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'role' => 'required|in:group_leader,member',
        ];
    }
}
