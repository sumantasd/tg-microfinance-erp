<?php

namespace App\Repositories;

use App\Models\LoanScheme;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LoanSchemeRepositoryInterface
{
    public function getPaginatedSchemes(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?LoanScheme;

    public function createScheme(array $data): LoanScheme;

    public function updateScheme(LoanScheme $scheme, array $data): LoanScheme;

    public function deleteScheme(LoanScheme $scheme): bool;

    public function generateSchemeCode(int $companyId): string;
}
