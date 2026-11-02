<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEGADO AB FÉNIX - Sistema de Gestión</title>

    @include('layouts.partials.site-icons')

    @unless($__env->hasSection('without-charts'))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endunless
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/layout-app.css') }}?v={{ substr(hash_file('sha256', public_path('css/layout-app.css')), 0, 12) }}">
    @vite('resources/css/auth-tailwind.css')

</head>

<body class="bg-gray-100 overflow-x-hidden">

<div
    class="app-shell flex min-h-screen"
    x-data="{
        sidebarOpen: false,
        isDesktop: window.innerWidth >= 1024,
        init() {
            this.sidebarOpen = this.isDesktop;

            window.addEventListener('resize', () => {
                this.isDesktop = window.innerWidth >= 1024;
                this.sidebarOpen = this.isDesktop;
            });
        }
    }"
    @keydown.escape.window="sidebarOpen = false"
>

    <!-- OVERLAY MÓVIL -->
    <div
        x-cloak
        x-show="sidebarOpen && !isDesktop"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
    ></div>

    <!-- SIDEBAR -->
    <aside
        x-cloak
        x-show="sidebarOpen || isDesktop"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="transform -translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform -translate-x-full"
        class="sidebar fixed inset-y-0 left-0 z-50 w-64 flex flex-col h-screen shadow-sm lg:z-40 lg:translate-x-0"
    >

        <!-- Logo -->
        <div class="px-6 py-6 border-b border-gray-200">
            <div class="flex flex-col items-center text-center">
                <div
                    class="logo-sequence mb-0 drop-shadow-lg"
                    role="img"
                    aria-label="Logo Legado Ave Fenix"
                >
                <picture style="display: contents">
                <source srcset="{{ asset('images/optimized/logo1-32489536c283.webp') }}" type="image/webp">
                <img
                    src="{{ asset('images/logo1.png') }}"
                    alt=""
                    aria-hidden="true"
                    class="logo-sequence-base w-30 h-30"
                >
                </picture>
                    <picture style="display: contents">
                    <source srcset="{{ asset('images/optimized/logo2-2ef164b28259.webp') }}" type="image/webp">
                    <img
                        src="{{ asset('images/logo2.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="logo-sequence-frame logo-sequence-frame--2"
                    >
                    </picture>
                    <picture style="display: contents">
                    <source srcset="{{ asset('images/optimized/logo3-d5f55659541f.webp') }}" type="image/webp">
                    <img
                        src="{{ asset('images/logo3.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="logo-sequence-frame logo-sequence-frame--3"
                    >
                    </picture>
                    <picture style="display: contents">
                    <source srcset="{{ asset('images/optimized/logo4-6b27cb0fdc70.webp') }}" type="image/webp">
                    <img
                        src="{{ asset('images/logo4.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="logo-sequence-frame logo-sequence-frame--4"
                    >
                    </picture>
                </div>
                <h1 class="text-sm font-semibold tracking-wide leading-tight logo-text">
                    LEGADO AB<br>
                    <span class="text-yellow-500 font-bold">FÉNIX</span>
                </h1>
            </div>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 px-4 py-6 space-y-2 text-sm overflow-y-auto">
            @php
                $usuarioActual = auth()->user();
                $usaPerfilExcentricos = $usuarioActual?->usesPasteurizadoraExcentricosAccessProfile() ?? false;
                $puedeVerDashboardPrincipal = $usuarioActual?->canUseCustomPermission('ver dashboard principal') ?? false;
                $puedeVerDashboardLavadora = ($usuarioActual?->canUseCustomPermission('ver dashboard lavadoras') ?? false)
                    && ($usuarioActual?->canAccessModule(\App\Models\User::MODULE_LAVADORA) ?? false);
                $puedeVerDashboardEtiquetadora = ($usuarioActual?->canUseCustomPermission('ver dashboard etiquetadoras') ?? false)
                    && ($usuarioActual?->canAccessModule(\App\Models\User::MODULE_ETIQUETADORA) ?? false);
                $puedeVerDashboardPasteurizadora = $usuarioActual?->canUseCustomPermission('ver dashboard pasteurizadoras') ?? false;
                $puedeVerReportes = $usuarioActual?->canUseCustomPermission('ver reportes') ?? false;
                $puedeVerLef52124 = $usuarioActual?->canUseCustomPermission('ver 52-12-4') ?? false;
                $puedeGestionarUsuarios = $usuarioActual?->canUseCustomPermission('gestionar usuarios') ?? false;
                $puedeVerObservabilidadIa = $usuarioActual?->canViewAiObservability() ?? false;
            @endphp

            @if($usaPerfilExcentricos)
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.excentricos.index') }}"
                   @click="if (!isDesktop) sidebarOpen = false"
                   aria-label="Revisiones de Lubricador"
                   class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('pasteurizadora.analisis-pasteurizadora.excentricos.*') || request()->routeIs('pasteurizadora.analisis-pasteurizadora.create-quick') ? 'nav-active' : '' }}">
                    <i class="fas fa-clipboard-check w-5 mr-3 text-gray-500"></i>
                    Lubricador
                </a>
            @else
            @if($puedeVerDashboardPrincipal)
            <a href="{{ route('dashboard') }}"
               @click="if (!isDesktop) sidebarOpen = false"
               aria-label="Ir al Dashboard"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('dashboard.index') || request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                <i class="fas fa-chart-line w-5 mr-3 text-gray-500"></i>
                Dashboard
            </a>
            @endif

            @if($puedeVerDashboardLavadora)
            <a href="{{ route('lavadora.dashboard') }}"
               @click="if (!isDesktop) sidebarOpen = false"
               aria-label="Dashboard de Lavadora"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('dashboard.lavadora') || request()->routeIs('lavadora.dashboard') || request()->routeIs('dashboard_lavadora') ? 'nav-active' : '' }}">
                <i class="fas fa-droplet w-5 mr-3 text-gray-500"></i>
                Lavadora
            </a>
            @endif

            @if($puedeVerDashboardEtiquetadora)
                <a href="{{ route('etiquetadora.dashboard') }}"
                   @click="if (!isDesktop) sidebarOpen = false"
                   aria-label="Dashboard de Etiquetadora"
                   class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('etiquetadora.*') || request()->routeIs('analisis-etiquetadora.*') || request()->routeIs('dashboard_etiquetadora') ? 'nav-active' : '' }}">
                    <i class="fas fa-tags w-5 mr-3 text-gray-500"></i>
                    Etiquetadora
                </a>
            @endif

            @auth
                @php
                    $mostrarPasteurizadora = ($canSeePasteurizadora ?? ($canAccessPasteurizadora ?? false))
                        && $puedeVerDashboardPasteurizadora;
                    $pasteurizadoraEnConstruccion = $pasteurizadoraComingSoon ?? false;
                @endphp

                @if($mostrarPasteurizadora)
                @if($pasteurizadoraEnConstruccion)
                    <button type="button"
                            @click="
                                if (!isDesktop) sidebarOpen = false;
                                Swal.fire({
                                    icon: 'info',
                                    
                                    text: 'Estamos trabajando en ello, estará disponible muy pronto.',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#1e40af'
                                });
                            "
                            aria-label="Análisis de Pasteurizadora"
                            class="nav-link flex items-center w-full text-left px-4 py-3 rounded-lg">
                        <i class="fas fa-thermometer-half w-5 mr-3 text-gray-500"></i>
                        Pasteurizadora
                    </button>
                @else
                    <a href="{{ route('pasteurizadora.dashboard') }}"
                       @click="if (!isDesktop) sidebarOpen = false"
                       aria-label="Análisis de Pasteurizadora"
                       class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('dashboard.pasteurizadora') || request()->routeIs('pasteurizadora.dashboard') || request()->routeIs('dashboard_pasteurizadora') ? 'nav-active' : '' }}">
                        <i class="fas fa-thermometer-half w-5 mr-3 text-gray-500"></i>
                        Pasteurizadora
                    </a>
                @endif
                @endif
            @endauth

            @if($puedeVerLef52124)
                <a href="{{ route('lef52124.index') }}"
                   @click="if (!isDesktop) sidebarOpen = false"
                   aria-label="Dashboard 52-12-4"
                   class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('lef52124.*') ? 'nav-active' : '' }}">
                    <i class="fas fa-chart-column w-5 mr-3 text-gray-500"></i>
                    52-12-4
                </a>
            @endif

            @if($puedeVerReportes)
                <a href="{{ route('reportes.index') }}"
                   @click="if (!isDesktop) sidebarOpen = false"
                   aria-label="Reportes"
                   class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('reportes.*') ? 'nav-active' : '' }}">
                    <i class="fas fa-chart-bar w-5 mr-3 text-gray-500"></i>
                    Reportes
                </a>
            @endif

            @auth
                @if($puedeGestionarUsuarios)
                    <a href="{{ route('admin.users.index') }}"
                       @click="if (!isDesktop) sidebarOpen = false"
                       aria-label="Gestion de usuarios"
                       class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('admin.users.*') ? 'nav-active' : '' }}">
                        <i class="fas fa-user-shield w-5 mr-3 text-gray-500"></i>
                        Gestion de usuarios
                    </a>
                    <a href="{{ route('admin.alert-recipients.index') }}"
                       @click="if (!isDesktop) sidebarOpen = false"
                       aria-label="Destinatarios de alertas"
                       class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('admin.alert-recipients.*') ? 'nav-active' : '' }}">
                        <i class="fas fa-mobile-screen-button w-5 mr-3 text-gray-500"></i>
                        Destinatarios de alertas
                    </a>
                @endif

                @if($puedeVerObservabilidadIa)
                    <a href="{{ route('admin.ai-observability.index') }}"
                       @click="if (!isDesktop) sidebarOpen = false"
                       aria-label="Observabilidad IA"
                       class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('admin.ai-observability.*') ? 'nav-active' : '' }}">
                        <i class="fas fa-brain w-5 mr-3 text-gray-500"></i>
                        Observabilidad IA
                    </a>
                @endif

                @if($usuarioActual?->canAccessLavadoraCosts())
                    <a href="{{ route('admin.costos.index') }}"
                       @click="if (!isDesktop) sidebarOpen = false"
                       aria-label="Control de gastos"
                       class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('admin.costos.*') ? 'nav-active' : '' }}">
                        <i class="fas fa-coins w-5 mr-3 text-gray-500"></i>
                        Control de gastos
                    </a>
                @endif
            @endauth
            @endif
        </nav>

        <!-- Footer del sidebar -->
        <div class="px-4 py-4 border-t border-gray-200">
            <div class="flex items-center space-x-3 px-4 py-2 text-xs text-gray-500">
                <i class="fas fa-copyright"></i>
                <span>v2.2.5</span>
            </div>
        </div>
    </aside>

    <!-- CONTENIDO -->
    <div class="flex-1 flex flex-col overflow-hidden min-w-0 lg:ml-64">

        <!-- Header -->
        <header class="bg-white shadow-sm border-b px-4 sm:px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3 sm:flex-nowrap sm:gap-4">

                <div class="flex flex-1 items-center space-x-3 min-w-0">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        aria-label="Abrir menú"
                        class="lg:hidden p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition">
                        <i class="fas fa-bars text-gray-600"></i>
                    </button>

                    <h2 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">
                        @yield('title', 'Legado AB Fénix')
                    </h2>
                </div>

                <div class="flex shrink-0 items-center space-x-2 sm:space-x-4">
                    @auth
                    @unless(auth()->user()?->usesPasteurizadoraExcentricosAccessProfile())
                    <div class="global-search-shell relative" x-data="globalSearch()" @click.away="close()" @keydown.escape.window="close()">
                        <button type="button"
                                @click="toggle()"
                                :class="{ 'is-open': open }"
                                :aria-expanded="open.toString()"
                                aria-controls="global-search-panel"
                                aria-label="Busqueda global"
                                class="global-search-button inline-flex items-center justify-center gap-2 rounded-full bg-white px-3 py-2 text-sm font-semibold text-gray-600 transition">
                            <i class="fas fa-magnifying-glass text-sm"></i>
                            <span class="hidden md:inline">Buscar</span>
                        </button>

                        <div x-show="open"
                             id="global-search-panel"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="global-search-panel absolute right-0 z-50 mt-3 w-[min(32rem,calc(100vw-2rem))] overflow-hidden rounded-2xl bg-white"
                             style="display: none;">
                            <div class="border-b border-gray-100 bg-gray-50/80 p-3">
                                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm">
                                    <i class="fas fa-magnifying-glass text-sm text-blue-600"></i>
                                    <input x-ref="searchInput"
                                           x-model="query"
                                           @input.debounce.220ms="search()"
                                           @keydown.arrow-down.prevent="next()"
                                           @keydown.arrow-up.prevent="previous()"
                                           @keydown.enter.prevent="openActive()"
                                           type="search"
                                           class="w-full border-0 p-0 text-sm font-medium text-gray-800 placeholder:text-gray-400 focus:ring-0"
                                           placeholder="Buscar en el sistema">
                                    <button type="button"
                                            x-show="query"
                                            @click="clear()"
                                            aria-label="Limpiar busqueda"
                                            class="rounded-full p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                            style="display: none;">
                                        <i class="fas fa-xmark text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="global-search-results max-h-[26rem] overflow-y-auto py-2">
                                <template x-if="loading">
                                    <div class="flex items-center gap-3 px-4 py-4 text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-blue-600"></i>
                                        Buscando...
                                    </div>
                                </template>

                                <template x-if="error">
                                    <div class="px-4 py-4 text-sm font-medium text-red-600" x-text="error"></div>
                                </template>

                                <template x-if="!loading && legacyUnavailable">
                                    <div class="px-4 py-3 text-sm text-amber-700" role="status">La búsqueda de análisis legado no está disponible. Se muestran los demás resultados.</div>
                                </template>

                                <template x-if="!loading && !error && results.length === 0">
                                    <div class="px-4 py-8 text-center text-sm text-gray-500">
                                        <i class="fas fa-magnifying-glass text-2xl text-gray-300"></i>
                                        <p class="mt-2 font-semibold">Sin resultados</p>
                                    </div>
                                </template>

                                <template x-for="(item, index) in results" :key="item.key || item.url || index">
                                    <a :href="item.url"
                                       @mouseenter="activeIndex = index"
                                       :class="{ 'is-active': activeIndex === index }"
                                       class="global-search-result mx-2 flex items-start gap-3 rounded-xl px-3 py-3 text-left">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                            <i :class="'fas ' + (item.icon || 'fa-magnifying-glass')"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-start justify-between gap-3">
                                                <span class="truncate text-sm font-bold text-gray-900" x-text="item.title"></span>
                                                <span x-show="item.badge"
                                                      class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-500"
                                                      x-text="item.badge"></span>
                                            </span>
                                            <span class="mt-0.5 block truncate text-xs text-gray-500" x-text="item.description"></span>
                                            <span class="mt-1 inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-400" x-text="item.section"></span>
                                        </span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                    @endunless
                    @endauth

                    <!-- NOTIFICACIONES DROPDOWN -->
                    @auth
                    @unless(auth()->user()?->usesPasteurizadoraExcentricosAccessProfile())
                    @php
                        $notificationVisibility = app(\App\Services\NotificationVisibilityService::class);
                        $availableNotifications = $notificationVisibility->availableNotificationsFor(auth()->user());
                        $notificationItems = $availableNotifications->take(10);
                        $notificationsCount = $availableNotifications->count();
                        $unreadCount = $availableNotifications->whereNull('read_at')->count();
                    @endphp
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open"
                                :class="{ 'is-open': open }"
                                id="notification-button"
                                aria-label="Notificaciones"
                                class="notification-bell-button relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 {{ $unreadCount > 0 ? 'has-unread' : '' }}">
                            <i class="notification-bell-icon fas fa-bell text-gray-600"></i>
                            <span id="notification-badge"
                                  class="notification-badge-pulse absolute top-0 right-0 inline-flex items-center justify-center px-1 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full {{ $unreadCount > 0 ? '' : 'hidden' }}">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="notification-elegant-panel absolute right-0 z-50 mt-3 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl bg-white"
                             style="display: none;">

                            <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                                <span class="inline-flex items-center gap-2 text-sm font-bold text-gray-800">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                        <i class="fas fa-bell"></i>
                                    </span>
                                    Notificaciones
                                </span>
                                <form id="notification-read-all-form" action="{{ route('notifications.read-all') }}" method="POST" class="inline {{ $unreadCount > 0 ? '' : 'hidden' }}">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-600 shadow-sm ring-1 ring-blue-100 transition hover:bg-blue-50">
                                        Marcar todas como leídas
                                    </button>
                                </form>
                            </div>

                            <div id="notifications-list" class="max-h-96 overflow-y-auto py-2">
                                @forelse($notificationItems as $notification)
                                    @php($notificationOpenUrl = route('notifications.open', $notification->id, false))
                                    <a href="{{ $notificationOpenUrl }}"
                                       class="notification-elegant-item mx-2 block rounded-xl px-3 py-3 transition hover:bg-gray-50 {{ $notification->read_at ? '' : 'is-unread bg-blue-50/70' }}">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl {{ $notification->read_at ? 'bg-gray-100' : 'bg-white shadow-sm ring-1 ring-blue-100' }}">
                                                @if(($notification->data['prioridad'] ?? 'baja') == 'alta')
                                                    <i class="fas fa-exclamation-circle text-red-500"></i>
                                                @elseif(($notification->data['prioridad'] ?? 'baja') == 'media')
                                                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                                @else
                                                    <i class="fas fa-info-circle text-blue-500"></i>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="text-sm text-gray-900 mb-1">{{ $notification->data['mensaje'] ?? $notification->data['message'] ?? 'Nueva notificación' }}</p>
                                                    @if(!$notification->read_at)
                                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">Nueva</span>
                                                    @endif
                                                </div>
                                                @if(!empty($notification->data['area_pasteurizadora_label']))
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100">
                                                        <i class="fas fa-tools"></i>
                                                        Parte: {{ $notification->data['area_pasteurizadora_label'] }}
                                                    </span>
                                                @endif
                                                <p class="mt-1 text-xs font-medium text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-4 py-8 text-center text-gray-500">
                                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-300">
                                            <i class="fas fa-bell-slash text-2xl"></i>
                                        </span>
                                        <p class="mt-3 text-sm font-semibold">No hay notificaciones</p>
                                    </div>
                                @endforelse
                            </div>

                            @if($notificationsCount > 0)
                                <div id="notifications-view-all-wrapper" class="border-t border-gray-100 bg-gray-50/80 px-4 py-3 text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                                        Ver todas las notificaciones
                                    </a>
                                </div>
                            @else
                                <div id="notifications-view-all-wrapper" class="hidden border-t border-gray-100 bg-gray-50/80 px-4 py-3 text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                                        Ver todas las notificaciones
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endunless
                    @endauth

                    @auth
                        <div class="relative" x-data="{ profileMenuOpen: false }" @click.away="profileMenuOpen = false">
                            <button type="button"
                                    @click="profileMenuOpen = !profileMenuOpen"
                                    aria-label="Perfil de usuario"
                                    class="inline-flex items-center gap-2 sm:gap-3 rounded-full bg-gray-100 px-2 sm:px-3 py-2 hover:bg-gray-200 transition">
                                <div class="hidden sm:block text-right leading-tight">
                                    <p class="text-sm font-semibold text-gray-700">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $userRoleLabel ?? 'Perfil de usuario' }}</p>
                                </div>
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white shadow-sm">
                                    <i class="fas fa-user text-sm"></i>
                                </span>
                                <i class="fas fa-chevron-down text-xs text-gray-500 hidden sm:inline"></i>
                            </button>

                            <div x-show="profileMenuOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg z-50"
                                 style="display: none;">
                                @unless(auth()->user()?->usesPasteurizadoraExcentricosAccessProfile())
                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-circle text-blue-600"></i>
                                    Ver perfil
                                </a>
                                @endunless

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex w-full items-center gap-3 border-t border-gray-100 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-right-from-bracket"></i>
                                        Cerrar sesion
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('welcome') }}"
                           class="inline-flex items-center rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
                            Inicio
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Main -->
        <main class="app-shell-main app-content flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6">
            @yield('content')
        </main>

    </div>

