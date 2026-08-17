<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenaltyCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_account_id',
        'loan_installment_id',
        'charge_date',
        'dpd_at_charge',
        'charge_amount',
        'calculation_type',
        'remarks',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'dpd_at_charge' => 'integer',
        'charge_amount' => 'decimal:2',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
    }

    public function loanInstallment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class);
    }
}
