<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'content',
        'featured_image',
        'published_date',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'published_date' => 'date',
        'sort_order' => 'integer',
    ];

    /**
     * Accessor for full image asset URL.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) {
            return null;
        }

        if (str_starts_with($this->featured_image, 'http://') || str_starts_with($this->featured_image, 'https://')) {
            return $this->featured_image;
        }

        $path = ltrim(str_replace('storage/', '', $this->featured_image), '/');
        return asset('storage/' . $path);
    }
}
