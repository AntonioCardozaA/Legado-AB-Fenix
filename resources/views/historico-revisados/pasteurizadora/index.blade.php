@extends('layouts.app')

@section('title', 'Historico de Revisados - Pasteurizadora')

@section('content')
<style>
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --info-blue: #3b82f6;
        --light-gray: #f3f4f6;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
        --slate-900: #0f172a;
        --slate-800: #1e293b;
    }

    .historico-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    .lineas-section,
    .grafica-section,
    .resumen-card,
    .componentes-table {
        background: white;
        border: 1px solid var(--medium-gray);
        box-shadow: 0 4px 6px rgba(15, 23, 42, 0.05);
    }

    .lineas-section {
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .linea-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-width: 110px;
        padding: 10px 24px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 15px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .linea-btn i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-2px);
    }

    .linea-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
    }

    .linea-btn.active i {
        color: white;
    }

    .resumen-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .resumen-card {
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .resumen-icono {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .resumen-icono.total { background: #e2e8f0; color: #475569; }
    .resumen-icono.revisado { background: #dbeafe; color: #2563eb; }
    .resumen-icono.porcentaje { background: #d1fae5; color: #059669; }

    .resumen-info h4 {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 4px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .resumen-info .valor {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }

    .componentes-table {
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .table-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
    }

    .table-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 22px;
        padding: 20px;
    }

    .linea-group-title {
        grid-column: 1 / -1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 18px 22px;
    }

    .linea-group-main {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .linea-group-name {
        font-size: 18px;
        font-weight: 800;
        color: #1f2937;
    }

    .linea-group-meta {
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
    }

    .linea-group-progress {
        min-width: 220px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .modulo-summary-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 22px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 18px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07);
    }

    .modulo-summary-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .modulo-summary-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--slate-900);
        margin: 0;
    }

    .modulo-summary-subtitle {
        font-size: 13px;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modulo-summary-badge {
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .modulo-stats,
    .modulo-side-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .modulo-side-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .modulo-stat {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px;
        text-align: center;
    }

    .modulo-stat-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }

    .modulo-stat-value {
        font-size: 18px;
        font-weight: 800;
        color: var(--slate-900);
    }

    .modulo-side-pill {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .modulo-side-pill.pasillo {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .modulo-side-pill-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #1e3a8a;
    }

    .modulo-side-pill.pasillo .modulo-side-pill-label {
        color: #334155;
    }

    .modulo-side-pill-value {
        font-size: 13px;
        font-weight: 700;
        color: var(--slate-900);
    }

    .progress-container {
        width: 100%;
        background: #e2e8f0;
        border-radius: 10px;
        height: 24px;
        position: relative;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        font-size: 12px;
        font-weight: 600;
        color: white;
        transition: width 0.5s ease;
    }

    .progress-bar.bg-success { background: linear-gradient(90deg, #10b981, #059669) !important; }
    .progress-bar.bg-info { background: linear-gradient(90deg, #3b82f6, #2563eb) !important; }
    .progress-bar.bg-warning { background: linear-gradient(90deg, #f59e0b, #d97706) !important; }
    .progress-bar.bg-danger { background: linear-gradient(90deg, #ef4444, #dc2626) !important; }

    .progress-label {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #1e293b;
        font-size: 12px;
        font-weight: 700;
        z-index: 1;
    }

    .modulo-summary-footer {
        display: flex;
        justify-content: flex-end;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
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
        width: min(1120px, 100%);
        max-height: calc(100vh - 40px);
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.28);
    }

    .modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #eef2ff);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-subtitle {
        display: block;
        margin-top: 4px;
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(100vh - 130px);
    }

    .modal-close {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .modal-close:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    .modal-module-overview {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        color: #0f172a;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 22px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
    }

    .modal-overview-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px;
    }

    .modal-overview-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 6px;
    }

    .modal-overview-value {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
    }

    .modal-levels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 18px;
    }

    .modal-level-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #dbe3ef;
        border-radius: 22px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        color: #0f172a;
    }

    .modal-level-card.nivel-inferior {
        background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
    }

    .modal-level-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 26px rgba(15, 23, 42, 0.22);
    }

    .modal-level-card:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.45);
        outline-offset: 3px;
    }

    .modal-level-card.is-selected {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16), 0 16px 26px rgba(15, 23, 42, 0.22);
    }

    .modal-level-header,
    .modal-side-header,
    .modal-level-component-head,
    .grafica-linea-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .modal-level-title {
        font-size: 28px;
        font-weight: 800;
        line-height: 1.1;
        margin: 0;
        color: #0f172a;
    }

    .modal-level-subtitle {
        display: block;
        margin-top: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
    }

    .modal-level-badge {
        background: #dbeafe;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        color: #1d4ed8;
    }

    .modal-level-section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
    }

    .modal-level-progress-meta,
    .modal-level-component-progress-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
    }

    .modal-level-card .progress-container {
        background: #e2e8f0;
    }

    .modal-level-card .progress-label {
        color: #1e293b;
    }

    .modal-level-sides,
    .modal-side-components {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .modal-side-block,
    .modal-level-component {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .modal-level-component {
        background: #f8fafc;
        border-radius: 16px;
        padding: 12px;
        gap: 10px;
    }

    .modal-side-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .modal-side-meta {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }

    .componente-nombre {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
        color: inherit;
        min-width: 0;
    }

    .componente-nombre span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .componente-imagen {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbe3ef;
    }

    .componente-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 4px;
    }

    .cantidad-badge {
        background: #e2e8f0;
        color: #334155;
        padding: 5px 10px;
        border-radius: 999px;
        font-weight: 700;
        font-size: 11px;
        white-space: nowrap;
    }

    .grafica-section {
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .grafica-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .grafica-subtitle {
        font-size: 14px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 22px;
    }

    .grafica-lineas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
        gap: 20px;
    }

    .grafica-linea-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 20px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .grafica-linea-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--slate-900);
    }

    .grafica-linea-summary {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
    }

    .grafica-pie-layout {
        display: grid;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        gap: 20px;
        align-items: center;
    }

    .grafica-pie-panel {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 18px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .grafica-pie-wrapper {
        width: 100%;
        max-width: 320px;
        aspect-ratio: 1;
        position: relative;
    }

    .grafica-pie-canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .grafica-pie-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        text-align: center;
        padding: 20px;
    }

    .grafica-pie-center-value {
        font-size: 34px;
        line-height: 1;
        font-weight: 800;
        color: var(--slate-900);
    }

    .grafica-pie-center-label {
        margin-top: 6px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
    }

    .grafica-legend {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .grafica-legend-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--slate-900);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .grafica-legend-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .grafica-legend-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .grafica-legend-name {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 800;
        color: var(--slate-900);
    }

    .grafica-color-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        flex-shrink: 0;
    }

    .grafica-legend-value {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        white-space: nowrap;
    }

    .grafica-legend-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }

    .acciones {
        display: flex;
        gap: 16px;
        margin-top: 24px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        color: #64748b;
        padding: 48px 16px;
    }

    @media (max-width: 768px) {
        .historico-container {
            padding: 16px;
        }

        .modulos-grid,
        .modulo-stats,
        .modulo-side-summary,
        .modal-levels-grid,
        .grafica-lineas-grid,
        .grafica-pie-layout {
            grid-template-columns: 1fr;
        }

        .linea-group-title,
        .modulo-summary-header,
        .modal-level-header,
        .modal-side-header,
        .modal-level-component-head,
        .grafica-linea-header,
        .grafica-legend-head {
            flex-direction: column;
            align-items: stretch;
        }

        .linea-group-progress {
            min-width: 0;
        }

        .modal-content {
            width: 100%;
            max-height: calc(100vh - 20px);
        }

        .modal-body {
            padding: 18px;
            max-height: calc(100vh - 110px);
        }

        .grafica-pie-panel {
            padding: 14px;
        }
    }
