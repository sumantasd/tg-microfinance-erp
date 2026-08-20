<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'logo',
        'favicon',
        'phone',
        'email',
        'address',
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
     * Accessor for full logo asset URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        $path = ltrim(str_replace('storage/', '', $this->logo), '/');
        return asset('storage/' . $path);
    }

    /**
     * Accessor for full favicon asset URL.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon) {
            return null;
        }

        if (str_starts_with($this->favicon, 'http://') || str_starts_with($this->favicon, 'https://')) {
            return $this->favicon;
        }

        $path = ltrim(str_replace('storage/', '', $this->favicon), '/');
        return asset('storage/' . $path);
    }
}
