{{-- resources/views/analisis-tendencia-mensual-pasteurizadora/index.blade.php --}}
@php
    $analisisTipo = $analisisTipo ?? '52124';
    $lineaSeleccionada = $lineaSeleccionada ?? null;
    $fechaInicio = $fechaInicio ?? request('fecha_inicio');
    $fechaFin = $fechaFin ?? request('fecha_fin');
    $isAnalisis30147 = $analisisTipo === '30147';
    $rutaAnalisisActiva = $isAnalisis30147
        ? 'analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7'
        : 'analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4';
    $parametrosFecha = array_filter([
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
    ], fn ($value) => filled($value));
    $parametrosLinea = array_filter([
        'linea_id' => $lineaSeleccionada,
    ], fn ($value) => filled($value)) + $parametrosFecha;
    $tituloAnalisis = $isAnalisis30147 ? 'Analisis 30-14-7' : 'Analisis 52-12-4';
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
        justify-content: center;
        gap: 12px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        text-decoration: none;
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
        letter-spacing: 0.3px;
        min-height: 48px;
        min-width: 0;
        max-width: 100%;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
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
        max-width: 100%;
        min-width: 0;
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
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
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

    @media (max-width: 1024px) {
        .executive-brief {
            grid-template-columns: 1fr;
        }

        .executive-window-grid {
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
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
        .industrial-empty {
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

        .view-btn {
            width: 100%;
            padding: 10px 12px;
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
            <a href="{{ route('pasteurizadora.dashboard') }}" 
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
            <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4', $parametrosLinea) }}" 
               class="inline-flex items-center gap-2 px-5 py-3 rounded-full font-semibold transition-all duration-300 {{ !$isAnalisis30147 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-700 border border-gray-200 hover:border-blue-300 hover:text-blue-700' }}">
                <i class="fas fa-chart-line"></i>
                <span>52-12-4</span>
            </a>
            <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7', $parametrosLinea) }}" 
               class="inline-flex items-center gap-2 px-5 py-3 rounded-full font-semibold transition-all duration-300 {{ $isAnalisis30147 ? 'bg-cyan-600 text-white shadow-lg' : 'bg-white text-gray-700 border border-gray-200 hover:border-cyan-300 hover:text-cyan-700' }}">
                <i class="fas fa-chart-bar"></i>
                <span>30-14-7</span>
            </a>
        </div>
    </div>
                  
    {{-- Filtros Industriales --}}
    <div class="industrial-filters">
        <div class="filters-header">
            <img src="{{ asset('images/icono-pasteurizadora.png') }}" class="w-10 h-8 " alt="Icono de máquina">
            <h2>SELECCIONAR PASTEURIZADORA</h2>
        </div>
        
        <div class="machine-grid">
            @foreach($lineas as $linea)
                <a href="{{ route($rutaAnalisisActiva, ['linea_id' => $linea->id] + $parametrosFecha) }}" 
                   class="machine-pill {{ $lineaSeleccionada == $linea->id ? 'machine-pill-active' : 'machine-pill-inactive' }}">
                    <i class="fas fa-industry"></i>
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
                    <div id="implementationStatusNote" class="executive-status-note">
                        La lectura rapida debe enfocarse en las ventanas cortas; el historico tarda mas en bajar porque conserva memoria de meses anteriores.
                    </div>
                </div>
                <div id="executiveWindowCards" class="executive-window-grid"></div>
            </div>
            <div class="industrial-chart">
                <div class="chart-header chart-header--executive">
                    <div class="chart-title-block">
                        <i class="fas fa-chart-line"></i>
                        <div class="chart-title-copy">
                            <h3>{{ strtoupper($tituloAnalisis) }} - CONTROL EJECUTIVO</h3>
                            <p class="chart-subcopy">Resumen visual para revisar si la baja de daños se sostiene sin entrar al detalle tecnico.</p>
                        </div>
                    </div>
                    <div class="chart-view-selector">
                        <button class="view-btn active" data-executive-chart-type="bar" onclick="changeExecutiveChartType('bar')">Barras</button>
                        <button class="view-btn" data-executive-chart-type="line" onclick="changeExecutiveChartType('line')">Línea</button>
                    </div>
                    <div id="executiveChartCaption" class="chart-caption"></div>
                </div>
                <div class="chart-container chart-container--executive">
                    <canvas id="executiveTrendChart"></canvas>
                </div>
            </div>
            @if(!$isAnalisis30147)
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

            <div class="industrial-chart">
                <div class="chart-header">
                    <i class="fas fa-chart-bar"></i>
                    <h3>{{ strtoupper($tituloAnalisis) }} - {{ $lineas->find($lineaSeleccionada)?->nombre }}</h3>
                    <div class="chart-view-selector">
                        <button class="view-btn active" data-chart-type="bar" onclick="changeChartType('bar')">Barras</button>
                        <button class="view-btn" data-chart-type="line" onclick="changeChartType('line')">Línea</button>
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
            <h3>SELECCIONE UNA PASTEURIZADORA</h3>
            <p>Elija una pasteurizadora del panel superior para visualizar los análisis</p>
        </div>
    @endif
</div>

@if($lineaSeleccionada && $analisis->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart, executiveTrendChart;

document.addEventListener('DOMContentLoaded', function() {
    if (window.ChartDataLabels) {
        Chart.register(ChartDataLabels);
    }

    const analisisData = @json($analisis);
    const mobileQuery = window.matchMedia('(max-width: 640px)');
    let currentChartType = 'bar';
    let currentExecutiveChartType = 'bar';
    
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
        const noteNode = document.getElementById('implementationStatusNote');
        const captionNode = document.getElementById('executiveChartCaption');

        if (!statusCard || !titleNode || !copyNode || !noteNode || !captionNode) {
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
        noteNode.textContent = @json($isAnalisis30147)
            ? 'Lectura rapida: 7 y 14 dias muestran si la operacion diaria ya cambio; 30 dias funciona como respaldo del mes.'
            : 'Lectura rapida: 4 y 12 semanas muestran el impacto reciente; 52 semanas tarda mas en reflejar la mejora completa.';
        captionNode.textContent = `Corte actual: ${latestLabel}. Verde = 52/30, rojo = 12/14, naranja = 4/7.`;
    }

    function createExecutiveTrendChart(type = currentExecutiveChartType) {
        const executiveCanvas = document.getElementById('executiveTrendChart');
        const executiveIsBar = type === 'bar';
        const executiveIsSmallScreen = mobileQuery.matches;

        if (!executiveCanvas || !labels.length) {
            return;
        }

        renderExecutiveCards();
        renderExecutiveStatus();

        if (executiveTrendChart) {
            executiveTrendChart.destroy();
        }

        executiveTrendChart = new Chart(executiveCanvas.getContext('2d'), {
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
                    datalabels: {
                        display: (context) => {
                            if (executiveIsSmallScreen) {
                                return false;
                            }

                            if (executiveIsBar) {
                                return Number(context.raw || 0) > 0;
                            }

                            return context.dataIndex === labels.length - 1 && Number(context.raw || 0) > 0;
                        },
                        align: 'top',
                        anchor: 'end',
                        offset: executiveIsBar ? 4 : 6,
                        clamp: true,
                        color: executiveIsBar ? '#0f172a' : ((context) => context.dataset.borderColor),
                        backgroundColor: executiveIsBar ? 'rgba(255, 255, 255, 0.94)' : null,
                        borderRadius: executiveIsBar ? 6 : 0,
                        padding: executiveIsBar ? {
                            top: 3,
                            right: 5,
                            bottom: 3,
                            left: 5
                        } : 0,
                        formatter: (value) => formatMetric(value),
                        font: {
                            size: executiveIsBar ? 9 : 10,
                            weight: '800'
                        }
                    }
                },
                layout: {
                    padding: {
                        top: executiveIsBar ? 16 : 14,
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
    }
    
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    // Función para crear la gráfica
    function createChart(type = 'bar') {
        const isSmallScreen = mobileQuery.matches;
        const isMediumScreen = window.matchMedia('(max-width: 1024px)').matches;

        if (chart) {
            chart.destroy();
        }
        
        chart = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [
                    @if(!$isAnalisis30147)
                    {
                        label: '52 Semanas',
                        data: data52,
                        backgroundColor: 'rgba(34, 197, 94, 0.78)',
                        borderColor: '#16a34a',
                        borderWidth: type === 'bar' ? 0 : 3,
                        borderRadius: type === 'bar' ? 8 : 0,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        pointBackgroundColor: '#16a34a',
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
                        backgroundColor: 'rgba(239, 68, 68, 0.78)',
                        borderColor: '#dc2626',
                        borderWidth: type === 'bar' ? 0 : 3,
                        borderRadius: type === 'bar' ? 8 : 0,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        pointBackgroundColor: '#dc2626',
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
                        backgroundColor: 'rgba(249, 115, 22, 0.8)',
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
                    @else
                    {
                        label: '30 Dias',
                        data: data30,
                        backgroundColor: 'rgba(34, 197, 94, 0.72)',
                        borderColor: '#16a34a',
                        borderWidth: type === 'bar' ? 0 : 3,
                        borderRadius: type === 'bar' ? 8 : 0,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        pointBackgroundColor: '#16a34a',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: type === 'bar' ? 0 : 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: type === 'line' ? true : false
                    },
                    {
                        label: '14 Dias',
                        data: data14,
                        backgroundColor: 'rgba(239, 68, 68, 0.72)',
                        borderColor: '#dc2626',
                        borderWidth: type === 'bar' ? 0 : 3,
                        borderRadius: type === 'bar' ? 8 : 0,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        pointBackgroundColor: '#dc2626',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: type === 'bar' ? 0 : 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: type === 'line' ? true : false
                    },
                    {
                        label: '7 Dias',
                        data: data7,
                        backgroundColor: 'rgba(249, 115, 22, 0.72)',
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
                    }
                    @endif
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
                        position: isSmallScreen ? 'bottom' : 'top',
                        labels: {
                            usePointStyle: true,
                            padding: isSmallScreen ? 10 : 20,
                            boxWidth: isSmallScreen ? 8 : 12,
                            font: {
                                size: isSmallScreen ? 10 : 12,
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
                        display: (context) => !isSmallScreen && Number(context.raw || 0) > 0,
                        color: '#0f172a',
                        backgroundColor: 'rgba(255, 255, 255, 0.94)',
                        borderRadius: 6,
                        padding: {
                            top: 3,
                            right: 5,
                            bottom: 3,
                            left: 5
                        },
                        clamp: true,
                        clip: false,
                        font: {
                            weight: 'bold',
                            size: isMediumScreen ? 10 : 11,
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
                        grace: type === 'bar' ? '18%' : '10%',
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2);
                            },
                            maxTicksLimit: isSmallScreen ? 6 : 8,
                            font: {
                                size: isSmallScreen ? 10 : 11,
                                family: 'JetBrains Mono'
                            }
                        },
                        title: {
                            display: true,
                            text: 'TOTAL DE DAÑOS',
                            font: {
                                size: isSmallScreen ? 10 : 11,
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
                            autoSkip: false,
                            maxRotation: isSmallScreen ? 60 : 45,
                            minRotation: isSmallScreen ? 60 : 45,
                            font: {
                                size: isSmallScreen ? 10 : 11,
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
                        left: isSmallScreen ? 4 : 10,
                        right: isSmallScreen ? 4 : 10,
                        top: type === 'bar' && !isSmallScreen ? 48 : 16,
                        bottom: 10
                    }
                }
            }
        });
    }
    
    createExecutiveTrendChart();

    // Crear gráfica inicial de barras
    createChart(currentChartType);

    window.changeExecutiveChartType = function(type) {
        currentExecutiveChartType = type;

        document.querySelectorAll('[data-executive-chart-type]').forEach((button) => {
            button.classList.toggle('active', button.dataset.executiveChartType === type);
        });

        createExecutiveTrendChart();
    };
    
    // Función para cambiar el tipo de gráfica
    window.changeChartType = function(type) {
        currentChartType = type;

        // Actualizar botones
        document.querySelectorAll('[data-chart-type]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.chartType === type);
        });
        
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

    let wasSmallScreen = mobileQuery.matches;
    window.addEventListener('resize', () => {
        const isSmallScreen = mobileQuery.matches;

        if (isSmallScreen !== wasSmallScreen) {
            wasSmallScreen = isSmallScreen;
            createExecutiveTrendChart();
            createChart(currentChartType);
        }
    });
});
</script>

{{-- Agregar plugin para etiquetas en barras --}}
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
@endif
@endsection