</style>

<div class="historico-container">
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Historico de Revisados - Pasteurizadora
            </h1>
        </div>
    </div>

    <div class="lineas-section">
        <div class="lineas-title">Lineas de pasteurizadora</div>
        <div class="lineas-grid">
            @foreach($lineas as $linea)
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.historico-revisados', ['linea_id' => $linea->id]) }}"
                   class="linea-btn {{ !$mostrarTodas && isset($lineaSeleccionada) && $lineaSeleccionada->id == $linea->id ? 'active' : '' }}">
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    @if(isset($estadisticas['resumen']))
        <div class="resumen-grid">
            <div class="resumen-card">
                <div class="resumen-icono revisado">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="resumen-info">
                    <h4>Revisados</h4>
                    <div class="valor">{{ $estadisticas['resumen']['total_revisado'] }}</div>
                </div>
            </div>
            <div class="resumen-card">
                <div class="resumen-icono porcentaje">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="resumen-info">
                    <h4>Progreso General</h4>
                    <div class="valor">{{ $estadisticas['resumen']['porcentaje_general'] }}%</div>
                </div>
            </div>
        </div>
    @endif

    <div class="componentes-table">
        <div class="table-header">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                Analisis de {{ isset($mostrarTodas) && $mostrarTodas ? 'todas las pasteurizadoras' : ($lineaSeleccionada->nombre ?? 'la linea seleccionada') }}
            </h3>
        </div>

        <div class="modulos-grid">
            @forelse($modulosHistorico as $lineaHistorico)
                @php
                    $lineaTotal = $lineaHistorico['totales']['total'] ?? 0;
                    $lineaRevisado = $lineaHistorico['totales']['revisado'] ?? 0;
                    $lineaPorcentaje = $lineaTotal > 0 ? round(($lineaRevisado / $lineaTotal) * 100) : 0;
                    $lineaColor = $lineaPorcentaje >= 80 ? 'success' : ($lineaPorcentaje >= 50 ? 'info' : ($lineaPorcentaje >= 20 ? 'warning' : 'danger'));
                @endphp

                <div class="linea-group-title">
                    <div class="linea-group-main">
                        <span class="linea-group-name">Linea {{ $lineaHistorico['linea_nombre'] }}</span>
                        <span class="linea-group-meta">{{ count($lineaHistorico['modulos']) }} modulos disponibles con niveles y lados separados</span>
                    </div>
                    <div class="linea-group-progress">
                        <div class="progress-container">
                            <span class="progress-label">{{ $lineaPorcentaje }}%</span>
                            <div class="progress-bar bg-{{ $lineaColor }}" style="width: {{ $lineaPorcentaje }}%;"></div>
                        </div>
                        <span class="linea-group-meta">{{ $lineaRevisado }}/{{ $lineaTotal }} revisados en la linea</span>
                    </div>
                </div>

                @foreach($lineaHistorico['modulos'] as $moduloData)
                    <div class="modulo-summary-card">
                        <div class="modulo-summary-header">
                            <div>
                                <h4 class="modulo-summary-title">Modulo {{ $moduloData['numero'] }}</h4>
                                <div class="modulo-summary-subtitle">{{ $lineaHistorico['linea_nombre'] }} · niveles y lados independientes</div>
                            </div>
                            <span class="modulo-summary-badge">{{ count($moduloData['niveles']) }} niveles / {{ count($moduloData['lados']) }} lados</span>
                        </div>

                        <div class="modulo-stats">
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Total</span>
                                <span class="modulo-stat-value">{{ $moduloData['total'] }}</span>
                            </div>
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Revisado</span>
                                <span class="modulo-stat-value">{{ $moduloData['revisado'] }}</span>
                            </div>
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Avance</span>
                                <span class="modulo-stat-value">{{ $moduloData['porcentaje'] }}%</span>
                            </div>
                        </div>

                        <div class="modulo-side-summary">
                            @foreach($moduloData['lados'] as $ladoModulo)
                                <div class="modulo-side-pill {{ $ladoModulo['key'] === 'PASILLO' ? 'pasillo' : '' }}">
                                    <span class="modulo-side-pill-label">{{ $ladoModulo['label'] }}</span>
                                    <span class="modulo-side-pill-value">{{ $ladoModulo['revisado'] }}/{{ $ladoModulo['total'] }} · {{ $ladoModulo['porcentaje'] }}%</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="progress-container">
                            <span class="progress-label">{{ $moduloData['porcentaje'] }}%</span>
                            <div class="progress-bar bg-{{ $moduloData['color'] }}" style="width: {{ $moduloData['porcentaje'] }}%;"></div>
                        </div>

                        <div class="modulo-summary-footer">
                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick="abrirModalNiveles('modulo-template-{{ $lineaHistorico['linea_id'] }}-{{ $moduloData['numero'] }}', 'Modulo {{ $moduloData['numero'] }}', '{{ $lineaHistorico['linea_nombre'] }}')">
                                <i class="fas fa-layer-group"></i>
                                Ver niveles
                            </button>
                        </div>
                    </div>

                    <template id="modulo-template-{{ $lineaHistorico['linea_id'] }}-{{ $moduloData['numero'] }}">
                        <div class="modal-module-overview">
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Modulo</span>
                                <span class="modal-overview-value">{{ $moduloData['numero'] }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Linea</span>
                                <span class="modal-overview-value">{{ $lineaHistorico['linea_nombre'] }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Revisado</span>
                                <span class="modal-overview-value">{{ $moduloData['revisado'] }}/{{ $moduloData['total'] }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Avance general</span>
                                <span class="modal-overview-value">{{ $moduloData['porcentaje'] }}%</span>
                            </div>
                        </div>

                        <div class="modal-levels-grid">
                            @foreach($moduloData['niveles'] as $nivelData)
                                <article
                                    class="modal-level-card {{ $nivelData['key'] === 'INFERIOR' ? 'nivel-inferior' : '' }} {{ $loop->first ? 'is-selected' : '' }}"
                                    tabindex="0"
                                    role="button"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    onclick="seleccionarNivelModal(this)"
                                    onkeydown="manejarNivelModal(event, this)">
                                    <div class="modal-level-header">
                                        <div>
                                            <h4 class="modal-level-title">{{ $nivelData['label'] }}</h4>
                                            <span class="modal-level-subtitle">Modulo {{ $moduloData['numero'] }}</span>
                                        </div>
                                        <span class="modal-level-badge">{{ $nivelData['porcentaje'] }}%</span>
                                    </div>

                                    <div>
                                        <div class="modal-level-section-title">Avance del historico</div>
                                        <div class="modal-level-progress-meta">
                                            <span>Revisados</span>
                                            <span>{{ $nivelData['revisado'] }}/{{ $nivelData['total'] }}</span>
                                        </div>
                                        <div class="progress-container">
                                            <span class="progress-label">{{ $nivelData['porcentaje'] }}%</span>
                                            <div class="progress-bar bg-{{ $nivelData['color'] }}" style="width: {{ $nivelData['porcentaje'] }}%;"></div>
                                        </div>
                                    </div>

                                    <div class="modal-level-sides">
                                        <div class="modal-level-section-title">Componentes por lado dentro de este nivel</div>
                                        @foreach($nivelData['lados'] as $ladoData)
                                            <div class="modal-side-block">
                                                <div class="modal-side-header">
                                                    <div>
                                                        <div class="modal-side-title">{{ $ladoData['label'] }}</div>
                                                        <div class="modal-side-meta">Avance {{ $ladoData['revisado'] }}/{{ $ladoData['total'] }}</div>
                                                    </div>
                                                    <span class="modal-level-badge">{{ $ladoData['porcentaje'] }}%</span>
                                                </div>

                                                <div class="progress-container" style="height: 18px;">
                                                    <span class="progress-label">{{ $ladoData['porcentaje'] }}%</span>
                                                    <div class="progress-bar bg-{{ $ladoData['color'] }}" style="width: {{ $ladoData['porcentaje'] }}%;"></div>
                                                </div>

                                                <div class="modal-side-components">
                                                    @foreach($ladoData['componentes'] as $componenteData)
                                                        <div class="modal-level-component">
                                                            <div class="modal-level-component-head">
                                                                <div class="componente-nombre">
                                                                    <div class="componente-imagen">
                                                                        <img
                                                                            src="{{ asset('images/componentes-pasteurizadora/' . $componenteData['codigo'] . '.png') }}"
                                                                            alt="{{ $componenteData['nombre'] }}"
                                                                            class="componente-img"
                                                                            onerror="this.src='{{ asset('images/extras/sin imagen.png') }}'">
                                                                    </div>
                                                                    <span>{{ $componenteData['nombre'] }}</span>
                                                                </div>
                                                                <span class="cantidad-badge">{{ $componenteData['total'] }} total</span>
                                                            </div>

                                                            <div>
                                                                <div class="modal-level-component-progress-meta">
                                                                    <span>Avance por componente</span>
                                                                    <span>{{ $componenteData['revisadas'] }}/{{ $componenteData['total'] }}</span>
                                                                </div>
                                                                <div class="progress-container" style="height: 18px;">
                                                                    <span class="progress-label">{{ $componenteData['porcentaje'] }}%</span>
                                                                    <div class="progress-bar bg-{{ $componenteData['color'] }}" style="width: {{ $componenteData['porcentaje'] }}%;"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="acciones" style="margin-top: 22px; justify-content: flex-start;">
                            <a
                                href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $lineaHistorico['linea_id'], 'modulo' => $moduloData['numero']]) }}"
                                class="btn btn-primary">
                                <i class="fas fa-chart-pie"></i>
                                Ver analisis del modulo
                            </a>
                        </div>
                    </template>
                @endforeach
            @empty
                <div class="empty-state">
                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                    <p>No hay datos disponibles para esta linea.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($modulosHistorico->isNotEmpty())
        <div class="grafica-section">
            <div class="grafica-title">
                <i class="fas fa-chart-pie text-blue-600"></i>
                Grafica en pastel por pasteurizadora y modulos
            </div>
            <div class="grafica-subtitle">
                Cada grafica muestra como se distribuye el avance revisado entre los modulos de cada pasteurizadora. La leyenda mantiene el detalle de revisados, total y porcentaje por modulo.
            </div>

            <div class="grafica-lineas-grid">
                @foreach($modulosHistorico as $lineaHistorico)
                    @php
                        $lineaTotal = $lineaHistorico['totales']['total'] ?? 0;
                        $lineaRevisado = $lineaHistorico['totales']['revisado'] ?? 0;
                        $lineaPorcentaje = $lineaTotal > 0 ? round(($lineaRevisado / $lineaTotal) * 100) : 0;
                        $chartId = 'grafica-pastel-' . $lineaHistorico['linea_id'];
                        $palette = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#14b8a6', '#6366f1', '#eab308', '#22c55e', '#3b82f6', '#f43f5e', '#0ea5e9'];
                        $chartModules = collect($lineaHistorico['modulos'])->values()->map(function ($modulo, $index) use ($palette) {
                            return [
                                'label' => 'Modulo ' . $modulo['numero'],
                                'value' => (int) ($modulo['revisado'] ?? 0),
                                'total' => (int) ($modulo['total'] ?? 0),
                                'revisado' => (int) ($modulo['revisado'] ?? 0),
                                'porcentaje' => (int) ($modulo['porcentaje'] ?? 0),
                                'color' => $palette[$index % count($palette)],
                            ];
                        })->all();
                    @endphp
                    <div class="grafica-linea-card">
                        <div class="grafica-linea-header">
                            <div>
                                <div class="grafica-linea-title">Linea {{ $lineaHistorico['linea_nombre'] }}</div>
                                <div class="grafica-linea-summary">{{ $lineaRevisado }}/{{ $lineaTotal }} revisados · {{ $lineaPorcentaje }}% general</div>
                            </div>
                            <span class="modulo-summary-badge">{{ count($lineaHistorico['modulos']) }} modulos</span>
                        </div>

                        <div class="grafica-pie-layout">
                            <div class="grafica-pie-panel">
                                <div class="grafica-pie-wrapper">
                                    <canvas id="{{ $chartId }}" class="grafica-pie-canvas" data-grafica-pastel='@json($chartModules)'></canvas>
                                    <div class="grafica-pie-center">
                                        <div class="grafica-pie-center-value">{{ $lineaPorcentaje }}%</div>
                                        <div class="grafica-pie-center-label">avance general</div>
                                        <div class="grafica-linea-summary" style="margin-top: 6px;">{{ $lineaRevisado }}/{{ $lineaTotal }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="grafica-legend">
                                <div class="grafica-legend-title">Distribucion por modulo</div>
                                @foreach($chartModules as $moduloChart)
                                    <div class="grafica-legend-item">
                                        <div class="grafica-legend-head">
                                            <div class="grafica-legend-name">
                                                <span class="grafica-color-dot" style="background: {{ $moduloChart['color'] }};"></span>
                                                <span>{{ $moduloChart['label'] }}</span>
                                            </div>
                                            <span class="grafica-legend-value">{{ $moduloChart['porcentaje'] }}%</span>
                                        </div>
                                        <div class="grafica-legend-meta">
                                            <span>Revisados: {{ $moduloChart['revisado'] }}</span>
                                            <span>Total: {{ $moduloChart['total'] }}</span>
                                        </div>
                                        <div class="progress-container" style="height: 14px;">
                                            <span class="progress-label">{{ $moduloChart['porcentaje'] }}%</span>
                                            <div class="progress-bar {{ $moduloChart['porcentaje'] >= 80 ? 'bg-success' : ($moduloChart['porcentaje'] >= 50 ? 'bg-info' : ($moduloChart['porcentaje'] >= 20 ? 'bg-warning' : 'bg-danger')) }}" style="width: {{ $moduloChart['porcentaje'] }}%;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="acciones">
        @if(!$mostrarTodas && isset($lineaSeleccionada))
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $lineaSeleccionada->id]) }}" class="btn btn-primary">
                <i class="fas fa-chart-pie"></i>
                Ver analisis detallado
            </a>
        @else
            <a href="{{ route('pasteurizadora.dashboard') }}" class="btn btn-primary">
                <i class="fas fa-chart-pie"></i>
                Ir al dashboard
            </a>
        @endif

        <button class="btn btn-success" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i>
            Actualizar datos
        </button>
    </div>
