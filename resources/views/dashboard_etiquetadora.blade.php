@extends('layouts.app')

@section('title', 'Etiquetadoras')

@section('content')
@include('etiquetadora.partials.styles')

@php
    $estadoEtiquetadoras = collect($estadoEtiquetadoras ?? []);
    $fallasPorLineaEtiquetadora = collect($fallasPorLineaEtiquetadora ?? []);
    $componentesDanadosEtiquetadora = collect($componentesDanadosEtiquetadora ?? []);
    $rankingEtiquetadoras = collect($rankingEtiquetadoras ?? []);
    $planesPendientesEtiquetadora = collect($planesPendientesEtiquetadora ?? []);
    $historicoRevisionesEtiquetadora = collect($historicoRevisionesEtiquetadora ?? []);
    $ultimosAnalisisEtiquetadora = collect($ultimosAnalisisEtiquetadora ?? []);
@endphp

<style>
    :root {
        --primary-blue: #3b82f6;
        --secondary-blue: #1e40af;
        --success-green: #10b981;
        --operational-orange: #f97316;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dashboard-container {
        width: 100%;
        max-width: 1680px;
        margin: 0 auto;
        padding: clamp(14px, 2vw, 20px);
        background: #f8fafc;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .dashboard-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .dashboard-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
        align-items: stretch;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 12px 14px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--medium-gray);
        transition: var(--transition);
        min-width: 0;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .stat-icon {
        float: right;
        font-size: 20px;
        color: var(--dark-gray);
    }

    .stat-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--dark-gray);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-primary);
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 24px 0 12px;
        color: #1f2937;
        font-size: 15px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .lavadoras-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 295px), 1fr));
        gap: 12px;
        margin-bottom: 12px;
        align-items: stretch;
    }

    .lavadora-card {
        border-radius: 12px;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        background: white;
        border: 1px solid var(--medium-gray);
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .lavadora-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-xl);
    }

    .lavadora-card.buen-estado {
        background-color: #f0fdf4;
        border-left: 6px solid var(--success-green);
    }

    .lavadora-card.riesgo-estado {
        background-color: #fff7ed;
        border-left: 6px solid var(--operational-orange);
    }

    .lavadora-card.operativo-estado {
        background-color: #fefce8;
        border-left: 6px solid var(--warning-yellow);
    }

    .lavadora-card.critico-estado {
        background-color: #fef2f2;
        border-left: 6px solid var(--danger-red);
    }

    .lavadora-card-header {
        padding: 10px 12px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }

    .lavadora-nombre {
        font-size: 13px;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1 1 180px;
    }

    .lavadora-card-body {
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1;
    }

    .lavadora-mensaje {
        color: var(--text-secondary);
        font-size: 13px;
        line-height: 1.45;
    }

    .lavadora-metricas {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .metric-item {
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 10px;
        padding: 10px;
    }

    .metric-label {
        color: var(--dark-gray);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .metric-value {
        margin-top: 2px;
        color: var(--text-primary);
        font-size: 17px;
        font-weight: 900;
    }

    .status-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
    }

    .status-tag.bueno { background: #d1fae5; color: #065f46; }
    .status-tag.operativo { background: #fef3c7; color: #92400e; }
    .status-tag.riesgo { background: #ffedd5; color: #9a3412; }
    .status-tag.critico { background: #fee2e2; color: #991b1b; }

    .lavadora-card-footer {
        padding: 10px 12px 12px;
    }

    .lavadora-card-action {
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 10px;
        background: #1f2937;
        color: white;
        padding: 9px 12px;
        font-size: 12px;
        font-weight: 800;
        transition: var(--transition);
    }

    .lavadora-card-action:hover {
        background: #111827;
        transform: translateY(-1px);
    }

    .dashboard-panels-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .chart-card {
        background: white;
        border: 1px solid var(--medium-gray);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        padding: 16px;
        min-width: 0;
    }

    .chart-card h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-primary);
        font-size: 16px;
        font-weight: 900;
        margin-bottom: 14px;
    }

    .chart-container {
        height: 280px;
        position: relative;
    }

    .chart-description,
    .table-footer,
    .ranking-footer {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        color: var(--text-secondary);
        font-size: 12px;
    }

    .ranking-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .ranking-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .ranking-position {
        display: flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #e2e8f0;
        color: #334155;
        font-weight: 900;
    }

    .ranking-position.top-1 { background: #fef3c7; color: #92400e; }
    .ranking-position.top-2 { background: #e0e7ff; color: #3730a3; }
    .ranking-position.top-3 { background: #dcfce7; color: #166534; }

    .ranking-linea {
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 900;
    }

    .ranking-puntaje,
    .ranking-meta {
        color: var(--text-secondary);
        font-size: 12px;
    }

    .ranking-badge,
    .severity-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        background: #e0f2fe;
        color: #075985;
        font-size: 11px;
        font-weight: 900;
        white-space: nowrap;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    th {
        color: #475569;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-align: left;
        text-transform: uppercase;
        border-bottom: 1px solid #e5e7eb;
        padding: 10px;
    }

    td {
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        padding: 10px;
        vertical-align: top;
    }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 80;
        background: rgba(15, 23, 42, 0.72);
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .modal.open {
        display: flex;
    }

    .modal-content {
        width: min(100%, 720px);
        max-height: 86vh;
        overflow: auto;
        border-radius: 18px;
        background: #fff;
        box-shadow: var(--shadow-xl);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #e5e7eb;
        padding: 16px 20px;
    }

    .modal-body {
        padding: 20px;
    }

    @media (max-width: 1024px) {
        .stats-grid,
        .dashboard-panels-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .stats-grid,
        .dashboard-panels-grid {
            grid-template-columns: 1fr;
        }

        .lavadora-metricas {
            grid-template-columns: 1fr;
        }

        .ranking-item {
            grid-template-columns: auto 1fr;
        }

        .ranking-badge {
            grid-column: 1 / -1;
            justify-content: center;
        }
    }
</style>

<div class="dashboard-container">
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
                    Dashboard Etiquetadoras
                </h1>
                @auth
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        Rol: {{ $userRoleLabel ?? auth()->user()->role_label }}
                    </p>
                @endauth
            </div>
            <div class="dashboard-actions">
                <a href="{{ route('etiquetadora.dashboard') }}" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-800 transition">
                    <i class="fas fa-layer-group mr-2"></i>Menu
                </a>
                <button onclick="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-industry"></i></div>
            <div class="stat-label">Total Etiquetadoras</div>
            <div class="stat-value">{{ $resumenEtiquetadora['total_etiquetadoras'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--danger-red);">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Criticas</div>
            <div class="stat-value" style="color: var(--danger-red);">{{ $resumenEtiquetadora['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--operational-orange);">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">Severo / Moderado</div>
            <div class="stat-value" style="color: var(--operational-orange);">{{ $resumenEtiquetadora['en_riesgo'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--warning-yellow);">
            <div class="stat-icon"><i class="fas fa-tools"></i></div>
            <div class="stat-label">Requiere Revision</div>
            <div class="stat-value" style="color: var(--warning-yellow);">{{ $resumenEtiquetadora['requiere_revision'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--success-green);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value" style="color: var(--success-green);">{{ $resumenEtiquetadora['buen_estado'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-label">Pendientes Accion</div>
            <div class="stat-value">{{ $resumenEtiquetadora['pendientes_accion'] }}</div>
        </div>
    </div>

    <div class="section-title">
        <i class="fas fa-tags"></i>
        ESTADO GENERAL DE ETIQUETADORAS
    </div>

    <div class="lavadoras-grid">
        @foreach($estadoEtiquetadoras as $etiquetadora)
            @php
                $estado = $etiquetadora['estado'];
                $nivel = $estado['nivel'] ?? 'bueno';
                $cardClass = $nivel === 'bueno'
                    ? 'buen-estado'
                    : ($nivel === 'operativo' ? 'operativo-estado' : ($nivel === 'riesgo' ? 'riesgo-estado' : 'critico-estado'));
                $progreso = $estado['progreso_revision'] ?? ['porcentaje' => 0, 'revisados' => 0, 'pendientes' => 0];
                $estadoLabel = $nivel === 'bueno'
                    ? 'Buen estado'
                    : ($nivel === 'operativo' ? 'Requiere revision' : ($nivel === 'riesgo' ? 'Severo / Moderado' : 'Critico'));
            @endphp
            <div class="lavadora-card {{ $cardClass }}">
                <div class="lavadora-card-header">
                    <div class="lavadora-nombre">
                        <i class="fas fa-tags status-icon"></i>
                        {{ $etiquetadora['nombre'] }}
                    </div>
                    <span class="status-tag {{ $nivel }}">
                        <i class="fas {{ $nivel === 'bueno' ? 'fa-check-circle' : ($nivel === 'operativo' ? 'fa-tools' : ($nivel === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle')) }}"></i>
                        {{ $estadoLabel }}
                    </span>
                </div>
                <div class="lavadora-card-body">
                    <div class="lavadora-mensaje">
                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                        {{ $estado['mensaje'] }}
                    </div>
                    <div class="lavadora-metricas">
                        <div class="metric-item">
                            <div class="metric-label">Avance</div>
                            <div class="metric-value" style="color: var(--primary-blue);">{{ $progreso['porcentaje'] ?? 0 }}%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Revisados</div>
                            <div class="metric-value" style="color: var(--success-green);">{{ $progreso['revisados'] ?? 0 }}</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Pendientes</div>
                            <div class="metric-value" style="color: {{ ($progreso['pendientes'] ?? 0) > 0 ? 'var(--warning-yellow)' : 'var(--success-green)' }};">{{ $progreso['pendientes'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="lavadora-card-footer">
                    <button onclick='showEtiquetadoraDetail(@json($etiquetadora))' class="lavadora-card-action">
                        <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-bar"></i>
                <span>Fallas por Linea</span>
            </h3>
            <div class="chart-container">
                <canvas id="fallasEtiquetadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Estados activos desde los ultimos analisis por componente.
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-pie"></i>
                <span>Componentes con Dano o Desgaste</span>
            </h3>
            <div class="chart-container">
                <canvas id="componentesEtiquetadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Distribucion real por componente revisado.
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Atencion</span>
            </h3>
            <ul class="ranking-list">
                @forelse($rankingEtiquetadoras as $index => $item)
                    <li class="ranking-item">
                        <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div>
                            <div class="ranking-linea flex items-center gap-2">
                                @include('etiquetadora.partials.presentation-icons', ['linea' => $item['nombre'], 'size' => 'xs'])
                                <span>{{ $item['nombre'] }}</span>
                            </div>
                            <div class="ranking-puntaje">
                                Criticas: {{ $item['criticos'] }} · Desgaste: {{ $item['desgaste'] }} · Revision: {{ $item['requiere_revision'] }}
                            </div>
                            <div class="ranking-meta">
                                Impacto {{ number_format((float) $item['porcentaje_impacto'], 1) }}% de {{ $item['total_componentes'] }} componentes.
                            </div>
                        </div>
                        <span class="ranking-badge">
                            <i class="fas fa-bolt"></i>
                            {{ $item['prioridad_label'] }}
                        </span>
                    </li>
                @empty
                    <li class="ranking-item">
                        <div class="ranking-position">0</div>
                        <div>
                            <div class="ranking-linea">Sin datos</div>
                            <div class="ranking-puntaje">No hay etiquetadoras para priorizar.</div>
                        </div>
                    </li>
                @endforelse
            </ul>
            <div class="ranking-footer">
                <i class="fas fa-info-circle"></i>
                Ordenado por criticidad, desgaste, revision y acciones pendientes.
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Avance por Linea</span>
            </h3>
            <div class="chart-container">
                <canvas id="avanceEtiquetadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Porcentaje de componentes con analisis vigente.
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card">
            <h3>
                <i class="fas fa-tasks"></i>
                <span>Plan de Accion Pendiente</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Linea</th>
                            <th>Actividad</th>
                            <th class="text-right">Proxima fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($planesPendientesEtiquetadora as $plan)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @include('etiquetadora.partials.presentation-icons', ['linea' => $plan->linea, 'size' => 'xs'])
                                        <span>{{ $plan->linea?->nombre ?? 'Sin linea' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ \Illuminate\Support\Str::limit($plan->actividad ?? 'Sin actividad', 56) }}</div>
                                    <div class="text-xs text-gray-500">{{ $plan->responsable?->name ?? 'Sin responsable' }}</div>
                                </td>
                                <td class="text-right">{{ optional($plan->proxima_fecha['fecha'] ?? null)->format('d/m/Y') ?? 'Sin fecha' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Sin pendientes activos</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                Conectado con la vista de plan de accion de Etiquetadora.
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-history"></i>
                <span>Historico de Revisiones</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Componente</th>
                            <th>Ultimo analisis</th>
                            <th class="text-right">Analisis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historicoRevisionesEtiquetadora as $item)
                            <tr>
                                <td>
                                    <div>{{ $item['componente'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $item['grupo'] ?? 'Sin grupo' }}</div>
                                </td>
                                <td>{{ $item['ultimo_analisis'] }}</td>
                                <td class="text-right">{{ $item['total_analisis'] }}</td>
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
                Componentes con mayor actividad historica.
            </div>
        </div>
    </div>

    <div class="chart-card">
        <h3>
            <i class="fas fa-clipboard-list"></i>
            <span>Ultimos Analisis Registrados</span>
        </h3>
        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Linea</th>
                        <th>Maquina</th>
                        <th>Componente</th>
                        <th>Estado</th>
                        <th>Orden</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ultimosAnalisisEtiquetadora as $registro)
                        <tr>
                            <td>{{ optional($registro->fecha_analisis)->format('d/m/Y') }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @include('etiquetadora.partials.presentation-icons', ['linea' => $registro->linea, 'size' => 'xs'])
                                    <span>{{ $registro->linea?->nombre ?? '-' }}</span>
                                </div>
                            </td>
                            <td>Maquina {{ $registro->maquina }}</td>
                            <td>{{ $registro->componente?->nombre ?? '-' }}</td>
                            <td>{{ $registro->estado }}</td>
                            <td>{{ $registro->numero_orden }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Sin analisis registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="alertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" class="text-lg font-black text-gray-900">Detalle de Etiquetadora</h3>
            <button onclick="closeModal()" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script>
    const fallasPorLineaEtiquetadora = @json($fallasPorLineaEtiquetadora->values());
    const componentesDanadosEtiquetadora = @json($componentesDanadosEtiquetadora->values());
    const historicoRevisionesEtiquetadora = @json($historicoRevisionesEtiquetadora->values());

    document.addEventListener('DOMContentLoaded', function() {
        initEtiquetadoraCharts();
    });

    function refreshData() {
        window.location.reload();
    }

    function initEtiquetadoraCharts() {
        const fallasCanvas = document.getElementById('fallasEtiquetadoraChart');
        if (fallasCanvas) {
            new Chart(fallasCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: fallasPorLineaEtiquetadora.map(item => item.linea),
                    datasets: [
                        {
                            label: 'Criticos',
                            data: fallasPorLineaEtiquetadora.map(item => item.criticos || 0),
                            backgroundColor: 'rgba(239, 68, 68, 0.9)',
                            borderColor: '#dc2626',
                            borderWidth: 2,
                            borderRadius: 10
                        },
                        {
                            label: 'Requiere revision',
                            data: fallasPorLineaEtiquetadora.map(item => item.requiere_revision || 0),
                            backgroundColor: 'rgba(245, 158, 11, 0.9)',
                            borderColor: '#d97706',
                            borderWidth: 2,
                            borderRadius: 10
                        },
                        {
                            label: 'Desgaste',
                            data: fallasPorLineaEtiquetadora.map(item => item.desgaste || 0),
                            backgroundColor: 'rgba(249, 115, 22, 0.85)',
                            borderColor: '#ea580c',
                            borderWidth: 2,
                            borderRadius: 10
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                    },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        const componentesCanvas = document.getElementById('componentesEtiquetadoraChart');
        if (componentesCanvas) {
            new Chart(componentesCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: componentesDanadosEtiquetadora.map(item => item.componente),
                    datasets: [{
                        data: componentesDanadosEtiquetadora.map(item => item.total),
                        backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#06b6d4', '#64748b'],
                        borderColor: '#fff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        const avanceCanvas = document.getElementById('avanceEtiquetadoraChart');
        if (avanceCanvas) {
            new Chart(avanceCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: fallasPorLineaEtiquetadora.map(item => item.linea),
                    datasets: [{
                        label: 'Avance %',
                        data: fallasPorLineaEtiquetadora.map(item => item.avance || 0),
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 2,
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100, ticks: { callback: value => `${value}%` } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    }

    function showEtiquetadoraDetail(item) {
        const modal = document.getElementById('alertModal');
        const body = document.getElementById('modalBody');
        const estado = item.estado || {};
        const progreso = estado.progreso_revision || {};
        const alertas = estado.alert_carousel || [];

        body.innerHTML = `
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500">Linea</div>
                    <div class="mt-1 text-lg font-black text-gray-900">${escapeHtml(item.linea || '-')}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500">Maquina</div>
                    <div class="mt-1 text-lg font-black text-gray-900">Maquina ${escapeHtml(item.maquina || '-')}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500">Avance</div>
                    <div class="mt-1 text-lg font-black text-gray-900">${Number(progreso.porcentaje || 0).toFixed(1)}%</div>
                </div>
            </div>
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700">
                ${escapeHtml(estado.mensaje || 'Sin detalle disponible.')}
            </div>
            <div class="mt-5">
                <h4 class="mb-3 text-sm font-black uppercase tracking-wide text-gray-600">Alertas activas</h4>
                ${
                    alertas.length
                        ? alertas.map(alerta => `
                            <a href="${alerta.url || '#'}" class="mb-3 block rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:bg-white hover:shadow-sm">
                                <div class="font-black text-gray-900">${escapeHtml(alerta.title || 'Componente')}</div>
                                <div class="text-sm font-semibold text-red-700">${escapeHtml(alerta.subtitle || '')}</div>
                                <div class="mt-1 text-xs text-gray-500">Grupo: ${escapeHtml(alerta.grupo || '-')} · Fecha: ${escapeHtml(alerta.fecha || '-')}</div>
                            </a>
                        `).join('')
                        : '<div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">Sin alertas activas.</div>'
                }
            </div>
        `;

        modal.classList.add('open');
    }

    function closeModal() {
        document.getElementById('alertModal').classList.remove('open');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.getElementById('alertModal')?.addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal();
        }
    });
</script>
@endsection
