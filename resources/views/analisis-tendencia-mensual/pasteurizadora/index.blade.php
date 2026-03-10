@extends('layouts.app')

@section('title', 'Análisis 52-12-4 - Pasteurizadora')

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

    .industrial-filters {
        background: white;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

    .industrial-table-container {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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

    .industrial-chart {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
    }

    .btn-industrial:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4);
    }

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

    .editable-field {
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
        padding: 8px;
        border-radius: 8px;
    }

    .editable-field:hover {
        background: rgba(37, 99, 235, 0.05);
        border-color: var(--primary);
    }

    .editable-field.editing {
        background: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Análisis 52-12-4 - Pasteurizadora
            </h1>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="industrial-filters">
        <div class="filters-header">
            <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                 class="w-8 h-8" 
                 alt="Icono"
                 onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
            <h2>SELECCIONAR LÍNEA</h2>
        </div>
        
        <div class="machine-grid">
            @foreach($lineas as $linea)
                <a href="{{ route('analisis-pasteurizadora.analisis-52-12-4', ['linea_id' => $linea->id]) }}" 
                   class="machine-pill {{ $lineaSeleccionada && $lineaSeleccionada->id == $linea->id ? 'machine-pill-active' : 'machine-pill-inactive' }}">
                    <i class="fas fa-temperature-high"></i>
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    @if($lineaSeleccionada)
        <div class="industrial-table-container">
            <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
                <h2 class="text-2xl font-bold flex items-center gap-3">
                    <i class="fas fa-chart-line"></i>
                    Análisis 52-12-4 - {{ $lineaSeleccionada->nombre }}
                </h2>
                <p class="text-blue-100 mt-2">Última actualización: {{ $registro ? $registro->updated_at->format('d/m/Y H:i') : 'Nunca' }}</p>
            </div>

            @if($registro)
                <div class="p-8">
                    <form id="analisisForm" action="{{ route('analisis-pasteurizadora.analisis-52-12-4.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="linea_id" value="{{ $lineaSeleccionada->id }}">
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {{-- 52 Semanas --}}
                            <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl p-6 border border-blue-200">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-blue-800 text-lg">52 Semanas</h3>
                                        <p class="text-xs text-gray-500">Último año</p>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Valor anterior</p>
                                        <p class="text-2xl font-bold text-gray-400">{{ number_format($registro->valor_anterior_52 ?? 0.51, 2) }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Valor actual</p>
                                        <div class="editable-field" data-field="valor_actual_52" data-value="{{ $registro->valor_actual_52 ?? 0.69 }}">
                                            <p class="text-3xl font-bold text-blue-600">{{ number_format($registro->valor_actual_52 ?? 0.69, 2) }}</p>
                                        </div>
                                    </div>
                                    
                                    @php
                                        $variacion52 = $registro->calcularVariacion($registro->valor_anterior_52, $registro->valor_actual_52);
                                        $color52 = $variacion52 > 0 ? 'text-red-600' : ($variacion52 < 0 ? 'text-green-600' : 'text-yellow-600');
                                        $icono52 = $variacion52 > 0 ? 'fa-arrow-up' : ($variacion52 < 0 ? 'fa-arrow-down' : 'fa-minus');
                                    @endphp
                                    
                                    <div class="pt-4 border-t border-blue-100">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Variación</span>
                                            <span class="text-lg font-bold {{ $color52 }}">
                                                <i class="fas {{ $icono52 }} mr-1"></i>
                                                {{ number_format(abs($variacion52), 2) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 12 Semanas --}}
                            <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-6 border border-green-200">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                        <i class="fas fa-calendar-alt text-green-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-green-800 text-lg">12 Semanas</h3>
                                        <p class="text-xs text-gray-500">Último trimestre</p>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Valor anterior</p>
                                        <p class="text-2xl font-bold text-gray-400">{{ number_format($registro->valor_anterior_12 ?? 0.25, 2) }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Valor actual</p>
                                        <div class="editable-field" data-field="valor_actual_12" data-value="{{ $registro->valor_actual_12 ?? 1.08 }}">
                                            <p class="text-3xl font-bold text-green-600">{{ number_format($registro->valor_actual_12 ?? 1.08, 2) }}</p>
                                        </div>
                                    </div>
                                    
                                    @php
                                        $variacion12 = $registro->calcularVariacion($registro->valor_anterior_12, $registro->valor_actual_12);
                                        $color12 = $variacion12 > 0 ? 'text-red-600' : ($variacion12 < 0 ? 'text-green-600' : 'text-yellow-600');
                                        $icono12 = $variacion12 > 0 ? 'fa-arrow-up' : ($variacion12 < 0 ? 'fa-arrow-down' : 'fa-minus');
                                    @endphp
                                    
                                    <div class="pt-4 border-t border-green-100">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Variación</span>
                                            <span class="text-lg font-bold {{ $color12 }}">
                                                <i class="fas {{ $icono12 }} mr-1"></i>
                                                {{ number_format(abs($variacion12), 2) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 4 Semanas --}}
                            <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-6 border border-purple-200">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                        <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-purple-800 text-lg">4 Semanas</h3>
                                        <p class="text-xs text-gray-500">Último mes</p>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Valor anterior</p>
                                        <p class="text-2xl font-bold text-gray-400">{{ number_format($registro->valor_anterior_4 ?? 0.23, 2) }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Valor actual</p>
                                        <div class="editable-field" data-field="valor_actual_4" data-value="{{ $registro->valor_actual_4 ?? 2.52 }}">
                                            <p class="text-3xl font-bold text-purple-600">{{ number_format($registro->valor_actual_4 ?? 2.52, 2) }}</p>
                                        </div>
                                    </div>
                                    
                                    @php
                                        $variacion4 = $registro->calcularVariacion($registro->valor_anterior_4, $registro->valor_actual_4);
                                        $color4 = $variacion4 > 0 ? 'text-red-600' : ($variacion4 < 0 ? 'text-green-600' : 'text-yellow-600');
                                        $icono4 = $variacion4 > 0 ? 'fa-arrow-up' : ($variacion4 < 0 ? 'fa-arrow-down' : 'fa-minus');
                                    @endphp
                                    
                                    <div class="pt-4 border-t border-purple-100">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Variación</span>
                                            <span class="text-lg font-bold {{ $color4 }}">
                                                <i class="fas {{ $icono4 }} mr-1"></i>
                                                {{ number_format(abs($variacion4), 2) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Botones de acción --}}
                        <div class="mt-8 flex justify-end gap-3">
                            <button type="button" onclick="cancelarEdicion()" 
                                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>

                    {{-- Historial de cambios --}}
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <i class="fas fa-history text-blue-600"></i>
                            Historial de Análisis
                        </h4>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">
                                <i class="far fa-clock mr-2"></i>
                                Última actualización: {{ $registro->updated_at ? $registro->updated_at->format('d/m/Y H:i:s') : 'Nunca' }}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <i class="far fa-calendar-alt mr-2"></i>
                                Registro creado: {{ $registro->created_at ? $registro->created_at->format('d/m/Y H:i:s') : 'Nunca' }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="industrial-empty">
                    <div class="empty-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>SIN DATOS DISPONIBLES</h3>
                    <p>No se encontraron análisis para {{ $lineaSeleccionada->nombre }}</p>
                    <p class="text-sm text-gray-500 mt-2">Los valores se inicializarán al guardar por primera vez</p>
                </div>
            @endif
        </div>

        @if($registro)
        {{-- Gráfica de tendencia --}}
        <div class="industrial-chart">
            <div class="chart-header">
                <i class="fas fa-chart-bar"></i>
                <h3>EVOLUCIÓN HISTÓRICA - {{ $lineaSeleccionada->nombre }}</h3>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        @endif

    @else
        <div class="industrial-empty">
            <div class="empty-icon">
                <i class="fas fa-hand-pointer"></i>
            </div>
            <h3>SELECCIONE UNA LÍNEA</h3>
            <p>Elija una línea del panel superior para visualizar los análisis 52-12-4</p>
        </div>
    @endif
</div>

@if($lineaSeleccionada && $registro)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart;
let editingField = null;

document.addEventListener('DOMContentLoaded', function() {
    // Configurar campos editables
    document.querySelectorAll('.editable-field').forEach(field => {
        field.addEventListener('click', function(e) {
            if (editingField === this) {
                return;
            }
            
            if (editingField) {
                cancelarEdicion();
            }
            
            iniciarEdicion(this);
        });
    });

    // Inicializar gráfica
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    // Datos de ejemplo - idealmente vendrían del backend
    const labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const data52 = [0.51, 0.55, 0.58, 0.62, 0.65, 0.69, 0.72, 0.75, 0.78, 0.81, 0.85, {{ $registro->valor_actual_52 ?? 0.69 }}];
    const data12 = [0.25, 0.35, 0.45, 0.58, 0.72, 0.88, 1.08, 1.15, 1.22, 1.28, 1.35, {{ $registro->valor_actual_12 ?? 1.08 }}];
    const data4 = [0.23, 0.45, 0.89, 1.34, 1.78, 2.52, 2.45, 2.38, 2.52, 2.48, 2.51, {{ $registro->valor_actual_4 ?? 2.52 }}];
    
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '52 Semanas',
                    data: data52,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: '12 Semanas',
                    data: data12,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: '4 Semanas',
                    data: data4,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
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
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#f8fafc',
                    bodyColor: '#f1f5f9',
                    bodyFont: { size: 13, family: 'JetBrains Mono' },
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: { 
                        callback: value => value.toFixed(2),
                        font: { size: 11, family: 'JetBrains Mono' }
                    },
                    title: {
                        display: true,
                        text: 'TOTAL DE DAÑOS',
                        font: { size: 11, weight: '600' },
                        color: '#64748b'
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
});

function iniciarEdicion(field) {
    const valorActual = field.querySelector('p');
    const valor = field.dataset.value;
    const fieldName = field.dataset.field;
    
    // Crear input
    const input = document.createElement('input');
    input.type = 'number';
    input.step = '0.01';
    input.value = valor;
    input.className = 'w-full px-3 py-2 text-3xl font-bold border-2 border-blue-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300';
    input.style.fontSize = '1.875rem';
    input.style.fontWeight = 'bold';
    input.style.color = '#2563eb';
    
    // Reemplazar contenido
    field.innerHTML = '';
    field.appendChild(input);
    field.classList.add('editing');
    
    input.focus();
    input.select();
    
    input.addEventListener('blur', function() {
        finalizarEdicion(field, input.value, fieldName);
    });
    
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            finalizarEdicion(field, input.value, fieldName);
        } else if (e.key === 'Escape') {
            cancelarEdicion();
        }
    });
    
    editingField = field;
}

function finalizarEdicion(field, newValue, fieldName) {
    // Actualizar valor mostrado
    const formattedValue = parseFloat(newValue).toFixed(2);
    const p = document.createElement('p');
    p.className = 'text-3xl font-bold text-blue-600';
    p.textContent = formattedValue;
    
    field.innerHTML = '';
    field.appendChild(p);
    field.classList.remove('editing');
    field.dataset.value = newValue;
    
    // Actualizar el input oculto en el formulario
    let hiddenInput = document.querySelector(`input[name="${fieldName}"]`);
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = fieldName;
        document.getElementById('analisisForm').appendChild(hiddenInput);
    }
    hiddenInput.value = newValue;
    
    editingField = null;
}

function cancelarEdicion() {
    if (editingField) {
        const field = editingField;
        const valor = field.dataset.value;
        
        const p = document.createElement('p');
        p.className = 'text-3xl font-bold text-blue-600';
        p.textContent = parseFloat(valor).toFixed(2);
        
        field.innerHTML = '';
        field.appendChild(p);
        field.classList.remove('editing');
        
        editingField = null;
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && editingField) {
        cancelarEdicion();
    }
});
</script>
@endif
@endsection