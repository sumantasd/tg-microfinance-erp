<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashBookCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'code',
        'name',
        'is_system_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
