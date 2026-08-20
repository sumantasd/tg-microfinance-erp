<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_code',
        'supplier_type',
        'supplier_name',
        'company_name',
        'contact_person',
        'mobile',
        'alternate_mobile',
        'email',
        'gstin',
        'pan',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'opening_balance',
        'opening_balance_type',
        'credit_limit',
        'payment_terms',
        'bank_name',
        'account_number',
        'ifsc_code',
        'branch_name',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ProductPurchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTotalPurchaseAttribute(): float
    {
        return (float) $this->purchases()
            ->whereIn('purchase_status', ['confirmed', 'received'])
            ->sum('grand_total');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getOpeningBalancePayableAttribute(): float
    {
        $bal = (float) $this->opening_balance;
        return $this->opening_balance_type === 'receivable' ? -$bal : $bal;
    }

    public function getOutstandingPayableAttribute(): float
    {
        return $this->opening_balance_payable + $this->total_purchase - $this->total_paid;
    }

    public function getPurchaseCountAttribute(): int
    {
        return $this->purchases()->count();
    }
}
