<?php

namespace App\Repositories;

use App\Models\LoanScheme;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoanSchemeRepository implements LoanSchemeRepositoryInterface
{
    public function getPaginatedSchemes(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = LoanScheme::with(['company', 'branch']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('branch_id')->orWhere('branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['loan_type'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('loan_type', $filters['loan_type'])->orWhere('loan_type', 'both');
            });
        }

        if (!empty($filters['applicant_type'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('applicant_type', $filters['applicant_type'])->orWhere('applicant_type', 'both');
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?LoanScheme
    {
        return LoanScheme::with(['company', 'branch'])->find($id);
    }

    public function createScheme(array $data): LoanScheme
    {
        return LoanScheme::create($data);
    }

    public function updateScheme(LoanScheme $scheme, array $data): LoanScheme
    {
        $scheme->update($data);
        return $scheme->fresh(['company', 'branch']);
    }

    public function deleteScheme(LoanScheme $scheme): bool
    {
        return (bool) $scheme->delete();
    }

    public function generateSchemeCode(int $companyId): string
    {
        return DB::transaction(function () use ($companyId) {
            $lastId = DB::table('loan_schemes')
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            return "SCH-{$nextSeq}";
        });
    }
}
