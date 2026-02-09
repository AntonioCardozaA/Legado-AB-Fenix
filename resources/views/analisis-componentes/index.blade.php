@extends('layouts.app')

@section('title', 'Análisis de Componentes')

@section('content')
<style>
    /* VARIABLES CSS PARA CONSISTENCIA */
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --changed-blue: #3b82f6; /* Nuevo color para "Dañado - Cambiado" */
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
    }
    
    .sticky-top { position: sticky; top: 0; z-index: 30; }
    .sticky-left { position: sticky; left: 0; z-index: 20; }
    .sticky-top-left { position: sticky; top: 0; left: 0; z-index: 40; }
    .cell-ok { background-color: #f0f9ff; border-left: 4px solid var(--success-green); }
    .cell-warning { background-color: #fffbeb; border-left: 4px solid var(--warning-yellow); }
    .cell-danger { background-color: #fef2f2; border-left: 4px solid var(--danger-red); }
    .cell-changed { background-color: #eff6ff; border-left: 4px solid var(--changed-blue); } /* Nuevo estilo */
    .cell-empty { background-color: var(--light-gray); }
    .cell-header { background-color: #eff6ff; }
    
    .compact-table td, .compact-table th {
        padding: 8px !important;
        font-size: 0.75rem !important;
        min-width: 120px;
    }
    
    .table-container {
        max-height: 600px;
        overflow-y: auto;
    }
    
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
        max-height: 60vh;
        overflow-y: auto;
        padding: 10px;
    }
    
    .image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid var(--medium-gray);
        transition: all 0.3s ease;
        background: white;
    }
    
    .image-item:hover {
        border-color: var(--primary-blue);
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .grid-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .grid-image:hover {
        transform: scale(1.05);
    }
    
    .image-info {
        padding: 8px;
        background: linear-gradient(to bottom, rgba(255,255,255,0.9), white);
        border-top: 1px solid #f3f4f6;
    }
    
    .image-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 12px;
        z-index: 10;
    }
    
    .download-image-btn {
        width: 100%;
        padding: 6px;
        margin-top: 8px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.3s ease;
    }
    
    .download-image-btn:hover {
        background: #2563eb;
    }
    
    .download-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: var(--success-green);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.3s ease;
    }
    
    .download-all-btn:hover {
        background: #059669;
    }
    
    .empty-images {
        text-align: center;
        padding: 40px;
        color: var(--dark-gray);
    }
    
    .analysis-cell {
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        min-height: 120px;
    }
    
    .analysis-cell.no-data {
        cursor: default;
    }
    
    .analysis-cell.no-data:hover {
        background-color: inherit !important;
        transform: none;
    }
    
    .click-indicator {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 10px;
        color: var(--primary-blue);
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .analysis-cell:not(.no-data):hover .click-indicator {
        opacity: 1;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .detail-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--medium-gray);
    }
    
    .detail-card h4 {
        font-size: 14px;
        font-weight: 600;
        color: var(--dark-gray);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-card p {
        font-size: 15px;
        color: #374151;
        line-height: 1.5;
    }
    
    .activity-content {
        background: #f9fafb;
        border-radius: 8px;
        padding: 15px;
        margin-top: 5px;
        border-left: 4px solid var(--primary-blue);
    }
    
    .activity-content p {
        white-space: pre-wrap;
        word-break: break-word;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .status-badge.ok {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-badge.warning {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-badge.danger {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .status-badge.changed {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .detail-images-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--medium-gray);
        margin-top: 20px;
    }
    
    .detail-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
    }
    
    .empty-cell {
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--dark-gray);
        padding: 10px;
    }
    
    .no-records {
        text-align: center;
        padding: 20px;
        color: var(--dark-gray);
    }
    
    .empty-cell-icon {
        font-size: 24px;
        margin-bottom: 8px;
        color: #d1d5db;
    }
    
    .component-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }
    
    .component-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }
    
    .component-code {
        font-size: 9px;
        color: var(--dark-gray);
        background: #f3f4f6;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .reductor-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }
    
    .reductor-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }
    
    .reductor-label {
        font-size: 9px;
        color: var(--dark-gray);
        background: #f3f4f6;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .table-wrapper {
        position: relative;
        overflow: auto;
        border: 1px solid var(--medium-gray);
        border-radius: 8px;
    }
    
    .table-corner {
        background: #eff6ff;
        border-right: 1px solid #dbeafe;
        border-bottom: 1px solid #dbeafe;
    }
    
    .scroll-indicator {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        z-index: 50;
        display: none;
    }
    
    .table-wrapper:hover .scroll-indicator {
        display: block;
    }
    
    /* MEJORAS VISUALES */
    .cell-highlight {
        animation: highlight-pulse 2s ease-out;
    }
    
    @keyframes highlight-pulse {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
    
    .badge-new {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #ef4444;
        color: white;
        font-size: 8px;
        padding: 2px 6px;
        border-radius: 10px;
        z-index: 5;
    }
    
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
    }
    
    /* NUEVOS ESTILOS PARA MEJOR ORGANIZACIÓN */
    .filter-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border: 1px solid var(--medium-gray);
    }
    
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .filter-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .header-with-icon {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .header-with-icon i {
        color: var(--primary-blue);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.75rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        border: 1px solid var(--medium-gray);
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin-top: 0.25rem;
    }
    
    .table-header-container {
        background: linear-gradient(135deg, var(--primary-blue), #1d4ed8);
        color: white;
        padding: 1.5rem;
        border-radius: 10px 10px 0 0;
    }
    
    .table-header-content {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .table-title-section {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .table-icon-container {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .table-info-section {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        align-items: center;
    }
    
    .table-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.1);
        padding: 0.5rem 0.75rem;
        border-radius: 20px;
    }
    
    .table-controls {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;
    }
    
    .control-btn {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 8px;
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .control-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
    }
    
    /* RESPONSIVE ADJUSTMENTS */
    @media (max-width: 768px) {
        .compact-table td, .compact-table th {
            min-width: 100px;
            font-size: 0.7rem !important;
            padding: 6px !important;
        }
        
        .detail-actions {
            flex-direction: column;
        }
        
        .image-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        
        .filter-grid {
            grid-template-columns: 1fr;
        }
        
        .table-header-content {
            gap: 0.75rem;
        }
        
        .table-title-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .table-info-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .table-controls {
            margin-left: 0;
            width: 100%;
            justify-content: flex-start;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .compact-table td, .compact-table th {
            min-width: 90px;
        }
        
        .image-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* ESTADO DE CARGA */
    .skeleton-loading {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>

<div class="max-w-full mx-auto px-4 py-6">
    {{-- HEADER MEJORADO --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-yellow-500"></i>
                Análisis de Componentes
            </h1>
            <p class="text-gray-600 text-sm mt-1">Gestión y seguimiento de análisis de componentes</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition flex items-center gap-2 shadow-sm hover:shadow-md">
                <i class="fas fa-plus"></i>
                Nuevo Análisis
            </a>
        </div>
    </div>

    {{-- FILTROS MEJORADOS --}}
    @php
        $lineas = $lineas ?? collect([]);
        $todosComponentes = $todosComponentes ?? [];
        $componentesPorLinea = $componentesPorLinea ?? [];
        $analisis = $analisis ?? null;
        $reductoresMostrar = $reductoresMostrar ?? [];
    @endphp
    
    @if(isset($lineas) && $lineas->count() > 0)
        <div class="filter-card mb-6">
            <form method="GET" action="{{ route('analisis-componentes.index') }}">
                <div class="filter-grid mb-4">
                    <div>
                        <div class="header-with-icon">
                            <i class="fas fa-washing-machine"></i>
                            <label class="block text-sm font-medium text-gray-700">Lavadora</label>
                        </div>
                        <select name="linea_id" id="lineaSelect" 
                                class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 shadow-sm p-2.5"
                                onchange="updateComponentes(this.value)">
                            <option value="">Todas las lavadoras</option>
                            @foreach($lineas as $l)
                                <option value="{{ $l->id }}" {{ request('linea_id') == $l->id ? 'selected' : '' }}>
                                    {{ $l->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="header-with-icon">
                            <i class="fas fa-cog"></i>
                            <label class="block text-sm font-medium text-gray-700">Componente</label>
                        </div>
                        <select name="componente_id" id="componenteSelect" 
                                class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 shadow-sm p-2.5">
                            <option value="">Todos los componentes</option>
                            @foreach(($todosComponentes ?? []) as $key => $nombre)
                                <option value="{{ $key }}" {{ request('componente_id') == $key ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="header-with-icon">
                            <i class="fas fa-compress-alt"></i>
                            <label class="block text-sm font-medium text-gray-700">Reductor</label>
                        </div>
                        <select name="reductor" id="reductorSelect" 
                                class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 shadow-sm p-2.5">
                            <option value="">Todos los reductores</option>
                            @php
                                $reductoresPorLinea = [
                                    'L-04' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                              'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                              'Reductor 18', 'Reductor 19', 'Reductor Loca'],
                                    'L-05' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                                              'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                                              'Reductor 11', 'Reductor 12', 'Reductor Principal', 'Reductor Loca'],
                                    'L-06' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                              'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                              'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22'],
                                    'L-07' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                              'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                              'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22'],
                                    'L-08' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                              'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                              'Reductor 18', 'Reductor 19', 'Reductor Loca'],
                                    'L-09' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                              'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                              'Reductor 18', 'Reductor 19', 'Reductor Loca'],
                                    'L-12' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                                              'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                                              'Reductor 11', 'Reductor 12', 'Reductor Loca'],
                                    'L-13' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                                              'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                                              'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal']
                                ];
                                
                                $todosReductores = [];
                                foreach ($reductoresPorLinea as $lineaReductores) {
                                    foreach ($lineaReductores as $reductor) {
                                        $todosReductores[$reductor] = $reductor;
                                    }
                                }
                                ksort($todosReductores);
                            @endphp
                            
                            @foreach($todosReductores as $reductor)
                                <option value="{{ $reductor }}" {{ request('reductor') == $reductor ? 'selected' : '' }}>
                                    {{ $reductor }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="header-with-icon">
                            <i class="far fa-calendar-alt"></i>
                            <label class="block text-sm font-medium text-gray-700">Mes / Año</label>
                        </div>
                        <input type="month" name="fecha" value="{{ request('fecha') }}"
                               class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 shadow-sm p-2.5">
                    </div>
                </div>
                
                {{-- FILTROS AVANZADOS (COLAPSABLE) --}}
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <button type="button" onclick="toggleAdvancedFilters()"
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-colors">
                        <i class="fas fa-sliders-h"></i>
                        Filtros avanzados
                        <i id="advancedFiltersIcon" class="fas fa-chevron-down ml-1 transition-transform duration-200"></i>
                    </button>
                    
                    <div id="advancedFilters" class="hidden mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="header-with-icon">
                                <i class="fas fa-clipboard-check"></i>
                                <label class="block text-xs font-medium text-gray-600">Estado</label>
                            </div>
                            <select name="estado" class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 p-2.5">
                                <option value="">Todos los estados</option>
                                <option value="Buen estado" {{ old('estado') == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                                <option value="Desgaste moderado" {{ old('estado') == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                                <option value="Desgaste severo" {{ old('estado') == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                                <option value="Dañado - Requiere cambio" {{ old('estado') == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                                <option value="Dañado - Cambiado" {{ old('estado') == 'Dañado - Cambiado' ? 'selected' : '' }}>🔄 Dañado - Cambiado</option>
                            </select>
                        </div>
                        
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" 
                            class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-sm hover:shadow-md font-medium">
                        <i class="fas fa-search"></i>
                        Aplicar Filtros
                    </button>
                    <a href="{{ route('analisis-componentes.index') }}"
                       class="flex-1 bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition flex items-center justify-center gap-2 shadow-sm hover:shadow-md font-medium">
                        <i class="fas fa-eraser"></i>
                        Limpiar Filtros
                    </a>
                </div>
            </form>
        </div>
    @endif

    {{-- RESÚMENES Y ESTADÍSTICAS --}}
    @php
    $analisisCollection = isset($analisis) ? collect($analisis->items() ?? []) : collect([]);

    if ($analisisCollection->count() > 0) {
        $estadisticas = [
            'total' => $analisisCollection->count(),

            'buen_estado' => $analisisCollection
                ->where('estado', 'Buen estado')
                ->count(),

            'desgaste' => $analisisCollection
                ->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])
                ->count(),

            'danado_requiere' => $analisisCollection
                ->where('estado', 'Dañado - Requiere cambio')
                ->count(),

            'danado_cambiado' => $analisisCollection
                ->where('estado', 'Dañado - Cambiado')
                ->count(),

            'danado' => $analisisCollection
                ->whereIn('estado', ['Dañado - Requiere cambio', 'Dañado - Cambiado'])
                ->count(),

            'recientes' => $analisisCollection->filter(function ($item) {
                return $item->created_at && $item->created_at->gt(now()->subDays(7));
            })->count(),
        ];
    }
@endphp


    @if($analisisCollection->count() > 0)
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <div class="text-sm text-gray-600 font-medium">Total</div>
            <div class="stat-value text-blue-700">{{ $estadisticas['total'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="text-sm text-gray-600 font-medium">Buen estado</div>
            <div class="stat-value text-green-600">{{ $estadisticas['buen_estado'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="text-sm text-gray-600 font-medium">Desgaste</div>
            <div class="stat-value text-yellow-600">{{ $estadisticas['desgaste'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="text-sm text-gray-600 font-medium">Dañado (Requiere)</div>
            <div class="stat-value text-red-600">{{ $estadisticas['danado_requiere'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="text-sm text-gray-600 font-medium">Dañado (Cambiado)</div>
            <div class="stat-value text-blue-600">{{ $estadisticas['danado_cambiado'] ?? 0 }}</div>
        </div>
    </div>
    @endif

    {{-- TABLA PRINCIPAL MEJORADA --}}
    @php
    /* ===============================
    LINEA A MOSTRAR
    =============================== */
    $lineaMostrar =
        (request('linea_id') && isset($lineas))
            ? $lineas->firstWhere('id', request('linea_id'))
            : ($analisisCollection->first()->linea ?? null);

    /* ===============================
    COMPONENTES PARA TABLA
    =============================== */
    $componentesParaTabla = collect();

    if ($lineaMostrar && isset($componentesPorLinea[$lineaMostrar->nombre])) {
        foreach ($componentesPorLinea[$lineaMostrar->nombre] as $id => $nombre) {
            $componentesParaTabla->push((object)[
                'id'     => $id,
                'nombre' => $nombre,
                'icono'  => asset("images/componentes/{$id}.png"),
            ]);
        }
    }

    if (request('componente_id')) {
        $componentesParaTabla = $componentesParaTabla
            ->where('id', request('componente_id'))
            ->values();
    }

    /* ===============================
    REDUCTORES PARA TABLA
    =============================== */
    $reductoresParaTabla = collect();

    if (request('linea_id') && !empty($reductoresMostrar)) {
        $reductoresParaTabla = collect($reductoresMostrar);
    } elseif ($analisisCollection->count() > 0) {
        $reductoresParaTabla = $analisisCollection
            ->pluck('reductor')
            ->unique()
            ->sort()
            ->values();
    }

    if (request('reductor')) {
        $reductoresParaTabla = $reductoresParaTabla
            ->where(fn($r) => $r == request('reductor'))
            ->values();
    }

    /* ===============================
    AGRUPAR ANALISIS
    =============================== */
    $analisisAgrupados = [];

    foreach ($analisisCollection as $item) {
        if (!$item->componente) continue;

        $reductor = $item->reductor;
        $codigo   = $item->componente->codigo ?? '';
        $codigoBase = $codigo;

        if (isset($componentesPorLinea)) {
            foreach ($componentesPorLinea as $lineaCodigos) {
                foreach ($lineaCodigos as $key => $nombre) {
                    if (str_contains($codigo, $key)) {
                        $codigoBase = $key;
                        break 2;
                    }
                }
            }
        }

        $analisisAgrupados[$reductor][$codigoBase] = $item;
    }
    @endphp

    @if((isset($lineaMostrar) && $lineas->count() > 0) || (isset($analisis) && $analisis->total() > 0))
        <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            {{-- ENCABEZADO DE TABLA MEJORADO --}}
            <div class="table-header-container">
                <div class="table-header-content">
                    <div class="table-title-section">
                           {{-- Ícono de máquina --}}
        <div class="flex justify-center md:justify-start">
            <div class="w-20 h-20">
                <img 
                    src="{{ asset('images/icono-maquina.png') }}" 
                    alt="Icono de lavadora"
                    class="w-full h-full object-contain">
            </div>
        </div>
                      <div>
                            <h2 class="font-bold text-xl text-white">
                                {{ $lineaMostrar->nombre ?? 'Análisis de Componentes' }}
                            </h2>
                            <div class="text-blue-100 text-sm mt-1 flex flex-wrap gap-2">
                                @if(request('componente_id') && isset($todosComponentes))
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-cog"></i>
                                        Componente: {{ $todosComponentes[request('componente_id')] ?? request('componente_id') }}
                                    </span>
                                @endif
                                
                                @if(request('reductor'))
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-sliders-h"></i>
                                        Reductor: {{ request('reductor') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="table-info-section">
                        @if(isset($componentesParaTabla) && isset($reductoresParaTabla) && isset($estadisticas))
                            <div class="table-stats">
                                <div class="stat-item">
                                    <i class="fas fa-check-circle text-green-300"></i>
                                    <span class="text-sm font-medium">{{ $estadisticas['buen_estado'] ?? 0 }}</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-exclamation-triangle text-yellow-300"></i>
                                    <span class="text-sm font-medium">{{ $estadisticas['desgaste'] ?? 0 }}</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-times-circle text-red-300"></i>
                                    <span class="text-sm font-medium">{{ $estadisticas['danado_requiere'] ?? 0 }}</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-exchange-alt text-blue-300"></i>
                                    <span class="text-sm font-medium">{{ $estadisticas['danado_cambiado'] ?? 0 }}</span>
                                </div>
                            </div>
                        @endif
                        
                        <div class="table-controls">
                            <button onclick="toggleViewMode()" 
                                    class="control-btn"
                                    title="Cambiar vista">
                                <i id="viewModeIcon" class="fas fa-table"></i>
                            </button>
                            <button onclick="toggleCompactView()" 
                                    class="control-btn"
                                    title="Vista compacta">
                                <i id="compactViewIcon" class="fas fa-compress-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLA COMPACTA CON MEJORAS --}}
            @if(isset($componentesParaTabla) && isset($reductoresParaTabla) && count($componentesParaTabla) > 0 && count($reductoresParaTabla) > 0)
                <div class="table-wrapper" id="mainTable">
                    <div class="scroll-indicator">
                        <i class="fas fa-arrows-alt-h mr-1"></i> Desplázate para ver más
                    </div>
                    <table class="w-full compact-table border-collapse" id="analysisTable">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="sticky-top-left sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm table-corner">
                                    <div class="reductor-header">
                                        <div class="reductor-name">REDUCTOR</div>
                                        <div class="reductor-label">COMPONENTE</div>
                                    </div>
                                </th>
                                @foreach($componentesParaTabla as $c)
                                    @php
                                        $conteoEstado = [
                                            'ok' => 0,
                                            'warning' => 0,
                                            'danger' => 0,
                                            'changed' => 0,
                                            'empty' => count($reductoresParaTabla)
                                        ];
                                        
                                        foreach($reductoresParaTabla as $r) {
                                            if(isset($analisisAgrupados[$r][$c->id])) {
                                                $registro = $analisisAgrupados[$r][$c->id];
                                                $estado = $registro->estado ?? 'Buen estado';
                                                if (str_contains($estado, 'Dañado - Cambiado')) {
                                                    $conteoEstado['changed']++;
                                                    $conteoEstado['empty']--;
                                                } elseif(str_contains($estado, 'Dañado')) {
                                                    $conteoEstado['danger']++;
                                                    $conteoEstado['empty']--;
                                                } elseif(str_contains($estado, 'Desgaste')) {
                                                    $conteoEstado['warning']++;
                                                    $conteoEstado['empty']--;
                                                } else {
                                                    $conteoEstado['ok']++;
                                                    $conteoEstado['empty']--;
                                                }
                                            }
                                        }
                                    @endphp
                                    <th class="sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                        <div class="component-header">
                                            <div class="component-name">{{ $c->nombre }}</div>
                                            <div class="component-code">{{ $c->id }}</div>
                                            <img
                                                src="{{ $c->icono }}"
                                                alt="Icono {{ $c->nombre }}"
                                                class="w-20 h-20 object-contain hover:scale-110 transition-transform"
                                                onerror="this.src='{{ asset('images/componentes/Buje Baquelita-Espiga.png') }}'">

                                            <div class="flex justify-center gap-1 mt-1">
                                                @if($conteoEstado['ok'] > 0)
                                                    <span class="w-1 h-1 bg-green-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['warning'] > 0)
                                                    <span class="w-1 h-1 bg-yellow-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['danger'] > 0)
                                                    <span class="w-1 h-1 bg-red-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['changed'] > 0)
                                                    <span class="w-1 h-1 bg-blue-500 rounded-full"></span>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reductoresParaTabla as $r)
                                @php
                                    $conteoReductor = [
                                        'total' => 0,
                                        'ok' => 0,
                                        'warning' => 0,
                                        'danger' => 0,
                                        'changed' => 0
                                    ];
                                    
                                    foreach($componentesParaTabla as $c) {
                                        if(isset($analisisAgrupados[$r][$c->id])) {
                                            $conteoReductor['total']++;
                                            $registro = $analisisAgrupados[$r][$c->id];
                                            $estado = $registro->estado ?? 'Buen estado';
                                            if (str_contains($estado, 'Dañado - Cambiado')) {
                                                $conteoReductor['changed']++;
                                            } elseif(str_contains($estado, 'Dañado')) {
                                                $conteoReductor['danger']++;
                                            } elseif(str_contains($estado, 'Desgaste')) {
                                                $conteoReductor['warning']++;
                                            } else {
                                                $conteoReductor['ok']++;
                                            }
                                        }
                                    }
                                @endphp
                                <tr>
                                    <th class="sticky-left cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm align-top">
                                        <div class="reductor-header">
                                            <div class="reductor-name">{{ $r }}</div>
                                            <div class="reductor-label">Reductor</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $conteoReductor['total'] }}/{{ count($componentesParaTabla) }}
                                            </div>
                                        </div>
                                    </th>
                                    
                                    @foreach($componentesParaTabla as $c)
                                        @php
                                            $registro = $analisisAgrupados[$r][$c->id] ?? null;
                                            $hasData = !empty($registro);
                                            $color = '';
                                            $isNew = false;
                                            
                                            if($hasData){
                                                $estadoActual = $registro->estado ?? 'Buen estado';
                                                
                                                // Determinar color de la celda
                                                if (str_contains($estadoActual, 'Dañado - Cambiado')) {
                                                    $color = 'cell-changed';
                                                } elseif (str_contains($estadoActual, 'Dañado')) {
                                                    $color = 'cell-danger';
                                                } elseif (str_contains($estadoActual, 'Desgaste')) {
                                                    $color = 'cell-warning';
                                                } else {
                                                    $color = 'cell-ok';
                                                }
                                                
                                                if($registro->created_at && $registro->created_at->gt(now()->subDays(3))) {
                                                    $isNew = true;
                                                }
                                                
                                                $imagenes = $registro->evidencia_fotos ?? null;
                                                if (is_string($imagenes)) {
                                                    $imagenes = json_decode($imagenes, true) ?? [];
                                                } elseif (is_array($imagenes)) {
                                                    $imagenes = $imagenes;
                                                } else {
                                                    $imagenes = [];
                                                }
                                            }
                                        @endphp
                                        
                                        <td class="border px-3 py-2 align-top {{ $hasData ? $color : 'cell-empty' }} {{ $hasData ? 'analysis-cell' : 'analysis-cell no-data' }}" 
                                            @if($hasData)
                                            onclick="openAnalysisDetail({{ json_encode([
                                                'id' => $registro->id,
                                                'linea' => $registro->linea->nombre ?? 'Sin nombre',
                                                'componente' => $registro->componente->nombre ?? 'Sin nombre',
                                                'componente_codigo' => $registro->componente->codigo ?? '',
                                                'reductor' => $registro->reductor,
                                                'fecha_analisis' => isset($registro->fecha_analisis) ? $registro->fecha_analisis->format('d/m/Y') : '',
                                                'numero_orden' => $registro->numero_orden,
                                                'estado' => $registro->estado ?? 'Buen estado',
                                                'actividad' => $registro->actividad,
                                                'imagenes' => $imagenes ?? [],
                                                'color' => $color,
                                                'created_at' => isset($registro->created_at) ? $registro->created_at->format('d/m/Y H:i') : '',
                                                'updated_at' => isset($registro->updated_at) ? $registro->updated_at->format('d/m/Y H:i') : '',
                                                'is_new' => $isNew
                                            ]) }})"
                                            @endif>
                                            
                                            @if($hasData)
                                                @if($isNew)
                                                    <div class="badge-new">NUEVO</div>
                                                @endif
                                                
                                                <div class="space-y-2">
                                                    <div class="flex flex-col">
                                                        <div class="flex items-center gap-1 mb-1">
                                                            <i class="fas fa-calendar text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-semibold text-gray-700">
                                                                {{ isset($registro->fecha_analisis) ? $registro->fecha_analisis->format('d/m/Y') : '' }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <i class="fas fa-hashtag text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-bold text-gray-800">
                                                                Orden #{{ $registro->numero_orden }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        @php
                                                            $estadoActual = $registro->estado ?? 'Buen estado';
                                                            
                                                            // Determinar clase de color según el estado
                                                            if (str_contains($estadoActual, 'Dañado - Cambiado')) {
                                                                $statusClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                                                $icon = 'fa-exchange-alt';
                                                            } elseif (str_contains($estadoActual, 'Dañado')) {
                                                                $statusClass = 'bg-red-100 text-red-800 border-red-200';
                                                                $icon = 'fa-times-circle';
                                                            } elseif (str_contains($estadoActual, 'Desgaste')) {
                                                                $statusClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                                                $icon = 'fa-exclamation-triangle';
                                                            } else {
                                                                $statusClass = 'bg-green-100 text-green-800 border-green-200';
                                                                $icon = 'fa-check-circle';
                                                            }
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $statusClass }}">
                                                            <i class="fas {{ $icon }} mr-1"></i>
                                                            {{ Str::limit($estadoActual, 20) }}
                                                        </span>
                                                    </div>
                                                    
                                                    <div>
                                                        <p class="text-gray-700 text-xs">
                                                            {{ Str::limit($registro->actividad, 80) }}
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="flex flex-col gap-1 mt-3">
                                                        @if(!empty($imagenes) && count($imagenes) > 0)
                                                            <button onclick="event.stopPropagation(); openAllImages({{ json_encode($imagenes) }}, 
                                                                    '{{ isset($registro->fecha_analisis) ? $registro->fecha_analisis->format('d/m/Y') : '' }}', 
                                                                    '{{ $registro->numero_orden }}', 
                                                                    '{{ $registro->estado ?? 'Buen estado' }}')"
                                                                    class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition text-xs font-medium">
                                                                <i class="fas fa-images mr-1"></i>
                                                                {{ count($imagenes) }} img
                                                            </button>
                                                        @endif
                                                        
                                                        <a href="{{ route('analisis-componentes.edit', [
                                                            'analisisComponente' => $registro->id,
                                                            'linea_id' => request('linea_id', ''),
                                                            'componente_id' => request('componente_id', ''),
                                                            'reductor' => request('reductor', ''),
                                                            'fecha' => request('fecha', '')
                                                        ]) }}"
                                                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-edit"></i>
                                                            Editar
                                                        </a>
                                                        
                                                        <form action="{{ route('analisis-componentes.destroy', $registro->id) }}" 
                                                              method="POST" 
                                                              class="inline"
                                                              onsubmit="return confirmDelete(event)">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="redirect_params" value="{{ json_encode(request()->query()) }}">
                                                            <button type="submit" 
                                                                    onclick="event.stopPropagation();"
                                                                    class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 transition text-xs font-medium">
                                                                <i class="fas fa-trash"></i>
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    @if($hasData)
                                                        <div class="click-indicator">
                                                            <i class="fas fa-search-plus mr-1"></i> Detalles
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="empty-cell">
                                                    <div class="empty-cell-icon">
                                                        <i class="fas fa-clipboard"></i>
                                                    </div>
                                                    <p class="text-gray-500 text-xs mb-3">Sin análisis</p>
                                                    
                                                    @if($lineaMostrar)
                                                        <a href="{{ route('analisis-componentes.create-quick',[
                                                            'linea_id' => $lineaMostrar->id,
                                                            'componente_codigo' => $c->id,
                                                            'reductor' => $r,
                                                            'fecha' => request('fecha', now()->format('Y-m'))]) }}"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-plus"></i>
                                                            Agregar
                                                        </a>
                                                    @else
                                                        <a href="{{ route('analisis-componentes.select-linea') }}"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-plus"></i>
                                                            Nuevo
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-info-circle text-3xl mb-4"></i>
                    <p>No hay componentes o reductores para mostrar</p>
                </div>
            @endif
        </div>
        
        {{-- PAGINACIÓN MEJORADA --}}
        @if(isset($analisis) && $analisis->total() > 0)
            <div class="mt-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Mostrando {{ $analisis->firstItem() }} - {{ $analisis->lastItem() }} de {{ $analisis->total() }} registros
                    </div>
                    <div class="flex flex-wrap gap-2">
                        {{ $analisis->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- VISTA INICIAL MEJORADA --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="text-blue-400 mb-4">
                <i class="fas fa-clipboard-list text-5xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Bienvenido al Sistema de Análisis de Componentes</h3>
            <p class="text-gray-500 mb-4">Seleccione una lavadora para ver los análisis o cree uno nuevo.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <i class="fas fa-search text-blue-600 text-xl mb-2"></i>
                    <h4 class="font-medium text-gray-700">Filtrar</h4>
                    <p class="text-sm text-gray-500">Use filtros para encontrar análisis específicos</p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg">
                    <i class="fas fa-plus text-green-600 text-xl mb-2"></i>
                    <h4 class="font-medium text-gray-700">Crear</h4>
                    <p class="text-sm text-gray-500">Agregue nuevos análisis de componentes</p>
                </div>
                <div class="p-4 bg-yellow-50 rounded-lg">
                    <i class="fas fa-chart-bar text-yellow-600 text-xl mb-2"></i>
                    <h4 class="font-medium text-gray-700">Analizar</h4>
                    <p class="text-sm text-gray-500">Vea estadísticas y tendencias</p>
                </div>
            </div>
            
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition shadow-lg hover:shadow-xl">
                <i class="fas fa-plus"></i>
                Comenzar Nuevo Análisis
            </a>
        </div>
    @endif
</div>

{{-- MODALES MEJORADOS --}}
<div id="analysisDetailModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4"
     onclick="closeAnalysisDetailModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <div>
                <h3 class="font-bold text-lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span id="detailModalTitle">Detalle del Análisis</span>
                </h3>
                <div class="text-sm text-blue-100 mt-1">
                    <span id="detailModalSubtitle">Información completa del registro</span>
                </div>
            </div>
            <button onclick="closeAnalysisDetailModal()"
                    class="text-white hover:text-yellow-300 text-2xl transition transform hover:scale-110">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-auto max-h-[calc(90vh-80px)]">
            <div class="detail-grid">
                <div class="detail-card">
                    <h4><i class="fas fa-washing-machine mr-2"></i>Lavadora</h4>
                    <p id="detail-linea" class="font-semibold text-lg"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-cog mr-2"></i>Componente</h4>
                    <p id="detail-componente" class="font-semibold text-lg"></p>
                    <p id="detail-componente-codigo" class="text-sm text-gray-500 mt-1"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-compress-alt mr-2"></i>Reductor</h4>
                    <p id="detail-reductor" class="font-semibold text-lg"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="far fa-calendar-alt mr-2"></i>Fecha de Análisis</h4>
                    <p id="detail-fecha" class="font-semibold"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-hashtag mr-2"></i>Número de Orden</h4>
                    <p id="detail-orden" class="font-semibold text-lg text-blue-700"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-clipboard-check mr-2"></i>Estado</h4>
                    <div id="detail-estado" class="status-badge ok mt-2 inline-flex"></div>
                </div>
                
                <div class="detail-card">
                    <h4><i class="far fa-calendar-plus mr-2"></i>Fecha de Creación</h4>
                    <p id="detail-created" class="text-sm text-gray-600"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="far fa-calendar-check mr-2"></i>Última Actualización</h4>
                    <p id="detail-updated" class="text-sm text-gray-600"></p>
                </div>
            </div>
            
            {{-- Actividad --}}
            <div class="detail-card mt-6">
                <h4><i class="fas fa-sticky-note mr-2"></i>Actividad / Observaciones</h4>
                <div class="activity-content">
                    <p id="detail-actividad" class="whitespace-pre-line"></p>
                </div>
            </div>
            
            {{-- Imágenes --}}
            <div id="detail-images-section" class="detail-images-container hidden">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-images mr-2"></i>Evidencia Fotográfica
                    </h4>
                    <button id="toggleDetailImages" onclick="toggleDetailImages()" 
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        <i class="fas fa-expand-alt"></i>
                        Expandir
                    </button>
                </div>
                <div id="detail-image-grid" class="image-grid"></div>
            </div>
            
            <div class="detail-actions">
                <a id="detail-edit-btn" href="#"
                   class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-edit"></i>
                    Editar Registro
                </a>
                
                <button id="detail-images-btn" onclick="showDetailImages()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-images"></i>
                    Ver Imágenes
                </button>
                
                <button id="detail-print-btn" onclick="printDetail()"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
                
                <button onclick="closeAnalysisDetailModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PARA IMÁGENES --}}
<div id="allImagesModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4"
     onclick="closeAllImagesModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <div>
                <h3 class="font-bold text-lg">
                    <i class="fas fa-images mr-2"></i>
                    <span id="modalTitle">Imágenes del Análisis</span>
                </h3>
                <div class="text-sm text-blue-100 mt-1">
                    <span id="modalDate"></span> • Orden: <span id="modalOrder"></span> • 
                    Estado: <span id="modalStatus"></span> •
                    <span id="imageCount" class="font-bold"></span>
                </div>
            </div>
            <button onclick="closeAllImagesModal()"
                    class="text-white hover:text-yellow-300 text-2xl transition transform hover:scale-110">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-auto max-h-[calc(90vh-80px)]">
            <div id="imageGrid" class="image-grid"></div>
            
            <div id="emptyImages" class="empty-images hidden">
                <i class="fas fa-image text-4xl mb-4 text-gray-300"></i>
                <p class="text-lg font-medium text-gray-500">No hay imágenes disponibles</p>
                <p class="text-sm text-gray-400 mt-2">Este registro no tiene imágenes adjuntas</p>
            </div>
            
            <div class="flex flex-wrap gap-3 mt-6 justify-center">
                <button onclick="closeAllImagesModal()"
                        class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
                
                <button id="downloadAllImagesBtn" onclick="downloadAllImages()"
                        class="download-all-btn shadow-sm hover:shadow-md">
                    <i class="fas fa-download"></i>
                    Descargar Todas las Imágenes
                </button>
                
                <button onclick="printImages()"
                        class="px-5 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-print"></i>
                    Imprimir Imágenes
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PARA IMAGEN INDIVIDUAL --}}
<div id="singleImageModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[60] p-4"
     onclick="closeSingleImageModal()">
    <div class="relative max-w-5xl w-full max-h-[90vh]">
        <div class="flex justify-between items-center mb-4">
            <div class="text-white text-sm opacity-75">
                <i class="fas fa-image mr-2"></i>
                <span id="singleImageTitle">Imagen</span>
            </div>
            <button onclick="closeSingleImageModal()"
                    class="text-white hover:text-yellow-300 text-2xl transition transform hover:scale-110">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <img id="singleModalImg" class="max-w-full max-h-[80vh] object-contain rounded-lg mx-auto shadow-2xl"
             onerror="this.onerror=null; this.src='https://via.placeholder.com/800x600?text=Imagen+no+disponible';">
        
        <div class="flex justify-between mt-4">
            <button onclick="navigateImage(-1)" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <button onclick="downloadCurrentImage()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                <i class="fas fa-download"></i> Descargar
            </button>
            <button onclick="navigateImage(1)" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loadingOverlay" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100]">
    <div class="bg-white rounded-lg p-8 shadow-2xl flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-700">Cargando...</p>
    </div>
</div>

<script>
let currentImages = [];
let currentModalInfo = {};
let currentAnalysisData = null;
let currentImageIndex = 0;
let isCompactView = false;
let isListView = false;

// FUNCIONES PRINCIPALES
function openAnalysisDetail(analysisData) {
    showLoading();
    currentAnalysisData = analysisData;
    const modal = document.getElementById('analysisDetailModal');
    
    document.getElementById('detail-linea').textContent = analysisData.linea;
    document.getElementById('detail-componente').textContent = analysisData.componente;
    document.getElementById('detail-componente-codigo').textContent = analysisData.componente_codigo;
    document.getElementById('detail-reductor').textContent = analysisData.reductor;
    document.getElementById('detail-fecha').textContent = analysisData.fecha_analisis;
    document.getElementById('detail-orden').textContent = analysisData.numero_orden;
    document.getElementById('detail-actividad').textContent = analysisData.actividad;
    document.getElementById('detail-created').textContent = analysisData.created_at;
    document.getElementById('detail-updated').textContent = analysisData.updated_at;
    
    const editBtn = document.getElementById('detail-edit-btn');
    editBtn.href = `/analisis-componentes/${analysisData.id}/edit`;
    
    const estadoElement = document.getElementById('detail-estado');
    estadoElement.textContent = analysisData.estado;
    
    estadoElement.classList.remove('ok', 'warning', 'danger', 'changed');
    
    if (analysisData.color === 'cell-ok') {
        estadoElement.classList.add('ok');
    } else if (analysisData.color === 'cell-warning') {
        estadoElement.classList.add('warning');
    } else if (analysisData.color === 'cell-danger') {
        estadoElement.classList.add('danger');
    } else if (analysisData.color === 'cell-changed') {
        estadoElement.classList.add('changed');
    }
    
    const imagesBtn = document.getElementById('detail-images-btn');
    const imagesSection = document.getElementById('detail-images-section');
    
    if (analysisData.imagenes && analysisData.imagenes.length > 0) {
        imagesBtn.style.display = 'flex';
        imagesSection.classList.remove('hidden');
        buildDetailImageGrid(analysisData.imagenes);
    } else {
        imagesBtn.style.display = 'none';
        imagesSection.classList.add('hidden');
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function buildDetailImageGrid(imagenes) {
    const imageGrid = document.getElementById('detail-image-grid');
    imageGrid.innerHTML = '';
    
    imagenes.forEach((imagePath, index) => {
        const imageItem = document.createElement('div');
        imageItem.className = 'image-item';
        
        const imageNumber = document.createElement('div');
        imageNumber.className = 'image-number';
        imageNumber.textContent = index + 1;
        
        const img = document.createElement('img');
        img.src = `{{ Storage::url('') }}${imagePath}`;
        img.alt = `Imagen ${index + 1}`;
        img.className = 'grid-image';
        img.onerror = function() {
            this.src = 'https://via.placeholder.com/200x150?text=Imagen+no+disponible';
        };
        img.onclick = () => openSingleImage(imagePath, index);
        
        const imageInfo = document.createElement('div');
        imageInfo.className = 'image-info';
        
        const fileName = imagePath.split('/').pop();
        const fileNameElement = document.createElement('div');
        fileNameElement.className = 'text-xs text-gray-500 truncate mb-1';
        fileNameElement.title = fileName;
        fileNameElement.textContent = fileName.length > 20 ? fileName.substring(0, 20) + '...' : fileName;
        
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'download-image-btn';
        downloadBtn.innerHTML = '<i class="fas fa-download mr-1"></i> Descargar';
        downloadBtn.onclick = (e) => {
            e.stopPropagation();
            downloadSingleImage(imagePath, index);
        };
        
        imageInfo.appendChild(fileNameElement);
        imageInfo.appendChild(downloadBtn);
        
        imageItem.appendChild(imageNumber);
        imageItem.appendChild(img);
        imageItem.appendChild(imageInfo);
        
        imageGrid.appendChild(imageItem);
    });
}

function showDetailImages() {
    if (currentAnalysisData && currentAnalysisData.imagenes && currentAnalysisData.imagenes.length > 0) {
        openAllImages(
            currentAnalysisData.imagenes,
            currentAnalysisData.fecha_analisis,
            currentAnalysisData.numero_orden,
            currentAnalysisData.estado
        );
    }
}

function closeAnalysisDetailModal() {
    document.getElementById('analysisDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentAnalysisData = null;
}

function openAllImages(imagenes, fecha, orden, estado) {
    showLoading();
    const modal = document.getElementById('allImagesModal');
    
    currentImages = Array.isArray(imagenes) ? imagenes.filter(img => img && img.trim() !== '') : [];
    currentModalInfo = { fecha, orden, estado };
    
    document.getElementById('modalDate').textContent = fecha || '';
    document.getElementById('modalOrder').textContent = orden || '';
    document.getElementById('modalStatus').textContent = estado || '';
    
    const imageCount = document.getElementById('imageCount');
    imageCount.textContent = currentImages.length > 0 ? 
        `${currentImages.length} imagen${currentImages.length > 1 ? 'es' : ''}` : '';
    
    buildImageGrid();
    
    const imageGrid = document.getElementById('imageGrid');
    const emptyImages = document.getElementById('emptyImages');
    const downloadAllBtn = document.getElementById('downloadAllImagesBtn');
    
    if (currentImages.length === 0) {
        imageGrid.classList.add('hidden');
        emptyImages.classList.remove('hidden');
        downloadAllBtn.style.display = 'none';
    } else {
        imageGrid.classList.remove('hidden');
        emptyImages.classList.add('hidden');
        downloadAllBtn.style.display = currentImages.length > 1 ? 'flex' : 'none';
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function buildImageGrid() {
    const imageGrid = document.getElementById('imageGrid');
    imageGrid.innerHTML = '';
    
    currentImages.forEach((imagePath, index) => {
        const imageItem = document.createElement('div');
        imageItem.className = 'image-item';
        
        const imageNumber = document.createElement('div');
        imageNumber.className = 'image-number';
        imageNumber.textContent = index + 1;
        
        const img = document.createElement('img');
        img.src = `{{ Storage::url('') }}${imagePath}`;
        img.alt = `Imagen ${index + 1}`;
        img.className = 'grid-image';
        img.onerror = function() {
            this.src = 'https://via.placeholder.com/200x150?text=Imagen+no+disponible';
        };
        img.onclick = () => openSingleImage(imagePath, index);
        
        const imageInfo = document.createElement('div');
        imageInfo.className = 'image-info';
        
        const fileName = imagePath.split('/').pop();
        const fileNameElement = document.createElement('div');
        fileNameElement.className = 'text-xs text-gray-500 truncate mb-1';
        fileNameElement.title = fileName;
        fileNameElement.textContent = fileName.length > 20 ? fileName.substring(0, 20) + '...' : fileName;
        
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'download-image-btn';
        downloadBtn.innerHTML = '<i class="fas fa-download mr-1"></i> Descargar';
        downloadBtn.onclick = (e) => {
            e.stopPropagation();
            downloadSingleImage(imagePath, index);
        };
        
        imageInfo.appendChild(fileNameElement);
        imageInfo.appendChild(downloadBtn);
        
        imageItem.appendChild(imageNumber);
        imageItem.appendChild(img);
        imageItem.appendChild(imageInfo);
        
        imageGrid.appendChild(imageItem);
    });
}

function openSingleImage(imagePath, index) {
    currentImageIndex = index;
    const modal = document.getElementById('singleImageModal');
    const imgElement = document.getElementById('singleModalImg');
    const titleElement = document.getElementById('singleImageTitle');
    
    imgElement.src = `{{ Storage::url('') }}${imagePath}`;
    titleElement.textContent = `Imagen ${index + 1} de ${currentImages.length}`;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function navigateImage(direction) {
    if (currentImages.length === 0) return;
    
    currentImageIndex += direction;
    
    if (currentImageIndex < 0) {
        currentImageIndex = currentImages.length - 1;
    } else if (currentImageIndex >= currentImages.length) {
        currentImageIndex = 0;
    }
    
    openSingleImage(currentImages[currentImageIndex], currentImageIndex);
}

function downloadSingleImage(imagePath, index) {
    showLoading();
    const link = document.createElement('a');
    link.href = `{{ Storage::url('') }}${imagePath}`;
    const fileName = imagePath.split('/').pop() || `imagen-${index + 1}-${Date.now()}.jpg`;
    link.download = fileName;
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(hideLoading, 500);
}

function downloadCurrentImage() {
    if (currentImages.length > 0 && currentImageIndex >= 0) {
        downloadSingleImage(currentImages[currentImageIndex], currentImageIndex);
    }
}

function downloadAllImages() {
    showLoading();
    currentImages.forEach((imagePath, index) => {
        setTimeout(() => {
            const link = document.createElement('a');
            link.href = `{{ Storage::url('') }}${imagePath}`;
            const fileName = imagePath.split('/').pop() || `imagen-${index + 1}-${Date.now()}.jpg`;
            link.download = fileName;
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }, index * 300);
    });
    setTimeout(hideLoading, 500);
}

function closeAllImagesModal() {
    document.getElementById('allImagesModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentImages = [];
}

function closeSingleImageModal() {
    document.getElementById('singleImageModal').classList.add('hidden');
}

// FUNCIONES DE INTERFAZ
function toggleAdvancedFilters() {
    const filters = document.getElementById('advancedFilters');
    const icon = document.getElementById('advancedFiltersIcon');
    filters.classList.toggle('hidden');
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-up');
}

function toggleViewMode() {
    const tableView = document.getElementById('mainTable');
    const listView = document.getElementById('listView');
    const icon = document.getElementById('viewModeIcon');
    
    isListView = !isListView;
    
    if (isListView) {
        tableView.classList.add('hidden');
        listView.classList.remove('hidden');
        icon.classList.remove('fa-table');
        icon.classList.add('fa-list');
    } else {
        tableView.classList.remove('hidden');
        listView.classList.add('hidden');
        icon.classList.remove('fa-list');
        icon.classList.add('fa-table');
    }
}

function toggleCompactView() {
    const cells = document.querySelectorAll('.analysis-cell');
    const icon = document.getElementById('compactViewIcon');
    
    isCompactView = !isCompactView;
    
    cells.forEach(cell => {
        if (isCompactView) {
            cell.classList.add('compact-mode');
            cell.querySelectorAll('button, a, .click-indicator, .empty-cell-icon').forEach(el => {
                if (!el.closest('.badge-new')) {
                    el.style.display = 'none';
                }
            });
        } else {
            cell.classList.remove('compact-mode');
            cell.querySelectorAll('button, a, .click-indicator, .empty-cell-icon').forEach(el => {
                el.style.display = '';
            });
        }
    });
    
    icon.classList.toggle('fa-compress-alt');
    icon.classList.toggle('fa-expand-alt');
}

function printTable() {
    window.print();
}

function printDetail() {
    const printContent = document.getElementById('analysisDetailModal').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

function printImages() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Impresión de Imágenes</title>');
    printWindow.document.write('<style>img { max-width: 100%; margin: 10px; }</style></head><body>');
    
    currentImages.forEach(imagePath => {
        printWindow.document.write(`<img src="{{ Storage::url('') }}${imagePath}" />`);
    });
    
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function confirmDelete(event) {
    event.preventDefault();
    if (confirm('¿Está seguro de eliminar este análisis? Esta acción no se puede deshacer.')) {
        showLoading();
        event.target.submit();
    }
    return false;
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function toggleDetailImages() {
    const grid = document.getElementById('detail-image-grid');
    const toggleBtn = document.getElementById('toggleDetailImages');
    
    if (grid.classList.contains('expanded')) {
        grid.classList.remove('expanded');
        grid.style.maxHeight = '60vh';
        toggleBtn.innerHTML = '<i class="fas fa-expand-alt"></i> Expandir';
    } else {
        grid.classList.add('expanded');
        grid.style.maxHeight = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-compress-alt"></i> Contraer';
    }
}

// EVENT LISTENERS
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (!document.getElementById('singleImageModal').classList.contains('hidden')) {
            closeSingleImageModal();
        } else if (!document.getElementById('allImagesModal').classList.contains('hidden')) {
            closeAllImagesModal();
        } else if (!document.getElementById('analysisDetailModal').classList.contains('hidden')) {
            closeAnalysisDetailModal();
        }
    } else if (e.key === 'ArrowLeft' && !document.getElementById('singleImageModal').classList.contains('hidden')) {
        navigateImage(-1);
    } else if (e.key === 'ArrowRight' && !document.getElementById('singleImageModal').classList.contains('hidden')) {
        navigateImage(1);
    }
});

document.querySelectorAll('#allImagesModal > div, #singleImageModal > div, #analysisDetailModal > div').forEach(modalContent => {
    modalContent.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Ocultar botones de agregar en celdas ya analizadas
    document.querySelectorAll('.cell-ok, .cell-warning, .cell-danger, .cell-changed').forEach(celda => {
        const botonesAgregar = celda.querySelectorAll('a[href*="create-quick"]');
        botonesAgregar.forEach(boton => boton.style.display = 'none');
    });
    
    if (window.location.hash === '#new') {
        const newCells = document.querySelectorAll('.badge-new');
        if (newCells.length > 0) {
            newCells[0].closest('.analysis-cell').classList.add('cell-highlight');
        }
    }
});

document.addEventListener('click', function(e) {
    if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('button') || e.target.closest('a')) {
        e.stopPropagation();
    }
});

// AUTO-SCROLL PARA NUEVOS REGISTROS
window.addEventListener('load', function() {
    const newRecord = document.querySelector('.badge-new');
    if (newRecord) {
        setTimeout(() => {
            newRecord.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 500);
    }
});
</script>
@endsection