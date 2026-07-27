<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsSavingsProduct extends Model
{
    use HasFactory;

    protected $table = 'cms_savings_products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'interest_rate',
        'min_balance',
        'tenure',
        'features',
        'image',
        'icon',
        'badge_color',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Accessor for full image asset URL.
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
