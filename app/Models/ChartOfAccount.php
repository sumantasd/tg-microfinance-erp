<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_code',
        'account_name',
        'account_type',
        'account_group',
        'parent_id',
        'description',
        'is_system',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function voucherEntries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class, 'account_id');
    }

    public function bankAccount(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'chart_of_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Compute real-time balance from posted voucher entries.
     */
    public function getBalance(?int $branchId = null, ?string $asOfDate = null): float
    {
        $query = $this->voucherEntries()
            ->whereHas('voucher', function ($q) use ($branchId, $asOfDate) {
                $q->where('status', 'posted');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                if ($asOfDate) {
                    $q->where('voucher_date', '<=', $asOfDate);
                }
            });

        $totalDebit = (float) (clone $query)->sum('debit');
        $totalCredit = (float) (clone $query)->sum('credit');

        // Assets and Expenses have normal Debit balance (Debit - Credit)
        // Liabilities, Equity, Revenue have normal Credit balance (Credit - Debit)
        if (in_array($this->account_type, ['asset', 'expense'])) {
            return round($totalDebit - $totalCredit, 2);
        } else {
            return round($totalCredit - $totalDebit, 2);
        }
    }
}
