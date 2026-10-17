<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLoanMargin extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_account_id',
        'company_id',
        'branch_id',
        'cost_price_total',
        'selling_price_total',
        'margin_amount',
        'status',
        'finalized_at',
        'finalized_by',
        'reversed_at',
        'reversed_by',
        'notes',
    ];

    protected $casts = [
        'cost_price_total' => 'decimal:2',
        'selling_price_total' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'finalized_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }
}
