<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\CompanyRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CompanyService
{
    public function __construct(
        protected CompanyRepositoryInterface $companyRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function createCompany(array $data): Company
    {
        $userId = Auth::id();
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        $company = $this->companyRepository->create($data);

        $this->activityLogService->log('created', $company, null, $company->toArray());

        return $company;
    }

    public function updateCompany(Company $company, array $data): Company
    {
        $oldValues = $company->toArray();

        $data['updated_by'] = Auth::id();
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : $company->is_active;

        $updatedCompany = $this->companyRepository->update($company, $data);

        $this->activityLogService->log('updated', $updatedCompany, $oldValues, $updatedCompany->toArray());

        return $updatedCompany;
    }

    public function toggleCompanyStatus(Company $company, bool $isActive): bool
    {
        $oldValues = ['is_active' => $company->is_active];
        $result = $this->companyRepository->toggleStatus($company, $isActive, Auth::id());

        $this->activityLogService->log('toggle_status', $company, $oldValues, ['is_active' => $isActive]);

        return $result;
    }

    public function deleteCompany(Company $company): bool
    {
        $oldValues = $company->toArray();
        $result = $this->companyRepository->delete($company, Auth::id());

        $this->activityLogService->log('deleted', $company, $oldValues, null);

        return $result;
    }

    public function restoreCompany(Company $company): bool
    {
        $result = $this->companyRepository->restore($company);

        $this->activityLogService->log('restored', $company, null, $company->toArray());

        return $result;
    }
}
