<?php

namespace App\Http\Controllers;

use App\Services\NotificationRedirectService;
use App\Services\NotificationVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationVisibilityService $notificationVisibilityService,
        private readonly NotificationRedirectService $notificationRedirectService
    ) {
    }

    public function index(Request $request): View
    {
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $availableNotifications = $this->notificationVisibilityService
            ->availableNotificationsFor($request->user());
        $notifications = new LengthAwarePaginator(
            $availableNotifications->forPage($page, $perPage)->values(),
            $availableNotifications->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $unreadCount = $this->notificationVisibilityService
            ->availableUnreadNotificationsCountFor($request->user());

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $notification = $this->notificationVisibilityService
            ->notificationsFor($request->user())
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

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $this->notificationVisibilityService
            ->notificationsFor($request->user())
            ->whereKey($id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $target = $this->notificationRedirectService->resolve($request->user(), $notification);

        if (!empty($target['message'])) {
            return redirect()
                ->route('notifications.index')
                ->with('notification_warning', $target['message']);
        }

        if (!empty($target['url'])) {
            return redirect()->to($target['url']);
        }

        return redirect()->route('notifications.index');
    }

    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $this->notificationVisibilityService
            ->unreadNotificationsFor($request->user())
            ->update([
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
        $visibleNotifications = $this->notificationVisibilityService
            ->availableNotificationsFor($request->user());
        $unreadNotifications = $this->notificationVisibilityService
            ->availableUnreadNotificationsFor($request->user());
        $notifications = $visibleNotifications->take(10);

        return response()->json([
            'count' => $unreadNotifications->count(),
            'notifications_count' => $visibleNotifications->count(),
            'items' => $notifications->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notificacion interna',
                'message' => $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion.',
                'url' => $notification->data['url'] ?? null,
                'open_url' => route('notifications.open', $notification->id),
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
