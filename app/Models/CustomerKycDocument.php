<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerKycDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'kyc_document_type',
        'document_number',
        'file_path',
        'file_name',
        'file_size_kb',
        'issue_date',
        'expiry_date',
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
