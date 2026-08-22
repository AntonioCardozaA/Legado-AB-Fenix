@extends('layouts.app')

@section('title', 'Historico de Revisados - Central Hidraulica')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.central-hidraulica';
    $analisisRoute = $analisisRoute ?? fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
    $analisisDashboardRoute = $analisisDashboardRoute ?? 'pasteurizadora.dashboard';
    $sinLineaSeleccionada = !isset($lineaSeleccionada) || !$lineaSeleccionada;
    $resumenCollection = collect($resumenCentral ?? []);

    $contextosConTotal = fn ($contextos) => collect($contextos)
        ->filter(fn ($contexto) => ($contexto['contabilizable'] ?? true) && ($contexto['total'] ?? null) !== null);
    $sumTotalContextos = fn ($contextos) => (int) $contextosConTotal($contextos)->sum(fn ($contexto) => (int) ($contexto['total'] ?? 0));
    $sumRevisadoContextos = fn ($contextos) => (int) $contextosConTotal($contextos)->sum(fn ($contexto) => min((int) ($contexto['revisado'] ?? 0), (int) ($contexto['total'] ?? 0)));
    $sumRegistrosContextos = fn ($contextos) => (int) collect($contextos)->sum(fn ($contexto) => (int) ($contexto['registros_count'] ?? 0));

    $totalGeneral = (int) $resumenCollection->sum(fn ($item) => (int) ($item['totales']['componentes'] ?? 0));
    $totalRevisado = (int) $resumenCollection->sum(fn ($item) => (int) ($item['totales']['revisados'] ?? 0));
    $porcentajeGeneral = $totalGeneral > 0 ? (int) round(($totalRevisado / $totalGeneral) * 100) : 0;

    $progressColor = function (int $porcentaje): string {
        return $porcentaje >= 80 ? 'success' : ($porcentaje >= 50 ? 'info' : ($porcentaje >= 20 ? 'warning' : 'danger'));
    };
