<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function getPaginatedCustomers(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Customer::with(['company', 'branch', 'presentAddress', 'kycDocuments', 'guarantors', 'nominees', 'activeGroupMembership.group']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                  ->orWhere('member_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['group_id'])) {
            $groupId = $filters['group_id'];
            $query->whereHas('groupMemberships', function ($q) use ($groupId) {
                $q->where('group_id', $groupId)->where('status', 'active');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
        }

        if (!empty($filters['kyc_status'])) {
            $kycStatus = $filters['kyc_status'];
            $query->whereHas('kycDocuments', function ($q) use ($kycStatus) {
                $q->where('verification_status', $kycStatus);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('registration_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('registration_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['trashed']) && $filters['trashed'] === 'only') {
            $query->onlyTrashed();
        }

        $sortField = $filters['sort_field'] ?? 'id';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSorts = ['id', 'customer_code', 'first_name', 'registration_date', 'status', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Customer
    {
        return Customer::with([
            'company',
            'branch',
            'addresses',
            'presentAddress',
            'permanentAddress',
            'kycDocuments.verifier',
            'guarantors',
            'nominees',
            'activeGroupMembership.group',
            'creator',
            'updater',
        ])->find($id);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function delete(Customer $customer, int $userId): bool
    {
        $customer->deleted_by = $userId;
        $customer->save();
        return (bool) $customer->delete();
    }

    public function restore(Customer $customer): bool
    {
        return (bool) $customer->restore();
    }

    public function changeStatus(Customer $customer, string $status, int $userId): bool
    {
        $customer->status = $status;
        $customer->updated_by = $userId;
        return $customer->save();
    }

    public function generateCustomerCode(int $companyId, int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::find($branchId);
            $branchCode = $branch ? strtoupper($branch->code ?? 'BR' . str_pad($branchId, 2, '0', STR_PAD_LEFT)) : 'BR' . str_pad($branchId, 2, '0', STR_PAD_LEFT);
            $year = date('Y');

            // Count existing records for this branch to form unique 5-digit sequence
            $count = Customer::where('branch_id', $branchId)->withTrashed()->count() + 1;
            $sequenceStr = str_pad($count, 5, '0', STR_PAD_LEFT);

            $code = "CUST-{$branchCode}-{$year}-{$sequenceStr}";

            // Guarantee uniqueness in case of deleted/seeded code overlap
            while (Customer::where('customer_code', $code)->withTrashed()->exists()) {
                $count++;
                $sequenceStr = str_pad($count, 5, '0', STR_PAD_LEFT);
                $code = "CUST-{$branchCode}-{$year}-{$sequenceStr}";
            }

            return $code;
        });
    }
}
