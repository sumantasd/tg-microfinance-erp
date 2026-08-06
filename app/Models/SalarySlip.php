<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalarySlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'payroll_id',
        'employee_id',
        'basic_salary',
        'hra',
        'conveyance_allowance',
        'special_allowance',
        'pf_deduction',
        'tax_deduction',
        'other_deduction',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'payment_status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:4',
            'hra' => 'decimal:4',
            'conveyance_allowance' => 'decimal:4',
            'special_allowance' => 'decimal:4',
            'pf_deduction' => 'decimal:4',
            'tax_deduction' => 'decimal:4',
            'other_deduction' => 'decimal:4',
            'gross_salary' => 'decimal:4',
            'total_deductions' => 'decimal:4',
            'net_salary' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($slip) {
            if (empty($slip->uuid)) {
                $slip->uuid = (string) Str::uuid();
            }
        });
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
