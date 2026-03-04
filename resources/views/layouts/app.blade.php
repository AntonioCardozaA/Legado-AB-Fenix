<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEGADO AVE FÉNIX - Sistema de Gestión</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- ChartJS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --accent-yellow: #f59e0b;
            --accent-red: #ef4444;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e40af 0%, #1e3a8a 100%);
        }

        .nav-link {
            transition: all 0.2s ease-in-out;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            padding-left: 1.25rem;
        }

        .nav-active {
            background-color: rgba(245, 158, 11, 0.25);
            border-left: 4px solid var(--accent-yellow);
            font-weight: 600;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        /* Estilos para el dropdown de notificaciones */
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
    </style>
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="sidebar w-64 text-white flex flex-col h-screen sticky top-0">

        <!-- Logo -->
        <div class="px-6 py-6 border-b border-blue-800">
            <div class="flex flex-col items-center text-center">
                <img 
                    src="{{ asset('images/logo.png') }}" 
                    alt="Logo Legado Ave Fénix"
                    class="w-20 h-20 mb-3 drop-shadow-lg"
                >
                <h1 class="text-sm font-semibold tracking-wide leading-tight">
                    LEGADO AVE<br>
                    <span class="text-yellow-400 font-bold">FÉNIX</span>
                </h1>
            </div>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 px-4 py-6 space-y-2 text-sm">
            <a href="{{ route('dashboard') }}"
               aria-label="Ir al Dashboard"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                Dashboard
            </a>
            <a href="{{ route('analisis.index') }}"
               aria-label="Análisis General"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('analisis.*') ? 'nav-active' : '' }}">
                <i class="fas fa-clipboard-list w-5 mr-3"></i>
                Análisis General
            </a>
            <a href="{{ route('lavadora.dashboard') }}"
                aria-label="Dashboard de Lavadora"
                class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('lavadora.dashboard') ? 'nav-active' : '' }}">
                <i class="fas fa-puzzle-piece w-5 mr-3"></i>
                Lavadora
            </a>
            <a href="{{ route('analisis-pasteurizadora.index') }}"
               aria-label="Análisis de Pasteurizadora"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('analisis-pasteurizadora.*') ? 'nav-active' : '' }}">
                <i class="fas fa-puzzle-piece w-5 mr-3"></i>
                Pasteurizadora
            </a>
            <a href="{{ route('lineas.index') }}"
               aria-label="Líneas"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('lineas.*') ? 'nav-active' : '' }}">
                <i class="fas fa-industry w-5 mr-3"></i>
                Líneas
            </a>
            <a href="{{ route('reportes.index') }}"
               aria-label="Reportes"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('reportes.*') ? 'nav-active' : '' }}">
                <i class="fas fa-chart-bar w-5 mr-3"></i>
                Reportes
            </a>
        </nav>
    </aside>

    <!-- CONTENIDO -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <header class="bg-white shadow-sm border-b px-6 py-4">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">
                    @yield('title', 'Legado Ave Fénix')
                </h2>

                <div class="flex items-center space-x-4">
                    <!-- NOTIFICACIONES DROPDOWN -->
                    @auth
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                @click.away="open = false"
                                aria-label="Notificaciones"
                                class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition relative">
                            <i class="fas fa-bell text-gray-600"></i>
                            @php
                                $unreadCount = auth()->user()->unreadNotifications->count();
                            @endphp
                            @if($unreadCount > 0)
                                <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Dropdown menu -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-50 border border-gray-200"
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
                                @forelse(auth()->user()->notifications()->take(10)->get() as $notification)
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
                            
                            @if(auth()->user()->notifications()->count() > 10)
                                <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 text-center">
                                    <a href="{{ route('profile.notifications') }}" class="text-xs text-blue-600 hover:text-blue-800">
                                        Ver todas las notificaciones
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endauth

                    <span class="text-sm text-gray-600">
                        {{ auth()->check() ? auth()->user()->name : 'Invitado' }}
                    </span>

                    <button aria-label="Perfil de usuario"
                            class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition">
                        <i class="fas fa-user text-gray-600"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main -->
        <main class="flex-1 overflow-auto p-6">
            @yield('content')
        </main>

    </div>

</div>

<!-- Alpine.js para el dropdown -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- SweetAlert2 -->
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
            window.location.href = url;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = url;
    });
}

// Actualizar contador de notificaciones cada 30 segundos
setInterval(function() {
    fetch('/notifications/unread-count', {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.fa-bell + span');
        if (badge) {
            if (data.count > 0) {
                badge.textContent = data.count > 9 ? '9+' : data.count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    });
}, 30000);
</script>

@hasSection('scripts')
    @yield('scripts')
@endif

</body>
</html>