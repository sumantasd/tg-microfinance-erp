<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanUpfrontPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'loan_account_id',
        'customer_id',
        'amount',
        'processing_fee_paid',
        'insurance_fee_paid',
        'payment_date',
        'payment_method',
        'reference_number',
        'received_by',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processing_fee_paid' => 'decimal:2',
        'insurance_fee_paid' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
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
