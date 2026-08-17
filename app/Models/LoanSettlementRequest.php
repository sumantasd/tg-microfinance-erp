<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSettlementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'loan_account_id',
        'request_type',
        'status',
        'as_of_date',
        'principal_outstanding',
        'accrued_interest',
        'unearned_interest_rebate',
        'fee_outstanding',
        'penalty_outstanding',
        'foreclosure_fee',
        'discount_concession_amount',
        'final_settlement_amount',
        'valid_until_date',
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'rejection_reason',
        'repayment_id',
        'voucher_id',
    ];

    protected $casts = [
        'as_of_date' => 'date',
        'valid_until_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'principal_outstanding' => 'decimal:2',
        'accrued_interest' => 'decimal:2',
        'unearned_interest_rebate' => 'decimal:2',
        'fee_outstanding' => 'decimal:2',
        'penalty_outstanding' => 'decimal:2',
        'foreclosure_fee' => 'decimal:2',
        'discount_concession_amount' => 'decimal:2',
        'final_settlement_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function repayment(): BelongsTo
    {
        return $this->belongsTo(LoanRepayment::class, 'repayment_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }
}
