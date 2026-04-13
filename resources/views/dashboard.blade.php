@extends('layouts.app')

@section('title', 'Dashboard Centralizado')

@section('content')
<style>
    /* Estilos generales */
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --light-gray: #f3f4f6;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
    }

    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    /* Animación de parpadeo para alertas críticas */
    @keyframes blink {
        0% { opacity: 1; background-color: #fee2e2; border-left-color: #ef4444; }
        50% { opacity: 0.7; background-color: #fff5f5; border-left-color: #fca5a5; }
        100% { opacity: 1; background-color: #fee2e2; border-left-color: #ef4444; }
    }

    .alert-critical {
        animation: blink 1s ease-in-out infinite;
    }

    /* Tarjetas de resumen */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .stat-card .stat-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--dark-gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-card .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
    }

    .stat-card .stat-icon {
        float: right;
        font-size: 28px;
        color: var(--dark-gray);
    }

    /* Grid de tarjetas de lavadoras */
    .lavadoras-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .lavadora-card {
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        background: white;
        border: 1px solid var(--medium-gray);
    }

    .lavadora-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* Estados de color para las tarjetas */
    .lavadora-card.buen-estado {
        background-color: #f0fdf4;
        border-left: 6px solid var(--success-green);
    }

    .lavadora-card.riesgo-estado {
        background-color: #fefce8;
        border-left: 6px solid var(--warning-yellow);
    }

    .lavadora-card.critico-estado {
        background-color: #fef2f2;
        border-left: 6px solid var(--danger-red);
    }

    .lavadora-card.critico-estado.alert-critical {
        animation: blink 1s ease-in-out infinite;
    }

    .lavadora-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .lavadora-nombre {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .status-icon {
        font-size: 20px;
    }

    .buen-estado .status-icon { color: var(--success-green); }
    .riesgo-estado .status-icon { color: var(--warning-yellow); }
    .critico-estado .status-icon { color: var(--danger-red); }

    .status-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }

    .status-tag.bueno { background: #d1fae5; color: #065f46; }
    .status-tag.riesgo { background: #fef3c7; color: #92400e; }
    .status-tag.critico { background: #fee2e2; color: #991b1b; }

    .lavadora-card-body {
        padding: 16px 20px;
    }

    .lavadora-mensaje {
        font-size: 14px;
        color: #475569;
        margin-bottom: 16px;
        line-height: 1.5;
    }

    .lavadora-metricas {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
        font-size: 13px;
        background: rgba(0,0,0,0.02);
        padding: 12px;
        border-radius: 12px;
    }

    .metric-item {
        text-align: center;
        flex: 1;
    }

    .metric-label {
        color: var(--dark-gray);
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .metric-value {
        font-weight: 700;
        font-size: 16px;
    }

    .lavadora-card-footer {
        padding: 12px 20px;
        background: rgba(0,0,0,0.02);
        border-top: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: flex-end;
    }

    /* Gráficas */
    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        margin-bottom: 24px;
    }

    .chart-card h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-container {
        height: 300px;
        position: relative;
    }

    /* Secciones */
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin: 24px 0 16px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        border-left: 4px solid var(--primary-blue);
        padding-left: 16px;
    }

    /* Modal para detalles de alerta */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 600px;
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }

    .modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid var(--medium-gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: 1px solid var(--medium-gray);
        color: var(--dark-gray);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background: var(--danger-red);
        color: white;
        border-color: var(--danger-red);
    }

    /* Ranking */
    .ranking-list {
        list-style: none;
        padding: 0;
    }

    .ranking-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        border-bottom: 1px solid var(--medium-gray);
    }

    .ranking-item:last-child {
        border-bottom: none;
    }

    .ranking-position {
        width: 40px;
        height: 40px;
        background: var(--light-gray);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--dark-gray);
    }

    .ranking-position.top-1 {
        background: #fef3c7;
        color: #f59e0b;
    }

    .ranking-position.top-2 {
        background: #e5e7eb;
        color: #6b7280;
    }

    .ranking-position.top-3 {
        background: #fef9c3;
        color: #eab308;
    }

    .ranking-info {
        flex: 1;
        margin-left: 12px;
    }

    .ranking-linea {
        font-weight: 600;
        color: #1e293b;
    }

    .ranking-puntaje {
        font-size: 14px;
        color: var(--dark-gray);
    }

    .ranking-badge {
        font-size: 12px;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 12px;
        background: var(--light-gray);
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .lavadoras-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    Dashboard Centralizado
                </h1>
                <p class="text-gray-500 mt-1">Monitoreo en tiempo real de lavadoras industriales</p>
            </div>
            <div class="flex gap-2">
                <button onclick="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>

    {{-- Tarjetas de Resumen --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-industry"></i></div>
            <div class="stat-label">Total Lavadoras</div>
            <div class="stat-value">{{ $resumenGeneral['total_lavadoras'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
            <div class="stat-label">Total Análisis</div>
            <div class="stat-value">{{ $resumenGeneral['total_analisis'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--danger-red);">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Críticas</div>
            <div class="stat-value" style="color: var(--danger-red);">{{ $resumenGeneral['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--warning-yellow);">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">En Riesgo</div>
            <div class="stat-value" style="color: var(--warning-yellow);">{{ $resumenGeneral['en_riesgo'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--success-green);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value" style="color: var(--success-green);">{{ $resumenGeneral['buen_estado'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-label">Pendientes Acción</div>
            <div class="stat-value">{{ $resumenGeneral['pendientes_accion'] }}</div>
        </div>
    </div>

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
                        <span class="status-tag {{ $estado['nivel'] === 'bueno' ? 'bueno' : ($estado['nivel'] === 'riesgo' ? 'riesgo' : 'critico') }}">
                            <i class="fas {{ $estado['nivel'] === 'bueno' ? 'fa-check-circle' : ($estado['nivel'] === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle') }}"></i>
                            {{ ucfirst($estado['nivel']) }}
                        </span>
                    </div>
                </div>
                <div class="lavadora-card-body">
                    <div class="lavadora-mensaje">
                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                        {{ $estado['mensaje'] }}
                    </div>
                    
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
                        @if(isset($estado['analisis_criticos']))
                        <div class="metric-item">
                            <div class="metric-label">Daños Críticos</div>
                            <div class="metric-value" style="color: var(--danger-red);">
                                {{ count($estado['analisis_criticos']) }}
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="lavadora-card-footer">
                    <button onclick="showAlertDetail({{ json_encode($lavadora) }})" 
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium shadow-sm">
                        <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Gráfica de Fallas por Línea --}}
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar text-red-500"></i> Fallas por Línea</h3>
            <div class="chart-container">
                <canvas id="fallasChart"></canvas>
            </div>
        </div>

        {{-- Gráfica de Componentes Más Dañados --}}
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie text-orange-500"></i> Componentes Más Dañados</h3>
            <div class="chart-container">
                <canvas id="componentesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Ranking de Lavadoras con Mayor Daño --}}
        <div class="chart-card">
            <h3><i class="fas fa-trophy text-yellow-500"></i> Ranking de Daño</h3>
            <ul class="ranking-list">
                @foreach($rankingDanos as $index => $item)
                    <li class="ranking-item">
                        <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="ranking-info">
                            <div class="ranking-linea">{{ $item['linea'] }}</div>
                            <div class="ranking-puntaje">Puntaje: {{ $item['puntaje'] }}</div>
                        </div>
                        <div class="ranking-badge">
                            {{ $item['analisis_criticos'] }} críticos
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Evolución de Elongaciones --}}
        <div class="chart-card">
            <h3><i class="fas fa-chart-line text-blue-500"></i> Evolución de Elongaciones</h3>
            <div class="chart-container">
                <canvas id="elongacionesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Histórico de Revisiones --}}
        <div class="chart-card">
            <h3><i class="fas fa-history text-purple-500"></i> Histórico de Revisiones</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2">Componente</th>
                            <th class="text-right py-2">Total Análisis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historicoRevisiones as $item)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">{{ $item['componente'] }}</td>
                                <td class="py-2 text-right font-semibold">{{ $item['total_analisis'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Análisis 52-12-4 (GRÁFICA) --}}
        <div class="chart-card">
            <h3><i class="fas fa-chart-line text-green-500"></i> Análisis 52-12-4 | Tendencia de Daños</h3>
            <div class="chart-container">
                <canvas id="analisis52124Chart"></canvas>
            </div>
            <div class="mt-3 text-xs text-center text-gray-500">
                Comparativa de daños en períodos: 52 semanas, 12 semanas y 4 semanas
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let fallasChart, componentesChart, elongacionesChart, analisis52124Chart;

    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        setAutoRefresh();
    });

    function initCharts() {
        // Gráfica de Fallas por Línea
        const fallasCtx = document.getElementById('fallasChart').getContext('2d');
        const fallasData = @json($fallasPorLinea);
        fallasChart = new Chart(fallasCtx, {
            type: 'bar',
            data: {
                labels: fallasData.map(item => item.linea),
                datasets: [{
                    label: 'Total de Fallas',
                    data: fallasData.map(item => item.total_fallas),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Número de Fallas'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Fallas: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });

        // Gráfica de Componentes Más Dañados
        const componentesCtx = document.getElementById('componentesChart').getContext('2d');
        const componentesData = @json($componentesDanados);
        componentesChart = new Chart(componentesCtx, {
            type: 'pie',
            data: {
                labels: componentesData.map(item => item.componente),
                datasets: [{
                    data: componentesData.map(item => item.total_danios),
                    backgroundColor: [
                        '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Gráfica de Evolución de Elongaciones
        const elongacionesCtx = document.getElementById('elongacionesChart').getContext('2d');
        const elongacionesData = @json($evolucionElongaciones);

        elongacionesChart = new Chart(elongacionesCtx, {
            type: 'line',
            data: {
                labels: elongacionesData.map(item => item.fecha),
                datasets: [
                    {
                        label: 'Bombas (%)',
                        data: elongacionesData.map(item => item.bombas),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointStyle: 'circle',
                        fill: true,
                        tension: 0.4,
                        segment: {
                            borderDash: ctx => [0, 0],
                        }
                    },
                    {
                        label: 'Vapor (%)',
                        data: elongacionesData.map(item => item.vapor),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointStyle: 'circle',
                        fill: true,
                        tension: 0.4
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
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#e5e5e5',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        padding: 12,
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
                            padding: 20,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        title: {
                            display: true,
                            text: 'Porcentaje de Elongación (%)',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            padding: { bottom: 10 }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // NUEVA GRÁFICA: Análisis 52-12-4
        const analisis52124Ctx = document.getElementById('analisis52124Chart').getContext('2d');
        const analisis52124Data = @json($analisis52124);
        
        // Agrupar datos por línea y período
        const lineasMap = new Map();
        analisis52124Data.forEach(item => {
            const lineaNombre = item.linea?.nombre ?? 'N/A';
            if (!lineasMap.has(lineaNombre)) {
                lineasMap.set(lineaNombre, {
                    '52_semanas': 0,
                    '12_semanas': 0,
                    '4_semanas': 0,
                    periodos: []
                });
            }
            const lineaData = lineasMap.get(lineaNombre);
            lineaData['52_semanas'] += parseFloat(item.total_danos_52_semanas) || 0;
            lineaData['12_semanas'] += parseFloat(item.total_danos_12_semanas) || 0;
            lineaData['4_semanas'] += parseFloat(item.total_danos_4_semanas) || 0;
        });

        const lineasNombres = Array.from(lineasMap.keys());
        const data52 = lineasNombres.map(linea => lineasMap.get(linea)['52_semanas']);
        const data12 = lineasNombres.map(linea => lineasMap.get(linea)['12_semanas']);
        const data4 = lineasNombres.map(linea => lineasMap.get(linea)['4_semanas']);

        analisis52124Chart = new Chart(analisis52124Ctx, {
            type: 'bar',
            data: {
                labels: lineasNombres,
                datasets: [
                    {
                        label: '52 Semanas',
                        data: data52,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: '12 Semanas',
                        data: data12,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: '#f59e0b',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: '4 Semanas',
                        data: data4,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total de Daños'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Línea de Lavadora'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw.toFixed(2)} daños`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                }
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

    function showAlertDetail(lavadora) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');

        modalTitle.innerHTML = `Detalle - ${lavadora.nombre}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${lavadora.estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (lavadora.estado.nivel === 'riesgo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500')}">
                <h4 class="font-bold text-lg mb-2">Estado: ${lavadora.estado.nivel.toUpperCase()}</h4>
                <p class="text-gray-700">${lavadora.estado.mensaje}</p>
            </div>
        `;

        if (lavadora.estado.analisis_criticos && lavadora.estado.analisis_criticos.length > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Componentes Dañados</h4>
                    <div class="space-y-3">
            `;
            lavadora.estado.analisis_criticos.forEach(analisis => {
                html += `
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>

                                    <div class="componente-header">
                                        <div class="componente-icono">
                                            <img 
    src="${analisis.componente?.icono || '/images/componentes-lavadora/default.png'}"
    class="w-8 h-8 object-contain"
>
                                        </div>
                                        <div class="flex-1">
                                            <div class="componente-nombre">${analisis.componente?.nombre || 'N/A'}</div>
                                            <div class="text-xs text-gray-500">${analisis.componente?.codigo || ''}</div>
                                        </div>
                                    </div>

                                    <p class="text-sm text-gray-600 mt-2">Reductor: ${analisis.reductor}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Fecha: ${new Date(analisis.fecha_analisis).toLocaleDateString()}
                                    </p>
                                </div>

                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                    Crítico
                                </span>
                            </div>

                            <p class="text-sm text-gray-700 mt-2">
                                ${analisis.actividad || 'Sin descripción'}
                            </p>
                        </div>
                    `;
            });
            html += `</div></div>`;
        }

        if (lavadora.estado.ultima_elongacion) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Última Medición de Elongación</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-sm text-gray-600">Bombas:</p>
                                <p class="font-semibold ${lavadora.estado.ultima_elongacion.bombas_porcentaje >= 1.8 ? 'text-red-600' : (lavadora.estado.ultima_elongacion.bombas_porcentaje >= 1.46 ? 'text-yellow-600' : 'text-green-600')}">
                                    ${lavadora.estado.ultima_elongacion.bombas_porcentaje}%
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Vapor:</p>
                                <p class="font-semibold ${lavadora.estado.ultima_elongacion.vapor_porcentaje >= 1.8 ? 'text-red-600' : (lavadora.estado.ultima_elongacion.vapor_porcentaje >= 1.46 ? 'text-yellow-600' : 'text-green-600')}">
                                    ${lavadora.estado.ultima_elongacion.vapor_porcentaje}%
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Fecha: ${new Date(lavadora.estado.ultima_elongacion.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
            `;
        }

        if (lavadora.estado.acciones_pendientes > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta lavadora tiene ${lavadora.estado.acciones_pendientes} acción(es) pendiente(s) en el plan de acción.</p>
                        <a href="{{ route('plan-accion.lavadora.index') }}?linea_id=${lavadora.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Acción
                        </a>
                    </div>
                </div>
            `;
        }

        html += `
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                <a href="{{ route('analisis-lavadora.index') }}?linea_id=${lavadora.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Análisis
                </a>
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `;

        modalBody.innerHTML = html;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function showImages(images) {
        if (!images || images.length === 0) return;
        
        let imagesHtml = '<div class="grid grid-cols-2 gap-2">';
        images.forEach(img => {
            imagesHtml += `
                <div class="relative">
                    <img src="{{ Storage::url('') }}${img}" class="w-full h-32 object-cover rounded cursor-pointer" onclick="window.open('{{ Storage::url('') }}${img}', '_blank')">
                </div>
            `;
        });
        imagesHtml += '</div>';
        
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        modalTitle.innerHTML = 'Evidencia Fotográfica';
        modalBody.innerHTML = `
            ${imagesHtml}
            <div class="flex justify-end mt-4">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `;
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
@endsection