</div>

@include('pasteurizadora.partials.responsive-styles')

@auth
    @if(auth()->user()?->canUseCustomPermission('usar asistente ia') && ! auth()->user()?->usesPasteurizadoraExcentricosAccessProfile())
        @include('layouts.partials.assistant-chat')
    @endif
@endauth

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const globalSearchUrl = @json(route('global-search.index', [], false));
const notificationReadUrlTemplate = @json(route('notifications.read', ['id' => '__ID__'], false));
const notificationsUnreadCountUrl = @json(route('notifications.unread-count', [], false));

function globalSearch() {
    return {
        open: false,
        query: '',
        results: [],
        legacyUnavailable: false,
        loading: false,
        error: '',
        activeIndex: 0,
        abortController: null,

        init() {
            document.addEventListener('global-search:open', () => this.openSearch());
        },

        toggle() {
            this.open ? this.close() : this.openSearch();
        },

        openSearch() {
            this.open = true;

            this.$nextTick(() => {
                this.$refs.searchInput?.focus();

                if (this.results.length === 0) {
                    this.search();
                }
            });
        },

        close() {
            this.open = false;
        },

        clear() {
            this.query = '';
            this.search();
            this.$refs.searchInput?.focus();
        },

        search() {
            if (this.abortController) {
                this.abortController.abort();
            }

            this.loading = true;
            this.error = '';
            this.legacyUnavailable = false;
            this.abortController = new AbortController();

            const url = new URL(globalSearchUrl, window.location.origin);
            const term = this.query.trim();

            if (term) {
                url.searchParams.set('q', term);
            }

            fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                signal: this.abortController.signal
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('No se pudo buscar en este momento.');
                    }

                    return response.json();
                })
                .then(data => {
                    this.results = data.items || [];
                    this.legacyUnavailable = (data.unavailable_sources || []).includes('legacy_analyses');
                    this.activeIndex = 0;
                })
                .catch(error => {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    this.error = error.message || 'No se pudo buscar en este momento.';
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        next() {
            if (this.results.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + 1) % this.results.length;
        },

        previous() {
            if (this.results.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex - 1 + this.results.length) % this.results.length;
        },

        openActive() {
            const item = this.results[this.activeIndex];

            if (item?.url) {
                window.location.href = item.url;
            }
        }
    };
}

