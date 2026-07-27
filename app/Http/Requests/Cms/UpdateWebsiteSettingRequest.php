<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico,webp|max:1024',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.twitter' => 'nullable|url|max:255',
            'social_links.linkedin' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.youtube' => 'nullable|url|max:255',
            'footer_text' => 'nullable|string',

            // Loan Calculator fields
            'calc_enabled' => 'nullable|boolean',
            'calc_title' => 'nullable|string|max:255',
            'calc_subtitle' => 'nullable|string|max:255',
            'calc_default_amount' => 'nullable|string|max:100',
            'calc_min_amount' => 'nullable|string|max:100',
            'calc_max_amount' => 'nullable|string|max:100',
            'calc_tenure_options' => 'nullable|array',
            'calc_interest_rate' => 'nullable|string|max:100',
            'calc_type' => 'nullable|string|max:100',
            'calc_rounding_type' => 'nullable|string|max:100',
            'calc_cta_text' => 'nullable|string|max:100',
            'calc_cta_url' => 'nullable|string|max:255',

            // Location & Support Section fields
            'location_heading' => 'nullable|string|max:255',
            'location_description' => 'nullable|string',
            'support_box_title' => 'nullable|string|max:255',
            'support_box_desc' => 'nullable|string',
            'support_box_button_text' => 'nullable|string|max:100',
            'support_box_button_url' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'calc_enabled' => $this->has('calc_enabled') ? (bool) $this->calc_enabled : false,
        ]);
    }
}
