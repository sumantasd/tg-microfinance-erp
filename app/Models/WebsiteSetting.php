<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'legal_name',
        'company_type',
        'tagline',
        'registration_number',
        'gstin',
        'pan',
        'cin',
        'logo',
        'logo_icon',
        'favicon',
        'phone',
        'alternate_phone',
        'email',
        'website',
        'whatsapp',
        'address',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'state',
        'country',
        'pin_code',
        'social_links',
        'footer_text',

        // Loan Calculator
        'calc_enabled',
        'calc_title',
        'calc_subtitle',
        'calc_default_amount',
        'calc_min_amount',
        'calc_max_amount',
        'calc_tenure_options',
        'calc_interest_rate',
        'calc_type',
        'calc_rounding_type',
        'calc_cta_text',
        'calc_cta_url',

        // Location & Support Section
        'location_heading',
        'location_description',
        'support_box_title',
        'support_box_desc',
        'support_box_button_text',
        'support_box_button_url',

        // System Loan Charges Settings
        'loan_processing_fee_percentage',
        'loan_processing_fee_enabled',
        'loan_insurance_percentage',
        'loan_insurance_enabled',

        // System Theme Customization Settings
        'theme_preset',
        'primary_color',
        'secondary_color',
        'accent_color',
        'sidebar_color',
        'sidebar_text_color',
        'sidebar_active_color',
        'header_color',
        'header_text_color',
        'body_background_color',
        'card_background_color',
        'border_color',
        'button_radius',
        'theme_mode',
    ];

    protected $casts = [
        'social_links' => 'array',
        'calc_tenure_options' => 'array',
        'calc_enabled' => 'boolean',
        'loan_processing_fee_percentage' => 'decimal:2',
        'loan_processing_fee_enabled' => 'boolean',
        'loan_insurance_percentage' => 'decimal:2',
        'loan_insurance_enabled' => 'boolean',
    ];

    /**
     * Accessor for authoritative system name.
     */
    public function getSystemNameAttribute(): string
    {
        return !empty($this->company_name) ? $this->company_name : config('app.name', 'Microfinance ERP');
    }

    /**
     * Accessor for full logo asset URL with dynamic fallback.
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo) {
            if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
                return $this->logo;
            }
            $path = ltrim(str_replace('storage/', '', $this->logo), '/');
            return asset('storage/' . $path);
        }

        return asset('images/logo.png');
    }

    /**
     * Accessor for full logo icon asset URL with dynamic fallback.
     */
    public function getLogoIconUrlAttribute(): string
    {
        if ($this->logo_icon) {
            if (str_starts_with($this->logo_icon, 'http://') || str_starts_with($this->logo_icon, 'https://')) {
                return $this->logo_icon;
            }
            $path = ltrim(str_replace('storage/', '', $this->logo_icon), '/');
            return asset('storage/' . $path);
        }

        return asset('images/logo-icon.png');
    }

    /**
     * Accessor for full favicon asset URL with dynamic fallback.
     */
    public function getFaviconUrlAttribute(): string
    {
        if ($this->favicon) {
            if (str_starts_with($this->favicon, 'http://') || str_starts_with($this->favicon, 'https://')) {
                return $this->favicon;
            }
            $path = ltrim(str_replace('storage/', '', $this->favicon), '/');
            return asset('storage/' . $path);
        }

        return asset('images/logo-icon.png');
    }

    /**
     * Accessor for formatted full company address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1 ?: $this->address,
            $this->address_line_2,
            $this->city,
            $this->district,
            $this->state,
            $this->pin_code ? "PIN - {$this->pin_code}" : null,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
