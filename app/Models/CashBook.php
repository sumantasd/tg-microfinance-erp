<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'date',
        'responsible_staff_id',
        'opening_balance',
        'total_cash_received',
        'total_product_received',
        'total_bank_received',
        'total_cash_payment',
        'total_product_payment',
        'total_bank_payment',
        'closing_cash',
        'physical_cash',
        'cash_difference',
        'reconciled_status',
        'status',
        'closed_at',
        'closed_by',
        'approved_at',
        'approved_by',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'total_cash_received' => 'decimal:2',
        'total_product_received' => 'decimal:2',
        'total_bank_received' => 'decimal:2',
        'total_cash_payment' => 'decimal:2',
        'total_product_payment' => 'decimal:2',
        'total_bank_payment' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'physical_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function responsibleStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_staff_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CashBookEntry::class)->orderBy('sort_order')->orderBy('id');
    }

    public function receivedEntries(): HasMany
    {
        return $this->hasMany(CashBookEntry::class)->where('entry_type', 'received')->orderBy('sort_order')->orderBy('id');
    }

    public function paymentEntries(): HasMany
    {
        return $this->hasMany(CashBookEntry::class)->where('entry_type', 'payment')->orderBy('sort_order')->orderBy('id');
    }

    public function onlineCollections(): HasMany
    {
        return $this->hasMany(CashBookOnlineCollection::class)->orderBy('id');
    }

    public function denominations(): HasMany
    {
        return $this->hasMany(CashBookDenomination::class)->orderByDesc('denomination');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(CashBookAudit::class)->orderByDesc('created_at');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed' || $this->status === 'approved';
    }
}
