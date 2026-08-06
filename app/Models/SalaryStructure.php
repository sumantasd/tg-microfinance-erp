<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'basic_salary',
        'hra',
        'conveyance_allowance',
        'special_allowance',
        'pf_deduction',
        'tax_deduction',
        'other_deduction',
        'gross_salary',
        'net_salary',
        'created_by',
        'updated_by',
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
            'net_salary' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
