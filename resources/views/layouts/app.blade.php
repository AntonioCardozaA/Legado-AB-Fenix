<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEGADO AVE FÉNIX - Sistema de Gestión</title>

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
            <div class="flex flex-col items-center text-center">
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="Logo Legado Ave Fénix"
                    class="w-30 h-30 mb-0 drop-shadow-lg"
                >
                <h1 class="text-sm font-semibold tracking-wide leading-tight logo-text">
                    LEGADO AVE<br>
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
                @if($canAccessPasteurizadora ?? false)
                @if(auth()->user()->hasRole(\App\Models\User::ROLE_TECNICO) && !auth()->user()->hasAnyRole(\App\Models\User::elevatedMaintenanceRoles()))
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

            <a href="{{ route('reportes.index') }}"
               @click="if (!isDesktop) sidebarOpen = false"
               aria-label="Reportes"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('reportes.*') ? 'nav-active' : '' }}">
                <i class="fas fa-chart-bar w-5 mr-3 text-gray-500"></i>
                Reportes
            </a>
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
                        @yield('title', 'Legado Ave Fénix')
                    </h2>
                </div>

                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- NOTIFICACIONES DROPDOWN -->
                    @auth
                    @php
                        $notificationItems = auth()->user()->notifications()->latest()->limit(10)->get();
                        $notificationsCount = auth()->user()->notifications()->count();
                        $unreadCount = auth()->user()->unreadNotifications()->count();
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
                                @if($unreadCount > 0)
                                    <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">
                                            Marcar todas como leídas
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="max-h-96 overflow-y-auto">
                                @forelse($notificationItems as $notification)
                                    <a href="{{ $notification->data['url'] ?? '#' }}"
                                       class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 {{ $notification->read_at ? '' : 'bg-blue-50' }}"
                                       onclick="event.preventDefault(); markAsRead('{{ $notification->id }}', '{{ $notification->data['url'] ?? '#' }}')">
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
                                                <p class="text-sm text-gray-900 mb-1">{!! $notification->data['mensaje'] ?? $notification->data['message'] ?? 'Nueva notificación' !!}</p>
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

                            @if($notificationsCount > 10)
                                <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 text-center">
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
function markAsRead(notificationId, url) {
    fetch(`/notifications/${notificationId}/read`, {
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

setInterval(function() {
    fetch('/notifications/unread-count', {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const badge = document.getElementById('notification-badge');

        if (!badge) {
            return;
        }

        if (data.count > 0) {
            badge.textContent = data.count > 9 ? '9+' : data.count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    });
}, 30000);
</script>

@hasSection('scripts')
    @yield('scripts')
@endif

@if(session('pasteurizadora_bloqueada'))
<script>
    Swal.fire({
        icon: 'info',
        text: '{{ session('pasteurizadora_bloqueada') }}',
        title: 'Acceso restringido',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#1e40af'
    });
</script>
@endif

</body>
</html>
