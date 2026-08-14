<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerNominee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'nominee_name',
        'relationship',
        'dob',
        'gender',
        'mobile',
        'address',
        'share_percentage',
        'is_minor',
        'guardian_name',
        'guardian_relationship',
        'guardian_contact',
        'guardian_address',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'share_percentage' => 'decimal:2',
        'is_minor' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