</div>

<div id="nivelesModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3>
                    <i class="fas fa-layer-group text-blue-600"></i>
                    <span id="modalTitulo">Modulo</span>
                </h3>
                <span class="modal-subtitle" id="modalSubtitulo">Linea</span>
            </div>
            <button type="button" onclick="cerrarModalNiveles()" class="modal-close" aria-label="Cerrar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalNivelesBody"></div>
    </div>
</div>

<script>
function abrirModalNiveles(templateId, titulo, linea) {
    const modal = document.getElementById('nivelesModal');
    const body = document.getElementById('modalNivelesBody');
    const template = document.getElementById(templateId);

    if (!template) {
        return;
    }

    document.getElementById('modalTitulo').textContent = titulo;
    document.getElementById('modalSubtitulo').textContent = 'Linea ' + linea;
    body.innerHTML = '';
    body.appendChild(template.content.cloneNode(true));

    const firstCard = body.querySelector('.modal-level-card');
    if (firstCard) {
        seleccionarNivelModal(firstCard);
    }

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function cerrarModalNiveles() {
    const modal = document.getElementById('nivelesModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function seleccionarNivelModal(card) {
    const modalBody = card.closest('.modal-body') || document;
    modalBody.querySelectorAll('.modal-level-card').forEach((item) => {
        item.classList.remove('is-selected');
        item.setAttribute('aria-pressed', 'false');
    });

    card.classList.add('is-selected');
    card.setAttribute('aria-pressed', 'true');
}

function manejarNivelModal(event, card) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        seleccionarNivelModal(card);
    }
}

function inicializarGraficasPastel() {
    if (typeof Chart === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-grafica-pastel]').forEach((canvas) => {
        const rawData = canvas.getAttribute('data-grafica-pastel');
        const modules = rawData ? JSON.parse(rawData) : [];
        const hasProgress = modules.some((item) => Number(item.value) > 0);
        const labels = hasProgress ? modules.map((item) => item.label) : ['Sin avance'];
        const values = hasProgress ? modules.map((item) => item.value) : [1];
        const colors = hasProgress ? modules.map((item) => item.color) : ['#cbd5e1'];
        const ctx = canvas.getContext('2d');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 4,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (!hasProgress) {
                                    return 'Sin avance registrado';
                                }

                                const modulo = modules[context.dataIndex];
                                return modulo.label + ': ' + modulo.revisado + '/' + modulo.total + ' revisados (' + modulo.porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
}

document.getElementById('nivelesModal').addEventListener('click', function (event) {
    if (event.target === this) {
        cerrarModalNiveles();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarModalNiveles();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    inicializarGraficasPastel();
});
</script>
@endsection
