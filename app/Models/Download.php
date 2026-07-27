<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Download extends Model
{
    use HasFactory;

    protected $table = 'downloads';

    protected $fillable = [
        'title',
        'description',
        'file',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Accessor for full file URL.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file) {
            return null;
        }

        if (str_starts_with($this->file, 'http://') || str_starts_with($this->file, 'https://')) {
            return $this->file;
        }

        $path = ltrim(str_replace('storage/', '', $this->file), '/');
        return asset('storage/' . $path);
    }

    /**
     * Accessor for file extension.
     */
    public function getFileExtensionAttribute(): string
    {
        if (!$this->file) {
            return 'file';
        }
        return strtoupper(pathinfo($this->file, PATHINFO_EXTENSION) ?: 'PDF');
    }
}
