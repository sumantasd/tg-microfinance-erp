<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'group_code',
        'name',
        'leader_customer_id',
        'meeting_day',
        'meeting_time',
        'meeting_location',
        'formation_date',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'formation_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'leader_customer_id');
    }

    public function groupMembers(): HasMany
    {
        return $this->hasMany(CustomerGroupMember::class, 'group_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CustomerGroupMember::class, 'group_id');
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(CustomerGroupMember::class, 'group_id')->where('status', 'active');
    }

    public function loanAccounts(): HasMany
    {
        return $this->hasMany(LoanAccount::class, 'customer_group_id');
    }

    public function getTotalDisbursedAttribute(): float
    {
        return round((float) $this->loanAccounts()->whereIn('status', ['active', 'closed', 'defaulted'])->sum('disbursed_amount'), 2);
    }

    public function getTotalOutstandingAttribute(): float
    {
        return round((float) $this->loanAccounts()->whereIn('status', ['active', 'defaulted', 'ready_for_disbursement', 'sanctioned'])->sum('total_outstanding'), 2);
    }

    public function getTotalPrincipalOutstandingAttribute(): float
    {
        return round((float) $this->loanAccounts()->whereIn('status', ['active', 'defaulted', 'ready_for_disbursement', 'sanctioned'])->sum('principal_outstanding'), 2);
    }

    public function getTotalRepaidAttribute(): float
    {
        return round((float) $this->loanAccounts()->get()->sum(function ($acc) {
            return max(0, (float) $acc->disbursed_amount - (float) $acc->principal_outstanding);
        }), 2);
    }

    public function getTotalOverdueAttribute(): float
    {
        return round((float) $this->loanAccounts()->whereIn('status', ['active', 'defaulted'])->get()->sum(function ($acc) {
            return (float) $acc->overdue_amount;
        }), 2);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
