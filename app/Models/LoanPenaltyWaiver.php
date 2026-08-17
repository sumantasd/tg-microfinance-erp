<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenaltyWaiver extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_account_id',
        'loan_installment_id',
        'waived_amount',
        'waiver_date',
        'waiver_reason',
        'authorized_by',
    ];

    protected $casts = [
        'waiver_date' => 'date',
        'waived_amount' => 'decimal:2',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
    }

    public function loanInstallment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
