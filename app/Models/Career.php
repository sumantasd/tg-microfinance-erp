<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    use HasFactory;

    protected $table = 'careers';

    protected $fillable = [
        'title',
        'slug',
        'location',
        'job_type',
        'short_description',
        'requirements',
        'application_email',
        'deadline',
        'apply_button_text',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];
}