document.addEventListener('keydown', function(event) {
    const target = event.target;
    const isTyping = target instanceof HTMLInputElement
        || target instanceof HTMLTextAreaElement
        || target instanceof HTMLSelectElement
        || target?.isContentEditable;

    if (event.key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey && !isTyping) {
        event.preventDefault();
        document.dispatchEvent(new CustomEvent('global-search:open'));
    }
});

function markAsRead(notificationId, url) {
    fetch(notificationReadUrlTemplate.replace('__ID__', encodeURIComponent(notificationId)), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (url && url !== '#') {
                window.location.href = url;
            } else {
                window.location.reload();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (url && url !== '#') {
            window.location.href = url;
        } else {
            window.location.reload();
        }
    });
}

function notificationIconClasses(priority) {
    if (priority === 'alta') {
        return 'fas fa-exclamation-circle text-red-500';
    }

    if (priority === 'media') {
        return 'fas fa-exclamation-triangle text-yellow-500';
    }

    return 'fas fa-info-circle text-blue-500';
}

function emptyNotificationsNode() {
    const empty = document.createElement('div');
    empty.className = 'px-4 py-8 text-center text-gray-500';

    const iconWrap = document.createElement('span');
    iconWrap.className = 'mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-300';

    const icon = document.createElement('i');
    icon.className = 'fas fa-bell-slash text-2xl';
    iconWrap.appendChild(icon);

    const text = document.createElement('p');
    text.className = 'mt-3 text-sm font-semibold';
    text.textContent = 'No hay notificaciones';

    empty.append(iconWrap, text);

    return empty;
}

