<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanScheme extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'loan_type',
        'applicant_type',
        'min_amount',
        'max_amount',
        'interest_type',
        'interest_rate_per_annum',
        'min_tenure_months',
        'max_tenure_months',
        'repayment_frequency',
        'processing_fee_percentage',
        'insurance_fee_percentage',
        'penalty_type',
        'flat_penalty_amount',
        'late_fee_percentage',
        'grace_period_days',
        'max_penalty_amount',
        'max_penalty_percentage',
        'allow_foreclosure',
        'foreclosure_fee_type',
        'foreclosure_fee_percentage',
        'foreclosure_flat_fee',
        'min_months_before_foreclosure',
        'is_active',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'interest_rate_per_annum' => 'decimal:2',
        'processing_fee_percentage' => 'decimal:2',
        'insurance_fee_percentage' => 'decimal:2',
        'flat_penalty_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'grace_period_days' => 'integer',
        'max_penalty_amount' => 'decimal:2',
        'max_penalty_percentage' => 'decimal:2',
        'allow_foreclosure' => 'boolean',
        'foreclosure_fee_percentage' => 'decimal:2',
        'foreclosure_flat_fee' => 'decimal:2',
        'min_months_before_foreclosure' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
