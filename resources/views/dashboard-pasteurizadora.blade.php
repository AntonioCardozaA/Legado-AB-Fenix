@extends('layouts.app')

@section('title', 'Dashboard - Pasteurizadoras')

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

    .lavadora-carousel {
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 16px;
    }

    .lavadora-carousel-track {
        display: flex;
        width: 100%;
    }

    .carousel-slide {
        min-width: 100%;
        padding: 16px;
        box-sizing: border-box;
        display: none;
    }

    .carousel-slide.active {
        display: block;
    }

    .carousel-slide-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .carousel-slide-image,
    .carousel-slide-icon {
        width: 72px;
        height: 72px;
        border-radius: 16px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        flex-shrink: 0;
    }

    .carousel-slide-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 16px;
    }

    .carousel-slide-icon i {
        font-size: 26px;
        color: #3b82f6;
    }

    .carousel-slide-info {
        flex: 1;
    }

    .carousel-slide-title {
        font-weight: 700;
        color: #111827;
        margin-bottom: 4px;
    }

    .carousel-slide-subtitle {
        font-size: 13px;
        color: #475569;
        margin-bottom: 8px;
    }

    .carousel-slide-detail,
    .carousel-slide-meta {
        font-size: 12px;
        color: #6b7280;
    }

    .carousel-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px 14px;
        gap: 10px;
    }

    .carousel-button {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, 0.3);
        background: white;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .carousel-button:hover {
        background: #e2e8f0;
        transform: translateY(-1px);
    }

    .carousel-dots {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .carousel-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(100, 116, 139, 0.35);
        cursor: pointer;
    }

    .carousel-dot.active {
        background: #3b82f6;
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
    {{-- Botón de regreso a módulos --}}
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-orange-600 transition">
            <i class="fas fa-arrow-left"></i>
            <span>Volver</span>
        </a>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-temperature-high text-orange-600"></i>
                    Dashboard Pasteurizadoras
                </h1>
                <p class="text-gray-500 mt-1">Monitoreo de componentes y estado de pasteurizadoras</p>
            </div>
            <div class="flex gap-2">
                <button onclick="refreshData()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>

    {{-- Tarjetas de Resumen --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-industry"></i></div>
            <div class="stat-label">Total Pasteurizadoras</div>
            <div class="stat-value">{{ $resumenPasteurizadora['total_pasteurizadoras'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
            <div class="stat-label">Total Análisis</div>
            <div class="stat-value">{{ $resumenPasteurizadora['total_analisis'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--danger-red);">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Críticas</div>
            <div class="stat-value" style="color: var(--danger-red);">{{ $resumenPasteurizadora['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--warning-yellow);">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">En Riesgo</div>
            <div class="stat-value" style="color: var(--warning-yellow);">{{ $resumenPasteurizadora['en_riesgo'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--success-green);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value" style="color: var(--success-green);">{{ $resumenPasteurizadora['buen_estado'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-label">Pendientes Acción</div>
            <div class="stat-value">{{ $resumenPasteurizadora['pendientes_accion'] }}</div>
        </div>
    </div>

    {{-- Estado de Pasteurizadoras --}}
    <div class="section-title">
        <i class="fas fa-temperature-high"></i>
        ESTADO DE PASTEURIZADORAS
    </div>
    
    <div class="pasteurizadoras-grid">
        @foreach($estadoPasteurizadoras as $pasteurizadora)
            @php
                $estado = $pasteurizadora['estado'];
                $cardClass = '';
                if ($estado['nivel'] === 'bueno') {
                    $cardClass = 'buen-estado';
                } elseif ($estado['nivel'] === 'riesgo') {
                    $cardClass = 'riesgo-estado';
                } else {
                    $cardClass = 'critico-estado';
                }
            @endphp
            
            <div class="lavadora-card {{ $cardClass }}">
                <div class="lavadora-card-header">
                    <div class="lavadora-nombre">
                        <i class="fas fa-temperature-high status-icon"></i>
                        {{ $pasteurizadora['nombre'] }}
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
                    
                    @if($estado['progreso_revision']['porcentaje'] > 0)
                        <div class="mb-3">
                            <div class="flex justify-between text-xs mb-1">
                                <span>Progreso de revisión</span>
                                <span>{{ $estado['progreso_revision']['porcentaje'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $estado['progreso_revision']['porcentaje'] }}%"></div>
                            </div>
                        </div>
                    @endif
                    
                    @if($estado['ultimo_analisis'])
                        <div class="text-xs text-gray-500 mt-2">
                            <i class="far fa-calendar-alt mr-1"></i>
                            Último análisis: {{ $estado['ultimo_analisis']['fecha'] }}
                        </div>
                    @endif
                </div>
                <div class="lavadora-card-footer">
                    <button onclick="showPasteurizadoraDetail({{ json_encode($pasteurizadora) }})" 
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium shadow-sm">
                        <i class="fas fa-chart-simple mr-1"></i> Ver Detalle
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    function refreshData() {
        window.location.reload();
    }
    
    function showPasteurizadoraDetail(pasteurizadora) {
        // Implementar modal de detalle similar al de lavadora
        alert('Detalle de pasteurizadora: ' + pasteurizadora.nombre + '\nEstado: ' + pasteurizadora.estado.nivel);
        // Puedes expandir esto con un modal completo
    }
</script>
@endsection