function notificationItemNode(item) {
    const targetUrl = item.open_url || item.url || '#';
    const link = document.createElement('a');
    link.href = targetUrl;
    link.className = 'notification-elegant-item mx-2 block rounded-xl px-3 py-3 transition hover:bg-gray-50 ' + (item.is_read ? '' : 'is-unread bg-blue-50/70');

    const row = document.createElement('div');
    row.className = 'flex items-start gap-3';

    const iconWrap = document.createElement('div');
    iconWrap.className = 'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl ' + (item.is_read ? 'bg-gray-100' : 'bg-white shadow-sm ring-1 ring-blue-100');
    const icon = document.createElement('i');
    icon.className = notificationIconClasses(item.prioridad || 'baja');
    iconWrap.appendChild(icon);

    const body = document.createElement('div');
    body.className = 'flex-1 min-w-0';

    const messageRow = document.createElement('div');
    messageRow.className = 'flex items-start justify-between gap-2';

    const message = document.createElement('p');
    message.className = 'mb-1 text-sm font-semibold leading-snug text-gray-900';
    message.textContent = item.message || item.title || 'Nueva notificacion';
    messageRow.appendChild(message);

    if (!item.is_read) {
        const unread = document.createElement('span');
        unread.className = 'rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700';
        unread.textContent = 'Nueva';
        messageRow.appendChild(unread);
    }

    body.appendChild(messageRow);

    if (item.area_pasteurizadora_label) {
        const area = document.createElement('span');
        area.className = 'inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100';

        const areaIcon = document.createElement('i');
        areaIcon.className = 'fas fa-tools';

        const areaText = document.createTextNode(' Parte: ' + item.area_pasteurizadora_label);
        area.append(areaIcon, areaText);
        body.appendChild(area);
    }

    const time = document.createElement('p');
    time.className = 'mt-1 text-xs font-medium text-gray-500';
    time.textContent = item.created_at_human || '';
    body.appendChild(time);

    row.append(iconWrap, body);
    link.appendChild(row);

    return link;
}

