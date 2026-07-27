<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_key',
        'title',
        'subtitle',
        'description',
        'image',
        'button_text',
        'button_url',
        'status',
        'sort_order',

        // Mission & Vision
        'mission_title',
        'mission_description',
        'mission_icon',
        'vision_title',
        'vision_description',
        'vision_icon',

        // Governance
        'governance_title',
        'governance_subtitle',
        'governance_description',
        'governance_bullets',
        'governance_icon',

        // Homepage CTA
        'cta_heading',
        'cta_description',
        'cta_button1_text',
        'cta_button1_url',
        'cta_button2_text',
        'cta_button2_url',
        'cta_bg_style',

        // Headquarters & Branch Section
        'head_office_title',
        'address',
        'phone',
        'email',
        'support_box_title',
        'support_box_description',
        'support_button_text',
        'support_button_url',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'governance_bullets' => 'array',
    ];

    /**
     * Accessor for full section image asset URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        $path = ltrim(str_replace('storage/', '', $this->image), '/');
        return asset('storage/' . $path);
    }
}
