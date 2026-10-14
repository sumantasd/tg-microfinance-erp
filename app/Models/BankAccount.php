<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'chart_of_account_id',
        'bank_name',
        'account_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'branch_name',
        'account_nickname',
        'opening_balance',
        'current_balance',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::saving(function ($account) {
            if (empty($account->account_name) && !empty($account->account_holder_name)) {
                $account->account_name = $account->account_holder_name;
            }
            if (empty($account->account_name)) {
                $account->account_name = 'Company Account';
            }
        });
    }

    public function setAccountHolderNameAttribute($value): void
    {
        $this->attributes['account_name'] = $value;
    }

    public function getAccountHolderNameAttribute(): ?string
    {
        return $this->attributes['account_name'] ?? null;
    }

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deposits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankDeposit::class, 'bank_account_id');
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        $num = (string) $this->account_number;
        $len = strlen($num);
        if ($len <= 4) {
            return $num;
        }

        return str_repeat('•', max(0, $len - 4)) . substr($num, -4);
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->account_nickname ?: $this->account_name;
        return "{$this->bank_name} - {$name} ({$this->masked_account_number})";
    }
}
