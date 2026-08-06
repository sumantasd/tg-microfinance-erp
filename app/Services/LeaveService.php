<?php

namespace App\Services;

use App\Models\Leave;

use App\Repositories\LeaveRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LeaveService
{
    public function __construct(
        protected LeaveRepositoryInterface $leaveRepo,
        protected ActivityLogService $activityLogger
    ) {}

    public function getPaginatedLeaves(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->leaveRepo->getPaginatedLeaves($filters, $perPage);
    }

    public function getLeaveById(int $id): ?Leave
    {
        return $this->leaveRepo->findById($id);
    }

    public function applyLeave(array $data): Leave
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';
        
        $start = \Carbon\Carbon::parse($data['start_date']);
        $end = \Carbon\Carbon::parse($data['end_date']);
        $data['total_days'] = $start->diffInDays($end) + 1;

        $leave = $this->leaveRepo->createLeave($data);

        $this->activityLogger->log(
            'Apply Leave',
            $leave,
            null,
            ['start_date' => $data['start_date'], 'end_date' => $data['end_date']]
        );

        return $leave;
    }

    public function approveLeave(Leave $leave): bool
    {
        $res = $this->leaveRepo->updateStatus($leave, 'approved', auth()->id());

        $this->activityLogger->log(
            'Approve Leave',
            $leave,
            ['status' => 'pending'],
            ['status' => 'approved']
        );

        return $res;
    }

    public function rejectLeave(Leave $leave, string $reason): bool
    {
        $res = $this->leaveRepo->updateStatus($leave, 'rejected', auth()->id(), $reason);

        $this->activityLogger->log(
            'Reject Leave',
            $leave,
            ['status' => 'pending'],
            ['status' => 'rejected', 'reason' => $reason]
        );

        return $res;
    }
}