function renderNotifications(items) {
    const list = document.getElementById('notifications-list');

    if (!list) {
        return;
    }

    list.replaceChildren();

    if (!items || items.length === 0) {
        list.appendChild(emptyNotificationsNode());
        return;
    }

    items.forEach(function(item) {
        list.appendChild(notificationItemNode(item));
    });
}

function updateNotificationControls(data) {
    const badge = document.getElementById('notification-badge');
    const notificationButton = document.getElementById('notification-button');
    const readAllForm = document.getElementById('notification-read-all-form');
    const viewAllWrapper = document.getElementById('notifications-view-all-wrapper');
    const unreadCount = Number(data.count) || 0;

    if (notificationButton) {
        notificationButton.classList.toggle('has-unread', unreadCount > 0);
    }

    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    if (readAllForm) {
        readAllForm.classList.toggle('hidden', unreadCount <= 0);
    }

    if (viewAllWrapper) {
        viewAllWrapper.classList.toggle('hidden', (data.notifications_count || 0) <= 0);
    }
}

let notificationRefreshPending = false;

function refreshNotifications() {
    if (document.hidden || notificationRefreshPending) {
        return;
    }
    if (!document.getElementById('notification-badge') && !document.getElementById('notifications-list')) {
        return;
    }

    notificationRefreshPending = true;
    return fetch(notificationsUnreadCountUrl, {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('No se pudieron consultar las notificaciones.');
        return response.json();
    })
    .then(data => {
        updateNotificationControls(data);
        renderNotifications(data.items || []);
    })
    .catch(error => {
        console.error('Error al actualizar notificaciones:', error);
    })
    .finally(() => {
        notificationRefreshPending = false;
    });
}

