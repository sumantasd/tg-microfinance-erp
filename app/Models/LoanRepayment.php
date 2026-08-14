<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'loan_account_id',
        'customer_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'penalty_paid',
        'fee_paid',
        'interest_paid',
        'principal_paid',
        'adjustment_mode',
        'received_by',
        'remarks',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'penalty_paid' => 'decimal:2',
        'fee_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'principal_paid' => 'decimal:2',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class, 'loan_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
