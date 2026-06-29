<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back()->with('status', 'notification-read');
    }

    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back()->with('status', 'notifications-read');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'notifications_count' => $request->user()->notifications()->count(),
            'items' => $notifications->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notificacion interna',
                'message' => $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion.',
                'url' => $notification->data['url'] ?? null,
                'prioridad' => $notification->data['prioridad'] ?? 'baja',
                'read_at' => $notification->read_at?->toIso8601String(),
                'is_read' => !is_null($notification->read_at),
                'created_at' => $notification->created_at?->toIso8601String(),
                'created_at_human' => $notification->created_at?->diffForHumans(),
                'area_pasteurizadora_label' => $notification->data['area_pasteurizadora_label'] ?? null,
            ])->values(),
        ]);
    }
}