@endphp

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
        width: 100%;
        max-width: min(1400px, 100%);
        margin: 0 auto;
        padding: 24px;
        overflow-x: hidden;
    }

    .historico-container * {
        box-sizing: border-box;
        min-width: 0;
    }

    .historico-container :is(h1, h2, h3, h4, p, span, a, button, div) {
        overflow-wrap: anywhere;
    }

    .lineas-section,
    .grafica-section,
    .resumen-card,
    .componentes-table {
        background: white;
        border: 1px solid var(--medium-gray);
        box-shadow: 0 4px 6px rgba(15, 23, 42, 0.05);
        max-width: 100%;
        min-width: 0;
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
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        max-width: 100%;
        min-width: 0;
    }

    .lineas-grid .empty-state {
        flex: 1 1 100%;
    }

    .linea-btn {
        display: inline-flex;
        flex: 0 1 128px;
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
        min-height: 44px;
        max-width: 100%;
        line-height: 1.2;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
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
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .resumen-card {
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
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
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    .table-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 22px;
        min-height: 56px;
        display: flex;
        align-items: center;
    }

    .table-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        min-width: 0;
        line-height: 1.25;
    }

    .pisos-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        padding: 24px;
        width: 100%;
        min-width: 0;
        align-items: stretch;
    }

    .linea-group-title {
        grid-column: 1 / -1;
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 22px 26px;
        min-width: 0;
        width: 100%;
    }

    .linea-group-main {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
    }

    .linea-group-name {
        font-size: 20px;
        font-weight: 800;
        color: #020617;
        overflow-wrap: anywhere;
    }

    .piso-summary-card {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        container-name: piso-card;
        container-type: inline-size;
        min-width: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .piso-summary-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        min-width: 0;
    }

    .piso-summary-header > div {
        min-width: 0;
    }

    .piso-summary-title {
        font-size: 30px;
        font-weight: 800;
        line-height: 1.05;
        color: var(--slate-900);
        margin: 0;
        overflow-wrap: anywhere;
    }

    .piso-summary-badge {
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 800;
        max-width: 100%;
        text-align: center;
        white-space: normal;
    }

    .piso-stats,
    .piso-context-summary {
        display: grid;
        gap: 10px;
    }

    .piso-stats {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
    }

    .piso-context-summary {
        grid-template-columns: 1fr;
    }

    .piso-stat {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px 12px;
        text-align: center;
        min-width: 0;
        min-height: 86px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .piso-stat-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
        max-width: 100%;
    }

    .piso-stat-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--slate-900);
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    .piso-context-pill {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 18px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 7px;
        min-height: 68px;
        width: 100%;
        min-width: 0;
    }

    .piso-context-pill:nth-child(even) {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .piso-context-pill-label {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #1e3a8a;
        overflow-wrap: anywhere;
    }

    .piso-context-pill:nth-child(even) .piso-context-pill-label {
        color: #334155;
    }

    .piso-context-pill-value {
        font-size: 16px;
        font-weight: 800;
        color: var(--slate-900);
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .progress-container {
        width: 100%;
        min-width: 0;
        background: #e2e8f0;
        border-radius: 10px;
        height: 28px;
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

    .piso-summary-footer {
        display: flex;
        justify-content: flex-end;
        width: 100%;
        min-width: 0;
    }

    @container piso-card (max-width: 420px) {
        .piso-stats {
            grid-template-columns: 1fr;
        }

        .piso-summary-title {
            font-size: 24px;
            line-height: 1.12;
        }

        .piso-stat {
            min-height: 76px;
            padding: 14px 10px;
        }

        .piso-context-pill {
            padding: 12px;
        }

        .piso-summary-footer .btn {
            width: 100%;
        }
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
        min-height: 44px;
        max-width: 100%;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        min-width: 170px;
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
        max-height: calc(100dvh - 40px);
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
        max-height: calc(100dvh - 130px);
    }

    .modal-close {
        width: 44px;
        height: 44px;
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
        overflow-wrap: anywhere;
    }

    .modal-levels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
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

    .modal-level-card:nth-child(even) {
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
        font-size: 26px;
        font-weight: 800;
        line-height: 1.1;
        margin: 0;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .modal-level-badge {
        background: #dbeafe;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        max-width: 100%;
        text-align: center;
        white-space: normal;
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

    .modal-context-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .modal-context-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        padding: 10px 12px;
    }

    .modal-context-label {
        display: block;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .modal-context-value {
        font-size: 13px;
        font-weight: 800;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .modal-activity {
        margin: 0;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 12px;
        font-size: 13px;
        line-height: 1.45;
        color: #334155;
        font-weight: 600;
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
        color: #2563eb;
    }

    .cantidad-badge {
        background: #e2e8f0;
        color: #334155;
        padding: 5px 10px;
        border-radius: 999px;
        font-weight: 700;
        font-size: 11px;
        max-width: 100%;
        text-align: center;
        white-space: normal;
    }

    .grafica-section {
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        width: 100%;
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

    .grafica-lineas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
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
        container-name: grafica-linea;
        container-type: inline-size;
        min-width: 0;
        overflow: hidden;
    }

    .grafica-linea-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--slate-900);
        overflow-wrap: anywhere;
    }

    .grafica-linea-summary {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
    }

    .grafica-pie-layout {
        display: grid;
        grid-template-columns: minmax(0, 320px) minmax(0, 1fr);
        gap: 20px;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-width: 0;
    }

    .grafica-pie-panel {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 18px;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    .grafica-pie-wrapper {
        width: min(100%, 320px);
        max-width: 100%;
        aspect-ratio: 1;
        position: relative;
        min-width: 0;
        margin-inline: auto;
    }

    .grafica-pie-canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
        max-width: 100%;
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
        min-width: 0;
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
        max-width: 90%;
        overflow-wrap: anywhere;
    }

    .grafica-legend {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
        min-width: 0;
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
        min-width: 0;
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
        min-width: 0;
    }

    .grafica-legend-name span:last-child {
        min-width: 0;
        overflow-wrap: anywhere;
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
        text-align: right;
        white-space: normal;
    }

    .grafica-legend-meta {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }

    @container grafica-linea (max-width: 640px) {
        .grafica-pie-layout {
            grid-template-columns: 1fr;
        }

        .grafica-pie-wrapper {
            width: min(100%, 280px);
        }

        .grafica-legend-head {
            flex-direction: column;
            align-items: stretch;
        }

        .grafica-legend-value {
            text-align: left;
        }
    }

    .acciones {
        display: flex;
        gap: 16px;
        margin-top: 24px;
        justify-content: flex-end;
        flex-wrap: wrap;
        max-width: 100%;
        min-width: 0;
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

        .pisos-grid,
        .piso-stats,
        .modal-levels-grid,
        .grafica-lineas-grid,
        .grafica-pie-layout,
        .modal-context-grid {
            grid-template-columns: 1fr;
        }

        .linea-group-title,
        .piso-summary-header,
        .modal-level-header,
        .modal-side-header,
        .modal-level-component-head,
        .grafica-linea-header,
        .grafica-legend-head {
            flex-direction: column;
            align-items: stretch;
        }

        .componentes-table {
            border-radius: 16px;
        }

        .table-header {
            padding: 14px 18px;
        }

        .linea-group-title {
            padding: 18px;
        }

        .modal-content {
            width: 100%;
            max-height: calc(100dvh - 20px);
        }

        .modal-body {
            padding: 18px;
            max-height: calc(100dvh - 110px);
        }

        .lineas-grid,
        .acciones,
        .piso-summary-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .linea-btn,
        .btn {
            width: 100%;
        }

        .grafica-pie-panel {
            padding: 14px;
        }

        .grafica-pie-wrapper {
            width: min(100%, 280px);
        }

        .table-header {
            align-items: flex-start;
        }

        .table-header h3,
        .piso-summary-title,
        .modal-level-title {
            font-size: 24px;
            line-height: 1.15;
        }

        .modal-overview-value {
            font-size: 20px;
        }
    }

    @media (max-width: 480px) {
        .historico-container {
            padding: 12px 10px;
        }

        .lineas-section,
        .grafica-section {
            padding: 16px;
        }

        .pisos-grid {
            padding: 14px;
            gap: 14px;
        }

        .componentes-table {
            border-radius: 14px;
        }

        .table-header {
            padding: 14px;
        }

        .linea-group-title {
            border-radius: 16px;
            padding: 16px;
        }

        .linea-group-name {
            font-size: 18px;
        }

        .resumen-card,
        .piso-summary-card,
        .grafica-linea-card {
            padding: 16px;
        }

        .piso-summary-card {
            border-radius: 18px;
            gap: 14px;
        }

        .piso-stat {
            min-height: 72px;
            padding: 12px 10px;
        }

        .piso-stat-label,
        .piso-context-pill-label {
            font-size: 10px;
        }

        .piso-stat-value {
            font-size: 20px;
        }

        .piso-context-pill-value {
            font-size: 15px;
        }

        .modal {
            padding: 8px;
        }

        .modal-header {
            align-items: flex-start;
            padding: 16px;
        }

        .modal-header h3 {
            font-size: 18px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .modal-body {
            padding: 14px;
        }

        .grafica-pie-center-value {
            font-size: 28px;
        }

        .grafica-pie-wrapper {
            width: min(100%, 230px);
        }

        .grafica-pie-center {
            padding: 14px;
        }

        .grafica-legend-meta {
            flex-direction: column;
            gap: 4px;
        }

        .table-header h3,
        .piso-summary-title,
        .modal-level-title {
            font-size: 20px;
        }

        .piso-stats,
        .piso-context-summary,
        .modal-module-overview {
            gap: 10px;
        }
    }
</style>

<div class="historico-container">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route($analisisDashboardRoute) }}"
               class="responsive-action responsive-action--secondary mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="flex items-start gap-2 text-xl font-bold text-gray-800 sm:items-center sm:text-2xl">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Historico de Revisados - Central Hidraulica
            </h1>
        </div>
    </div>

    <div class="lineas-section">
        <div class="lineas-title">
            Pasteurizadora de central hidraulica
        </div>
        <div class="lineas-grid">
            @forelse($lineas as $linea)
                <a href="{{ $analisisRoute('historico-revisados', ['linea_id' => $linea->id]) }}"
                   class="linea-btn {{ !$sinLineaSeleccionada && isset($lineaSeleccionada) && $lineaSeleccionada->id == $linea->id ? 'active' : '' }}">
                    {{ $linea->nombre }}
                </a>
            @empty
                <div class="empty-state">
                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                    <p>No hay pasteurizadoras disponibles.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="resumen-grid">
        <div class="resumen-card">
            <div class="resumen-icono total">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="resumen-info">
                <h4>Total Componentes</h4>
                <div class="valor">{{ $totalGeneral }}</div>
            </div>
        </div>
        <div class="resumen-card">
            <div class="resumen-icono revisado">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="resumen-info">
                <h4>Revisados</h4>
                <div class="valor">{{ $totalRevisado }}</div>
            </div>
        </div>
        <div class="resumen-card">
            <div class="resumen-icono porcentaje">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="resumen-info">
                <h4>Progreso General</h4>
                <div class="valor">{{ $porcentajeGeneral }}%</div>
            </div>
        </div>
    </div>

    <div class="componentes-table">
       

        <div class="pisos-grid">
            @forelse($resumenCentral as $lineaId => $seguimiento)
                <div class="linea-group-title">
                    <div class="linea-group-main">
                        <span class="linea-group-name">Pasteurizadora {{ $seguimiento['linea']->nombre }}</span>
                    </div>
                </div>

                @foreach($seguimiento['pisos'] as $piso)
                    @php
                        $componentesPiso = collect($piso['componentes'] ?? []);
                        $contextosPiso = $componentesPiso->flatMap(fn ($item) => $item['contextos'] ?? []);
                        $pisoTotal = $sumTotalContextos($contextosPiso);
                        $pisoRevisado = $sumRevisadoContextos($contextosPiso);
                        $pisoPorcentaje = $pisoTotal > 0 ? (int) round(($pisoRevisado / $pisoTotal) * 100) : 0;
                        $pisoColor = $progressColor($pisoPorcentaje);
                        $templateId = 'central-piso-template-' . $lineaId . '-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($piso['key'] ?? $loop->index));
                    @endphp

                    <div class="piso-summary-card">
                        <div class="piso-summary-header">
                            <div>
                                <h4 class="piso-summary-title">{{ $piso['label'] }}</h4>
                            </div>
                        </div>

                        <div class="piso-stats">
                            <div class="piso-stat">
                                <span class="piso-stat-label">Total</span>
                                <span class="piso-stat-value">{{ $pisoTotal }}</span>
                            </div>
                            <div class="piso-stat">
                                <span class="piso-stat-label">Avance</span>
                                <span class="piso-stat-value">{{ $pisoPorcentaje }}%</span>
                            </div>
                        </div>

                        <div class="piso-context-summary">
                            <div class="piso-context-pill">
                                <span class="piso-context-pill-label">Componentes</span>
                                <span class="piso-context-pill-value">{{ $pisoRevisado }}/{{ $pisoTotal }} - {{ $pisoPorcentaje }}%</span>
                            </div>
                        </div>

                        <div class="progress-container">
                            <span class="progress-label">{{ $pisoPorcentaje }}%</span>
                            <div class="progress-bar bg-{{ $pisoColor }}" style="width: {{ $pisoPorcentaje }}%;"></div>
                        </div>

                        <div class="piso-summary-footer">
                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick='abrirModalPiso(@json($templateId), @json($piso["label"]), @json($seguimiento["linea"]->nombre))'>
                                <i class="fas fa-layer-group"></i>
                                Ver Detalles
                            </button>
                        </div>
                    </div>

                    <template id="{{ $templateId }}">
                        <div class="modal-module-overview">
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Piso</span>
                                <span class="modal-overview-value">{{ $piso['label'] }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Central</span>
                                <span class="modal-overview-value">{{ $seguimiento['linea']->nombre }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Revisado</span>
                                <span class="modal-overview-value">{{ $pisoRevisado }}/{{ $pisoTotal }}</span>
                            </div>
                            <div class="modal-overview-item">
                                <span class="modal-overview-label">Avance general</span>
                                <span class="modal-overview-value">{{ $pisoPorcentaje }}%</span>
                            </div>
                        </div>

                        <div class="modal-levels-grid">
                            @forelse($componentesPiso as $item)
                                @php
                                    $contextosComponente = collect($item['contextos'] ?? []);
                                    $componenteTotal = $sumTotalContextos($contextosComponente);
                                    $componenteRevisado = $sumRevisadoContextos($contextosComponente);
                                    $componenteRegistros = $sumRegistrosContextos($contextosComponente);
                                    $componentePorcentaje = $componenteTotal > 0 ? (int) round(($componenteRevisado / $componenteTotal) * 100) : 0;
                                    $componenteColor = $progressColor($componentePorcentaje);
                                    $config = $item['configuracion'];
                                @endphp
                                <article
                                    class="modal-level-card {{ $loop->first ? 'is-selected' : '' }}"
                                    tabindex="0"
                                    role="button"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    onclick="seleccionarElementoModal(this)"
                                    onkeydown="manejarElementoModal(event, this)">
                                    <div class="modal-level-header">
                                        <div>
                                            <h4 class="modal-level-title">{{ $config->componente_nombre }}</h4>
                                        </div>
                                        <span class="modal-level-badge">
                                            {{ $componenteTotal > 0 ? $componentePorcentaje . '%' : $componenteRegistros . ' registros' }}
                                        </span>
                                    </div>

                                    <div>
                                        <div class="modal-level-progress-meta">
                                            <span>Revisados</span>
                                            <span>
                                                @if($componenteTotal > 0)
                                                    {{ $componenteRevisado }}/{{ $componenteTotal }}
                                                @else
                                                    {{ $componenteRegistros }} registros
                                                @endif
                                            </span>
                                        </div>
                                        <div class="progress-container">
                                            <span class="progress-label">{{ $componentePorcentaje }}%</span>
                                            <div class="progress-bar bg-{{ $componenteColor }}" style="width: {{ $componentePorcentaje }}%;"></div>
                                        </div>
                                    </div>

                                    <div class="modal-level-sides">
                                        <div class="modal-level-section-title">Detalle por {{ $config->lado_requerido ? 'lado' : 'piso' }}</div>
                                        @foreach($contextosComponente as $contexto)
                                            @php
                                                $contextoTotal = $contexto['total'] ?? null;
                                                $contextoRevisado = (int) ($contexto['revisado'] ?? 0);
                                                $contextoRegistros = (int) ($contexto['registros_count'] ?? 0);
                                                $contextoPorcentaje = $contextoTotal !== null ? (int) ($contexto['porcentaje'] ?? 0) : 0;
                                                $contextoColor = $progressColor($contextoPorcentaje);
                                                $ultimo = $contexto['ultimo'] ?? null;
                                            @endphp
                                            <div class="modal-side-block">
                                                <div class="modal-side-header">
                                                    <div>
                                                        <div class="modal-side-title">{{ $contexto['lado_label'] ?? 'Piso completo' }}</div>
                                                        <div class="modal-side-meta">
                                                            @if($contextoTotal !== null)
                                                                Avance {{ $contextoRevisado }}/{{ $contextoTotal }}
                                                            @else
                                                                {{ $contextoRegistros }} registros capturados
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <span class="modal-level-badge">
                                                        {{ $contextoTotal !== null ? $contextoPorcentaje . '%' : 'Sin total' }}
                                                    </span>
                                                </div>

                                                @if($contextoTotal !== null)
                                                    <div class="progress-container" style="height: 18px;">
                                                        <span class="progress-label">{{ $contextoPorcentaje }}%</span>
                                                        <div class="progress-bar bg-{{ $contextoColor }}" style="width: {{ $contextoPorcentaje }}%;"></div>
                                                    </div>
                                                @else
                                                    <div class="modal-activity">
                                                        Cantidad base pendiente por definir.
                                                    </div>
                                                @endif

                                                <div class="modal-context-grid">
                                                    <div class="modal-context-item">
                                                        <span class="modal-context-label">Ultima fecha</span>
                                                        <span class="modal-context-value">{{ optional($ultimo?->fecha_analisis)->format('d/m/Y') ?: 'Sin registro' }}</span>
                                                    </div>
                                                    <div class="modal-context-item">
                                                        <span class="modal-context-label">Orden</span>
                                                        <span class="modal-context-value">{{ $ultimo?->numero_orden ?: 'Sin orden' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </article>
                            @empty
                                <div class="empty-state">
                                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                                    <p>Este piso no tiene componentes configurados.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="acciones" style="margin-top: 22px; justify-content: flex-start;">
                            <a
                                href="{{ $analisisRoute('index', array_filter(['linea_id' => $lineaId, 'piso' => $piso['key']], fn ($value) => filled($value))) }}"
                                class="btn btn-primary">
                                <i class="fas fa-chart-pie"></i>
                                Ver analisis del piso
                            </a>
                        </div>
                    </template>
                @endforeach
            @empty
                <div class="empty-state">
                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                    <p>No hay datos disponibles para central hidraulica.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($resumenCollection->isNotEmpty())
        <div class="grafica-section">
            <div class="grafica-title">
                <i class="fas fa-chart-pie text-blue-600"></i>
                Grafica.
            </div>

            <div class="grafica-lineas-grid">
                @foreach($resumenCentral as $lineaId => $seguimiento)
                    @php
                        $lineaTotal = (int) ($seguimiento['totales']['componentes'] ?? 0);
                        $lineaRevisado = (int) ($seguimiento['totales']['revisados'] ?? 0);
                        $lineaPorcentaje = $lineaTotal > 0 ? (int) round(($lineaRevisado / $lineaTotal) * 100) : 0;
                        $chartId = 'grafica-central-pastel-' . $lineaId;
                        $palette = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
                        $chartPisos = collect($seguimiento['pisos'] ?? [])->values()->map(function ($pisoData, $index) use ($palette, $sumTotalContextos, $sumRevisadoContextos) {
                            $contextos = collect($pisoData['componentes'] ?? [])->flatMap(fn ($item) => $item['contextos'] ?? []);
                            $total = $sumTotalContextos($contextos);
                            $revisado = $sumRevisadoContextos($contextos);
                            $porcentaje = $total > 0 ? (int) round(($revisado / $total) * 100) : 0;

                            return [
                                'label' => $pisoData['label'] ?? 'Piso',
                                'value' => $revisado,
                                'total' => $total,
                                'revisado' => $revisado,
                                'porcentaje' => $porcentaje,
                                'color' => $palette[$index % count($palette)],
                            ];
                        })->all();
                    @endphp
                    <div class="grafica-linea-card">
                        <div class="grafica-linea-header">
                            <div>
                                <div class="grafica-linea-title">Central Hidraulica {{ $seguimiento['linea']->nombre }}</div>
                                <div class="grafica-linea-summary">{{ $lineaPorcentaje }}% general</div>
                            </div>
                            <span class="piso-summary-badge">{{ count($seguimiento['pisos'] ?? []) }} pisos</span>
                        </div>

                        <div class="grafica-pie-layout">
                            <div class="grafica-pie-panel">
                                <div class="grafica-pie-wrapper">
                                    <canvas id="{{ $chartId }}" class="grafica-pie-canvas" data-grafica-pastel='@json($chartPisos)'></canvas>
                                    <div class="grafica-pie-center">
                                        <div class="grafica-pie-center-value">{{ $lineaPorcentaje }}%</div>
                                        <div class="grafica-pie-center-label">avance general</div>
                                    </div>
                                </div>
                            </div>

                            <div class="grafica-legend">
                                <div class="grafica-legend-title">Pisos</div>
                                @foreach($chartPisos as $pisoChart)
                                    <div class="grafica-legend-item">
                                        <div class="grafica-legend-head">
                                            <div class="grafica-legend-name">
                                                <span class="grafica-color-dot" style="background: {{ $pisoChart['color'] }};"></span>
                                                <span>{{ $pisoChart['label'] }}</span>
                                            </div>
                                            <span class="grafica-legend-value">{{ $pisoChart['porcentaje'] }}%</span>
                                        </div>
                                        <div class="grafica-legend-meta">
                                            <span>Revisados: {{ $pisoChart['revisado'] }}</span>
                                            <span>Total: {{ $pisoChart['total'] }}</span>
                                        </div>
                                        <div class="progress-container" style="height: 14px;">
                                            <span class="progress-label">{{ $pisoChart['porcentaje'] }}%</span>
                                            <div class="progress-bar bg-{{ $progressColor($pisoChart['porcentaje']) }}" style="width: {{ $pisoChart['porcentaje'] }}%;"></div>
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
        @if(!$sinLineaSeleccionada && isset($lineaSeleccionada))
            <a href="{{ $analisisRoute('index', ['linea_id' => $lineaSeleccionada->id]) }}" class="btn btn-primary">
                <i class="fas fa-chart-pie"></i>
                Ver analisis detallado
            </a>
        @else
            <a href="{{ route($analisisDashboardRoute) }}" class="btn btn-primary">
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

<div id="centralHistoricoModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3>
                    <i class="fas fa-layer-group text-blue-600"></i>
                    <span id="centralModalTitulo">Piso</span>
                </h3>
                <span class="modal-subtitle" id="centralModalSubtitulo">Central Hidraulica</span>
            </div>
            <button type="button" onclick="cerrarModalPiso()" class="modal-close" aria-label="Cerrar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="centralModalBody"></div>
    </div>
</div>

<script>
function abrirModalPiso(templateId, titulo, linea) {
    const modal = document.getElementById('centralHistoricoModal');
    const body = document.getElementById('centralModalBody');
    const template = document.getElementById(templateId);

    if (!template) {
        return;
    }

    document.getElementById('centralModalTitulo').textContent = titulo;
    document.getElementById('centralModalSubtitulo').textContent = 'Central Hidraulica ' + linea;
    body.innerHTML = '';
    body.appendChild(template.content.cloneNode(true));

    const firstCard = body.querySelector('.modal-level-card');
    if (firstCard) {
        seleccionarElementoModal(firstCard);
    }

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function cerrarModalPiso() {
    const modal = document.getElementById('centralHistoricoModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function seleccionarElementoModal(card) {
    const modalBody = card.closest('.modal-body') || document;
    modalBody.querySelectorAll('.modal-level-card').forEach((item) => {
        item.classList.remove('is-selected');
        item.setAttribute('aria-pressed', 'false');
    });

    card.classList.add('is-selected');
    card.setAttribute('aria-pressed', 'true');
}

function manejarElementoModal(event, card) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        seleccionarElementoModal(card);
    }
}

function inicializarGraficasPastel() {
    if (typeof Chart === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-grafica-pastel]').forEach((canvas) => {
        const rawData = canvas.getAttribute('data-grafica-pastel');
        const pisos = rawData ? JSON.parse(rawData) : [];
        const hasProgress = pisos.some((item) => Number(item.value) > 0);
        const labels = hasProgress ? pisos.map((item) => item.label) : ['Sin avance'];
        const values = hasProgress ? pisos.map((item) => item.value) : [1];
        const colors = hasProgress ? pisos.map((item) => item.color) : ['#cbd5e1'];
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

                                const piso = pisos[context.dataIndex];
                                return piso.label + ': ' + piso.revisado + '/' + piso.total + ' revisados (' + piso.porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
}

document.getElementById('centralHistoricoModal').addEventListener('click', function (event) {
    if (event.target === this) {
        cerrarModalPiso();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarModalPiso();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    inicializarGraficasPastel();
});
</script>
@endsection
