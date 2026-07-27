<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhyChooseUs extends Model
{
    use HasFactory;

    protected $table = 'why_choose_us_items';

    protected $fillable = [
        'title',
        'description',
        'icon',
        'badge_color',
        'status',
        'sort_order',
    ];
}
