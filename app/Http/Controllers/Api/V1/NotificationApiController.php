<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    use ApiResponse;

    /**
     * List user notifications with optional unread filter and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = UserNotification::with(['notification.sender', 'notification.branch'])
            ->where('user_id', $user->id);

        if ($request->input('filter') === 'unread' || $request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        $notifications = $query->latest()->paginate($request->input('per_page', 15));
        $unreadCount = UserNotification::where('user_id', $user->id)->where('is_read', false)->count();

        return $this->successResponse([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ], 'Notifications retrieved successfully');
    }

    /**
     * Get unread notification count for user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadCount = UserNotification::where('user_id', $user->id)->where('is_read', false)->count();

        return $this->successResponse([
            'unread_count' => $unreadCount,
        ], 'Unread notification count retrieved');
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $notification = UserNotification::where('user_id', $user->id)->find($id);

        if (!$notification) {
            return $this->errorResponse('Notification not found', 404);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $this->successResponse($notification, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $updatedCount = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->successResponse([
            'marked_as_read_count' => $updatedCount,
        ], 'All notifications marked as read');
    }

    /**
     * Delete notification.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $notification = UserNotification::where('user_id', $user->id)->find($id);

        if (!$notification) {
            return $this->errorResponse('Notification not found', 404);
        }

        $notification->delete();

        return $this->successResponse(null, 'Notification deleted successfully');
    }
}