// Blade ya entrega la campana actualizada; el primer refresco sera a los 30 segundos.
setInterval(refreshNotifications, 30000);
document.addEventListener('visibilitychange', function () {
    if (!document.hidden) refreshNotifications();
});

(function() {
    const actividadSelector = 'textarea[name="actividad"], input[name="actividad"]';

    function esCampoActividad(element) {
        return (element instanceof HTMLTextAreaElement || element instanceof HTMLInputElement)
            && element.matches(actividadSelector);
    }

    function convertirActividadAMayusculas(field) {
        if (!esCampoActividad(field) || field.dataset.actividadComposing === 'true') {
            return;
        }

        const value = field.value;
        const upperValue = value.toLocaleUpperCase('es-MX');

        if (value === upperValue) {
            return;
        }

        const selectionStart = field.selectionStart;
        const selectionEnd = field.selectionEnd;

        field.value = upperValue;

        if (
            document.activeElement === field
            && typeof field.setSelectionRange === 'function'
            && typeof selectionStart === 'number'
            && typeof selectionEnd === 'number'
        ) {
            field.setSelectionRange(selectionStart, selectionEnd);
        }
    }

    function prepararCampoActividad(field) {
        if (!esCampoActividad(field)) {
            return;
        }

        field.setAttribute('autocapitalize', 'characters');
        convertirActividadAMayusculas(field);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll(actividadSelector).forEach(prepararCampoActividad);
    });

    document.addEventListener('compositionstart', function(event) {
        if (esCampoActividad(event.target)) {
            event.target.dataset.actividadComposing = 'true';
        }
    }, true);

    document.addEventListener('compositionend', function(event) {
        if (esCampoActividad(event.target)) {
            event.target.dataset.actividadComposing = 'false';
            convertirActividadAMayusculas(event.target);
        }
    }, true);

    document.addEventListener('input', function(event) {
        convertirActividadAMayusculas(event.target);
    }, true);

    document.addEventListener('submit', function(event) {
        if (!(event.target instanceof HTMLFormElement)) {
            return;
        }

        event.target.querySelectorAll(actividadSelector).forEach(function(field) {
            field.dataset.actividadComposing = 'false';
            convertirActividadAMayusculas(field);
        });
    }, true);
})();
</script>

