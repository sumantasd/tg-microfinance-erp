<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = ActivityLog::with(['user', 'branch', 'company']);

        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('branch_id', $user->branch_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $activityLogs = $query->latest('created_at')->paginate(25)->withQueryString();
        $users = User::where('status', 'active')->orderBy('name')->get();
        $events = ActivityLog::distinct()->pluck('event')->filter()->values();

        return view('admin.system.activity_logs.index', [
            'activityLogs' => $activityLogs,
            'users' => $users,
            'events' => $events,
            'filters' => $request->all(),
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $activityLog->company_id !== $user->company_id) {
            abort(403, 'Unauthorized activity log access.');
        }

        return view('admin.system.activity_logs.show', [
            'activityLog' => $activityLog,
        ]);
    }
}
