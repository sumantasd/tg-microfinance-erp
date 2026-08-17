<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class VoucherNumberSequence extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'prefix',
        'module',
        'current_number',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'current_number' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Atomically generate next sequential voucher number with lock.
     * Format: {Prefix}-{BranchCode}-{Year}-{SeqNumber}
     */
    public static function generateNextVoucherNumber(int $companyId, int $branchId, string $prefix, string $module = 'accounting'): string
    {
        return DB::transaction(function () use ($companyId, $branchId, $prefix, $module) {
            $seq = self::where('company_id', $companyId)
                ->where('module', $module)
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $seq = self::create([
                    'company_id' => $companyId,
                    'module' => $module,
                    'prefix' => $prefix,
                    'current_number' => 1000,
                ]);
            }

            $seq->increment('current_number');
            $number = str_pad($seq->current_number, 5, '0', STR_PAD_LEFT);

            $branch = Branch::find($branchId);
            $branchCode = $branch ? $branch->code : 'HO';
            $year = date('Y');

            return "{$prefix}-{$branchCode}-{$year}-{$number}";
        });
    }
}