<script>
(function() {
    const formSelector = 'form[data-auto-filter-form]';
    const ignoredInputTypes = new Set(['button', 'submit', 'reset', 'file', 'hidden']);
    const textInputTypes = new Set(['text', 'search', 'email', 'number', 'tel', 'url', 'password']);
    const pendingTimers = new WeakMap();

    function getForm(element) {
        return element?.closest?.(formSelector) || null;
    }

    function isFilterControl(element) {
        if (!(element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement)) {
            return false;
        }

        if (!element.name || element.disabled || element.matches('[data-auto-filter-ignore]')) {
            return false;
        }

        if (element instanceof HTMLInputElement && ignoredInputTypes.has(element.type)) {
            return false;
        }

        return Boolean(getForm(element));
    }

    function shouldWaitForCommit(element) {
        return element instanceof HTMLTextAreaElement
            || (element instanceof HTMLInputElement && textInputTypes.has(element.type || 'text'));
    }

    function buildFilterUrl(form) {
        const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
        const data = new FormData(form);

        for (const key of Array.from(url.searchParams.keys())) {
            url.searchParams.delete(key);
        }

        data.forEach(function(value, key) {
            if (value instanceof File || value === null) {
                return;
            }

            if (String(value).trim() === '') {
                url.searchParams.delete(key);
                return;
            }

            if (key.endsWith('[]')) {
                url.searchParams.append(key, value);
                return;
            }

            url.searchParams.set(key, value);
        });

        url.searchParams.delete('page');
        url.searchParams.delete('importaciones_page');

        return url.toString();
    }

    function submitFilter(form) {
        if (!(form instanceof HTMLFormElement) || form.dataset.autoFilterLoading === 'true') {
            return;
        }

        if (form.matches('[data-user-directory-filter]') && typeof window.navigateUserDirectory === 'function') {
            window.navigateUserDirectory(buildFilterUrl(form));
            return;
        }

        form.dataset.autoFilterLoading = 'true';
        window.location.assign(buildFilterUrl(form));
    }

    function scheduleSubmit(form) {
        const delay = Number(form.dataset.autoFilterDebounce || 0);
        const previousTimer = pendingTimers.get(form);

        if (previousTimer) {
            window.clearTimeout(previousTimer);
        }

        if (delay > 0) {
            pendingTimers.set(form, window.setTimeout(() => submitFilter(form), delay));
            return;
        }

        submitFilter(form);
    }

    document.addEventListener('change', function(event) {
        const control = event.target;

        if (!isFilterControl(control)) {
            return;
        }

        scheduleSubmit(getForm(control));
    }, true);

    document.addEventListener('keydown', function(event) {
        const control = event.target;

        if (event.key !== 'Enter' || !isFilterControl(control) || !shouldWaitForCommit(control)) {
            return;
        }

        event.preventDefault();
        scheduleSubmit(getForm(control));
    }, true);

    document.addEventListener('submit', function(event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches(formSelector)) {
            return;
        }

        const submitter = event.submitter;

        if (submitter instanceof HTMLElement && (submitter.hasAttribute('formaction') || submitter.matches('[data-auto-filter-ignore]'))) {
            return;
        }

        event.preventDefault();
        scheduleSubmit(form);
    }, true);
})();
</script>

@hasSection('scripts')
    @yield('scripts')
@endif

@if(session('pasteurizadora_bloqueada') || session('acceso_restringido'))
<script>
    Swal.fire({
        icon: 'info',
        text: @json(session('pasteurizadora_bloqueada') ?? session('acceso_restringido')),
        title: 'Acceso restringido',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#1e40af'
    });
</script>
@endif

</body>
</html>
