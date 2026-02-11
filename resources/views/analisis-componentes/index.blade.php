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
        --changed-blue: #3b82f6;
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
    .cell-changed { background-color: #eff6ff; border-left: 4px solid var(--changed-blue); }
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
    
    .ver-mas-btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 20px;
        background: white;
        border: 2px dashed #cbd5e1;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .ver-mas-btn:hover {
        border-color: #3b82f6;
        color: #2563eb;
        background: #eff6ff;
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
        display: none;
        border: 1px solid #e2e8f0;
    }
    
    .advanced-filters-panel.show {
        display: block;
    }
    
    .advanced-filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    
    /* MODAL PARA VER MÁS LAVADORAS */
    .lineas-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .lineas-modal.show {
        display: flex;
    }
    
    .lineas-modal-content {
        background: white;
        border-radius: 24px;
        max-width: 800px;
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    .lineas-modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .lineas-modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .lineas-modal-header i {
        color: #3b82f6;
    }
    
    .lineas-modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
    }
    
    .lineas-modal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
    }
    
    .close-modal-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .close-modal-btn:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
    
    .stat-trend {
        font-size: 12px;
        color: #94a3b8;
    }
    
    .table-header-container {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
        border-radius: 12px 12px 0 0;
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
        
        .compact-table td, .compact-table th {
            min-width: 100px;
            font-size: 0.7rem !important;
            padding: 6px !important;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .lineas-modal-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .lineas-modal-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="max-w-full mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-pie text-blue-600"></i>
                Análisis de Componentes
            </h1>
            <p class="text-sm text-gray-500 mt-1">Gestión y monitoreo de análisis de componentes por lavadora</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition flex items-center gap-2 shadow-lg shadow-blue-500/20">
                <i class="fas fa-plus-circle"></i>
                Nuevo Análisis
            </a>
        </div>
    </div>

    {{-- FILTROS ESTILO IMAGEN - CON VER MÁS FUNCIONAL --}}
    @php
        $lineas = $lineas ?? collect([]);
        $todosComponentes = $todosComponentes ?? [];
        $componentesPorLinea = $componentesPorLinea ?? [];
        $analisis = $analisis ?? collect([]);
        $reductoresMostrar = $reductoresMostrar ?? [];
        
        // Filtrar solo las lavadoras que queremos mostrar
        $lavadorasPermitidas = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];
        $lineasFiltradas = $lineas->filter(function($linea) use ($lavadorasPermitidas) {
            return in_array($linea->nombre, $lavadorasPermitidas);
        })->values();
        
        // Todas las lavadoras para el modal de "Ver más"
        $todasLasLineas = $lineas->filter(function($linea) use ($lavadorasPermitidas) {
            return !in_array($linea->nombre, $lavadorasPermitidas) && $linea->nombre != null;
        })->values();
    @endphp
    
    @if(isset($lineas) && $lineas->count() > 0)
        <div class="filters-section">
            {{-- LÍNEAS: con las lavadoras específicas --}}
            <div class="lineas-title">
                <i class="fas fa-washing-machine"></i>
                LÍNEAS:
            </div>
            
            <form method="GET" action="{{ route('analisis-componentes.index') }}" id="filterForm">
                <div class="lineas-grid">
                    @foreach($lineasFiltradas as $l)
                        <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}" 
                             onclick="selectLinea('{{ $l->id }}')">
                            <i class="fas fa-washing-machine"></i>
                            {{ $l->nombre }}
                        </div>
                    @endforeach
                    
                    @if($todasLasLineas->count() > 0)
                        <div class="ver-mas-btn" onclick="showAllLineas()">
                            <i class="fas fa-ellipsis-h"></i>
                            Ver más
                        </div>
                    @endif
                    
                    {{-- Select oculto para el valor real --}}
                    <input type="hidden" name="linea_id" id="lineaInput" value="{{ request('linea_id') }}">
                    <input type="hidden" name="componente_id" value="{{ request('componente_id') }}">
                    <input type="hidden" name="reductor" value="{{ request('reductor') }}">
                    <input type="hidden" name="fecha" value="{{ request('fecha') }}">
                    <input type="hidden" name="estado" value="{{ request('estado') }}" id="estadoInput">
                </div>

                <div class="filters-divider"></div>

                {{-- FILTROS AVANZADOS Y ACCIONES --}}
                <div class="filters-row">
                    <div class="filter-link {{ request()->has('componente_id') || request()->has('reductor') || request()->has('fecha') ? 'active' : '' }}" 
                         onclick="toggleAdvancedFilters()">
                        <i class="fas fa-sliders-h"></i>
                        Filtros avanzados
                        <i id="advancedFiltersIcon" class="fas fa-chevron-down ml-1"></i>
                    </div>
                    
                    <button type="submit" class="btn-apply">
                        <i class="fas fa-search"></i>
                        Aplicar filtros
                    </button>
                    
                    <a href="{{ route('analisis-componentes.index') }}" class="btn-clear">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </div>

                {{-- PANEL DE FILTROS AVANZADOS --}}
                <div id="advancedFiltersPanel" class="advanced-filters-panel {{ request()->has('componente_id') || request()->has('reductor') || request()->has('fecha') || request()->has('estado') ? 'show' : '' }}">
                    <div class="advanced-filters-grid">
                        <div class="filter-group">
                            <label><i class="fas fa-cog mr-1"></i> Componente</label>
                            <select name="componente_id" class="filter-select">
                                <option value="">Todos los componentes</option>
                                @foreach(($todosComponentes ?? []) as $key => $nombre)
                                    <option value="{{ $key }}" {{ request('componente_id') == $key ? 'selected' : '' }}>
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-compress-alt mr-1"></i> Reductor</label>
                            <select name="reductor" class="filter-select">
                                <option value="">Todos los reductores</option>
                                @php
                                    $todosReductores = [
                                        'Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5',
                                        'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10',
                                        'Reductor 11', 'Reductor 12', 'Reductor 13', 'Reductor 14', 'Reductor 15',
                                        'Reductor 16', 'Reductor 17', 'Reductor 18', 'Reductor 19', 'Reductor 20',
                                        'Reductor 21', 'Reductor 22', 'Reductor Principal', 'Reductor Loca'
                                    ];
                                @endphp
                                @foreach($todosReductores as $reductor)
                                    <option value="{{ $reductor }}" {{ request('reductor') == $reductor ? 'selected' : '' }}>
                                        {{ $reductor }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="far fa-calendar-alt mr-1"></i> Mes / Año</label>
                            <input type="month" name="fecha" value="{{ request('fecha') }}" class="filter-input">
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
                </div>
            </form>
        </div>
    @endif

    {{-- MODAL PARA VER MÁS LAVADORAS --}}
    <div id="lineasModal" class="lineas-modal">
        <div class="lineas-modal-content">
            <div class="lineas-modal-header">
                <h3>
                    <i class="fas fa-washing-machine"></i>
                    Estas lineas no cuentan con Lavadora
                </h3>
                <button onclick="closeLineasModal()" class="close-modal-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="lineas-modal-body">
                <div class="lineas-modal-grid">
                    @foreach($todasLasLineas as $l)
                        <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}" 
                             onclick="selectLineaFromModal('{{ $l->id }}')">
                            <i class="fas fa-washing-machine"></i>
                            {{ $l->nombre }}
                        </div>
                    @endforeach
                    @if($todasLasLineas->count() == 0)
                        <p class="text-gray-500 col-span-full text-center py-8">
                            No hay más lavadoras disponibles
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- RESÚMENES Y ESTADÍSTICAS --}}
    @php
        $analisisCollection = isset($analisis) ? collect($analisis) : collect([]);

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
                'recientes' => $analisisCollection->filter(function ($item) {
                    return $item->created_at &&
                           $item->created_at->gt(now()->subDays(7));
                })->count(),
            ];
        }
    @endphp

    @if($analisisCollection->count() > 0)
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total análisis</div>
                <div class="stat-value text-blue-700">{{ $estadisticas['total'] ?? 0 }}</div>
                <div class="stat-trend">{{ $estadisticas['recientes'] ?? 0 }} nuevos (7d)</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Buen estado</div>
                <div class="stat-value text-green-600">{{ $estadisticas['buen_estado'] ?? 0 }}</div>
                <div class="stat-trend">Componentes óptimos</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Desgaste</div>
                <div class="stat-value text-yellow-600">{{ $estadisticas['desgaste'] ?? 0 }}</div>
                <div class="stat-trend">Moderado/Severo</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Dañados</div>
                <div class="stat-value text-red-600">{{ ($estadisticas['danado_requiere'] ?? 0) + ($estadisticas['danado_cambiado'] ?? 0) }}</div>
                <div class="stat-trend">{{ $estadisticas['danado_requiere'] ?? 0 }} requieren cambio</div>
            </div>
        </div>
    @endif

    {{-- TABLA PRINCIPAL --}}
    @php
        /* ===============================
        LINEA A MOSTRAR
        =============================== */
        $lineaMostrar = null;
        
        if (request('linea_id') && isset($lineas)) {
            $lineaMostrar = $lineas->firstWhere('id', request('linea_id'));
        } elseif ($analisisCollection->isNotEmpty()) {
            $lineaMostrar = $analisisCollection->first()->linea ?? null;
        }

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
                ->filter(fn($r) => $r == request('reductor'))
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
            
            if (!isset($analisisAgrupados[$reductor][$codigoBase])) {
                $analisisAgrupados[$reductor][$codigoBase] = collect();
            }

            $analisisAgrupados[$reductor][$codigoBase]->push($item);
        }
    @endphp

    @if((isset($lineaMostrar) && $lineas->count() > 0) || ($analisisCollection->count() > 0))
        <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            {{-- ================= ENCABEZADO DE TABLA ================= --}}
                <div class="table-header-container">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">

                        {{-- LADO IZQUIERDO: ICONO + TITULO + FILTROS --}}
                        <div class="flex items-center gap-5">

                            {{-- ICONO --}}
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 md:w-20 md:h-20">
                                    <img src="{{ asset('images/icono-maquina.png') }}" 
                                        alt="Icono de máquina" 
                                        class="w-full h-full object-contain drop-shadow-lg">
                                </div>
                            </div>

                            {{-- TITULO Y FILTROS --}}
                            <div>
                                {{-- TITULO --}}
                                <h2 class="font-bold text-2xl text-white leading-tight">
                                    {{ $lineaMostrar->nombre ?? 'Análisis de Componentes' }}
                                </h2>

                                {{-- FILTROS ACTIVOS --}}
                                <div class="flex flex-wrap gap-4 mt-2 text-blue-100 text-sm">

                                    @if(request('componente_id') && isset($todosComponentes))
                                        <span class="flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full">
                                            <i class="fas fa-cog text-xs"></i>
                                            {{ $todosComponentes[request('componente_id')] ?? request('componente_id') }}
                                        </span>
                                    @endif

                                    @if(request('reductor'))
                                        <span class="flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full">
                                            <i class="fas fa-sliders-h text-xs"></i>
                                            {{ request('reductor') }}
                                        </span>
                                    @endif

                                    @if(request('fecha'))
                                        <span class="flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full">
                                            <i class="far fa-calendar-alt text-xs"></i>
                                            {{ request('fecha') }}
                                        </span>
                                    @endif

                                </div>
                            </div>

                        </div>

                        {{-- LADO DERECHO (ESPACIO DISPONIBLE PARA BOTONES FUTUROS) --}}
                        {{-- Aquí puedes agregar botones como Exportar, Nuevo Análisis, etc --}}
                        
                    </div>
                </div>
            {{-- TABLA COMPACTA --}}
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
                                    @endphp

                                    @foreach($reductoresParaTabla as $r)
                                        @if(isset($analisisAgrupados[$r][$c->id]))
                                            @php
                                                $primerRegistro = $analisisAgrupados[$r][$c->id]->sortByDesc('fecha_analisis')->first();
                                                $estado = $primerRegistro->estado ?? 'Buen estado';
                                                
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
                                            @endphp
                                        @endif
                                    @endforeach

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
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['warning'] > 0)
                                                    <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['danger'] > 0)
                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['changed'] > 0)
                                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
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
                                            $primerRegistro = $analisisAgrupados[$r][$c->id]->sortByDesc('fecha_analisis')->first();
                                            $estado = $primerRegistro->estado ?? 'Buen estado';
                                            
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
                                            $registros = $analisisAgrupados[$r][$c->id] ?? collect();
                                            $registro = $registros->sortByDesc('fecha_analisis')->first();
                                            $totalHistorial = $registros->count();
                                            $hasData = $registros->isNotEmpty() && !empty($registro);
                                            $color = '';
                                            $isNew = false;
                                            $imagenes = [];
                                            
                                            if($hasData){
                                                $estadoActual = $registro->estado ?? 'Buen estado';
                                                
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
                                                            <button onclick="event.stopPropagation(); openAllImages(
                                                                @json($imagenes),
                                                                @json(isset($registro->fecha_analisis) ? $registro->fecha_analisis->format('d/m/Y') : ''),
                                                                @json($registro->numero_orden),
                                                                @json($registro->estado ?? 'Buen estado')
                                                            )"
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
                                                        
                                                        <a href="{{ route('analisis-componentes.create-quick', [
                                                                'linea_id' => $registro->linea_id,
                                                                'componente_codigo' => $c->id,
                                                                'reductor' => $r,
                                                                'fecha' => now()->format('Y-m')
                                                                ]) }}"
                                                                class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 transition text-xs font-medium"
                                                                onclick="event.stopPropagation();">
                                                                    <i class="fas fa-plus"></i>
                                                                    Nuevo Registro
                                                        </a>
                                                        
                                                        @if($totalHistorial > 1)
                                                            <a href="{{ route('analisis-componentes.historial', [
                                                                    'linea_id' => $registro->linea_id,
                                                                    'componente_id' => $c->id,
                                                                    'reductor' => $r
                                                                ]) }}"
                                                            class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-purple-100 text-purple-700 rounded hover:bg-purple-200 transition text-xs font-medium"
                                                            onclick="event.stopPropagation();">
                                                                <i class="fas fa-history"></i>
                                                                Historial ({{ $totalHistorial }})
                                                            </a>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="click-indicator">
                                                        <i class="fas fa-search-plus mr-1"></i> Detalles
                                                    </div>
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
                    <p class="text-sm text-gray-400 mt-2">Selecciona una lavadora para ver sus análisis</p>
                </div>
            @endif
        </div>
    @else
        {{-- VISTA INICIAL --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="text-blue-400 mb-4">
                <i class="fas fa-clipboard-list text-5xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Bienvenido al Sistema de Análisis de Componentes</h3>
            <p class="text-gray-500 mb-6">Selecciona una lavadora para ver los análisis o crea uno nuevo.</p>
            
            <div class="flex flex-wrap justify-center gap-3 mb-8">
                @foreach($lineasFiltradas as $l)
                    <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}" 
                         onclick="selectLinea('{{ $l->id }}')">
                        <i class="fas fa-washing-machine"></i>
                        {{ $l->nombre }}
                    </div>
                @endforeach
            </div>
            
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition shadow-lg hover:shadow-xl">
                <i class="fas fa-plus-circle"></i>
                Comenzar Nuevo Análisis
            </a>
        </div>
    @endif
</div>

{{-- MODALES --}}
<div id="analysisDetailModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4"
     onclick="closeAnalysisDetailModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <div>
                <h3 class="font-bold text-lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span id="detailModalTitle">Detalle del Análisis</span>
                </h3>
            </div>
            <button onclick="closeAnalysisDetailModal()" class="text-white hover:text-yellow-300 text-2xl">
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
                    <h4><i class="far fa-calendar-alt mr-2"></i>Fecha</h4>
                    <p id="detail-fecha" class="font-semibold"></p>
                </div>
                <div class="detail-card">
                    <h4><i class="fas fa-hashtag mr-2"></i>Orden</h4>
                    <p id="detail-orden" class="font-semibold text-lg text-blue-700"></p>
                </div>
                <div class="detail-card">
                    <h4><i class="fas fa-clipboard-check mr-2"></i>Estado</h4>
                    <div id="detail-estado" class="status-badge ok mt-2 inline-flex"></div>
                </div>
            </div>
            
            <div class="detail-card mt-6">
                <h4><i class="fas fa-sticky-note mr-2"></i>Actividad</h4>
                <div class="activity-content">
                    <p id="detail-actividad" class="whitespace-pre-line"></p>
                </div>
            </div>
            
            <div id="detail-images-section" class="detail-images-container hidden">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-images mr-2"></i>Evidencia Fotográfica
                </h4>
                <div id="detail-image-grid" class="image-grid"></div>
            </div>
            
            <div class="detail-actions">
                <a id="detail-edit-btn" href="#" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <button onclick="closeAnalysisDetailModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<div id="allImagesModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4"
     onclick="closeAllImagesModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <h3 class="font-bold text-lg">
                <i class="fas fa-images mr-2"></i>
                <span id="modalTitle">Imágenes del Análisis</span>
            </h3>
            <button onclick="closeAllImagesModal()" class="text-white hover:text-yellow-300 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-80px)]">
            <div id="imageGrid" class="image-grid"></div>
            <div id="emptyImages" class="empty-images hidden">
                <i class="fas fa-image text-4xl mb-4 text-gray-300"></i>
                <p>No hay imágenes disponibles</p>
            </div>
        </div>
    </div>
</div>

<div id="singleImageModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[60] p-4"
     onclick="closeSingleImageModal()">
    <div class="relative max-w-5xl w-full">
        <button onclick="closeSingleImageModal()" class="absolute top-4 right-4 text-white text-2xl">
            <i class="fas fa-times"></i>
        </button>
        <img id="singleModalImg" class="max-w-full max-h-[90vh] object-contain mx-auto">
    </div>
</div>

<div id="loadingOverlay" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100]">
    <div class="bg-white rounded-lg p-8 shadow-2xl">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-700">Cargando...</p>
    </div>
