<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEGADO AB FÉNIX - Sistema de Gestión</title>

    @include('layouts.partials.site-icons')

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

        body,
        .sidebar,
        header,
        .card,
        .notification-elegant-panel,
        .global-search-panel {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
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

        .notification-bell-button {
            transform: translateZ(0);
        }

        .notification-elegant-panel,
        .global-search-panel {
            border: 1px solid rgba(209, 213, 219, 0.9);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        }

        .notification-elegant-item {
            position: relative;
        }

        .notification-elegant-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.75rem;
            bottom: 0.75rem;
            width: 3px;
            border-radius: 999px;
            background: transparent;
        }

        .notification-elegant-item.is-unread::before {
            background: linear-gradient(180deg, #2563eb, #60a5fa);
        }

        .global-search-button {
            min-height: 2.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .global-search-button:hover,
        .global-search-button.is-open {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .global-search-result {
            transition: background-color 0.15s ease, transform 0.15s ease;
        }

        .global-search-result:hover,
        .global-search-result.is-active {
            background: #eff6ff;
            transform: translateX(2px);
        }

        .notification-bell-icon {
            display: inline-block;
            transform-origin: 50% 0;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .notification-bell-button.has-unread:not(.is-open) .notification-bell-icon {
            animation: notification-bell-ring 7s cubic-bezier(0.36, 0.07, 0.19, 0.97) infinite;
            will-change: transform;
        }

        .notification-bell-button.has-unread:not(.is-open) .notification-badge-pulse {
            animation: notification-badge-pulse 2.6s ease-out infinite;
        }

        @keyframes notification-bell-ring {
            0%, 18%, 100% {
                transform: rotate(0deg);
            }

            2% {
                transform: rotate(14deg);
            }

            4% {
                transform: rotate(-12deg);
            }

            6% {
                transform: rotate(10deg);
            }

            9% {
                transform: rotate(-8deg);
            }

            12% {
                transform: rotate(5deg);
            }

            15% {
                transform: rotate(-3deg);
            }
        }

        @keyframes notification-badge-pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
            }

            20% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.3);
            }

            70% {
                box-shadow: 0 0 0 7px rgba(220, 38, 38, 0);
            }
        }

        .logo-text {
            color: #1e40af;
        }

        .logo-text span {
            color: #f59e0b;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            max-width: 100%;
            overflow-x: hidden;
        }

        img,
        video,
        canvas,
        svg {
            max-width: 100%;
        }

        .app-shell {
            width: 100%;
            min-width: 0;
        }

        .app-shell-main,
        .app-content {
            width: 100%;
            min-width: 0;
            max-width: 100%;
        }

        .app-content {
            overflow-x: hidden;
        }

        .app-content :where(.grid, .flex) > * {
            min-width: 0;
        }

        .app-content :where(input:not([type="checkbox"]):not([type="radio"]):not([type="range"]), select, textarea) {
            max-width: 100%;
            min-width: 0;
        }

        .app-content :where(.overflow-x-auto, .table-wrapper, .table-container, .table-shell, .responsive-table, .industrial-table-container, .etq-table-wrapper) {
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .app-content :where(.overflow-x-auto, .table-wrapper, .table-container, .table-shell, .responsive-table, .industrial-table-container, .etq-table-wrapper) > table {
            min-width: 100%;
        }

        .app-content :where(td, th) {
            overflow-wrap: anywhere;
        }

        .app-content :where(.card, section, article, form) {
            max-width: 100%;
        }

        .logo-sequence {
            position: relative;
            display: inline-block;
            line-height: 0;
        }

        .logo-sequence-base {
            display: block;
        }

        .logo-sequence-frame {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            opacity: 0;
            pointer-events: none;
            user-select: none;
            backface-visibility: hidden;
            transform: translateZ(0);
            will-change: opacity;
            animation-duration: 480ms;
            animation-iteration-count: infinite;
            animation-timing-function: steps(1, end);
            animation-fill-mode: both;
        }

        .logo-sequence-frame--2 {
            animation-name: logo-sequence-frame-2;
        }

        .logo-sequence-frame--3 {
            animation-name: logo-sequence-frame-3;
        }

        .logo-sequence-frame--4 {
            animation-name: logo-sequence-frame-4;
        }

        @keyframes logo-sequence-frame-2 {
            0%, 24.99%, 50%, 100% {
                opacity: 0;
            }

            25%, 49.99% {
                opacity: 1;
            }
        }

        @keyframes logo-sequence-frame-3 {
            0%, 49.99%, 75%, 100% {
                opacity: 0;
            }

            50%, 74.99% {
                opacity: 1;
            }
        }

        @keyframes logo-sequence-frame-4 {
            0%, 74.99%, 100% {
                opacity: 0;
            }

            75%, 99.99% {
                opacity: 1;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .logo-sequence-frame,
            .notification-bell-button.has-unread .notification-bell-icon,
            .notification-bell-button.has-unread .notification-badge-pulse {
                animation: none;
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
            .app-shell-main {
                padding: 0.75rem;
            }

            .app-content :where(.overflow-x-auto, .table-wrapper, .table-container, .table-shell, .responsive-table, .industrial-table-container, .etq-table-wrapper) {
                margin-inline: -0.75rem;
                padding-inline: 0.75rem;
            }

            .app-content :where(div:not(.overflow-x-auto):not(.table-wrapper):not(.table-container):not(.table-shell):not(.responsive-table):not(.industrial-table-container):not(.etq-table-wrapper)) > table {
                display: block;
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

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
                <img
                    src="{{ asset('images/logo1.png') }}"
                    alt=""
                    aria-hidden="true"
                    class="logo-sequence-base w-30 h-30"
                >
                    <img
                        src="{{ asset('images/logo2.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="logo-sequence-frame logo-sequence-frame--2"
                    >
                    <img
                        src="{{ asset('images/logo3.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="logo-sequence-frame logo-sequence-frame--3"
                    >
                    <img
                        src="{{ asset('images/logo4.png') }}"
                        alt=""
                        aria-hidden="true"
                        class="logo-sequence-frame logo-sequence-frame--4"
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
            @php
                $usuarioActual = auth()->user();
                $puedeVerDashboardPrincipal = $usuarioActual?->canUseCustomPermission('ver dashboard principal') ?? false;
                $puedeVerDashboardLavadora = ($usuarioActual?->canUseCustomPermission('ver dashboard lavadoras') ?? false)
                    && ($usuarioActual?->canAccessModule(\App\Models\User::MODULE_LAVADORA) ?? false);
                $puedeVerDashboardEtiquetadora = ($usuarioActual?->canUseCustomPermission('ver dashboard etiquetadoras') ?? false)
                    && ($usuarioActual?->canAccessModule(\App\Models\User::MODULE_ETIQUETADORA) ?? false);
                $puedeVerDashboardPasteurizadora = $usuarioActual?->canUseCustomPermission('ver dashboard pasteurizadoras') ?? false;
                $puedeVerReportes = $usuarioActual?->canUseCustomPermission('ver reportes') ?? false;
                $puedeGestionarUsuarios = $usuarioActual?->canUseCustomPermission('gestionar usuarios') ?? false;
                $puedeVerObservabilidadIa = $usuarioActual?->canViewAiObservability() ?? false;
            @endphp

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
                    <div class="relative" x-data="globalSearch()" @click.away="close()" @keydown.escape.window="close()">
                        <button type="button"
                                @click="toggle()"
                                :class="{ 'is-open': open }"
                                aria-label="Busqueda global"
                                class="global-search-button inline-flex items-center justify-center gap-2 rounded-full bg-white px-3 py-2 text-sm font-semibold text-gray-600 transition">
                            <i class="fas fa-magnifying-glass text-sm"></i>
                            <span class="hidden md:inline">Buscar</span>
                        </button>

                        <div x-show="open"
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

                            <div class="max-h-[26rem] overflow-y-auto py-2">
                                <template x-if="loading">
                                    <div class="flex items-center gap-3 px-4 py-4 text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-blue-600"></i>
                                        Buscando...
                                    </div>
                                </template>

                                <template x-if="error">
                                    <div class="px-4 py-4 text-sm font-medium text-red-600" x-text="error"></div>
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
                    @endauth

                    <!-- NOTIFICACIONES DROPDOWN -->
                    @auth
                    @php
                        $notificationVisibility = app(\App\Services\NotificationVisibilityService::class);
                        $availableNotifications = $notificationVisibility->availableNotificationsFor(auth()->user());
                        $notificationItems = $availableNotifications->take(10);
                        $notificationsCount = $availableNotifications->count();
                        $unreadCount = $notificationVisibility->availableUnreadNotificationsCountFor(auth()->user());
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
        <main class="app-shell-main app-content flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6">
            @yield('content')
        </main>

    </div>

</div>

@auth
    @include('layouts.partials.assistant-chat')
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
