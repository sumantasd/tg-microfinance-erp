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

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $date = $request->input('date', now()->toDateString());
        $statusFilter = $request->input('status', 'all');

        // Field Staff Users query with branch scope
        $staffQuery = User::with(['branch', 'roles', 'employee'])->where('status', 'active');
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
            $lastPing = $statusInfo['last_ping'];

            return [
                'user' => $staff,
                'user_id' => $staff->id,
                'staff_name' => $staff->name,
                'employee_code' => $staff->employee?->employee_code ?? ('EMP-' . sprintf('%04d', $staff->id)),
                'role' => $staff->roles->first()?->name ?? 'Field Staff',
                'branch_id' => $staff->branch_id,
                'branch_name' => $staff->branch->name ?? 'Head Office',
                'status' => $statusInfo['status'],
                'status_label' => $statusInfo['status_label'],
                'badge_class' => $statusInfo['badge_class'],
                'last_ping' => $lastPing,
                'last_ping_formatted' => $lastPing ? [
                    'latitude' => (float) $lastPing->latitude,
                    'longitude' => (float) $lastPing->longitude,
                    'recorded_at' => $lastPing->recorded_at->format('Y-m-d H:i:s'),
                    'time_formatted' => $lastPing->recorded_at->format('h:i A'),
                    'diff_human' => $lastPing->recorded_at->diffForHumans(),
                    'location_address' => $lastPing->location_address ?: 'GPS Location Available',
                    'battery_level' => $lastPing->battery_level,
                    'event_type' => $lastPing->event_type,
                ] : null,
                'battery_level' => $statusInfo['battery_level'] ?? null,
                'verified_distance_km' => $verifiedDistanceKm,
                'ping_count' => $pingCount,
                'has_location' => $lastPing && $lastPing->latitude && $lastPing->longitude,
            ];
        });

        // Compute summary metrics before applying status filter
        $totalStaffCount = $staffTrackingSummary->count();
        $onlineCount = $staffTrackingSummary->where('status', 'online')->count();
        $staleCount = $staffTrackingSummary->where('status', 'stale')->count();
        $offlineCount = $staffTrackingSummary->where('status', 'offline')->count();
        $trackedCount = $staffTrackingSummary->where('has_location', true)->count();
        $totalKm = round($staffTrackingSummary->sum('verified_distance_km'), 2);

        // Apply status filter if provided
        if ($statusFilter && $statusFilter !== 'all') {
            $filteredSummary = $staffTrackingSummary->filter(function ($item) use ($statusFilter) {
                return $item['status'] === $statusFilter;
            })->values();
        } else {
            $filteredSummary = $staffTrackingSummary->values();
        }

        $summaryMetrics = [
            'total_staff' => $totalStaffCount,
            'online_count' => $onlineCount,
            'stale_count' => $staleCount,
            'offline_count' => $offlineCount,
            'tracked_count' => $trackedCount,
            'total_km' => $totalKm,
        ];

        // Return JSON for AJAX requests / Auto Refresh Polling
        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            return response()->json([
                'success' => true,
                'selected_date' => $date,
                'summary' => $summaryMetrics,
                'staff' => $filteredSummary,
            ]);
        }

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
        $fieldStaffUsers = User::where('status', 'active');
        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $fieldStaffUsers->where('company_id', $user->company_id);
            } else {
                $fieldStaffUsers->where('branch_id', $user->branch_id);
            }
        }
        $fieldStaffUsers = $fieldStaffUsers->orderBy('name')->get();

        return view('admin.field_tracking.index', [
            'staffTrackingSummary' => $filteredSummary,
            'summaryMetrics' => $summaryMetrics,
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
