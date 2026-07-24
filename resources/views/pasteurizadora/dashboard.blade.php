@extends('layouts.app')

@section('title', 'Menú | Pasteurizadora')

@section('content')
@php
    $usuarioActual = auth()->user();
    $puedeVerMecanicaPasteurizadora = $usuarioActual?->canAccessPasteurizadoraArea(\App\Models\AnalisisPasteurizadora::AREA_MECANICA) ?? false;
    $puedeVerCentralHidraulica = $usuarioActual?->canAccessPasteurizadoraArea(\App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA) ?? false;
    $puedeVerPlanesPasteurizadora = $puedeVerMecanicaPasteurizadora
        && ($usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_PASTEURIZADORA) ?? false);
    $puedeVerTendenciasPasteurizadora = ($usuarioActual?->canAccessModule(\App\Models\User::MODULE_PASTEURIZADORA) ?? false)
        && ($usuarioActual?->canUseCustomPermission('ver tendencias pasteurizadora') ?? false);
@endphp
<div class="pasteur-menu min-h-screen overflow-x-hidden bg-gradient-to-br from-gray-50 to-gray-100 py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- HEADER MEJORADO --}}
        <div class="mb-10 animate-fade-in">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="mb-2 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                        <!-- Imagen a la izquierda (icono de pasteurizadora) -->
                        <div class="mb-2 flex h-24 w-24 items-center justify-center sm:mb-4 sm:h-32 sm:w-32">
                            <img src="{{ asset('images/icono_pas.png') }}" 
                                 alt="Icono de Pasteurizadora" 
                                 class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <!-- Barra decorativa -->
                        <div class="h-10 w-2 bg-gradient-to-b from-gray-800 to-gray-600 rounded-full"></div>
                        
                        <h1 class="break-words text-3xl font-black tracking-tight text-gray-800 sm:text-4xl">
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-800 to-gray-600">
                                PASTEURIZADORA
                            </span>
                        </h1>
                    </div>
                </div>
                {{-- BADGE DE ESTADO --}}
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

        {{-- GRID DE OPCIONES MEJORADO CON COLOR RGB 31 35 72 --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            
            {{-- ANALISIS PASTEURIZADORA --}}
            @if($puedeVerMecanicaPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                {{-- Barra superior con el color especificado --}}
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                
                {{-- Efecto de brillo hover con el color especificado --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        {{-- Icono con animación usando el color especificado --}}
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-chart-pie text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        {{-- Contenido --}}
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ANÁLISIS PASTEURIZADORA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Registra y consulta los análisis de componentes
                        </p>
                        
                        {{-- Indicador de acción con el color especificado --}}
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- HISTORICO (reubicado en la posición que dejó ELONGACIÓN) --}}
            @endif

            @if($puedeVerMecanicaPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.historico-revisados') }}" 
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
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            HISTÓRICO DE REVISADOS
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza registros de componentes revisados
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            @endif

            @if($puedeVerCentralHidraulica)
            {{-- ANALISIS PASTEURIZADORA CENTRAL HIDRAULICA --}}
            <a href="{{ route('pasteurizadora.central-hidraulica.index') }}"
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">

                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>

                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-oil-can text-2xl sm:text-3xl"></i>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ANÁLISIS PASTEURIZADORA CENTRAL HIDRÁULICA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Registra y consulta análisis de Central Hidráulica
                        </p>

                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- HISTORICO DE REVISADOS CENTRAL HIDRAULICA --}}
            <a href="{{ route('pasteurizadora.central-hidraulica.historico-revisados') }}"
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">

                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>

                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-clipboard-check text-2xl sm:text-3xl"></i>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            HISTÓRICO DE REVISADOS CENTRAL HIDRÁULICA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza registros revisados de Central Hidráulica
                        </p>

                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            {{-- PLAN DE ACCION --}}
            @if($puedeVerPlanesPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}" 
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
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            PLAN DE ACCIÓN
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Administración y seguimiento de acciones preventivas
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- ANALISIS 52-12-4 --}}
            @endif

            @if($puedeVerTendenciasPasteurizadora)
            <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-search text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ANÁLISIS 52 - 12 - 4 / 30 - 14 - 7
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualizacion automatica de tendencia de daños
                        </p>
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @unless($puedeVerMecanicaPasteurizadora || $puedeVerCentralHidraulica || $puedeVerPlanesPasteurizadora || $puedeVerTendenciasPasteurizadora)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm font-semibold text-amber-800 md:col-span-2 lg:col-span-3">
                    No tiene vistas disponibles en este modulo. Solicite al administrador la asignacion del permiso correspondiente.
                </div>
            @endunless
        </div>
    </div>
</div>

{{-- ANIMACIONES PERSONALIZADAS --}}
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

    .pasteur-menu * {
        box-sizing: border-box;
        min-width: 0;
    }

    .pasteur-menu h1,
    .pasteur-menu h3,
    .pasteur-menu p,
    .pasteur-menu span {
        overflow-wrap: anywhere;
    }

    @media (max-width: 480px) {
        .pasteur-menu h1 {
            font-size: 1.85rem;
            line-height: 1.1;
        }

        .pasteur-menu a.group {
            border-radius: 1rem;
        }
    }
    
    /* Mejoras de accesibilidad */
    a:focus-visible {
        outline: 3px solid rgba(31, 35, 72, 0.5);
        outline-offset: 2px;
    }

    /* Estilo para el hover del título */
    .group:hover h3 {
        color: rgb(245, 192, 37) !important;
    }
</style>
@endsection
