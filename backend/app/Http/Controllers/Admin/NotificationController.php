<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated admin user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Filter for reservation notifications only
        $notifications = $user->notifications()
            ->where('type', 'App\\Notifications\\NewReservationNotification')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $data,
                    'read_at' => $notification->read_at ? $notification->read_at->toIso8601String() : null,
                    'created_at' => $notification->created_at->toIso8601String(),
                    'created_at_human' => $notification->created_at->diffForHumans(),
                ];
            });

        $unreadCount = $user->unreadNotifications()
            ->where('type', 'App\\Notifications\\NewReservationNotification')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    /**
     * Get unread count only (for polling).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadCount = $user->unreadNotifications()
            ->where('type', 'App\\Notifications\\NewReservationNotification')
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }
}
