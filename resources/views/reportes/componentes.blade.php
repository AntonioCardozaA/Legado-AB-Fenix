{{-- resources/views/reportes/componentes.blade.php --}}
@extends('layouts.app')

@section('title', 'Reporte de Componentes por Línea')

@section('content')
<style>
    /* VARIABLES CSS PARA CONSISTENCIA */
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --changed-blue: #3b82f6;
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
    }
    
    .sticky-top { position: sticky; top: 0; z-index: 30; }
    .sticky-left { position: sticky; left: 0; z-index: 20; }
    .sticky-top-left { position: sticky; top: 0; left: 0; z-index: 40; }
    
    /* ESTILOS DE FILTROS - ESTILO IMAGEN */
    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }
    
    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .lineas-title i {
        color: #3b82f6;
        font-size: 16px;
    }
    
    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .linea-item {
        display: inline-flex;
        align-items: center;
        padding: 8px 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }
    
    .linea-item i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }
    
    .linea-item:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }
    
    .linea-item.active {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }
    
    .linea-item.active i {
        color: white;
    }
    
    .filters-divider {
        margin: 24px 0 16px 0;
        border-top: 2px solid #f1f5f9;
    }
    
    .filters-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
    }
    
    .filter-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }
    
    .filter-link i {
        color: #64748b;
        font-size: 14px;
    }
    
    .filter-link:hover {
        background: #f8fafc;
        color: #2563eb;
    }
    
    .filter-link:hover i {
        color: #2563eb;
    }
    
    .filter-link.active {
        color: #2563eb;
        font-weight: 600;
    }
    
    .btn-apply {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 28px;
        background: #2563eb;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: auto;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }
    
    .btn-apply:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 6px 10px -1px rgba(37, 99, 235, 0.3);
    }
    
    .btn-clear {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: white;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-clear:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #475569;
    }
    
    .advanced-filters-panel {
        margin-top: 20px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    
    .advanced-filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-select, .filter-input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        color: #1e293b;
        background: white;
        transition: all 0.2s ease;
    }
    
    .filter-select:focus, .filter-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* CARDS DE ESTADÍSTICAS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        border: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.05);
        border-color: #e2e8f0;
    }
    
    .stat-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }
    
    /* TABLA DE COMPONENTES */
    .table-header-container {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
        border-radius: 12px 12px 0 0;
    }
    
    .componentes-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .componentes-table th {
        background-color: #f8fafc;
        color: #1e293b;
        font-weight: 600;
        font-size: 0.75rem;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .componentes-table td {
        padding: 12px 16px;
        font-size: 0.85rem;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    
    .componentes-table tbody tr:hover {
        background-color: #f8fafc;
    }
    
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge.bueno {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .badge.regular {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .badge.danado {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .badge.reemplazado {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background-color: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    
    .progress-bar-fill.green {
        background-color: #10b981;
    }
    
    .progress-bar-fill.yellow {
        background-color: #f59e0b;
    }
    
    .progress-bar-fill.red {
        background-color: #ef4444;
    }
    
    /* CARDS DE RESUMEN POR LÍNEA */
    .linea-card {
        background: white;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .linea-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.05);
        border-color: #3b82f6;
    }
    
    .linea-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .linea-icon {
        width: 40px;
        height: 40px;
        background: #eff6ff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3b82f6;
        font-size: 20px;
    }
    
    .linea-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 16px;
    }
    
    .linea-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .linea-stat {
        text-align: center;
        padding: 8px;
        background: #f8fafc;
        border-radius: 8px;
    }
    
    .linea-stat-value {
        font-weight: 700;
        font-size: 18px;
        color: #1e293b;
    }
    
    .linea-stat-label {
        font-size: 11px;
        color: #64748b;
    }
    
    /* BOTONES DE ACCIÓN */
    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .btn-excel {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #059669;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-excel:hover {
        background: #047857;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.2);
    }
    
    .btn-print {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-print:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }
    
    /* GRÁFICO */
    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    
    @media (max-width: 768px) {
        .lineas-grid {
            gap: 8px;
        }
        
        .linea-item {
            padding: 6px 16px;
            font-size: 13px;
        }
        
        .filters-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .btn-apply {
            margin-left: 0;
            justify-content: center;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="max-w-full mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Reporte de Componentes por Línea
            </h1>
            <p class="text-gray-600 text-sm mt-1">Estado y análisis de componentes por línea de lavadora</p>
        </div>

        <div class="action-buttons">
            <button onclick="exportarExcel()" class="btn-excel">
                <i class="fas fa-file-excel"></i>
                Exportar Excel
            </button>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i>
                Imprimir
            </button>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="filters-section">
        <div class="lineas-title">
            <i class="fas fa-filter"></i>
            FILTROS DE BÚSQUEDA:
        </div>
        
        <form method="GET" action="{{ route('reportes.componentes') }}" id="filterForm">
            <div class="advanced-filters-grid">
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt mr-1"></i> Periodo</label>
                    <select name="periodo" class="filter-select">
                        <option value="1mes" {{ request('periodo') == '1mes' ? 'selected' : '' }}>Último mes</option>
                        <option value="3meses" {{ request('periodo') == '3meses' ? 'selected' : '' }}>Últimos 3 meses</option>
                        <option value="6meses" {{ request('periodo') == '6meses' ? 'selected' : '' }}>Últimos 6 meses</option>
                        <option value="1anio" {{ request('periodo') == '1anio' ? 'selected' : '' }}>Último año</option>
                        <option value="todo" {{ request('periodo') == 'todo' ? 'selected' : '' }}>Todo el historial</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-washing-machine mr-1"></i> Línea</label>
                    <select name="linea" class="filter-select">
                        <option value="">Todas las líneas</option>
                        @foreach($lineas ?? [] as $linea)
                            <option value="{{ $linea->id }}" {{ request('linea') == $linea->id ? 'selected' : '' }}>
                                {{ $linea->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-cog mr-1"></i> Componente</label>
                    <select name="componente" class="filter-select">
                        <option value="">Todos los componentes</option>
                        @foreach($todosComponentes ?? [] as $id => $nombre)
                            <option value="{{ $id }}" {{ request('componente') == $id ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-clipboard-check mr-1"></i> Estado</label>
                    <select name="estado" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="Buen estado" {{ request('estado') == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                        <option value="Desgaste moderado" {{ request('estado') == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                        <option value="Desgaste severo" {{ request('estado') == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                        <option value="Dañado - Requiere cambio" {{ request('estado') == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                        <option value="Dañado - Cambiado" {{ request('estado') == 'Dañado - Cambiado' ? 'selected' : '' }}>🔄 Dañado - Cambiado</option>
                    </select>
                </div>
            </div>

            <div class="filters-row mt-4">
                <button type="submit" class="btn-apply">
                    <i class="fas fa-search"></i>
                    Aplicar filtros
                </button>
                
                <a href="{{ route('reportes.componentes') }}" class="btn-clear">
                    <i class="fas fa-times"></i>
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- ESTADÍSTICAS RÁPIDAS --}}
    @if(isset($estadisticas))
    <div class="stats-grid">
        <div class="stat-card border-t-4 border-blue-600">
            <div class="stat-label">Total Componentes</div>
            <div class="stat-value">{{ $estadisticas['total_componentes'] ?? 0 }}</div>
            <div class="stat-trend">En todas las líneas</div>
        </div>

        <div class="stat-card border-t-4 border-green-600">
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value text-green-600">{{ $estadisticas['buen_estado'] ?? 0 }}</div>
            <div class="stat-trend">{{ $estadisticas['porcentaje_bueno'] ?? 0 }}% del total</div>
        </div>

        <div class="stat-card border-t-4 border-yellow-500">
            <div class="stat-label">Desgaste</div>
            <div class="stat-value text-yellow-500">{{ $estadisticas['desgaste'] ?? 0 }}</div>
            <div class="stat-trend">{{ $estadisticas['porcentaje_desgaste'] ?? 0 }}% del total</div>
        </div>

        <div class="stat-card border-t-4 border-red-600">
            <div class="stat-label">Dañados</div>
            <div class="stat-value text-red-600">{{ $estadisticas['danados'] ?? 0 }}</div>
            <div class="stat-trend">{{ $estadisticas['porcentaje_danado'] ?? 0 }}% requieren atención</div>
        </div>

        <div class="stat-card border-t-4 border-blue-500">
            <div class="stat-label">Reemplazados</div>
            <div class="stat-value text-blue-600">{{ $estadisticas['reemplazados'] ?? 0 }}</div>
            <div class="stat-trend">Componentes cambiados</div>
        </div>
    </div>
    @endif

    {{-- GRÁFICO DE DISTRIBUCIÓN --}}
    @if(isset($estados) && !empty($estados))
    <div class="chart-container">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-blue-600"></i>
            Distribución de Estados por Componente
        </h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <canvas id="estadoComponentesChart" height="250"></canvas>
            </div>
            <div class="space-y-4">
                @foreach($estados as $estado => $data)
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">{{ $estado }}</span>
                        <span class="text-sm font-medium text-gray-700">
                            {{ $data['cantidad'] }} ({{ number_format($data['porcentaje'], 1) }}%)
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full {{ $data['color'] }}" style="width: {{ $data['porcentaje'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- RESUMEN POR LÍNEAS --}}
    @if(isset($lineas) && $lineas->count() > 0)
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-washing-machine text-blue-600"></i>
            Resumen por Línea
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($lineas as $linea)
                @php
                    $statsLinea = $estadisticasPorLinea[$linea->id] ?? [
                        'total' => 0,
                        'buen_estado' => 0,
                        'desgaste' => 0,
                        'danado' => 0,
                        'reemplazado' => 0
                    ];
                @endphp
                <div class="linea-card">
                    <div class="linea-card-header">
                        <div class="linea-icon">
                            <i class="fas fa-washing-machine"></i>
                        </div>
                        <div class="linea-name">{{ $linea->nombre }}</div>
                    </div>
                    <div class="linea-stats">
                        <div class="linea-stat">
                            <div class="linea-stat-value">{{ $statsLinea['total'] }}</div>
                            <div class="linea-stat-label">Total</div>
                        </div>
                        <div class="linea-stat">
                            <div class="linea-stat-value text-green-600">{{ $statsLinea['buen_estado'] }}</div>
                            <div class="linea-stat-label">Buenos</div>
                        </div>
                        <div class="linea-stat">
                            <div class="linea-stat-value text-yellow-600">{{ $statsLinea['desgaste'] }}</div>
                            <div class="linea-stat-label">Desgaste</div>
                        </div>
                        <div class="linea-stat">
                            <div class="linea-stat-value text-red-600">{{ $statsLinea['danado'] }}</div>
                            <div class="linea-stat-label">Dañados</div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="{{ route('analisis-lavadora.index', ['linea_id' => $linea->id]) }}" 
                           class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Ver detalles <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- TABLA DETALLADA DE COMPONENTES --}}
    @if(request('linea'))
        {{-- 🔵 OPCIÓN 1: UNA SOLA LÍNEA SELECCIONADA --}}
        @if(isset($reporte) && count($reporte) > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="table-header-container">
                <h3 class="font-bold text-white flex items-center gap-2">
                    <i class="fas fa-clipboard-list"></i>
                    Detalle de Componentes - 
                    {{ $lineas->firstWhere('id', request('linea'))?->nombre }}
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="componentes-table">
                    <thead>
                        <tr>
                            <th>Componente</th>
                            <th>Código</th>
                            <th>Total / Revisados</th>
                            <th>Buen Estado</th>
                            <th>Desgaste Moderado</th>
                            <th>Desgaste Severo</th>
                            <th>Dañado - Requiere</th>
                            <th>Dañado - Cambiado</th>
                            <th>% Revisión</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reporte as $item)
                            @include('reportes.partials.fila-componente', ['item' => $item])
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @else
        {{-- 🟢 OPCIÓN 2: TODAS LAS LÍNEAS (TABLA POR CADA UNA) --}}
        @if(isset($reportePorLinea) && count($reportePorLinea) > 0)
            @foreach($reportePorLinea as $nombreLinea => $componentes)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="table-header-container flex justify-between items-center">
                    <h3 class="font-bold text-white flex items-center gap-2">
                        <i class="fas fa-washing-machine"></i>
                        Línea {{ $nombreLinea }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="componentes-table">
                        <thead>
                            <tr>
                                <th>Componente</th>
                                <th>Código</th>
                                <th>Total / Revisados</th>
                                <th>Buen Estado</th>
                                <th>Desgaste Moderado</th>
                                <th>Desgaste Severo</th>
                                <th>Dañado - Requiere</th>
                                <th>Dañado - Cambiado</th>
                                <th>% Revisión</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($componentes as $item)
                                @include('reportes.partials.fila-componente', ['item' => $item])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <div class="text-blue-400 mb-4">
                    <i class="fas fa-clipboard-list text-5xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-700 mb-2">No hay datos disponibles</h3>
                <p class="text-gray-500">No se encontraron análisis de componentes para los filtros seleccionados.</p>
            </div>
        @endif
    @endif

    {{-- COMPONENTES CRÍTICOS --}}
    @if(isset($componentesCriticos) && count($componentesCriticos) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-red-500"></i>
                Componentes con Mayor Daño
            </h4>
            <div class="space-y-3">
                @foreach($componentesCriticos as $item)
                <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                    <div>
                        <span class="font-medium text-gray-800">{{ $item['componente'] ?? 'Sin nombre' }}</span>
                        <span class="text-xs text-gray-500 ml-2">{{ $item['codigo'] ?? $item['componente_id'] ?? '' }}</span>
                    </div>
                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-medium">
                        {{ $item['danados'] ?? 0 }} dañados
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-line text-yellow-500"></i>
                Componentes con Baja Revisión
            </h4>
            <div class="space-y-3">
                @foreach($componentesBajaRevision ?? [] as $item)
                <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                    <div>
                        <span class="font-medium text-gray-800">{{ $item['componente'] ?? 'Sin nombre' }}</span>
                        <span class="text-xs text-gray-500 ml-2">{{ $item['codigo'] ?? $item['componente_id'] ?? '' }}</span>
                    </div>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-medium">
                        {{ number_format($item['porcentaje_revisado'] ?? 0, 1) }}% revisado
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

{{-- MODAL DE CARGA --}}
<div id="loadingOverlay" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100]">
    <div class="bg-white rounded-lg p-8 shadow-2xl">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-700">Generando reporte...</p>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Mostrar/ocultar loading
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

// Exportar a Excel
function exportarExcel() {
    showLoading();
    
    // Construir URL con filtros actuales
    const params = new URLSearchParams(window.location.search);
   window.location.href = "{{ route('export.excel') }}?tipo=componentes&" + params.toString();
    
    // Ocultar loading después de un tiempo
    setTimeout(hideLoading, 3000);
}

// Gráfico de estado de componentes
@if(isset($estados) && !empty($estados))
const estadoCtx = document.getElementById('estadoComponentesChart').getContext('2d');
new Chart(estadoCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($estados)) !!},
        datasets: [{
            data: {!! json_encode(array_column($estados, 'cantidad')) !!},
            backgroundColor: [
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#3b82f6'
            ],
            borderWidth: 0,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});
@endif

// Manejar envío del formulario
document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    showLoading();
});

// Cerrar loading cuando la página termine de cargar
window.addEventListener('load', function() {
    hideLoading();
});

// Imprimir
window.onbeforeprint = function() {
    // Ocultar elementos no deseados en impresión
    document.querySelectorAll('.btn-excel, .btn-print, .filter-link, .btn-clear').forEach(el => {
        el.style.display = 'none';
    });
};

window.onafterprint = function() {
    // Restaurar elementos
    document.querySelectorAll('.btn-excel, .btn-print, .filter-link, .btn-clear').forEach(el => {
        el.style.display = '';
    });
};
</script>
@endpush