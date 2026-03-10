@extends('layouts.app')

@section('title', 'Dashboard | Pasteurizadora')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- HEADER MEJORADO --}}
        <div class="mb-10 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <!-- Imagen a la izquierda (icono de pasteurizadora) -->
                        <div class="w-32 h-32 mb-4 flex items-center justify-center">
                            <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                                 alt="Icono de Pasteurizadora" 
                                 class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <!-- Barra decorativa -->
                        <div class="h-10 w-2 bg-gradient-to-b from-gray-800 to-gray-600 rounded-full"></div>
                        
                        <h1 class="text-4xl font-black text-gray-800 tracking-tight">
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
                                <div class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
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
            <a href="{{ route('analisis-pasteurizadora.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                {{-- Barra superior con el color especificado --}}
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                
                {{-- Efecto de brillo hover con el color especificado --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        {{-- Icono con animación usando el color especificado --}}
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-chart-pie text-3xl"></i>
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
                        <div class="mt-6 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- HISTORICO (reubicado en la posición que dejó ELONGACIÓN) --}}
            <a href="{{ route('analisis-pasteurizadora.historico-revisados') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-history text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            HISTÓRICO DE REVISADOS
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza registros de componentes revisados
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- PLAN DE ACCION --}}
            <a href="{{ route('analisis-pasteurizadora.plan-accion') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-tasks text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            PLAN DE ACCIÓN
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Administración y seguimiento de acciones preventivas
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- ANALISIS 52-12-4 --}}
            <a href="{{ route('analisis-pasteurizadora.analisis-tendencia-mensual') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-search text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ANÁLISIS 52 - 12 - 4
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualización de análisis de índice de daños
                        </p>
                        <div class="mt-6 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- FOOTER CON ESTADÍSTICAS RÁPIDAS (OPCIONAL) --}}
        <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-4 border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-xl" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Análisis este mes</p>
                        <p class="text-xl font-bold text-gray-800">156</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-4 border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-xl" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                        <i class="fas fa-exclamation-triangle text-white"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Componentes críticos</p>
                        <p class="text-xl font-bold text-gray-800">12</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-4 border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-xl" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Acciones completadas</p>
                        <p class="text-xl font-bold text-gray-800">89%</p>
                    </div>
                </div>
            </div>
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