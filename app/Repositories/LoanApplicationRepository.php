<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\LoanApplication;
use App\Models\LoanApplicationMember;
use App\Models\LoanApplicationProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoanApplicationRepository implements LoanApplicationRepositoryInterface
{
    public function getPaginatedApplications(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = LoanApplication::with(['company', 'branch', 'customer', 'customerGroup', 'loanScheme', 'creator', 'approver']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['loan_type'])) {
            $query->where('loan_type', $filters['loan_type']);
        }

        if (!empty($filters['borrower_type'])) {
            $query->where('borrower_type', $filters['borrower_type']);
        }

        if (!empty($filters['loan_scheme_id'])) {
            $query->where('loan_scheme_id', $filters['loan_scheme_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['customer_group_id'])) {
            $query->where('customer_group_id', $filters['customer_group_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($c) use ($search) {
                      $c->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customerGroup', function ($g) use ($search) {
                      $g->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('application_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?LoanApplication
    {
        return LoanApplication::with([
            'company', 'branch', 'customer', 'customerGroup.members.customer',
            'loanScheme', 'members.customer', 'products.product',
            'creator', 'updater', 'reviewer', 'approver', 'rejecter', 'canceller'
        ])->find($id);
    }

    public function findByApplicationNumber(string $applicationNumber): ?LoanApplication
    {
        return LoanApplication::where('application_number', $applicationNumber)->first();
    }

    public function createApplication(array $masterData, array $membersData = [], array $productsData = []): LoanApplication
    {
        return DB::transaction(function () use ($masterData, $membersData, $productsData) {
            $application = LoanApplication::create($masterData);

            if (!empty($membersData)) {
                foreach ($membersData as $member) {
                    $member['loan_application_id'] = $application->id;
                    LoanApplicationMember::create($member);
                }
            }

            if (!empty($productsData)) {
                foreach ($productsData as $product) {
                    $product['loan_application_id'] = $application->id;
                    LoanApplicationProduct::create($product);
                }
            }

            return $application->fresh(['members.customer', 'products.product']);
        });
    }

    public function updateApplication(LoanApplication $application, array $masterData, array $membersData = [], array $productsData = []): LoanApplication
    {
        return DB::transaction(function () use ($application, $masterData, $membersData, $productsData) {
            $application->update($masterData);

            $application->members()->delete();
            if (!empty($membersData)) {
                foreach ($membersData as $member) {
                    $member['loan_application_id'] = $application->id;
                    LoanApplicationMember::create($member);
                }
            }

            $application->products()->delete();
            if (!empty($productsData)) {
                foreach ($productsData as $product) {
                    $product['loan_application_id'] = $application->id;
                    LoanApplicationProduct::create($product);
                }
            }

            return $application->fresh(['members.customer', 'products.product']);
        });
    }

    public function updateStatus(LoanApplication $application, string $status, array $additionalData = []): LoanApplication
    {
        $additionalData['status'] = $status;
        $application->update($additionalData);
        return $application->fresh(['members.customer', 'products.product']);
    }

    public function generateApplicationNumber(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = $branch->code ?? 'BR001';
            $year = date('Y');

            $lastId = DB::table('loan_applications')
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
            return "LN-APP-{$branchCode}-{$year}-{$nextSeq}";
        });
    }
}
