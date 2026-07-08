<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEGADO AB FÉNIX - Sistema de Gestión</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logoo.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --accent-yellow: #f59e0b;
            --accent-red: #ef4444;
        }

        [x-cloak] {
            display: none !important;
        }

        .sidebar {
            background: white;
            border-right: 1px solid #e5e7eb;
        }

        .nav-link {
            transition: all 0.2s ease-in-out;
            color: #374151;
        }

        .nav-link:hover {
            background-color: #f3f4f6;
            color: #1e40af;
            padding-left: 1.25rem;
        }

        .nav-active {
            background-color: #e6f0ff;
            border-left: 4px solid var(--primary-blue);
            color: var(--primary-blue);
            font-weight: 600;
        }

        .nav-active i {
            color: var(--primary-blue);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        .notifications-dropdown {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f3f4f6;
        }

        .notification-unread {
            background-color: #eff6ff;
        }

        .badge-notification {
            font-size: 10px;
            transform: translate(-30%, -50%);
        }

        .logo-text {
            color: #1e40af;
        }

        .logo-text span {
            color: #f59e0b;
        }

        /* === Animacion de fuego del logo === */
        .phoenix-logo-block {
            position: relative;
        }

        .phoenix-logo-shell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            overflow: visible;
            padding: 0.2rem 0 0.1rem;
            isolation: isolate;
        }

        .phoenix-logo-wing-fire,
        .phoenix-logo-wing-embers {
            position: absolute;
            pointer-events: none;
            will-change: transform, opacity, filter;
        }

        .phoenix-logo-wing-fire {
            top: 8%;
            width: 62%;
            height: auto;
            z-index: 1;
            max-width: none;
            opacity: 0.92;
            filter:
                saturate(1.08)
                brightness(1.04);
            mix-blend-mode: screen;
        }

        .phoenix-logo-wing-fire--left {
            left: -13%;
            transform-origin: right 52%;
            animation: phoenix-wing-fire-left 3.4s ease-in-out infinite;
        }

        .phoenix-logo-wing-fire--right {
            right: -13%;
            transform-origin: left 52%;
            animation: phoenix-wing-fire-right 3.4s ease-in-out infinite;
        }

        .phoenix-logo-wing-embers {
            inset: 10% -6% 28%;
            z-index: 2;
            background:
                radial-gradient(circle at 15% 40%, rgba(255, 209, 74, 0.18) 0 0.9%, rgba(255, 209, 74, 0) 2.8%),
                radial-gradient(circle at 22% 31%, rgba(255, 127, 36, 0.18) 0 1%, rgba(255, 127, 36, 0) 3%),
                radial-gradient(circle at 79% 33%, rgba(255, 209, 74, 0.18) 0 0.9%, rgba(255, 209, 74, 0) 2.8%),
                radial-gradient(circle at 86% 42%, rgba(255, 127, 36, 0.18) 0 1%, rgba(255, 127, 36, 0) 3%),
                radial-gradient(circle at 18% 56%, rgba(255, 235, 160, 0.14) 0 0.9%, rgba(255, 235, 160, 0) 2.8%),
                radial-gradient(circle at 82% 57%, rgba(255, 235, 160, 0.14) 0 0.9%, rgba(255, 235, 160, 0) 2.8%);
            filter: blur(0.8px);
            opacity: 0.66;
            animation: phoenix-wing-embers 3.8s ease-in-out infinite;
        }

        .phoenix-logo-image {
            position: relative;
            z-index: 3;
            display: block;
            max-width: 100%;
            height: auto;
            filter: none;
            animation: none;
        }

        @keyframes phoenix-wing-fire-left {
            0%, 100% {
                opacity: 0.82;
                transform: scaleX(-1) translateX(0) translateY(5px) rotate(-3deg) scale(0.94);
            }

            50% {
                opacity: 1;
                transform: scaleX(-1) translateX(5%) translateY(-4px) rotate(-8deg) scale(1.03);
            }

            75% {
                opacity: 0.9;
                transform: scaleX(-1) translateX(2%) translateY(-1px) rotate(-6deg) scale(0.99);
            }
        }

        @keyframes phoenix-wing-fire-right {
            0%, 100% {
                opacity: 0.82;
                transform: translateX(0) translateY(5px) rotate(3deg) scale(0.94);
            }

            50% {
                opacity: 1;
                transform: translateX(5%) translateY(-4px) rotate(8deg) scale(1.03);
            }

            75% {
                opacity: 0.9;
                transform: translateX(2%) translateY(-1px) rotate(6deg) scale(0.99);
            }
        }

        @keyframes phoenix-wing-embers {
            0%, 100% {
                opacity: 0.48;
                transform: translateY(3px) scale(0.98);
            }

            50% {
                opacity: 0.82;
                transform: translateY(-5px) scale(1.04);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .phoenix-logo-wing-fire,
            .phoenix-logo-wing-embers {
                animation: none !important;
            }
        }

        textarea[name="actividad"],
        input[name="actividad"] {
            text-transform: uppercase;
        }

        .create-actions,
        .responsive-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            max-width: 100%;
            min-width: 0;
        }

        .create-actions--end,
        .responsive-actions--end {
            justify-content: flex-end;
        }

        .create-actions > .create-action,
        .responsive-actions > .responsive-action {
            flex: 0 1 auto;
        }

        .create-actions > .create-action.flex-1,
        .responsive-actions > .responsive-action.flex-1 {
            flex: 1 1 12rem;
        }

        .create-action,
        .responsive-action {
            --create-action-bg: linear-gradient(135deg, #2563eb, #1e40af);
            --create-action-bg-hover: linear-gradient(135deg, #1d4ed8, #1e3a8a);
            --create-action-border: rgba(30, 64, 175, 0.85);
            --create-action-color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            box-sizing: border-box;
            min-height: 2.75rem;
            min-width: 0;
            max-width: 100%;
            padding: 0.72rem 1.15rem;
            border: 1px solid var(--create-action-border);
            border-radius: 0.65rem;
            background: var(--create-action-bg);
            color: var(--create-action-color) !important;
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
            text-decoration: none;
            white-space: normal;
            overflow-wrap: anywhere;
            box-shadow: 0 12px 22px rgba(30, 64, 175, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
            touch-action: manipulation;
        }

        .create-action:hover,
        .responsive-action:hover {
            background: var(--create-action-bg-hover);
            box-shadow: 0 16px 26px rgba(30, 64, 175, 0.26);
            transform: translateY(-1px);
        }

        .create-action:focus-visible,
        .responsive-action:focus-visible {
            outline: 3px solid rgba(59, 130, 246, 0.35);
            outline-offset: 3px;
        }

        .create-action i,
        .create-action svg,
        .responsive-action i,
        .responsive-action svg {
            flex: 0 0 auto;
        }

        .create-action.hidden,
        .responsive-action.hidden {
            display: none !important;
        }

        .create-action--compact,
        .responsive-action--compact {
            min-height: 2.25rem;
            padding: 0.48rem 0.78rem;
            border-radius: 0.5rem;
            gap: 0.38rem;
            font-size: 0.75rem;
            box-shadow: 0 6px 12px rgba(30, 64, 175, 0.12);
        }

        .create-action--compact:hover,
        .responsive-action--compact:hover {
            box-shadow: 0 8px 16px rgba(30, 64, 175, 0.18);
        }

        .create-action--success,
        .responsive-action--success {
            --create-action-bg: #dcfce7;
            --create-action-bg-hover: #bbf7d0;
            --create-action-border: #86efac;
            --create-action-color: #166534;
            box-shadow: none;
        }

        .create-action--secondary,
        .responsive-action--secondary {
            --create-action-bg: #f8fafc;
            --create-action-bg-hover: #e2e8f0;
            --create-action-border: #cbd5e1;
            --create-action-color: #334155;
            box-shadow: none;
        }

        .create-action--danger,
        .responsive-action--danger {
            --create-action-bg: #dc2626;
            --create-action-bg-hover: #b91c1c;
            --create-action-border: #b91c1c;
            --create-action-color: #ffffff;
            box-shadow: none;
        }

        .create-action--on-dark,
        .responsive-action--on-dark {
            --create-action-bg: rgba(255, 255, 255, 0.14);
            --create-action-bg-hover: rgba(255, 255, 255, 0.24);
            --create-action-border: rgba(255, 255, 255, 0.34);
            --create-action-color: #ffffff;
            box-shadow: none;
            backdrop-filter: blur(8px);
        }

        @media (pointer: coarse) {
            .create-action,
            .responsive-action {
                min-height: 3rem;
            }

            .create-action--compact,
            .responsive-action--compact {
                min-height: 2.75rem;
            }
        }

        @media (max-width: 768px) {
            .create-actions,
            .responsive-actions {
                width: 100%;
                align-items: stretch;
            }

            .create-actions--end,
            .responsive-actions--end {
                justify-content: stretch;
            }

            .create-action,
            .responsive-action {
                width: 100%;
                min-width: 0;
                padding-inline: 1rem;
            }
        }
    </style>
</head>

<body class="bg-gray-100">

<div
    class="flex min-h-screen"
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
        class="sidebar fixed inset-y-0 left-0 z-50 w-64 flex flex-col h-screen shadow-sm lg:sticky lg:top-0 lg:z-auto lg:translate-x-0"
    >

        <!-- Logo -->
        <div class="px-6 py-6 border-b border-gray-200">
            <div class="flex flex-col items-center text-center phoenix-logo-block">
                <!-- Animacion de fuego del logo -->
                <div class="phoenix-logo-shell">
                    <img
                        src="{{ asset('images/logo-wing-fire.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="phoenix-logo-wing-fire phoenix-logo-wing-fire--left"
                    >
                    <img
                        src="{{ asset('images/logo-wing-fire.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="phoenix-logo-wing-fire phoenix-logo-wing-fire--right"
                    >
                    <span class="phoenix-logo-wing-embers" aria-hidden="true"></span>
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo Legado Ave Fénix"
                        class="w-30 h-30 mb-0 phoenix-logo-image"
                    >
                </div>
                <h1 class="text-sm font-semibold tracking-wide leading-tight logo-text">
                    LEGADO AB<br>
                    <span class="text-yellow-500 font-bold">FÉNIX</span>
                </h1>
            </div>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 px-4 py-6 space-y-2 text-sm overflow-y-auto">

            <a href="{{ route('dashboard') }}"
               @click="if (!isDesktop) sidebarOpen = false"
               aria-label="Ir al Dashboard"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('dashboard.index') || request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                <i class="fas fa-chart-line w-5 mr-3 text-gray-500"></i>
                Dashboard
            </a>

            <a href="{{ route('lavadora.dashboard') }}"
               @click="if (!isDesktop) sidebarOpen = false"
               aria-label="Dashboard de Lavadora"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('dashboard.lavadora') || request()->routeIs('lavadora.dashboard') || request()->routeIs('dashboard_lavadora') ? 'nav-active' : '' }}">
                <i class="fas fa-droplet w-5 mr-3 text-gray-500"></i>
                Lavadora
            </a>

            @auth
                @php
                    $mostrarPasteurizadora = $canSeePasteurizadora ?? ($canAccessPasteurizadora ?? false);
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
            @else
                <a href="{{ route('pasteurizadora.dashboard') }}"
                   @click="if (!isDesktop) sidebarOpen = false"
                   aria-label="Análisis de Pasteurizadora"
                   class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('dashboard.pasteurizadora') || request()->routeIs('pasteurizadora.dashboard') || request()->routeIs('dashboard_pasteurizadora') ? 'nav-active' : '' }}">
                    <i class="fas fa-thermometer-half w-5 mr-3 text-gray-500"></i>
                    Pasteurizadora
                </a>
            @endauth

            @php
                $usuarioActual = auth()->user();
                $bloquearReportesTecnico = $usuarioActual
                    && $usuarioActual->usesTechnicianAccessProfile();
            @endphp

            @if($bloquearReportesTecnico)
                <button type="button"
                        @click="
                            if (!isDesktop) sidebarOpen = false;
                            Swal.fire({
                                icon: 'info',
                                text: 'No cuentas con los permisos necesarios para visualizar los reportes.',
                                title: 'Acceso restringido',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#1e40af'
                            });
                        "
                        aria-label="Reportes"
                        class="nav-link flex items-center w-full text-left px-4 py-3 rounded-lg">
                    <i class="fas fa-chart-bar w-5 mr-3 text-gray-500"></i>
                    Reportes
                </button>
            @else
                <a href="{{ route('reportes.index') }}"
                   @click="if (!isDesktop) sidebarOpen = false"
                   aria-label="Reportes"
                   class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('reportes.*') ? 'nav-active' : '' }}">
                    <i class="fas fa-chart-bar w-5 mr-3 text-gray-500"></i>
                    Reportes
                </a>
            @endif

            @auth
                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.users.index') }}"
                       @click="if (!isDesktop) sidebarOpen = false"
                       aria-label="Gestion de usuarios"
                       class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('admin.users.*') ? 'nav-active' : '' }}">
                        <i class="fas fa-user-shield w-5 mr-3 text-gray-500"></i>
                        Gestion de usuarios
                    </a>

                    <a href="{{ route('admin.costos.index') }}"
                       @click="if (!isDesktop) sidebarOpen = false"
                       aria-label="Control de gastos"
                       class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('admin.costos.*') ? 'nav-active' : '' }}">
                        <i class="fas fa-coins w-5 mr-3 text-gray-500"></i>
                        Control de gastos
                    </a>
                @endif
            @endauth
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
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">

        <!-- Header -->
        <header class="bg-white shadow-sm border-b px-4 sm:px-6 py-4">
            <div class="flex justify-between items-center gap-4">

                <div class="flex items-center space-x-3 min-w-0">
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

                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- NOTIFICACIONES DROPDOWN -->
                    @auth
                    @php
                        $notificationVisibility = app(\App\Services\NotificationVisibilityService::class);
                        $availableNotifications = $notificationVisibility->availableNotificationsFor(auth()->user());
                        $notificationItems = $availableNotifications->take(10);
                        $notificationsCount = $availableNotifications->count();
                        $unreadCount = $notificationVisibility->availableUnreadNotificationsCountFor(auth()->user());
                    @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                @click.away="open = false"
                                aria-label="Notificaciones"
                                class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition relative">
                            <i class="fas fa-bell text-gray-600"></i>
                            <span id="notification-badge"
                                  class="absolute top-0 right-0 inline-flex items-center justify-center px-1 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full {{ $unreadCount > 0 ? '' : 'hidden' }}">
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
                             class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-md shadow-lg overflow-hidden z-50 border border-gray-200"
                             style="display: none;">

                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700">Notificaciones</span>
                                <form id="notification-read-all-form" action="{{ route('notifications.read-all') }}" method="POST" class="inline {{ $unreadCount > 0 ? '' : 'hidden' }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">
                                        Marcar todas como leídas
                                    </button>
                                </form>
                            </div>

                            <div id="notifications-list" class="max-h-96 overflow-y-auto">
                                @forelse($notificationItems as $notification)
                                    @php($notificationOpenUrl = route('notifications.open', $notification->id, false))
                                    <a href="{{ $notificationOpenUrl }}"
                                       class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 {{ $notification->read_at ? '' : 'bg-blue-50' }}">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
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
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                                        <i class="fas fa-tools"></i>
                                                        Parte: {{ $notification->data['area_pasteurizadora_label'] }}
                                                    </span>
                                                @endif
                                                <p class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-4 py-6 text-center text-gray-500">
                                        <i class="fas fa-bell-slash text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-sm">No hay notificaciones</p>
                                    </div>
                                @endforelse
                            </div>

                            @if($notificationsCount > 0)
                                <div id="notifications-view-all-wrapper" class="px-4 py-2 bg-gray-50 border-t border-gray-200 text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-xs text-blue-600 hover:text-blue-800">
                                        Ver todas las notificaciones
                                    </a>
                                </div>
                            @else
                                <div id="notifications-view-all-wrapper" class="hidden px-4 py-2 bg-gray-50 border-t border-gray-200 text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-xs text-blue-600 hover:text-blue-800">
                                        Ver todas las notificaciones
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
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
                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-circle text-blue-600"></i>
                                    Ver perfil
                                </a>

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
        <main class="flex-1 overflow-auto p-4 sm:p-6">
            @yield('content')
        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const notificationReadUrlTemplate = @json(route('notifications.read', ['id' => '__ID__'], false));
