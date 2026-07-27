<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeoSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seoId = $this->route('seo') ? $this->route('seo')->id : null;

        return [
            'page_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('seo_settings', 'page_name')->ignore($seoId),
            ],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'status' => 'required|in:active,inactive',
        ];
    }
}
