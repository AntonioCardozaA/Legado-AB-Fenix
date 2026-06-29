@extends('layouts.app')

@section('title', $analisisTitulo ?? 'Análisis de Pasteurizadoras')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
    $analisisBaseUrl = $analisisBaseUrl ?? '/pasteurizadora/analisis-pasteurizadora';
    $canDeleteAnalysis = $canDeleteAnalysis ?? (auth()->user()?->canDeleteAnalysis() ?? false);
@endphp
<style>
    /* VARIABLES CSS PARA CONSISTENCIA */
    :root {
        --primary-blue: #2563eb;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --changed-blue: #3b82f6;
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
        --soft-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }
    
    .sticky-top { position: sticky; top: 0; z-index: 30; }
    .sticky-left { position: sticky; left: 0; z-index: 20; }
    .sticky-top-left { position: sticky; top: 0; left: 0; z-index: 40; }
    .cell-ok { background-color: #f0f9ff; border-left: 4px solid var(--success-green); }
    .cell-review { background-color: #fff7ed; border-left: 4px solid #f97316; }
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
        min-height: 44px;
        padding: 8px 10px;
        margin-top: 8px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        transition: background 0.3s ease;
        touch-action: manipulation;
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
    
    .status-badge.review {
        background-color: #ffedd5;
        color: #9a3412;
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
    
    .lado-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 16px;
        font-weight: 500;
        font-size: 13px;
    }
    
    .lado-badge.vapor {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .lado-badge.pasillo {
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
    
    /* ESTILOS DE FILTROS */
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
        justify-content: center;
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
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
    }
    
    .linea-item i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-item-icon {
        width: 24px;
        height: 24px;
        margin-right: 10px;
        object-fit: contain;
        flex-shrink: 0;
    }

    .lineas-title-icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
        flex-shrink: 0;
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
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
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

    .stat-action-card {
        min-height: 6rem;
        min-width: 0;
        max-width: 100%;
        text-align: left;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
    }

    .stat-action-card > div {
        min-width: 0;
        gap: 0.75rem;
    }

    .stat-action-card p,
    .stat-action-card h3 {
        overflow-wrap: anywhere;
    }
    
    .btn-apply {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 28px;
        background: #2563eb;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        transition: all 0.2s ease;
        margin-left: auto;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        touch-action: manipulation;
    }
    
    .btn-apply:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 6px 10px -1px rgba(37, 99, 235, 0.3);
    }
    
    .btn-clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 24px;
        background: white;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        cursor: pointer;
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        transition: all 0.2s ease;
        text-decoration: none;
        touch-action: manipulation;
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
        box-shadow: 0 0 0 3px rgba(16, 83, 192, 0.1);
    }
    
    /* Estilos para media queries responsivas */
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

        .filter-link {
            width: 100%;
        }

        .btn-apply,
        .btn-clear {
            margin-left: 0;
            justify-content: center;
            width: 100%;
        }

        .compact-table td, .compact-table th {
            min-width: 100px;
            font-size: 0.7rem !important;
            padding: 6px !important;
        }
    }

    @media (max-width: 480px) {
        .lineas-grid {
            flex-direction: column;
            align-items: stretch;
        }

        .linea-item {
            width: 100%;
            justify-content: center;
        }
    }

    /* ESTILOS PARA SECCION DE TODAS LAS PASTEURIZADORAS */
    .pasteurizadoras-section {
        margin-top: 30px;
    }

    .pasteurizadora-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--soft-shadow);
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
    }

    .pasteurizadora-card-header {
        background: linear-gradient(to right, #f9fafb, #ffffff);
        border-bottom: 1px solid #e5e7eb;
        color: #111827;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .pasteurizadora-card-header h3 {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }

    .pasteurizadora-card-header .badge {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .pasteurizadora-card .table-wrapper {
        border-radius: 0;
        border: none;
        border-top: 1px solid #e2e8f0;
    }

    /* ESTILOS PARA MODALES */
    @keyframes modalIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .animate-modalIn {
        animation: modalIn 0.3s ease-out;
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .pasteur-index-shell {
        max-width: 1280px;
    }

    .pasteur-index-shell > .flex:first-child {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: var(--soft-shadow);
        padding: 20px 24px;
    }

    .pasteur-index-shell .filters-section,
    .pasteur-index-shell .table-wrapper {
        border-radius: 12px;
        border-color: #e5e7eb;
        box-shadow: var(--soft-shadow);
    }

    .pasteur-index-shell .pasteurizadora-card-header img {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 6px;
    }

    /* Grid de imagenes mejorado para el modal monocromatico */
    .image-grid-enhanced {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .image-grid-enhanced .image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
        background: white;
    }

    .image-grid-enhanced .image-item:hover {
        border-color: #4b5563;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
    }

    .image-grid-enhanced .grid-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .image-grid-enhanced .grid-image:hover {
        transform: scale(1.05);
    }

    .image-grid-enhanced .image-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(31, 41, 55, 0.9);
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 4px;
        z-index: 10;
        border: 1px solid #6b7280;
        font-family: monospace;
    }

    .image-grid-enhanced .image-info {
        padding: 8px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }

    .image-grid-enhanced .download-image-btn {
        width: 100%;
        min-height: 44px;
        padding: 8px 10px;
        background: #374151;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
        font-family: monospace;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        touch-action: manipulation;
    }

    .image-grid-enhanced .download-image-btn:hover {
        background: #1f2937;
    }

    @media (max-width: 768px) {
        .image-grid-enhanced {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .image-grid-enhanced .grid-image {
            height: 120px;
        }
    }

    @media (max-width: 480px) {
        .image-grid-enhanced {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="pasteur-index-shell max-w-full mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <a href="{{ route($analisisDashboardRoute ?? 'pasteurizadora.dashboard') }}" class="responsive-action responsive-action--secondary group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <span>{{ $analisisTitulo ?? 'Análisis de Pasteurizadoras' }}</span>
            </h1>
        </div>
    </div>

    {{-- FILTROS ESTILO IMAGEN - CON VER MÁS FUNCIONAL --}}
    @php
        $lineasFiltradas = $lineasFiltradas ?? collect();
        $mostrarTodas = $mostrarTodas ?? true;
        $analisisCollection = isset($analisis) ? collect($analisis) : collect([]);
        $seguimientoPasteurizadora = $seguimientoPasteurizadora ?? [];
        // Cambia esta ruta para usar el icono que quieras en todas las pasteurizadoras.
        $iconoPasteurizadora = 'images/icono_pas.png';
        $tieneIconoPasteurizadora = file_exists(public_path($iconoPasteurizadora));
        $diagramasPasteurizadoraBase = 'images/Diagramas-Pasteurizadoras';
        $configuracionPasteurizadoras = \App\Models\AnalisisPasteurizadora::getPasteurizadoresConfiguracion();
        $nombreLineaSeleccionada = $lineaSeleccionada->nombre ?? null;
        $tipoLineaSeleccionada = $nombreLineaSeleccionada
            ? ($configuracionPasteurizadoras[$nombreLineaSeleccionada]['tipo'] ?? null)
            : null;
        $lineaSlug = $nombreLineaSeleccionada
            ? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombreLineaSeleccionada))
            : null;
        $lineaCompacta = $nombreLineaSeleccionada
            ? strtolower(preg_replace('/[^a-z0-9]+/i', '', $nombreLineaSeleccionada))
            : null;
        $lineaNumeroSeleccionada = $nombreLineaSeleccionada
            ? preg_replace('/\D+/', '', $nombreLineaSeleccionada)
            : null;
        $lineaNumeroSimple = $lineaNumeroSeleccionada !== null
            ? ltrim($lineaNumeroSeleccionada, '0')
            : null;
        $lineaNumeroSimple = $lineaNumeroSimple === '' ? '0' : $lineaNumeroSimple;
        $diagramaPasteurizadoraPath = null;
        $diagramaPasteurizadoraEsReferencia = false;

        $diagramaPasteurizadoraCandidates = array_filter([
            $nombreLineaSeleccionada ? $diagramasPasteurizadoraBase . '/' . $nombreLineaSeleccionada . '.png' : null,
            $nombreLineaSeleccionada ? $diagramasPasteurizadoraBase . '/' . strtolower($nombreLineaSeleccionada) . '.png' : null,
            $lineaSlug ? $diagramasPasteurizadoraBase . '/' . $lineaSlug . '.png' : null,
            $lineaCompacta ? $diagramasPasteurizadoraBase . '/' . $lineaCompacta . '.png' : null,
            $lineaNumeroSeleccionada ? $diagramasPasteurizadoraBase . '/linea' . $lineaNumeroSeleccionada . '.png' : null,
            $lineaNumeroSeleccionada ? $diagramasPasteurizadoraBase . '/linea-' . $lineaNumeroSeleccionada . '.png' : null,
            $lineaNumeroSimple ? $diagramasPasteurizadoraBase . '/linea' . $lineaNumeroSimple . '.png' : null,
            $lineaNumeroSimple ? $diagramasPasteurizadoraBase . '/linea-' . $lineaNumeroSimple . '.png' : null,
            $nombreLineaSeleccionada ? $diagramasPasteurizadoraBase . '/' . $nombreLineaSeleccionada . '.jpg' : null,
            $lineaSlug ? $diagramasPasteurizadoraBase . '/' . $lineaSlug . '.jpg' : null,
            $lineaNumeroSeleccionada ? $diagramasPasteurizadoraBase . '/linea' . $lineaNumeroSeleccionada . '.jpg' : null,
            $lineaNumeroSeleccionada ? $diagramasPasteurizadoraBase . '/linea-' . $lineaNumeroSeleccionada . '.jpg' : null,
            $lineaNumeroSimple ? $diagramasPasteurizadoraBase . '/linea' . $lineaNumeroSimple . '.jpg' : null,
            $lineaNumeroSimple ? $diagramasPasteurizadoraBase . '/linea-' . $lineaNumeroSimple . '.jpg' : null,
            $tipoLineaSeleccionada ? $diagramasPasteurizadoraBase . '/' . $tipoLineaSeleccionada . '.png' : null,
            $tipoLineaSeleccionada ? $diagramasPasteurizadoraBase . '/pasteurizadora-' . $tipoLineaSeleccionada . '.png' : null,
            $tipoLineaSeleccionada ? $diagramasPasteurizadoraBase . '/diagrama-' . $tipoLineaSeleccionada . '.png' : null,
            $diagramasPasteurizadoraBase . '/diagramapas.png',
            $diagramasPasteurizadoraBase . '/diagrama-pasteurizadora.png',
            $diagramasPasteurizadoraBase . '/pasteurizadora.png',
        ]);

        foreach ($diagramaPasteurizadoraCandidates as $candidate) {
            if (file_exists(public_path($candidate))) {
                $diagramaPasteurizadoraPath = $candidate;
                break;
            }
        }

        if (!$diagramaPasteurizadoraPath && $tieneIconoPasteurizadora) {
            $diagramaPasteurizadoraPath = $iconoPasteurizadora;
            $diagramaPasteurizadoraEsReferencia = true;
        }
    @endphp

    @if(isset($lineasFiltradas) && $lineasFiltradas->count() > 0)
    <div class="filters-section">
        {{-- LÍNEAS: con las pasteurizadoras específicas --}}
        <div class="lineas-title">
       
            LÍNEAS DE PASTEURIZADORA:
        </div>
        
        <form method="GET" action="{{ $analisisRoute('index') }}" id="filterForm">
            <div class="lineas-grid">
                <!-- Todas -->
                <a href="{{ $analisisRoute('index', ['linea_id' => 'todas']) }}"
                   class="linea-item {{ $mostrarTodas ? 'active' : '' }}">
                    <i class="fas fa-globe"></i>
                    Todas
                </a>
                
                @foreach($lineasFiltradas as $l)
                    <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}" 
                         onclick="selectLinea('{{ $l->id }}')">
                    
                        {{ $l->nombre }}
                    </div>
                @endforeach
                
                {{-- Select oculto para el valor real --}}
                <input type="hidden" name="linea_id" id="lineaInput" value="{{ request('linea_id') }}">
                <input type="hidden" name="modulo" id="moduloInput" value="{{ request('modulo') }}">
                <input type="hidden" name="estado" value="{{ request('estado') }}" id="estadoInput">
            </div>

            <div class="filters-divider"></div>

            {{-- FILTROS AVANZADOS Y ACCIONES --}}
            <div class="filters-row">
                <div class="filter-link {{ request()->has('modulo') || request()->has('estado') ? 'active' : '' }}" 
                     onclick="toggleAdvancedFilters()">
                    <i class="fas fa-sliders-h"></i>
                    Filtros avanzados
                    <i id="advancedFiltersIcon" class="fas fa-chevron-down ml-1"></i>
                </div>
                
                <button type="submit" class="btn-apply">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
                
                <a href="{{ $analisisRoute('index', ['linea_id' => 'todas']) }}" class="btn-clear">
                    <i class="fas fa-times"></i>
                    Limpiar
                </a>
            </div>

            {{-- PANEL DE FILTROS AVANZADOS --}}
            <div id="advancedFiltersPanel" class="advanced-filters-panel {{ request()->has('modulo') || request()->has('estado') ? 'show' : '' }}">
                <div class="advanced-filters-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-cube mr-1"></i> Módulo</label>
                        <select name="modulo" class="filter-select">
                            <option value="">Todos los módulos</option>
                            @for($i = 1; $i <= 16; $i++)
                                <option value="{{ $i }}" {{ request('modulo') == $i ? 'selected' : '' }}>
                                    Módulo {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-clipboard-check mr-1"></i> Estado</label>
                        <select name="estado" class="filter-select">
                            <option value="">Todos los estados</option>
                            @foreach(\App\Models\AnalisisPasteurizadora::getEstadoOpciones() as $estado => $label)
                                <option value="{{ $estado }}" {{ request('estado') === $estado ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- ESTADÍSTICAS / INDICADORES --}}
    {{-- DIAGRAMA DE PASTEURIZADORA SEGUN LINEA SELECCIONADA --}}
    @if(!$mostrarTodas && isset($lineaSeleccionada) && is_object($lineaSeleccionada))
        <div class="mb-6 p-4 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">
                            <i class="fas fa-diagram-project text-blue-600 mr-2"></i>
                            <span>{{ $lineaSeleccionada->nombre }}</span>
                        </h2>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        @if($tipoLineaSeleccionada)
                            <span class="px-3 py-1 rounded-full bg-slate-100 border border-slate-200">
                                Tipo {{ $tipoLineaSeleccionada }}
                            </span>
                        @endif
                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                            {{ \App\Models\AnalisisPasteurizadora::getModulosPorLinea($lineaSeleccionada->nombre) }} modulos
                        </span>
                    </div>
                </div>
            </div>

            @if($diagramaPasteurizadoraPath)
                <div class="mt-4">
                    <div class="flex justify-center">
                        <img
                            src="{{ asset($diagramaPasteurizadoraPath) }}"
                            alt="Diagrama Pasteurizadora {{ $lineaSeleccionada->nombre }}"
                            class="max-w-full h-auto rounded-lg border border-gray-300 shadow-md"
                        >
                    </div>

                    @if($diagramaPasteurizadoraEsReferencia)
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            No se encontro un diagrama cargado para {{ $lineaSeleccionada->nombre }}.
                            Se muestra una referencia temporal. Puedes agregar la imagen en
                            <span class="font-semibold">{{ $diagramasPasteurizadoraBase }}</span>.
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-4">
                    <div class="rounded-lg border border-dashed border-amber-300 bg-amber-50 px-4 py-5 text-sm text-amber-800">
                        No hay un diagrama disponible para {{ $lineaSeleccionada->nombre }}.
                        Agrega una imagen en <span class="font-semibold">{{ $diagramasPasteurizadoraBase }}</span>
                        para mostrarla aqui automaticamente.
                    </div>
                </div>
            @endif
        </div>
    @elseif($mostrarTodas)
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-center gap-3">
                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                <p class="text-blue-700">
                    Selecciona una pasteurizadora especifica para ver su diagrama
                </p>
            </div>
        </div>
    @endif

    {{-- ESTADISTICAS / INDICADORES --}}
    @if($analisisCollection->count() > 0)
        @php
            $registrosPorEstado = [
                'total' => $analisisCollection,
                'buen_estado' => $analisisCollection->where('estado', \App\Models\AnalisisPasteurizadora::ESTADO_BUENO),
                'requiere_revision' => $analisisCollection->where('estado', \App\Models\AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION),
                'desgaste' => $analisisCollection->whereIn('estado', \App\Models\AnalisisPasteurizadora::ESTADOS_DESGASTE),
                'danado' => $analisisCollection->whereIn('estado', \App\Models\AnalisisPasteurizadora::estadosDanado()),
                'cambiado' => $analisisCollection->where('estado', \App\Models\AnalisisPasteurizadora::ESTADO_CAMBIADO),
            ];

            $estadisticas = [
                'total' => $analisisCollection->count(),
                'buen_estado' => $registrosPorEstado['buen_estado']->count(),
                'requiere_revision' => $registrosPorEstado['requiere_revision']->count(),
                'desgaste' => $registrosPorEstado['desgaste']->count(),
                'danado' => $registrosPorEstado['danado']->count(),
                'cambiado' => $registrosPorEstado['cambiado']->count(),
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
            {{-- TOTAL ANÁLISIS --}}
            <div class="bg-white rounded-xl shadow-sm p-4 border-t-4 border-gray-600 hover:shadow-lg transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total análisis</p>
                        <h3 class="text-2xl font-bold text-gray-700 mt-1">{{ $estadisticas['total'] ?? 0 }}</h3>
                    </div>
                    <div class="bg-gray-100 text-gray-600 p-2 rounded-full"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
            
            {{-- BUEN ESTADO (BOTÓN CLICKEABLE) --}}
            <button onclick="openEstadoModal('buen_estado', 'Buen Estado', {{ json_encode($registrosPorEstado['buen_estado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})" 
                    class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-emerald-600 hover:shadow-lg hover:bg-emerald-50 transition-all text-left w-full cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Buen estado</p>
                        <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ $estadisticas['buen_estado'] ?? 0 }}</h3>
                        <p class="text-xs text-emerald-500 group-hover:text-emerald-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                    </div>
                    <div class="bg-emerald-100 text-emerald-600 p-2 rounded-full group-hover:bg-emerald-200 transition"><i class="fas fa-check-circle"></i></div>
                </div>
            </button>
            
            {{-- REQUIERE REVISIÓN (BOTÓN CLICKEABLE) --}}
            <button onclick="openEstadoModal('requiere_revision', '🔧 Requiere revisión', {{ json_encode($registrosPorEstado['requiere_revision']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                    class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-yellow-500 hover:shadow-lg hover:bg-yellow-50 transition-all text-left w-full cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wide">🔧 Requiere revisión</p>
                        <h3 class="text-2xl font-bold text-yellow-600 mt-1">{{ $estadisticas['requiere_revision'] ?? 0 }}</h3>
                        <p class="text-xs text-yellow-500 group-hover:text-yellow-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                    </div>
                    <div class="bg-yellow-100 text-yellow-600 p-2 rounded-full group-hover:bg-yellow-200 transition"><i class="fas fa-tools"></i></div>
                </div>
            </button>

            {{-- DESGASTE (BOTÓN CLICKEABLE) --}}
            <button onclick="openEstadoModal('desgaste', 'Severo / Moderado', {{ json_encode($registrosPorEstado['desgaste']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                    class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-orange-500 hover:shadow-lg hover:bg-orange-50 transition-all text-left w-full cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Severo / Moderado</p>
                        <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ $estadisticas['desgaste'] ?? 0 }}</h3>
                        <p class="text-xs text-orange-500 group-hover:text-orange-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                    </div>
                    <div class="bg-orange-100 text-orange-600 p-2 rounded-full group-hover:bg-orange-200 transition"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </button>
            
            {{-- DAÑADOS (BOTÓN CLICKEABLE) --}}
            <button onclick="openEstadoModal('danado', 'Dañados', {{ json_encode($registrosPorEstado['danado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                    class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-red-600 hover:shadow-lg hover:bg-red-50 transition-all text-left w-full cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-red-600 uppercase tracking-wide">Dañados</p>
                        <h3 class="text-2xl font-bold text-red-600 mt-1">{{ ($estadisticas['danado'] ?? 0) }}</h3>
                        <p class="text-xs text-red-500 group-hover:text-red-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                    </div>
                    <div class="bg-red-100 text-red-600 p-2 rounded-full group-hover:bg-red-200 transition"><i class="fas fa-times-circle"></i></div>
                </div>
            </button>
            
            {{-- CAMBIADOS (BOTÓN CLICKEABLE) --}}
            <button onclick="openEstadoModal('cambiado', 'Cambiados', {{ json_encode($registrosPorEstado['cambiado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                    class="stat-action-card bg-white rounded-xl shadow-sm p-4 border-t-4 border-sky-600 hover:shadow-lg hover:bg-sky-50 transition-all text-left w-full cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-sky-600 uppercase tracking-wide">Cambiados</p>
                        <h3 class="text-2xl font-bold text-sky-600 mt-1">{{ $estadisticas['cambiado'] ?? 0 }}</h3>
                        <p class="text-xs text-sky-500 group-hover:text-sky-700 mt-1"><i class="fas fa-eye text-xs"></i> Ver detalles</p>
                    </div>
                    <div class="bg-sky-100 text-sky-600 p-2 rounded-full group-hover:bg-sky-200 transition"><i class="fas fa-sync-alt"></i></div>
                </div>
            </button>
        </div>
    @endif

    {{-- SECCIÓN PRINCIPAL - TABLA DE ANÁLISIS --}}
    <div class="pasteurizadoras-section">
        @php
            $lineasToShow = $mostrarTodas ? $lineasFiltradas : collect([$lineaSeleccionada ?? null])->filter();
        @endphp

        @foreach($lineasToShow as $linea)
            @php
                if(!$linea) continue;

                $nombreLinea = $linea->nombre;
                $componentesLinea = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($nombreLinea);
                $totalModulos = \App\Models\AnalisisPasteurizadora::getModulosPorLinea($nombreLinea);
                $modulosLinea = collect(range(1, $totalModulos));

                $analisisLinea = $analisisCollection->filter(fn($item) => $item->linea_id == $linea->id);
                $seguimientoLinea = $seguimientoPasteurizadora[$linea->id] ?? [];
                $resumenLinea = $seguimientoLinea['resumen'] ?? [
                    'total' => 0,
                    'completados' => 0,
                    'pendientes' => 0,
                    'porcentaje' => 0,
                    'completado' => false,
                ];

                $analisisAgrupadosLinea = [];
                foreach ($analisisLinea as $item) {
                    if (!isset($analisisAgrupadosLinea[$item->modulo][$item->componente])) {
                        $analisisAgrupadosLinea[$item->modulo][$item->componente] = collect();
                    }
                    $analisisAgrupadosLinea[$item->modulo][$item->componente]->push($item);
                }
            @endphp

            @if(count($componentesLinea) > 0 && $modulosLinea->count() > 0)
                <div class="pasteurizadora-card">
                    <div class="pasteurizadora-card-header">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 w-full">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12">
                                    <img src="{{ asset('images/icono_pas.png') }}" alt="Icono" class="w-full h-full object-contain">
                                </div>
                                <div>
                                    <h3>{{ $linea->nombre }}</h3>
                                    <div class="badge">{{ $totalModulos }} módulos | {{ count($componentesLinea) }} componentes</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <div class="scroll-indicator">
                            <i class="fas fa-arrows-alt-h mr-1"></i> Desplazate para ver mas
                        </div>
                        <table class="w-full compact-table border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="sticky-left cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                        <div class="reductor-header">
                                            <div class="reductor-name">MÓDULO</div>
                                            <div class="reductor-label">COMPONENTE</div>
                                        </div>
                                    </th>
                                    @foreach($componentesLinea as $codigo => $compData)
                                        <th class="cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                            <div class="component-header">
                                                <div class="component-name">
                                                    {{ $compData['nombre'] }}
                                                </div>
                                                <img
                                                    src="{{ asset('images/componentes-pasteurizadora/' . $codigo . '.png') }}"
                                                    alt="Icono {{ $compData['nombre'] }}"
                                                    class="w-20 h-20 object-contain"
                                                    onerror="this.src='{{ asset('images/icono-pasteurizadora.png') }}'">
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modulosLinea as $moduloNumero)
                                    <tr>
                                        <td class="sticky-left cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm align-top">
                                            <div class="reductor-header">
                                                <div class="reductor-name">Módulo {{ $moduloNumero }}</div>
                                            </div>
                                            @if($seguimientoLinea['modulos'][$moduloNumero]['completado'] ?? false)
                                                <span class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[11px] font-semibold">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Terminado
                                                </span>
                                            @endif
                                        </td>
                                        @foreach($componentesLinea as $codigo => $compData)
                                            @php
                                                $celdaSeguimiento = $seguimientoLinea['celdas'][$moduloNumero][$codigo] ?? null;
                                                $registros = collect($celdaSeguimiento['registros_visibles'] ?? []);
                                                $registro = $registros->sortByDesc(function ($item) {
                                                    return ($item->created_at?->timestamp ?? 0) . '-' . str_pad((string) $item->id, 10, '0', STR_PAD_LEFT);
                                                })->first();
                                                $hasData = $registro !== null;
                                                $totalComponentesComponente = $compData['cantidad'] ?? 0;
                                                $esBrazoTorsion = \App\Models\AnalisisPasteurizadora::esBrazoTorsion($codigo);
                                                $brazoAplicaModulo = !$esBrazoTorsion || $moduloNumero <= \App\Models\AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre);
                                                
                                                $componentesRevisadosAcumulados = $registros
                                                    ->flatMap(function ($item) {
                                                        if (is_array($item->componentes_revisados)) {
                                                            return $item->componentes_revisados;
                                                        }
                                                        if (is_string($item->componentes_revisados)) {
                                                            $decoded = json_decode($item->componentes_revisados, true);
                                                            return is_array($decoded) ? $decoded : [];
                                                        }
                                                        return [];
                                                    })
                                                    ->filter(fn($numeroComponente) => is_numeric($numeroComponente))
                                                    ->map(fn($numeroComponente) => (int) $numeroComponente)
                                                    ->unique()
                                                    ->sort()
                                                    ->values();
                                                $revisadasAcumuladas = $componentesRevisadosAcumulados->count();
                                                $pendientesAcumulados = max(0, $totalComponentesComponente - $revisadasAcumuladas);
                                                $estadoPorNivel = [];
                                                $siguienteRevision = null;

                                                $bgColor = 'cell-empty';
                                                $borderColor = '';
                                                $estadoActual = '';
                                                if ($celdaSeguimiento) {
                                                    $estadoPorNivel = $celdaSeguimiento['estado_por_nivel'];
                                                    $siguienteRevision = $celdaSeguimiento['siguiente_revision'];
                                                }
                                                $procesoCompletado = (bool) ($celdaSeguimiento['completado'] ?? ($hasData && !$siguienteRevision));
                                                $moduloCompletado = (bool) ($seguimientoLinea['modulos'][$moduloNumero]['completado'] ?? false);

                                                if($hasData){
                                                    $estadoActual = $registro->estado ?? 'Buen estado';
                                                    if (\App\Models\AnalisisPasteurizadora::esEstadoCambiado($estadoActual)) {
                                                        $bgColor = 'cell-changed';
                                                        $borderColor = '';
                                                    } elseif (\App\Models\AnalisisPasteurizadora::esEstadoDanado($estadoActual)) {
                                                        $bgColor = 'cell-danger';
                                                        $borderColor = '';
                                                    } elseif (\App\Models\AnalisisPasteurizadora::esEstadoRequiereRevision($estadoActual)) {
                                                        $bgColor = 'bg-orange-50';
                                                        $borderColor = 'border-l-4 border-orange-500';
                                                    } elseif (\App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estadoActual)) {
                                                        $bgColor = 'bg-yellow-50';
                                                        $borderColor = 'border-l-4 border-yellow-500';
                                                    } else {
                                                        $bgColor = 'cell-ok';
                                                        $borderColor = '';
                                                    }

                                                    if ($procesoCompletado) {
                                                        $bgColor = 'cell-ok';
                                                        $borderColor = 'ring-1 ring-green-200';
                                                    }
                                                }
                                            @endphp

                                            @if(!$brazoAplicaModulo)
                                                <td class="border px-3 py-2 align-middle cell-empty text-center text-xs text-gray-400">
                                                    No aplica
                                                </td>
                                            @else
                                            <td class="border px-3 py-2 align-top {{ $bgColor }} {{ $borderColor }} {{ $hasData ? 'analysis-cell' : 'analysis-cell no-data' }}"
                                                @if($hasData)
                                                    onclick="openAnalysisDetail({{ json_encode([
                                                        'id' => $registro->id,
                                                        'linea' => $linea->nombre,
                                                        'modulo' => $moduloNumero,
                                                        'componente' => $compData['nombre'],
                                                        'lado' => $registro->lado,
                                                        'nivel' => $registro->nivel,
                                                        'fecha_analisis' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : $registro->created_at->format('d/m/Y'),
                                                        'numero_orden' => $registro->numero_orden,
                                                        'estado' => $estadoActual,
                                                        'usuario_nombre' => $registro->usuario?->name ?? $registro->responsable ?? 'Usuario no registrado',
                                                        'actividad' => $registro->actividad,
                                                        'imagenes' => $registro->evidencia_fotos ?? [],
                                                        'componentes_revisados' => $componentesRevisadosAcumulados,
                                                        'total_componentes' => $totalComponentesComponente ?: $registro->total_componentes,
                                                        'estado_por_nivel' => $estadoPorNivel,
                                                        'pendientes_por_nivel' => [
                                                            'SUPERIOR' => isset($estadoPorNivel['SUPERIOR']) && !$estadoPorNivel['SUPERIOR']['completado'] 
                                                                ? $estadoPorNivel['SUPERIOR']['lados_pendientes'] 
                                                                : [],
                                                            'INFERIOR' => isset($estadoPorNivel['INFERIOR']) && !$estadoPorNivel['INFERIOR']['completado'] 
                                                                ? $estadoPorNivel['INFERIOR']['lados_pendientes'] 
                                                                : [],
                                                        ],
                                                        'actualizaciones' => $registros
                                                            ->sortByDesc(function ($item) {
                                                                return ($item->created_at?->timestamp ?? 0) . '-' . str_pad((string) $item->id, 10, '0', STR_PAD_LEFT);
                                                            })
                                                            ->map(function ($item) {
                                                                $componentes = [];

                                                                if (is_array($item->componentes_revisados)) {
                                                                    $componentes = $item->componentes_revisados;
                                                                } elseif (is_string($item->componentes_revisados)) {
                                                                    $decoded = json_decode($item->componentes_revisados, true);
                                                                    $componentes = is_array($decoded) ? $decoded : [];
                                                                }

                                                                return [
                                                                    'id' => $item->id,
                                                                    'fecha' => $item->fecha_analisis ? $item->fecha_analisis->format('d/m/Y') : $item->created_at?->format('d/m/Y'),
                                                                    'hora' => $item->created_at?->format('H:i'),
                                                                    'orden' => $item->numero_orden,
                                                                    'estado' => $item->estado,
                                                                    'usuario_nombre' => $item->usuario?->name ?? $item->responsable ?? 'Usuario no registrado',
                                                                    'actividad' => $item->actividad,
                                                                    'lado' => $item->lado,
                                                                    'nivel' => $item->nivel,
                                                                    'componentes_revisados' => collect($componentes)
                                                                        ->filter(fn($numeroComponente) => is_numeric($numeroComponente))
                                                                        ->map(fn($numeroComponente) => (int) $numeroComponente)
                                                                        ->values(),
                                                                ];
                                                            })
                                                            ->values(),
                                                        'edit_url' => $analisisRoute('edit', $registro->id),
                                                        'delete_url' => $canDeleteAnalysis ? $analisisRoute('destroy', $registro->id) : null,
                                                        'historial_url' => $analisisRoute('historial', ['linea_id' => $linea->id, 'modulo' => $moduloNumero, 'componente' => $codigo])
                                                    ]) }})"
                                                @endif>
                                                @if($hasData)
                                                    <div class="space-y-2">
                                                        @if($procesoCompletado)
                                                            <div class="flex items-center justify-between gap-2 rounded-lg border border-green-200 bg-green-100 px-2 py-1.5 text-green-800">
                                                                <span class="inline-flex items-center gap-1 text-xs font-bold">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                    </svg>
                                                                    Proceso terminado
                                                                </span>
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="flex items-center justify-between text-xs text-gray-600">
                                                            <span class="flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                </svg>
                                                                {{ $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : $registro->created_at->format('d/m/Y') }}
                                                            </span>
                                                            <span class="flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                                                </svg>
                                                                #{{ $registro->numero_orden }}
                                                            </span>
                                                        </div>

                                                        @if($registro->lado)
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $registro->lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                                </svg>
                                                                {{ $registro->lado }}
                                                            </span>
                                                        @endif

                                                        <div>
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                                                                @if(\App\Models\AnalisisPasteurizadora::esEstadoBueno($estadoActual)) bg-green-100 text-green-700
                                                                @elseif(\App\Models\AnalisisPasteurizadora::esEstadoRequiereRevision($estadoActual)) bg-yellow-100 text-yellow-700
                                                                @elseif(\App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estadoActual)) bg-orange-100 text-orange-700
                                                                @elseif(\App\Models\AnalisisPasteurizadora::esEstadoDanado($estadoActual)) bg-red-100 text-red-700
                                                                @else bg-blue-100 text-blue-700
                                                                @endif">
                                                                @if(\App\Models\AnalisisPasteurizadora::esEstadoBueno($estadoActual))
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                                    </svg>
                                                                @elseif(\App\Models\AnalisisPasteurizadora::esEstadoRequiereRevision($estadoActual))
                                                                    <i class="fas fa-tools text-[10px]"></i>
                                                                @elseif(\App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estadoActual))
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                                    </svg>
                                                                @elseif(\App\Models\AnalisisPasteurizadora::esEstadoDanado($estadoActual))
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                                    </svg>
                                                                @endif
                                                                {{ $estadoActual }}
                                                            </span>
                                                        </div>

                                                        <div>
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9.004 9.004 0 0112 15c2.21 0 4.234.797 5.879 2.119M15 11a3 3 0 10-6 0 3 3 0 006 0z"/>
                                                                </svg>
                                                                Realizado por: {{ $registro->usuario?->name ?? $registro->responsable ?? 'Usuario no registrado' }}
                                                            </span>
                                                        </div>

                                                        {{-- Resumen simple de progreso --}}
                                                 

                                                        <div class="flex flex-wrap gap-2 pt-1">
                                                            @if(count($registro->evidencia_fotos ?? []) > 0)
                                                                <button onclick="event.stopPropagation(); openAllImages({{ Illuminate\Support\Js::from($registro->evidencia_fotos ?? []) }}, {{ Illuminate\Support\Js::from($registro->numero_orden) }})"
                                                                        class="responsive-action responsive-action--compact responsive-action--secondary">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                    </svg>
                                                                    {{ count($registro->evidencia_fotos) }}
                                                                </button>
                                                            @endif
                                                            @if($procesoCompletado)
                                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                                    </svg>
                                                                    Terminado
                                                                </span>
                                                                    <a href="{{ $analisisRoute('create-quick', [
                                                                    'linea_id' => $linea->id,
                                                                    'modulo' => $moduloNumero,
                                                                    'componente' => $codigo,
                                                                    'lado' => $siguienteRevision['lado'] ?? '',
                                                                    'nivel' => $siguienteRevision['nivel'] ?? ''
                                                                ]) }}"
                                                                   class="create-action create-action--compact create-action--success"
                                                                   onclick="event.stopPropagation();">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                                    </svg>
                                                                    Nuevo análisis
                                                                </a>
                                                            @else
                                                                    <a href="{{ $analisisRoute('create-quick', [
                                                                    'linea_id' => $linea->id,
                                                                    'modulo' => $moduloNumero,
                                                                    'componente' => $codigo,
                                                                    'lado' => $siguienteRevision['lado'] ?? '',
                                                                    'nivel' => $siguienteRevision['nivel'] ?? ''
                                                                ]) }}"
                                                                   class="create-action create-action--compact"
                                                                   onclick="event.stopPropagation();">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                                    </svg>
                                                                    Continuar
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="empty-cell">
                                                        @if($procesoCompletado)
                                                            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-green-800">
                                                                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                                <p class="text-xs font-bold">Proceso terminado</p>
                                                            </div>
                                                        @else
                                                            <div class="empty-cell-icon">
                                                                <i class="fas fa-clipboard"></i>
                                                            </div>
                                                            <p class="text-xs text-gray-400 mb-2">Sin análisis</p>
                                                            <a href="{{ $analisisRoute('create-quick', [
                                                                'linea_id' => $linea->id,
                                                                'modulo' => $moduloNumero,
                                                                'componente' => $codigo,
                                                                'lado' => '',
                                                                'nivel' => ''
                                                            ]) }}"
                                                               class="create-action create-action--compact"
                                                               onclick="event.stopPropagation();">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                                </svg>
                                                                Crear análisis
                                                            </a>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            @endif
                                        @endforeach
                                    </td>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>

