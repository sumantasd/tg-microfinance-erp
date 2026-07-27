<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterSetting extends Model
{
    use HasFactory;

    protected $table = 'footer_settings';

    protected $fillable = [
        'footer_logo',
        'about_text',
        'quick_links',
        'social_links',
        'address',
        'phone',
        'email',
        'copyright_text',
    ];

    protected $casts = [
        'quick_links' => 'array',
        'social_links' => 'array',
    ];

    /**
     * Accessor for full footer logo URL.
     */
    public function getFooterLogoUrlAttribute(): ?string
    {
        if (!$this->footer_logo) {
            return null;
        }

        if (str_starts_with($this->footer_logo, 'http://') || str_starts_with($this->footer_logo, 'https://')) {
            return $this->footer_logo;
        }

        $path = ltrim(str_replace('storage/', '', $this->footer_logo), '/');
        return asset('storage/' . $path);
    }
}
