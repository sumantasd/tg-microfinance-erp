<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class NotificationController extends Controller
{
    public function __construct(protected ActivityLogService $activityLogService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = UserNotification::with(['notification.sender', 'notification.branch'])
                    ->where('user_id', $user->id);

        if ($request->input('filter') === 'unread') {
            $query->where('is_read', false);
        }

        $userNotifications = $query->latest()->paginate(20)->withQueryString();
        $unreadCount = UserNotification::where('user_id', $user->id)->where('is_read', false)->count();

        return view('admin.notifications.index', [
            'userNotifications' => $userNotifications,
            'unreadCount' => $unreadCount,
            'filter' => $request->input('filter', 'all'),
        ]);
    }

    public function manage(Request $request): View
    {
        $user = Auth::user();
        $query = SystemNotification::with(['sender', 'branch', 'recipients']);

        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('branch_id', $user->branch_id);
            }
        }

        $systemNotifications = $query->latest()->paginate(15)->withQueryString();
        $branches = $user->isSuperAdmin() ? Branch::where('is_active', true)->get() : Branch::where('company_id', $user->company_id)->get();
        $roles = Role::pluck('name');

        return view('admin.notifications.manage', [
            'systemNotifications' => $systemNotifications,
            'branches' => $branches,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,urgent,announcement',
            'target_branch_id' => 'nullable|exists:branches,id',
            'target_role' => 'nullable|string',
            'action_url' => 'nullable|url|max:255',
        ]);

        $sender = Auth::user();
        $branchId = $request->input('target_branch_id') ?: $sender->branch_id;

        $sysNotif = SystemNotification::create([
            'company_id' => $sender->company_id,
            'branch_id' => $branchId,
            'sender_id' => $sender->id,
            'target_role' => $request->input('target_role'),
            'title' => $request->input('title'),
            'message' => $request->input('message'),
            'type' => $request->input('type'),
            'action_url' => $request->input('action_url'),
        ]);

        // Target users query
        $userQuery = User::where('status', 'active');
        if ($sender->company_id && !$sender->isSuperAdmin()) {
            $userQuery->where('company_id', $sender->company_id);
        }
        if ($branchId) {
            $userQuery->where('branch_id', $branchId);
        }

        $targetUsers = $userQuery->get();
        if ($request->filled('target_role')) {
            $roleName = $request->input('target_role');
            $targetUsers = $targetUsers->filter(fn($u) => $u->hasRole($roleName));
        }

        foreach ($targetUsers as $targetUser) {
            UserNotification::create([
                'system_notification_id' => $sysNotif->id,
                'user_id' => $targetUser->id,
                'is_read' => false,
            ]);
        }

        $this->activityLogService->log('notification_sent', $sysNotif, null, $sysNotif->toArray());

        return redirect()->route('admin.notifications.manage')->with('success', 'Notification sent to ' . $targetUsers->count() . ' user(s).');
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        $userNotif = UserNotification::where('user_id', $user->id)->findOrFail($id);
        $userNotif->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        UserNotification::where('user_id', $user->id)->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $userNotif = UserNotification::where('user_id', $user->id)->findOrFail($id);
        $userNotif->delete();

        return back()->with('success', 'Notification deleted.');
    }
}
