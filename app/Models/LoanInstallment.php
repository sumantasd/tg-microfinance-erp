<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_account_id',
        'installment_number',
        'due_date',
        'opening_principal',
        'principal_amount',
        'interest_amount',
        'fee_amount',
        'penalty_amount',
        'installment_amount',
        'principal_paid',
        'interest_paid',
        'fee_paid',
        'penalty_paid',
        'total_paid',
        'closing_principal',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'opening_principal' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'fee_paid' => 'decimal:2',
        'penalty_paid' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'closing_principal' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class, 'loan_account_id');
    }
}
