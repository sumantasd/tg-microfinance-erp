<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\SalarySlip;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrmApiController extends Controller
{
    use ApiResponse;

    /**
     * List active leave types.
     */
    public function leaveTypes(): JsonResponse
    {
        $types = LeaveType::where('is_active', true)->orderBy('name')->get();
        return $this->successResponse($types, 'Leave types retrieved successfully');
    }

    /**
     * List leave requests with role & branch isolation.
     */
    public function leaves(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Leave::with(['employee', 'leaveType', 'approver']);

        // Data scoping
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('Branch Manager')) {
                $query->where('branch_id', $user->branch_id);
            } elseif ($user->hasPermissionTo('hrm.leave.view')) {
                $query->where('company_id', $user->company_id);
            } else {
                // Employee can view only own leave requests
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $query->where('employee_id', $employee->id);
                } else {
                    $query->where('created_by', $user->id);
                }
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leaves = $query->latest()->paginate($request->input('per_page', 15));

        return $this->successResponse($leaves, 'Leave requests retrieved successfully');
    }

    /**
     * View leave request details.
     */
    public function showLeave(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $leave = Leave::with(['employee', 'leaveType', 'approver', 'company', 'branch'])->find($id);

        if (!$leave) {
            return $this->errorResponse('Leave request not found', 404);
        }

        // Branch and ownership checks
        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin() && $leave->company_id !== $user->company_id) {
                return $this->errorResponse('Unauthorized access to leave record', 403);
            }
            if ($user->hasRole('Branch Manager') && $leave->branch_id !== $user->branch_id) {
                return $this->errorResponse('Unauthorized access to leave record outside your branch', 403);
            }
        }

        return $this->successResponse($leave, 'Leave request details retrieved');
    }

    /**
     * Submit new leave request.
     */
    public function storeLeave(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        $employee = Employee::where('user_id', $user->id)->first();

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $totalDays = $start->diffInDays($end) + 1;

        $leave = Leave::create([
            'company_id' => $user->company_id ?? 1,
            'branch_id' => $user->branch_id ?? 1,
            'employee_id' => $employee?->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return $this->successResponse($leave->load(['employee', 'leaveType']), 'Leave request submitted successfully', 201);
    }

    /**
     * Approve leave request.
     */
    public function approveLeave(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $leave = Leave::find($id);

        if (!$leave) {
            return $this->errorResponse('Leave request not found', 404);
        }

        if ($leave->status !== 'pending') {
            return $this->errorResponse('Only pending leave requests can be approved', 422);
        }

        if (!$user->isSuperAdmin() && $user->branch_id && $leave->branch_id !== $user->branch_id) {
            return $this->errorResponse('Unauthorized to approve leave for another branch', 403);
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $this->successResponse($leave->fresh(['employee', 'leaveType', 'approver']), 'Leave request approved successfully');
    }

    /**
     * Reject leave request.
     */
    public function rejectLeave(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leave = Leave::find($id);

        if (!$leave) {
            return $this->errorResponse('Leave request not found', 404);
        }

        if ($leave->status !== 'pending') {
            return $this->errorResponse('Only pending leave requests can be rejected', 422);
        }

        if (!$user->isSuperAdmin() && $user->branch_id && $leave->branch_id !== $user->branch_id) {
            return $this->errorResponse('Unauthorized to reject leave for another branch', 403);
        }

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return $this->successResponse($leave->fresh(['employee', 'leaveType', 'approver']), 'Leave request rejected');
    }

    /**
     * List employee payslips.
     */
    public function payslips(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        $query = SalarySlip::with(['payroll', 'employee']);

        if (!$user->isSuperAdmin() && !$user->hasPermissionTo('payroll.view')) {
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                return $this->successResponse([], 'No employee profile associated with logged in user');
            }
        } elseif (!$user->isSuperAdmin() && $user->branch_id) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        $payslips = $query->latest()->paginate($request->input('per_page', 15));

        return $this->successResponse($payslips, 'Payslips retrieved successfully');
    }

    /**
     * View salary slip details.
     */
    public function showPayslip(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $slip = SalarySlip::with(['payroll', 'employee.department', 'employee.designation', 'employee.branch'])->find($id);

        if (!$slip) {
            return $this->errorResponse('Salary slip not found', 404);
        }

        $employee = Employee::where('user_id', $user->id)->first();

        if (!$user->isSuperAdmin() && !$user->hasPermissionTo('payroll.view')) {
            if (!$employee || $slip->employee_id !== $employee->id) {
                return $this->errorResponse('Unauthorized access to salary slip', 403);
            }
        }

        return $this->successResponse($slip, 'Salary slip details retrieved');
    }
}
