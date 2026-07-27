<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    use HasFactory;

    protected $table = 'seo_settings';

    protected $fillable = [
        'page_name',
        'meta_title',
        'meta_description',
        'keywords',
        'og_image',
        'status',
    ];

    /**
     * Accessor for full OpenGraph image URL.
     */
    public function getOgImageUrlAttribute(): ?string
    {
        if (!$this->og_image) {
            return null;
        }

        if (str_starts_with($this->og_image, 'http://') || str_starts_with($this->og_image, 'https://')) {
            return $this->og_image;
        }

        $path = ltrim(str_replace('storage/', '', $this->og_image), '/');
        return asset('storage/' . $path);
    }
}
