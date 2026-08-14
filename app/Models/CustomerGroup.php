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
