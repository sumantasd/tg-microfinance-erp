<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'branch_id',
        'month',
        'year',
        'total_employees',
        'total_gross',
        'total_deductions',
        'total_net_payout',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_gross' => 'decimal:4',
            'total_deductions' => 'decimal:4',
            'total_net_payout' => 'decimal:4',
            'processed_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payroll) {
            if (empty($payroll->uuid)) {
                $payroll->uuid = (string) Str::uuid();
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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function salarySlips(): HasMany
    {
        return $this->hasMany(SalarySlip::class);
    }
}
