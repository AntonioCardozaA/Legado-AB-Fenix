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
    </style>
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="sidebar w-52 text-white flex flex-col h-screen sticky top-0">

        <!-- Logo -->
        <div class="px-6 py-6 border-b border-blue-800">
            <div class="flex flex-col items-center text-center">
                <img 
                    src="{{ asset('images/logo.png') }}" 
                    alt="Logo"
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

            <a href="{{ route('analisis-componentes.index') }}"
               aria-label="Análisis de Componentes"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('analisis-componentes.*') ? 'nav-active' : '' }}">
                <i class="fas fa-puzzle-piece w-5 mr-3"></i>
                Análisis de Componentes
            </a>

            <a href="{{ route('elongaciones.index') }}"
               aria-label="Elongación"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('elongaciones.*') ? 'nav-active' : '' }}">
                <i class="fas fa-arrows-alt-h w-5 mr-3"></i>
                Elongación
            </a>
            <!--
            <a href="{{ route('paros.index') }}"
               aria-label="Paros de Máquina"
               class="nav-link flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('paros.*') ? 'nav-active' : '' }}">
                <i class="fas fa-triangle-exclamation w-5 mr-3"></i>
                Paros de Máquina
            </a>
            -->
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
                    @yield('title')
                </h2>

                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        {{ auth()->user()->name }}
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

@yield('scripts')

</body>
</html>
