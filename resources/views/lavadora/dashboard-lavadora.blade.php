@extends('layouts.app')

@section('title', 'Dashboard | Lavadora')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- HEADER MEJORADO --}}
        <div class="mb-10 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
            <div class="flex items-center gap-3 mb-2">
                <!-- Imagen a la izquierda -->
                <div class="w-32 h-32 mb-4 flex items-center justify-center">
                            <img src="{{ asset('images/icono-maquina.png') }}" 
                                 alt="Icono de Maquinaria" 
                                 class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        </div>
                <!-- Barra decorativa (opcional, puedes mantenerla o quitarla) -->
                <div class="h-10 w-2 bg-gradient-to-b from-gray-800 to-gray-600 rounded-full"></div>
                
                <h1 class="text-4xl font-black text-gray-800 tracking-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-800 to-gray-600">
                        LAVADORA
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

        {{-- GRID DE OPCIONES MEJORADO --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            
            {{-- ANALISIS LAVADORA --}}
            <a href="{{ route('analisis-lavadora.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                {{-- Barra superior degradada --}}
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-blue-600 to-blue-400"></div>
                
                {{-- Efecto de brillo hover --}}
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/0 via-blue-600/5 to-blue-600/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        {{-- Icono con animación --}}
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-blue-500 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                            <div class="relative bg-gradient-to-br from-blue-500 to-blue-600 text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3">
                                <i class="fas fa-chart-pie text-3xl"></i>
                            </div>
                        </div>
                        
                        {{-- Contenido --}}
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">
                            ANÁLISIS LAVADORA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Consulta y gestiona los análisis de componentes
                        </p>
                        
                        {{-- Indicador de acción --}}
                        <div class="mt-6 flex items-center gap-2 text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- ELONGACION LAVADORA --}}
            <a href="{{ route('elongaciones.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-green-600 to-green-400"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-green-600/0 via-green-600/5 to-green-600/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-green-500 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                            <div class="relative bg-gradient-to-br from-green-500 to-green-600 text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3">
                                <i class="fas fa-ruler-combined text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-green-600 transition-colors">
                            ELONGACIÓN LAVADORA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Registro y seguimiento de elongaciones
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 text-green-600 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- HISTORICO --}}
            <a href="{{ route('historico-revisados.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-purple-600 to-purple-400"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-purple-600/0 via-purple-600/5 to-purple-600/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-purple-500 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                            <div class="relative bg-gradient-to-br from-purple-500 to-purple-600 text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3">
                                <i class="fas fa-history text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-purple-600 transition-colors">
                            HISTÓRICO DE REVISADOS
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza registros históricos y reportes anteriores
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 text-purple-600 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- PLAN DE ACCION --}}
            <a href="{{ route('plan-accion.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-red-600 to-red-400"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-red-600/0 via-red-600/5 to-red-600/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-red-500 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                            <div class="relative bg-gradient-to-br from-red-500 to-red-600 text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3">
                                <i class="fas fa-tasks text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-red-600 transition-colors">
                            PLAN DE ACCIÓN
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Administración y seguimiento de acciones correctivas
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            {{--Analsis 52-12-4--}}
            <a href="{{ route('analisis-52-12-4.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-blue-600 to-blue-400"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/0 via-blue-600/5 to-blue-600/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="p-8 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-blue-500 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                            <div class="relative bg-gradient-to-br from-blue-500 to-blue-600 text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3">
                                <i class="fas fa-search text-xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">
                            ANÁLISIS 52 - 12 - 4
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualización de análisis de calidad del producto
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 text-blue-600 opacity-75 group-hover:opacity-100 transition-opacity">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- FOOTER CON ESTADÍSTICAS RÁPIDAS (OPCIONAL) --}}
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
        outline: 3px solid rgba(59, 130, 246, 0.5);
        outline-offset: 2px;
    }
</style>
@endsection