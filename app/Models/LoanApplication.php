<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_number',
        'company_id',
        'branch_id',
        'loan_type',
        'borrower_type',
        'customer_id',
        'customer_group_id',
        'loan_scheme_id',
        'application_date',
        'requested_amount',
        'approved_amount',
        'tenure_months',
        'repayment_frequency',
        'interest_type',
        'interest_rate_per_annum',
        'processing_fee_percentage',
        'processing_fee_amount',
        'insurance_fee_percentage',
        'insurance_fee_amount',
        'late_fee_percentage',
        'grace_period_days',
        'purpose',
        'status',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'created_by',
        'updated_by',
        'reviewed_by',
        'approved_by',
        'rejected_by',
        'cancelled_by',
        'rejection_reason',
        'remarks',
    ];

    protected $casts = [
        'application_date' => 'date',
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'tenure_months' => 'integer',
        'interest_rate_per_annum' => 'decimal:2',
        'processing_fee_percentage' => 'decimal:2',
        'processing_fee_amount' => 'decimal:2',
        'insurance_fee_percentage' => 'decimal:2',
        'insurance_fee_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'grace_period_days' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function loanScheme(): BelongsTo
    {
        return $this->belongsTo(LoanScheme::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(LoanApplicationMember::class, 'loan_application_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(LoanApplicationProduct::class, 'loan_application_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
