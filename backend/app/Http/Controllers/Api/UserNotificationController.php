<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications->map(function (DatabaseNotification $notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at?->toISOString(),
                    'data' => $notification->data,
                ];
            })->values(),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        if ($notification->notifiable_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }
}
