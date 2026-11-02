@extends('layouts.app')

@section('title', 'Pasteurizadoras')

@section('content')
@php
    $estadoPasteurizadoras = $estadoPasteurizadoras ?? [];
    $dashboardPasteurizadoraParte = $dashboardPasteurizadoraParte ?? \App\Models\AnalisisPasteurizadora::AREA_MECANICA;
    $dashboardPasteurizadoraParte = \App\Models\AnalisisPasteurizadora::normalizarArea($dashboardPasteurizadoraParte);
    $esDashboardMecanica = $dashboardPasteurizadoraParte === \App\Models\AnalisisPasteurizadora::AREA_MECANICA;
    $esDashboardCentral = $dashboardPasteurizadoraParte === \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA;
    $dashboardParteBaseQuery = request()->except('parte');
    $dashboardParteMecanicaUrl = route('dashboard.global.pasteurizadoras', array_merge($dashboardParteBaseQuery, [
        'parte' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA,
    ]));
    $dashboardParteCentralUrl = route('dashboard.global.pasteurizadoras', array_merge($dashboardParteBaseQuery, [
        'parte' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
    ]));
    $pasteurizadoras = collect($estadoPasteurizadoras);
    $resumenPasteurizadora = $resumenPasteurizadora ?? [];
    $fallasPorLineaPasteurizadora = collect($fallasPorLineaPasteurizadora ?? []);
    $componentesDanadosPasteurizadora = collect($componentesDanadosPasteurizadora ?? []);
    $historicoRevisionesPasteurizadora = collect($historicoRevisionesPasteurizadora ?? []);
    $analisis52124Pasteurizadora = $analisis52124Pasteurizadora ?? ['lineas' => [], 'criterios' => []];
    $analisis30147Pasteurizadora = $analisis30147Pasteurizadora ?? ['lineas' => [], 'criterios' => []];
    $planesAccionDashboardPasteurizadora = $planesAccionDashboardPasteurizadora ?? ['resumen' => [], 'estado_general' => [], 'por_linea' => [], 'planes' => []];
    $rankingDanosPasteurizadora = collect($rankingDanosPasteurizadora ?? []);
    $avanceRevisionPasteurizadora = $avanceRevisionPasteurizadora ?? ['labels' => [], 'porcentajes' => [], 'revisados' => [], 'totales' => [], 'lineas' => []];
    $ultimosAnalisisPasteurizadora = collect($ultimosAnalisisPasteurizadora ?? []);
    $trendFilters = $trendFilters ?? [];
    $usuarioActual = auth()->user();
    $puedeVerMecanicaPasteurizadora = $usuarioActual?->canAccessPasteurizadoraArea(\App\Models\AnalisisPasteurizadora::AREA_MECANICA) ?? false;
    $puedeVerCentralHidraulicaPasteurizadora = $usuarioActual?->canAccessPasteurizadoraArea(\App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA) ?? false;
    $puedeVerPlanesPasteurizadora = $puedeVerMecanicaPasteurizadora
        && ($usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_PASTEURIZADORA) ?? false);
    $puedeVerPlanesCentralHidraulica = $puedeVerCentralHidraulicaPasteurizadora
        && ($usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_PASTEURIZADORA) ?? false);
    $puedeVerTendenciasPasteurizadora = ($usuarioActual?->canAccessModule(\App\Models\User::MODULE_PASTEURIZADORA) ?? false)
        && ($usuarioActual?->canUseCustomPermission('ver tendencias pasteurizadora') ?? false);
    $totalPasteurizadoras = max((int) ($resumenPasteurizadora['total_pasteurizadoras'] ?? $pasteurizadoras->count()), 1);
    $estadoLineas = [
        'bueno' => $pasteurizadoras->where('estado.nivel', 'bueno')->count(),
        'operativo' => $pasteurizadoras->where('estado.nivel', 'operativo')->count(),
        'riesgo' => $pasteurizadoras->where('estado.nivel', 'riesgo')->count(),
        'critico' => $pasteurizadoras->where('estado.nivel', 'critico')->count(),
    ];
    $avancePromedio = (int) ($avanceRevisionPasteurizadora['promedio'] ?? round($pasteurizadoras->avg('estado.progreso_revision.porcentaje') ?? 0));
    $totalRevisados = (int) ($avanceRevisionPasteurizadora['total_revisados'] ?? $pasteurizadoras->sum(fn($item) => (int) data_get($item, 'estado.progreso_revision.revisados', 0)));
    $centralesHidraulicasPasteurizadora = collect($estadoCentralHidraulicaPasteurizadora ?? []);
    $fallasPorLineaCentralHidraulica = collect($fallasPorLineaCentralHidraulica ?? []);
    $historicoRevisionesCentralHidraulica = collect($historicoRevisionesCentralHidraulica ?? []);
    $analisis52124CentralHidraulica = $analisis52124CentralHidraulica ?? ['lineas' => [], 'criterios' => []];
    $analisis30147CentralHidraulica = $analisis30147CentralHidraulica ?? ['lineas' => [], 'criterios' => []];
    $planesAccionDashboardCentralHidraulica = $planesAccionDashboardCentralHidraulica ?? ['resumen' => [], 'estado_general' => [], 'por_linea' => [], 'planes' => []];
    $rankingDanosCentralHidraulica = collect($rankingDanosCentralHidraulica ?? []);
    $avanceRevisionCentralHidraulica = $avanceRevisionCentralHidraulica ?? ['labels' => [], 'porcentajes' => [], 'revisados' => [], 'totales' => [], 'lineas' => []];
    $resumenCentralHidraulicaPasteurizadora = $resumenCentralHidraulicaPasteurizadora ?? [
        'total_pasteurizadoras' => $centralesHidraulicasPasteurizadora->count(),
        'total_analisis' => 0,
        'alertas_criticas' => 0,
        'en_riesgo' => 0,
        'requiere_revision' => 0,
        'buen_estado' => 0,
        'pendientes_accion' => 0,
    ];
    $avanceCentralPromedio = (int) ($avanceRevisionCentralHidraulica['promedio'] ?? round($centralesHidraulicasPasteurizadora->avg('estado.progreso_revision.porcentaje') ?? 0));
    $totalCentralRevisados = (int) ($avanceRevisionCentralHidraulica['total_revisados'] ?? $centralesHidraulicasPasteurizadora->sum(fn($item) => (int) data_get($item, 'estado.progreso_revision.revisados', 0)));
@endphp

@vite('resources/css/dashboard-pasteurizadora.css')

