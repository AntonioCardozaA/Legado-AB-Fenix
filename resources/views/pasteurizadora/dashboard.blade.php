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
<div class="pasteur-menu industrial-menu min-h-screen overflow-x-hidden bg-gradient-to-br from-gray-50 to-gray-100 py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- HEADER MEJORADO --}}
        <div class="mb-10 animate-fade-in">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="menu-hero-brand">
                    <div class="menu-hero-icon rounded-full overflow-hidden">
                        <img src="{{ asset('images/icono_pas.png') }}"
                             alt="Icono de Pasteurizadora"
                             width="1206" height="880" decoding="async"
                             class="h-full w-full object-contain">
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
        <div class="industrial-menu-grid" aria-label="Módulos de Pasteurizadora">
            @if($puedeVerMecanicaPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}" class="industrial-menu-card industrial-menu-card--pasteur-analysis" data-number="01" style="--card-accent: #2aa9ff; --card-glow: rgba(42, 169, 255, .34);">
                <span class="industrial-menu-card__shine" aria-hidden="true"></span>
                <span class="industrial-menu-card__topline" aria-hidden="true"></span>
                <span class="industrial-menu-card__visual">
                    <span class="industrial-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/pasteurizadora/menu/analisis-pasteurizadora-collage.webp') }}" width="1538" height="1023" alt="Pantalla de análisis con componentes industriales de pasteurizadora" loading="eager" decoding="async">
                    <span class="industrial-menu-card__visual-tag">01 / MONITOR</span>
                </span>
                <span class="industrial-menu-card__body">
                    <span class="industrial-menu-card__eyebrow">Diagnóstico industrial</span>
                    <span class="industrial-menu-card__title">AN&Aacute;LISIS PASTEURIZADORA</span>
                    <span class="industrial-menu-card__description">Registra y consulta los an&aacute;lisis de componentes.</span>
                    <span class="industrial-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerMecanicaPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.historico-revisados') }}" class="industrial-menu-card" data-number="02" style="--card-accent: #9a7bff; --card-glow: rgba(154, 123, 255, .34);">
                <span class="industrial-menu-card__shine" aria-hidden="true"></span>
                <span class="industrial-menu-card__topline" aria-hidden="true"></span>
                <span class="industrial-menu-card__visual">
                    <span class="industrial-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/historico-revisados.webp') }}" width="960" height="800" alt="Portapapeles industrial con historial de revisiones" loading="eager" decoding="async">
                    <span class="industrial-menu-card__visual-tag">02 / HISTORIAL</span>
                </span>
                <span class="industrial-menu-card__body">
                    <span class="industrial-menu-card__eyebrow">Trazabilidad</span>
                    <span class="industrial-menu-card__title">HIST&Oacute;RICO DE REVISADOS</span>
                    <span class="industrial-menu-card__description">Visualiza registros de componentes revisados.</span>
                    <span class="industrial-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerPlanesPasteurizadora)
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}" class="industrial-menu-card" data-number="03" style="--card-accent: #ff9f43; --card-glow: rgba(255, 159, 67, .34);">
                <span class="industrial-menu-card__shine" aria-hidden="true"></span>
                <span class="industrial-menu-card__topline" aria-hidden="true"></span>
                <span class="industrial-menu-card__visual">
                    <span class="industrial-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/plan-accion.webp') }}" width="960" height="802" alt="Plan de acción de mantenimiento con herramientas y engranajes" loading="lazy" decoding="async">
                    <span class="industrial-menu-card__visual-tag">03 / EJECUCI&Oacute;N</span>
                </span>
                <span class="industrial-menu-card__body">
                    <span class="industrial-menu-card__eyebrow">Mantenimiento activo</span>
                    <span class="industrial-menu-card__title">PLAN DE ACCI&Oacute;N</span>
                    <span class="industrial-menu-card__description">Administra y da seguimiento a acciones preventivas.</span>
                    <span class="industrial-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerMecanicaPasteurizadora && $puedeVerTendenciasPasteurizadora)
            <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.index') }}" class="industrial-menu-card" data-number="04" style="--card-accent: #ff5b68; --card-glow: rgba(255, 91, 104, .34);">
                <span class="industrial-menu-card__shine" aria-hidden="true"></span>
                <span class="industrial-menu-card__topline" aria-hidden="true"></span>
                <span class="industrial-menu-card__visual">
                    <span class="industrial-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/analisis-tendencias.webp') }}" width="960" height="640" alt="Panel industrial de análisis de tendencias y daños" loading="lazy" decoding="async">
                    <span class="industrial-menu-card__visual-tag">04 / TENDENCIAS</span>
                </span>
                <span class="industrial-menu-card__body">
                    <span class="industrial-menu-card__eyebrow">Indicadores de daño</span>
                    <span class="industrial-menu-card__title">AN&Aacute;LISIS 52-12-4 / 30-14-7</span>
                    <span class="industrial-menu-card__description">Visualizaci&oacute;n  de tendencia de fallas.</span>
                    <span class="industrial-menu-card__action">Abrir m&oacute;dulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            @endif

            @if($puedeVerMecanicaPasteurizadora)
            <div class="industrial-menu-card" role="group" aria-disabled="true" aria-label="Costos de pasteurizadora: próximamente" data-number="05" style="--card-accent: #f2c14e; --card-glow: rgba(242, 193, 78, .34);">
                <span class="industrial-menu-card__shine" aria-hidden="true"></span>
                <span class="industrial-menu-card__topline" aria-hidden="true"></span>
                <span class="industrial-menu-card__visual">
                    <span class="industrial-menu-card__visual-glow" aria-hidden="true"></span>
                    <img src="{{ asset('images/lavadora/menu/costos.webp') }}" width="960" height="640" alt="Costos de mantenimiento con monedas, calculadora y engranajes" loading="lazy" decoding="async">
                    <span class="industrial-menu-card__visual-tag">05 / COSTOS</span>
                </span>
                <span class="industrial-menu-card__body">
                    <span class="industrial-menu-card__eyebrow">Planeaci&oacute;n financiera</span>
                    <span class="industrial-menu-card__title">COSTOS</span>
                    <span class="industrial-menu-card__description">Visualiza gastos, presupuestos y tendencias por componente.</span>
                    <span class="industrial-menu-card__action">Pr&oacute;ximamente <i class="fas fa-clock" aria-hidden="true"></i></span>
                </span>
            </div>
            @endif

            @if($puedeVerCentralHidraulica)
            <a href="{{ route('pasteurizadora.central-hidraulica.index') }}" class="industrial-menu-card" data-number="06" style="--card-accent: #19d3b1; --card-glow: rgba(25, 211, 177, .34);">
                <span class="industrial-menu-card__shine" aria-hidden="true"></span>
                <span class="industrial-menu-card__topline" aria-hidden="true"></span>
                <span class="industrial-menu-card__visual" aria-hidden="true">
                    <span class="industrial-menu-card__visual-glow"></span>
                    <i class="fas fa-oil-can"></i>
                </span>
                <span class="industrial-menu-card__body">
                    <span class="industrial-menu-card__title">ANÁLISIS PASTEURIZADORA CENTRAL HIDRÁULICA</span>
                    <span class="industrial-menu-card__description">Registra y consulta análisis de Central Hidráulica.</span>
                    <span class="industrial-menu-card__action">Abrir módulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
            </a>
            <a href="{{ route('pasteurizadora.central-hidraulica.historico-revisados') }}" class="industrial-menu-card" data-number="07" style="--card-accent: #9a7bff; --card-glow: rgba(154, 123, 255, .34);">
                <span class="industrial-menu-card__shine" aria-hidden="true"></span>
                <span class="industrial-menu-card__topline" aria-hidden="true"></span>
                <span class="industrial-menu-card__visual" aria-hidden="true">
                    <span class="industrial-menu-card__visual-glow"></span>
                    <img src="{{ asset('images/lavadora/menu/historico-revisados.webp') }}" width="960" height="800" alt="" loading="lazy" decoding="async">
                </span>
                <span class="industrial-menu-card__body">
                    <span class="industrial-menu-card__title">HISTÓRICO DE REVISADOS CENTRAL HIDRÁULICA</span>
                    <span class="industrial-menu-card__description">Visualiza registros de componentes revisados de Central Hidráulica.</span>
                    <span class="industrial-menu-card__action">Abrir módulo <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </span>
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

</style>
@include('partials.industrial-menu-styles')
@endsection
