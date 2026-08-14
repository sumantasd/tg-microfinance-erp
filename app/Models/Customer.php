<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_code',
        'member_number',
        'customer_type',
        'status',
        'profile_photo_path',
        'first_name',
        'middle_name',
        'last_name',
        'father_husband_guardian_name',
        'mobile_number',
        'alternate_contact',
        'email',
        'dob',
        'gender',
        'marital_status',
        'occupation',
        'monthly_income',
        'registration_date',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'registration_date' => 'date',
        'monthly_income' => 'decimal:2',
    ];

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function presentAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('address_type', 'present');
    }

    public function permanentAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('address_type', 'permanent');
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(CustomerKycDocument::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(CustomerGuarantor::class);
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(CustomerNominee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
