<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerGuarantor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'full_name',
        'relationship',
        'mobile',
        'alternate_contact',
        'address',
        'occupation',
        'monthly_income',
        'kyc_type',
        'kyc_number',
        'kyc_document_path',
        'verification_status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
