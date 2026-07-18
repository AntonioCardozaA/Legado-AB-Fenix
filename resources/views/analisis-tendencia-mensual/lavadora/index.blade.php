{{-- resources/views/analisis-tendencia-mensual-lavadora/index.blade.php --}}
@php
    $analisisTipo = $analisisTipo ?? '52124';
    $lineaSeleccionada = $lineaSeleccionada ?? null;
    $fechaInicio = $fechaInicio ?? request('fecha_inicio');
    $fechaFin = $fechaFin ?? request('fecha_fin');
    $isAnalisis30147 = $analisisTipo === '30147';
    $rutaAnalisisActiva = $isAnalisis30147
        ? 'analisis-tendencia-mensual.lavadora.analisis-30-14-7'
        : 'analisis-tendencia-mensual.lavadora.analisis-52-12-4';
    $parametrosFecha = array_filter([
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
    ], fn ($value) => filled($value));
    $parametrosLinea = array_filter([
        'linea_id' => $lineaSeleccionada,
    ], fn ($value) => filled($value)) + $parametrosFecha;
    $tituloAnalisis = $isAnalisis30147 ? 'Analisis 30-14-7' : 'Analisis 52-12-4';
    $analisisDetalle = $analisisDetalle ?? [];
    $detalleLinea = $detalleLinea ?? null;
    $resumenDetalle = $detalleLinea['resumen'] ?? [];
    $componentesDetalle = collect($detalleLinea['componentes'] ?? []);
    $danosDetalle = collect($detalleLinea['danos'] ?? []);
    $eventosDetalle = collect($detalleLinea['eventos'] ?? []);
    $globalDetalle = $analisisDetalle['global'] ?? [];
    $periodoDetalle = $analisisDetalle['periodo']['label'] ?? 'Historico disponible';
    $graficasDetalle = $detalleLinea['graficas'] ?? [];
@endphp
@extends('layouts.app')

