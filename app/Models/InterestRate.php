<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterestRate extends Model
{
    use HasFactory;

    protected $table = 'interest_rates';

    protected $fillable = [
        'product_name',
        'product_type',
        'amount_range',
        'tenure_options',
        'interest_rate',
        'interest_method',
        'processing_fee',
        'description',
        'status',
        'sort_order',
    ];
}
