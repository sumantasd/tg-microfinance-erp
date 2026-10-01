<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FieldLocationLog;
use App\Models\FieldVisit;
use App\Models\User;
use App\Services\LocationTrackingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FieldTrackingController extends Controller
{
    public function __construct(protected LocationTrackingService $locationTrackingService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $date = $request->input('date', now()->toDateString());

        // Field Staff Users query with branch scope
        $staffQuery = User::where('status', 'active');
        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $staffQuery->where('company_id', $user->company_id);
            } else {
                $staffQuery->where('branch_id', $user->branch_id);
            }
        }
        if ($request->filled('branch_id')) {
            $staffQuery->where('branch_id', $request->input('branch_id'));
        }
        if ($request->filled('user_id')) {
            $staffQuery->where('id', $request->input('user_id'));
        }

        $staffMembers = $staffQuery->orderBy('name')->get();

        // Calculate status & verified distance per staff member for the selected date
        $staffTrackingSummary = $staffMembers->map(function ($staff) use ($date) {
            $statusInfo = $this->locationTrackingService->getStaffStatus($staff);
            $verifiedDistanceKm = $this->locationTrackingService->calculateVerifiedDistanceKm($staff->id, $date);
            $pingCount = FieldLocationLog::where('user_id', $staff->id)->whereDate('recorded_at', $date)->count();

            return [
                'user' => $staff,
                'status' => $statusInfo['status'],
                'status_label' => $statusInfo['status_label'],
                'badge_class' => $statusInfo['badge_class'],
                'last_ping' => $statusInfo['last_ping'],
                'battery_level' => $statusInfo['battery_level'] ?? null,
                'verified_distance_km' => $verifiedDistanceKm,
                'ping_count' => $pingCount,
            ];
        });

        // Field Visits Logs query
        $visitQuery = FieldVisit::with(['user', 'employee', 'customer', 'branch']);
        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $visitQuery->where('company_id', $user->company_id);
            } else {
                $visitQuery->where('branch_id', $user->branch_id);
            }
        }
        if ($request->filled('branch_id')) {
            $visitQuery->where('branch_id', $request->input('branch_id'));
        }
        if ($request->filled('user_id')) {
            $visitQuery->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('date')) {
            $visitQuery->whereDate('recorded_at', $date);
        }

        $fieldVisits = $visitQuery->latest('recorded_at')->paginate(15)->withQueryString();

        $branches = $user->isSuperAdmin() ? Branch::where('is_active', true)->get() : Branch::where('company_id', $user->company_id)->get();
        $fieldStaffUsers = User::where('status', 'active')->orderBy('name')->get();

        return view('admin.field_tracking.index', [
            'staffTrackingSummary' => $staffTrackingSummary,
            'fieldVisits' => $fieldVisits,
            'branches' => $branches,
            'fieldStaffUsers' => $fieldStaffUsers,
            'selectedDate' => $date,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Return JSON route history for map playback & polyline.
     */
    public function routeHistory(Request $request, int $userId): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $routeData = $this->locationTrackingService->getDailyRoute($userId, $date);

        return response()->json($routeData);
    }
}
