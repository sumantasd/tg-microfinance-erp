<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TravelAllowanceClaim;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TravelAllowanceController extends Controller
{
    public function __construct(protected ActivityLogService $activityLogService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = TravelAllowanceClaim::with(['user', 'employee', 'approver', 'branch']);

        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('branch_id', $user->branch_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('travel_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('travel_date', '<=', $request->input('date_to'));
        }

        $claims = $query->latest('travel_date')->paginate(20)->withQueryString();

        return view('admin.ta_claims.index', [
            'claims' => $claims,
            'filters' => $request->all(),
        ]);
    }

    public function create(): View
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        return view('admin.ta_claims.create', [
            'user' => $user,
            'employee' => $employee,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'travel_date' => 'required|date|before_or_equal:today',
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'transport_mode' => 'required|string|in:bike,car,bus,train,auto,other',
            'distance_km' => 'required|numeric|min:0',
            'rate_per_km' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:1',
            'purpose' => 'required|string',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        $claimNumber = 'TA-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));

        $claim = TravelAllowanceClaim::create([
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'claim_number' => $claimNumber,
            'travel_date' => $request->input('travel_date'),
            'from_location' => $request->input('from_location'),
            'to_location' => $request->input('to_location'),
            'transport_mode' => $request->input('transport_mode'),
            'distance_km' => $request->input('distance_km'),
            'rate_per_km' => $request->input('rate_per_km'),
            'amount' => $request->input('amount'),
            'purpose' => $request->input('purpose'),
            'status' => 'pending',
        ]);

        $this->activityLogService->log('ta_claim_submitted', $claim, null, $claim->toArray());

        return redirect()->route('admin.ta-claims.index')->with('success', 'Travel allowance claim ' . $claimNumber . ' submitted successfully.');
    }

    public function approve(TravelAllowanceClaim $claim)
    {
        $user = Auth::user();

        if ($claim->status !== 'pending') {
            return back()->with('error', 'Only pending claims can be approved.');
        }

        $claim->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->activityLogService->log('ta_claim_approved', $claim, null, $claim->toArray());

        return back()->with('success', 'Travel allowance claim approved.');
    }

    public function reject(Request $request, TravelAllowanceClaim $claim)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        if ($claim->status !== 'pending') {
            return back()->with('error', 'Only pending claims can be rejected.');
        }

        $claim->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $this->activityLogService->log('ta_claim_rejected', $claim, null, $claim->toArray());

        return back()->with('success', 'Travel allowance claim rejected.');
    }

    public function pay(Request $request, TravelAllowanceClaim $claim)
    {
        $request->validate([
            'payment_reference' => 'required|string|max:100',
        ]);

        if ($claim->status !== 'approved') {
            return back()->with('error', 'Only approved claims can be marked as paid.');
        }

        $claim->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $request->input('payment_reference'),
        ]);

        $this->activityLogService->log('ta_claim_paid', $claim, null, $claim->toArray());

        return back()->with('success', 'Travel allowance claim marked as paid.');
    }
}
