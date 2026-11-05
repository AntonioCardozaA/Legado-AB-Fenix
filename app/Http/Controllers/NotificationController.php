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
        $dashboardUrl = route('dashboard');
        $notificationsPath = '/' . ltrim((string) parse_url(route('notifications.index', [], false), PHP_URL_PATH), '/');
        $previousUrl = url()->previous($dashboardUrl);
        $previousPath = '/' . ltrim((string) parse_url($previousUrl, PHP_URL_PATH), '/');
        $previousHost = parse_url($previousUrl, PHP_URL_HOST);
        $isSameHost = blank($previousHost) || $previousHost === $request->getHost();
        $isNotificationsUrl = $previousPath === $notificationsPath
            || str_starts_with($previousPath, rtrim($notificationsPath, '/') . '/');

        if ($isSameHost && ! $isNotificationsUrl && $previousUrl !== $request->fullUrl()) {
            $request->session()->put('notifications.return_url', $previousUrl);
        }

        $returnUrl = $request->session()->get('notifications.return_url', $dashboardUrl);
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $availableNotifications = $this->notificationVisibilityService
            ->availableNotificationsFor($request->user());
        $totalNotifications = $availableNotifications->count();
        $unreadCount = $availableNotifications->whereNull('read_at')->count();
        $activeFilter = $request->string('filter')->lower()->value();
        $searchTerm = trim((string) $request->input('search', ''));
        $priorityFilter = strtolower(trim((string) $request->input('priority', '')));
        $typeFilter = trim((string) $request->input('type', ''));
        $lineFilter = trim((string) $request->input('line', ''));
        $sortOrder = strtolower(trim((string) $request->input('sort', 'recent')));

        $typeOptions = $availableNotifications
            ->mapWithKeys(function ($notification): array {
                $data = $notification->data ?? [];
                $typeKey = $this->notificationTypeKey($data);

                return [$typeKey => $this->notificationTypeLabel($data)];
            })
            ->sort()
            ->all();
        $lineOptions = $availableNotifications
            ->map(fn ($notification): string => $this->notificationLine($notification->data ?? []))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (! in_array($activeFilter, ['all', 'unread', 'read'], true)) {
            $activeFilter = 'all';
        }
        if (! in_array($priorityFilter, ['alta', 'media', 'baja'], true)) {
            $priorityFilter = '';
        }
        if (! array_key_exists($typeFilter, $typeOptions)) {
            $typeFilter = '';
        }
        if (! in_array($lineFilter, $lineOptions, true)) {
            $lineFilter = '';
        }
        if (! in_array($sortOrder, ['recent', 'oldest', 'priority'], true)) {
            $sortOrder = 'recent';
        }

        $filteredNotifications = match ($activeFilter) {
            'unread' => $availableNotifications->whereNull('read_at'),
            'read' => $availableNotifications->whereNotNull('read_at'),
            default => $availableNotifications,
        };

        if ($searchTerm !== '') {
            $normalizedSearch = strtolower(\Illuminate\Support\Str::ascii($searchTerm));
            $filteredNotifications = $filteredNotifications->filter(function ($notification) use ($normalizedSearch): bool {
                $data = $notification->data ?? [];
                $searchableText = implode(' ', array_filter([
                    $data['title'] ?? null,
                    $data['message'] ?? $data['mensaje'] ?? null,
                    $data['actor_name'] ?? null,
                    $data['linea'] ?? $data['linea_nombre'] ?? null,
                    $data['detail'] ?? null,
                    $data['estado'] ?? $data['component_state'] ?? null,
                    $this->notificationTypeLabel($data),
                ], fn ($value): bool => filled($value)));

                return str_contains(
                    strtolower(\Illuminate\Support\Str::ascii($searchableText)),
                    $normalizedSearch
                );
            });
        }

        if ($priorityFilter !== '') {
            $filteredNotifications = $filteredNotifications->filter(
                fn ($notification): bool => strtolower((string) (($notification->data ?? [])['prioridad'] ?? 'baja')) === $priorityFilter
            );
        }

        if ($typeFilter !== '') {
            $filteredNotifications = $filteredNotifications->filter(
                fn ($notification): bool => $this->notificationTypeKey($notification->data ?? []) === $typeFilter
            );
        }

        if ($lineFilter !== '') {
            $filteredNotifications = $filteredNotifications->filter(
                fn ($notification): bool => $this->notificationLine($notification->data ?? []) === $lineFilter
            );
        }

        $priorityRank = ['baja' => 1, 'media' => 2, 'alta' => 3];
        $filteredNotifications = match ($sortOrder) {
            'oldest' => $filteredNotifications
                ->sortBy(fn ($notification): int => $notification->created_at?->getTimestamp() ?? 0)
                ->values(),
            'priority' => $filteredNotifications
                ->sortByDesc(fn ($notification): int => $priorityRank[strtolower((string) (($notification->data ?? [])['prioridad'] ?? 'baja'))] ?? 1)
                ->values(),
            default => $filteredNotifications
                ->sortByDesc(fn ($notification): int => $notification->created_at?->getTimestamp() ?? 0)
                ->values(),
        };

        $notifications = new LengthAwarePaginator(
            $filteredNotifications->forPage($page, $perPage)->values(),
            $filteredNotifications->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('notifications.index', compact(
            'notifications',
            'unreadCount',
            'returnUrl',
            'totalNotifications',
            'activeFilter',
            'searchTerm',
            'priorityFilter',
            'typeFilter',
            'lineFilter',
            'sortOrder',
            'typeOptions',
            'lineOptions'
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notificationTypeKey(array $data): string
    {
        return (string) ($data['type'] ?? $data['record_type'] ?? 'notification');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notificationTypeLabel(array $data): string
    {
        return (string) ($data['record_label'] ?? match ($data['type'] ?? null) {
            'plan_accion_due' => 'Plan de accion',
            'component_alert' => 'Alerta de componente',
            'admin_analysis_deleted' => 'Analisis eliminado',
            default => 'Notificacion',
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notificationLine(array $data): string
    {
        return trim((string) ($data['linea'] ?? $data['linea_nombre'] ?? ''));
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
        $notifications = $visibleNotifications->take(10);

        return response()->json([
            'count' => $visibleNotifications->whereNull('read_at')->count(),
            'notifications_count' => $visibleNotifications->count(),
            'items' => $notifications->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notificacion interna',
                'message' => $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion.',
                'url' => $notification->data['url'] ?? null,
                'open_url' => route('notifications.open', $notification->id, false),
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
