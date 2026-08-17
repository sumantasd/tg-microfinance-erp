<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'branch_id',
        'financial_year_id',
        'voucher_number',
        'voucher_type',
        'voucher_date',
        'total_debit',
        'total_credit',
        'narration',
        'status',
        'is_reversal',
        'reversed_voucher_id',
        'reversal_reason',
        'reference_type',
        'reference_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'is_reversal' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class, 'voucher_id');
    }

    public function reversedVoucher(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_voucher_id');
    }

    public function reversalVouchers(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_voucher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
