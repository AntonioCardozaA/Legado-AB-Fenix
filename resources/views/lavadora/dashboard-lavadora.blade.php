@extends('layouts.app')

@section('title', 'Menú | Lavadora')

@section('content')
@php
    $usuarioActual = auth()->user();
    $puedeVerAnalisisLavadora = $usuarioActual?->canUseCustomPermission('ver analisis lavadora') ?? false;
    $puedeVerElongaciones = $usuarioActual?->canUseCustomPermission('ver elongaciones') ?? false;
    $puedeVerHistoricoRevisados = $usuarioActual?->canUseCustomPermission('ver historico revisados') ?? false;
    $puedeVerPlanesLavadora = $usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_LAVADORA) ?? false;
    $puedeVerTendenciasLavadora = $usuarioActual?->canUseCustomPermission('ver tendencias lavadora') ?? false;
    $puedeVerCostosLavadora = $canViewLavadoraCostsModule ?? ($usuarioActual?->canViewLavadoraCostModule() ?? false);
@endphp
<div class="lavadora-menu min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- HEADER MEJORADO --}}
        <div class="mb-10 animate-fade-in">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="menu-hero-brand">
                    <div class="menu-hero-icon">
                        <img src="{{ asset('images/icono-maquina.png') }}"
                             alt="Icono de Maquinaria"
                             class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="menu-hero-copy">
                        <div class="menu-hero-accent"></div>
                        <h1 class="menu-hero-title text-gray-800">
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
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium text-gray-700">Legado AB Fénix</span>
                            </div>
                            <div class="h-4 w-px bg-gray-300"></div>
                            <span class="text-sm text-gray-500">{{ now()->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MENU INDUSTRIAL --}}
        <div class="lavadora-menu-grid" aria-label="Módulos de Lavadora">
            @if($puedeVerAnalisisLavadora)
            <a href="{{ route('analisis-lavadora.index') }}" class="lavadora-menu-card" data-number="01" style="--card-accent: #2aa9ff; --card-glow: rgba(42, 169, 255, .34);">
                <span class="lavadora-menu-card__shine" aria-hidden="true"></span>
                <span class="lavadora-menu-card__topline" aria-hidden="true"></span>
                <span class="lavadora-menu-card__visual">
                    <span class="lavadora-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/analisis-lavadora-collage.webp') }}" width="960" height="639" alt="Pantalla de análisis con componentes industriales de lavadora" loading="eager" decoding="async">
                    <span class="lavadora-menu-card__visual-tag">01 / MONITOR</span>
                </span>
                <span class="lavadora-menu-card__body">
                    <span class="lavadora-menu-card__eyebrow">Diagnóstico industrial</span>
                    <span class="lavadora-menu-card__title">AN&Aacute;LISIS LAVADORA</span>
                    <span class="lavadora-menu-card__description">Registra y consulta los an&aacute;lisis de componentes.</span>
                    <span class="lavadora-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerElongaciones)
            <a href="{{ route('elongaciones.index') }}" class="lavadora-menu-card" data-number="02" style="--card-accent: #19d3b1; --card-glow: rgba(25, 211, 177, .34);">
                <span class="lavadora-menu-card__shine" aria-hidden="true"></span>
                <span class="lavadora-menu-card__topline" aria-hidden="true"></span>
                <span class="lavadora-menu-card__visual">
                    <span class="lavadora-menu-card__visual-glow" aria-hidden="true"></span>
                    {{-- Recurso generado a partir de la referencia real de la cadena de Lavadora. --}}
                    <img src="{{ asset('images/lavadora/menu/elongacion-cadena.webp') }}" width="960" height="549" alt="Cadena industrial de lavadora con medición de elongación" loading="eager" decoding="async">
                    <span class="lavadora-menu-card__visual-tag">02 / MEDICI&Oacute;N</span>
                </span>
                <span class="lavadora-menu-card__body">
                    <span class="lavadora-menu-card__eyebrow">Control dimensional</span>
                    <span class="lavadora-menu-card__title">ELONGACI&Oacute;N CADENA</span>
                    <span class="lavadora-menu-card__description">Registro y seguimiento de elongaciones de cadena.</span>
                    <span class="lavadora-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerHistoricoRevisados)
            <a href="{{ route('historico-revisados.index') }}" class="lavadora-menu-card" data-number="03" style="--card-accent: #9a7bff; --card-glow: rgba(154, 123, 255, .34);">
                <span class="lavadora-menu-card__shine" aria-hidden="true"></span>
                <span class="lavadora-menu-card__topline" aria-hidden="true"></span>
                <span class="lavadora-menu-card__visual">
                    <span class="lavadora-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/historico-revisados.webp') }}" width="960" height="800" alt="Portapapeles industrial con historial de revisiones" loading="eager" decoding="async">
                    <span class="lavadora-menu-card__visual-tag">03 / HISTORIAL</span>
                </span>
                <span class="lavadora-menu-card__body">
                    <span class="lavadora-menu-card__eyebrow">Trazabilidad</span>
                    <span class="lavadora-menu-card__title">HIST&Oacute;RICO DE REVISADOS</span>
                    <span class="lavadora-menu-card__description">Visualiza registros de componentes revisados.</span>
                    <span class="lavadora-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerPlanesLavadora)
            <a href="{{ route('plan-accion.index') }}" class="lavadora-menu-card" data-number="04" style="--card-accent: #ff9f43; --card-glow: rgba(255, 159, 67, .34);">
                <span class="lavadora-menu-card__shine" aria-hidden="true"></span>
                <span class="lavadora-menu-card__topline" aria-hidden="true"></span>
                <span class="lavadora-menu-card__visual">
                    <span class="lavadora-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/plan-accion.webp') }}" width="960" height="802" alt="Plan de acción de mantenimiento con herramientas y engranajes" loading="lazy" decoding="async">
                    <span class="lavadora-menu-card__visual-tag">04 / EJECUCI&Oacute;N</span>
                </span>
                <span class="lavadora-menu-card__body">
                    <span class="lavadora-menu-card__eyebrow">Mantenimiento activo</span>
                    <span class="lavadora-menu-card__title">PLAN DE ACCI&Oacute;N</span>
                    <span class="lavadora-menu-card__description">Administra y da seguimiento a acciones preventivas.</span>
                    <span class="lavadora-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerTendenciasLavadora)
            <a href="{{ route('analisis-tendencia-mensual.lavadora.index') }}" class="lavadora-menu-card" data-number="05" style="--card-accent: #ff5b68; --card-glow: rgba(255, 91, 104, .34);">
                <span class="lavadora-menu-card__shine" aria-hidden="true"></span>
                <span class="lavadora-menu-card__topline" aria-hidden="true"></span>
                <span class="lavadora-menu-card__visual">
                    <span class="lavadora-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/analisis-tendencias.webp') }}" width="960" height="640" alt="Panel industrial de análisis de tendencias y daños" loading="lazy" decoding="async">
                    <span class="lavadora-menu-card__visual-tag">05 / TENDENCIAS</span>
                </span>
                <span class="lavadora-menu-card__body">
                    <span class="lavadora-menu-card__eyebrow">Indicadores de daño</span>
                    <span class="lavadora-menu-card__title">AN&Aacute;LISIS 52-12-4 / 30-14-7</span>
                    <span class="lavadora-menu-card__description">Visualizaci&oacute;n  de tendencia de fallas.</span>
                    <span class="lavadora-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerCostosLavadora)
            <a href="{{ route('lavadora.costos.index') }}" class="lavadora-menu-card" data-number="06" style="--card-accent: #f2c14e; --card-glow: rgba(242, 193, 78, .34);">
                <span class="lavadora-menu-card__shine" aria-hidden="true"></span>
                <span class="lavadora-menu-card__topline" aria-hidden="true"></span>
                <span class="lavadora-menu-card__visual">
                    <span class="lavadora-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/costos.webp') }}" width="960" height="640" alt="Costos de mantenimiento con monedas, calculadora y engranajes" loading="lazy" decoding="async">
                    <span class="lavadora-menu-card__visual-tag">06 / COSTOS</span>
                </span>
                <span class="lavadora-menu-card__body">
                    <span class="lavadora-menu-card__eyebrow">Planeaci&oacute;n financiera</span>
                    <span class="lavadora-menu-card__title">COSTOS</span>
                    <span class="lavadora-menu-card__description">Visualiza gastos, presupuestos y tendencias por componente.</span>
                    <span class="lavadora-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @unless($puedeVerAnalisisLavadora || $puedeVerElongaciones || $puedeVerHistoricoRevisados || $puedeVerPlanesLavadora || $puedeVerTendenciasLavadora || $puedeVerCostosLavadora)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm font-semibold text-amber-800 md:col-span-2 lg:col-span-3">
                    No tiene vistas disponibles en este modulo. Solicite al administrador la asignacion del permiso correspondiente.
                </div>
            @endunless
        </div>

        {{-- VERSION ANTERIOR CONSERVADA PARA NO TOCAR LA LOGICA EXISTENTE --}}
        <div class="legacy-menu-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            
            @if($puedeVerAnalisisLavadora)
            {{-- ANALISIS LAVADORA --}}
            <a href="{{ route('analisis-lavadora.index') }}" 
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
                                <i class="fas fa-search text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        {{-- Contenido --}}
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ANÁLISIS LAVADORA
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
            @endif

            @if($puedeVerElongaciones)
            {{-- ELONGACION LAVADORA --}}
            <a href="{{ route('elongaciones.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-ruler-combined text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            ELONGACIÓN CADENA
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Registro y seguimiento de elongaciones
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @if($puedeVerHistoricoRevisados)
            {{-- HISTORICO --}}
            <a href="{{ route('historico-revisados.index') }}" 
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

            @if($puedeVerPlanesLavadora)
            {{-- PLAN DE ACCION --}}
            <a href="{{ route('plan-accion.index') }}" 
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
            @endif

            @if($puedeVerTendenciasLavadora)
            {{-- ANALISIS 52-12-4 --}}
            <a href="{{ route('analisis-tendencia-mensual.lavadora.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-chart-bar text-2xl sm:text-3xl"></i>
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

            @if($puedeVerCostosLavadora)
                 {{-- COSTOS --}}
            <a href="{{ route('lavadora.costos.index') }}" 
               class="group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
                <div class="absolute top-0 left-0 right-0 h-2" style="background-color: rgb(31, 35, 72);"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[rgba(31,35,72,0.1)] to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                
                <div class="relative z-10 p-5 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full blur-lg opacity-50 group-hover:opacity-75 transition-opacity" style="background-color: rgba(31, 35, 72, 0.5);"></div>
                            <div class="relative text-white p-5 rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-3" style="background: linear-gradient(135deg, rgb(31, 35, 72), rgb(51, 55, 92));">
                                <i class="fas fa-coins text-2xl sm:text-3xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover" style="group-hover:color: rgb(31, 35, 72); transition: color 0.3s;">
                            COSTOS
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Visualiza gastos, presupuestos y tendencias por componente y lavadora
                        </p>
                        
                        <div class="mt-6 flex items-center gap-2 transition-opacity sm:opacity-0 sm:group-hover:opacity-100" style="color: rgb(31, 35, 72);">
                            <span class="text-sm font-medium">Acceder</span>
                            <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @unless($puedeVerAnalisisLavadora || $puedeVerElongaciones || $puedeVerHistoricoRevisados || $puedeVerPlanesLavadora || $puedeVerTendenciasLavadora || $puedeVerCostosLavadora)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm font-semibold text-amber-800 md:col-span-2 lg:col-span-3">
                    No tiene vistas disponibles en este modulo. Solicite al administrador la asignacion del permiso correspondiente.
                </div>
            @endunless
        </div>

        {{-- FOOTER CON ESTADÍSTICAS RÁPIDAS --}}
        @php
            $trendModules = [
                ['key' => '52124', 'title' => 'Analisis 52-12-4'],
                ['key' => '30147', 'title' => 'Analisis 30-14-7'],
            ];
        @endphp

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.ChartDataLabels) {
            Chart.register(ChartDataLabels);
        }

        const dashboardTrendDatasets = {
            '52124': @json($analisis52124 ?? []),
            '30147': @json($analisis30147 ?? []),
        };

        const dashboardTrendMobileQuery = window.matchMedia('(max-width: 768px)');
        const dashboardTrendStates = {};

        function normalizeTrendLabel(label) {
            return String(label || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        }

        function formatMetric(value) {
            return new Intl.NumberFormat('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(value || 0));
        }

        function getWindowPresentation(label) {
            const normalized = normalizeTrendLabel(label);

            if (normalized.includes('52')) {
                return { role: 'historico acumulado', color: '#16a34a', fill: 'rgba(34, 197, 94, 0.18)', dashed: true };
            }

            if (normalized.includes('30')) {
                return { role: 'referencia operativa', color: '#16a34a', fill: 'rgba(34, 197, 94, 0.18)', dashed: true };
            }

            if (normalized.includes('12')) {
                return { role: 'impacto trimestral', color: '#dc2626', fill: 'rgba(239, 68, 68, 0.16)', dashed: false };
            }

            if (normalized.includes('14')) {
                return { role: 'respuesta reciente', color: '#dc2626', fill: 'rgba(239, 68, 68, 0.16)', dashed: false };
            }

            return { role: 'control inmediato', color: '#f97316', fill: 'rgba(249, 115, 22, 0.18)', dashed: false };
        }

        function compareSeries(series) {
            const current = Number(series[series.length - 1] || 0);
            const previous = Number(series[series.length - 2] || 0);
            const diff = current - previous;
            const percentage = previous === 0 ? null : (diff / previous) * 100;

            return { current, previous, diff, percentage, trend: diff < 0 ? 'down' : (diff > 0 ? 'up' : 'stable') };
        }

        function zeroStreak(series) {
            let streak = 0;

            for (let index = series.length - 1; index >= 0; index -= 1) {
                if (Number(series[index] || 0) !== 0) {
                    break;
                }

                streak += 1;
            }

            return streak;
        }

        function deltaChipCopy(delta) {
            if (delta.diff < 0) {
                const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
                return `Bajo ${formatMetric(Math.abs(delta.diff))}${pct}`;
            }

            if (delta.diff > 0) {
                const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
                return `Subio ${formatMetric(delta.diff)}${pct}`;
            }

            return 'Sin cambio';
        }

        function buildWindows(row) {
            return (Array.isArray(row?.series) ? row.series : []).map((serie) => {
                const presentation = getWindowPresentation(serie.label);

                return {
                    label: serie.label || 'Ventana',
                    data: Array.isArray(serie.data) ? serie.data.map((value) => Number(value || 0)) : [],
                    role: presentation.role,
                    color: presentation.color,
                    fill: presentation.fill,
                    dashed: presentation.dashed
                };
            });
        }

        function getRowForState(state) {
            const rows = state.rows || [];

            return rows.find((row) => String(row.linea_id) === String(state.lineId))
                || rows.find((row) => !row.sin_datos && Array.isArray(row.labels) && row.labels.length)
                || rows[0]
                || null;
        }

        function renderTrendCards(state, windows) {
            const cardsHost = document.getElementById(`dashboardTrendCards${state.key}`);

            if (!cardsHost) {
                return;
            }

            if (!windows.length) {
                cardsHost.innerHTML = '';
                return;
            }

            cardsHost.innerHTML = windows.map((windowItem) => {
                const delta = compareSeries(windowItem.data);
                const deltaClass = delta.diff < 0
                    ? 'dashboard-trend-window-delta--positive'
                    : (delta.diff > 0 ? 'dashboard-trend-window-delta--alert' : 'dashboard-trend-window-delta--neutral');
                const deltaIcon = delta.diff < 0 ? 'fa-arrow-down' : (delta.diff > 0 ? 'fa-arrow-up' : 'fa-minus');

                return `
                    <article class="dashboard-trend-window-card" style="--dashboard-trend-accent: ${windowItem.color}">
                        <div class="dashboard-trend-window-label">${windowItem.label}</div>
                        <div class="dashboard-trend-window-value">${formatMetric(delta.current)}</div>
                        <div class="dashboard-trend-window-role">${windowItem.role}</div>
                        <div class="dashboard-trend-window-delta ${deltaClass}">
                            <i class="fas ${deltaIcon}"></i>
                            <span>${deltaChipCopy(delta)} vs periodo anterior</span>
                        </div>
                    </article>
                `;
            }).join('');
        }

        function renderTrendStatus(state, row, windows) {
            const statusCard = document.getElementById(`dashboardTrendStatusCard${state.key}`);
            const titleNode = document.getElementById(`dashboardTrendStatusTitle${state.key}`);
            const copyNode = document.getElementById(`dashboardTrendStatusCopy${state.key}`);
            const captionNode = document.getElementById(`dashboardTrendCaption${state.key}`);

            if (!statusCard || !titleNode || !copyNode || !captionNode) {
                return;
            }

            const latestLabel = Array.isArray(row?.labels) && row.labels.length
                ? row.labels[row.labels.length - 1]
                : 'Sin corte';

            if (!row || row.sin_datos || windows.length < 3 || !Array.isArray(row.labels) || !row.labels.length) {
                statusCard.className = 'dashboard-trend-status dashboard-trend-status--neutral';
                titleNode.textContent = 'Sin tendencia disponible';
                copyNode.textContent = 'Esta lavadora todavia no tiene historial suficiente para mostrar una lectura comparable en este dashboard.';
                captionNode.textContent = 'Sin corte disponible.';
                return;
            }

            const baseWindow = windows[0];
            const midWindow = windows[1];
            const recentWindow = windows[2];
            const midDelta = compareSeries(midWindow.data);
            const recentDelta = compareSeries(recentWindow.data);
            const recentZeroRun = zeroStreak(recentWindow.data);

            let tone = 'neutral';
            let title = 'Monitoreo en curso';
            let copy = `${recentWindow.label} no presenta repunte, pero todavia se requiere continuidad para que ${baseWindow.label} refleje una mejora mas amplia.`;

            if ((recentWindow.data.length > 1 && recentDelta.diff <= 0 && midDelta.diff < 0) || (recentDelta.current === 0 && recentZeroRun >= 2 && midDelta.diff <= 0)) {
                tone = 'positive';

                if (recentDelta.current === 0 && recentZeroRun >= 2) {
                    title = 'Implementacion funcionando';
                    copy = `${recentWindow.label} se mantiene en 0 daños durante ${recentZeroRun} periodos y ${midWindow.label} sigue bajando frente al corte anterior.`;
                } else {
                    title = 'Tendencia de baja confirmada';
                    copy = `${recentWindow.label} ${deltaChipCopy(recentDelta).toLowerCase()} y ${midWindow.label} tambien viene a la baja.`;
                }
            } else if (recentDelta.diff > 0 || midDelta.diff > 0) {
                tone = 'alert';
                title = 'Repunte reciente';
                copy = `${recentWindow.label} o ${midWindow.label} subieron frente al corte anterior. Conviene revisar la ejecucion antes de que el historico vuelva a crecer.`;
            }

            statusCard.className = `dashboard-trend-status dashboard-trend-status--${tone}`;
            titleNode.textContent = title;
            copyNode.textContent = copy;
            captionNode.textContent = `Corte actual: ${latestLabel}.`;
        }

        function updateTrendViewButtons(state) {
            document.querySelectorAll(`[data-dashboard-trend-module="${state.key}"]`).forEach((button) => {
                button.classList.toggle('active', button.dataset.dashboardTrendType === state.chartType);
            });
        }

        function renderTrendChart(state) {
            const row = getRowForState(state);
            const windows = buildWindows(row);
            const labels = Array.isArray(row?.labels) ? row.labels : [];
            const emptyNode = document.getElementById(`dashboardTrendEmpty${state.key}`);
            const canvas = document.getElementById(`dashboardTrendChart${state.key}`);
            const isBar = state.chartType === 'bar';
            const isSmall = dashboardTrendMobileQuery.matches;

            renderTrendCards(state, windows);
            renderTrendStatus(state, row, windows);
            updateTrendViewButtons(state);

            if (state.chart) {
                state.chart.destroy();
                state.chart = null;
            }

            if (!canvas || !emptyNode) {
                return;
            }

            if (!row || row.sin_datos || !labels.length || !windows.length) {
                emptyNode.hidden = false;
                emptyNode.textContent = 'Sin tendencia disponible para la lavadora seleccionada.';
                canvas.style.display = 'none';
                return;
            }

            emptyNode.hidden = true;
            canvas.style.display = '';

            state.chart = new Chart(canvas.getContext('2d'), {
                type: state.chartType,
                data: {
                    labels,
                    datasets: windows.map((windowItem, datasetIndex) => ({
                        label: windowItem.label,
                        data: windowItem.data,
                        borderColor: windowItem.color,
                        backgroundColor: windowItem.fill,
                        borderWidth: isBar ? 0 : (datasetIndex === windows.length - 1 ? 4 : 3),
                        borderDash: isBar ? [] : (windowItem.dashed ? [8, 6] : []),
                        pointBackgroundColor: windowItem.color,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: isBar ? 0 : ((context) => context.dataIndex === labels.length - 1 ? 5 : 3),
                        pointHoverRadius: 7,
                        tension: 0.32,
                        fill: false,
                        borderRadius: isBar ? 10 : 0,
                        borderSkipped: isBar ? false : undefined,
                        barPercentage: isBar ? 0.72 : undefined,
                        categoryPercentage: isBar ? 0.74 : undefined,
                        maxBarThickness: isBar ? 28 : undefined
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 10,
                                padding: 14,
                                color: '#334155',
                                font: {
                                    size: isSmall ? 10 : 12,
                                    weight: '700'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.96)',
                            titleColor: '#fff',
                            bodyColor: '#e2e8f0',
                            callbacks: {
                                label: (context) => {
                                    const currentValue = Number(context.parsed.y || 0);
                                    const previousValue = Number(context.dataset.data?.[context.dataIndex - 1] || 0);
                                    const diff = currentValue - previousValue;
                                    const change = context.dataIndex === 0
                                        ? 'Sin comparativo anterior'
                                        : (diff < 0
                                            ? `Bajo ${formatMetric(Math.abs(diff))}`
                                            : (diff > 0 ? `Subio ${formatMetric(diff)}` : 'Sin cambio'));

                                    return `${context.dataset.label}: ${formatMetric(currentValue)} daños. ${change}.`;
                                }
                            }
                        },
                        datalabels: isBar ? {
                            display: (context) => Number(context.raw || 0) > 0,
                            anchor: 'end',
                            align: 'top',
                            offset: 8,
                            clamp: true,
                            clip: false,
                            color: '#ffffff',
                            backgroundColor: (context) => context.dataset.borderColor,
                            borderRadius: 999,
                            padding: {
                                top: 4,
                                right: 8,
                                bottom: 4,
                                left: 8
                            },
                            formatter: (value) => formatMetric(value),
                            font: {
                                size: isSmall ? 9 : 11,
                                weight: '800'
                            }
                        } : {
                            display: (context) => !isSmall && context.dataIndex === labels.length - 1 && Number(context.raw || 0) > 0,
                            anchor: 'end',
                            align: 'top',
                            offset: 6,
                            clamp: true,
                            clip: false,
                            color: (context) => context.dataset.borderColor,
                            backgroundColor: null,
                            borderRadius: 0,
                            padding: 0,
                            formatter: (value) => formatMetric(value),
                            textStrokeColor: 'rgba(255, 255, 255, 0.98)',
                            textStrokeWidth: 2,
                            font: {
                                size: 10,
                                weight: '800'
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: isBar ? 72 : 16,
                            right: 8,
                            bottom: 10,
                            left: 4
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#475569',
                                maxRotation: isSmall ? 48 : 0,
                                minRotation: isSmall ? 48 : 0,
                                font: {
                                    size: isSmall ? 10 : 11,
                                    weight: '700'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grace: isBar ? '36%' : '8%',
                            grid: {
                                color: 'rgba(148, 163, 184, 0.18)'
                            },
                            title: {
                                display: true,
                                text: 'Daños registrados',
                                color: '#64748b',
                                font: {
                                    size: 12,
                                    weight: '800'
                                }
                            },
                            ticks: {
                                color: '#64748b',
                                callback: (value) => formatMetric(value)
                            }
                        }
                    }
                }
            });
        }

        function initTrendModule(key) {
            const dataset = dashboardTrendDatasets[key] || {};
            const rows = Array.isArray(dataset.lineas) ? dataset.lineas : [];
            const select = document.getElementById(`dashboardTrendSelect${key}`);
            const defaultLineId = String(
                dataset.default_linea_id
                || rows.find((row) => !row.sin_datos && Array.isArray(row.labels) && row.labels.length)?.linea_id
                || rows[0]?.linea_id
                || (select ? select.value : '')
            );

            const state = {
                key,
                rows,
                lineId: defaultLineId,
                chartType: 'bar',
                chart: null
            };

            dashboardTrendStates[key] = state;

            if (select) {
                select.value = defaultLineId;
                select.addEventListener('change', function () {
                    state.lineId = this.value;
                    renderTrendChart(state);
                });
            }

            document.querySelectorAll(`[data-dashboard-trend-module="${key}"]`).forEach((button) => {
                button.addEventListener('click', function () {
                    state.chartType = this.dataset.dashboardTrendType || 'bar';
                    renderTrendChart(state);
                });
            });

            renderTrendChart(state);
        }

        initTrendModule('52124');
        initTrendModule('30147');

        window.addEventListener('resize', function () {
            Object.values(dashboardTrendStates).forEach((state) => {
                renderTrendChart(state);
            });
        });
    });
</script>

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

    .lavadora-menu * {
        box-sizing: border-box;
        min-width: 0;
    }

    .lavadora-menu h1,
    .lavadora-menu h3,
    .lavadora-menu p,
    .lavadora-menu span {
        overflow-wrap: anywhere;
    }

    .lavadora-menu .menu-hero-brand {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .lavadora-menu .menu-hero-icon {
        display: flex;
        width: clamp(4.75rem, 22vw, 8rem);
        height: clamp(4.75rem, 22vw, 8rem);
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .lavadora-menu .menu-hero-copy {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        flex: 1 1 auto;
        min-width: 0;
        max-width: 100%;
    }

    .lavadora-menu .menu-hero-accent {
        width: 0.45rem;
        height: clamp(2.8rem, 12vw, 4rem);
        flex: 0 0 0.45rem;
        border-radius: 9999px;
        background: linear-gradient(to bottom, #1f2937, #4b5563);
    }

    .lavadora-menu .menu-hero-title {
        margin: 0;
        flex: 1 1 auto;
        max-width: 100%;
        font-size: clamp(1.55rem, 6vw, 3rem);
        font-weight: 900;
        line-height: 0.95;
        letter-spacing: -0.04em;
        text-wrap: balance;
    }

    .lavadora-menu .menu-hero-title span {
        display: block;
    }

    @media (min-width: 640px) {
        .lavadora-menu .menu-hero-brand {
            gap: 1rem;
        }

        .lavadora-menu .menu-hero-title {
            line-height: 1;
        }
    }

    @media (max-width: 480px) {
        .lavadora-menu .menu-hero-brand {
            gap: 0.65rem;
        }

        .lavadora-menu .menu-hero-icon {
            width: 4.2rem;
            height: 4.2rem;
        }

        .lavadora-menu .menu-hero-copy {
            gap: 0.6rem;
        }

        .lavadora-menu .menu-hero-accent {
            height: 2.45rem;
        }

        .lavadora-menu a.group {
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
    .dashboard-trend-suite {
        margin-top: 48px;
    }

    .dashboard-trend-suite-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .dashboard-trend-kicker {
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgb(31, 35, 72);
        margin-bottom: 6px;
    }

    .dashboard-trend-suite-header h2 {
        margin: 0;
        font-size: clamp(1.75rem, 3vw, 2.4rem);
        font-weight: 900;
        color: #111827;
    }

    .dashboard-trend-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 18px;
        border-radius: 999px;
        border: 1px solid rgba(31, 35, 72, 0.12);
        background: white;
        color: rgb(31, 35, 72);
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.25s ease;
    }

    .dashboard-trend-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
    }

    .dashboard-trend-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    .dashboard-trend-panel {
        min-width: 0;
        border-radius: 28px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.98));
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
        padding: 22px;
    }

    .dashboard-trend-toolbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .dashboard-trend-heading h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #0f172a;
    }

    .dashboard-trend-actions {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .dashboard-trend-select-wrap {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 150px;
    }

    .dashboard-trend-select-wrap span {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .dashboard-trend-select {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: white;
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        padding: 0 14px;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
    }

    .dashboard-trend-view-selector {
        display: inline-flex;
        align-items: center;
        padding: 4px;
        border-radius: 16px;
        background: rgba(241, 245, 249, 0.95);
        border: 1px solid rgba(148, 163, 184, 0.16);
        gap: 4px;
    }

    .dashboard-trend-view-btn {
        border: none;
        border-radius: 12px;
        background: transparent;
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
        padding: 10px 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dashboard-trend-view-btn.active {
        background: rgb(31, 35, 72);
        color: white;
        box-shadow: 0 8px 20px rgba(31, 35, 72, 0.18);
    }

    .dashboard-trend-brief {
        display: grid;
        grid-template-columns: minmax(230px, 0.95fr) minmax(0, 1.45fr);
        gap: 16px;
        margin-bottom: 18px;
    }

    .dashboard-trend-status {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 22px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        min-width: 0;
    }

    .dashboard-trend-status::after {
        content: '';
        position: absolute;
        right: -34px;
        bottom: -58px;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.12);
    }

    .dashboard-trend-status--positive {
        border-color: rgba(16, 185, 129, 0.24);
        background: linear-gradient(145deg, #ecfdf5, #f8fafc);
    }

    .dashboard-trend-status--positive::after {
        background: rgba(16, 185, 129, 0.16);
    }

    .dashboard-trend-status--alert {
        border-color: rgba(239, 68, 68, 0.22);
        background: linear-gradient(145deg, #fef2f2, #fff7ed);
    }

    .dashboard-trend-status--alert::after {
        background: rgba(239, 68, 68, 0.14);
    }

    .dashboard-trend-status--neutral {
        border-color: rgba(245, 158, 11, 0.22);
        background: linear-gradient(145deg, #fffbeb, #f8fafc);
    }

    .dashboard-trend-status--neutral::after {
        background: rgba(245, 158, 11, 0.14);
    }

    .dashboard-trend-eyebrow,
    .dashboard-trend-window-label {
        position: relative;
        z-index: 1;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .dashboard-trend-status-title {
        position: relative;
        z-index: 1;
        margin-top: 10px;
        font-size: 28px;
        line-height: 1.08;
        font-weight: 900;
        color: #0f172a;
    }

    .dashboard-trend-status-copy {
        position: relative;
        z-index: 1;
        margin: 12px 0 0;
        color: #475569;
        font-size: 14px;
        line-height: 1.55;
    }

    .dashboard-trend-window-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
        gap: 14px;
        min-width: 0;
    }

    .dashboard-trend-window-card {
        position: relative;
        overflow: hidden;
        min-width: 0;
        border-radius: 20px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: white;
        padding: 18px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
    }

    .dashboard-trend-window-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--dashboard-trend-accent, rgb(31, 35, 72));
    }

    .dashboard-trend-window-value {
        margin-top: 12px;
        color: #0f172a;
        font-size: 30px;
        line-height: 1.1;
        font-weight: 900;
        font-family: 'JetBrains Mono', 'Courier New', monospace;
    }

    .dashboard-trend-window-role {
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
    }

    .dashboard-trend-window-delta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 14px;
        border-radius: 999px;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.35;
        max-width: 100%;
    }

    .dashboard-trend-window-delta--positive {
        background: #d1fae5;
        color: #065f46;
    }

    .dashboard-trend-window-delta--alert {
        background: #fee2e2;
        color: #991b1b;
    }

    .dashboard-trend-window-delta--neutral {
        background: #fef3c7;
        color: #92400e;
    }

    .dashboard-trend-chart-card {
        border-radius: 24px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: white;
        padding: 18px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
    }

    .dashboard-trend-chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .dashboard-trend-chart-title {
        color: #0f172a;
        font-size: 16px;
        font-weight: 800;
    }

    .dashboard-trend-caption {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .dashboard-trend-chart-wrap {
        position: relative;
        height: 380px;
    }

    .dashboard-trend-empty {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
        color: #64748b;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.5;
        border-radius: 18px;
        background: linear-gradient(145deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.95));
        border: 1px dashed rgba(148, 163, 184, 0.34);
    }

    @media (max-width: 1280px) {
        .dashboard-trend-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1024px) {
        .dashboard-trend-brief {
            grid-template-columns: 1fr;
        }

        .dashboard-trend-window-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .dashboard-trend-suite {
            margin-top: 40px;
        }

        .dashboard-trend-panel {
            padding: 18px;
            border-radius: 24px;
        }

        .dashboard-trend-toolbar,
        .dashboard-trend-chart-header,
        .dashboard-trend-suite-header {
            flex-direction: column;
            align-items: stretch;
        }

        .dashboard-trend-actions {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }

        .dashboard-trend-select-wrap,
        .dashboard-trend-view-selector {
            width: 100%;
        }

        .dashboard-trend-view-selector {
            justify-content: space-between;
        }

        .dashboard-trend-view-btn {
            flex: 1 1 0;
        }

        .dashboard-trend-status {
            padding: 18px;
        }

        .dashboard-trend-status-title {
            font-size: 24px;
        }

        .dashboard-trend-window-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-trend-chart-wrap {
            height: 320px;
        }
    }
</style>
<style>
    .lavadora-menu {
        position: relative;
        isolation: isolate;
        background:
            radial-gradient(circle at 12% 8%, rgba(42, 169, 255, .10), transparent 27rem),
            radial-gradient(circle at 88% 18%, rgba(154, 123, 255, .08), transparent 25rem),
            linear-gradient(135deg, #f7faff 0%, #eef3f8 52%, #f9fbfd 100%);
    }

    .lavadora-menu::before {
        position: absolute;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        content: "";
        opacity: .42;
        background-image: linear-gradient(rgba(31, 35, 72, .035) 1px, transparent 1px), linear-gradient(90deg, rgba(31, 35, 72, .035) 1px, transparent 1px);
        background-size: 34px 34px;
        mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .72), transparent 68%);
    }

    .lavadora-menu > .max-w-7xl > .mb-10 {
        margin-bottom: 2rem;
        padding: clamp(1rem, 2vw, 1.4rem) clamp(1rem, 2.5vw, 1.7rem);
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        backdrop-filter: none;
    }

    .lavadora-menu .menu-hero-brand {
        gap: 1rem;
    }

    .lavadora-menu .menu-hero-icon {
        width: clamp(4.25rem, 11vw, 6.5rem);
        height: clamp(4.25rem, 11vw, 6.5rem);
        filter: drop-shadow(0 12px 16px rgba(31, 35, 72, .15));
    }

    .lavadora-menu .menu-hero-copy {
        gap: .8rem;
    }

    .lavadora-menu .menu-hero-accent {
        width: .3rem;
        flex-basis: .3rem;
        height: 3.5rem;
        background: linear-gradient(to bottom, #1f2348, #2aa9ff);
        box-shadow: 0 0 16px rgba(42, 169, 255, .28);
    }

    .lavadora-menu .menu-hero-title {
        font-size: clamp(1.8rem, 4vw, 3.4rem);
        letter-spacing: .08em;
    }

    .lavadora-menu-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: clamp(1rem, 2vw, 1.35rem);
        margin-top: .75rem;
    }

    .lavadora-menu-card {
        position: relative;
        display: flex;
        min-width: 0;
        min-height: 27rem;
        flex-direction: column;
        overflow: hidden;
        color: #111827;
        text-decoration: none;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 24px;
        background: linear-gradient(145deg, rgba(255, 255, 255, .98), rgba(241, 245, 249, .94));
        box-shadow: 0 14px 30px rgba(31, 35, 72, .09), inset 0 1px 0 rgba(255, 255, 255, .88);
        transition: transform 300ms cubic-bezier(.2, .75, .25, 1), box-shadow 300ms ease, border-color 300ms ease;
    }

    .lavadora-menu-card::after {
        position: absolute;
        right: -3.5rem;
        bottom: -4rem;
        width: 10rem;
        height: 10rem;
        pointer-events: none;
        content: "";
        opacity: .14;
        border-radius: 999px;
        background: var(--card-accent);
        filter: blur(34px);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .lavadora-menu-card:hover {
        transform: translateY(-5px);
        border-color: color-mix(in srgb, var(--card-accent) 42%, white);
        box-shadow: 0 24px 42px rgba(31, 35, 72, .15), 0 0 0 1px rgba(255, 255, 255, .72) inset;
    }

    .lavadora-menu-card:hover::after {
        opacity: .28;
        transform: scale(1.2);
    }

    .lavadora-menu-card:active {
        transform: translateY(-1px) scale(.985);
        transition-duration: 120ms;
    }

    .lavadora-menu-card:focus-visible {
        outline: 3px solid var(--card-accent);
        outline-offset: 4px;
    }

    .lavadora-menu-card__shine {
        position: absolute;
        inset: 0;
        z-index: 3;
        pointer-events: none;
        transform: translateX(-120%);
        background: linear-gradient(105deg, transparent 32%, rgba(255, 255, 255, .32) 48%, transparent 64%);
        transition: transform 700ms ease;
    }

    .lavadora-menu-card:hover .lavadora-menu-card__shine {
        transform: translateX(120%);
    }

    .lavadora-menu-card__topline {
        position: absolute;
        top: 0;
        left: 1.2rem;
        right: 1.2rem;
        z-index: 4;
        height: 3px;
        border-radius: 99px;
        background: var(--card-accent);
        box-shadow: 0 0 14px var(--card-glow);
        transition: box-shadow 300ms ease;
    }

    .lavadora-menu-card:hover .lavadora-menu-card__topline {
        box-shadow: 0 0 24px var(--card-glow), 0 0 6px var(--card-accent);
    }

    .lavadora-menu-card__visual {
        position: relative;
        display: grid;
        place-items: center;
        height: 14.6rem;
        flex: 0 0 14.6rem;
        margin: .7rem .7rem 0;
        overflow: hidden;
        border-radius: 19px;
        background:
            radial-gradient(circle at 50% 48%, var(--card-glow), transparent 47%),
            linear-gradient(145deg, #172033 0%, #0b1220 100%);
    }

    .lavadora-menu-card__visual::before {
        position: absolute;
        inset: .55rem;
        content: "";
        border: 1px solid rgba(255, 255, 255, .11);
        border-radius: 14px;
        background-image: linear-gradient(rgba(255, 255, 255, .06) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .06) 1px, transparent 1px);
        background-size: 24px 24px;
        mask-image: radial-gradient(circle at center, black, transparent 74%);
    }

    .lavadora-menu-card__visual-glow {
        position: absolute;
        width: 58%;
        height: 58%;
        border-radius: 50%;
        background: var(--card-accent);
        opacity: .2;
        filter: blur(28px);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .lavadora-menu-card__visual img {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        transform: translateY(2px) scale(.94);
        filter: drop-shadow(0 18px 14px rgba(0, 0, 0, .28));
        transition: transform 320ms cubic-bezier(.2, .75, .25, 1), filter 320ms ease;
    }

    .lavadora-menu-card:hover .lavadora-menu-card__visual img {
        transform: translateY(-6px) scale(1.02);
        filter: drop-shadow(0 24px 18px rgba(0, 0, 0, .32));
    }

    .lavadora-menu-card:hover .lavadora-menu-card__visual-glow {
        opacity: .34;
        transform: scale(1.15);
    }

    .lavadora-menu-card__visual-tag {
        position: absolute;
        right: .85rem;
        bottom: .8rem;
        z-index: 2;
        padding: .28rem .5rem;
        color: rgba(255, 255, 255, .82);
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 999px;
        background: rgba(7, 13, 26, .54);
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .12em;
        line-height: 1;
    }

    .lavadora-menu-card__body {
        position: relative;
        z-index: 2;
        display: flex;
        min-height: 11.35rem;
        flex: 1;
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem 1.15rem 1.15rem;
    }

    .lavadora-menu-card__eyebrow {
        margin-bottom: .45rem;
        color: var(--card-accent);
        font-size: .65rem;
        font-weight: 800;
        letter-spacing: .13em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .lavadora-menu-card__title {
        max-width: 100%;
        color: #182033;
        font-size: clamp(1.03rem, 1.5vw, 1.24rem);
        font-weight: 900;
        letter-spacing: -.025em;
        line-height: 1.15;
        text-wrap: balance;
    }

    .lavadora-menu-card__description {
        max-width: 32rem;
        margin-top: .55rem;
        color: #64748b;
        font-size: .82rem;
        line-height: 1.45;
    }

    .lavadora-menu-card__action {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-top: auto;
        padding-top: .8rem;
        color: #334155;
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .02em;
        transition: color 250ms ease, gap 250ms ease;
    }

    .lavadora-menu-card__action i {
        color: var(--card-accent);
        font-size: .72rem;
    }

    .lavadora-menu-card:hover .lavadora-menu-card__action {
        gap: .65rem;
        color: #111827;
    }

    .legacy-menu-grid {
        display: none !important;
    }

    @media (max-width: 1100px) {
        .lavadora-menu-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .lavadora-menu {
            padding-top: 1rem;
        }

        .lavadora-menu > .max-w-7xl {
            padding-right: .85rem;
            padding-left: .85rem;
        }

        .lavadora-menu > .max-w-7xl > .mb-10 {
            padding: .9rem;
            border-radius: 20px;
        }

        .lavadora-menu .menu-hero-title {
            font-size: 1.7rem;
        }

        .lavadora-menu-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .lavadora-menu-card {
            min-height: 25.5rem;
        }

        .lavadora-menu-card__visual {
            height: 14rem;
            flex-basis: 14rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .lavadora-menu-card,
        .lavadora-menu-card::after,
        .lavadora-menu-card__shine,
        .lavadora-menu-card__visual img,
        .lavadora-menu-card__visual-glow,
        .lavadora-menu-card__action {
            transition: none;
        }

        .lavadora-menu-card:hover,
        .lavadora-menu-card:active,
        .lavadora-menu-card:hover .lavadora-menu-card__visual img {
            transform: none;
        }
    }
</style>
<style>
    /* Composición horizontal tipo ficha industrial */
    .lavadora-menu-grid {
        gap: clamp(1rem, 1.8vw, 1.3rem);
    }

    .lavadora-menu-card {
        display: block;
        min-height: 14.25rem;
        border-radius: 22px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 12px 25px rgba(31, 35, 72, .10), inset 0 1px 0 rgba(255, 255, 255, .92);
    }

    .lavadora-menu-card::before {
        position: absolute;
        top: 1rem;
        left: 1.35rem;
        z-index: 4;
        color: color-mix(in srgb, var(--card-accent) 72%, #ffffff);
        content: attr(data-number);
        font-size: 2.15rem;
        font-weight: 900;
        letter-spacing: -.06em;
        line-height: 1;
    }

    .lavadora-menu-card::after {
        right: -1px;
        bottom: -1px;
        width: 65%;
        height: 100%;
        z-index: 0;
        opacity: .16;
        border-radius: 0 22px 22px 0;
        background: linear-gradient(135deg, transparent 0 25%, var(--card-accent) 25%, color-mix(in srgb, var(--card-accent) 65%, white) 100%);
        filter: none;
        clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .lavadora-menu-card:hover::after {
        opacity: .24;
        transform: scale(1.03);
    }

    .lavadora-menu-card__topline {
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        z-index: 5;
        border-radius: 22px 22px 0 0;
    }

    .lavadora-menu-card__visual {
        position: absolute;
        inset: 0 0 0 47%;
        z-index: 1;
        display: grid;
        height: auto;
        flex: none;
        margin: 0;
        overflow: hidden;
        border-radius: 0 22px 22px 0;
        background: transparent;
    }

    .lavadora-menu-card__visual::before {
        display: none;
    }

    .lavadora-menu-card__visual-glow {
        width: 70%;
        height: 70%;
        opacity: .18;
        filter: blur(32px);
    }

    .lavadora-menu-card__visual img {
        width: 100%;
        height: 100%;
        transform: translateY(1px) scale(.98);
        filter: drop-shadow(0 17px 13px rgba(31, 35, 72, .22));
    }

    .lavadora-menu-card:hover .lavadora-menu-card__visual img {
        transform: translateY(-4px) scale(1.06);
        filter: drop-shadow(0 22px 16px rgba(31, 35, 72, .26));
    }

    .lavadora-menu-card__visual-tag {
        display: none;
    }

    .lavadora-menu-card__body {
        position: relative;
        z-index: 3;
        display: flex;
        width: 47%;
        min-height: 14.25rem;
        padding: 3.65rem .75rem 1rem 1.35rem;
        align-items: flex-start;
        background: linear-gradient(90deg, rgba(255, 255, 255, .98) 0%, rgba(255, 255, 255, .94) 84%, rgba(255, 255, 255, 0) 100%);
    }

    .lavadora-menu-card__eyebrow {
        display: none;
    }

    .lavadora-menu-card__title {
        max-width: 15rem;
        color: #14213d;
        font-size: clamp(1rem, 1.55vw, 1.2rem);
        line-height: 1.08;
    }

    .lavadora-menu-card__description {
        max-width: 14rem;
        margin-top: .65rem;
        color: #64748b;
        font-size: .78rem;
        line-height: 1.45;
    }

    .lavadora-menu-card__action {
        gap: .55rem;
        margin-top: auto;
        padding: .28rem .68rem .28rem .28rem;
        color: #1e293b;
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 999px;
        background: rgba(255, 255, 255, .76);
        box-shadow: 0 7px 15px rgba(31, 35, 72, .08);
        font-size: .74rem;
        white-space: nowrap;
    }

    .lavadora-menu-card__action i {
        order: -1;
        display: grid;
        width: 1.85rem;
        height: 1.85rem;
        place-items: center;
        color: #fff;
        border-radius: 999px;
        background: var(--card-accent);
        box-shadow: 0 5px 10px var(--card-glow);
        font-size: .68rem;
    }

    @media (max-width: 640px) {
        .lavadora-menu-card {
            display: flex;
            min-height: 0;
            flex-direction: column;
        }

        .lavadora-menu-card::before {
            top: .85rem;
            left: 1rem;
            font-size: 1.9rem;
        }

        .lavadora-menu-card__visual {
            position: relative;
            inset: auto;
            width: 100%;
            height: 11.25rem;
            flex: 0 0 11.25rem;
            border-radius: 0;
        }

        .lavadora-menu-card__visual img {
            transform: translateY(1px) scale(.96);
        }

        .lavadora-menu-card:hover .lavadora-menu-card__visual img {
            transform: translateY(-4px) scale(1.03);
        }

        .lavadora-menu-card__body {
            width: 100%;
            min-height: 0;
            padding: 1rem 1rem 1.1rem;
            background: rgba(255, 255, 255, .96);
        }

        .lavadora-menu-card__title {
            font-size: .95rem;
        }

        .lavadora-menu-card__description {
            max-width: 100%;
            font-size: .72rem;
        }

        .lavadora-menu-card__action {
            font-size: .68rem;
        }
    }
</style>
@endsection
