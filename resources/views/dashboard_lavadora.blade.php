@extends('layouts.app')

@section('title', 'Lavadoras ')

@section('content')
@php
    $usuarioActual = auth()->user();
    $puedeVerAnalisisLavadora = $usuarioActual?->canUseCustomPermission('ver analisis lavadora') ?? false;
    $puedeVerPlanesLavadora = $usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_LAVADORA) ?? false;
@endphp
@vite('resources/css/dashboard-lavadora.css')

<div class="dashboard-container">
    {{-- Header --}}
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 transition">
            <i class="fas fa-arrow-left"></i>
            <span>Volver</span>
        </a>
    </div>
    <div class="mb-6">
        <div class="dashboard-header">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    Dashboard Lavadoras
                </h1>
                @auth
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        Rol: {{ $userRoleLabel ?? auth()->user()->role_label }}
                    </p>
                @endauth
            </div>
            <div class="dashboard-actions">
                <button onclick="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>

    {{-- Tarjetas de Resumen --}}
    <div class="stats-grid">
        <div class="stat-card stat-card--primary">
            <div class="stat-icon"><i class="fas fa-industry"></i></div>
            <div class="stat-label">Total Lavadoras</div>
            <div class="stat-value">{{ $resumenGeneral['total_lavadoras'] }}</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Críticas</div>
            <div class="stat-value">{{ $resumenGeneral['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card stat-card--risk">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">Severo / Moderado</div>
            <div class="stat-value">{{ $resumenGeneral['en_riesgo'] }}</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-icon"><i class="fas fa-tools"></i></div>
            <div class="stat-label">Requiere Revisión</div>
            <div class="stat-value">{{ $resumenGeneral['requiere_revision'] }}</div>
        </div>
        <div class="stat-card stat-card--success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value">{{ $resumenGeneral['buen_estado'] }}</div>
        </div>
        <div class="stat-card stat-card--action">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-label">Pendientes Acción</div>
            <div class="stat-value">{{ $resumenGeneral['pendientes_accion'] }}</div>
        </div>
    </div>

    @if($canViewLavadoraCostsModule ?? false)
    <div class="dashboard-panels-grid">
        <div class="chart-card">
            <h3>
                <i class="fas fa-coins"></i>
                <span>Costos por Lavadora</span>
            </h3>
            <div class="chart-container">
                <canvas id="lavadoraCostSummaryChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Resumen anual de gastos calculados automáticamente.
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-wallet"></i>
                <span>Resumen de Costos</span>
            </h3>
            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">Gasto anual</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">${{ number_format((float) ($lavadoraCostSummary['summary']['year_total'] ?? 0), 2) }}</div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">Mayor costo</div>
                        <div class="mt-2 text-lg font-extrabold text-slate-900">{{ $lavadoraCostSummary['summary']['top_component']['label'] ?? 'Sin datos' }}</div>
                        <div class="mt-1 text-sm text-slate-500">
                            {{ isset($lavadoraCostSummary['summary']['top_component']['total']) ? '$' . number_format((float) $lavadoraCostSummary['summary']['top_component']['total'], 2) : 'Aún sin acumulado.' }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">Más reemplazado</div>
                        <div class="mt-2 text-lg font-extrabold text-slate-900">{{ $lavadoraCostSummary['summary']['top_replacement']['label'] ?? 'Sin datos' }}</div>
                        <div class="mt-1 text-sm text-slate-500">
                            {{ isset($lavadoraCostSummary['summary']['top_replacement']['total']) ? $lavadoraCostSummary['summary']['top_replacement']['total'] . ' eventos' : 'Sin reemplazos detectados.' }}
                        </div>
                    </div>
                </div>

                <a href="{{ route('lavadora.costos.index') }}" class="create-action">
                    <i class="fas fa-arrow-right"></i>
                    Abrir módulo completo de costos
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- ESTADO GENERAL DE LAVADORAS en Tarjetas --}}
    <div class="section-title">
        <i class="fas fa-washing-machine"></i>
        ESTADO GENERAL DE LAVADORAS
    </div>
    <div class="lavadoras-grid">
        @foreach($estadoLavadoras as $lavadora)
            @php
                $estado = $lavadora['estado'];
                $isCritical = $estado['nivel'] === 'critico';
                $cardClass = '';
                if ($estado['nivel'] === 'bueno') {
                    $cardClass = 'buen-estado';
                } elseif ($estado['nivel'] === 'operativo') {
                    $cardClass = 'operativo-estado';
                } elseif ($estado['nivel'] === 'riesgo') {
                    $cardClass = 'riesgo-estado';
                } else {
                    $cardClass = 'critico-estado';
                }
                if ($isCritical) {
                    $cardClass .= ' alert-critical';
                }
            @endphp
            <div class="lavadora-card {{ $cardClass }}">
                <div class="lavadora-card-header">
                    <div class="lavadora-nombre">
                        <i class="fas fa-microchip status-icon"></i>
                        {{ $lavadora['nombre'] }}
                    </div>
                    <div>
                        <span class="status-tag {{ $estado['nivel'] === 'bueno' ? 'bueno' : ($estado['nivel'] === 'operativo' ? 'operativo' : ($estado['nivel'] === 'riesgo' ? 'riesgo' : 'critico')) }}">
                            <i class="fas {{ $estado['nivel'] === 'bueno' ? 'fa-check-circle' : ($estado['nivel'] === 'operativo' ? 'fa-tools' : ($estado['nivel'] === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle')) }}"></i>
                            {{ $estado['nivel'] === 'bueno' ? 'Buen estado' : ($estado['nivel'] === 'operativo' ? 'Requiere revisión' : ($estado['nivel'] === 'riesgo' ? 'Severo / Moderado' : 'Crítico')) }}
                        </span>
                    </div>
                </div>
                <div class="lavadora-card-body">
                    <div class="lavadora-mensaje">
                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                        {{ $estado['mensaje'] }}
                    </div>

                    @if(isset($estado['alert_carousel']) && count($estado['alert_carousel']) > 0)
                        <div class="lavadora-carousel" id="lavadora-carousel-{{ $lavadora['id'] }}">
                            <div class="lavadora-carousel-track">
                                @foreach($estado['alert_carousel'] as $index => $item)
                                    <div class="carousel-slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}">
                                        <div class="carousel-slide-content">
                                            @if($item['type'] === 'componente')
                                                <div class="carousel-slide-image">
                                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" />
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
                                                @if(!empty($item['detail']) || !empty($item['description']))
                                                    <div class="carousel-slide-detail">{{ $item['detail'] ?? $item['description'] }}</div>
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
                                        @foreach($estado['alert_carousel'] as $index => $item)
                                            <span class="carousel-dot {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}"></span>
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
                                <span class="severity-pill revision">{{ $conteoAlertas['revision'] }} revisión</span>
                            @endif
                        </div>
                    @endif

                    @if(isset($estado['ultima_elongacion']))
                    <div class="lavadora-metricas">
                        <div class="metric-item">
                            <div class="metric-label">Elongación Bombas</div>
                            <div class="metric-value" style="color: {{ $estado['ultima_elongacion']['bombas_porcentaje'] >= 1.46 ? 'var(--danger-red)' : ($estado['ultima_elongacion']['bombas_porcentaje'] >= 1.3 ? 'var(--warning-yellow)' : 'var(--success-green)') }}">
                                {{ $estado['ultima_elongacion']['bombas_porcentaje'] }}%
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Elongación Vapor</div>
                            <div class="metric-value" style="color: {{ $estado['ultima_elongacion']['vapor_porcentaje'] >= 1.46 ? 'var(--danger-red)' : ($estado['ultima_elongacion']['vapor_porcentaje'] >= 1.3 ? 'var(--warning-yellow)' : 'var(--success-green)') }}">
                                {{ $estado['ultima_elongacion']['vapor_porcentaje'] }}%
                            </div>
                        </div>
                        @if($totalAlertas > 0)
                        <div class="metric-item">
                            <div class="metric-label">Alertas Activas</div>
                            <div class="metric-value" style="color: {{ $estado['nivel'] === 'critico' ? 'var(--danger-red)' : ($estado['nivel'] === 'riesgo' ? 'var(--operational-orange)' : 'var(--warning-yellow)') }};">
                                {{ $totalAlertas }}
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="lavadora-card-footer">
                    <button onclick='showAlertDetail(@json($lavadora))'
                            class="lavadora-card-action">
                        <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Gráficas Mejoradas --}}
    @php
        $lineaOptions = $lineasLavadora
            ->map(fn ($linea) => ['id' => $linea->id, 'nombre' => $linea->nombre])
            ->values();
    @endphp

    <div class="dashboard-panels-grid">
        {{-- Gráfica 1: Fallas por Línea --}}
        <div class="chart-card fallas-card">
            <h3>
                <i class="fas fa-chart-bar"></i>
                <span>Fallas por Línea</span>
            </h3>
            <div class="chart-container">
                <canvas id="fallasChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Fallas activas
            </div>
        </div>

        {{-- Gráfica 2: Componentes Más Dañados --}}
        <div class="chart-card planes-card">
            <h3>
                <i class="fas fa-chart-pie"></i>
                <span>Componentes Más Dañados</span>
            </h3>
            <div class="chart-container">
                <canvas id="componentesChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Planes activos
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        {{-- Ranking: Lavadoras con Mayor Daño --}}
        <div class="chart-card ranking-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Daño</span>
            </h3>
            <ul class="ranking-list" id="rankingList">
                @foreach($rankingDanos as $index => $item)
                    <li class="ranking-item">
                        <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="ranking-asset">
                            <div class="asset-media">
                                <i class="fas fa-industry" style="font-size: 18px; color: #2563eb;"></i>
                            </div>
                            <div class="ranking-info">
                                <div class="ranking-linea">{{ $item['linea'] }}</div>
                                <div class="ranking-puntaje">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    Criticas: {{ $item['criticas'] ?? 0 }} · Severo / Moderado: {{ ($item['severos'] ?? 0) + ($item['moderados'] ?? 0) }}
                                </div>
                                <div class="ranking-meta">
                                    Total con daño: {{ $item['total_danos'] ?? 0 }} de {{ $item['total_componentes'] ?? 0 }} componentes · Impacto {{ number_format((float) ($item['porcentaje_impacto'] ?? 0), 1) }}% · Revision: {{ $item['fecha_analisis_humana'] ?? 'Sin fecha' }}
                                </div>
                            </div>
                        </div>
                        <div class="ranking-status-stack">
                            <span class="severity-pill {{ $item['prioridad'] ?? 'estable' }}">{{ $item['prioridad_label'] ?? 'Estable' }}</span>
                            <div class="ranking-badge">
                                <i class="fas fa-bolt"></i>
                                {{ number_format((float) ($item['total_danos'] ?? 0), 0) }} daños
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="ranking-footer" id="rankingFooter">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Daños activos
                </div>
            </div>
        </div>

        {{-- Gráfica 3: Evolución de Elongaciones --}}
        <div class="chart-card elongaciones-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Evolución de Elongaciones</span>
            </h3>
            <div class="chart-container">
                <canvas id="elongacionesChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Lado Bombas vs Lado Vapor
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        {{-- Tabla: Histórico de Revisiones --}}
        <div class="chart-card historico-card">
            <h3>
                <i class="fas fa-history"></i>
                <span>Histórico de Revisiones</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <i class="fas fa-cube" style="color: #3b82f6;"></i> Componente
                            </th>
                            <th class="text-right">
                                <i class="fas fa-hashtag" style="color: #8b5cf6;"></i> Análisis
                            </th>
                        </tr>
                    </thead>
                    <tbody id="historicoTableBody">
                        @foreach([] as $item)
                            <tr>
                                <td data-label="Componente"><i class="fas fa-microchip mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                <td data-label="Análisis">{{ $item['total_analisis'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                Cantidad total de análisis por componente
            </div>
        </div>

    </div>

    <div class="dashboard-panels-full">
        <div class="chart-card trend-card trend-card-primary lef-dashboard-card">
            <div class="lef-dashboard-header">
                <div>
                    <h3><i class="fas fa-chart-line"></i> <span>52-12-4</span></h3>
                    <p class="lef-dashboard-period">Periodo de análisis: {{ $lefDashboard['period_label'] ?? 'Sin datos disponibles' }}</p>
                </div>
                <div class="lef-dashboard-filter">
                    <label for="lefDashboardLinea">Lavadora</label>
                    <select id="lefDashboardLinea" @disabled(empty($lefDashboard['lineas']))>
                        @foreach($lefDashboard['lineas'] as $linea)
                            <option value="{{ $linea['id'] }}">{{ $linea['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="lefDashboardLoading" class="lef-dashboard-message" role="status">Cargando análisis...</div>
            <div id="lefDashboardEmpty" class="lef-dashboard-message" role="status" hidden></div>
            <div id="lefDashboardCharts" hidden>
                <section class="lef-dashboard-section" aria-labelledby="lefWasherTitle">
                    <p id="lefWasherEmpty" class="lef-dashboard-message" hidden></p>
                    <div id="lefWasherChartWrap" class="lef-dashboard-chart"><canvas id="lefDashboardWasherChart" role="img" aria-label="Valores LEF de 52, 12 y 4 semanas de la lavadora seleccionada"></canvas></div>
                </section>
                <section class="lef-dashboard-section" aria-labelledby="lefParetoTitle">
                    <h4 id="lefParetoTitle">Pareto LEF - Máquinas</h4>
                    <p id="lefDashboardPosition" class="lef-dashboard-position" role="status" aria-live="polite"></p>
                    <p id="lefParetoEmpty" class="lef-dashboard-message" hidden></p>
                    <div id="lefParetoChartWrap" class="lef-dashboard-scroll">
                        <div id="lefDashboardParetoWidth" class="lef-dashboard-pareto"><canvas id="lefDashboardParetoChart" role="img" aria-label="Valores LEF por máquina, ordenados de mayor a menor según 52 semanas"></canvas></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

{{-- Modal para Detalle de Alerta --}}
<div id="alertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Detalle de Alerta</h3>
            <button onclick="closeModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    let fallasChart, componentesChart, elongacionesChart;

    document.addEventListener('DOMContentLoaded', function() {
        if (window.ChartDataLabels) {
            Chart.register(ChartDataLabels);
        }

        if (typeof window.dashboardLavadoraInitCharts === 'function') {
            window.dashboardLavadoraInitCharts();
        } else {
            initLegacyCharts();
        }
        initLavadoraCarousels();
        setAutoRefresh();
    });

    function initLegacyCharts() {
        // ─────────────────────────────────────────────────────────────────
        // 1️⃣ GRÁFICA: FALLAS POR LÍNEA
        // ─────────────────────────────────────────────────────────────────
        const fallasCtx = document.getElementById('fallasChart').getContext('2d');
        const fallasData = @json($fallasPorLinea);

        fallasChart = new Chart(fallasCtx, {
            type: 'bar',
            data: {
                labels: fallasData.map(item => item.linea),
                datasets: [{
                    label: 'Total de Fallas',
                    data: fallasData.map(item => item.total_fallas),
                    backgroundColor: fallasData.map((item, i) => {
                        const colors = [
                            'rgba(239, 68, 68, 0.9)',
                            'rgba(248, 113, 113, 0.85)',
                            'rgba(252, 165, 165, 0.8)',
                            'rgba(254, 202, 202, 0.75)',
                            'rgba(254, 226, 226, 0.7)'
                        ];
                        return colors[i] || colors[0];
                    }),
                    borderColor: fallasData.map((item, i) => {
                        const colors = ['#dc2626', '#f87171', '#fca5a5', '#fb7185', '#fecdd3'];
                        return colors[i] || colors[0];
                    }),
                    borderWidth: 2,
                    borderRadius: 12,
                    borderSkipped: false,
                    hoverBackgroundColor: 'rgba(239, 68, 68, 1)',
                    hoverBorderColor: '#991b1b',
                    hoverBorderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                            drawTicks: false
                        },
                        ticks: {
                            callback: function(value) {
                                return value;
                            },
                            font: {
                                size: 12,
                                weight: 600
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 13,
                                weight: 600
                            },
                            color: '#334155',
                            padding: 8
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        padding: 14,
                        titleFont: {
                            size: 13,
                            weight: 'bold',
                            family: "'Inter', sans-serif"
                        },
                        bodyFont: {
                            size: 12,
                            weight: 600
                        },
                        callbacks: {
                            label: function(context) {
                                return `Fallas: ${context.raw}`;
                            }
                        },
                        usePointStyle: true,
                        boxPadding: 12,
                        displayColors: true
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 2️⃣ GRÁFICA: COMPONENTES MÁS DAÑADOS (DOUGHNUT)
        // ─────────────────────────────────────────────────────────────────
        const componentesCtx = document.getElementById('componentesChart').getContext('2d');
        const componentesData = @json($componentesDanados);

        componentesChart = new Chart(componentesCtx, {
            type: 'doughnut',
            data: {
                labels: componentesData.map(item => item.componente),
                datasets: [{
                    data: componentesData.map(item => item.total_danios),
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.9)',
                        'rgba(245, 158, 11, 0.9)',
                        'rgba(16, 185, 129, 0.9)',
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(139, 92, 246, 0.9)',
                        'rgba(236, 72, 153, 0.9)'
                    ],
                    borderColor: [
                        '#dc2626',
                        '#d97706',
                        '#059669',
                        '#2563eb',
                        '#7c3aed',
                        '#db2777'
                    ],
                    borderWidth: 3,
                    borderRadius: 8,
                    hoverBorderWidth: 5,
                    hoverOffset: 12,
                    spacing: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12,
                                weight: 600,
                                family: "'Inter', sans-serif"
                            },
                            color: '#334155',
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: label,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                }));
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#f59e0b',
                        borderWidth: 2,
                        padding: 14,
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12,
                            weight: 600
                        },
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return [`Daños: ${value}`, `${percentage}%`];
                            }
                        }
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 3️⃣ GRÁFICA: EVOLUCIÓN DE ELONGACIONES (LÍNEA)
        // ─────────────────────────────────────────────────────────────────
        const elongacionesCtx = document.getElementById('elongacionesChart').getContext('2d');
        const elongacionesData = @json($evolucionElongaciones);

        elongacionesChart = new Chart(elongacionesCtx, {
            type: 'line',
            data: {
                labels: elongacionesData.map(item => item.fecha),
                datasets: [
                    {
                        label: 'Lado Bombas (%)',
                        data: elongacionesData.map(item => item.bombas),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 10,
                        pointStyle: 'circle',
                        fill: true,
                        tension: 0.4,
                        hoverBorderWidth: 4
                    },
                    {
                        label: 'Lado Vapor (%)',
                        data: elongacionesData.map(item => item.vapor),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 10,
                        pointStyle: 'circle',
                        fill: true,
                        tension: 0.4,
                        hoverBorderWidth: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        padding: 14,
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12,
                            weight: 600
                        },
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}%`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 24,
                            font: {
                                size: 13,
                                weight: 'bold'
                            },
                            color: '#334155',
                            generateLabels: function(chart) {
                                const datasets = chart.data.datasets;
                                return datasets.map((dataset, i) => ({
                                    text: dataset.label,
                                    fillStyle: dataset.borderColor,
                                    hidden: false,
                                    index: i,
                                    pointStyle: 'circle'
                                }));
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                            drawTicks: false
                        },
                        title: {
                            display: true,
                            text: 'Porcentaje (%)',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            padding: 10
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                size: 12,
                                weight: 600
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 12,
                                weight: 500
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 4️⃣ GRÁFICA: ANÁLISIS 52-12-4 (BARRAS AGRUPADAS)
        // ─────────────────────────────────────────────────────────────────
    }

    function initLavadoraCarousels() {
        document.querySelectorAll('.lavadora-carousel').forEach(carousel => {
            const slides = carousel.querySelectorAll('.carousel-slide');
            const prevButton = carousel.querySelector('.carousel-prev');
            const nextButton = carousel.querySelector('.carousel-next');
            const dots = carousel.querySelectorAll('.carousel-dot');
            let currentIndex = 0;

            function showSlide(index) {
                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('active', slideIndex === index);
                });
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('active', dotIndex === index);
                });
                currentIndex = index;
            }

            function goNext() {
                const nextIndex = (currentIndex + 1) % slides.length;
                showSlide(nextIndex);
            }

            function goPrev() {
                const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
                showSlide(prevIndex);
            }

            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    goNext();
                });
            }

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    goPrev();
                });
            }

            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    const index = parseInt(dot.dataset.index, 10);
                    if (!isNaN(index)) {
                        showSlide(index);
                    }
                });
            });

            if (slides.length > 1) {
                setInterval(goNext, 6000);
            }
        });
    }

    function refreshData() {
        window.location.reload();
    }

    function setAutoRefresh() {
        setInterval(() => {
            refreshData();
        }, 300000); // 5 minutos
    }

    function modalEscapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function(char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function formatModalDate(value) {
        if (!value) {
            return 'Sin fecha';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return modalEscapeHtml(value);
        }

        return date.toLocaleDateString('es-MX');
    }

    function renderLavadoraAnalisisSection(title, tone, analyses) {
        if (!Array.isArray(analyses) || analyses.length === 0) {
            return '';
        }

        const toneClasses = {
            critico: 'bg-red-100 text-red-700',
            severo: 'bg-orange-100 text-orange-700',
            moderado: 'bg-amber-100 text-amber-700',
            revision: 'bg-yellow-100 text-yellow-700'
        };

        const badgeClass = toneClasses[tone] || 'bg-slate-100 text-slate-700';

        const cards = analyses.map((analisis) => {
            const iconoUrl = analisis.componente?.icono || '/images/componentes-lavadora/default.png';
            const ubicacion = [analisis.reductor ? `Reductor: ${analisis.reductor}` : null, analisis.lado || null]
                .filter(Boolean)
                .join(' · ');

            return `
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="componente-header">
                                <div class="componente-icono">
                                    <img src="${iconoUrl}" class="w-8 h-8 object-contain" onerror="this.src='/images/componentes-lavadora/default.png'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="componente-nombre">${modalEscapeHtml(analisis.componente?.nombre || 'N/A')}</div>
                                </div>
                            </div>
                            ${ubicacion ? `<p class="text-sm text-gray-600 mt-2">${modalEscapeHtml(ubicacion)}</p>` : ''}
                            <p class="text-xs text-gray-500 mt-1">Fecha: ${modalEscapeHtml(analisis.fecha_formateada || formatModalDate(analisis.fecha_analisis))}</p>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold ${badgeClass}">
                            ${modalEscapeHtml(analisis.estado_label || title)}
                        </span>
                    </div>
                    <p class="text-sm text-gray-700 mt-2">
                        ${modalEscapeHtml(analisis.actividad || 'Sin descripción')}
                    </p>
                </div>
            `;
        }).join('');

        return `
            <div class="mb-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <h4 class="font-bold text-gray-800">${modalEscapeHtml(title)}</h4>
                    <span class="severity-pill ${tone}">${analyses.length}</span>
                </div>
                <div class="space-y-3">
                    ${cards}
                </div>
            </div>
        `;
    }

    function showAlertDetail(lavadora) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const estado = lavadora.estado || {};
        const analisisPorEstado = estado.analisis_por_estado || {};
        const secciones = [
            { key: 'critico', title: 'Requiere cambio', tone: 'critico' },
            { key: 'severo', title: 'Daño severo', tone: 'severo' },
            { key: 'moderado', title: 'Daño moderado', tone: 'moderado' },
            { key: 'revision', title: 'Requiere revisión', tone: 'revision' }
        ].filter((seccion) => Array.isArray(analisisPorEstado[seccion.key]) && analisisPorEstado[seccion.key].length > 0);
        const conteoAlertas = estado.conteo_alertas || {};
        const totalAlertas = Object.values(conteoAlertas).reduce((sum, value) => sum + Number(value || 0), 0);

        modalTitle.innerHTML = `Detalle - ${modalEscapeHtml(lavadora.nombre)}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (estado.nivel === 'riesgo' ? 'bg-orange-50 border-l-4 border-orange-500' : (estado.nivel === 'operativo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500'))}">
                <h4 class="font-bold text-lg mb-2">Estado: ${modalEscapeHtml((estado.nivel || 'bueno').toUpperCase())}</h4>
                <p class="text-gray-700">${modalEscapeHtml(estado.mensaje || 'Sin mensaje de estado')}</p>
            </div>
        `;

        if (totalAlertas > 0) {
            html += `
                <div class="mb-4 flex flex-wrap gap-2">
                    ${Number(conteoAlertas.critico || 0) > 0 ? `<span class="severity-pill critico">${Number(conteoAlertas.critico || 0)} requiere cambio</span>` : ''}
                    ${Number(conteoAlertas.severo || 0) > 0 ? `<span class="severity-pill severo">${Number(conteoAlertas.severo || 0)} severo</span>` : ''}
                    ${Number(conteoAlertas.moderado || 0) > 0 ? `<span class="severity-pill moderado">${Number(conteoAlertas.moderado || 0)} moderado</span>` : ''}
                    ${Number(conteoAlertas.revision || 0) > 0 ? `<span class="severity-pill revision">${Number(conteoAlertas.revision || 0)} revisión</span>` : ''}
                </div>
            `;

            secciones.forEach((seccion) => {
                html += renderLavadoraAnalisisSection(seccion.title, seccion.tone, analisisPorEstado[seccion.key] || []);
            });
        }

        if (estado.ultima_elongacion) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Última Medición de Elongación</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-sm text-gray-600">Lado Bombas:</p>
                                <p class="font-semibold ${estado.ultima_elongacion.bombas_porcentaje >= 1.8 ? 'text-red-600' : (estado.ultima_elongacion.bombas_porcentaje >= 1.46 ? 'text-yellow-600' : 'text-green-600')}">
                                    ${modalEscapeHtml(estado.ultima_elongacion.bombas_porcentaje)}%
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Lado Vapor:</p>
                                <p class="font-semibold ${estado.ultima_elongacion.vapor_porcentaje >= 1.8 ? 'text-red-600' : (estado.ultima_elongacion.vapor_porcentaje >= 1.46 ? 'text-yellow-600' : 'text-green-600')}">
                                    ${modalEscapeHtml(estado.ultima_elongacion.vapor_porcentaje)}%
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Fecha: ${formatModalDate(estado.ultima_elongacion.created_at)}</p>
                    </div>
                </div>
            `;
        }

        if (estado.acciones_pendientes > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta lavadora tiene ${modalEscapeHtml(estado.acciones_pendientes)} acción(es) pendiente(s) en el plan de acción.</p>
                        @if($puedeVerPlanesLavadora)
                        <a href="{{ route('plan-accion.lavadora.index') }}?linea_id=${lavadora.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Acción
                        </a>
                        @endif
                    </div>
                </div>
            `;
        }

        if (secciones.length === 0 && !estado.ultima_elongacion && !(estado.acciones_pendientes > 0)) {
            html += `
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm text-slate-600">
                    No hay componentes con alertas activas para esta lavadora.
                </div>
            `;
        }

        html += `
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                @if($puedeVerAnalisisLavadora)
                <a href="{{ route('analisis-lavadora.index') }}?linea_id=${lavadora.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Análisis
                </a>
                @endif
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `;

        modalBody.innerHTML = html;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('alertModal');
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('alertModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
</script>
@php
    $lavadoraPanelsData = [
        'dashboardUrl' => route('dashboard.global.lavadoras'),
        'lineas' => $lineaOptions,
        'fallas' => $fallasPorLinea,
        'planes' => $planesAccionDashboard,
        'ranking' => $rankingDanos,
        'elongaciones' => $evolucionElongaciones,
        'historico' => $historicoRevisiones,
        'planesUrl' => $puedeVerPlanesLavadora ? route('plan-accion.lavadora.index') : null,
    ];
@endphp
<script type="application/json" id="lavadoraPanelsData">@json($lavadoraPanelsData)</script>
@vite('resources/js/dashboard-lavadora-panels.js')
<script type="application/json" id="lefDashboardData">@json($lefDashboard)</script>
<script src="{{ asset('js/dashboard-lef-52124.js') }}"></script>
@if($canViewLavadoraCostsModule ?? false)
<script>
window.addEventListener('load', function () {
    const canvas = document.getElementById('lavadoraCostSummaryChart');
    const rows = @json($lavadoraCostSummary['by_lavadora'] ?? []);

    if (!canvas || !window.Chart) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length || !rows.some((row) => Number(row.total || 0) > 0)) {
        const container = canvas.parentElement;
        if (container) {
            container.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-slate-500">Sin costos acumulados todavía para el resumen global.</div>';
        }
        return;
    }

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: rows.map((row) => row.label),
            datasets: [{
                label: 'Costo acumulado',
                data: rows.map((row) => Number(row.total || 0)),
                backgroundColor: 'rgba(14, 165, 233, 0.85)',
                borderColor: '#0284c7',
                borderWidth: 2,
                borderRadius: 10,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => new Intl.NumberFormat('es-MX', {
                            style: 'currency',
                            currency: 'MXN',
                            minimumFractionDigits: 2,
                        }).format(Number(context.raw || 0)),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#475569' },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.16)' },
                    ticks: {
                        color: '#64748b',
                        callback: (value) => new Intl.NumberFormat('es-MX', {
                            style: 'currency',
                            currency: 'MXN',
                            notation: 'compact',
                            maximumFractionDigits: 1,
                        }).format(Number(value || 0)),
                    },
                },
            },
        },
    });
});
</script>
@endif
@endsection
