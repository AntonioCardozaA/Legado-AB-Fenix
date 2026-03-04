{{-- resources/views/analisis-tendencia-mensual-lavadora/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Análisis 52-12-4')

@section('content')
<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --primary-light: #3b82f6;
        --secondary: #64748b;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #0f172a;
        --dark-light: #1e293b;
        --dark-card: #334155;
        --border: #e2e8f0;
        --background: #f8fafc;
        --text-primary: #0f172a;
        --text-secondary: #475569;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: var(--background);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    /* Header Industrial */
    .industrial-header {
        background: linear-gradient(135deg, var(--dark) 0%, var(--dark-light) 100%);
        border-radius: 24px;
        padding: 32px;
        margin-bottom: 32px;
        box-shadow: 0 20px 25px -5px rgba(255, 255, 255, 0.2), 0 8px 10px -6px rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
    }    

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #94a3b8;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 20px;
        padding: 8px 16px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 40px;
        transition: all 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .back-link:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateX(-5px);
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .title-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
    }

    .title-content h1 {
        font-size: 32px;
        font-weight: 700;
        color: white;
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }

    .title-content p {
        color: #94a3b8;
        font-size: 15px;
    }

    .btn-industrial {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 14px 28px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 15px;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        text-decoration: none;
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
        letter-spacing: 0.3px;
    }

    .btn-industrial:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4);
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    }

    /* Filtros Industriales */
    .industrial-filters {
        background: white;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid var(--border);
    }

    .filters-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--border);
    }

    .filters-header i {
        font-size: 24px;
        color: var(--primary);
        background: rgba(37, 99, 235, 0.1);
        padding: 10px;
        border-radius: 12px;
    }

    .filters-header h2 {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .machine-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .machine-pill {
        padding: 12px 24px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid transparent;
    }

    .machine-pill-active {
        background: var(--dark);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        border-color: var(--primary);
    }

    .machine-pill-inactive {
        background: #f1f5f9;
        color: var(--text-secondary);
        border-color: #e2e8f0;
    }

    .machine-pill-inactive:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
        border-color: var(--primary);
    }

    /* Tabla Industrial */
    .industrial-table-container {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        margin-bottom: 32px;
        border: 1px solid var(--border);
    }

    .industrial-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .industrial-table th {
        background: var(--dark);
        color: white;
        padding: 18px 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 12px;
        border-right: 1px solid var(--dark-card);
        white-space: nowrap;
        position: relative;
    }

    .industrial-table th:last-child {
        border-right: none;
    }

    .industrial-table th.group-header {
        background: var(--dark-light);
        font-size: 13px;
        padding: 12px;
    }

    .industrial-table td {
        padding: 18px 12px;
        border: 1px solid var(--border);
        vertical-align: middle;
    }

    .industrial-table tbody tr {
        transition: all 0.2s;
    }

    .industrial-table tbody tr:hover {
        background: #f8fafc;
        transform: scale(1.01);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 10;
    }

    .period-cell {
        background: #f8fafc;
        font-weight: 700;
        position: relative;
    }

    .period-main {
        font-size: 15px;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .period-sub {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .current-badge {
        background: var(--primary);
        color: white;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 8px;
        display: inline-block;
    }

    .value-industrial {
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
    }

    .comparison-industrial {
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-weight: 600;
    }

    .trend-industrial {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 12px;
        min-width: 90px;
        justify-content: center;
    }

    .trend-up-industrial {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .trend-down-industrial {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .trend-stable-industrial {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    /* Gráfica Industrial - Versión Barras */
    .industrial-chart {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        border: 1px solid var(--border);
        margin-top: 32px;
    }

    .chart-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--border);
    }

    .chart-header i {
        font-size: 24px;
        color: var(--primary);
        background: rgba(37, 99, 235, 0.1);
        padding: 10px;
        border-radius: 12px;
    }

    .chart-header h3 {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .chart-container {
        height: 450px;
        position: relative;
    }

    /* Selector de vista para la gráfica */
    .chart-view-selector {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }

    .view-btn {
        padding: 8px 16px;
        border-radius: 40px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border);
        background: white;
        color: var(--text-secondary);
    }

    .view-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .view-btn:hover:not(.active) {
        background: #f1f5f9;
        border-color: var(--primary);
    }

    /* Empty State Industrial */
    .industrial-empty {
        background: white;
        border-radius: 20px;
        padding: 60px 40px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border);
    }

    .empty-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        border-radius: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 48px;
        color: var(--secondary);
        border: 4px solid white;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .industrial-empty h3 {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .industrial-empty p {
        color: var(--text-secondary);
        margin-bottom: 24px;
        font-size: 16px;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, transparent, rgba(37, 99, 235, 0.05));
        border-radius: 50%;
    }

    .stat-label {
        font-size: 14px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-primary);
        font-family: 'JetBrains Mono', monospace;
    }

    .stat-trend {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 8px;
        font-size: 13px;
        font-weight: 600;
    }

    /* Tooltip personalizado */
    .custom-tooltip {
        background: var(--dark);
        color: white;
        padding: 12px 16px;
        border-radius: 12px;
        font-family: 'JetBrains Mono', monospace;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        border: 1px solid var(--primary);
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Header Industrial --}}
    <div class="industrial-header">
        <a href="{{ route('lavadora.dashboard') }}" class="back-link">
            <i class="fas fa-arrow-left"></i>
            <span>VOLVER AL DASHBOARD</span>
        </a>
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="header-title">
                <div class="title-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="title-content">
                    <h1>ANÁLISIS DE TENDENCIAS 52-12-4</h1>
                    <p>Monitoreo industrial de daños en lavadoras • Comparativa mensual</p>
                </div>
            </div>
            
            <a href="{{ route('analisis-tendencia-mensual-lavadora.create', $lineaSeleccionada ? ['linea_id' => $lineaSeleccionada] : []) }}" 
               class="btn-industrial">
                <i class="fas fa-plus-circle"></i>
                <span>NUEVO ANÁLISIS</span>
            </a>
        </div>
    </div>

    {{-- Filtros Industriales --}}
    <div class="industrial-filters">
        <div class="filters-header">
            <i class="fas fa-washing-machine"></i>
            <h2>SELECCIONAR LÍNEA DE PRODUCCIÓN</h2>
        </div>
        
        <div class="machine-grid">
            @foreach($lineas as $linea)
                <a href="{{ route('analisis-tendencia-mensual-lavadora.index', ['linea_id' => $linea->id]) }}" 
                   class="machine-pill {{ $lineaSeleccionada == $linea->id ? 'machine-pill-active' : 'machine-pill-inactive' }}">
                    <i class="fas fa-washing-machine"></i>
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Tabla de Tendencias --}}
    @if($lineaSeleccionada)
        @if($analisis->isNotEmpty())
            <div class="industrial-table-container">
                <table class="industrial-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="border-r border-gray-600">PERÍODO</th>
                            <th colspan="3" class="group-header border-r border-gray-600">52 SEMANAS</th>
                            <th colspan="3" class="group-header border-r border-gray-600">12 SEMANAS</th>
                            <th colspan="3" class="group-header">4 SEMANAS</th>
                        </tr>
                        <tr>
                            <th class="bg-gray-700">TOTAL</th>
                            <th class="bg-gray-700">VS MES ANT</th>
                            <th class="bg-gray-700">TENDENCIA</th>
                            <th class="bg-gray-700">TOTAL</th>
                            <th class="bg-gray-700">VS MES ANT</th>
                            <th class="bg-gray-700">TENDENCIA</th>
                            <th class="bg-gray-700">TOTAL</th>
                            <th class="bg-gray-700">VS MES ANT</th>
                            <th class="bg-gray-700">TENDENCIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $analisisOrdenados = $analisis->sortByDesc(function($item) {
                                return $item->anio . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT);
                            });
                        @endphp
                        
                        @foreach($analisisOrdenados as $index => $item)
                            @php
                                $variacion52 = $item->variacion_52_semanas;
                                $variacion12 = $item->variacion_12_semanas;
                                $variacion4 = $item->variacion_4_semanas;
                            @endphp
                            <tr>
                                {{-- Período --}}
                                <td class="period-cell">
                                    <div class="period-main">
                                        {{ $item->mesNombre }} {{ $item->anio }}
                                        @if($loop->first)
                                            <span class="current-badge">ACTUAL</span>
                                        @endif
                                    </div>
                                    <div class="period-sub">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        Semana {{ $item->semana ?? '—' }}
                                    </div>
                                </td>

                                {{-- 52 Semanas --}}
                                <td class="value-industrial">{{ number_format($item->total_danos_52_semanas, 2) }}</td>
                                <td class="comparison-industrial">
                                    @if($variacion52)
                                        <span style="color: {{ $variacion52['diferencia'] > 0 ? 'var(--danger)' : ($variacion52['diferencia'] < 0 ? 'var(--success)' : 'var(--warning)') }}">
                                            {{ $variacion52['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion52['diferencia'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variacion52)
                                        @php
                                            $clase = $variacion52['tendencia'] == 'up' ? 'trend-up-industrial' : ($variacion52['tendencia'] == 'down' ? 'trend-down-industrial' : 'trend-stable-industrial');
                                            $icono = $variacion52['tendencia'] == 'up' ? 'fa-arrow-up' : ($variacion52['tendencia'] == 'down' ? 'fa-arrow-down' : 'fa-minus');
                                        @endphp
                                        <span class="trend-industrial {{ $clase }}">
                                            <i class="fas {{ $icono }}"></i>
                                            {{ $variacion52['porcentaje'] > 0 ? '+' : '' }}{{ number_format($variacion52['porcentaje'], 2) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- 12 Semanas --}}
                                <td class="value-industrial">{{ number_format($item->total_danos_12_semanas, 2) }}</td>
                                <td class="comparison-industrial">
                                    @if($variacion12)
                                        <span style="color: {{ $variacion12['diferencia'] > 0 ? 'var(--danger)' : ($variacion12['diferencia'] < 0 ? 'var(--success)' : 'var(--warning)') }}">
                                            {{ $variacion12['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion12['diferencia'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variacion12)
                                        @php
                                            $clase = $variacion12['tendencia'] == 'up' ? 'trend-up-industrial' : ($variacion12['tendencia'] == 'down' ? 'trend-down-industrial' : 'trend-stable-industrial');
                                            $icono = $variacion12['tendencia'] == 'up' ? 'fa-arrow-up' : ($variacion12['tendencia'] == 'down' ? 'fa-arrow-down' : 'fa-minus');
                                        @endphp
                                        <span class="trend-industrial {{ $clase }}">
                                            <i class="fas {{ $icono }}"></i>
                                            {{ $variacion12['porcentaje'] > 0 ? '+' : '' }}{{ number_format($variacion12['porcentaje'], 2) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- 4 Semanas --}}
                                <td class="value-industrial">{{ number_format($item->total_danos_4_semanas, 2) }}</td>
                                <td class="comparison-industrial">
                                    @if($variacion4)
                                        <span style="color: {{ $variacion4['diferencia'] > 0 ? 'var(--danger)' : ($variacion4['diferencia'] < 0 ? 'var(--success)' : 'var(--warning)') }}">
                                            {{ $variacion4['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion4['diferencia'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variacion4)
                                        @php
                                            $clase = $variacion4['tendencia'] == 'up' ? 'trend-up-industrial' : ($variacion4['tendencia'] == 'down' ? 'trend-down-industrial' : 'trend-stable-industrial');
                                            $icono = $variacion4['tendencia'] == 'up' ? 'fa-arrow-up' : ($variacion4['tendencia'] == 'down' ? 'fa-arrow-down' : 'fa-minus');
                                        @endphp
                                        <span class="trend-industrial {{ $clase }}">
                                            <i class="fas {{ $icono }}"></i>
                                            {{ $variacion4['porcentaje'] > 0 ? '+' : '' }}{{ number_format($variacion4['porcentaje'], 2) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Gráfica Industrial de Barras --}}
            <div class="industrial-chart">
                <div class="chart-header">
                    <i class="fas fa-chart-bar"></i>
                    <h3>EVOLUCIÓN DE DAÑOS - {{ $lineas->find($lineaSeleccionada)?->nombre }}</h3>
                    <div class="chart-view-selector">
                        <button class="view-btn active" onclick="changeChartType('bar')">Barras</button>
                        <button class="view-btn" onclick="changeChartType('line')">Línea</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

        @else
            {{-- Empty State Industrial --}}
            <div class="industrial-empty">
                <div class="empty-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>SIN DATOS DISPONIBLES</h3>
                <p>No se encontraron análisis para {{ $lineas->find($lineaSeleccionada)?->nombre }}</p>
                <a href="{{ route('analisis-tendencia-mensual-lavadora.create', ['linea_id' => $lineaSeleccionada]) }}" 
                   class="btn-industrial">
                    <i class="fas fa-plus-circle"></i>
                    INICIAR PRIMER ANÁLISIS
                </a>
            </div>
        @endif
    @else
        {{-- Selección requerida --}}
        <div class="industrial-empty">
            <div class="empty-icon">
                <i class="fas fa-hand-pointer"></i>
            </div>
            <h3>SELECCIONE UNA LAVADORA</h3>
            <p>Elija una línea del panel superior para visualizar los análisis</p>
        </div>
    @endif
</div>

@if($lineaSeleccionada && $analisis->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart;

document.addEventListener('DOMContentLoaded', function() {
    const analisisData = @json($analisis);
    
    const datosOrdenados = [...analisisData].sort((a, b) => {
        if (a.anio !== b.anio) return a.anio - b.anio;
        return a.mes - b.mes;
    });
    
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const labels = datosOrdenados.map(item => meses[item.mes - 1] + ' ' + item.anio);
    
    const data52 = datosOrdenados.map(item => parseFloat(item.total_danos_52_semanas) || 0);
    const data12 = datosOrdenados.map(item => parseFloat(item.total_danos_12_semanas) || 0);
    const data4 = datosOrdenados.map(item => parseFloat(item.total_danos_4_semanas) || 0);
    
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    // Función para crear la gráfica
    function createChart(type = 'bar') {
        if (chart) {
            chart.destroy();
        }
        
        chart = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '52 Semanas',
                        data: data52,
                        backgroundColor: 'rgba(14, 180, 66, 0.8)',
                        borderColor: '#8b5cf6',
                        borderWidth: type === 'bar' ? 0 : 3,
                        borderRadius: type === 'bar' ? 8 : 0,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        pointBackgroundColor: '#8b5cf6',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: type === 'bar' ? 0 : 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: type === 'line' ? true : false
                    },
                    {
                        label: '12 Semanas',
                        data: data12,
                        backgroundColor: 'rgba(224, 9, 9, 0.8)',
                        borderColor: '#f97316',
                        borderWidth: type === 'bar' ? 0 : 3,
                        borderRadius: type === 'bar' ? 8 : 0,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        pointBackgroundColor: '#f97316',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: type === 'bar' ? 0 : 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: type === 'line' ? true : false
                    },
                    {
                        label: '4 Semanas',
                        data: data4,
                        backgroundColor: 'rgba(222, 96, 12, 0.8)',
                        borderColor: '#10b981',
                        borderWidth: type === 'bar' ? 0 : 3,
                        borderRadius: type === 'bar' ? 8 : 0,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: type === 'bar' ? 0 : 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: type === 'line' ? true : false
                    }
                ]
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
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12,
                                weight: '500',
                                family: 'Inter'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#f8fafc',
                        bodyColor: '#f1f5f9',
                        bodyFont: {
                            size: 13,
                            family: 'JetBrains Mono'
                        },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('es-MX', { 
                                        minimumFractionDigits: 2, 
                                        maximumFractionDigits: 2 
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: type === 'bar' ? {
                        display: true,
                        color: 'white',
                        font: {
                            weight: 'bold',
                            size: 11,
                            family: 'JetBrains Mono'
                        },
                        formatter: (value) => {
                            return value.toFixed(1);
                        },
                        anchor: 'end',
                        align: 'top',
                        offset: 4
                    } : {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2);
                            },
                            font: {
                                size: 11,
                                family: 'JetBrains Mono'
                            }
                        },
                        title: {
                            display: true,
                            text: 'TOTAL DE DAÑOS',
                            font: {
                                size: 11,
                                weight: '600',
                                family: 'Inter'
                            },
                            color: '#64748b'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 11,
                                family: 'Inter'
                            }
                        }
                    }
                },
                elements: {
                    line: {
                        borderWidth: 2
                    }
                },
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: type === 'bar' ? 30 : 20,
                        bottom: 10
                    }
                }
            }
        });
    }
    
    // Crear gráfica inicial de barras
    createChart('bar');
    
    // Función para cambiar el tipo de gráfica
    window.changeChartType = function(type) {
        // Actualizar botones
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Cambiar gráfica
        createChart(type);
        
        // Actualizar icono del header
        const chartIcon = document.querySelector('.chart-header i');
        if (type === 'bar') {
            chartIcon.className = 'fas fa-chart-bar';
        } else {
            chartIcon.className = 'fas fa-chart-line';
        }
    };
});
</script>

{{-- Agregar plugin para etiquetas en barras --}}
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
@endif
@endsection