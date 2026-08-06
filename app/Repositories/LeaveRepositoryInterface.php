<?php

namespace App\Repositories;

use App\Models\Leave;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LeaveRepositoryInterface
{
    public function getPaginatedLeaves(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Leave;

    public function createLeave(array $data): Leave;

    public function updateStatus(Leave $leave, string $status, ?int $approvedBy = null, ?string $rejectionReason = null): bool;
}
