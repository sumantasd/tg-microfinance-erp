<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    // Status Constants
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';

    // Payment Status Constants
    public const PAYMENT_STATUS_UNPAID = 'UNPAID';
    public const PAYMENT_STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const PAYMENT_STATUS_PAID = 'PAID';

    protected $fillable = [
        'company_id',
        'branch_id',
        'expense_number',
        'expense_date',
        'expense_category_id',
        'supplier_id',
        'payee_name',
        'description',
        'amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'outstanding_amount',
        'status',
        'payment_status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'cancellation_reason',
        'reference_number',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class, 'expense_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class, 'expense_id');
    }

    // Helper display for Payee
    public function getPayeeDisplayNameAttribute(): string
    {
        if ($this->supplier) {
            return $this->supplier->supplier_name . ($this->supplier->company_name ? " ({$this->supplier->company_name})" : '');
        }

        return $this->payee_name ?: 'N/A';
    }

    // Status helpers
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    public function isSubmittable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApprovable(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIALLY_PAID]) 
            && $this->outstanding_amount > 0;
    }

    public function isCancellable(): bool
    {
        return !in_array($this->status, [self::STATUS_CANCELLED]);
    }

    public function isDeletable(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->payments()->count() === 0;
    }
}
