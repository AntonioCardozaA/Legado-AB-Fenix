@extends('layouts.app')

@section('title', 'Menu | Etiquetadora')

@section('content')
@php
    $usuarioActual = auth()->user();
    $puedeVerAnalisisEtiquetadora = $usuarioActual?->canUseCustomPermission('ver analisis etiquetadora') ?? false;
    $puedeVerPlanesEtiquetadora = $usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_ETIQUETADORA) ?? false;
@endphp
<div class="etiquetadora-menu min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 animate-fade-in">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="menu-hero-brand">
                    <div class="menu-hero-icon">
                            <img src="{{ asset('images/icono-maquina.png') }}"
                                 alt="Icono de Etiquetadora"
                                 class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="menu-hero-copy">
                        <div class="menu-hero-accent"></div>
                        <h1 class="menu-hero-title text-gray-800">
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-800 to-gray-600">
                                ETIQUETADORA
                            </span>
                        </h1>
                    </div>
                </div>

                <div class="hidden sm:block">
                    <div class="bg-white/80 backdrop-blur-sm px-4 py-2 rounded-2xl shadow-sm border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium text-gray-700">Legado Ave Fenix</span>
                            </div>
                            <div class="h-4 w-px bg-gray-300"></div>
                            <span class="text-sm text-gray-500">{{ now()->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            @if($puedeVerAnalisisEtiquetadora)
            <a href="{{ route('analisis-etiquetadora.index') }}"
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>

                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-chart-pie text-2xl sm:text-3xl"></i>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-3">
                            ANALISIS ETIQUETADORA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Registra y consulta los analisis de componentes.
                        </p>

                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @if($puedeVerAnalisisEtiquetadora)
            <a href="{{ route('analisis-etiquetadora.historial') }}"
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>

                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-history text-2xl sm:text-3xl"></i>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-3">
                            HISTORICO DE REVISADOS
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza registros de componentes revisados.
                        </p>

                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @if($puedeVerPlanesEtiquetadora)
            <a href="{{ route('plan-accion.index', ['tipo' => 'etiquetadora']) }}"
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>

                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-tasks text-2xl sm:text-3xl"></i>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-3">
                            PLAN DE ACCION
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Administracion y seguimiento de acciones preventivas.
                        </p>

                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @unless($puedeVerAnalisisEtiquetadora || $puedeVerPlanesEtiquetadora)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm font-semibold text-amber-800 md:col-span-2 lg:col-span-3">
                    No tiene vistas disponibles en este modulo. Solicite al administrador la asignacion del permiso correspondiente.
                </div>
            @endunless
        </div>
    </div>
</div>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }

    .etiquetadora-menu * {
        box-sizing: border-box;
        min-width: 0;
    }

    .etiquetadora-menu h1,
    .etiquetadora-menu h3,
    .etiquetadora-menu p,
    .etiquetadora-menu span {
        overflow-wrap: anywhere;
    }

    .etiquetadora-menu .menu-hero-brand {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .etiquetadora-menu .menu-hero-icon {
        display: flex;
        width: clamp(4.75rem, 22vw, 8rem);
        height: clamp(4.75rem, 22vw, 8rem);
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .etiquetadora-menu .menu-hero-copy {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        flex: 1 1 auto;
        min-width: 0;
        max-width: 100%;
    }

    .etiquetadora-menu .menu-hero-accent {
        width: 0.45rem;
        height: clamp(2.8rem, 12vw, 4rem);
        flex: 0 0 0.45rem;
        border-radius: 9999px;
        background: linear-gradient(to bottom, #1f2937, #4b5563);
    }

    .etiquetadora-menu .menu-hero-title {
        margin: 0;
        flex: 1 1 auto;
        max-width: 100%;
        font-size: clamp(1.55rem, 6vw, 3rem);
        line-height: 0.95;
        letter-spacing: -0.04em;
        text-wrap: balance;
    }

    .etiquetadora-menu .menu-hero-title span {
        display: block;
    }

    @media (min-width: 640px) {
        .etiquetadora-menu .menu-hero-brand {
            gap: 1rem;
        }

        .etiquetadora-menu .menu-hero-title {
            line-height: 1;
        }
    }

    @media (max-width: 480px) {
        .etiquetadora-menu .menu-hero-brand {
            gap: 0.65rem;
        }

        .etiquetadora-menu .menu-hero-icon {
            width: 4.2rem;
            height: 4.2rem;
        }

        .etiquetadora-menu .menu-hero-copy {
            gap: 0.6rem;
        }

        .etiquetadora-menu .menu-hero-accent {
            height: 2.45rem;
        }

        .etiquetadora-menu a.group {
            border-radius: 1rem;
        }
    }
</style>
@endsection
