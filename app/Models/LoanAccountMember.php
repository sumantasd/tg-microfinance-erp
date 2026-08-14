<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanAccountMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_account_id',
        'customer_id',
        'sanctioned_amount',
        'down_payment_amount',
        'principal_outstanding',
        'interest_outstanding',
        'total_outstanding',
    ];

    protected $casts = [
        'sanctioned_amount' => 'decimal:2',
        'down_payment_amount' => 'decimal:2',
        'principal_outstanding' => 'decimal:2',
        'interest_outstanding' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class, 'loan_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