{{-- MODAL DE DETALLES --}}
<div id="analysisDetailModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4"
     onclick="if(event.target === this) closeAnalysisDetailModal()">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-gray-600 text-sm"></i>
                    </div>
                    <h3 class="font-medium text-gray-900" id="detailModalTitle">
                        Detalle del Análisis
                    </h3>
                </div>
                <button onclick="closeAnalysisDetailModal()" 
                        class="w-11 h-11 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="p-8 overflow-auto max-h-[calc(90vh-100px)] bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-industry text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Línea</p>
                            <p id="detail-linea" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-cube text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Módulo</p>
                            <p id="detail-modulo" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-cog text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Componente</p>
                            <p id="detail-componente" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                <div id="detail-lado-container" class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-arrows-alt-h text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Lado</p>
                            <p id="detail-lado" class="font-bold text-gray-800 text-lg mt-1"></p>
                            <div id="detail-lado-badge-container" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="far fa-calendar-alt text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Fecha</p>
                            <p id="detail-fecha" class="font-bold text-gray-800 text-lg mt-1 font-mono"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-hashtag text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Orden</p>
                            <p id="detail-orden" class="font-bold text-gray-800 text-lg mt-1 font-mono"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="bg-white rounded-lg p-5 border border-blue-200 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <i class="fas fa-user-check text-blue-600"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700 border-blue-200 border-b-2 uppercase tracking-wider text-sm">Responsable</h4>
                    </div>
                    <div class="flex justify-center">
                        <div id="detail-usuario" class="px-6 py-3 bg-blue-50 text-blue-700 rounded-lg text-sm w-full text-center font-semibold"></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border border-green-200 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-green-100 p-2 rounded-lg">
                            <i class="fas fa-clipboard-check text-green-600"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700 border-green-200 border-b-2 uppercase tracking-wider text-sm">Estado</h4>
                    </div>
                    <div class="flex justify-center">
                        <div id="detail-estado" class="px-6 py-3 bg-green-100 text-green-700 rounded-lg text-sm w-full text-center"></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-gray-200 p-2 rounded-lg">
                            <i class="fas fa-sticky-note text-gray-700"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700 uppercase tracking-wider text-sm font-mono">Actividad</h4>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <p id="detail-actividad" class="text-gray-700 whitespace-pre-line leading-relaxed text-sm"></p>
                    </div>
                </div>
            </div>

            <div id="detail-componentes-section" class="mt-6 hidden"></div>

            <div id="detail-niveles-section" class="mt-6 hidden"></div>

            <div id="detail-actualizaciones-section" class="mt-6 hidden"></div>
            
            <div id="detail-images-section" class="mt-6 hidden">
                <div class=" text-gray-700 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class=" p-2 rounded-lg">
                            <i class="fas fa-images text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg uppercase tracking-wider font-mono">Evidencia Fotográfica</h4>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 border-x-2 border-b-2 border-gray-200">
                    <div id="detail-image-grid" class="image-grid-enhanced"></div>
                </div>
            </div>

            <div class="responsive-actions responsive-actions--end mt-8 pt-4 border-t border-gray-200">
                <a id="detail-edit-btn" 
                href="#" 
                class="responsive-action">
                    <i class="fas fa-edit"></i>
                    Editar Análisis
                </a>

                <a id="detail-historial-btn"
                href="#"
                class="responsive-action responsive-action--secondary hidden">
                    <span id="detail-historial-text">Ver Historial</span>
                </a>

                @if($canDeleteAnalysis)
                    <button id="detail-delete-btn"
                            type="button"
                            onclick="confirmDeleteAnalysis()"
                            class="responsive-action responsive-action--danger">
                        <i class="fas fa-trash"></i>
                        Eliminar
                    </button>
                @endif

                <button onclick="closeAnalysisDetailModal()" 
                        class="responsive-action responsive-action--secondary">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@if($canDeleteAnalysis)
    <form id="delete-analysis-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endif

