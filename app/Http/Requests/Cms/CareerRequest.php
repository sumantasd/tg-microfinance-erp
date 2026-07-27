<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $careerId = $this->route('career') ? $this->route('career')->id : null;

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('careers', 'slug')->ignore($careerId),
            ],
            'location' => 'nullable|string|max:255',
            'job_type' => 'required|string|max:100',
            'short_description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'application_email' => 'nullable|email|max:255',
            'deadline' => 'nullable|date',
            'apply_button_text' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
