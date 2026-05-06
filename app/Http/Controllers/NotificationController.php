<?php

namespace App\Http\Controllers;

use App\Events\UserInboxUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->isAuditor()) {
            abort(403);
        }

        $items = $user->notifications()
            ->orderByDesc('created_at')
            ->when(! $request->boolean('history'), fn ($q) => $q->whereNull('read_at'))
            ->limit(100)
            ->get()
            ->map(function ($n): array {
                /** @var array{title?: string, body?: string, action_url?: string|null, module?: string} $data */
                $data = $n->data;

                return [
                    'id' => $n->id,
                    'read' => $n->read_at !== null,
                    'title' => $data['title'] ?? '',
                    'body' => $data['body'] ?? '',
                    'url' => $data['action_url'] ?? null,
                    'created_at' => $n->created_at instanceof Carbon ? $n->created_at->toIso8601String() : null,
                ];
            });

        return response()->json([
            'notifications' => $items,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->isAuditor()) {
            abort(403);
        }

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->isAuditor()) {
            abort(403);
        }

        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $count = $user->unreadNotifications()->count();
        broadcast(new UserInboxUpdated((int) $user->id, $count));

        return response()->json([
            'ok' => true,
            'unread_count' => $count,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->isAuditor()) {
            abort(403);
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        broadcast(new UserInboxUpdated((int) $user->id, 0));

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }
}