<div class="dashboard-container">
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 transition">
            <i class="fas fa-arrow-left"></i>
            <span>Volver</span>
        </a>
    </div>

    <div class="mb-6">
        <div class="dashboard-header">
            <div class="min-w-0">
                <h1 class="flex min-w-0 items-center gap-2 break-words text-2xl font-bold text-gray-800">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    Dashboard Pasteurizadoras
                </h1>
                @auth
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        Rol: {{ $userRoleLabel ?? auth()->user()->role_label }}
                    </p>
                @endauth
            </div>
            <div class="dashboard-actions">
                <button onclick="refreshData()" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
                <div class="dashboard-part-switch" aria-label="Vistas del dashboard de Pasteurizadora">
                    @if($puedeVerMecanicaPasteurizadora)
                        <a href="{{ $dashboardParteMecanicaUrl }}"
                           class="dashboard-part-btn {{ $esDashboardMecanica ? 'active' : '' }}"
                           aria-current="{{ $esDashboardMecanica ? 'page' : 'false' }}">
                            <i class="fas fa-cogs"></i>
                            <span>Parte Mecanica</span>
                        </a>
                    @endif
                    @if($puedeVerCentralHidraulicaPasteurizadora)
                        <a href="{{ $dashboardParteCentralUrl }}"
                           class="dashboard-part-btn dashboard-part-btn--central {{ $esDashboardCentral ? 'active' : '' }}"
                           aria-current="{{ $esDashboardCentral ? 'page' : 'false' }}">
                            <i class="fas fa-droplet"></i>
                            <span>Central Hidraulica</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @if($esDashboardMecanica)
    <div class="stats-grid">
        <div class="stat-card stat-card--primary">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-label">Total Pasteurizadoras</div>
            <div class="stat-value">{{ $resumenPasteurizadora['total_pasteurizadoras'] }}</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Críticas</div>
            <div class="stat-value">{{ $resumenPasteurizadora['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card stat-card--risk">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">Severo / Moderado</div>
            <div class="stat-value">{{ $resumenPasteurizadora['en_riesgo'] }}</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-icon"><i class="fas fa-tools"></i></div>
            <div class="stat-label">Requiere Revisión</div>
            <div class="stat-value">{{ $resumenPasteurizadora['requiere_revision'] }}</div>
        </div>
        <div class="stat-card stat-card--success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value">{{ $resumenPasteurizadora['buen_estado'] }}</div>
        </div>
        <div class="stat-card stat-card--action">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-label">Pendientes Acción</div>
            <div class="stat-value">{{ $resumenPasteurizadora['pendientes_accion'] }}</div>
        </div>
    </div>

    <div class="section-title">
        <i class="fas fa-temperature-high"></i>
        ESTADO GENERAL DE PASTEURIZADORAS
    </div>

    <div class="lavadoras-grid">
        @foreach($pasteurizadoras as $pasteurizadora)
            @php
                $estado = $pasteurizadora['estado'];
                $nivel = $estado['nivel'] ?? 'bueno';
                $isCritical = $nivel === 'critico';
                $cardClass = $nivel === 'bueno'
                    ? 'buen-estado'
                    : ($nivel === 'operativo' ? 'operativo-estado' : ($nivel === 'riesgo' ? 'riesgo-estado' : 'critico-estado'));
                if ($isCritical) {
                    $cardClass .= ' alert-critical';
                }
                $progreso = $estado['progreso_revision'] ?? ['porcentaje' => 0];
                $porcentaje = (int) ($progreso['porcentaje'] ?? 0);
            @endphp
            <div class="lavadora-card {{ $cardClass }}">
                <div class="lavadora-card-header">
                    <div class="lavadora-nombre">
                        <i class="fas fa-temperature-high status-icon"></i>
                        {{ $pasteurizadora['nombre'] }}
                    </div>
                    <div>
                        <span class="status-tag {{ $nivel === 'bueno' ? 'bueno' : ($nivel === 'operativo' ? 'operativo' : ($nivel === 'riesgo' ? 'riesgo' : 'critico')) }}">
                            <i class="fas {{ $nivel === 'bueno' ? 'fa-check-circle' : ($nivel === 'operativo' ? 'fa-tools' : ($nivel === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle')) }}"></i>
                            {{ $nivel === 'bueno' ? 'Buen estado' : ($nivel === 'operativo' ? 'Requiere revisión' : ($nivel === 'riesgo' ? 'Severo / Moderado' : 'Crítico')) }}
                        </span>
                    </div>
                </div>
                <div class="lavadora-card-body">
                    <div class="lavadora-mensaje">
                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                        {{ $estado['mensaje'] }}
                    </div>

                    @if(isset($estado['alert_carousel']) && count($estado['alert_carousel']) > 0)
                        <div class="lavadora-carousel" id="pasteurizadora-carousel-{{ $pasteurizadora['id'] }}">
                            <div class="lavadora-carousel-track">
                                @foreach($estado['alert_carousel'] as $carouselIndex => $item)
                                    <div class="carousel-slide {{ $carouselIndex === 0 ? 'active' : '' }}" data-slide="{{ $carouselIndex }}">
                                        <div class="carousel-slide-content">
                                            @if(($item['type'] ?? '') === 'componente')
                                                <div class="carousel-slide-image">
                                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" onerror="this.src='{{ $item['fallback_image'] ?? asset('images/icono_pas.png') }}'" />
                                                </div>
                                            @else
                                                <div class="carousel-slide-icon">
                                                    <i class="fas {{ $item['icon'] ?? 'fa-info-circle' }}"></i>
                                                </div>
                                            @endif
                                            <div class="carousel-slide-info">
                                                @if(!empty($item['estado_label']))
                                                    <div class="mb-1">
                                                        <span class="severity-pill {{ $item['estado_key'] ?? 'estable' }}">
                                                            {{ $item['estado_label'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                                <div class="carousel-slide-title">{{ $item['title'] }}</div>
                                                @if(!empty($item['subtitle']) && $item['subtitle'] !== ($item['meta'] ?? null))
                                                    <div class="carousel-slide-subtitle">{{ $item['subtitle'] }}</div>
                                                @endif
                                                @if(!empty($item['componentes_revisados_label']))
                                                    <div class="carousel-slide-meta font-semibold">{{ $item['componentes_revisados_label'] }}</div>
                                                @endif
                                                @if(!empty($item['detail']) || !empty($item['description']))
                                                    <div class="carousel-slide-detail">{{ $item['detail'] ?? $item['description'] }}</div>
                                                @endif
                                                @if(!empty($item['meta']))
                                                    <div class="carousel-slide-meta">Orden: {{ $item['meta'] }}</div>
                                                @endif
                                                @if(!empty($item['fecha']))
                                                    <div class="carousel-slide-meta">Fecha: {{ $item['fecha'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(count($estado['alert_carousel']) > 1)
                                <div class="carousel-controls">
                                    <button type="button" class="carousel-button carousel-prev" aria-label="Anterior">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="carousel-dots">
                                        @foreach($estado['alert_carousel'] as $carouselIndex => $item)
                                            <span class="carousel-dot {{ $carouselIndex === 0 ? 'active' : '' }}" data-index="{{ $carouselIndex }}"></span>
                                        @endforeach
                                    </div>
                                    <button type="button" class="carousel-button carousel-next" aria-label="Siguiente">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    @php
                        $conteoAlertas = $estado['conteo_alertas'] ?? [];
                        $totalAlertas = array_sum($conteoAlertas);
                    @endphp

                    @if($totalAlertas > 0)
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if(($conteoAlertas['critico'] ?? 0) > 0)
                                <span class="severity-pill critico">{{ $conteoAlertas['critico'] }} requiere cambio</span>
                            @endif
                            @if(($conteoAlertas['severo'] ?? 0) > 0)
                                <span class="severity-pill severo">{{ $conteoAlertas['severo'] }} severo</span>
                            @endif
                            @if(($conteoAlertas['moderado'] ?? 0) > 0)
                                <span class="severity-pill moderado">{{ $conteoAlertas['moderado'] }} moderado</span>
                            @endif
                            @if(($conteoAlertas['revision'] ?? 0) > 0)
                                <span class="severity-pill revision">{{ $conteoAlertas['revision'] }} revision</span>
                            @endif
                        </div>
                    @endif

                    <div class="lavadora-metricas">
                        <div class="metric-item">
                            <div class="metric-label">Avance</div>
                            <div class="metric-value" style="color: var(--primary-blue);">{{ $porcentaje }}%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Revisados</div>
                            <div class="metric-value" style="color: var(--success-green);">
                                {{ $progreso['revisados'] ?? $progreso['componentes_revisados'] ?? 0 }}
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Pendientes</div>
                            <div class="metric-value" style="color: {{ ($estado['acciones_pendientes'] ?? 0) > 0 ? 'var(--danger-red)' : 'var(--success-green)' }};">
                                {{ $estado['acciones_pendientes'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lavadora-card-footer">
                    <button onclick='showPasteurizadoraDetail(@json($pasteurizadora))'
                            class="lavadora-card-action">
                        <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @php
        $planesResumen = $planesAccionDashboardPasteurizadora['resumen'] ?? [];
        $planesEstado = $planesAccionDashboardPasteurizadora['estado_general'] ?? [];
        $planesPorLinea = collect($planesAccionDashboardPasteurizadora['por_linea'] ?? []);
        $planesActivos = collect($planesAccionDashboardPasteurizadora['planes'] ?? []);
        $avanceLineas = collect($avanceRevisionPasteurizadora['lineas'] ?? [])->sortBy('porcentaje')->values();
    @endphp

    <div class="dashboard-panels-grid">
        <div class="chart-card fallas-card">
            <h3>
                <i class="fas fa-chart-bar"></i>
                <span>Daños por Línea</span>
            </h3>
            <div class="chart-container">
                <canvas id="fallasPasteurizadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Datos reales desde análisis activos de pasteurizadora
            </div>
        </div>

        <div class="chart-card componentes-card">
            <h3>
                <i class="fas fa-chart-pie"></i>
                <span>Componentes Más Dañados</span>
            </h3>
            <div class="chart-container">
                <canvas id="componentesPasteurizadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Componentes mecánicos con mayor incidencia de daño o desgaste
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card ranking-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Daños</span>
            </h3>
            <ul class="ranking-list">
                @forelse($rankingDanosPasteurizadora->take(8) as $index => $item)
                    @php
                        $estado = $item['estado'];
                        $nivelEstado = $estado['nivel'] ?? 'bueno';
                        $estadoLabel = $nivelEstado === 'bueno'
                            ? 'Buen estado'
                            : ($nivelEstado === 'operativo'
                                ? 'Requiere revisión'
                                : ($nivelEstado === 'riesgo' ? 'Severo / Moderado' : 'Crítico'));
                        $pendientes = (int) ($estado['acciones_pendientes'] ?? 0);
                    @endphp
                    <li class="ranking-item">
                        <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="ranking-asset">
                            <div class="asset-media">
                                <i class="fas fa-temperature-high" style="font-size: 18px; color: #2563eb;"></i>
                            </div>
                        <div class="ranking-info">
                            <div class="ranking-linea">{{ $item['linea'] ?? $item['nombre'] }}</div>
                            <div class="ranking-puntaje">
                                <i class="fas fa-triangle-exclamation"></i>
                                Criticas: {{ $item['criticas'] ?? 0 }} - Severo / Moderado: {{ ($item['severos'] ?? 0) + ($item['moderados'] ?? 0) }} - Revision: {{ $item['requiere_revision'] ?? 0 }}
                            </div>
                            <div class="ranking-meta">
                                Total con dano: {{ $item['total_danos'] ?? 0 }} de {{ $item['total_componentes'] ?? 0 }} registros - Impacto {{ number_format((float) ($item['porcentaje_impacto'] ?? 0), 1) }}% - Revision: {{ $item['fecha_analisis_humana'] ?? 'Sin fecha' }}
                            </div>
                        </div>
                        </div>
                        <div class="ranking-status-stack">
                            <span class="severity-pill {{ $item['prioridad'] ?? 'estable' }}">{{ $item['prioridad_label'] ?? $estadoLabel }}</span>
                        <div class="ranking-badge">
                            <i class="fas fa-bolt"></i>
                            {{ number_format((float) ($item['total_danos'] ?? $pendientes), 0) }} danos
                        </div>
                        </div>
                    </li>
                @empty
                    <li class="ranking-item">
                        <div class="ranking-position">0</div>
                        <div class="ranking-asset">
                        <div class="ranking-info">
                            <div class="ranking-linea">Sin datos</div>
                            <div class="ranking-puntaje">No hay pasteurizadoras para priorizar</div>
                        </div>
                        </div>
                    </li>
                @endforelse
            </ul>
            <div class="ranking-footer">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Daños activos
                </div>
            </div>
        </div>

    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card planes-card">
            <h3>
                <i class="fas fa-tasks"></i>
                <span>Plan de Acción</span>
            </h3>
            <div class="status-banner {{ $planesEstado['nivel'] ?? 'estable' }}">
                <i class="fas fa-clipboard-check"></i>
                <span>{{ $planesEstado['label'] ?? 'Controlado' }}: {{ $planesEstado['mensaje'] ?? 'Sin planes abiertos con riesgo inmediato.' }}</span>
            </div>
            <div class="mini-stats-grid">
                <div class="mini-stat info">
                    <div class="mini-stat-label">Activos</div>
                    <div class="mini-stat-value">{{ $planesResumen['activos'] ?? 0 }}</div>
                </div>
                <div class="mini-stat danger">
                    <div class="mini-stat-label">Alta prioridad</div>
                    <div class="mini-stat-value">{{ $planesResumen['prioridad_alta'] ?? 0 }}</div>
                </div>
                <div class="mini-stat warning">
                    <div class="mini-stat-label">Próx. 7 días</div>
                    <div class="mini-stat-value">{{ $planesResumen['proximos_7_dias'] ?? 0 }}</div>
                </div>
                <div class="mini-stat success">
                    <div class="mini-stat-label">Cierre</div>
                    <div class="mini-stat-value">{{ $planesResumen['avance'] ?? 0 }}%</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="planesPasteurizadoraChart"></canvas>
            </div>
            <div class="worklist">
                @forelse($planesActivos->take(4) as $plan)
                    <div class="work-item">
                        <div class="work-item-top">
                            <div>
                                <div class="work-title">{{ $plan['linea'] }} · {{ Str::limit($plan['actividad'] ?? 'Sin actividad', 62) }}</div>
                                <div class="work-meta">Próxima fecha: {{ $plan['proxima_fecha_humana'] ?? 'Sin fecha' }}</div>
                            </div>
                            <span class="severity-pill {{ ($plan['prioridad'] ?? 'baja') === 'alta' ? 'critico' : (($plan['prioridad'] ?? 'baja') === 'media' ? 'severo' : 'estable') }}">
                                {{ $plan['prioridad_label'] ?? 'Baja' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="work-item">
                        <div class="work-title">Sin planes activos</div>
                        <div class="work-meta">No hay actividades abiertas de plan de acción mecánico.</div>
                    </div>
                @endforelse
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                <span>Planes reales del módulo Pasteurizadora.</span>
                @if($puedeVerPlanesPasteurizadora)
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}">Abrir plan</a>
                @endif
            </div>
        </div>

        <div class="chart-card avance-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Avance de Revisión Mecánica</span>
            </h3>
            <div class="mini-stats-grid compact">
                <div class="mini-stat info">
                    <div class="mini-stat-label">Promedio</div>
                    <div class="mini-stat-value">{{ $avancePromedio }}%</div>
                    <div class="mini-stat-meta">avance global</div>
                </div>
                <div class="mini-stat success">
                    <div class="mini-stat-label">Revisados</div>
                    <div class="mini-stat-value">{{ number_format($totalRevisados) }}</div>
                    <div class="mini-stat-meta">posiciones revisadas</div>
                </div>
        
            </div>
            <div class="chart-container">
                <canvas id="avanceRevisionPasteurizadoraChart"></canvas>
            </div>
            <div class="linea-breakdown">
                @forelse($avanceLineas->take(4) as $lineaAvance)
                    <div class="breakdown-item">
                        <div class="breakdown-item-top">
                            <div>
                                <div class="breakdown-title">{{ $lineaAvance['linea'] }}</div>
                                <div class="breakdown-meta">{{ number_format((int) ($lineaAvance['revisados'] ?? 0)) }} de {{ number_format((int) ($lineaAvance['total'] ?? 0)) }} posiciones revisadas</div>
                            </div>
                            <span class="severity-pill {{ (int) ($lineaAvance['porcentaje'] ?? 0) >= 90 ? 'estable' : ((int) ($lineaAvance['porcentaje'] ?? 0) >= 60 ? 'revision' : 'severo') }}">
                                {{ (int) ($lineaAvance['porcentaje'] ?? 0) }}%
                            </span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar" style="width: {{ min(100, max(0, (int) ($lineaAvance['porcentaje'] ?? 0))) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="breakdown-item">
                        <div class="breakdown-title">Sin avance registrado</div>
                        <div class="breakdown-meta">Aún no hay revisiones mecánicas para graficar.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid dashboard-history-trend-grid">
        <div class="chart-card historico-card historico-dashboard-card">
            <h3>
                <i class="fas fa-history"></i>
                <span>Histórico de Revisiones</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-cube" style="color: #3b82f6;"></i> Componente</th>
                            <th><i class="fas fa-calendar-alt" style="color: #8b5cf6;"></i> Último análisis</th>
                            <th class="text-right"><i class="fas fa-hashtag" style="color: #10b981;"></i> Análisis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historicoRevisionesPasteurizadora as $item)
                            <tr>
                                <td data-label="Componente"><i class="fas fa-microchip mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                <td data-label="Último análisis">{{ $item['ultimo_analisis'] }}</td>
                                <td data-label="Análisis">{{ $item['total_analisis'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Sin análisis registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                <span>Información conectada con el historial de análisis de pasteurizadora</span>
                @if($puedeVerMecanicaPasteurizadora)
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.historico-revisados') }}">Abrir historico</a>
                @endif
            </div>
        </div>

        <div class="chart-card trend-card trend-card-primary dashboard-panel dashboard-trend-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Análisis 52-12-4 | Tendencia de Daños</span>
            </h3>
            <div class="dashboard-trend-main-header">
                <div class="dashboard-trend-title-block">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <div class="dashboard-trend-eyebrow">Análisis de tendencia Pasteurizadora</div>
                        <h3 class="dashboard-trend-heading">Análisis 52-12-4</h3>
                        <p class="dashboard-trend-subcopy">Lectura ejecutiva por pasteurizadora con ventanas de 52, 12 y 4 semanas.</p>
                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters" data-auto-filter-form>
                <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_MECANICA }}">
                <select id="analisis52124PasteurizadoraLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="52124">
                    @forelse(($analisis52124Pasteurizadora['lineas'] ?? []) as $lineaTrend)
                        <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis52124Pasteurizadora, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                    @empty
                        <option value="">Sin pasteurizadoras</option>
                    @endforelse
                </select>
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia30147.from_param', 'trend_30147_desde') }}" value="{{ data_get($trendFilters, 'tendencia30147.from_input', '') }}">
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia30147.to_param', 'trend_30147_hasta') }}" value="{{ data_get($trendFilters, 'tendencia30147.to_input', '') }}">
                <div class="trend-filter-field">
                    <label>Desde</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia.from_param', 'trend_52124_desde') }}" value="{{ data_get($trendFilters, 'tendencia.from_input', '') }}">
                </div>
                <div class="trend-filter-field">
                    <label>Hasta</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia.to_param', 'trend_52124_hasta') }}" value="{{ data_get($trendFilters, 'tendencia.to_input', '') }}">
                </div>
                <a href="{{ route('dashboard.global.pasteurizadoras', ['parte' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA]) }}" class="trend-open-link">
                    <i class="fas fa-rotate-left"></i>
                    Limpiar
                </a>
                @if($puedeVerTendenciasPasteurizadora)
                    <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4', ['area' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA]) }}" class="trend-open-link">
                        <i class="fas fa-up-right-from-square"></i>
                        Abrir
                    </a>
                @endif
            </form>
            <div class="dashboard-trend-machine-strip">
                <div class="dashboard-trend-machine-header">
                    <i class="fas fa-industry"></i>
                    <span>Pasteurizadoras incluidas</span>
                </div>
                <div id="trend52124MachineGrid" class="dashboard-trend-machine-grid"></div>
            </div>
            <div class="dashboard-trend-brief">
                <div id="trend52124StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                    <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                    <div id="trend52124StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                    <p id="trend52124StatusCopy" class="dashboard-trend-status-copy">
                        Se está calculando el comportamiento reciente con información de análisis de Pasteurizadora.
                    </p>
                    <div id="trend52124StatusNote" class="dashboard-trend-status-note">Ventanas: 52, 12 y 4 semanas.</div>
                </div>
                <div id="trend52124WindowCards" class="dashboard-trend-window-grid"></div>
            </div>
            <div class="dashboard-trend-chart-shell">
                <div class="dashboard-trend-chart-header">
                    <div class="dashboard-trend-chart-title">
                        <i class="fas fa-chart-column"></i>
                        <div>
                            <h4>Comparativo por pasteurizadora</h4>
                            <p>Último corte disponible para cada ventana de análisis.</p>
                        </div>
                    </div>
                    <div class="dashboard-trend-view-selector">
                        <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="52124" data-pasteur-trend-type="bar">Barras</button>
                        <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="52124" data-pasteur-trend-type="line">Línea</button>
                    </div>
                    <div id="trend52124Caption" class="dashboard-trend-caption">Corte actual de tendencia 52-12-4.</div>
                </div>
                <div class="chart-container dashboard-trend-chart-container">
                    <canvas id="analisis52124PasteurizadoraChart"></canvas>
                </div>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                <span>Tendencia automática calculada desde daños registrados en los análisis de Pasteurizadora</span>
            </div>
        </div>
        <div class="chart-card trend-card trend-card-side dashboard-panel dashboard-trend-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Análisis 30-14-7 | Tendencia de Daños</span>
            </h3>
            <div class="dashboard-trend-main-header">
                <div class="dashboard-trend-title-block">
                    <i class="fas fa-bolt"></i>
                    <div>
                        <div class="dashboard-trend-eyebrow">Análisis de tendencia Pasteurizadora</div>
                        <h3 class="dashboard-trend-heading">Análisis 30-14-7</h3>
                        <p class="dashboard-trend-subcopy">Lectura ejecutiva por pasteurizadora con ventanas de 30, 14 y 7 días.</p>
                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters" data-auto-filter-form>
                <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_MECANICA }}">
                <select id="analisis30147PasteurizadoraLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="30147">
                    @forelse(($analisis30147Pasteurizadora['lineas'] ?? []) as $lineaTrend)
                        <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis30147Pasteurizadora, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                    @empty
                        <option value="">Sin pasteurizadoras</option>
                    @endforelse
                </select>
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia.from_param', 'trend_52124_desde') }}" value="{{ data_get($trendFilters, 'tendencia.from_input', '') }}">
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia.to_param', 'trend_52124_hasta') }}" value="{{ data_get($trendFilters, 'tendencia.to_input', '') }}">
                <div class="trend-filter-field">
                    <label>Desde</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia30147.from_param', 'trend_30147_desde') }}" value="{{ data_get($trendFilters, 'tendencia30147.from_input', '') }}">
                </div>
                <div class="trend-filter-field">
                    <label>Hasta</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia30147.to_param', 'trend_30147_hasta') }}" value="{{ data_get($trendFilters, 'tendencia30147.to_input', '') }}">
                </div>
                <a href="{{ route('dashboard.global.pasteurizadoras', ['parte' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA]) }}" class="trend-open-link">
                    <i class="fas fa-rotate-left"></i>
                    Limpiar
                </a>
                @if($puedeVerTendenciasPasteurizadora)
                    <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7', ['area' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA]) }}" class="trend-open-link">
                        <i class="fas fa-up-right-from-square"></i>
                        Abrir
                    </a>
                @endif
            </form>
            <div class="dashboard-trend-machine-strip">
                <div class="dashboard-trend-machine-header">
                    <i class="fas fa-industry"></i>
                    <span>Pasteurizadoras incluidas</span>
                </div>
                <div id="trend30147MachineGrid" class="dashboard-trend-machine-grid"></div>
            </div>
            <div class="dashboard-trend-brief">
                <div id="trend30147StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                    <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                    <div id="trend30147StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                    <p id="trend30147StatusCopy" class="dashboard-trend-status-copy">
                        Se está calculando el comportamiento reciente con información de análisis de Pasteurizadora.
                    </p>
                    <div id="trend30147StatusNote" class="dashboard-trend-status-note">Ventanas: 30, 14 y 7 días.</div>
                </div>
                <div id="trend30147WindowCards" class="dashboard-trend-window-grid"></div>
            </div>
            <div class="dashboard-trend-chart-shell">
                <div class="dashboard-trend-chart-header">
                    <div class="dashboard-trend-chart-title">
                        <i class="fas fa-chart-column"></i>
                        <div>
                            <h4>Comparativo por pasteurizadora</h4>
                            <p>Último corte disponible para cada ventana de análisis.</p>
                        </div>
                    </div>
                    <div class="dashboard-trend-view-selector">
                        <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="30147" data-pasteur-trend-type="bar">Barras</button>
                        <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="30147" data-pasteur-trend-type="line">Línea</button>
                    </div>
                    <div id="trend30147Caption" class="dashboard-trend-caption">Corte actual de tendencia 30-14-7.</div>
                </div>
                <div class="chart-container dashboard-trend-chart-container">
                    <canvas id="analisis30147PasteurizadoraChart"></canvas>
                </div>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                <span>Seguimiento de fallas recientes calculado automáticamente desde los análisis registrados de Pasteurizadora</span>
            </div>
        </div>
    </div>
</div>

    @elseif($esDashboardCentral)
        @php
            $planesCentralResumen = $planesAccionDashboardCentralHidraulica['resumen'] ?? [];
            $planesCentralEstado = $planesAccionDashboardCentralHidraulica['estado_general'] ?? [];
            $planesCentralActivos = collect($planesAccionDashboardCentralHidraulica['planes'] ?? []);
            $avanceCentralLineas = collect($avanceRevisionCentralHidraulica['lineas'] ?? [])->sortBy('porcentaje')->values();
        @endphp

        <div class="stats-grid">
            <div class="stat-card stat-card--primary">
                <div class="stat-icon"><i class="fas fa-oil-can"></i></div>
                <div class="stat-label">Centrales Configuradas</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['total_pasteurizadoras'] }}</div>
            </div>
            <div class="stat-card stat-card--danger">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-label">Alertas Criticas</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['alertas_criticas'] }}</div>
            </div>
            <div class="stat-card stat-card--risk">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-label">Severo / Moderado</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['en_riesgo'] }}</div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-icon"><i class="fas fa-tools"></i></div>
                <div class="stat-label">Requiere Revision</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['requiere_revision'] }}</div>
            </div>
            <div class="stat-card stat-card--success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Buen Estado</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['buen_estado'] }}</div>
            </div>
            <div class="stat-card stat-card--action">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-label">Pendientes Accion</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['pendientes_accion'] }}</div>
            </div>
        </div>

        <div class="section-title">
            <i class="fas fa-droplet"></i>
            CENTRAL HIDRAULICA DE PASTEURIZADORAS
        </div>

        <div class="lavadoras-grid">
            @forelse($centralesHidraulicasPasteurizadora as $central)
                @php
                    $estado = $central['estado'];
                    $nivel = $estado['nivel'] ?? 'bueno';
                    $isCritical = $nivel === 'critico';
                    $cardClass = $nivel === 'bueno'
                        ? 'buen-estado'
                        : ($nivel === 'operativo' ? 'operativo-estado' : ($nivel === 'riesgo' ? 'riesgo-estado' : 'critico-estado'));
                    if ($isCritical) {
                        $cardClass .= ' alert-critical';
                    }
                    $progreso = $estado['progreso_revision'] ?? ['porcentaje' => 0];
                    $porcentaje = (int) ($progreso['porcentaje'] ?? 0);
                    $conteoAlertas = $estado['conteo_alertas'] ?? [];
                    $totalAlertas = array_sum($conteoAlertas);
                @endphp
                <div class="lavadora-card {{ $cardClass }}">
                    <div class="lavadora-card-header">
                        <div class="lavadora-nombre">
                            <i class="fas fa-oil-can status-icon"></i>
                            {{ $central['nombre'] }}
                        </div>
                        <div>
                            <span class="status-tag {{ $nivel === 'bueno' ? 'bueno' : ($nivel === 'operativo' ? 'operativo' : ($nivel === 'riesgo' ? 'riesgo' : 'critico')) }}">
                                <i class="fas {{ $nivel === 'bueno' ? 'fa-check-circle' : ($nivel === 'operativo' ? 'fa-tools' : ($nivel === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle')) }}"></i>
                                {{ $nivel === 'bueno' ? 'Buen estado' : ($nivel === 'operativo' ? 'Requiere revision' : ($nivel === 'riesgo' ? 'Severo / Moderado' : 'Critico')) }}
                            </span>
                        </div>
                    </div>
                    <div class="lavadora-card-body">
                        <div class="lavadora-mensaje">
                            <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                            {{ $estado['mensaje'] }}
                        </div>

                        @if(isset($estado['alert_carousel']) && count($estado['alert_carousel']) > 0)
                            <div class="lavadora-carousel" id="central-carousel-{{ $central['id'] }}">
                                <div class="lavadora-carousel-track">
                                    @foreach($estado['alert_carousel'] as $carouselIndex => $item)
                                        <div class="carousel-slide {{ $carouselIndex === 0 ? 'active' : '' }}" data-slide="{{ $carouselIndex }}">
                                            <div class="carousel-slide-content">
                                                <div class="carousel-slide-icon">
                                                    <i class="fas {{ $item['icon'] ?? 'fa-droplet' }}"></i>
                                                </div>
                                                <div class="carousel-slide-info">
                                                    @if(!empty($item['estado_label']))
                                                        <div class="mb-1">
                                                            <span class="severity-pill {{ $item['estado_key'] ?? 'estable' }}">
                                                                {{ $item['estado_label'] }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <div class="carousel-slide-title">{{ $item['title'] }}</div>
                                                    @if(!empty($item['subtitle']) && $item['subtitle'] !== ($item['meta'] ?? null))
                                                        <div class="carousel-slide-subtitle">{{ $item['subtitle'] }}</div>
                                                    @endif
                                                    @if(!empty($item['detail']) || !empty($item['description']))
                                                        <div class="carousel-slide-detail">{{ $item['detail'] ?? $item['description'] }}</div>
                                                    @endif
                                                    @if(!empty($item['meta']))
                                                        <div class="carousel-slide-meta">Orden: {{ $item['meta'] }}</div>
                                                    @endif
                                                    @if(!empty($item['fecha']))
                                                        <div class="carousel-slide-meta">Fecha: {{ $item['fecha'] }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if(count($estado['alert_carousel']) > 1)
                                    <div class="carousel-controls">
                                        <button type="button" class="carousel-button carousel-prev" aria-label="Anterior">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div class="carousel-dots">
                                            @foreach($estado['alert_carousel'] as $carouselIndex => $item)
                                                <span class="carousel-dot {{ $carouselIndex === 0 ? 'active' : '' }}" data-index="{{ $carouselIndex }}"></span>
                                            @endforeach
                                        </div>
                                        <button type="button" class="carousel-button carousel-next" aria-label="Siguiente">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($totalAlertas > 0)
                            <div class="flex flex-wrap gap-2 mb-3">
                                @if(($conteoAlertas['critico'] ?? 0) > 0)
                                    <span class="severity-pill critico">{{ $conteoAlertas['critico'] }} requiere cambio</span>
                                @endif
                                @if(($conteoAlertas['severo'] ?? 0) > 0)
                                    <span class="severity-pill severo">{{ $conteoAlertas['severo'] }} severo</span>
                                @endif
                                @if(($conteoAlertas['moderado'] ?? 0) > 0)
                                    <span class="severity-pill moderado">{{ $conteoAlertas['moderado'] }} moderado</span>
                                @endif
                                @if(($conteoAlertas['revision'] ?? 0) > 0)
                                    <span class="severity-pill revision">{{ $conteoAlertas['revision'] }} revision</span>
                                @endif
                            </div>
                        @endif

                        @if(!empty($estado['pisos']))
                            <div class="flex flex-wrap gap-2 mb-3">
                                @foreach($estado['pisos'] as $piso)
                                    <span class="severity-pill {{ ($piso['alertas'] ?? 0) > 0 ? 'revision' : 'estable' }}">
                                        {{ $piso['label'] }} {{ (int) ($piso['porcentaje'] ?? 0) }}%
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="lavadora-metricas">
                            <div class="metric-item">
                                <div class="metric-label">Avance</div>
                                <div class="metric-value" style="color: var(--primary-blue);">{{ $porcentaje }}%</div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Revisados</div>
                                <div class="metric-value" style="color: var(--success-green);">
                                    {{ $progreso['revisados'] ?? $progreso['componentes_revisados'] ?? 0 }}
                                </div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Pendientes</div>
                                <div class="metric-value" style="color: {{ ($estado['acciones_pendientes'] ?? 0) > 0 ? 'var(--danger-red)' : 'var(--success-green)' }};">
                                    {{ $estado['acciones_pendientes'] ?? 0 }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lavadora-card-footer">
                        <button onclick='showCentralHidraulicaDetail(@json($central))'
                                class="lavadora-card-action">
                            <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                        </button>
                    </div>
                </div>
            @empty
                <div class="chart-card">
                    <h3>
                        <i class="fas fa-oil-can"></i>
                        <span>Sin centrales configuradas</span>
                    </h3>
                    <div class="chart-description">
                        <i class="fas fa-info-circle"></i>
                        Aun no hay lineas con configuracion de Central Hidraulica.
                    </div>
                </div>
            @endforelse
        </div>

        <div class="dashboard-panels-grid">
            <div class="chart-card fallas-card">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    <span>Fallas por Linea</span>
                </h3>
                <div class="chart-container">
                    <canvas id="fallasCentralHidraulicaChart"></canvas>
                </div>
            </div>
        </div>

        <div class="dashboard-panels-grid">
            <div class="chart-card ranking-card">
                <h3>
                    <i class="fas fa-trophy"></i>
                    <span>Ranking de Daño Hidraulico</span>
                </h3>
                <ul class="ranking-list">
                    @forelse($rankingDanosCentralHidraulica->take(8) as $index => $item)
                        @php
                            $estado = $item['estado'];
                            $nivelEstado = $estado['nivel'] ?? 'bueno';
                            $estadoLabel = $nivelEstado === 'bueno'
                                ? 'Buen estado'
                                : ($nivelEstado === 'operativo'
                                    ? 'Requiere revision'
                                    : ($nivelEstado === 'riesgo' ? 'Severo / Moderado' : 'Critico'));
                            $pendientes = (int) ($estado['acciones_pendientes'] ?? 0);
                        @endphp
                        <li class="ranking-item">
                            <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                                {{ $index + 1 }}
                            </div>
                            <div class="ranking-asset">
                                <div class="asset-media">
                                    <i class="fas fa-oil-can" style="font-size: 18px; color: #0f766e;"></i>
                                </div>
                                <div class="ranking-info">
                                    <div class="ranking-linea">{{ $item['linea'] ?? $item['nombre'] }}</div>
                                    <div class="ranking-puntaje">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        Criticas: {{ $item['criticas'] ?? 0 }} - Severo / Moderado: {{ ($item['severos'] ?? 0) + ($item['moderados'] ?? 0) }} - Revision: {{ $item['requiere_revision'] ?? 0 }}
                                    </div>
                                    <div class="ranking-meta">
                                        Pisos: {{ $item['pisos_afectados'] ?? 'Sin alertas' }} - Impacto {{ number_format((float) ($item['porcentaje_impacto'] ?? 0), 1) }}% - Revision: {{ $item['fecha_analisis_humana'] ?? 'Sin fecha' }}
                                    </div>
                                </div>
                            </div>
                            <div class="ranking-status-stack">
                                <span class="severity-pill {{ $item['prioridad'] ?? 'estable' }}">{{ $item['prioridad_label'] ?? $estadoLabel }}</span>
                                <div class="ranking-badge">
                                    <i class="fas fa-bolt"></i>
                                    {{ number_format((float) ($item['total_danos'] ?? $pendientes), 0) }} danos
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="ranking-item">
                            <div class="ranking-position">0</div>
                            <div class="ranking-asset">
                                <div class="ranking-info">
                                    <div class="ranking-linea">Sin datos</div>
                                    <div class="ranking-puntaje">No hay centrales para priorizar</div>
                                </div>
                            </div>
                        </li>
                    @endforelse
                </ul>
                <div class="ranking-footer">
                    <div>
                        <i class="fas fa-info-circle"></i>
                        Daños activos de Central Hidraulica
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-panels-grid">
            <div class="chart-card planes-card">
                <h3>
                    <i class="fas fa-tasks"></i>
                    <span>Plan de Accion</span>
                </h3>
                <div class="status-banner {{ $planesCentralEstado['nivel'] ?? 'estable' }}">
                    <i class="fas fa-clipboard-check"></i>
                    <span>{{ $planesCentralEstado['label'] ?? 'Controlado' }}: {{ $planesCentralEstado['mensaje'] ?? 'Sin planes abiertos con riesgo inmediato.' }}</span>
                </div>
                <div class="mini-stats-grid">
                    <div class="mini-stat info">
                        <div class="mini-stat-label">Activos</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['activos'] ?? 0 }}</div>
                    </div>
                    <div class="mini-stat danger">
                        <div class="mini-stat-label">Alta prioridad</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['prioridad_alta'] ?? 0 }}</div>
                    </div>
                    <div class="mini-stat warning">
                        <div class="mini-stat-label">Prox. 7 dias</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['proximos_7_dias'] ?? 0 }}</div>
                    </div>
                    <div class="mini-stat success">
                        <div class="mini-stat-label">Cierre</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['avance'] ?? 0 }}%</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="planesCentralHidraulicaChart"></canvas>
                </div>
                <div class="worklist">
                    @forelse($planesCentralActivos->take(4) as $plan)
                        <div class="work-item">
                            <div class="work-item-top">
                                <div>
                                    <div class="work-title">{{ $plan['linea'] }} - {{ Str::limit($plan['actividad'] ?? 'Sin actividad', 62) }}</div>
                                    <div class="work-meta">Proxima fecha: {{ $plan['proxima_fecha_humana'] ?? 'Sin fecha' }}</div>
                                </div>
                                <span class="severity-pill {{ ($plan['prioridad'] ?? 'baja') === 'alta' ? 'critico' : (($plan['prioridad'] ?? 'baja') === 'media' ? 'severo' : 'estable') }}">
                                    {{ $plan['prioridad_label'] ?? 'Baja' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="work-item">
                            <div class="work-title">Sin planes activos</div>
                            <div class="work-meta">No hay actividades abiertas de plan de accion hidraulico.</div>
                        </div>
                    @endforelse
                </div>
                <div class="table-footer">
                    <i class="fas fa-info-circle"></i>
                    <span>Planes reales del modulo Pasteurizadora filtrados por Central Hidraulica.</span>
                    @if($puedeVerPlanesCentralHidraulica)
                        <a href="{{ route('plan-accion.index', [
                            'tipo' => \App\Models\User::MODULE_PASTEURIZADORA,
                            'area_pasteurizadora' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                        ]) }}">Abrir plan</a>
                    @endif
                </div>
            </div>

            <div class="chart-card avance-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>Avance de Revision Hidraulica</span>
                </h3>
                <div class="mini-stats-grid compact">
                    <div class="mini-stat info">
                        <div class="mini-stat-label">Promedio</div>
                        <div class="mini-stat-value">{{ $avanceCentralPromedio }}%</div>
                        <div class="mini-stat-meta">avance global</div>
                    </div>
                    <div class="mini-stat success">
                        <div class="mini-stat-label">Revisados</div>
                        <div class="mini-stat-value">{{ number_format($totalCentralRevisados) }}</div>
                        <div class="mini-stat-meta">posiciones revisadas</div>
                    </div>
                
                </div>
                <div class="chart-container">
                    <canvas id="avanceRevisionCentralHidraulicaChart"></canvas>
                </div>
                <div class="linea-breakdown">
                    @forelse($avanceCentralLineas->take(4) as $lineaAvance)
                        <div class="breakdown-item">
                            <div class="breakdown-item-top">
                                <div>
                                    <div class="breakdown-title">{{ $lineaAvance['linea'] }}</div>
                                    <div class="breakdown-meta">{{ number_format((int) ($lineaAvance['revisados'] ?? 0)) }} de {{ number_format((int) ($lineaAvance['total'] ?? 0)) }} posiciones revisadas</div>
                                </div>
                                <span class="severity-pill {{ (int) ($lineaAvance['porcentaje'] ?? 0) >= 90 ? 'estable' : ((int) ($lineaAvance['porcentaje'] ?? 0) >= 60 ? 'revision' : 'severo') }}">
                                    {{ (int) ($lineaAvance['porcentaje'] ?? 0) }}%
                                </span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar" style="width: {{ min(100, max(0, (int) ($lineaAvance['porcentaje'] ?? 0))) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="breakdown-item">
                            <div class="breakdown-title">Sin avance registrado</div>
                            <div class="breakdown-meta">Aun no hay revisiones hidraulicas para graficar.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="dashboard-panels-grid dashboard-history-trend-grid">
            <div class="chart-card historico-card historico-dashboard-card">
                <h3>
                    <i class="fas fa-history"></i>
                    <span>Historico de Revisiones</span>
                </h3>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-layer-group" style="color: #3b82f6;"></i> Piso / Componente</th>
                                <th><i class="fas fa-calendar-alt" style="color: #8b5cf6;"></i> Ultimo analisis</th>
                                <th class="text-right"><i class="fas fa-hashtag" style="color: #10b981;"></i> Analisis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historicoRevisionesCentralHidraulica as $item)
                                <tr>
                                    <td data-label="Piso / Componente"><i class="fas fa-oil-can mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                    <td data-label="Ultimo analisis">{{ $item['ultimo_analisis'] }}</td>
                                    <td data-label="Analisis">{{ $item['total_analisis'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">Sin analisis registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <i class="fas fa-info-circle"></i>
                    <span>Informacion conectada con el historial de Central Hidraulica</span>
                    @if($puedeVerCentralHidraulicaPasteurizadora)
                        <a href="{{ route('pasteurizadora.central-hidraulica.historico-revisados') }}">Abrir historico</a>
                    @endif
                </div>
            </div>

            <div class="chart-card trend-card trend-card-primary dashboard-panel dashboard-trend-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>Central Hidraulica 52-12-4 | Tendencia de Daños</span>
                </h3>
                <div class="dashboard-trend-main-header">
                    <div class="dashboard-trend-title-block">
                        <i class="fas fa-droplet"></i>
                        <div>
                            <div class="dashboard-trend-eyebrow">Analisis de tendencia Central Hidraulica</div>
                            <h3 class="dashboard-trend-heading">Analisis 52-12-4</h3>
                            <p class="dashboard-trend-subcopy">Lectura ejecutiva por central con ventanas de 52, 12 y 4 semanas.</p>
                        </div>
                    </div>
                </div>
                <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters" data-auto-filter-form>
                    <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA }}">
                    <select id="analisis52124CentralHidraulicaLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="central52124">
                        @forelse(($analisis52124CentralHidraulica['lineas'] ?? []) as $lineaTrend)
                            <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis52124CentralHidraulica, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                        @empty
                            <option value="">Sin centrales</option>
                        @endforelse
                    </select>
                    <input type="hidden" name="{{ data_get($trendFilters, 'tendencia30147.from_param', 'trend_30147_desde') }}" value="{{ data_get($trendFilters, 'tendencia30147.from_input', '') }}">
                    <input type="hidden" name="{{ data_get($trendFilters, 'tendencia30147.to_param', 'trend_30147_hasta') }}" value="{{ data_get($trendFilters, 'tendencia30147.to_input', '') }}">
                    <div class="trend-filter-field">
                        <label>Desde</label>
                        <input type="date" name="{{ data_get($trendFilters, 'tendencia.from_param', 'trend_52124_desde') }}" value="{{ data_get($trendFilters, 'tendencia.from_input', '') }}">
                    </div>
                    <div class="trend-filter-field">
                        <label>Hasta</label>
                        <input type="date" name="{{ data_get($trendFilters, 'tendencia.to_param', 'trend_52124_hasta') }}" value="{{ data_get($trendFilters, 'tendencia.to_input', '') }}">
                    </div>
                    <a href="{{ route('dashboard.global.pasteurizadoras', ['parte' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]) }}" class="trend-open-link">
                        <i class="fas fa-rotate-left"></i>
                        Limpiar
                    </a>
                    @if($puedeVerTendenciasPasteurizadora)
                        <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4', ['area' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]) }}" class="trend-open-link">
                            <i class="fas fa-up-right-from-square"></i>
                            Abrir
                        </a>
                    @endif
                </form>
                <div class="dashboard-trend-machine-strip">
                    <div class="dashboard-trend-machine-header">
                        <i class="fas fa-industry"></i>
                        <span>Centrales incluidas</span>
                    </div>
                    <div id="trendCentral52124MachineGrid" class="dashboard-trend-machine-grid"></div>
                </div>
                <div class="dashboard-trend-brief">
                    <div id="trendCentral52124StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                        <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                        <div id="trendCentral52124StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                        <p id="trendCentral52124StatusCopy" class="dashboard-trend-status-copy">
                            Se esta calculando el comportamiento reciente con informacion de Central Hidraulica.
                        </p>
                        <div id="trendCentral52124StatusNote" class="dashboard-trend-status-note">Ventanas: 52, 12 y 4 semanas.</div>
                    </div>
                    <div id="trendCentral52124WindowCards" class="dashboard-trend-window-grid"></div>
                </div>
                <div class="dashboard-trend-chart-shell">
                    <div class="dashboard-trend-chart-header">
                        <div class="dashboard-trend-chart-title">
                            <i class="fas fa-chart-column"></i>
                            <div>
                                <h4>Comparativo por central</h4>
                                <p>Ultimo corte disponible para cada ventana de analisis.</p>
                            </div>
                        </div>
                        <div class="dashboard-trend-view-selector">
                            <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="central52124" data-pasteur-trend-type="bar">Barras</button>
                            <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="central52124" data-pasteur-trend-type="line">Linea</button>
                        </div>
                        <div id="trendCentral52124Caption" class="dashboard-trend-caption">Corte actual de tendencia 52-12-4.</div>
                    </div>
                    <div class="chart-container dashboard-trend-chart-container">
                        <canvas id="analisis52124CentralHidraulicaChart"></canvas>
                    </div>
                </div>
                <div class="chart-description">
                    <i class="fas fa-info-circle"></i>
                    <span>Tendencia calculada solo con registros de Central Hidraulica</span>
                </div>
            </div>

            <div class="chart-card trend-card trend-card-side dashboard-panel dashboard-trend-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>Central Hidraulica 30-14-7 | Tendencia de Daños</span>
                </h3>
                <div class="dashboard-trend-main-header">
                    <div class="dashboard-trend-title-block">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <div class="dashboard-trend-eyebrow">Analisis de tendencia Central Hidraulica</div>
                            <h3 class="dashboard-trend-heading">Analisis 30-14-7</h3>
                            <p class="dashboard-trend-subcopy">Lectura ejecutiva por central con ventanas de 30, 14 y 7 dias.</p>
                        </div>
                    </div>
                </div>
                <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters" data-auto-filter-form>
                    <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA }}">
                    <select id="analisis30147CentralHidraulicaLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="central30147">
                        @forelse(($analisis30147CentralHidraulica['lineas'] ?? []) as $lineaTrend)
                            <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis30147CentralHidraulica, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                        @empty
                            <option value="">Sin centrales</option>
                        @endforelse
                    </select>
                    <input type="hidden" name="{{ data_get($trendFilters, 'tendencia.from_param', 'trend_52124_desde') }}" value="{{ data_get($trendFilters, 'tendencia.from_input', '') }}">
                    <input type="hidden" name="{{ data_get($trendFilters, 'tendencia.to_param', 'trend_52124_hasta') }}" value="{{ data_get($trendFilters, 'tendencia.to_input', '') }}">
                    <div class="trend-filter-field">
                        <label>Desde</label>
                        <input type="date" name="{{ data_get($trendFilters, 'tendencia30147.from_param', 'trend_30147_desde') }}" value="{{ data_get($trendFilters, 'tendencia30147.from_input', '') }}">
                    </div>
                    <div class="trend-filter-field">
                        <label>Hasta</label>
                        <input type="date" name="{{ data_get($trendFilters, 'tendencia30147.to_param', 'trend_30147_hasta') }}" value="{{ data_get($trendFilters, 'tendencia30147.to_input', '') }}">
                    </div>
                    <a href="{{ route('dashboard.global.pasteurizadoras', ['parte' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]) }}" class="trend-open-link">
                        <i class="fas fa-rotate-left"></i>
                        Limpiar
                    </a>
                    @if($puedeVerTendenciasPasteurizadora)
                        <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7', ['area' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]) }}" class="trend-open-link">
                            <i class="fas fa-up-right-from-square"></i>
                            Abrir
                        </a>
                    @endif
                </form>
                <div class="dashboard-trend-machine-strip">
                    <div class="dashboard-trend-machine-header">
                        <i class="fas fa-industry"></i>
                        <span>Centrales incluidas</span>
                    </div>
                    <div id="trendCentral30147MachineGrid" class="dashboard-trend-machine-grid"></div>
                </div>
                <div class="dashboard-trend-brief">
                    <div id="trendCentral30147StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                        <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                        <div id="trendCentral30147StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                        <p id="trendCentral30147StatusCopy" class="dashboard-trend-status-copy">
                            Se esta calculando el comportamiento reciente con informacion de Central Hidraulica.
                        </p>
                        <div id="trendCentral30147StatusNote" class="dashboard-trend-status-note">Ventanas: 30, 14 y 7 dias.</div>
                    </div>
                    <div id="trendCentral30147WindowCards" class="dashboard-trend-window-grid"></div>
                </div>
                <div class="dashboard-trend-chart-shell">
                    <div class="dashboard-trend-chart-header">
                        <div class="dashboard-trend-chart-title">
                            <i class="fas fa-chart-column"></i>
                            <div>
                                <h4>Comparativo por central</h4>
                                <p>Ultimo corte disponible para cada ventana de analisis.</p>
                            </div>
                        </div>
                        <div class="dashboard-trend-view-selector">
                            <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="central30147" data-pasteur-trend-type="bar">Barras</button>
                            <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="central30147" data-pasteur-trend-type="line">Linea</button>
                        </div>
                        <div id="trendCentral30147Caption" class="dashboard-trend-caption">Corte actual de tendencia 30-14-7.</div>
                    </div>
                    <div class="chart-container dashboard-trend-chart-container">
                        <canvas id="analisis30147CentralHidraulicaChart"></canvas>
                    </div>
                </div>
                <div class="chart-description">
                    <i class="fas fa-info-circle"></i>
                    <span>Seguimiento calculado solo con registros de Central Hidraulica</span>
                </div>
            </div>
        </div>
    </div>
    @endif

<div id="alertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Detalle de Alerta</h3>
            <button onclick="closeModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

@php
    $pasteurDashboardData = [
        'pasteurizadorasData' => $esDashboardMecanica ? $pasteurizadoras->values() : [],
        'fallasPorLineaPasteurizadora' => $esDashboardMecanica ? $fallasPorLineaPasteurizadora->values() : [],
        'componentesDanadosPasteurizadora' => $esDashboardMecanica ? $componentesDanadosPasteurizadora->values() : [],
        'planesAccionDashboardPasteurizadora' => $esDashboardMecanica ? $planesAccionDashboardPasteurizadora : ['por_linea' => []],
        'avanceRevisionPasteurizadora' => $esDashboardMecanica ? $avanceRevisionPasteurizadora : ['labels' => [], 'porcentajes' => []],
        'analisis52124Pasteurizadora' => $esDashboardMecanica ? $analisis52124Pasteurizadora : ['lineas' => []],
        'analisis30147Pasteurizadora' => $esDashboardMecanica ? $analisis30147Pasteurizadora : ['lineas' => []],
        'centralesHidraulicasData' => $esDashboardCentral ? $centralesHidraulicasPasteurizadora->values() : [],
        'fallasPorLineaCentralHidraulica' => $esDashboardCentral ? $fallasPorLineaCentralHidraulica->values() : [],
        'planesAccionDashboardCentralHidraulica' => $esDashboardCentral ? $planesAccionDashboardCentralHidraulica : ['por_linea' => []],
        'avanceRevisionCentralHidraulica' => $esDashboardCentral ? $avanceRevisionCentralHidraulica : ['labels' => [], 'porcentajes' => []],
        'analisis52124CentralHidraulica' => $esDashboardCentral ? $analisis52124CentralHidraulica : ['lineas' => []],
        'analisis30147CentralHidraulica' => $esDashboardCentral ? $analisis30147CentralHidraulica : ['lineas' => []],
        'fallasCanvasId' => $esDashboardMecanica ? 'fallasPasteurizadoraChart' : '',
        'urls' => [
            'mechanicalPlans' => $puedeVerPlanesPasteurizadora ? route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') : null,
            'mechanicalAnalyses' => $puedeVerMecanicaPasteurizadora ? route('pasteurizadora.analisis-pasteurizadora.index') : null,
            'centralPlans' => $esDashboardCentral && $puedeVerPlanesCentralHidraulica ? route('plan-accion.index', [
                'tipo' => \App\Models\User::MODULE_PASTEURIZADORA,
                'area_pasteurizadora' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            ]) : null,
            'centralAnalyses' => $esDashboardCentral && $puedeVerCentralHidraulicaPasteurizadora ? route('pasteurizadora.central-hidraulica.index') : null,
            'centralHistory' => $esDashboardCentral && $puedeVerCentralHidraulicaPasteurizadora ? route('pasteurizadora.central-hidraulica.historico-revisados') : null,
        ],
    ];
@endphp
<script type="application/json" id="pasteurDashboardData">@json($pasteurDashboardData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@vite('resources/js/dashboard-pasteurizadora.js')
@endsection
