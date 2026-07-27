<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $table = 'team_members';

    protected $fillable = [
        'name',
        'designation',
        'type',
        'bio',
        'photo',
        'email',
        'social_links',
        'display_order',
        'status',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    /**
     * Accessor for full photo URL.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        $path = ltrim(str_replace('storage/', '', $this->photo), '/');
        return asset('storage/' . $path);
    }
}
