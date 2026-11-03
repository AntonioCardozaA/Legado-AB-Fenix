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
                <div class="menu-hero-brand">
                    <div class="menu-hero-icon">
                        <img src="{{ asset('images/icono-pas-cover.png') }}"
                             alt="Icono de Pasteurizadora"
                             class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="menu-hero-copy">
                        <div class="menu-hero-accent"></div>
                        <h1 class="menu-hero-title text-gray-800">
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
        <div class="pasteur-mechanical-menu-grid" aria-label="M&oacute;dulos mec&aacute;nicos de Pasteurizadora">
            @if($puedeVerMecanicaPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}" class="pasteur-mechanical-card pasteur-mechanical-card--analysis" data-number="01" style="--card-accent: #2aa9ff; --card-glow: rgba(42, 169, 255, .34);">
                <span class="pasteur-mechanical-card__shine" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__topline" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__visual" aria-hidden="true">
                    <span class="pasteur-mechanical-card__visual-glow"></span>
                    <span class="pasteur-component-collage">
                        <img class="pasteur-component-collage__anillas" src="{{ asset('images/componentes-pasteurizadora/ANILLAS.png') }}" alt="" loading="eager" decoding="async">
                        <img class="pasteur-component-collage__excentricos" src="{{ asset('images/componentes-pasteurizadora/EXCENTRICOS.png') }}" alt="" loading="eager" decoding="async">
                        <img class="pasteur-component-collage__rodajas" src="{{ asset('images/componentes-pasteurizadora/RODAJAS.png') }}" alt="" loading="eager" decoding="async">
                        <img class="pasteur-component-collage__placas" src="{{ asset('images/componentes-pasteurizadora/PLACAS_PERNO.png') }}" alt="" loading="eager" decoding="async">
                        <img class="pasteur-component-collage__vigas" src="{{ asset('images/componentes-pasteurizadora/VIGAS_MOVIMIENTO.png') }}" alt="" loading="eager" decoding="async">
                        <img class="pasteur-component-collage__esparragos" src="{{ asset('images/componentes-pasteurizadora/ESPARRAGOS.png') }}" alt="" loading="eager" decoding="async">
                    </span>
                    <span class="pasteur-mechanical-card__visual-tag">01 / MONITOR</span>
                </span>
                <span class="pasteur-mechanical-card__body">
                    <span class="pasteur-mechanical-card__eyebrow">Diagn&oacute;stico industrial</span>
                    <span class="pasteur-mechanical-card__title">AN&Aacute;LISIS PASTEURIZADORA</span>
                    <span class="pasteur-mechanical-card__description">Registra y consulta los an&aacute;lisis de componentes.</span>
                    <span class="pasteur-mechanical-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>

            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.historico-revisados') }}" class="pasteur-mechanical-card" data-number="02" style="--card-accent: rgb(31, 35, 72); --card-glow: rgba(31, 35, 72, .34);">
                <span class="pasteur-mechanical-card__shine" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__topline" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__visual" aria-hidden="true">
                    <span class="pasteur-mechanical-card__visual-glow"></span>
                    <img src="{{ asset('images/lavadora/menu/historico-revisados.webp') }}" width="960" height="800" alt="" loading="eager" decoding="async">
                    <span class="pasteur-mechanical-card__visual-tag">02 / HISTORIAL</span>
                </span>
                <span class="pasteur-mechanical-card__body">
                    <span class="pasteur-mechanical-card__eyebrow">Trazabilidad</span>
                    <span class="pasteur-mechanical-card__title">HIST&Oacute;RICO DE REVISADOS</span>
                    <span class="pasteur-mechanical-card__description">Visualiza registros de componentes revisados.</span>
                    <span class="pasteur-mechanical-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>

            @if($puedeVerPlanesPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}" class="pasteur-mechanical-card" data-number="03" style="--card-accent: rgb(31, 35, 72); --card-glow: rgba(31, 35, 72, .34);">
                <span class="pasteur-mechanical-card__shine" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__topline" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__visual" aria-hidden="true">
                    <span class="pasteur-mechanical-card__visual-glow"></span>
                    <img src="{{ asset('images/lavadora/menu/plan-accion.webp') }}" width="960" height="802" alt="" loading="lazy" decoding="async">
                    <span class="pasteur-mechanical-card__visual-tag">03 / EJECUCI&Oacute;N</span>
                </span>
                <span class="pasteur-mechanical-card__body">
                    <span class="pasteur-mechanical-card__eyebrow">Mantenimiento activo</span>
                    <span class="pasteur-mechanical-card__title">PLAN DE ACCI&Oacute;N</span>
                    <span class="pasteur-mechanical-card__description">Administra y da seguimiento a acciones preventivas.</span>
                    <span class="pasteur-mechanical-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerTendenciasPasteurizadora)
            <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.index') }}" class="pasteur-mechanical-card" data-number="04" style="--card-accent: rgb(31, 35, 72); --card-glow: rgba(31, 35, 72, .34);">
                <span class="pasteur-mechanical-card__shine" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__topline" aria-hidden="true"></span>
                <span class="pasteur-mechanical-card__visual" aria-hidden="true">
                    <span class="pasteur-mechanical-card__visual-glow"></span>
                    <img src="{{ asset('images/lavadora/menu/analisis-tendencias.webp') }}" width="960" height="640" alt="" loading="lazy" decoding="async">
                    <span class="pasteur-mechanical-card__visual-tag">04 / TENDENCIAS</span>
                </span>
                <span class="pasteur-mechanical-card__body">
                    <span class="pasteur-mechanical-card__eyebrow">Indicadores de da&ntilde;o</span>
                    <span class="pasteur-mechanical-card__title">AN&Aacute;LISIS 52-12-4 / 30-14-7</span>
                    <span class="pasteur-mechanical-card__description">Visualizaci&oacute;n autom&aacute;tica de tendencia de da&ntilde;os.</span>
                    <span class="pasteur-mechanical-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            
            {{-- ANALISIS PASTEURIZADORA --}}
            @if($puedeVerMecanicaPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}" 
               class="pasteur-mechanical-legacy group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
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
               class="pasteur-mechanical-legacy group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
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
               class="pasteur-mechanical-legacy group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
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
               class="pasteur-mechanical-legacy group relative bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden hover:-translate-y-2">
                
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

    .pasteur-menu .menu-hero-brand {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .pasteur-menu .menu-hero-icon {
        display: flex;
        width: clamp(4.75rem, 22vw, 8rem);
        height: clamp(4.75rem, 22vw, 8rem);
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .pasteur-menu .menu-hero-copy {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        flex: 1 1 auto;
        min-width: 0;
        max-width: 100%;
    }

    .pasteur-menu .menu-hero-accent {
        width: 0.45rem;
        height: clamp(2.8rem, 12vw, 4rem);
        flex: 0 0 0.45rem;
        border-radius: 9999px;
        background: linear-gradient(to bottom, #1f2937, #4b5563);
    }

    .pasteur-menu .menu-hero-title {
        margin: 0;
        flex: 1 1 auto;
        max-width: 100%;
        font-size: clamp(1.55rem, 6vw, 3rem);
        font-weight: 900;
        line-height: 0.95;
        letter-spacing: -0.04em;
        text-wrap: balance;
    }

    .pasteur-menu .menu-hero-title span {
        display: block;
    }

    @media (min-width: 640px) {
        .pasteur-menu .menu-hero-brand {
            gap: 1rem;
        }

        .pasteur-menu .menu-hero-title {
            line-height: 1;
        }
    }

    @media (max-width: 480px) {
        .pasteur-menu .menu-hero-brand {
            gap: 0.65rem;
        }

        .pasteur-menu .menu-hero-icon {
            width: 4.2rem;
            height: 4.2rem;
        }

        .pasteur-menu .menu-hero-copy {
            gap: 0.6rem;
        }

        .pasteur-menu .menu-hero-accent {
            height: 2.45rem;
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

    .pasteur-mechanical-menu-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: clamp(1rem, 1.8vw, 1.3rem);
        margin-top: .75rem;
    }

    .pasteur-mechanical-card {
        position: relative;
        display: block;
        min-width: 0;
        min-height: 14.25rem;
        overflow: hidden;
        color: #111827;
        text-decoration: none;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 22px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 12px 25px rgba(31, 35, 72, .10), inset 0 1px 0 rgba(255, 255, 255, .92);
        transition: transform 300ms cubic-bezier(.2, .75, .25, 1), box-shadow 300ms ease, border-color 300ms ease;
    }

    .pasteur-mechanical-card::before {
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

    .pasteur-mechanical-card::after {
        position: absolute;
        right: -1px;
        bottom: -1px;
        z-index: 0;
        width: 65%;
        height: 100%;
        pointer-events: none;
        content: "";
        opacity: .16;
        border-radius: 0 22px 22px 0;
        background: linear-gradient(135deg, transparent 0 25%, var(--card-accent) 25%, color-mix(in srgb, var(--card-accent) 65%, white) 100%);
        clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .pasteur-mechanical-card:hover {
        transform: translateY(-5px);
        border-color: color-mix(in srgb, var(--card-accent) 42%, white);
        box-shadow: 0 24px 42px rgba(31, 35, 72, .15), 0 0 0 1px rgba(255, 255, 255, .72) inset;
    }

    .pasteur-mechanical-card:hover::after {
        opacity: .24;
        transform: scale(1.03);
    }

    .pasteur-mechanical-card:active {
        transform: translateY(-1px) scale(.985);
        transition-duration: 120ms;
    }

    .pasteur-mechanical-card:focus-visible {
        outline: 3px solid var(--card-accent);
        outline-offset: 4px;
    }

    .pasteur-mechanical-card__shine {
        position: absolute;
        inset: 0;
        z-index: 3;
        pointer-events: none;
        transform: translateX(-120%);
        background: linear-gradient(105deg, transparent 32%, rgba(255, 255, 255, .32) 48%, transparent 64%);
        transition: transform 700ms ease;
    }

    .pasteur-mechanical-card:hover .pasteur-mechanical-card__shine {
        transform: translateX(120%);
    }

    .pasteur-mechanical-card__topline {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 5;
        height: 4px;
        border-radius: 22px 22px 0 0;
        background: var(--card-accent);
        box-shadow: 0 0 14px var(--card-glow);
    }

    .pasteur-mechanical-card__visual {
        position: absolute;
        inset: 0 0 0 47%;
        z-index: 1;
        display: grid;
        place-items: center;
        overflow: hidden;
        border-radius: 0 22px 22px 0;
        background: radial-gradient(circle at 50% 48%, var(--card-glow), transparent 47%), linear-gradient(145deg, #172033 0%, #0b1220 100%);
    }

    .pasteur-mechanical-card__visual::before {
        position: absolute;
        inset: .55rem;
        content: "";
        border: 1px solid rgba(255, 255, 255, .11);
        border-radius: 14px;
        background-image: linear-gradient(rgba(255, 255, 255, .06) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .06) 1px, transparent 1px);
        background-size: 24px 24px;
        mask-image: radial-gradient(circle at center, black, transparent 74%);
    }

    .pasteur-mechanical-card__visual-glow {
        position: absolute;
        width: 70%;
        height: 70%;
        border-radius: 50%;
        background: var(--card-accent);
        opacity: .2;
        filter: blur(32px);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .pasteur-mechanical-card__visual > img {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        transform: translateY(1px) scale(.98);
        filter: drop-shadow(0 17px 13px rgba(31, 35, 72, .22));
        transition: transform 320ms cubic-bezier(.2, .75, .25, 1), filter 320ms ease;
    }

    .pasteur-mechanical-card:hover .pasteur-mechanical-card__visual > img {
        transform: translateY(-4px) scale(1.06);
        filter: drop-shadow(0 22px 16px rgba(31, 35, 72, .26));
    }

    .pasteur-component-collage {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
    }

    .pasteur-component-collage img {
        position: absolute;
        display: block;
        width: auto;
        height: auto;
        object-fit: contain;
        filter: drop-shadow(0 10px 8px rgba(0, 0, 0, .34));
        transition: transform 320ms cubic-bezier(.2, .75, .25, 1), filter 320ms ease;
    }

    .pasteur-component-collage__anillas {
        top: 18%;
        left: 3%;
        width: 43% !important;
    }

    .pasteur-component-collage__excentricos {
        top: 7%;
        left: 38%;
        width: 29% !important;
    }

    .pasteur-component-collage__rodajas {
        top: 12%;
        right: 3%;
        width: 30% !important;
    }

    .pasteur-component-collage__placas {
        right: 8%;
        bottom: 3%;
        width: 31% !important;
    }

    .pasteur-component-collage__vigas {
        bottom: 4%;
        left: 25%;
        width: 43% !important;
    }

    .pasteur-component-collage__esparragos {
        bottom: 7%;
        left: 1%;
        width: 29% !important;
    }

    .pasteur-mechanical-card:hover .pasteur-component-collage img {
        filter: drop-shadow(0 15px 11px rgba(0, 0, 0, .42));
    }

    .pasteur-mechanical-card--analysis .pasteur-mechanical-card__visual {
        background: linear-gradient(135deg, #f7fcff 0%, #dceeff 100%);
    }

    .pasteur-mechanical-card--analysis .pasteur-mechanical-card__visual::before {
        display: none;
    }

    .pasteur-mechanical-card--analysis .pasteur-mechanical-card__visual-glow {
        background: #2aa9ff;
        opacity: .12;
    }

    .pasteur-mechanical-card--analysis .pasteur-mechanical-card__visual-tag {
        display: none;
    }

    .pasteur-mechanical-card--analysis .pasteur-component-collage img {
        filter: drop-shadow(0 12px 8px rgba(31, 35, 72, .22));
    }

    .pasteur-mechanical-card--analysis:hover .pasteur-component-collage img {
        filter: drop-shadow(0 17px 11px rgba(31, 35, 72, .28));
    }

    .pasteur-mechanical-card:hover .pasteur-mechanical-card__visual-glow {
        opacity: .34;
        transform: scale(1.15);
    }

    .pasteur-mechanical-card__visual-tag {
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

    .pasteur-mechanical-card__body {
        position: relative;
        z-index: 3;
        display: flex;
        width: 47%;
        min-height: 14.25rem;
        flex-direction: column;
        align-items: flex-start;
        padding: 3.65rem .75rem 1rem 1.35rem;
        background: linear-gradient(90deg, rgba(255, 255, 255, .98) 0%, rgba(255, 255, 255, .94) 84%, rgba(255, 255, 255, 0) 100%);
    }

    .pasteur-mechanical-card__eyebrow {
        display: none;
    }

    .pasteur-mechanical-card__title {
        max-width: 15rem;
        color: #14213d;
        font-size: clamp(1rem, 1.55vw, 1.2rem);
        font-weight: 900;
        letter-spacing: -.025em;
        line-height: 1.08;
    }

    .pasteur-mechanical-card__description {
        max-width: 14rem;
        margin-top: .65rem;
        color: #64748b;
        font-size: .78rem;
        line-height: 1.45;
    }

    .pasteur-mechanical-card__action {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        margin-top: auto;
        padding: .28rem .68rem .28rem .28rem;
        color: #1e293b;
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 999px;
        background: rgba(255, 255, 255, .76);
        box-shadow: 0 7px 15px rgba(31, 35, 72, .08);
        font-size: .74rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .pasteur-mechanical-card__action i {
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

    .pasteur-mechanical-card:hover .pasteur-mechanical-card__action {
        color: #111827;
    }

    .pasteur-mechanical-legacy {
        display: none !important;
    }

    @media (max-width: 1100px) {
        .pasteur-mechanical-menu-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .pasteur-mechanical-menu-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .pasteur-mechanical-card {
            display: flex;
            min-height: 0;
            flex-direction: column;
        }

        .pasteur-mechanical-card::before {
            top: .85rem;
            left: 1rem;
            font-size: 1.9rem;
        }

        .pasteur-mechanical-card__visual {
            position: relative;
            inset: auto;
            width: 100%;
            height: 11.25rem;
            flex: 0 0 11.25rem;
            border-radius: 0;
        }

        .pasteur-mechanical-card__body {
            width: 100%;
            min-height: 0;
            padding: 1rem 1rem 1.1rem;
            background: rgba(255, 255, 255, .96);
        }

        .pasteur-mechanical-card__title {
            font-size: .95rem;
        }

        .pasteur-mechanical-card__description {
            max-width: 100%;
            font-size: .72rem;
        }

        .pasteur-mechanical-card__action {
            font-size: .68rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .pasteur-mechanical-card,
        .pasteur-mechanical-card::after,
        .pasteur-mechanical-card__shine,
        .pasteur-mechanical-card__visual > img,
        .pasteur-component-collage img,
        .pasteur-mechanical-card__visual-glow {
            transition: none;
        }

        .pasteur-mechanical-card:hover,
        .pasteur-mechanical-card:active,
        .pasteur-mechanical-card:hover .pasteur-mechanical-card__visual > img {
            transform: none;
        }
    }
</style>
@endsection
