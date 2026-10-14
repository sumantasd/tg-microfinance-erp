<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankDeposit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'bank_account_id',
        'deposit_date',
        'amount',
        'bank_name',
        'account_number',
        'reference_number',
        'description',
        'receipt_path',
        'receipt_original_name',
        'receipt_mime_type',
        'receipt_size',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'deposit_date' => 'date',
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'receipt_size' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function hasReceipt(): bool
    {
        return !empty($this->receipt_path);
    }

    public function getMaskedAccountNumberAttribute(): ?string
    {
        $num = $this->account_number ?: ($this->bankAccount ? $this->bankAccount->account_number : null);
        if (!$num) {
            return null;
        }

        $len = strlen($num);
        return $len > 4 ? str_repeat('•', max(0, $len - 4)) . substr($num, -4) : $num;
    }

    public function getReceiptUrlAttribute(): string
    {
        return route('admin.bank-deposits.receipt', $this->id);
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
