<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_number',
        'company_id',
        'branch_id',
        'loan_application_id',
        'customer_id',
        'customer_group_id',
        'loan_scheme_id',
        'loan_type',
        'borrower_type',
        'product_price_amount',
        'down_payment_amount',
        'sanctioned_amount',
        'disbursed_amount',
        'tenure_months',
        'repayment_frequency',
        'interest_type',
        'interest_rate_per_annum',
        'processing_fee_percentage',
        'processing_fee_amount',
        'insurance_fee_percentage',
        'insurance_fee_amount',
        'other_charges_amount',
        'total_interest_amount',
        'total_repayment_amount',
        'principal_outstanding',
        'interest_outstanding',
        'fee_outstanding',
        'penalty_outstanding',
        'total_outstanding',
        'status',
        'sanction_date',
        'disbursement_date',
        'maturity_date',
        'closed_at',
        'closure_type',
        'closure_remarks',
        'closure_approved_by',
        'closure_approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'product_price_amount' => 'decimal:2',
        'down_payment_amount' => 'decimal:2',
        'sanctioned_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'tenure_months' => 'integer',
        'interest_rate_per_annum' => 'decimal:2',
        'processing_fee_percentage' => 'decimal:2',
        'processing_fee_amount' => 'decimal:2',
        'insurance_fee_percentage' => 'decimal:2',
        'insurance_fee_amount' => 'decimal:2',
        'other_charges_amount' => 'decimal:2',
        'total_interest_amount' => 'decimal:2',
        'total_repayment_amount' => 'decimal:2',
        'principal_outstanding' => 'decimal:2',
        'interest_outstanding' => 'decimal:2',
        'fee_outstanding' => 'decimal:2',
        'penalty_outstanding' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
        'sanction_date' => 'date',
        'disbursement_date' => 'date',
        'maturity_date' => 'date',
        'closed_at' => 'datetime',
        'closure_approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
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

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class, 'loan_account_id')->orderBy('installment_number', 'asc');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LoanAccountMember::class, 'loan_account_id');
    }

    public function downPayments(): HasMany
    {
        return $this->hasMany(LoanDownPayment::class, 'loan_account_id');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(LoanDisbursement::class, 'loan_account_id');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class, 'loan_account_id')->orderBy('id', 'desc');
    }

    public function penaltyCharges(): HasMany
    {
        return $this->hasMany(LoanPenaltyCharge::class, 'loan_account_id')->orderBy('charge_date', 'desc');
    }

    public function penaltyWaivers(): HasMany
    {
        return $this->hasMany(LoanPenaltyWaiver::class, 'loan_account_id')->orderBy('waiver_date', 'desc');
    }

    public function settlementRequests(): HasMany
    {
        return $this->hasMany(LoanSettlementRequest::class, 'loan_account_id')->orderBy('id', 'desc');
    }

    public function closureApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closure_approved_by');
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