const notificationsUnreadCountUrl = @json(route('notifications.unread-count', [], false));

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
    empty.className = 'px-4 py-6 text-center text-gray-500';

    const icon = document.createElement('i');
    icon.className = 'fas fa-bell-slash text-gray-400 text-2xl mb-2';

    const text = document.createElement('p');
    text.className = 'text-sm';
    text.textContent = 'No hay notificaciones';

    empty.append(icon, text);

    return empty;
}

function notificationItemNode(item) {
    const targetUrl = item.open_url || item.url || '#';
    const link = document.createElement('a');
    link.href = targetUrl;
    link.className = 'block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 ' + (item.is_read ? '' : 'bg-blue-50');

    const row = document.createElement('div');
    row.className = 'flex items-start space-x-3';

    const iconWrap = document.createElement('div');
    iconWrap.className = 'flex-shrink-0';
    const icon = document.createElement('i');
    icon.className = notificationIconClasses(item.prioridad || 'baja');
    iconWrap.appendChild(icon);

    const body = document.createElement('div');
    body.className = 'flex-1 min-w-0';

    const messageRow = document.createElement('div');
    messageRow.className = 'flex items-start justify-between gap-2';

    const message = document.createElement('p');
    message.className = 'text-sm text-gray-900 mb-1';
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
        area.className = 'inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700';

        const areaIcon = document.createElement('i');
        areaIcon.className = 'fas fa-tools';

        const areaText = document.createTextNode(' Parte: ' + item.area_pasteurizadora_label);
        area.append(areaIcon, areaText);
        body.appendChild(area);
    }

    const time = document.createElement('p');
    time.className = 'text-xs text-gray-500';
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
    const readAllForm = document.getElementById('notification-read-all-form');
    const viewAllWrapper = document.getElementById('notifications-view-all-wrapper');

    if (badge) {
        if (data.count > 0) {
            badge.textContent = data.count > 9 ? '9+' : data.count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    if (readAllForm) {
        readAllForm.classList.toggle('hidden', data.count <= 0);
    }

    if (viewAllWrapper) {
        viewAllWrapper.classList.toggle('hidden', (data.notifications_count || 0) <= 0);
    }
}

function refreshNotifications() {
    if (!document.getElementById('notification-badge') && !document.getElementById('notifications-list')) {
        return;
    }

    fetch(notificationsUnreadCountUrl, {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        updateNotificationControls(data);
        renderNotifications(data.items || []);
    })
    .catch(error => {
        console.error('Error al actualizar notificaciones:', error);
    });
}

refreshNotifications();
setInterval(refreshNotifications, 30000);

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
