<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sectionId = $this->route('homepage_section') ? $this->route('homepage_section')->id : null;

        return [
            'section_key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('homepage_sections', 'section_key')->ignore($sectionId),
            ],
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer|min:0',

            // Mission & Vision
            'mission_title' => 'nullable|string|max:255',
            'mission_description' => 'nullable|string',
            'mission_icon' => 'nullable|string|max:100',
            'vision_title' => 'nullable|string|max:255',
            'vision_description' => 'nullable|string',
            'vision_icon' => 'nullable|string|max:100',

            // Governance
            'governance_title' => 'nullable|string|max:255',
            'governance_subtitle' => 'nullable|string|max:255',
            'governance_description' => 'nullable|string',
            'governance_bullets' => 'nullable|array',
            'governance_bullets.*' => 'nullable|string|max:255',
            'governance_icon' => 'nullable|string|max:100',

            // CTA
            'cta_heading' => 'nullable|string|max:255',
            'cta_description' => 'nullable|string',
            'cta_button1_text' => 'nullable|string|max:100',
            'cta_button1_url' => 'nullable|string|max:255',
            'cta_button2_text' => 'nullable|string|max:100',
            'cta_button2_url' => 'nullable|string|max:255',
            'cta_bg_style' => 'nullable|string|max:50',

            // Headquarters
            'head_office_title' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'support_box_title' => 'nullable|string|max:255',
            'support_box_description' => 'nullable|string',
            'support_button_text' => 'nullable|string|max:100',
            'support_button_url' => 'nullable|string|max:255',
        ];
    }
}
