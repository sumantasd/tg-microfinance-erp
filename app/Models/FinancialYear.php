<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'start_date',
        'end_date',
        'is_closed',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
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
     * Find active financial year for a given company and date.
     */
    public static function forDate(int $companyId, string|Carbon $date): ?self
    {
        $d = Carbon::parse($date)->toDateString();
        return self::where('company_id', $companyId)
            ->where('start_date', '<=', $d)
            ->where('end_date', '>=', $d)
            ->first();
    }
}