</div>

<script>
let currentImages = [];
let currentAnalysisData = null;
let currentImageIndex = 0;

// FUNCIONES DE FILTROS
function toggleAdvancedFilters() {
    const panel = document.getElementById('advancedFiltersPanel');
    const icon = document.getElementById('advancedFiltersIcon');
    panel.classList.toggle('show');
    
    if (panel.classList.contains('show')) {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function selectLinea(lineaId) {
    document.getElementById('lineaInput').value = lineaId;
    document.getElementById('filterForm').submit();
}

function selectLineaFromModal(lineaId) {
    closeLineasModal();
    selectLinea(lineaId);
}

// FUNCIONES PARA EL MODAL DE VER MÁS
function showAllLineas() {
    const modal = document.getElementById('lineasModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLineasModal() {
    const modal = document.getElementById('lineasModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// FUNCIONES PRINCIPALES
function openAnalysisDetail(analysisData) {
    showLoading();
    currentAnalysisData = analysisData;
    
    document.getElementById('detail-linea').textContent = analysisData.linea;
    document.getElementById('detail-componente').textContent = analysisData.componente;
    document.getElementById('detail-componente-codigo').textContent = analysisData.componente_codigo;
    document.getElementById('detail-reductor').textContent = analysisData.reductor;
    document.getElementById('detail-fecha').textContent = analysisData.fecha_analisis;
    document.getElementById('detail-orden').textContent = analysisData.numero_orden;
    document.getElementById('detail-actividad').textContent = analysisData.actividad;
    
    const estadoElement = document.getElementById('detail-estado');
    estadoElement.textContent = analysisData.estado;
    estadoElement.className = 'status-badge ' + 
        (analysisData.color === 'cell-ok' ? 'ok' : 
         analysisData.color === 'cell-warning' ? 'warning' : 
         analysisData.color === 'cell-danger' ? 'danger' : 'changed');
    
    document.getElementById('detail-edit-btn').href = `/analisis-componentes/${analysisData.id}/edit`;
    
    const imagesSection = document.getElementById('detail-images-section');
    if (analysisData.imagenes && analysisData.imagenes.length > 0) {
        imagesSection.classList.remove('hidden');
        buildDetailImageGrid(analysisData.imagenes);
    } else {
        imagesSection.classList.add('hidden');
    }
    
    document.getElementById('analysisDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function buildDetailImageGrid(imagenes) {
    const grid = document.getElementById('detail-image-grid');
    grid.innerHTML = '';
    
    imagenes.forEach((path, index) => {
        const item = document.createElement('div');
        item.className = 'image-item';
        item.innerHTML = `
            <div class="image-number">${index + 1}</div>
            <img src="{{ Storage::url('') }}${path}" class="grid-image" onclick="openSingleImage('${path}', ${index})">
            <div class="image-info">
                <button class="download-image-btn" onclick="event.stopPropagation(); downloadSingleImage('${path}', ${index})">
                    <i class="fas fa-download mr-1"></i> Descargar
                </button>
            </div>
        `;
        grid.appendChild(item);
    });
}

function openAllImages(imagenes, fecha, orden, estado) {
    showLoading();
    currentImages = Array.isArray(imagenes) ? imagenes : [];
    
    const modal = document.getElementById('allImagesModal');
    const grid = document.getElementById('imageGrid');
    const empty = document.getElementById('emptyImages');
    
    grid.innerHTML = '';
    
    if (currentImages.length === 0) {
        grid.classList.add('hidden');
        empty.classList.remove('hidden');
    } else {
        grid.classList.remove('hidden');
        empty.classList.add('hidden');
        
        currentImages.forEach((path, index) => {
            const item = document.createElement('div');
            item.className = 'image-item';
            item.innerHTML = `
                <div class="image-number">${index + 1}</div>
                <img src="{{ Storage::url('') }}${path}" class="grid-image" onclick="openSingleImage('${path}', ${index})">
                <div class="image-info">
                    <button class="download-image-btn" onclick="event.stopPropagation(); downloadSingleImage('${path}', ${index})">
                        <i class="fas fa-download"></i> Descargar
                    </button>
                </div>
            `;
            grid.appendChild(item);
        });
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function openSingleImage(imagePath, index) {
    currentImageIndex = index;
    const modal = document.getElementById('singleImageModal');
    const img = document.getElementById('singleModalImg');
    img.src = `{{ Storage::url('') }}${imagePath}`;
    modal.classList.remove('hidden');
}

function downloadSingleImage(imagePath, index) {
    const link = document.createElement('a');
    link.href = `{{ Storage::url('') }}${imagePath}`;
    link.download = `imagen-${index + 1}.jpg`;
    link.click();
}

function closeAnalysisDetailModal() {
    document.getElementById('analysisDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function closeAllImagesModal() {
    document.getElementById('allImagesModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function closeSingleImageModal() {
    document.getElementById('singleImageModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

// EVENT LISTENERS
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSingleImageModal();
        closeAllImagesModal();
        closeAnalysisDetailModal();
        closeLineasModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Resaltar nuevo registro si existe en URL
    if (window.location.hash === '#new') {
        const newCell = document.querySelector('.badge-new');
        if (newCell) {
            newCell.closest('.analysis-cell').classList.add('cell-highlight');
        }
    }
});

// Cerrar modal al hacer clic fuera del contenido
document.getElementById('lineasModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLineasModal();
    }
});
</script>
@endsection