@section('title', $analisisTipo === '30147' ? 'Analisis 30-14-7' : 'Analisis 52-12-4')

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
        color: black;
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

    .analysis-switcher {
        max-width: 100%;
        min-width: 0;
    }

    .analysis-switcher a {
        justify-content: center;
        min-height: 48px;
        min-width: 0;
        max-width: 100%;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
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
        max-width: 100%;
        min-width: 0;
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
        justify-content: center;
        gap: 8px;
        border: 1px solid transparent;
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
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
        overflow-x: auto;
        overflow-y: hidden;
        box-shadow: 0 20px 25px -5px rgba(82, 74, 74, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        margin-bottom: 32px;
        border: 1px solid var(--border);
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    .industrial-table {
        width: 100%;
        min-width: 940px;
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
        text-align: center;
    }

    .industrial-table tbody tr {
        transition: all 0.2s;
    }

    .industrial-table tbody tr:hover {
        background: #f8fafc;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 10;
    }

    .period-cell {
        background: #f8fafc;
        font-weight: 700;
        text-align: left;
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
        max-width: 100%;
        overflow: hidden;
    }

    .chart-header {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
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
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .chart-container {
        height: clamp(300px, 48vh, 450px);
        position: relative;
        width: 100%;
    }

    .executive-brief {
        display: grid;
        grid-template-columns: minmax(280px, 1.1fr) minmax(0, 1.9fr);
        gap: 18px;
        margin: 32px 0 24px;
        align-items: stretch;
    }

    .executive-status {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 22px;
        border: 1px solid var(--border);
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        box-shadow: 0 18px 32px rgba(15, 23, 42, 0.08);
    }

    .executive-status::after {
        content: '';
        position: absolute;
        right: -40px;
        bottom: -60px;
        width: 170px;
        height: 170px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.12);
    }

    .executive-status--positive {
        border-color: rgba(16, 185, 129, 0.24);
        background: linear-gradient(145deg, #ecfdf5, #f8fafc);
    }

    .executive-status--positive::after {
        background: rgba(16, 185, 129, 0.16);
    }

    .executive-status--alert {
        border-color: rgba(239, 68, 68, 0.22);
        background: linear-gradient(145deg, #fef2f2, #fff7ed);
    }

    .executive-status--alert::after {
        background: rgba(239, 68, 68, 0.14);
    }

    .executive-status--neutral {
        border-color: rgba(245, 158, 11, 0.22);
        background: linear-gradient(145deg, #fffbeb, #f8fafc);
    }

    .executive-status--neutral::after {
        background: rgba(245, 158, 11, 0.14);
    }

    .executive-eyebrow,
    .executive-window-label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-secondary);
    }

    .executive-status-title {
        position: relative;
        z-index: 1;
        color: var(--text-primary);
        font-size: 28px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 10px;
    }

    .executive-status-copy {
        position: relative;
        z-index: 1;
        margin-top: 12px;
        max-width: 42ch;
        color: var(--text-secondary);
        font-size: 14px;
        line-height: 1.55;
    }

    .executive-status-note {
        position: relative;
        z-index: 1;
        margin-top: 16px;
        padding: 10px 12px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.72);
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
    }

    .executive-window-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr));
        gap: 16px;
        align-content: start;
    }

    .executive-window-card {
        position: relative;
        overflow: hidden;
        min-width: 0;
        border-radius: 20px;
        border: 1px solid var(--border);
        background: white;
        padding: 20px 18px 18px;
        box-shadow: 0 14px 24px rgba(15, 23, 42, 0.06);
    }

    .executive-window-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--window-accent, var(--primary));
    }

    .executive-window-value {
        color: var(--text-primary);
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-size: 30px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 12px;
    }

    .executive-window-role {
        margin-top: 8px;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
        max-width: 18ch;
    }

    .executive-window-delta {
        display: inline-flex;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
        border-radius: 999px;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.35;
        max-width: 100%;
    }

    .executive-window-delta--positive {
        background: #d1fae5;
        color: #065f46;
    }

    .executive-window-delta--alert {
        background: #fee2e2;
        color: #991b1b;
    }

    .executive-window-delta--neutral {
        background: #fef3c7;
        color: #92400e;
    }

    .chart-caption {
        margin-left: auto;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
    }

    .chart-header--executive {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        grid-template-areas:
            'title controls'
            'caption caption';
        column-gap: 16px;
        row-gap: 14px;
        align-items: start;
    }

    .chart-title-block {
        grid-area: title;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    .chart-title-block i {
        flex: 0 0 auto;
    }

    .chart-title-copy {
        min-width: 0;
    }

    .chart-title-copy h3 {
        margin: 0;
        line-height: 1.25;
    }

    .chart-subcopy {
        margin-top: 6px;
        max-width: 56ch;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
    }

    .chart-header--executive .chart-view-selector {
        grid-area: controls;
        margin-left: 0;
        justify-content: flex-end;
    }

    .chart-header--executive .chart-caption {
        grid-area: caption;
        margin-left: 0;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: #f8fafc;
        max-width: 100%;
    }

    .chart-container--executive {
        height: clamp(320px, 46vh, 420px);
        margin-top: 6px;
    }

    /* Selector de vista para la gráfica */
    .chart-view-selector {
        display: flex;
        flex-wrap: wrap;
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

    .analysis-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .analysis-summary-card {
        background: white;
        border: 1px solid var(--border);
        border-top: 4px solid var(--primary);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        min-width: 0;
    }

    .analysis-summary-card.danger { border-top-color: var(--danger); }
    .analysis-summary-card.warning { border-top-color: var(--warning); }
    .analysis-summary-card.success { border-top-color: var(--success); }

    .analysis-summary-label {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .analysis-summary-value {
        color: var(--text-primary);
        font-size: 24px;
        font-weight: 800;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .analysis-summary-meta {
        color: var(--text-secondary);
        font-size: 12px;
        margin-top: 8px;
        line-height: 1.4;
    }

    .analysis-insight-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }

    .analysis-panel {
        background: white;
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.07);
        min-width: 0;
    }

    .analysis-panel-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-primary);
        font-size: 15px;
        font-weight: 800;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .analysis-panel-title i {
        color: var(--primary);
    }

    .analysis-panel-copy {
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
        margin: -4px 0 14px;
    }

    .analysis-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .analysis-row {
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 14px;
        padding: 12px;
        background: #ffffff;
    }

    .analysis-row-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        min-width: 0;
    }

    .analysis-row-name {
        color: var(--text-primary);
        font-size: 13px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .analysis-row-meta {
        color: var(--text-secondary);
        font-size: 11px;
        line-height: 1.4;
        margin-top: 4px;
    }

    .analysis-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 5px 10px;
        color: #0f172a;
        background: #e2e8f0;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .analysis-pill.danger {
        color: #991b1b;
        background: #fee2e2;
    }

    .analysis-pill.warning {
        color: #92400e;
        background: #fef3c7;
    }

    .analysis-pill.success {
        color: #065f46;
        background: #d1fae5;
    }

    .analysis-progress {
        height: 7px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 10px;
    }

    .analysis-progress span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--primary), var(--danger));
    }

    .component-detail-table {
        width: 100%;
        min-width: 920px;
        border-collapse: collapse;
        font-size: 13px;
    }

    .component-detail-table th {
        background: var(--dark);
        color: white;
        padding: 12px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .component-detail-table td {
        border-bottom: 1px solid var(--border);
        padding: 12px;
        vertical-align: top;
        color: var(--text-primary);
    }

    .component-chip-stack {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .event-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 12px;
    }

    .event-card {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 12px;
        min-width: 0;
    }

    .analysis-chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }

    .analysis-chart-grid--two {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .analysis-panel--wide {
        grid-column: 1 / -1;
    }

    .mini-chart-container {
        position: relative;
        height: 280px;
        width: 100%;
        min-width: 0;
    }

    .mini-chart-container--bar {
        height: 310px;
    }

    .mini-chart-container--bar-tall {
        height: 340px;
    }

    .mini-chart-container--combined {
        height: 440px;
    }

    .chart-hint {
        color: var(--text-secondary);
        font-size: 12px;
        line-height: 1.4;
        margin-top: 10px;
    }

    .chart-reading-copy {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 8px;
        margin-top: 12px;
    }

    .chart-reading-copy span {
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #f8fafc;
        color: var(--text-secondary);
        font-size: 11px;
        font-weight: 700;
        line-height: 1.35;
        padding: 9px 10px;
    }

    .empty-note {
        color: var(--text-secondary);
        font-size: 13px;
        padding: 16px;
        border: 1px dashed var(--border);
        border-radius: 14px;
        background: #f8fafc;
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

    @media (max-width: 1024px) {
        .executive-brief {
            grid-template-columns: 1fr;
        }

        .executive-window-grid {
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        }

        .analysis-chart-grid--two {
            grid-template-columns: 1fr;
        }

        .mini-chart-container--combined {
            height: 390px;
        }

        .industrial-table {
            min-width: 880px;
        }

        .industrial-table th,
        .industrial-table td {
            padding: 14px 10px;
        }
    }

    @media (max-width: 640px) {
        .analysis-page {
            padding: 20px 12px;
        }

        .analysis-title {
            font-size: 22px;
            line-height: 1.2;
        }

        .analysis-switcher {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        .analysis-switcher a {
            justify-content: center;
            padding: 12px 10px;
            border-radius: 14px;
            width: 100%;
        }

        .industrial-filters,
        .industrial-chart,
        .industrial-empty,
        .analysis-panel {
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .executive-brief {
            margin: 24px 0 20px;
            gap: 14px;
        }

        .executive-status {
            border-radius: 18px;
            padding: 18px;
        }

        .executive-status-title {
            font-size: 23px;
        }

        .executive-window-grid {
            grid-template-columns: 1fr;
        }

        .chart-header--executive {
            grid-template-columns: 1fr;
            grid-template-areas:
                'title'
                'controls'
                'caption';
        }

        .analysis-summary-grid,
        .analysis-insight-grid,
        .analysis-chart-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .mini-chart-container {
            height: 250px;
        }

        .analysis-summary-value {
            font-size: 20px;
        }

        .analysis-row-top {
            flex-direction: column;
            gap: 8px;
        }

        .filters-header,
        .chart-header {
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 12px;
        }

        .filters-header h2,
        .chart-header h3 {
            font-size: 15px;
            line-height: 1.35;
        }

        .machine-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .machine-pill {
            justify-content: center;
            width: 100%;
            min-width: 0;
            padding: 10px 8px;
            border-radius: 14px;
            font-size: 13px;
            text-align: center;
        }

        .industrial-table-container {
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            overflow: visible;
            margin-bottom: 24px;
        }

        .industrial-table {
            min-width: 0;
            border-collapse: separate;
            border-spacing: 0 12px;
            font-size: 13px;
        }

        .industrial-table,
        .industrial-table thead,
        .industrial-table tbody,
        .industrial-table tr,
        .industrial-table td {
            display: block;
            width: 100%;
        }

        .industrial-table thead {
            display: none;
        }

        .industrial-table tbody tr {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .industrial-table tbody tr:hover {
            background: white;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            z-index: auto;
        }

        .industrial-table td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 48px;
            padding: 12px 14px;
            border: 0;
            border-bottom: 1px solid var(--border);
            text-align: right;
        }

        .industrial-table td:last-child {
            border-bottom: 0;
        }

        .industrial-table td::before {
            content: attr(data-label);
            flex: 1 1 auto;
            color: var(--text-secondary);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.25;
            text-align: left;
            text-transform: uppercase;
            white-space: normal;
        }

        .industrial-table td.period-cell {
            display: block;
            text-align: left;
            background: #f8fafc;
        }

        .industrial-table td.period-cell::before {
            display: block;
            margin-bottom: 8px;
        }

        .period-main {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .current-badge {
            margin-left: 0;
        }

        .value-industrial,
        .comparison-industrial,
        .trend-industrial {
            flex: 0 0 auto;
            max-width: 48%;
        }

        .trend-industrial {
            min-width: 0;
            white-space: nowrap;
        }

        .chart-view-selector {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            margin-left: 0;
        }

        .chart-caption {
            width: 100%;
            margin-left: 0;
        }

        .chart-title-block {
            gap: 10px;
        }

        .chart-subcopy {
            font-size: 11px;
        }

        .view-btn {
            width: 100%;
            padding: 10px 12px;
        }

        .chart-container {
            height: 320px;
        }

        .chart-container--executive {
            height: 300px;
        }

        .industrial-empty {
            padding: 32px 18px;
        }

        .empty-icon {
            width: 88px;
            height: 88px;
            font-size: 34px;
            margin-bottom: 18px;
        }

        .industrial-empty h3 {
            font-size: 20px;
            line-height: 1.25;
        }
    }

    @media (max-width: 380px) {
        .analysis-switcher,
        .machine-grid {
            grid-template-columns: 1fr;
        }

        .industrial-table td {
            align-items: flex-start;
            flex-direction: column;
            gap: 6px;
            text-align: left;
        }

        .value-industrial,
        .comparison-industrial,
        .trend-industrial {
            max-width: 100%;
        }
    }
</style>

<div class="analysis-page max-w-7xl mx-auto px-4 py-8">
    {{-- Header Industrial --}}
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 mb-6">
        <div>
            <a href="{{ route('lavadora.dashboard') }}" 
               class="responsive-action responsive-action--secondary mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="analysis-title text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-600"></i>
                {{ $tituloAnalisis }}
            </h1>
        </div>
        <div class="analysis-switcher flex flex-wrap gap-3">
            <a href="{{ route('analisis-tendencia-mensual.lavadora.analisis-52-12-4', $parametrosLinea) }}" 
               class="inline-flex items-center gap-2 px-5 py-3 rounded-full font-semibold transition-all duration-300 {{ !$isAnalisis30147 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-700 border border-gray-200 hover:border-blue-300 hover:text-blue-700' }}">
                <i class="fas fa-chart-line"></i>
                <span>52-12-4</span>
            </a>
            <a href="{{ route('analisis-tendencia-mensual.lavadora.analisis-30-14-7', $parametrosLinea) }}" 
               class="inline-flex items-center gap-2 px-5 py-3 rounded-full font-semibold transition-all duration-300 {{ $isAnalisis30147 ? 'bg-cyan-600 text-white shadow-lg' : 'bg-white text-gray-700 border border-gray-200 hover:border-cyan-300 hover:text-cyan-700' }}">
                <i class="fas fa-chart-bar"></i>
                <span>30-14-7</span>
            </a>
        </div>
    </div>
                  
    {{-- Filtros Industriales --}}
    <div class="industrial-filters">
        <div class="filters-header">
            <img src="{{ asset('images/icono-maquina.png') }}" class="w-10 h-8 " alt="Icono de máquina">
            <h2>SELECCIONAR LÍNEA </h2>
        </div>
        
        <div class="machine-grid">
            @foreach($lineas as $linea)
                <a href="{{ route($rutaAnalisisActiva, ['linea_id' => $linea->id] + $parametrosFecha) }}" 
                   class="machine-pill {{ $lineaSeleccionada == $linea->id ? 'machine-pill-active' : 'machine-pill-inactive' }}">
                    <i class="fas fa-washing-machine"></i>
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route($rutaAnalisisActiva) }}" class="mt-6 grid gap-4 border-t border-gray-200 pt-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
            @if($lineaSeleccionada)
                <input type="hidden" name="linea_id" value="{{ $lineaSeleccionada }}">
            @endif

            <div>
                <label for="fecha_inicio" class="mb-2 block text-xs font-bold uppercase tracking-wide text-gray-500">Fecha inicial</label>
                <input type="date"
                       id="fecha_inicio"
                       name="fecha_inicio"
                       value="{{ $fechaInicio }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('fecha_inicio')
                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_fin" class="mb-2 block text-xs font-bold uppercase tracking-wide text-gray-500">Fecha final</label>
                <input type="date"
                       id="fecha_fin"
                       name="fecha_fin"
                       value="{{ $fechaFin }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('fecha_fin')
                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="create-action create-action--compact">
                    <i class="fas fa-filter"></i>
                    Filtrar
                </button>

                @if($fechaInicio || $fechaFin)
                    <a href="{{ route($rutaAnalisisActiva, array_filter(['linea_id' => $lineaSeleccionada], fn ($value) => filled($value))) }}"
                       class="create-action create-action--compact create-action--secondary">
                        <i class="fas fa-rotate-left"></i>
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($lineaSeleccionada && $detalleLinea)
        @php
            $totalPeriodo = (int) ($resumenDetalle['total_periodo'] ?? $resumenDetalle['total_fallas'] ?? 0);
            $totalGlobal = (int) ($globalDetalle['total_fallas'] ?? 0);
            $participacion = (float) ($resumenDetalle['participacion_global'] ?? 0);
            $componenteCritico = $resumenDetalle['componente_critico'] ?? ($componentesDetalle->first()['componente'] ?? 'Sin componente');
            $componenteCriticoTotal = (int) ($resumenDetalle['componente_critico_total'] ?? ($componentesDetalle->first()['total'] ?? 0));
            $danoFrecuente = $resumenDetalle['dano_mas_frecuente'] ?? ($danosDetalle->first()['estado'] ?? 'Sin daño');
            $danoFrecuenteTotal = (int) ($resumenDetalle['dano_mas_frecuente_total'] ?? ($danosDetalle->first()['total'] ?? 0));
            $ultimoMesTotal = (int) ($resumenDetalle['ultimo_mes_total'] ?? 0);
            $variacionMensual = $resumenDetalle['variacion_mensual'] ?? null;
            $deltaMensual = (float) ($variacionMensual['diferencia'] ?? 0);
            $tonoDano = function ($estado) {
                $normalizado = strtolower(\Illuminate\Support\Str::ascii((string) $estado));
                if (str_contains($normalizado, 'requiere cambio') || str_contains($normalizado, 'danado')) {
                    return 'danger';
                }
                if (str_contains($normalizado, 'desgaste')) {
                    return 'warning';
                }
                return 'success';
            };
        @endphp

        <div class="analysis-summary-grid">
            <div class="analysis-summary-card {{ $totalPeriodo > 0 ? 'danger' : 'success' }}">
                <div class="analysis-summary-label">Total de daños</div>
                <div class="analysis-summary-value">{{ number_format($totalPeriodo) }}</div>
                <div class="analysis-summary-meta">{{ $periodoDetalle }}</div>
            </div>

            <div class="analysis-summary-card warning">
                <div class="analysis-summary-label">Componente critico</div>
                <div class="analysis-summary-value">{{ $componenteCritico }}</div>
                <div class="analysis-summary-meta">{{ number_format($componenteCriticoTotal) }} daños registrados</div>
            </div>

            <div class="analysis-summary-card danger">
                <div class="analysis-summary-label">Daño mas frecuente</div>
                <div class="analysis-summary-value">{{ $danoFrecuente }}</div>
            </div>

            <div class="analysis-summary-card">
                <div class="analysis-summary-label">Porcentaje global</div>
                <div class="analysis-summary-value">{{ number_format($participacion, 1) }}%</div>
            </div>

            <div class="analysis-summary-card {{ $deltaMensual > 0 ? 'danger' : ($deltaMensual < 0 ? 'success' : '') }}">
                <div class="analysis-summary-label">Mes actual</div>
                <div class="analysis-summary-value">{{ number_format($ultimoMesTotal) }}</div>
                <div class="analysis-summary-meta">
                    @if($variacionMensual)
                        {{ $deltaMensual > 0 ? '+' : '' }}{{ number_format($deltaMensual) }} vs mes anterior
                        ({{ number_format((float) ($variacionMensual['porcentaje'] ?? 0), 1) }}%)
                    @else
                        Sin comparativo mensual
                    @endif
                </div>
            </div>
        </div>

        <div class="analysis-chart-grid analysis-chart-grid--two">
            <div class="analysis-panel">
                <div class="analysis-panel-title">
                    <i class="fas fa-chart-bar"></i>
                    {{ $tituloAnalisis }} - componentes criticos
                </div>
                <div class="analysis-panel-copy">

                </div>
                <div class="mini-chart-container mini-chart-container--bar mini-chart-container--bar-tall">
                    <canvas id="componentBarChart"></canvas>
                </div>
            </div>

            <div class="analysis-panel">
                <div class="analysis-panel-title">
                    <i class="fas fa-triangle-exclamation"></i>
                    {{ $tituloAnalisis }} - tipos de daños frecuentes
                </div>

                <div class="mini-chart-container mini-chart-container--bar mini-chart-container--bar-tall">
                    <canvas id="damageBarChart"></canvas>
                </div>
            </div>
        </div>

        <div class="analysis-chart-grid">
            <div class="analysis-panel analysis-panel--wide">
                <div class="analysis-panel-title">
                    <i class="fas fa-layer-group"></i>
                    {{ $tituloAnalisis }} - daños por componente
                </div>

                <div class="mini-chart-container mini-chart-container--bar mini-chart-container--combined">
                    <canvas id="damageComponentChart"></canvas>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabla de Tendencias --}}
    @if($lineaSeleccionada)
        @if($analisis->isNotEmpty())
            @php
                $analisisOrdenados = $analisis->sortByDesc(function($item) {
                    return $item->anio . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT);
                });
            @endphp
            <div class="executive-brief">
                <div id="implementationStatusCard" class="executive-status executive-status--neutral">
                    <div class="executive-eyebrow">Control inmediato</div>
                    <div id="implementationStatusTitle" class="executive-status-title">Leyendo tendencia reciente...</div>
                    <p id="implementationStatusCopy" class="executive-status-copy">
                        Estamos comparando las ventanas recientes para mostrar si los daños van bajando y si la implementacion ya se refleja en la operacion.
                    </p>
                </div>
                <div id="executiveWindowCards" class="executive-window-grid"></div>
            </div>
            <div class="industrial-chart">
                <div class="chart-header chart-header--executive">
                    <div class="chart-title-block">
                        <i class="fas fa-chart-line"></i>
                        <div class="chart-title-copy">
                            <h3>{{ strtoupper($tituloAnalisis) }}</h3>
                        </div>
                    </div>
                    <div class="chart-view-selector">
                        <button class="view-btn active" data-executive-chart-type="bar" onclick="changeExecutiveChartType('bar')">Barras</button>
                        <button class="view-btn" data-executive-chart-type="line" onclick="changeExecutiveChartType('line')">Línea</button>
                    </div>
                    <div id="executiveChartCaption" class="chart-caption"></div>
                </div>
                <div class="chart-container chart-container--executive">
                    <canvas id="windowTrendChart"></canvas>
                </div>
            </div>
            @if(!$isAnalisis30147)
            <div class="industrial-table-container">
                <table class="industrial-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="border-r border-gray-600">PERIODO</th>
                            <th colspan="3" class="group-header border-r border-gray-600">52 SEMANAS</th>
                            <th colspan="3" class="group-header border-r border-gray-600">12 SEMANAS</th>
                            <th colspan="3" class="group-header">4 SEMANAS</th>
                        </tr>
                        <tr>
                            <th class="bg-gray-300">TOTAL</th>
                            <th class="bg-gray-500">VS MES ANT</th>
                            <th class="bg-gray-500">TENDENCIA</th>
                            <th class="bg-gray-500">TOTAL</th>
                            <th class="bg-gray-500">VS MES ANT</th>
                            <th class="bg-gray-500">TENDENCIA</th>
                            <th class="bg-gray-500">TOTAL</th>
                            <th class="bg-gray-500">VS MES ANT</th>
                            <th class="bg-gray-500">TENDENCIA</th>
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
                                <td class="period-cell" data-label="Periodo">
                                    <div class="period-main">
                                        {{ $item->mesNombre }} {{ $item->anio }}
                                        @if($loop->first)
                                            <span class="current-badge">ACTUAL</span>
                                        @endif
                                    </div>
                                   
                                </td>

                                {{-- 52 Semanas --}}
                                <td class="value-industrial" data-label="52 semanas - Total">{{ number_format($item->total_danos_52_semanas, 2) }}</td>
                                <td class="comparison-industrial" data-label="52 semanas - Vs mes ant">
                                    @if($variacion52)
                                        <span style="color: {{ $variacion52['diferencia'] > 0 ? 'var(--danger)' : ($variacion52['diferencia'] < 0 ? 'var(--success)' : 'var(--warning)') }}">
                                            {{ $variacion52['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion52['diferencia'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td data-label="52 semanas - Tendencia">
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
                                <td class="value-industrial" data-label="12 semanas - Total">{{ number_format($item->total_danos_12_semanas, 2) }}</td>
                                <td class="comparison-industrial" data-label="12 semanas - Vs mes ant">
                                    @if($variacion12)
                                        <span style="color: {{ $variacion12['diferencia'] > 0 ? 'var(--danger)' : ($variacion12['diferencia'] < 0 ? 'var(--success)' : 'var(--warning)') }}">
                                            {{ $variacion12['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion12['diferencia'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td data-label="12 semanas - Tendencia">
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
                                <td class="value-industrial" data-label="4 semanas - Total">{{ number_format($item->total_danos_4_semanas, 2) }}</td>
                                <td class="comparison-industrial" data-label="4 semanas - Vs mes ant">
                                    @if($variacion4)
                                        <span style="color: {{ $variacion4['diferencia'] > 0 ? 'var(--danger)' : ($variacion4['diferencia'] < 0 ? 'var(--success)' : 'var(--warning)') }}">
                                            {{ $variacion4['diferencia'] > 0 ? '+' : '' }}{{ number_format($variacion4['diferencia'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td data-label="4 semanas - Tendencia">
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
            @endif
            @if($isAnalisis30147)
            <div class="industrial-table-container">
                <table class="industrial-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="border-r border-gray-600">PERIODO</th>
                            <th colspan="3" class="group-header border-r border-gray-600">30 DIAS</th>
                            <th colspan="3" class="group-header border-r border-gray-600">14 DIAS</th>
                            <th colspan="3" class="group-header">7 DIAS</th>
                        </tr>
                        <tr>
                            <th class="bg-gray-300">TOTAL</th>
                            <th class="bg-gray-500">VS MES ANT</th>
                            <th class="bg-gray-500">TENDENCIA</th>
                            <th class="bg-gray-500">TOTAL</th>
                            <th class="bg-gray-500">VS MES ANT</th>
                            <th class="bg-gray-500">TENDENCIA</th>
                            <th class="bg-gray-500">TOTAL</th>
                            <th class="bg-gray-500">VS MES ANT</th>
                            <th class="bg-gray-500">TENDENCIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analisisOrdenados as $item)
                            @php
                                $variacion30 = $item->variacion_30_dias;
                                $variacion14 = $item->variacion_14_dias;
                                $variacion7 = $item->variacion_7_dias;
                            @endphp
                            <tr>
                                <td class="period-cell" data-label="Periodo">
                                    <div class="period-main">
                                        {{ $item->mesNombre }} {{ $item->anio }}
                                        @if($loop->first)
                                            <span class="current-badge">ACTUAL</span>
                                        @endif
                                    </div>
                                </td>

                                @foreach([
                                    ['valor' => $item->total_danos_30_dias, 'variacion' => $variacion30],
                                    ['valor' => $item->total_danos_14_dias, 'variacion' => $variacion14],
                                    ['valor' => $item->total_danos_7_dias, 'variacion' => $variacion7],
                                ] as $metrica)
                                    <td class="value-industrial" data-label="{{ [0 => '30 dias - Total', 1 => '14 dias - Total', 2 => '7 dias - Total'][$loop->index] }}">{{ number_format($metrica['valor'], 2) }}</td>
                                    <td class="comparison-industrial" data-label="{{ [0 => '30 dias - Vs mes ant', 1 => '14 dias - Vs mes ant', 2 => '7 dias - Vs mes ant'][$loop->index] }}">
                                        @if($metrica['variacion'])
                                            <span style="color: {{ $metrica['variacion']['diferencia'] > 0 ? 'var(--danger)' : ($metrica['variacion']['diferencia'] < 0 ? 'var(--success)' : 'var(--warning)') }}">
                                                {{ $metrica['variacion']['diferencia'] > 0 ? '+' : '' }}{{ number_format($metrica['variacion']['diferencia'], 2) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ [0 => '30 dias - Tendencia', 1 => '14 dias - Tendencia', 2 => '7 dias - Tendencia'][$loop->index] }}">
                                        @if($metrica['variacion'])
                                            @php
                                                $clase = $metrica['variacion']['tendencia'] == 'up' ? 'trend-up-industrial' : ($metrica['variacion']['tendencia'] == 'down' ? 'trend-down-industrial' : 'trend-stable-industrial');
                                                $icono = $metrica['variacion']['tendencia'] == 'up' ? 'fa-arrow-up' : ($metrica['variacion']['tendencia'] == 'down' ? 'fa-arrow-down' : 'fa-minus');
                                            @endphp
                                            <span class="trend-industrial {{ $clase }}">
                                                <i class="fas {{ $icono }}"></i>
                                                {{ $metrica['variacion']['porcentaje'] > 0 ? '+' : '' }}{{ number_format($metrica['variacion']['porcentaje'], 2) }}%
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @else
            {{-- Empty State Industrial --}}
            <div class="industrial-empty">
                <div class="empty-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>SIN DATOS DISPONIBLES</h3>
                <p>No se encontraron análisis para {{ $lineas->find($lineaSeleccionada)?->nombre }}</p>
                <a href="{{ route($rutaAnalisisActiva, $parametrosLinea) }}" 
                   class="btn-industrial">
                    <i class="fas fa-sync-alt"></i>
                    VER TENDENCIA AUTOMATICA
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
let componentBarChart, damageBarChart, damageComponentChart, windowTrendChart;

document.addEventListener('DOMContentLoaded', function() {
    if (window.ChartDataLabels) {
        Chart.register(ChartDataLabels);
    }

    const analisisData = @json($analisis);
    const detalleData = @json($detalleLinea ?? []);
    const graphData = detalleData.graficas || {};
    
    const datosOrdenados = [...analisisData].sort((a, b) => {
        if (a.anio !== b.anio) return a.anio - b.anio;
        return a.mes - b.mes;
    });
    
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const labels = datosOrdenados.map(item => meses[item.mes - 1] + ' ' + item.anio);
    
    const data52 = datosOrdenados.map(item => parseFloat(item.total_danos_52_semanas) || 0);
    const data12 = datosOrdenados.map(item => parseFloat(item.total_danos_12_semanas) || 0);
    const data4 = datosOrdenados.map(item => parseFloat(item.total_danos_4_semanas) || 0);
    const data30 = datosOrdenados.map(item => parseFloat(item.total_danos_30_dias) || 0);
    const data14 = datosOrdenados.map(item => parseFloat(item.total_danos_14_dias) || 0);
    const data7 = datosOrdenados.map(item => parseFloat(item.total_danos_7_dias) || 0);
    let currentExecutiveChartType = 'bar';
    const componentPalette = [
        ['#1d4ed8', 'rgba(37, 99, 235, 0.95)'],
        ['#dc2626', 'rgba(239, 68, 68, 0.95)'],
        ['#059669', 'rgba(16, 185, 129, 0.95)'],
        ['#d97706', 'rgba(245, 158, 11, 0.95)'],
        ['#6d28d9', 'rgba(124, 58, 237, 0.92)'],
        ['#0e7490', 'rgba(14, 165, 233, 0.92)'],
        ['#be185d', 'rgba(219, 39, 119, 0.92)'],
        ['#475569', 'rgba(100, 116, 139, 0.9)']
    ];

    function normalizeStatusText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function indicatorChartPalette(label) {
        const normalized = normalizeStatusText(label);

        if (normalized.includes('requiere cambio') || normalized.includes('elongacion')) {
            return {
                background: 'rgba(239, 68, 68, 0.92)',
                border: '#dc2626',
                text: '#ffffff'
            };
        }

        if (normalized.includes('requiere revision')) {
            return {
                background: 'rgba(245, 158, 11, 0.92)',
                border: '#d97706',
                text: '#ffffff'
            };
        }

        if (normalized.includes('desgaste sever') || normalized.includes('desgaste moder')) {
            return {
                background: 'rgba(249, 115, 22, 0.92)',
                border: '#ea580c',
                text: '#ffffff'
            };
        }

        if (normalized.includes('cambiado')) {
            return {
                background: 'rgba(59, 130, 246, 0.92)',
                border: '#2563eb',
                text: '#ffffff'
            };
        }

        if (normalized.includes('buen estado') || normalized.includes('estable')) {
            return {
                background: 'rgba(16, 185, 129, 0.9)',
                border: '#059669',
                text: '#ffffff'
            };
        }

        return {
            background: 'rgba(100, 116, 139, 0.9)',
            border: '#475569',
            text: '#ffffff'
        };
    }

    function formatList(items, labelKey, limit = 3) {
        if (!Array.isArray(items) || !items.length) {
            return null;
        }

        return items
            .slice(0, limit)
            .map((item) => `${item[labelKey] || 'Sin dato'} (${Number(item.total || 0)})`)
            .join(', ');
    }

    function responsiveTooltipOptions() {
        const isMobile = window.innerWidth < 640;

        return {
            titleFont: { size: isMobile ? 15 : 17, weight: '700', family: 'Inter' },
            bodyFont: { size: isMobile ? 14 : 16, weight: '500', family: 'Inter' },
            padding: isMobile ? 10 : 14,
            cornerRadius: 10,
            titleSpacing: 4,
            titleMarginBottom: isMobile ? 5 : 7,
            bodySpacing: isMobile ? 3 : 5
        };
    }

    function wrapTooltipLines(lines) {
        const maxCharacters = window.innerWidth < 480 ? 32 : (window.innerWidth < 768 ? 46 : 72);
        const sourceLines = Array.isArray(lines) ? lines : [lines];

        return sourceLines.flatMap((line) => {
            const words = String(line ?? '').split(/\s+/);
            const wrapped = [];
            let currentLine = '';

            words.forEach((word) => {
                const candidate = currentLine ? `${currentLine} ${word}` : word;
                if (candidate.length > maxCharacters && currentLine) {
                    wrapped.push(currentLine);
                    currentLine = word;
                } else {
                    currentLine = candidate;
                }
            });

            if (currentLine) wrapped.push(currentLine);
            return wrapped;
        });
    }

    function componentTooltipLines(meta, options = {}) {
        const lines = [];

        if (!meta) {
            return lines;
        }

        if (meta.porcentaje !== undefined) lines.push(`Participacion: ${Number(meta.porcentaje || 0).toFixed(1)}%`);
        if (meta.ultima_falla) lines.push(`Ultima falla: ${meta.ultima_falla}`);

        const ubicaciones = options.currentUbicacion
            ? options.currentUbicacion
            : formatList(meta.ubicaciones, 'ubicacion');
        if (ubicaciones) lines.push(`Ubicaciones: ${ubicaciones}`);

        const danos = formatList(meta.danos, 'estado');
        if (danos) lines.push(`Daños: ${danos}`);

        return lines;
    }

    function damageTooltipLines(meta) {
        const lines = [];

        if (!meta) {
            return lines;
        }

        if (meta.porcentaje !== undefined) lines.push(`Participacion: ${Number(meta.porcentaje || 0).toFixed(1)}%`);
        if (meta.ultima_falla) lines.push(`Ultima falla: ${meta.ultima_falla}`);
        if (meta.historial_total !== undefined) {
            lines.push(`Historial del periodo: ${Number(meta.historial_total || 0)} registros`);
        }
        if (meta.componentes_afectados !== undefined) {
            lines.push(`Componentes afectados: ${Number(meta.componentes_afectados || 0)}`);
        }

        const componentes = formatList(meta.componentes, 'componente', 4);
        if (componentes) lines.push(`Componentes: ${componentes}`);

        return lines;
    }

    function locationTooltipLines(meta) {
        const lines = [];

        if (!meta) {
            return lines;
        }

        const componentes = formatList(meta.componentes, 'componente', 4);
        if (componentes) lines.push(`Componentes: ${componentes}`);

        const danos = formatList(meta.danos, 'estado', 3);
        if (danos) lines.push(`Daños principales: ${danos}`);

        return lines;
    }
    function createPieCharts() {
        if (componentBarChart) componentBarChart.destroy();
        if (damageBarChart) damageBarChart.destroy();
        if (damageComponentChart) damageComponentChart.destroy();
        if (windowTrendChart) windowTrendChart.destroy();

        componentBarChart = createHorizontalBarChart('componentBarChart', graphData.barras_componentes_totales || {}, '', {
            datasetLabel: 'Daños por componente'
        });
        damageBarChart = createHorizontalBarChart('damageBarChart', graphData.pastel_danos || {}, '', {
            datasetLabel: 'Daños por tipo de daño',
            damageMode: true
        });
        damageComponentChart = createStackedHorizontalBarChart('damageComponentChart', graphData.barras_danos_componentes || {}, '');
        windowTrendChart = createWindowTrendChart();
    }

    function createHorizontalBarChart(canvasId, chartData, title, options = {}) {
        const canvas = document.getElementById(canvasId);
        const values = Array.isArray(chartData?.data) ? chartData.data.map(value => Number(value || 0)) : [];

        if (!canvas || !values.some(value => value > 0)) {
            return null;
        }

        const isDamageChart = Boolean(options.damageMode) || canvasId.toLowerCase().includes('damagebar');
        const labels = Array.isArray(chartData?.labels) ? chartData.labels : [];
        const indicatorKeys = labels.map((label, index) => {
            if (isDamageChart) {
                return chartData.meta?.[index]?.estado || label;
            }

            return chartData.principal?.[index]
                || chartData.meta?.[index]?.dano_principal
                || chartData.meta?.[index]?.danos?.[0]?.estado
                || '';
        });
        const palettes = indicatorKeys.map((label) => indicatorChartPalette(label));

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: options.datasetLabel || (isDamageChart ? 'Daños por tipo de daño' : 'Daños por componente'),
                    data: values,
                    backgroundColor: palettes.map((item) => item.background),
                    borderColor: palettes.map((item) => item.border),
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    minBarLength: 6,
                    maxBarThickness: 34
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: title,
                        color: '#0f172a',
                        font: { size: 12, weight: '800' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        ...responsiveTooltipOptions(),
                        callbacks: {
                            label: (context) => {
                                const meta = chartData.meta?.[context.dataIndex] || null;
                                const pct = chartData.porcentajes?.[context.dataIndex] ?? meta?.porcentaje ?? null;
                                const principal = chartData.principal?.[context.dataIndex] || null;

                                if (isDamageChart) {
                                    return wrapTooltipLines(pct !== null
                                        ? `${context.raw} componentes/ubicaciones con este ultimo daño (${Number(pct || 0).toFixed(1)}%)`
                                        : `${context.raw} componentes/ubicaciones con este ultimo daño`);
                                }

                                return wrapTooltipLines(principal ? `${context.raw} daños - ${principal}` : `${context.raw} daños`);
                            },
                            afterLabel: (context) => {
                                const meta = chartData.meta?.[context.dataIndex] || null;

                                if (isDamageChart) {
                                    return wrapTooltipLines(damageTooltipLines(meta));
                                }

                                return wrapTooltipLines(canvasId.toLowerCase().includes('location')
                                    ? locationTooltipLines(meta)
                                    : componentTooltipLines(meta));
                            }
                        }
                    },
                    datalabels: {
                        display: (context) => Number(context.raw || 0) > 0,
                        formatter: (value) => Number(value || 0),
                        color: (context) => palettes[context.dataIndex]?.text || '#ffffff',
                        font: { size: 10, weight: '800' }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                        ticks: { precision: 0, color: '#64748b' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: '#334155',
                            font: { size: 10, weight: '700' }
                        }
                    }
                }
            }
        });
    }

    function createStackedHorizontalBarChart(canvasId, chartData, title) {
        const canvas = document.getElementById(canvasId);
        const componentLabels = Array.isArray(chartData?.labels) ? chartData.labels : [];
        const series = Array.isArray(chartData?.series) ? chartData.series : [];
        const metaComponents = Array.isArray(chartData?.meta?.componentes) ? chartData.meta.componentes : [];
        const hasData = series.some(serie => Array.isArray(serie.data) && serie.data.some(value => Number(value || 0) > 0));

        if (!canvas || !componentLabels.length || !hasData) {
            return null;
        }

        const reducerLabels = series.map((serie, index) => serie.label || `Reductor ${index + 1}`);
        const datasets = componentLabels
            .map((componentLabel, componentIndex) => {
                const data = series.map((serie) => Number(serie.data?.[componentIndex] || 0));
                const segmentMeta = series.map((serie) => serie.meta?.[componentIndex] || null);
                const componentMeta = metaComponents[componentIndex] || null;
                const indicatorKey = componentMeta?.dano_principal
                    || segmentMeta.find((item) => item?.ultimo_dano)?.ultimo_dano
                    || segmentMeta.find((item) => Array.isArray(item?.danos) && item.danos.length)?.danos?.[0]?.estado
                    || '';
                const palette = indicatorChartPalette(indicatorKey);

                return {
                    label: componentLabel || `Componente ${componentIndex + 1}`,
                    data,
                    backgroundColor: palette.background,
                    borderColor: palette.border,
                    borderWidth: 2,
                    borderRadius: 7,
                    borderSkipped: false,
                    stack: 'fallas',
                    componentIndex,
                    textColor: palette.text,
                    segmentMeta
                };
            })
            .filter((dataset) => dataset.data.some((value) => value > 0));

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: reducerLabels,
                datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            boxWidth: 10,
                            color: '#334155',
                            font: { size: 10, weight: '700' }
                        }
                    },
                    title: {
                        display: true,
                        text: title,
                        color: '#0f172a',
                        font: { size: 12, weight: '800' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        ...responsiveTooltipOptions(),
                        callbacks: {
                            title: (context) => {
                                return context[0]?.label || 'Sin reductor';
                            },
                            label: (context) => wrapTooltipLines(`${context.dataset.label || 'Sin componente'}: ${Number(context.raw || 0)} daños`),
                            afterLabel: (context) => {
                                const componentIndex = context.dataset.componentIndex;
                                const componentMeta = chartData.meta?.componentes?.[componentIndex] || null;
                                const segmentMeta = context.dataset.segmentMeta?.[context.dataIndex] || null;
                                const lines = [];

                                if (segmentMeta?.ultimo_dano) lines.push(`Ultimo daño: ${segmentMeta.ultimo_dano}`);
                                if (segmentMeta?.ultima_falla) lines.push(`Ultimo registro: ${segmentMeta.ultima_falla}`);

                                const danos = formatList(segmentMeta?.danos, 'estado');
                                if (danos) lines.push(`Daños en este reductor: ${danos}`);

                                return wrapTooltipLines([
                                    ...lines,
                                    ...componentTooltipLines(componentMeta, {
                                        currentUbicacion: segmentMeta?.ubicacion || null
                                    })
                                ]);
                            }
                        }
                    },
                    datalabels: {
                        display: (context) => Number(context.raw || 0) > 0,
                        formatter: (value) => Number(value || 0),
                        color: (context) => context.dataset.textColor || '#ffffff',
                        font: { size: 10, weight: '800' }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        stacked: true,
                        grid: { color: 'rgba(148, 163, 184, 0.14)' },
                        title: {
                            display: true,
                            text: 'Total de daños',
                            color: '#64748b',
                            font: { size: 12, weight: '800' }
                        },
                        ticks: {
                            precision: 0,
                            color: '#334155',
                            font: { size: 10, weight: '700' }
                        }
                    },
                    y: {
                        stacked: true,
                        grid: { color: 'rgba(148, 163, 184, 0.14)' },
                        title: {
                            display: true,
                            text: 'Reductores / ubicaciones',
                            color: '#64748b',
                            font: { size: 12, weight: '800' }
                        },
                        ticks: {
                            color: '#334155',
                            font: { size: 10, weight: '700' }
                        }
                    }
                }
            }
        });
    }

    const executiveWindows = @json($isAnalisis30147)
        ? [
            { label: '30 dias', data: data30, role: 'referencia operativa', color: '#16a34a', fill: 'rgba(34, 197, 94, 0.18)', dashed: true },
            { label: '14 dias', data: data14, role: 'respuesta reciente', color: '#dc2626', fill: 'rgba(239, 68, 68, 0.16)' },
            { label: '7 dias', data: data7, role: 'control inmediato', color: '#f97316', fill: 'rgba(249, 115, 22, 0.18)' }
        ]
        : [
            { label: '52 semanas', data: data52, role: 'historico acumulado', color: '#16a34a', fill: 'rgba(34, 197, 94, 0.18)', dashed: true },
            { label: '12 semanas', data: data12, role: 'impacto trimestral', color: '#dc2626', fill: 'rgba(239, 68, 68, 0.16)' },
            { label: '4 semanas', data: data4, role: 'control inmediato', color: '#f97316', fill: 'rgba(249, 115, 22, 0.18)' }
        ];

    const executiveMobileQuery = window.matchMedia('(max-width: 640px)');

    function formatMetric(value) {
        return new Intl.NumberFormat('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(Number(value || 0));
    }

    function compareSeries(series) {
        const current = Number(series[series.length - 1] || 0);
        const previous = Number(series[series.length - 2] || 0);
        const diff = current - previous;
        const percentage = previous === 0 ? null : (diff / previous) * 100;

        return {
            current,
            previous,
            diff,
            percentage,
            trend: diff < 0 ? 'down' : (diff > 0 ? 'up' : 'stable')
        };
    }

    function zeroStreak(series) {
        let streak = 0;

        for (let index = series.length - 1; index >= 0; index -= 1) {
            if (Number(series[index] || 0) !== 0) {
                break;
            }

            streak += 1;
        }

        return streak;
    }

    function deltaChipCopy(delta) {
        if (delta.diff < 0) {
            const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
            return `Bajo ${formatMetric(Math.abs(delta.diff))}${pct}`;
        }

        if (delta.diff > 0) {
            const pct = delta.percentage === null ? '' : ` (${Math.abs(delta.percentage).toFixed(1)}%)`;
            return `Subio ${formatMetric(delta.diff)}${pct}`;
        }

        return 'Sin cambio';
    }

    function renderExecutiveCards() {
        const cardsHost = document.getElementById('executiveWindowCards');

        if (!cardsHost) {
            return;
        }

        cardsHost.innerHTML = executiveWindows.map((windowItem) => {
            const delta = compareSeries(windowItem.data);
            const deltaClass = delta.diff < 0
                ? 'executive-window-delta--positive'
                : (delta.diff > 0 ? 'executive-window-delta--alert' : 'executive-window-delta--neutral');
            const deltaIcon = delta.diff < 0
                ? 'fa-arrow-down'
                : (delta.diff > 0 ? 'fa-arrow-up' : 'fa-minus');

            return `
                <article class="executive-window-card" style="--window-accent: ${windowItem.color}">
                    <div class="executive-window-label">${windowItem.label}</div>
                    <div class="executive-window-value">${formatMetric(delta.current)}</div>
                    <div class="executive-window-role">${windowItem.role}</div>
                    <div class="executive-window-delta ${deltaClass}">
                        <i class="fas ${deltaIcon}"></i>
                        <span>${deltaChipCopy(delta)} vs mes anterior</span>
                    </div>
                </article>
            `;
        }).join('');
    }

    function renderExecutiveStatus() {
        const statusCard = document.getElementById('implementationStatusCard');
        const titleNode = document.getElementById('implementationStatusTitle');
        const copyNode = document.getElementById('implementationStatusCopy');
        const captionNode = document.getElementById('executiveChartCaption');

        if (!statusCard || !titleNode || !copyNode || !captionNode) {
            return;
        }

        const baseWindow = executiveWindows[0];
        const midWindow = executiveWindows[1];
        const recentWindow = executiveWindows[2];
        const midDelta = compareSeries(midWindow.data);
        const recentDelta = compareSeries(recentWindow.data);
        const recentZeroRun = zeroStreak(recentWindow.data);
        const latestLabel = labels[labels.length - 1] || 'periodo actual';

        let tone = 'neutral';
        let title = 'Monitoreo en curso';
        let copy = `${recentWindow.label} no presenta repunte, pero todavia se requiere continuidad para que ${baseWindow.label} refleje una mejora mas amplia.`;

        if ((recentWindow.data.length > 1 && recentDelta.diff <= 0 && midDelta.diff < 0) || (recentDelta.current === 0 && recentZeroRun >= 2 && midDelta.diff <= 0)) {
            tone = 'positive';

            if (recentDelta.current === 0 && recentZeroRun >= 2) {
                title = 'Implementacion funcionando';
                copy = `${recentWindow.label} se mantiene en 0 daños durante ${recentZeroRun} periodos y ${midWindow.label} sigue bajando frente al mes anterior.`;
            } else {
                title = 'Tendencia de baja confirmada';
                copy = `${recentWindow.label} ${deltaChipCopy(recentDelta).toLowerCase()} y ${midWindow.label} tambien viene a la baja.`;
            }
        } else if (recentDelta.diff > 0 || midDelta.diff > 0) {
            tone = 'alert';
            title = 'Repunte reciente';
            copy = `${recentWindow.label} o ${midWindow.label} subieron frente al mes anterior. Conviene revisar la ejecucion antes de que el historico vuelva a crecer.`;
        }

        statusCard.className = `executive-status executive-status--${tone}`;
        titleNode.textContent = title;
        copyNode.textContent = copy;
        captionNode.textContent = `Corte actual: ${latestLabel}.`;
    }

    function createWindowTrendChart(type = currentExecutiveChartType) {
        const canvas = document.getElementById('windowTrendChart');
        const executiveIsBar = type === 'bar';
        const executiveIsSmallScreen = executiveMobileQuery.matches;

        if (!canvas || !labels.length) {
            return null;
        }

        renderExecutiveCards();
        renderExecutiveStatus();

        return new Chart(canvas.getContext('2d'), {
            type,
            data: {
                labels,
                datasets: executiveWindows.map((windowItem, datasetIndex) => ({
                    label: windowItem.label,
                    data: windowItem.data.map((value) => Number(value || 0)),
                    borderColor: windowItem.color,
                    backgroundColor: windowItem.fill,
                    borderWidth: executiveIsBar ? 0 : (datasetIndex === executiveWindows.length - 1 ? 4 : 3),
                    borderDash: executiveIsBar ? [] : (windowItem.dashed ? [8, 6] : []),
                    pointBackgroundColor: windowItem.color,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: executiveIsBar ? 0 : ((context) => context.dataIndex === labels.length - 1 ? 5 : 3),
                    pointHoverRadius: 7,
                    tension: 0.32,
                    fill: executiveIsBar ? false : datasetIndex === executiveWindows.length - 1,
                    order: datasetIndex,
                    borderRadius: executiveIsBar ? 10 : 0,
                    borderSkipped: executiveIsBar ? false : undefined,
                    barPercentage: executiveIsBar ? 0.72 : undefined,
                    categoryPercentage: executiveIsBar ? 0.74 : undefined,
                    maxBarThickness: executiveIsBar ? 28 : undefined
                }))
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
                        position: executiveIsBar ? 'bottom' : (executiveIsSmallScreen ? 'bottom' : 'top'),
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            padding: executiveIsBar ? 14 : (executiveIsSmallScreen ? 12 : 18),
                            color: '#334155',
                            font: {
                                size: executiveIsSmallScreen ? 10 : 12,
                                weight: '700'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            label: (context) => {
                                const currentValue = Number(context.parsed.y || 0);
                                const previousValue = Number(context.dataset.data?.[context.dataIndex - 1] || 0);
                                const diff = currentValue - previousValue;
                                const change = context.dataIndex === 0
                                    ? 'Sin comparativo anterior'
                                    : (diff < 0
                                        ? `Bajo ${formatMetric(Math.abs(diff))}`
                                        : (diff > 0 ? `Subio ${formatMetric(diff)}` : 'Sin cambio'));

                                return `${context.dataset.label}: ${formatMetric(currentValue)} daños. ${change}.`;
                            }
                        }
                    },
                    datalabels: executiveIsBar ? {
                        display: (context) => Number(context.raw || 0) > 0,
                        anchor: 'end',
                        align: 'top',
                        offset: 8,
                        clamp: true,
                        clip: false,
                        color: '#ffffff',
                        backgroundColor: (context) => context.dataset.borderColor,
                        borderRadius: 999,
                        padding: {
                            top: 4,
                            right: 8,
                            bottom: 4,
                            left: 8
                        },
                        formatter: (value) => formatMetric(value),
                        font: {
                            size: executiveIsSmallScreen ? 9 : 11,
                            weight: '800'
                        }
                    } : {
                        display: (context) => !executiveIsSmallScreen && context.dataIndex === labels.length - 1 && Number(context.raw || 0) > 0,
                        anchor: 'end',
                        align: 'top',
                        offset: 6,
                        clamp: true,
                        clip: false,
                        color: (context) => context.dataset.borderColor,
                        backgroundColor: null,
                        borderRadius: 0,
                        padding: 0,
                        formatter: (value) => formatMetric(value),
                        textStrokeColor: 'rgba(255, 255, 255, 0.98)',
                        textStrokeWidth: 2,
                        font: {
                            size: 10,
                            weight: '800'
                        }
                    }
                },
                layout: {
                    padding: {
                        top: executiveIsBar ? 72 : 14,
                        right: 8,
                        bottom: executiveIsBar ? 10 : 4,
                        left: 4
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#475569',
                            maxRotation: executiveIsSmallScreen ? 50 : 0,
                            minRotation: executiveIsSmallScreen ? 50 : 0,
                            font: {
                                size: executiveIsSmallScreen ? 10 : 11,
                                weight: '700'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grace: executiveIsBar ? '36%' : '8%',
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)'
                        },
                        title: {
                            display: true,
                            text: 'Daños registrados',
                            color: '#64748b',
                            font: {
                                size: 12,
                                weight: '800'
                            }
                        },
                        ticks: {
                            color: '#64748b',
                            callback: (value) => formatMetric(value)
                        }
                    }
                }
            }
        });

        const latestIndex = labels.length - 1;
        const latestLabel = labels[latestIndex] || 'Periodo actual';
        const serviceWindowData = graphData.barras_ventanas || {};
        const windowMeta = Array.isArray(serviceWindowData.meta) ? serviceWindowData.meta : [];
        const damagePercent = (value) => Math.min(Math.max(Number(value || 0), 0), 100);
        const fallbackWindowSeries = @json($isAnalisis30147)
            ? [
                { label: '30 dias', total: Number(data30[latestIndex] || 0), porcentaje: damagePercent(data30[latestIndex]), descripcion: '30 dias hacia atras desde el corte actual.', escala: '1 daño = 1%' },
                { label: '14 dias', total: Number(data14[latestIndex] || 0), porcentaje: damagePercent(data14[latestIndex]), descripcion: '14 dias hacia atras desde el corte actual.', escala: '1 daño = 1%' },
                { label: '7 dias', total: Number(data7[latestIndex] || 0), porcentaje: damagePercent(data7[latestIndex]), descripcion: '7 dias hacia atras desde el corte actual.', escala: '1 daño = 1%' }
            ]
            : [
                { label: '52 semanas', total: Number(data52[latestIndex] || 0), porcentaje: damagePercent(data52[latestIndex]), descripcion: '52 semanas equivalen a 1 ano hacia atras desde el corte actual.', escala: '1 daño = 1%' },
                { label: '12 semanas', total: Number(data12[latestIndex] || 0), porcentaje: damagePercent(data12[latestIndex]), descripcion: '12 semanas equivalen a 3 meses hacia atras desde el corte actual.', escala: '1 daño = 1%' },
                { label: '4 semanas', total: Number(data4[latestIndex] || 0), porcentaje: damagePercent(data4[latestIndex]), descripcion: '4 semanas equivalen a 1 mes hacia atras desde el corte actual.', escala: '1 daño = 1%' }
            ];
        const windowSeries = windowMeta.length ? windowMeta : fallbackWindowSeries;
        const windowColorForLabel = (label) => {
            const normalized = String(label || '').toLowerCase();

            if (normalized.includes('52') || normalized.includes('30')) {
                return ['#047857', 'rgba(16, 185, 129, 0.92)'];
            }

            if (normalized.includes('12') || normalized.includes('14')) {
                return ['#dc2626', 'rgba(239, 68, 68, 0.92)'];
            }

            if (normalized.includes('4') || normalized.includes('7')) {
                return ['#d97706', 'rgba(245, 158, 11, 0.94)'];
            }

            return ['#475569', 'rgba(100, 116, 139, 0.88)'];
        };

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: windowSeries.map((item) => item.label || 'Ventana'),
                datasets: [{
                    label: `${latestLabel} - incidencia por daños`,
                    data: windowSeries.map((item) => Number(item.porcentaje || 0)),
                    borderColor: windowSeries.map((item) => windowColorForLabel(item.label)[0]),
                    backgroundColor: windowSeries.map((item) => windowColorForLabel(item.label)[1]),
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false,
                    minBarLength: 8,
                    maxBarThickness: 74,
                    barPercentage: 0.72,
                    categoryPercentage: 0.72
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            title: (context) => `${context[0]?.label || ''} - ${latestLabel}`,
                            label: (context) => `Incidencia: ${Number(context.raw || 0).toFixed(1)}%`,
                            afterLabel: (context) => {
                                const item = windowSeries[context.dataIndex] || {};
                                const lines = [
                                    `Total real: ${Number(item.total || 0).toFixed(2)} daños`,
                                    item.desde && item.hasta ? `Rango: ${item.desde} al ${item.hasta}` : null,
                                    item.escala || 'Escala: 1 daño = 1%',
                                    item.descripcion || null
                                ];

                                return lines.filter(Boolean);
                            }
                        }
                    },
                    datalabels: {
                        display: true,
                        color: '#0f172a',
                        anchor: 'end',
                        align: 'top',
                        formatter: (value) => `${Number(value || 0).toFixed(1)}%`,
                        font: { weight: '800', size: 11 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#334155',
                            font: { size: 12, weight: '800' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
                        max: 100,
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                        title: {
                            display: true,
                            text: 'Cantidad de daños (%)',
                            color: '#64748b',
                            font: { size: 12, weight: '700' }
                        },
                        ticks: {
                            color: '#64748b',
                            callback: (value) => `${value}%`
                        }
                    }
                }
            }
        });
    }
    window.changeExecutiveChartType = function(type) {
        currentExecutiveChartType = type;

        document.querySelectorAll('[data-executive-chart-type]').forEach((button) => {
            button.classList.toggle('active', button.dataset.executiveChartType === type);
        });

        if (windowTrendChart) {
            windowTrendChart.destroy();
        }

        windowTrendChart = createWindowTrendChart();
    };

    createPieCharts();

    let wasExecutiveSmallScreen = executiveMobileQuery.matches;
    window.addEventListener('resize', () => {
        const isSmallScreen = executiveMobileQuery.matches;

        if (isSmallScreen !== wasExecutiveSmallScreen) {
            wasExecutiveSmallScreen = isSmallScreen;
            createPieCharts();
        }
    });
});
</script>

{{-- Agregar plugin para etiquetas en barras --}}
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
@endif
@endsection
