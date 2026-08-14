<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDisbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_account_id',
        'disbursement_number',
        'disbursement_date',
        'disbursed_amount',
        'payment_method',
        'reference_number',
        'disbursed_by',
        'remarks',
    ];

    protected $casts = [
        'disbursed_amount' => 'decimal:2',
        'disbursement_date' => 'date',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class, 'loan_account_id');
    }

    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }
}
