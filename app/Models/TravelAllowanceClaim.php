<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelAllowanceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'employee_id',
        'claim_number',
        'travel_date',
        'from_location',
        'to_location',
        'transport_mode',
        'distance_km',
        'verified_distance_km',
        'rate_per_km',
        'amount',
        'purpose',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'paid_at',
        'payment_reference',
        'cash_book_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'distance_km' => 'float',
            'verified_distance_km' => 'float',
            'rate_per_km' => 'float',
            'amount' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
