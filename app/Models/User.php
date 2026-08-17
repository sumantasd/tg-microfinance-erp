<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'branch_id',
        'employee_id',
        'mobile_number',
        'avatar',
        'signature_path',
        'digital_id_number',
        'status',
        'last_login_at',
        'last_login_ip',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user status is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * Check if user is Company Admin.
     */
    public function isCompanyAdmin(): bool
    {
        return $this->hasRole('Admin') || $this->hasRole('Company Admin');
    }

    /**
     * Relationship to Company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship to Branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship to creator user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to editor user.
     */
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if user can access a specific branch.
     */
    public function canAccessBranch(?int $branchId): bool
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) {
            return true;
        }

        if (!$branchId) {
            return true;
        }

        return $this->branch_id && (int) $this->branch_id === (int) $branchId;
    }

    /**
     * Check if user can access a specific company.
     */
    public function canAccessCompany(?int $companyId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$companyId) {
            return true;
        }

        return $this->company_id && (int) $this->company_id === (int) $companyId;
    }

    /**
     * Derive authoritative server-side branch ID for queries.
     * Never trusts request input for branch-locked staff.
     */
    public function resolveScopedBranchId(?int $requestedBranchId = null): ?int
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) {
            return $requestedBranchId ? (int) $requestedBranchId : null;
        }

        return $this->branch_id ? (int) $this->branch_id : null;
    }

    /**
     * Derive authoritative server-side company ID for queries.
     */
    public function resolveScopedCompanyId(?int $requestedCompanyId = null): int
    {
        if ($this->isSuperAdmin()) {
            return $requestedCompanyId ? (int) $requestedCompanyId : (Company::first()?->id ?? 1);
        }

        return $this->company_id ? (int) $this->company_id : (Company::first()?->id ?? 1);
    }
}
