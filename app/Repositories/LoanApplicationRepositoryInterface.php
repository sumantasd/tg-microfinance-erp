<?php

namespace App\Repositories;

use App\Models\LoanApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LoanApplicationRepositoryInterface
{
    public function getPaginatedApplications(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?LoanApplication;

    public function findByApplicationNumber(string $applicationNumber): ?LoanApplication;

    public function createApplication(array $masterData, array $membersData = [], array $productsData = []): LoanApplication;

    public function updateApplication(LoanApplication $application, array $masterData, array $membersData = [], array $productsData = []): LoanApplication;

    public function updateStatus(LoanApplication $application, string $status, array $additionalData = []): LoanApplication;

    public function generateApplicationNumber(int $branchId): string;
}
