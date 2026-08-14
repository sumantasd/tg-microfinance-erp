<?php

namespace App\Services;

use App\Models\LoanScheme;
use App\Repositories\LoanSchemeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanSchemeService
{
    public function __construct(
        protected LoanSchemeRepositoryInterface $schemeRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedSchemes(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->schemeRepository->getPaginatedSchemes($filters, $perPage);
    }

    public function getSchemeById(int $id): ?LoanScheme
    {
        return $this->schemeRepository->findById($id);
    }

    public function createScheme(array $data): LoanScheme
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            if ($user && !$user->isSuperAdmin()) {
                $data['company_id'] = $user->company_id;
            }

            if (empty($data['code'])) {
                $data['code'] = $this->schemeRepository->generateSchemeCode($data['company_id']);
            }

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

            $scheme = $this->schemeRepository->createScheme($data);
            $this->activityLogService->log('loan_scheme_created', $scheme);

            return $scheme;
        });
    }

    public function updateScheme(LoanScheme $scheme, array $data): LoanScheme
    {
        return DB::transaction(function () use ($scheme, $data) {
            $data['updated_by'] = Auth::id();
            if (isset($data['is_active'])) {
                $data['is_active'] = (bool) $data['is_active'];
            }

            $updatedScheme = $this->schemeRepository->updateScheme($scheme, $data);
            $this->activityLogService->log('loan_scheme_updated', $updatedScheme);

            return $updatedScheme;
        });
    }

    public function deleteScheme(LoanScheme $scheme): bool
    {
        return DB::transaction(function () use ($scheme) {
            $this->activityLogService->log('loan_scheme_deleted', $scheme);
            return $this->schemeRepository->deleteScheme($scheme);
        });
    }
}