{{-- MODAL DE ESTADÍSTICAS --}}
<div id="estadoModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closeEstadoModal()">
    <div class="bg-white rounded-xl shadow-xl max-w-5xl w-full max-h-[85vh] overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center" id="estadoModalHeader">
            <h3 class="text-xl font-bold" id="estadoModalTitle">Detalle de registros</h3>
            <button onclick="closeEstadoModal()" class="w-11 h-11 rounded-lg hover:bg-gray-100 flex items-center justify-center transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(85vh-80px)]" id="estadoModalContent">
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Cargando...</p>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE IMÁGENES --}}
<div id="allImagesModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closeAllImagesModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-lg">Galería de Imágenes</h3>
            <button onclick="closeAllImagesModal()" class="w-11 h-11 rounded-lg hover:bg-gray-700 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-80px)]">
            <div id="imageGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
            <div id="emptyImages" class="hidden text-center py-16">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-500">No hay imágenes disponibles</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentAnalysisData = null;
let currentEstadoData = [];
const OPEN_ANALYSIS_DATA = @json($openAnalysisData ?? null);

function openEstadoModal(tipo, nombre, registros) {
    currentEstadoData = registros;
    const modal = document.getElementById('estadoModal');
    const title = document.getElementById('estadoModalTitle');
    const header = document.getElementById('estadoModalHeader');
    const content = document.getElementById('estadoModalContent');

    let bgColor = '', textColor = '', icono = '';
    switch(tipo) {
        case 'total':
            bgColor = 'bg-gray-100';
            textColor = 'text-gray-800';
            icono = '📊';
            title.innerHTML = `${icono} ${nombre} (${registros.length})`;
            break;
        case 'buen_estado':
            bgColor = 'bg-emerald-100';
            textColor = 'text-emerald-800';
            icono = '✅';
            title.innerHTML = `${icono} ${nombre} (${registros.length})`;
            break;
        case 'requiere_revision':
            bgColor = 'bg-yellow-100';
            textColor = 'text-yellow-800';
            icono = '🔧';
            title.innerHTML = `${icono} ${nombre} (${registros.length})`;
            break;
        case 'desgaste':
            bgColor = 'bg-orange-100';
            textColor = 'text-orange-800';
            icono = '⚠️';
            title.innerHTML = `${icono} ${nombre} (${registros.length})`;
            break;
        case 'danado':
            bgColor = 'bg-red-100';
            textColor = 'text-red-800';
            icono = '❌';
            title.innerHTML = `${icono} ${nombre} (${registros.length})`;
            break;
        case 'cambiado':
            bgColor = 'bg-sky-100';
            textColor = 'text-sky-800';
            icono = '🔄';
            title.innerHTML = `${icono} ${nombre} (${registros.length})`;
            break;
        default:
            title.innerHTML = `${nombre} (${registros.length})`;
    }

    header.className = `px-6 py-4 border-b flex justify-between items-center ${bgColor}`;
    title.className = `text-xl font-bold ${textColor}`;

    if (registros.length === 0) {
        content.innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-gray-500">No hay registros en esta categoría</p>
            </div>
        `;
    } else {
        const agrupadosPorLinea = {};
        registros.forEach(reg => {
            if (!agrupadosPorLinea[reg.linea]) {
                agrupadosPorLinea[reg.linea] = [];
            }
            agrupadosPorLinea[reg.linea].push(reg);
        });

        let html = '';
        for (const [linea, items] of Object.entries(agrupadosPorLinea)) {
            html += `
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        <h4 class="font-bold text-lg text-gray-800">${linea}</h4>
                        <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">${items.length} registros</span>
                    </div>
                    <div class="space-y-3">
            `;

            items.forEach(reg => {
                let estadoClass = '';
                let estadoIcon = '';
                let estadoColor = '';
                if (reg.estado === 'Buen estado') {
                    estadoClass = 'border-l-green-500';
                    estadoIcon = '✅';
                    estadoColor = 'bg-green-50';
                } else if (reg.estado === 'Requiere revisión') {
                    estadoClass = 'border-l-yellow-500';
                    estadoIcon = '🔧';
                    estadoColor = 'bg-yellow-50';
                } else if (reg.estado.includes('Desgaste')) {
                    estadoClass = 'border-l-orange-500';
                    estadoIcon = '⚠️';
                    estadoColor = 'bg-orange-50';
                } else if (reg.estado === 'Dañado - Requiere cambio') {
                    estadoClass = 'border-l-red-500';
                    estadoIcon = '❌';
                    estadoColor = 'bg-red-50';
                } else if (reg.estado === 'Cambiado') {
                    estadoClass = 'border-l-blue-500';
                    estadoIcon = '🔄';
                    estadoColor = 'bg-blue-50';
                }

                html += `
                    <div class="${estadoColor} border-l-4 ${estadoClass} p-4 rounded-lg hover:shadow-md transition-all cursor-pointer" onclick="cerrarEstadoModalYVerAnalisis(${reg.id})">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap mb-2">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">Módulo ${reg.modulo}</span>
                                    <span class="font-semibold text-gray-800">${reg.componente}</span>
                                    ${reg.lado ? `<span class="text-xs px-2 py-1 rounded ${reg.lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">${reg.lado === 'VAPOR' ? '💨 Vapor' : '🚶 Pasillo'}</span>` : ''}
                                    <span class="text-xs text-gray-500">📅 ${reg.fecha}</span>
                                </div>
                                <p class="text-sm text-gray-600">${reg.actividad}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-1 rounded-full font-medium
                                      ${reg.estado === 'Buen estado' ? 'bg-green-100 text-green-700' :
                                      (reg.estado === 'Requiere revisión' ? 'bg-yellow-100 text-yellow-700' :
                                      (reg.estado.includes('Desgaste') ? 'bg-orange-100 text-orange-700' :
                                      (reg.estado === 'Dañado - Requiere cambio' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')))}">
                                    ${estadoIcon} ${reg.estado}
                                </span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div></div>`;
        }

        content.innerHTML = html;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeEstadoModal() {
    const modal = document.getElementById('estadoModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function cerrarEstadoModalYVerAnalisis(id) {
    closeEstadoModal();
    const registro = currentEstadoData.find(r => r.id === id);
    if (registro) {
        window.location.href = `{{ url($analisisBaseUrl) }}/${id}`;
    }
}

function openAnalysisDetail(data) {
    currentAnalysisData = data;

    document.getElementById('detail-linea').textContent = data.linea || 'N/A';
    document.getElementById('detail-modulo').textContent = data.modulo ? `Modulo ${data.modulo}` : 'N/A';
    document.getElementById('detail-componente').textContent = data.componente || 'N/A';
    document.getElementById('detail-fecha').textContent = data.fecha_analisis || 'N/A';
    document.getElementById('detail-orden').textContent = data.numero_orden || 'N/A';
    document.getElementById('detail-actividad').textContent = data.actividad || 'No especificada';
    document.getElementById('detail-usuario').textContent = `Realizado por: ${data.usuario_nombre || 'Usuario no registrado'}`;

    const ladoContainer = document.getElementById('detail-lado-container');
    const ladoElement = document.getElementById('detail-lado');
    const ladoBadgeContainer = document.getElementById('detail-lado-badge-container');

    if (data.lado) {
        ladoContainer.classList.remove('hidden');
        ladoElement.textContent = data.lado === 'VAPOR' ? 'Lado Vapor' : 'Lado Pasillo';
        ladoBadgeContainer.innerHTML = `
            <span class="lado-badge ${data.lado === 'VAPOR' ? 'vapor' : 'pasillo'}">
                <i class="fas ${data.lado === 'VAPOR' ? 'fa-wind' : 'fa-walking'}"></i>
                ${data.lado === 'VAPOR' ? 'Vapor' : 'Pasillo'}
            </span>
        `;
    } else {
        ladoContainer.classList.add('hidden');
        ladoElement.textContent = '';
        ladoBadgeContainer.innerHTML = '';
    }

    const estadoElement = document.getElementById('detail-estado');
    let bgClass = 'bg-gray-800';
    if (data.estado === 'Buen estado') {
        bgClass = 'bg-green-800';
    } else if (data.estado === 'Requiere revisión') {
        bgClass = 'bg-yellow-700';
    } else if ((data.estado || '').includes('Desgaste')) {
        bgClass = 'bg-orange-700';
    } else if (data.estado === 'Danado - Requiere cambio' || data.estado === 'Dañado - Requiere cambio') {
        bgClass = 'bg-red-800';
    } else if (data.estado === 'Cambiado') {
        bgClass = 'bg-blue-800';
    }

    estadoElement.className = `px-6 py-3 ${bgClass} text-white rounded-lg font-mono text-sm tracking-wider w-full text-center`;
    estadoElement.textContent = data.estado || 'N/A';

    document.getElementById('detail-edit-btn').href = data.edit_url || '#';
    const historialBtn = document.getElementById('detail-historial-btn');
    const historialText = document.getElementById('detail-historial-text');
    historialBtn.classList.remove('hidden');
    historialBtn.href = data.historial_url || '#';
    historialText.innerHTML = '<i class="fas fa-history mr-2"></i>Ver Historial';

    renderComponentesRevisados(data);
    renderEstadoPorNivel(data);
    renderActualizaciones(data);
    renderDetailImages(data.imagenes || []);

    const modal = document.getElementById('analysisDetailModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function confirmDeleteAnalysis() {
    if (!currentAnalysisData || !currentAnalysisData.delete_url) {
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: 'Eliminar analisis',
        text: 'Esta accion es irreversible y eliminara el registro seleccionado.',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then(function(result) {
        if (result.isConfirmed) {
            const form = document.getElementById('delete-analysis-form');
            form.action = currentAnalysisData.delete_url;
            form.submit();
        }
    });
}

function renderComponentesRevisados(data) {
    const section = document.getElementById('detail-componentes-section');
    const componentes = Array.isArray(data.componentes_revisados) ? data.componentes_revisados : [];

    if (componentes.length === 0) {
        section.classList.add('hidden');
        section.innerHTML = '';
        return;
    }

    section.classList.remove('hidden');
    section.innerHTML = `
        <div class="bg-white rounded-lg p-5 border border-indigo-200 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-100 p-2 rounded-lg">
                        <i class="fas fa-check-circle text-indigo-600"></i>
                    </div>
                    <h4 class="font-semibold text-gray-700 border-indigo-200 border-b-2 uppercase tracking-wider text-sm">Componentes revisados</h4>
                </div>
                <span class="text-sm font-bold text-indigo-700">${componentes.length} de ${data.total_componentes || componentes.length}</span>
            </div>
            <div class="flex flex-wrap gap-2">
                ${componentes.map(num => `
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-semibold border border-indigo-100">
                        <i class="fas fa-check text-xs"></i>
                        #${num}
                    </span>
                `).join('')}
            </div>
        </div>
    `;
}

function renderEstadoPorNivel(data) {
    const section = document.getElementById('detail-niveles-section');

    if (!data.estado_por_nivel) {
        section.classList.add('hidden');
        section.innerHTML = '';
        return;
    }

    const nivelesOrden = ['SUPERIOR', 'INFERIOR'];
    section.classList.remove('hidden');
    section.innerHTML = `
        <div class="bg-white rounded-lg p-5 border border-purple-200 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-purple-100 p-2 rounded-lg">
                    <i class="fas fa-layer-group text-purple-600"></i>
                </div>
                <h4 class="font-semibold text-gray-700 border-purple-200 border-b-2 uppercase tracking-wider text-sm">Estado de revision por nivel</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${nivelesOrden.map((nivel) => {
                    const info = data.estado_por_nivel[nivel] || { completado: false, lados_pendientes: [] };
                    const ladosPendientes = info.lados_pendientes || [];
                    const completado = Boolean(info.completado);

                    return `
                        <div class="rounded-lg border p-4 ${completado ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50'}">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="text-xs font-semibold ${completado ? 'text-green-700' : 'text-amber-700'} uppercase tracking-wider">
                                    <i class="fas ${nivel === 'SUPERIOR' ? 'fa-arrow-up' : 'fa-arrow-down'} mr-1"></i>
                                    Nivel ${nivel === 'SUPERIOR' ? 'Superior' : 'Inferior'}
                                </span>
                                <span class="px-2 py-1 rounded text-xs font-semibold ${completado ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}">
                                    ${completado ? 'Completado' : 'Pendiente'}
                                </span>
                            </div>
                            <p class="text-sm text-gray-700">
                                ${completado
                                    ? 'Ambos lados ya fueron revisados.'
                                    : ladosPendientes.length > 0
                                        ? `Falta revisar: ${ladosPendientes.map((lado) => lado === 'VAPOR' ? 'Vapor' : 'Pasillo').join(', ')}`
                                        : 'Pendiente de revision'}
                            </p>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

function renderActualizaciones(data) {
    const section = document.getElementById('detail-actualizaciones-section');
    const actualizaciones = Array.isArray(data.actualizaciones) ? data.actualizaciones : [];

    if (actualizaciones.length === 0) {
        section.classList.add('hidden');
        section.innerHTML = '';
        return;
    }

    section.classList.remove('hidden');
    section.innerHTML = `
        <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-slate-200 p-2 rounded-lg">
                        <i class="fas fa-clock text-slate-700"></i>
                    </div>
                    <h4 class="font-semibold text-gray-700 border-slate-200 border-b-2 uppercase tracking-wider text-sm">Historial de actualizaciones</h4>
                </div>
                <span class="text-sm font-bold text-slate-700">${actualizaciones.length}</span>
            </div>
            <div class="space-y-3">
                ${actualizaciones.map((item, index) => {
                    const estadoClass = getEstadoPillClass(item.estado || '');
                    const ladoClass = item.lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700';
                    const nivelNombre = item.nivel === 'SUPERIOR' ? 'Superior' : (item.nivel === 'INFERIOR' ? 'Inferior' : '');
                    const ladoNombre = item.lado === 'VAPOR' ? 'Vapor' : (item.lado === 'PASILLO' ? 'Pasillo' : '');
                    const componentes = Array.isArray(item.componentes_revisados) ? item.componentes_revisados : [];

                    return `
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-gray-200 text-gray-700 font-semibold text-xs">${index === 0 ? 'Ultimo registro' : 'Registro #' + (actualizaciones.length - index)}</span>
                                    <span class="text-gray-700 font-medium">${item.fecha || ''} ${item.hora || ''}</span>
                                    ${item.orden ? `<span class="text-gray-500 text-xs">Orden #${item.orden}</span>` : ''}
                                    <span class="text-gray-600 text-xs font-semibold">Realizado por: ${item.usuario_nombre || 'Usuario no registrado'}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    ${nivelNombre ? `<span class="text-xs px-2 py-1 rounded bg-purple-100 text-purple-700">${nivelNombre}</span>` : ''}
                                    ${ladoNombre ? `<span class="text-xs px-2 py-1 rounded ${ladoClass}">${ladoNombre}</span>` : ''}
                                    <span class="text-xs px-2 py-1 rounded ${estadoClass}">${item.estado || 'N/A'}</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-700 whitespace-pre-line">${item.actividad || 'Sin actividad registrada.'}</p>
                            ${componentes.length > 0 ? `
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="text-xs text-gray-500">Componentes revisados:</span>
                                    ${componentes.map((numeroComponente) => `<span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-xs font-medium">#${numeroComponente}</span>`).join('')}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

function getEstadoPillClass(estado) {
    if (estado === 'Buen estado') return 'bg-green-100 text-green-700';
    if (estado === 'Requiere revisión') return 'bg-yellow-100 text-yellow-700';
    if (estado.includes('Desgaste')) return 'bg-orange-100 text-orange-700';
    if (estado === 'Danado - Requiere cambio' || estado === 'Dañado - Requiere cambio') return 'bg-red-100 text-red-700';
    if (estado === 'Cambiado') return 'bg-blue-100 text-blue-700';
    return 'bg-gray-100 text-gray-700';
}

function renderDetailImages(imagenes) {
    const imagesSection = document.getElementById('detail-images-section');
    const grid = document.getElementById('detail-image-grid');
    const normalizedImages = normalizeEvidenceImages(imagenes);

    grid.innerHTML = '';

    if (normalizedImages.length === 0) {
        imagesSection.classList.add('hidden');
        return;
    }

    imagesSection.classList.remove('hidden');
    normalizedImages.forEach((path, index) => {
        const item = document.createElement('div');
        item.className = 'image-item';
        const safePath = String(path).replace(/'/g, "\\'");
        item.innerHTML = `
            <div class="image-number">#${index + 1}</div>
            <img src="${resolveEvidenceImageUrl(path)}" class="grid-image" onclick="openSingleImage('${safePath}')" alt="Evidencia ${index + 1}">
            <div class="image-info">
                <button class="download-image-btn" onclick="event.stopPropagation(); downloadImage('${safePath}', ${index + 1})">
                    <i class="fas fa-download"></i>
                    Descargar
                </button>
            </div>
        `;
        grid.appendChild(item);
    });
}

function normalizeEvidenceImages(imagenes) {
    if (!imagenes) return [];

    if (typeof imagenes === 'string') {
        const valor = imagenes.trim();
        if (!valor || valor === 'null' || valor === '[]') return [];

        if ((valor.startsWith('[') && valor.endsWith(']')) || (valor.startsWith('{') && valor.endsWith('}')) || (valor.startsWith('"') && valor.endsWith('"'))) {
            try {
                return normalizeEvidenceImages(JSON.parse(valor));
            } catch (error) {
                return [valor];
            }
        }

        return [valor];
    }

    if (typeof imagenes === 'object' && !Array.isArray(imagenes)) {
        return normalizeEvidenceImages(Object.values(imagenes));
    }

    if (!Array.isArray(imagenes)) return [];

    return imagenes
        .flatMap((item) => normalizeEvidenceImages(item))
        .map((item) => String(item).trim().replace(/\\/g, '/'))
        .filter((item) => item.length > 0);
}

function resolveEvidenceImageUrl(path) {
    const rawPath = String(path || '').trim().replace(/\\/g, '/');
    if (/^https?:\/\//i.test(rawPath)) return rawPath;

    let cleanPath = rawPath
        .replace(/^\/+/, '')
        .replace(/^public\//, '')
        .replace(/^app\/public\//, '')
        .replace(/^storage\/app\/public\//, '')
        .replace(/^public\/storage\//, '')
        .replace(/^storage\//, '');

    return `/storage/${cleanPath}`;
}

function closeAnalysisDetailModal() {
    document.getElementById('analysisDetailModal').classList.add('hidden');
    document.getElementById('analysisDetailModal').classList.remove('flex');
    document.body.style.overflow = '';
}

let currentImages = [];

function openAllImages(imagenes, orden) {
    currentImages = normalizeEvidenceImages(imagenes);
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
            item.className = 'relative group cursor-pointer';
            const safePath = String(path).replace(/'/g, "\\'");
            item.innerHTML = `
                <img src="${resolveEvidenceImageUrl(path)}" class="w-full h-40 object-cover rounded-lg border-2 border-gray-200 hover:border-blue-500 transition" onclick="openSingleImage('${safePath}')">
                <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">${index + 1}</div>
                <button onclick="event.stopPropagation(); downloadImage('${safePath}', ${index + 1})" class="absolute top-2 right-2 flex min-h-11 min-w-11 items-center justify-center rounded-lg bg-blue-600 p-2.5 text-white transition sm:opacity-0 sm:group-hover:opacity-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
            `;
            grid.appendChild(item);
        });
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeAllImagesModal() {
    document.getElementById('allImagesModal').classList.add('hidden');
    document.getElementById('allImagesModal').classList.remove('flex');
    document.body.style.overflow = '';
}

function openSingleImage(path) {
    window.open(resolveEvidenceImageUrl(path), '_blank');
}

function downloadImage(path, index) {
    const link = document.createElement('a');
    link.href = resolveEvidenceImageUrl(path);
    link.download = `imagen-${index}.jpg`;
    link.click();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAnalysisDetailModal();
        closeAllImagesModal();
        closeEstadoModal();
    }
});

function selectLinea(lineaId) {
    document.getElementById('lineaInput').value = lineaId;
    document.getElementById('filterForm').submit();
}

function toggleAdvancedFilters() {
    const panel = document.getElementById('advancedFiltersPanel');
    const icon = document.getElementById('advancedFiltersIcon');
    
    panel.classList.toggle('show');
    icon.style.transform = panel.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
}

document.addEventListener('DOMContentLoaded', function() {
    if (OPEN_ANALYSIS_DATA) {
        openAnalysisDetail(OPEN_ANALYSIS_DATA);
    }
});
</script>
@endsection
