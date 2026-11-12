@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
@php
    $totalNotifications = $totalNotifications ?? (method_exists($notifications, 'total') ? $notifications->total() : $notifications->count());
    $pendingNotifications = $unreadCount ?? 0;
    $reviewedNotifications = max($totalNotifications - $pendingNotifications, 0);
    $activeFilter = $activeFilter ?? 'all';
    $searchTerm = $searchTerm ?? '';
    $priorityFilter = $priorityFilter ?? '';
    $typeFilter = $typeFilter ?? '';
    $lineFilter = $lineFilter ?? '';
    $sortOrder = $sortOrder ?? 'recent';
    $typeOptions = $typeOptions ?? [];
    $lineOptions = $lineOptions ?? [];
    $filterQuery = request()->except('page');
    $hasAdvancedFilters = filled($searchTerm) || filled($priorityFilter) || filled($typeFilter) || filled($lineFilter) || $sortOrder !== 'recent';
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-gray-950">Notificaciones</h1>
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ $returnUrl ?? route('dashboard') }}"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 sm:w-auto"
            >
                <i class="fas fa-arrow-left"></i>
                Volver
            </a>

            @if($pendingNotifications > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 sm:w-auto">
                        <i class="fas fa-check-double"></i>
                        Marcar todas como leidas
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Total</p>
                    <p class="mt-1 text-2xl font-black text-gray-950">{{ $totalNotifications }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-700">
                    <i class="fas fa-inbox"></i>
                </span>
            </div>
        </div>

        <a
            href="{{ route('notifications.index', ['filter' => 'unread']) }}"
            aria-current="{{ $activeFilter === 'unread' ? 'page' : 'false' }}"
            class="group rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 shadow-sm transition hover:border-blue-400 hover:bg-blue-100 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $activeFilter === 'unread' ? 'ring-2 ring-blue-500 ring-offset-2' : '' }}"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Nuevas</p>
                    <p class="mt-1 text-2xl font-black text-blue-950">{{ $pendingNotifications }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm transition group-hover:scale-105">
                    <i class="fas fa-bell"></i>
                </span>
            </div>
        </a>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Revisadas</p>
                    <p class="mt-1 text-2xl font-black text-emerald-950">{{ $reviewedNotifications }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm">
                    <i class="fas fa-circle-check"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <p class="text-sm font-black text-gray-950">Filtrar notificaciones</p>

            <div class="flex flex-wrap gap-2" aria-label="Filtros de notificaciones">
                @foreach([
                    'all' => ['label' => 'Todas', 'icon' => 'fa-inbox'],
                    'unread' => ['label' => 'Nuevas', 'icon' => 'fa-bell'],
                    'read' => ['label' => 'Revisadas', 'icon' => 'fa-circle-check'],
                ] as $filter => $option)
                    <a
                        href="{{ route('notifications.index', array_merge($filterQuery, ['filter' => $filter])) }}"
                        aria-current="{{ $activeFilter === $filter ? 'page' : 'false' }}"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $activeFilter === $filter ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"
                    >
                        <i class="fas {{ $option['icon'] }}"></i>
                        {{ $option['label'] }}
                        @if($filter === 'unread')
                            <span class="rounded-full px-1.5 py-0.5 text-xs {{ $activeFilter === $filter ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-700' }}">{{ $pendingNotifications }}</span>
                        @elseif($filter === 'read')
                            <span class="rounded-full px-1.5 py-0.5 text-xs {{ $activeFilter === $filter ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700' }}">{{ $reviewedNotifications }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <form action="{{ route('notifications.index') }}" method="GET" class="mt-4 space-y-3" data-auto-filter-form>
            <input type="hidden" name="filter" value="{{ $activeFilter }}">

            <div class="grid gap-3 lg:grid-cols-5">
                <label class="relative block lg:col-span-2">
                    <span class="sr-only">Buscar notificaciones</span>
                    <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ $searchTerm }}"
                        placeholder="Buscar notificaciones"
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-3 text-sm font-medium text-gray-800 placeholder:text-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </label>

                <label>
                    <span class="sr-only">Prioridad</span>
                    <select name="priority" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todas las prioridades</option>
                        <option value="alta" @selected($priorityFilter === 'alta')>Prioridad alta</option>
                        <option value="media" @selected($priorityFilter === 'media')>Prioridad media</option>
                        <option value="baja" @selected($priorityFilter === 'baja')>Prioridad baja</option>
                    </select>
                </label>

                <label>
                    <span class="sr-only">Tipo</span>
                    <select name="type" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos los tipos</option>
                        @foreach($typeOptions as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected($typeFilter === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="sr-only">Línea</span>
                    <select name="line" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todas las líneas</option>
                        @foreach($lineOptions as $lineOption)
                            <option value="{{ $lineOption }}" @selected($lineFilter === $lineOption)>{{ $lineOption }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-600">
                    <span>Ordenar:</span>
                    <select name="sort" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="recent" @selected($sortOrder === 'recent')>Más recientes</option>
                        <option value="oldest" @selected($sortOrder === 'oldest')>Más antiguas</option>
                        <option value="priority" @selected($sortOrder === 'priority')>Mayor prioridad</option>
                    </select>
                </label>
                @if($hasAdvancedFilters)
                    <a href="{{ route('notifications.index', ['filter' => $activeFilter]) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-rotate-left"></i>
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(session('notification_warning'))
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-semibold text-yellow-900 shadow-sm">
            <i class="fas fa-triangle-exclamation mr-2"></i>
            {{ session('notification_warning') }}
        </div>
    @endif

    @php
        $previousDateGroup = null;
    @endphp
    <div class="space-y-3">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data ?? [];
                $priority = $data['prioridad'] ?? 'baja';
                $notificationType = $data['type'] ?? null;
                $activityDateDisplay = $notificationType === 'admin_analysis_deleted'
                    ? ($data['deleted_at_display'] ?? $notification->created_at->format('d/m/Y H:i'))
                    : ($data['created_at_display'] ?? $notification->created_at->format('d/m/Y H:i'));
                $typeLabel = $data['record_label'] ?? match ($notificationType) {
                    'plan_accion_due' => 'Plan de accion',
                    'component_alert' => 'Alerta de componente',
                    'admin_analysis_deleted' => 'Analisis eliminado',
                    default => 'Notificacion',
                };
                $isPlanActionNotification = $notificationType === 'plan_accion_due'
                    || ($data['record_type'] ?? null) === 'plan_accion'
                    || str_contains(strtolower($typeLabel), 'plan de accion');
                $stateText = $data['estado'] ?? $data['component_state'] ?? null;

                if (blank($stateText)) {
                    $stateSource = (string) ($data['detail'] ?? $data['message'] ?? $data['mensaje'] ?? '');

                    if (preg_match('/Estado:\s*([^\.]+)/u', $stateSource, $stateMatch)) {
                        $stateText = trim($stateMatch[1]);
                    }
                }

                $stateKey = strtolower(\Illuminate\Support\Str::ascii((string) $stateText));
                $priorityConfig = match ($priority) {
                    'alta' => [
                        'label' => 'Alta',
                        'rail' => 'bg-red-500',
                        'icon' => 'fa-exclamation-circle',
                        'iconClass' => 'bg-red-50 text-red-600 ring-red-100',
                        'badge' => 'border-red-200 bg-red-50 text-red-700',
                    ],
                    'media' => [
                        'label' => 'Media',
                        'rail' => 'bg-yellow-400',
                        'icon' => 'fa-exclamation-triangle',
                        'iconClass' => 'bg-yellow-50 text-yellow-600 ring-yellow-100',
                        'badge' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                    ],
                    default => [
                        'label' => 'Baja',
                        'rail' => 'bg-blue-500',
                        'icon' => 'fa-info-circle',
                        'iconClass' => 'bg-blue-50 text-blue-600 ring-blue-100',
                        'badge' => 'border-blue-200 bg-blue-50 text-blue-700',
                    ],
                };
                $statusConfig = match (true) {
                    $isPlanActionNotification => [
                        'label' => 'Plan de accion',
                        'rail' => 'bg-blue-500',
                        'icon' => 'fa-clipboard-list',
                        'iconClass' => 'bg-blue-50 text-blue-600 ring-blue-100',
                        'badge' => 'border-blue-200 bg-blue-50 text-blue-700',
                    ],
                    str_contains($stateKey, 'danado') || str_contains($stateKey, 'requiere cambio') => [
                        'label' => $stateText ?: 'Dañado',
                        'rail' => 'bg-red-500',
                        'icon' => 'fa-exclamation-circle',
                        'iconClass' => 'bg-red-50 text-red-600 ring-red-100',
                        'badge' => 'border-red-200 bg-red-50 text-red-700',
                    ],
                    str_contains($stateKey, 'desgaste') => [
                        'label' => $stateText ?: 'Desgaste',
                        'rail' => 'bg-orange-500',
                        'icon' => 'fa-exclamation-triangle',
                        'iconClass' => 'bg-orange-50 text-orange-600 ring-orange-100',
                        'badge' => 'border-orange-200 bg-orange-50 text-orange-700',
                    ],
                    str_contains($stateKey, 'requiere revision') => [
                        'label' => $stateText ?: 'Requiere revision',
                        'rail' => 'bg-yellow-400',
                        'icon' => 'fa-tools',
                        'iconClass' => 'bg-yellow-50 text-yellow-600 ring-yellow-100',
                        'badge' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                    ],
                    str_contains($stateKey, 'cambiado') => [
                        'label' => $stateText ?: 'Cambiado',
                        'rail' => 'bg-sky-500',
                        'icon' => 'fa-exchange-alt',
                        'iconClass' => 'bg-sky-50 text-sky-600 ring-sky-100',
                        'badge' => 'border-sky-200 bg-sky-50 text-sky-700',
                    ],
                    str_contains($stateKey, 'buen estado') => [
                        'label' => $stateText ?: 'Buen estado',
                        'rail' => 'bg-emerald-500',
                        'icon' => 'fa-check-circle',
                        'iconClass' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                        'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    ],
                    default => $priorityConfig,
                };
                $metaItems = collect([
                    ['Usuario', $data['actor_name'] ?? null, 'fa-user'],
                    ['Linea', $data['linea'] ?? $data['linea_nombre'] ?? null, 'fa-industry'],
                    ['Estado', $stateText, 'fa-circle-info'],
                    ['Tipo', $typeLabel, 'fa-tag'],
                    ['Fecha y hora', $activityDateDisplay, 'fa-clock'],
                ])->filter(fn ($item) => filled($item[1]));
            @endphp

            @php
                $notificationDateGroup = $notification->created_at->isToday()
                    ? 'Hoy'
                    : ($notification->created_at->isYesterday() ? 'Ayer' : $notification->created_at->format('d/m/Y'));
            @endphp

            @if($notificationDateGroup !== $previousDateGroup)
                <h2 class="pt-2 text-base font-black text-gray-950">{{ $notificationDateGroup }}</h2>
                @php
                    $previousDateGroup = $notificationDateGroup;
                @endphp
            @endif

            <article class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:border-blue-200 hover:shadow-md {{ $notification->read_at ? '' : 'ring-1 ring-blue-100' }}">
                <div class="absolute inset-y-0 left-0 w-1.5 {{ $statusConfig['rail'] }}"></div>

                <div class="p-4 pl-6 sm:p-5 sm:pl-7">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex gap-3">
                                <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $statusConfig['iconClass'] }}">
                                    <i class="fas {{ $statusConfig['icon'] }}"></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-sm font-black text-gray-950">
                                            {{ $data['title'] ?? 'Notificacion interna' }}
                                        </h2>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $statusConfig['badge'] }}">
                                            {{ $statusConfig['label'] }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold {{ $notification->read_at ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $notification->read_at ? 'Revisada' : 'Nueva' }}
                                        </span>
                                    </div>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                        <span class="inline-flex items-center gap-1">
                                            <i class="far fa-clock"></i>
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fas fa-layer-group"></i>
                                            {{ $typeLabel }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <p class="mt-4 max-w-5xl text-sm leading-6 text-gray-700">
                                {{ $data['message'] ?? $data['mensaje'] ?? 'Nueva notificacion.' }}
                            </p>

                            @if($metaItems->isNotEmpty())
                                <dl class="mt-4 grid gap-x-6 gap-y-3 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                                    @foreach($metaItems as [$label, $value, $icon])
                                        <div class="min-w-0">
                                            <dt class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-gray-500">
                                                <i class="fas {{ $icon }} text-gray-400"></i>
                                                {{ $label }}
                                            </dt>
                                            <dd class="mt-1 break-words font-semibold text-gray-950 sm:truncate">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if(!empty($data['area_pasteurizadora_label']))
                                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100">
                                        <i class="fas fa-tools"></i>
                                        {{ $data['area_pasteurizadora_label'] }}
                                    </span>
                                @endif

                                @if(!empty($data['pcm']))
                                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">
                                        <i class="fas fa-calendar-check"></i>
                                        {{ $data['pcm'] }}
                                    </span>
                                @endif

                                @if(isset($data['dias_restantes']))
                                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">
                                        <i class="fas fa-hourglass-half"></i>
                                        {{ $data['dias_restantes'] }} dia(s)
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap lg:justify-end">
                            <a
                                href="{{ route('notifications.open', $notification->id, false) }}"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 sm:w-auto">
                                <i class="fas fa-arrow-up-right-from-square"></i>
                                Abrir
                            </a>

                            @if(!$notification->read_at)
                                <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="w-full sm:w-auto">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50 sm:w-auto">
                                        <i class="fas fa-check"></i>
                                        Marcar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center text-gray-500">
                <i class="fas fa-bell-slash text-4xl text-gray-300"></i>
                <p class="mt-3 text-sm font-semibold">No hay notificaciones registradas.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="notifications-pagination rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

<style>
    .notifications-pagination {
        margin-bottom: 5.5rem;
    }

    @media (min-width: 640px) {
        .notifications-pagination nav > div:last-child {
            align-items: flex-start !important;
            flex-direction: column !important;
            gap: 0.75rem;
            justify-content: flex-start !important;
        }
    }
</style>
@endsection
