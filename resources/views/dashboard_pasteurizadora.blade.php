@extends('layouts.app')

@section('title', 'Pasteurizadoras')

@section('content')
@php
    $pasteurizadoras = collect($estadoPasteurizadoras);
    $fallasPorLineaPasteurizadora = collect($fallasPorLineaPasteurizadora ?? []);
    $componentesDanadosPasteurizadora = collect($componentesDanadosPasteurizadora ?? []);
    $historicoRevisionesPasteurizadora = collect($historicoRevisionesPasteurizadora ?? []);
    $analisis52124Pasteurizadora = $analisis52124Pasteurizadora ?? ['lineas' => [], 'criterios' => []];
    $analisis30147Pasteurizadora = $analisis30147Pasteurizadora ?? ['lineas' => [], 'criterios' => []];
    $planesPendientesPasteurizadora = collect($planesPendientesPasteurizadora ?? []);
    $planesAccionDashboardPasteurizadora = $planesAccionDashboardPasteurizadora ?? ['resumen' => [], 'estado_general' => [], 'por_linea' => [], 'planes' => []];
    $rankingDanosPasteurizadora = collect($rankingDanosPasteurizadora ?? []);
    $avanceRevisionPasteurizadora = $avanceRevisionPasteurizadora ?? ['labels' => [], 'porcentajes' => [], 'revisados' => [], 'totales' => [], 'lineas' => []];
    $ultimosAnalisisPasteurizadora = collect($ultimosAnalisisPasteurizadora ?? []);
    $trendFilters = $trendFilters ?? [];
    $usuarioActual = auth()->user();
    $puedeVerMecanicaPasteurizadora = $usuarioActual?->canAccessPasteurizadoraArea(\App\Models\AnalisisPasteurizadora::AREA_MECANICA) ?? false;
    $puedeVerPlanesPasteurizadora = $puedeVerMecanicaPasteurizadora
        && ($usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_PASTEURIZADORA) ?? false);
    $puedeVerTendenciasPasteurizadora = ($usuarioActual?->canAccessModule(\App\Models\User::MODULE_PASTEURIZADORA) ?? false)
        && ($usuarioActual?->canUseCustomPermission('ver tendencias pasteurizadora') ?? false);
    $totalPasteurizadoras = max((int) ($resumenPasteurizadora['total_pasteurizadoras'] ?? $pasteurizadoras->count()), 1);
    $estadoLineas = [
        'bueno' => $pasteurizadoras->where('estado.nivel', 'bueno')->count(),
        'operativo' => $pasteurizadoras->where('estado.nivel', 'operativo')->count(),
        'riesgo' => $pasteurizadoras->where('estado.nivel', 'riesgo')->count(),
        'critico' => $pasteurizadoras->where('estado.nivel', 'critico')->count(),
    ];
    $avancePromedio = (int) ($avanceRevisionPasteurizadora['promedio'] ?? round($pasteurizadoras->avg('estado.progreso_revision.porcentaje') ?? 0));
    $totalRevisados = (int) ($avanceRevisionPasteurizadora['total_revisados'] ?? $pasteurizadoras->sum(fn($item) => (int) data_get($item, 'estado.progreso_revision.revisados', 0)));
    $totalConfigurados = (int) ($avanceRevisionPasteurizadora['total_configurado'] ?? $pasteurizadoras->sum(fn($item) => (int) data_get($item, 'estado.progreso_revision.total', 0)));
@endphp

<style>
    :root {
        --primary-blue: #3b82f6;
        --secondary-blue: #1e40af;
        --accent-blue: #0284c7;
        --success-green: #10b981;
        --success-light: #d1fae5;
        --operational-orange: #f97316;
        --operational-light: #ffedd5;
        --warning-yellow: #f59e0b;
        --warning-light: #fef3c7;
        --danger-red: #ef4444;
        --danger-light: #fee2e2;
        --light-gray: #f3f4f6;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --border-light: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dashboard-container {
        width: 100%;
        max-width: 1680px;
        margin: 0 auto;
        padding: clamp(14px, 2vw, 20px);
        background: #f8fafc;
        box-sizing: border-box;
        overflow-x: hidden;
        overflow-x: clip;
    }

    .dashboard-container *,
    .dashboard-container *::before,
    .dashboard-container *::after {
        box-sizing: border-box;
        min-width: 0;
    }

    .dashboard-container :where(
        .stat-label,
        .stat-value,
        .lavadora-nombre,
        .lavadora-mensaje,
        .status-tag,
        .carousel-slide-title,
        .carousel-slide-subtitle,
        .carousel-slide-detail,
        .carousel-slide-meta,
        .metric-label,
        .metric-value,
        .chart-card h3 span,
        .chart-description,
        .ranking-linea,
        .ranking-puntaje,
        .ranking-badge,
        .table-footer
    ) {
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .dashboard-container img,
    .dashboard-container canvas,
    .dashboard-container svg {
        max-width: 100%;
    }

    .dashboard-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .dashboard-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    @keyframes blink {
        0% { opacity: 1; background-color: #fee2e2; border-left-color: #ef4444; }
        50% { opacity: 0.7; background-color: #fff5f5; border-left-color: #fca5a5; }
        100% { opacity: 1; background-color: #fee2e2; border-left-color: #ef4444; }
    }

    .alert-critical {
        animation: blink 1s ease-in-out infinite;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
        gap: 12px;
        margin-bottom: 16px;
        align-items: stretch;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 12px 14px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--medium-gray);
        transition: var(--transition);
        min-width: 0;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .stat-card .stat-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--dark-gray);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 4px;
    }

    .stat-card .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stat-card .stat-icon {
        float: right;
        font-size: 20px;
        color: var(--dark-gray);
    }

    .lavadoras-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 295px), 1fr));
        gap: 14px;
        margin-bottom: 16px;
        align-items: stretch;
    }

    .lavadora-card {
        border-radius: 12px;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        background: white;
        border: 1px solid var(--medium-gray);
        min-width: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .lavadora-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-xl);
    }

    .lavadora-card.buen-estado {
        background-color: #f0fdf4;
        border-left: 6px solid var(--success-green);
    }

    .lavadora-card.riesgo-estado {
        background-color: #fff7ed;
        border-left: 6px solid var(--operational-orange);
    }

    .lavadora-card.operativo-estado {
        background-color: #fefce8;
        border-left: 6px solid var(--warning-yellow);
    }

    .lavadora-card.critico-estado {
        background-color: #fef2f2;
        border-left: 6px solid var(--danger-red);
    }

    .lavadora-card.critico-estado.alert-critical {
        animation: blink 1s ease-in-out infinite;
    }

    .lavadora-card-header {
        padding: 10px 12px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }

    .lavadora-nombre {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1 1 180px;
        min-width: 0;
    }

    .status-icon {
        font-size: 14px;
    }

    .buen-estado .status-icon { color: var(--success-green); }
    .operativo-estado .status-icon { color: var(--warning-yellow); }
    .riesgo-estado .status-icon { color: var(--operational-orange); }
    .critico-estado .status-icon { color: var(--danger-red); }

    .status-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 16px;
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        white-space: normal;
        text-align: center;
        flex-wrap: wrap;
        max-width: 100%;
        line-height: 1.2;
    }

    .status-tag.bueno { background: var(--success-light); color: #065f46; }
    .status-tag.operativo { background: var(--warning-light); color: #92400e; }
    .status-tag.riesgo { background: var(--operational-light); color: #9a3412; }
    .status-tag.critico { background: var(--danger-light); color: #991b1b; }

    .lavadora-card-body {
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
    }

    .lavadora-mensaje {
        font-size: 12px;
        color: #475569;
        margin-bottom: 10px;
        line-height: 1.4;
        min-height: 34px;
    }

    .lavadora-carousel {
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .lavadora-carousel-track {
        display: flex;
        width: 100%;
    }

    .carousel-slide {
        min-width: 100%;
        padding: 10px;
        box-sizing: border-box;
        display: none;
    }

    .carousel-slide.active {
        display: block;
    }

    .carousel-slide-content {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 0;
    }

    .carousel-slide-image,
    .carousel-slide-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
        flex-shrink: 0;
    }

    .carousel-slide-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 10px;
    }

    .carousel-slide-icon i {
        font-size: 18px;
        color: var(--primary-blue);
    }

    .carousel-slide-info {
        flex: 1;
        min-width: 0;
    }

    .carousel-slide-title {
        font-weight: 700;
        color: #111827;
        margin-bottom: 2px;
        font-size: 12px;
    }

    .carousel-slide-subtitle {
        font-size: 11px;
        color: #475569;
        margin-bottom: 4px;
    }

    .carousel-slide-detail,
    .carousel-slide-meta {
        font-size: 10px;
        color: var(--dark-gray);
    }

    .carousel-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 10px 10px;
        gap: 8px;
    }

    .carousel-button {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.3);
        background: white;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition);
        font-size: 12px;
    }

    .carousel-button:hover {
        background: #e2e8f0;
        transform: translateY(-1px);
    }

    .carousel-dots {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .carousel-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(100, 116, 139, 0.35);
        cursor: pointer;
        transition: var(--transition);
    }

    .carousel-dot:hover {
        background: rgba(100, 116, 139, 0.6);
    }

    .carousel-dot.active {
        background: var(--primary-blue);
        width: 24px;
        border-radius: 4px;
    }

    .lavadora-metricas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        margin-bottom: 10px;
        font-size: 11px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
        padding: 8px;
        border-radius: 10px;
        border: 1px solid rgba(59, 130, 246, 0.1);
        gap: 8px;
    }

    .metric-item {
        text-align: center;
        min-width: 0;
    }

    .metric-label {
        color: var(--text-secondary);
        font-size: 9px;
        text-transform: uppercase;
        margin-bottom: 2px;
        letter-spacing: 0.3px;
        font-weight: 600;
    }

    .metric-value {
        font-weight: 700;
        font-size: 13px;
    }

    .progress-track {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.25);
        overflow: hidden;
        margin-top: 6px;
    }

    .progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--primary-blue), #8b5cf6);
    }

    .lavadora-card-footer {
        padding: 8px 12px;
        background: transparent;
        border-top: 1px solid rgba(148, 163, 184, 0.18);
        display: flex;
        justify-content: stretch;
        margin-top: auto;
    }

    .lavadora-card-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: rgba(255, 255, 255, 0.92);
        color: #334155;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        width: 100%;
        justify-content: center;
    }

    .lavadora-card-action:hover {
        background: white;
        transform: translateY(-1px);
    }

    .critico-estado .lavadora-card-footer {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.18), rgba(220, 38, 38, 0.26));
        border-top-color: rgba(185, 28, 28, 0.18);
    }

    .chart-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(59, 130, 246, 0.1);
        margin-bottom: 20px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        animation: slideInUp 0.6s ease-out;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-self: stretch;
        height: 100%;
    }

    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chart-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 25%, #10b981 50%, #f59e0b 75%, #3b82f6 100%);
        background-size: 400% 100%;
        animation: gradientShift 8s ease infinite;
    }

    @keyframes gradientShift {
        0%, 100% { background-position: 0% center; }
        50% { background-position: 100% center; }
    }

    .chart-card:hover {
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(59, 130, 246, 0.15);
        transform: translateY(-6px);
        border-color: rgba(59, 130, 246, 0.2);
    }

    .chart-card h3 {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        letter-spacing: -0.3px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(59, 130, 246, 0.08);
        flex-wrap: wrap;
    }

    .chart-card h3 span {
        flex: 1 1 220px;
        min-width: 0;
        line-height: 1.35;
    }

    .chart-card > * {
        min-width: 0;
    }

    .chart-card h3 i {
        font-size: 18px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        filter: drop-shadow(0 1px 2px rgba(59, 130, 246, 0.15));
    }

    .chart-container {
        height: 280px;
        position: relative;
        width: 100%;
        min-width: 0;
        padding: 12px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, rgba(139, 92, 246, 0.02) 100%);
        border-radius: 12px;
        margin: 4px 0;
    }

    .chart-description,
    .table-footer,
    .ranking-footer {
        margin-top: 12px;
        padding: 10px 12px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.06) 0%, rgba(139, 92, 246, 0.06) 100%);
        border-radius: 10px;
        text-align: center;
        font-size: 11px;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 6px;
        border: 1px solid rgba(59, 130, 246, 0.1);
        font-weight: 500;
    }

    .table-footer a,
    .chart-description a {
        color: #1d4ed8;
        font-weight: 800;
        text-decoration: none;
    }

    .table-footer a:hover,
    .chart-description a:hover {
        color: #1e40af;
        text-decoration: underline;
    }

    .dashboard-table-link {
        color: #1d4ed8;
        font-weight: 800;
        text-decoration: none;
    }

    .dashboard-table-link:hover {
        color: #1e40af;
        text-decoration: underline;
    }

    .dashboard-panels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 420px), 1fr));
        gap: 12px;
        margin-bottom: 12px;
        align-items: stretch;
    }

    .dashboard-panels-full {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        margin-top: 12px;
    }

    .dashboard-panel {
        position: relative;
    }

    .trend-card-primary {
        grid-column: 1 / -1;
        order: 2;
    }

    .trend-card-side {
        order: 1;
    }

    .panel-actions {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-start;
        min-width: 0;
        margin-bottom: 18px;
    }

    .panel-select,
    .panel-button,
    .panel-date-input {
        border: 1px solid var(--border-light);
        background: white;
        border-radius: 10px;
        color: var(--text-primary);
        font-size: 12px;
        font-weight: 700;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }

    .panel-select,
    .panel-date-input {
        min-width: 148px;
        max-width: 100%;
        padding: 10px 12px;
        outline: none;
    }

    .panel-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        cursor: pointer;
    }

    .panel-button:hover {
        transform: translateY(-1px);
        background: #f8fafc;
        box-shadow: var(--shadow-md);
    }

    .panel-select:focus,
    .panel-date-input:focus,
    .panel-button:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
        outline: none;
    }

    .trend-card .trend-filter-form {
        gap: 8px;
        align-items: flex-end;
        margin-bottom: 18px;
        padding: 0;
        border: 0;
        background: transparent;
        justify-content: flex-start;
    }

    .trend-date-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 148px;
        max-width: 100%;
    }

    .trend-date-field span {
        color: var(--text-secondary);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .chart-shell {
        position: relative;
        margin: 8px 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .chart-shell .chart-container {
        margin: 0;
        padding: 12px 10px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    .chart-container.tall {
        height: 276px;
    }

    .mini-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }

    .mini-stats-grid.compact {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .mini-stat {
        min-height: 76px;
        border: 1px solid var(--border-light);
        border-radius: 14px;
        background: white;
        padding: 12px;
        box-shadow: var(--shadow-sm);
    }

    .mini-stat.danger { border-top: 4px solid var(--danger-red); }
    .mini-stat.warning,
    .mini-stat.revision { border-top: 4px solid var(--warning-yellow); }
    .mini-stat.severo { border-top: 4px solid var(--operational-orange); }
    .mini-stat.success { border-top: 4px solid var(--success-green); }
    .mini-stat.info { border-top: 4px solid var(--primary-blue); }

    .mini-stat-label {
        margin-bottom: 6px;
        color: var(--text-secondary);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
    }

    .mini-stat-value {
        color: var(--text-primary);
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .mini-stat-meta {
        margin-top: 6px;
        color: var(--text-secondary);
        font-size: 11px;
    }

    .status-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 18px;
        padding: 14px 16px;
        border: 1px solid transparent;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.4;
    }

    .status-banner.critico {
        background: var(--danger-light);
        color: #991b1b;
        border-color: rgba(239, 68, 68, 0.18);
    }

    .status-banner.riesgo {
        background: var(--operational-light);
        color: #9a3412;
        border-color: rgba(249, 115, 22, 0.18);
    }

    .status-banner.estable {
        background: var(--success-light);
        color: #065f46;
        border-color: rgba(16, 185, 129, 0.18);
    }

    .severity-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        max-width: 100%;
        padding: 6px 12px;
        border: 1px solid transparent;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.3px;
        line-height: 1.2;
        text-align: center;
        text-transform: uppercase;
        white-space: normal;
    }

    .severity-pill.critico {
        background: var(--danger-light);
        color: #991b1b;
    }

    .severity-pill.revision {
        background: var(--warning-light);
        color: #92400e;
    }

    .severity-pill.severo,
    .severity-pill.moderado {
        background: var(--operational-light);
        color: #9a3412;
    }

    .severity-pill.estable {
        background: var(--success-light);
        color: #065f46;
    }

    .linea-breakdown,
    .worklist {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .breakdown-item,
    .work-item {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.84);
        padding: 13px 14px;
    }

    .breakdown-item-top,
    .work-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .breakdown-title,
    .work-title {
        color: var(--text-primary);
        font-size: 13px;
        font-weight: 800;
    }

    .breakdown-meta,
    .work-meta {
        margin-top: 5px;
        color: var(--text-secondary);
        font-size: 11px;
    }

    .progress-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f172a, #3b82f6);
    }

    .trend-filter-form {
        display: flex;
        align-items: end;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
        padding: 10px;
        border: 1px solid rgba(59, 130, 246, 0.12);
        border-radius: 12px;
        background: rgba(248, 250, 252, 0.82);
    }

    .trend-filter-field {
        display: flex;
        flex: 1 1 130px;
        flex-direction: column;
        gap: 4px;
    }

    .trend-filter-field label {
        font-size: 10px;
        font-weight: 800;
        color: var(--text-secondary);
        text-transform: uppercase;
    }

    .trend-filter-field input {
        min-height: 38px;
        border: 1px solid var(--border-light);
        border-radius: 8px;
        padding: 7px 9px;
        color: var(--text-primary);
        font-size: 12px;
        font-weight: 700;
    }

    .trend-filter-button,
    .trend-open-link {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 8px;
        padding: 8px 11px;
        font-size: 12px;
        font-weight: 800;
        transition: var(--transition);
    }

    .trend-filter-button {
        background: var(--primary-blue);
        color: white;
    }

    .trend-filter-button:hover {
        background: var(--secondary-blue);
    }

    .trend-open-link {
        border: 1px solid rgba(59, 130, 246, 0.18);
        background: white;
        color: #1d4ed8;
    }

    .trend-open-link:hover {
        border-color: rgba(59, 130, 246, 0.35);
        color: #1e40af;
    }

    .dashboard-trend-card {
        grid-column: 1 / -1;
        gap: 16px;
        padding: 22px;
    }

    .trend-card-side.dashboard-trend-card {
        grid-column: auto;
    }

    .trend-card .dashboard-trend-main-header,
    .trend-card .dashboard-trend-machine-strip,
    .trend-card .chart-description {
        display: none;
    }

    .trend-card .dashboard-trend-filters {
        padding: 0;
        border: 0;
        background: transparent;
        border-radius: 0;
    }

    .trend-card .trend-filter-field {
        min-width: 148px;
        max-width: 100%;
        flex: 1 1 148px;
        gap: 6px;
    }

    .trend-card .trend-filter-field label {
        color: var(--text-secondary);
        font-size: 11px;
        letter-spacing: 0.04em;
    }

    .trend-card .trend-filter-field input {
        min-height: 0;
        padding: 10px 12px;
        border-radius: 10px;
        box-shadow: var(--shadow-sm);
    }

    .trend-card .trend-filter-button {
        min-height: 0;
        border: 1px solid var(--border-light);
        border-radius: 10px;
        background: white;
        color: var(--text-primary);
        padding: 10px 14px;
        box-shadow: var(--shadow-sm);
    }

    .trend-card .trend-filter-button:hover {
        background: #f8fafc;
        color: var(--text-primary);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .trend-card .trend-open-link {
        display: none;
    }

    .trend-card .dashboard-trend-brief {
        display: grid;
        grid-template-columns: minmax(230px, 0.95fr) minmax(0, 1.45fr);
        gap: 16px;
        align-items: stretch;
    }

    .trend-card .dashboard-trend-status {
        border-radius: 24px;
        padding: 22px;
        box-shadow: none;
    }

    .trend-card .dashboard-trend-status-title {
        font-size: 28px;
        font-weight: 900;
    }

    .trend-card .dashboard-trend-status-note {
        display: none;
    }

    .trend-card .dashboard-trend-window-grid {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 185px), 1fr));
        gap: 14px;
    }

    .trend-card .dashboard-trend-window-card {
        border-radius: 20px;
        padding: 18px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
    }

    .trend-card .dashboard-trend-window-delta {
        padding: 10px 12px;
        font-size: 12px;
    }

    .trend-card .dashboard-trend-chart-shell {
        border: 0;
        border-radius: 14px;
        background: transparent;
        padding: 0;
    }

    .trend-card .dashboard-trend-chart-title {
        display: none;
    }

    .trend-card .dashboard-trend-chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .trend-card .dashboard-trend-caption {
        border: 0;
        background: transparent;
        padding: 0;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
    }

    .trend-card .dashboard-trend-view-selector {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px;
        border-radius: 16px;
        background: rgba(241, 245, 249, 0.95);
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .trend-card .dashboard-trend-view-btn {
        min-height: 0;
        border: 0;
        border-radius: 12px;
        background: transparent;
        color: #64748b;
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 800;
        box-shadow: none;
    }

    .trend-card .dashboard-trend-view-btn.active {
        background: #0f172a;
        color: white;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
    }

    .trend-card .dashboard-trend-chart-container {
        height: 276px;
        margin: 0;
        padding: 12px 10px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 14px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    .trend-card-side .dashboard-trend-chart-container {
        min-height: clamp(280px, 34vw, 340px);
        height: clamp(280px, 34vw, 340px);
    }

    .dashboard-trend-card:hover {
        transform: translateY(-2px);
    }

    .dashboard-trend-main-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(59, 130, 246, 0.1);
    }

    .dashboard-trend-title-block,
    .dashboard-trend-chart-title {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    .dashboard-trend-title-block i,
    .dashboard-trend-chart-title i {
        flex: 0 0 auto;
        color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.1);
        border-radius: 12px;
        padding: 10px;
        font-size: 17px;
    }

    .dashboard-trend-eyebrow,
    .dashboard-trend-window-label {
        color: var(--text-secondary);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .dashboard-trend-card h3.dashboard-trend-heading {
        display: block;
        margin: 4px 0 0;
        padding: 0;
        border: 0;
        color: var(--text-primary);
        font-size: 20px;
        font-weight: 850;
        letter-spacing: 0;
        line-height: 1.2;
    }

    .dashboard-trend-subcopy {
        margin-top: 6px;
        max-width: 68ch;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
    }

    .dashboard-trend-filters {
        margin-bottom: 0;
        padding: 14px;
        border-radius: 16px;
        border-color: rgba(59, 130, 246, 0.16);
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.96), rgba(241, 245, 249, 0.82));
    }

    .dashboard-trend-machine-strip {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 18px;
        background: #ffffff;
        padding: 16px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
    }

    .dashboard-trend-machine-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        color: var(--text-primary);
        font-size: 12px;
        font-weight: 850;
        text-transform: uppercase;
    }

    .dashboard-trend-machine-header i {
        color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.1);
        border-radius: 10px;
        padding: 8px;
    }

    .dashboard-trend-machine-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .dashboard-trend-machine-pill {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: 9px 16px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
    }

    .dashboard-trend-machine-pill.active {
        border-color: rgba(15, 23, 42, 0.12);
        background: #0f172a;
        color: #ffffff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.16);
    }

    .dashboard-trend-machine-pill.inactive {
        border-color: #e2e8f0;
        background: #f1f5f9;
        color: var(--text-secondary);
    }

    .dashboard-trend-brief {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) minmax(0, 1.85fr);
        gap: 14px;
        align-items: stretch;
    }

    .dashboard-trend-status {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        padding: 20px;
    }

    .dashboard-trend-status::after {
        content: '';
        position: absolute;
        right: -44px;
        bottom: -62px;
        width: 170px;
        height: 170px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.12);
    }

    .dashboard-trend-status--positive {
        border-color: rgba(16, 185, 129, 0.24);
        background: linear-gradient(145deg, #ecfdf5, #f8fafc);
    }

    .dashboard-trend-status--positive::after {
        background: rgba(16, 185, 129, 0.15);
    }

    .dashboard-trend-status--alert {
        border-color: rgba(239, 68, 68, 0.22);
        background: linear-gradient(145deg, #fef2f2, #fff7ed);
    }

    .dashboard-trend-status--alert::after {
        background: rgba(239, 68, 68, 0.14);
    }

    .dashboard-trend-status--neutral {
        border-color: rgba(245, 158, 11, 0.22);
        background: linear-gradient(145deg, #fffbeb, #f8fafc);
    }

    .dashboard-trend-status--neutral::after {
        background: rgba(245, 158, 11, 0.14);
    }

    .dashboard-trend-status-title {
        position: relative;
        z-index: 1;
        margin-top: 10px;
        color: var(--text-primary);
        font-size: clamp(20px, 2vw, 28px);
        font-weight: 850;
        line-height: 1.1;
    }

    .dashboard-trend-status-copy {
        position: relative;
        z-index: 1;
        margin-top: 12px;
        max-width: 48ch;
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 600;
        line-height: 1.5;
    }

    .dashboard-trend-status-note {
        position: relative;
        z-index: 1;
        margin-top: 14px;
        padding: 10px 12px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.74);
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 750;
        line-height: 1.4;
    }

    .dashboard-trend-window-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        align-content: start;
    }

    .dashboard-trend-window-card {
        position: relative;
        overflow: hidden;
        min-width: 0;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: #ffffff;
        padding: 18px 16px 16px;
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.06);
    }

    .dashboard-trend-window-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--window-accent, var(--primary-blue));
    }

    .dashboard-trend-window-value {
        margin-top: 10px;
        color: var(--text-primary);
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-size: clamp(22px, 2vw, 30px);
        font-weight: 850;
        line-height: 1.1;
    }

    .dashboard-trend-window-role {
        margin-top: 8px;
        max-width: 22ch;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
    }

    .dashboard-trend-window-delta {
        display: inline-flex;
        align-items: flex-start;
        gap: 7px;
        margin-top: 12px;
        border-radius: 999px;
        padding: 8px 10px;
        color: #92400e;
        background: #fef3c7;
        font-size: 11px;
        font-weight: 850;
        line-height: 1.3;
    }

    .dashboard-trend-window-delta.positive {
        color: #065f46;
        background: #d1fae5;
    }

    .dashboard-trend-window-delta.alert {
        color: #991b1b;
        background: #fee2e2;
    }

    .dashboard-trend-chart-shell {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.9);
        padding: 16px;
    }

    .dashboard-trend-chart-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        grid-template-areas:
            'title controls'
            'caption caption';
        gap: 12px 14px;
        align-items: start;
        margin-bottom: 12px;
    }

    .dashboard-trend-chart-title h4 {
        margin: 0;
        color: var(--text-primary);
        font-size: 16px;
        font-weight: 850;
        line-height: 1.25;
    }

    .dashboard-trend-chart-title p {
        margin-top: 5px;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
    }

    .dashboard-trend-chart-title {
        grid-area: title;
    }

    .dashboard-trend-view-selector {
        grid-area: controls;
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .dashboard-trend-view-btn {
        display: inline-flex;
        min-height: 34px;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(148, 163, 184, 0.26);
        border-radius: 999px;
        background: #ffffff;
        padding: 7px 14px;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: var(--transition);
    }

    .dashboard-trend-view-btn.active {
        border-color: var(--primary-blue);
        background: var(--primary-blue);
        color: #ffffff;
    }

    .dashboard-trend-view-btn:hover:not(.active) {
        border-color: rgba(59, 130, 246, 0.42);
        background: #f1f5f9;
        color: #1e40af;
    }

    .dashboard-trend-caption {
        grid-area: caption;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 14px;
        background: #f8fafc;
        padding: 10px 12px;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
    }

    .dashboard-trend-chart-container {
        height: clamp(320px, 44vh, 430px);
        margin: 0;
        padding: 12px 0;
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.95), rgba(241, 245, 249, 0.72));
    }

    .section-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        margin: 40px 0 28px 0;
        display: flex;
        align-items: center;
        gap: 14px;
        border-left: 5px solid var(--primary-blue);
        padding-left: 18px;
        letter-spacing: -0.5px;
        animation: slideInLeft 0.6s ease-out;
    }

    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .section-title i {
        font-size: 26px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .ranking-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .ranking-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(59, 130, 246, 0.08);
        background: linear-gradient(90deg, transparent 0%, rgba(59, 130, 246, 0.01) 50%, transparent 100%);
        transition: var(--transition);
        position: relative;
        border-radius: 10px;
        margin-bottom: 8px;
    }

    .ranking-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 60%;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0), rgba(59, 130, 246, 0.6), rgba(59, 130, 246, 0));
        border-radius: 2px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .ranking-item:hover {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.08) 50%, rgba(59, 130, 246, 0.05) 100%);
        transform: translateX(6px);
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.12);
    }

    .ranking-item:hover::before {
        opacity: 1;
    }

    .ranking-position {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #f0f4f8, #e5e7eb);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        color: #6b7280;
        font-size: 16px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.06);
        transition: var(--transition);
        position: relative;
    }

    .ranking-position.top-1 {
        background: linear-gradient(135deg, #fef9e7 0%, #fef3c7 50%, #fde68a 100%);
        color: #d97706;
        box-shadow: 0 8px 24px rgba(217, 119, 6, 0.3);
        border: 2px solid rgba(217, 119, 6, 0.2);
    }

    .ranking-position.top-2 {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 50%, #d1d5db 100%);
        color: #374151;
        box-shadow: 0 6px 20px rgba(107, 114, 128, 0.25);
        border: 2px solid rgba(107, 114, 128, 0.15);
    }

    .ranking-position.top-3 {
        background: linear-gradient(135deg, #fed7aa 0%, #fcd5ce 50%, #fce7f3 100%);
        color: #b45309;
        box-shadow: 0 6px 20px rgba(180, 83, 9, 0.25);
        border: 2px solid rgba(180, 83, 9, 0.15);
    }

    .ranking-info {
        flex: 1;
        margin-left: 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .ranking-linea {
        font-weight: 700;
        color: var(--text-primary);
        font-size: 13px;
        letter-spacing: -0.1px;
    }

    .ranking-puntaje {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ranking-puntaje i {
        color: #fbbf24;
        font-size: 12px;
    }

    .ranking-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 10px;
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #7f1d1d;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        box-shadow: 0 2px 8px rgba(153, 27, 27, 0.12);
        border: 1px solid rgba(153, 27, 27, 0.2);
        display: flex;
        align-items: center;
        gap: 6px;
        transition: var(--transition);
    }

    .ranking-item:hover .ranking-badge {
        transform: scale(1.08);
        box-shadow: 0 6px 16px rgba(153, 27, 27, 0.25);
    }

    .chart-card .overflow-x-auto {
        border-radius: 14px;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        border: 1px solid rgba(59, 130, 246, 0.08);
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .chart-card table {
        width: 100%;
        min-width: 760px;
        border-collapse: collapse;
        background: white;
    }

    .chart-card table thead {
        background: linear-gradient(135deg, #f0f4f9 0%, #e8ecf3 100%);
        border-bottom: 2.5px solid rgba(59, 130, 246, 0.12);
    }

    .chart-card table th {
        padding: 18px 20px;
        text-align: left;
        font-weight: 800;
        font-size: 12px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .chart-card table td {
        padding: 16px 20px;
        font-size: 14px;
        color: var(--text-primary);
        vertical-align: middle;
        font-weight: 500;
    }

    .chart-card table tbody tr {
        border-bottom: 1px solid rgba(59, 130, 246, 0.08);
        transition: var(--transition);
    }

    .chart-card table tbody tr:nth-child(odd) {
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.02) 50%, transparent);
    }

    .chart-card table tbody tr:hover {
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.08) 50%, transparent);
        box-shadow: inset 0 0 0 1.5px rgba(59, 130, 246, 0.12), 0 2px 8px rgba(59, 130, 246, 0.08);
    }

    .chart-card table td:last-child {
        text-align: right;
        font-weight: 700;
        color: var(--primary-blue);
    }

    .grid.gap-8 {
        gap: 32px;
        display: grid;
    }

    .grid.grid-cols-1 {
        grid-template-columns: 1fr;
    }

    .grid.md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }

    @media (min-width: 768px) {
        .grid.md\:grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .modal {
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

    .modal.show {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: min(600px, 100%);
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: 1px solid var(--medium-gray);
        color: var(--dark-gray);
        cursor: pointer;
        transition: var(--transition);
    }

    .modal-close:hover {
        background: var(--danger-red);
        color: white;
        border-color: var(--danger-red);
    }

    @media (max-width: 1400px) {
        .lavadoras-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 295px), 1fr));
        }

        .mini-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-trend-window-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
        }
    }

    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .lavadoras-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 295px), 1fr));
        }

        .dashboard-trend-brief {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 12px;
        }

        .dashboard-header {
            align-items: stretch;
            flex-direction: column;
            gap: 12px;
        }

        .dashboard-actions,
        .dashboard-actions button {
            width: 100%;
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .mini-stats-grid,
        .mini-stats-grid.compact {
            grid-template-columns: 1fr;
        }

        .lavadoras-grid {
            grid-template-columns: 1fr;
        }

        .section-title {
            font-size: 18px;
            margin: 28px 0 16px 0;
        }

        .chart-card {
            padding: 16px;
        }

        .dashboard-trend-card {
            padding: 16px;
        }

        .dashboard-trend-main-header,
        .dashboard-trend-title-block,
        .dashboard-trend-chart-title {
            gap: 10px;
        }

        .dashboard-trend-chart-header {
            grid-template-columns: 1fr;
            grid-template-areas:
                'title'
                'controls'
                'caption';
        }

        .dashboard-trend-view-selector {
            justify-content: flex-start;
        }

        .dashboard-trend-view-btn,
        .trend-filter-button,
        .trend-open-link {
            flex: 1 1 130px;
        }

        .modal {
            padding: 10px;
        }

        .modal-header {
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
        }

        .modal-header h3 {
            overflow-wrap: anywhere;
        }

        .modal-body {
            padding: 16px;
        }

        .historico-dashboard-card .overflow-x-auto {
            overflow: visible;
            border: 0;
            box-shadow: none;
        }

        .historico-dashboard-card table {
            min-width: 0;
            border-collapse: separate;
            border-spacing: 0 10px;
            background: transparent;
        }

        .historico-dashboard-card table thead {
            display: none;
        }

        .historico-dashboard-card table tbody,
        .historico-dashboard-card table tr,
        .historico-dashboard-card table td {
            display: block;
            width: 100%;
        }

        .historico-dashboard-card table tr {
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .historico-dashboard-card table td {
            display: grid;
            grid-template-columns: minmax(100px, 0.42fr) minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            text-align: left !important;
            font-size: 12px;
            color: var(--text-primary);
        }

        .historico-dashboard-card table td:last-child {
            border-bottom: 0;
        }

        .historico-dashboard-card table td::before {
            content: attr(data-label);
            font-size: 10px;
            font-weight: 800;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .historico-dashboard-card table td[colspan] {
            display: block;
            text-align: center !important;
        }

        .historico-dashboard-card table td[colspan]::before {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .dashboard-container {
            padding: 10px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .lavadora-metricas {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .historico-dashboard-card table td {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .section-title {
            align-items: flex-start;
            font-size: 16px;
            line-height: 1.25;
        }
    }
</style>

<div class="dashboard-container">
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 transition">
            <i class="fas fa-arrow-left"></i>
            <span>Volver</span>
        </a>
    </div>

    <div class="mb-6">
        <div class="dashboard-header">
            <div class="min-w-0">
                <h1 class="flex min-w-0 items-center gap-2 break-words text-2xl font-bold text-gray-800">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    Dashboard Pasteurizadoras
                </h1>
            </div>
            <div class="dashboard-actions">
                <button onclick="refreshData()" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>

    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-label">Total Pasteurizadoras</div>
            <div class="stat-value">{{ $resumenPasteurizadora['total_pasteurizadoras'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--danger-red);">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Críticas</div>
            <div class="stat-value" style="color: var(--danger-red);">{{ $resumenPasteurizadora['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--operational-orange);">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">Severo / Moderado</div>
            <div class="stat-value" style="color: var(--operational-orange);">{{ $resumenPasteurizadora['en_riesgo'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--warning-yellow);">
            <div class="stat-icon"><i class="fas fa-tools"></i></div>
            <div class="stat-label">Requiere Revisión</div>
            <div class="stat-value" style="color: var(--warning-yellow);">{{ $resumenPasteurizadora['requiere_revision'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--success-green);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value" style="color: var(--success-green);">{{ $resumenPasteurizadora['buen_estado'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-label">Pendientes Acción</div>
            <div class="stat-value">{{ $resumenPasteurizadora['pendientes_accion'] }}</div>
        </div>
    </div>

    <div class="section-title">
        <i class="fas fa-temperature-high"></i>
        ESTADO GENERAL DE PASTEURIZADORAS
    </div>

    <div class="lavadoras-grid">
        @foreach($pasteurizadoras as $pasteurizadora)
            @php
                $estado = $pasteurizadora['estado'];
                $nivel = $estado['nivel'] ?? 'bueno';
                $isCritical = $nivel === 'critico';
                $cardClass = $nivel === 'bueno'
                    ? 'buen-estado'
                    : ($nivel === 'operativo' ? 'operativo-estado' : ($nivel === 'riesgo' ? 'riesgo-estado' : 'critico-estado'));
                if ($isCritical) {
                    $cardClass .= ' alert-critical';
                }
                $progreso = $estado['progreso_revision'] ?? ['porcentaje' => 0];
                $porcentaje = (int) ($progreso['porcentaje'] ?? 0);
            @endphp
            <div class="lavadora-card {{ $cardClass }}">
                <div class="lavadora-card-header">
                    <div class="lavadora-nombre">
                        {{ $pasteurizadora['nombre'] }}
                    </div>
                    <div>
                        <span class="status-tag {{ $nivel === 'bueno' ? 'bueno' : ($nivel === 'operativo' ? 'operativo' : ($nivel === 'riesgo' ? 'riesgo' : 'critico')) }}">
                            <i class="fas {{ $nivel === 'bueno' ? 'fa-check-circle' : ($nivel === 'operativo' ? 'fa-tools' : ($nivel === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle')) }}"></i>
                            {{ $nivel === 'bueno' ? 'Buen estado' : ($nivel === 'operativo' ? 'Requiere revisión' : ($nivel === 'riesgo' ? 'Severo / Moderado' : 'Crítico')) }}
                        </span>
                    </div>
                </div>
                <div class="lavadora-card-body">
                    <div class="lavadora-mensaje">
                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                        {{ $estado['mensaje'] }}
                    </div>

                    @if(isset($estado['alert_carousel']) && count($estado['alert_carousel']) > 0)
                        <div class="lavadora-carousel" id="pasteurizadora-carousel-{{ $pasteurizadora['id'] }}">
                            <div class="lavadora-carousel-track">
                                @foreach($estado['alert_carousel'] as $carouselIndex => $item)
                                    <div class="carousel-slide {{ $carouselIndex === 0 ? 'active' : '' }}" data-slide="{{ $carouselIndex }}">
                                        <div class="carousel-slide-content">
                                            @if(($item['type'] ?? '') === 'componente')
                                                <div class="carousel-slide-image">
                                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" onerror="this.src='{{ $item['fallback_image'] ?? asset('images/icono-pasteurizadora.png') }}'" />
                                                </div>
                                            @else
                                                <div class="carousel-slide-icon">
                                                    <i class="fas {{ $item['icon'] ?? 'fa-info-circle' }}"></i>
                                                </div>
                                            @endif
                                            <div class="carousel-slide-info">
                                                <div class="carousel-slide-title">{{ $item['title'] }}</div>
                                                <div class="carousel-slide-subtitle">{{ $item['subtitle'] }}</div>
                                                @if(!empty($item['detail']) || !empty($item['description']))
                                                    <div class="carousel-slide-detail">{{ $item['detail'] ?? $item['description'] }}</div>
                                                @endif
                                                @if(!empty($item['meta']))
                                                    <div class="carousel-slide-meta">Orden: {{ $item['meta'] }}</div>
                                                @endif
                                                @if(!empty($item['fecha']))
                                                    <div class="carousel-slide-meta">Fecha: {{ $item['fecha'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(count($estado['alert_carousel']) > 1)
                                <div class="carousel-controls">
                                    <button type="button" class="carousel-button carousel-prev" aria-label="Anterior">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="carousel-dots">
                                        @foreach($estado['alert_carousel'] as $carouselIndex => $item)
                                            <span class="carousel-dot {{ $carouselIndex === 0 ? 'active' : '' }}" data-index="{{ $carouselIndex }}"></span>
                                        @endforeach
                                    </div>
                                    <button type="button" class="carousel-button carousel-next" aria-label="Siguiente">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="lavadora-metricas">
                        <div class="metric-item">
                            <div class="metric-label">Avance</div>
                            <div class="metric-value" style="color: var(--primary-blue);">{{ $porcentaje }}%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Revisados</div>
                            <div class="metric-value" style="color: var(--success-green);">
                                {{ $progreso['revisados'] ?? $progreso['componentes_revisados'] ?? 0 }}
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Pendientes</div>
                            <div class="metric-value" style="color: {{ ($estado['acciones_pendientes'] ?? 0) > 0 ? 'var(--danger-red)' : 'var(--success-green)' }};">
                                {{ $estado['acciones_pendientes'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lavadora-card-footer">
                    <button onclick='showPasteurizadoraDetail(@json($pasteurizadora))'
                            class="lavadora-card-action">
                        <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @php
        $planesResumen = $planesAccionDashboardPasteurizadora['resumen'] ?? [];
        $planesEstado = $planesAccionDashboardPasteurizadora['estado_general'] ?? [];
        $planesPorLinea = collect($planesAccionDashboardPasteurizadora['por_linea'] ?? []);
        $planesActivos = collect($planesAccionDashboardPasteurizadora['planes'] ?? []);
        $avanceLineas = collect($avanceRevisionPasteurizadora['lineas'] ?? [])->sortBy('porcentaje')->values();
    @endphp

    <div class="dashboard-panels-grid">
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-bar"></i>
                <span>Fallas por Línea</span>
            </h3>
            <div class="chart-container">
                <canvas id="fallasPasteurizadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Datos reales desde análisis activos de pasteurizadora
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-pie"></i>
                <span>Componentes con Daño o Desgaste</span>
            </h3>
            <div class="chart-container">
                <canvas id="componentesPasteurizadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Distribución real por componente revisado
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Atención</span>
            </h3>
            <ul class="ranking-list">
                @forelse($rankingDanosPasteurizadora->take(8) as $index => $item)
                    @php
                        $estado = $item['estado'];
                        $nivelEstado = $estado['nivel'] ?? 'bueno';
                        $estadoLabel = $nivelEstado === 'bueno'
                            ? 'Buen estado'
                            : ($nivelEstado === 'operativo'
                                ? 'Requiere revisión'
                                : ($nivelEstado === 'riesgo' ? 'Severo / Moderado' : 'Crítico'));
                        $pendientes = (int) ($estado['acciones_pendientes'] ?? 0);
                    @endphp
                    <li class="ranking-item">
                        <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="ranking-info">
                            <div class="ranking-linea">{{ $item['nombre'] }}</div>
                            <div class="ranking-puntaje">
                                <i class="fas fa-star"></i>
                                Estado: <strong>{{ $estadoLabel }}</strong>
                            </div>
                        </div>
                        <div class="ranking-badge">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $pendientes }} pendientes
                        </div>
                    </li>
                @empty
                    <li class="ranking-item">
                        <div class="ranking-position">0</div>
                        <div class="ranking-info">
                            <div class="ranking-linea">Sin datos</div>
                            <div class="ranking-puntaje">No hay pasteurizadoras para priorizar</div>
                        </div>
                    </li>
                @endforelse
            </ul>
            <div class="ranking-footer">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Ordenado visualmente por criticidad y pendientes activos
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-tasks"></i>
                <span>Componentes que Requieren Cambio</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Línea</th>
                            <th><i class="fas fa-clipboard-list" style="color: #8b5cf6;"></i> Actividad</th>
                            <th class="text-right"><i class="fas fa-calendar" style="color: #f59e0b;"></i> Próxima fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($planesPendientesPasteurizadora as $plan)
                            @php
                                $fechaPlan = $plan->plan_accion_pcm1['fecha']
                                    ?? $plan->plan_accion_pcm2['fecha']
                                    ?? $plan->plan_accion_pcm3['fecha']
                                    ?? $plan->plan_accion_pcm4['fecha']
                                    ?? null;
                            @endphp
                            <tr>
                                <td>
                                    @if($puedeVerMecanicaPasteurizadora)
                                        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $plan->linea_id]) }}" class="dashboard-table-link">
                                            {{ $plan->linea?->nombre ?? 'Sin línea' }}
                                        </a>
                                    @else
                                        {{ $plan->linea?->nombre ?? 'Sin línea' }}
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        @if($puedeVerMecanicaPasteurizadora)
                                            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.show', $plan) }}" class="dashboard-table-link">
                                                {{ Str::limit($plan->actividad ?? 'Sin actividad', 48) }}
                                            </a>
                                        @else
                                            {{ Str::limit($plan->actividad ?? 'Sin actividad', 48) }}
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">Módulo {{ $plan->modulo }} · {{ $plan->componente_nombre }}</div>
                                </td>
                                <td>
                                    {{ $fechaPlan ? \Carbon\Carbon::parse($fechaPlan)->format('d/m/Y') : 'Sin fecha' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Sin pendientes activos</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                <span>Registros activos de análisis mecánico que requieren cambio.</span>
                @if($puedeVerMecanicaPasteurizadora)
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}">Abrir análisis</a>
                @endif
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card">
            <h3>
                <i class="fas fa-tasks"></i>
                <span>Plan de Acción</span>
            </h3>
            <div class="status-banner {{ $planesEstado['nivel'] ?? 'estable' }}">
                <i class="fas fa-clipboard-check"></i>
                <span>{{ $planesEstado['label'] ?? 'Controlado' }}: {{ $planesEstado['mensaje'] ?? 'Sin planes abiertos con riesgo inmediato.' }}</span>
            </div>
            <div class="mini-stats-grid">
                <div class="mini-stat info">
                    <div class="mini-stat-label">Activos</div>
                    <div class="mini-stat-value">{{ $planesResumen['activos'] ?? 0 }}</div>
                </div>
                <div class="mini-stat danger">
                    <div class="mini-stat-label">Alta prioridad</div>
                    <div class="mini-stat-value">{{ $planesResumen['prioridad_alta'] ?? 0 }}</div>
                </div>
                <div class="mini-stat warning">
                    <div class="mini-stat-label">Próx. 7 días</div>
                    <div class="mini-stat-value">{{ $planesResumen['proximos_7_dias'] ?? 0 }}</div>
                </div>
                <div class="mini-stat success">
                    <div class="mini-stat-label">Cierre</div>
                    <div class="mini-stat-value">{{ $planesResumen['avance'] ?? 0 }}%</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="planesPasteurizadoraChart"></canvas>
            </div>
            <div class="worklist">
                @forelse($planesActivos->take(4) as $plan)
                    <div class="work-item">
                        <div class="work-item-top">
                            <div>
                                <div class="work-title">{{ $plan['linea'] }} · {{ Str::limit($plan['actividad'] ?? 'Sin actividad', 62) }}</div>
                                <div class="work-meta">Próxima fecha: {{ $plan['proxima_fecha_humana'] ?? 'Sin fecha' }}</div>
                            </div>
                            <span class="severity-pill {{ ($plan['prioridad'] ?? 'baja') === 'alta' ? 'critico' : (($plan['prioridad'] ?? 'baja') === 'media' ? 'severo' : 'estable') }}">
                                {{ $plan['prioridad_label'] ?? 'Baja' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="work-item">
                        <div class="work-title">Sin planes activos</div>
                        <div class="work-meta">No hay actividades abiertas de plan de acción mecánico.</div>
                    </div>
                @endforelse
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                <span>Planes reales del módulo Pasteurizadora.</span>
                @if($puedeVerPlanesPasteurizadora)
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}">Abrir plan</a>
                @endif
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Avance de Revisión</span>
            </h3>
            <div class="mini-stats-grid compact">
                <div class="mini-stat info">
                    <div class="mini-stat-label">Promedio</div>
                    <div class="mini-stat-value">{{ $avancePromedio }}%</div>
                    <div class="mini-stat-meta">avance global</div>
                </div>
                <div class="mini-stat success">
                    <div class="mini-stat-label">Revisados</div>
                    <div class="mini-stat-value">{{ number_format($totalRevisados) }}</div>
                    <div class="mini-stat-meta">posiciones revisadas</div>
                </div>
                <div class="mini-stat warning">
                    <div class="mini-stat-label">Configurados</div>
                    <div class="mini-stat-value">{{ number_format($totalConfigurados) }}</div>
                    <div class="mini-stat-meta">posiciones totales</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="avanceRevisionPasteurizadoraChart"></canvas>
            </div>
            <div class="linea-breakdown">
                @forelse($avanceLineas->take(4) as $lineaAvance)
                    <div class="breakdown-item">
                        <div class="breakdown-item-top">
                            <div>
                                <div class="breakdown-title">{{ $lineaAvance['linea'] }}</div>
                                <div class="breakdown-meta">{{ number_format((int) ($lineaAvance['revisados'] ?? 0)) }} de {{ number_format((int) ($lineaAvance['total'] ?? 0)) }} posiciones revisadas</div>
                            </div>
                            <span class="severity-pill {{ (int) ($lineaAvance['porcentaje'] ?? 0) >= 90 ? 'estable' : ((int) ($lineaAvance['porcentaje'] ?? 0) >= 60 ? 'revision' : 'severo') }}">
                                {{ (int) ($lineaAvance['porcentaje'] ?? 0) }}%
                            </span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar" style="width: {{ min(100, max(0, (int) ($lineaAvance['porcentaje'] ?? 0))) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="breakdown-item">
                        <div class="breakdown-title">Sin avance registrado</div>
                        <div class="breakdown-meta">Aún no hay revisiones mecánicas para graficar.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card historico-dashboard-card">
            <h3>
                <i class="fas fa-history"></i>
                <span>Histórico de Revisiones</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-cube" style="color: #3b82f6;"></i> Componente</th>
                            <th><i class="fas fa-calendar-alt" style="color: #8b5cf6;"></i> Último análisis</th>
                            <th class="text-right"><i class="fas fa-hashtag" style="color: #10b981;"></i> Análisis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historicoRevisionesPasteurizadora as $item)
                            <tr>
                                <td data-label="Componente"><i class="fas fa-microchip mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                <td data-label="Último análisis">{{ $item['ultimo_analisis'] }}</td>
                                <td data-label="Análisis">{{ $item['total_analisis'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Sin análisis registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                <span>Información conectada con el historial de análisis de pasteurizadora</span>
                @if($puedeVerMecanicaPasteurizadora)
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.historico-revisados') }}">Abrir historico</a>
                @endif
            </div>
        </div>

        <div class="chart-card trend-card trend-card-primary dashboard-panel dashboard-trend-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Análisis 52-12-4 | Tendencia de Daños</span>
            </h3>
            <div class="dashboard-trend-main-header">
                <div class="dashboard-trend-title-block">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <div class="dashboard-trend-eyebrow">Análisis de tendencia Pasteurizadora</div>
                        <h3 class="dashboard-trend-heading">Análisis 52-12-4</h3>
                        <p class="dashboard-trend-subcopy">Lectura ejecutiva por pasteurizadora con ventanas de 52, 12 y 4 semanas.</p>
                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters">
                <select id="analisis52124PasteurizadoraLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="52124">
                    @forelse(($analisis52124Pasteurizadora['lineas'] ?? []) as $lineaTrend)
                        <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis52124Pasteurizadora, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                    @empty
                        <option value="">Sin pasteurizadoras</option>
                    @endforelse
                </select>
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia30147.from_param', 'trend_30147_desde') }}" value="{{ data_get($trendFilters, 'tendencia30147.from_input', '') }}">
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia30147.to_param', 'trend_30147_hasta') }}" value="{{ data_get($trendFilters, 'tendencia30147.to_input', '') }}">
                <div class="trend-filter-field">
                    <label>Desde</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia.from_param', 'trend_52124_desde') }}" value="{{ data_get($trendFilters, 'tendencia.from_input', '') }}">
                </div>
                <div class="trend-filter-field">
                    <label>Hasta</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia.to_param', 'trend_52124_hasta') }}" value="{{ data_get($trendFilters, 'tendencia.to_input', '') }}">
                </div>
                <button type="submit" class="trend-filter-button">
                    <i class="fas fa-filter"></i>
                    Aplicar
                </button>
                @if($puedeVerTendenciasPasteurizadora)
                    <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4') }}" class="trend-open-link">
                        <i class="fas fa-up-right-from-square"></i>
                        Abrir
                    </a>
                @endif
            </form>
            <div class="dashboard-trend-machine-strip">
                <div class="dashboard-trend-machine-header">
                    <i class="fas fa-industry"></i>
                    <span>Pasteurizadoras incluidas</span>
                </div>
                <div id="trend52124MachineGrid" class="dashboard-trend-machine-grid"></div>
            </div>
            <div class="dashboard-trend-brief">
                <div id="trend52124StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                    <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                    <div id="trend52124StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                    <p id="trend52124StatusCopy" class="dashboard-trend-status-copy">
                        Se está calculando el comportamiento reciente con información de análisis de Pasteurizadora.
                    </p>
                    <div id="trend52124StatusNote" class="dashboard-trend-status-note">Ventanas: 52, 12 y 4 semanas.</div>
                </div>
                <div id="trend52124WindowCards" class="dashboard-trend-window-grid"></div>
            </div>
            <div class="dashboard-trend-chart-shell">
                <div class="dashboard-trend-chart-header">
                    <div class="dashboard-trend-chart-title">
                        <i class="fas fa-chart-column"></i>
                        <div>
                            <h4>Comparativo por pasteurizadora</h4>
                            <p>Último corte disponible para cada ventana de análisis.</p>
                        </div>
                    </div>
                    <div class="dashboard-trend-view-selector">
                        <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="52124" data-pasteur-trend-type="bar">Barras</button>
                        <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="52124" data-pasteur-trend-type="line">Línea</button>
                    </div>
                    <div id="trend52124Caption" class="dashboard-trend-caption">Corte actual de tendencia 52-12-4.</div>
                </div>
                <div class="chart-container dashboard-trend-chart-container">
                    <canvas id="analisis52124PasteurizadoraChart"></canvas>
                </div>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                <span>Tendencia automática calculada desde daños registrados en los análisis de Pasteurizadora</span>
            </div>
        </div>
        <div class="chart-card trend-card trend-card-side dashboard-panel dashboard-trend-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Análisis 30-14-7 | Tendencia de Daños</span>
            </h3>
            <div class="dashboard-trend-main-header">
                <div class="dashboard-trend-title-block">
                    <i class="fas fa-bolt"></i>
                    <div>
                        <div class="dashboard-trend-eyebrow">Análisis de tendencia Pasteurizadora</div>
                        <h3 class="dashboard-trend-heading">Análisis 30-14-7</h3>
                        <p class="dashboard-trend-subcopy">Lectura ejecutiva por pasteurizadora con ventanas de 30, 14 y 7 días.</p>
                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters">
                <select id="analisis30147PasteurizadoraLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="30147">
                    @forelse(($analisis30147Pasteurizadora['lineas'] ?? []) as $lineaTrend)
                        <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis30147Pasteurizadora, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                    @empty
                        <option value="">Sin pasteurizadoras</option>
                    @endforelse
                </select>
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia.from_param', 'trend_52124_desde') }}" value="{{ data_get($trendFilters, 'tendencia.from_input', '') }}">
                <input type="hidden" name="{{ data_get($trendFilters, 'tendencia.to_param', 'trend_52124_hasta') }}" value="{{ data_get($trendFilters, 'tendencia.to_input', '') }}">
                <div class="trend-filter-field">
                    <label>Desde</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia30147.from_param', 'trend_30147_desde') }}" value="{{ data_get($trendFilters, 'tendencia30147.from_input', '') }}">
                </div>
                <div class="trend-filter-field">
                    <label>Hasta</label>
                    <input type="date" name="{{ data_get($trendFilters, 'tendencia30147.to_param', 'trend_30147_hasta') }}" value="{{ data_get($trendFilters, 'tendencia30147.to_input', '') }}">
                </div>
                <button type="submit" class="trend-filter-button">
                    <i class="fas fa-filter"></i>
                    Aplicar
                </button>
                @if($puedeVerTendenciasPasteurizadora)
                    <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7') }}" class="trend-open-link">
                        <i class="fas fa-up-right-from-square"></i>
                        Abrir
                    </a>
                @endif
            </form>
            <div class="dashboard-trend-machine-strip">
                <div class="dashboard-trend-machine-header">
                    <i class="fas fa-industry"></i>
                    <span>Pasteurizadoras incluidas</span>
                </div>
                <div id="trend30147MachineGrid" class="dashboard-trend-machine-grid"></div>
            </div>
            <div class="dashboard-trend-brief">
                <div id="trend30147StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                    <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                    <div id="trend30147StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                    <p id="trend30147StatusCopy" class="dashboard-trend-status-copy">
                        Se está calculando el comportamiento reciente con información de análisis de Pasteurizadora.
                    </p>
                    <div id="trend30147StatusNote" class="dashboard-trend-status-note">Ventanas: 30, 14 y 7 días.</div>
                </div>
                <div id="trend30147WindowCards" class="dashboard-trend-window-grid"></div>
            </div>
            <div class="dashboard-trend-chart-shell">
                <div class="dashboard-trend-chart-header">
                    <div class="dashboard-trend-chart-title">
                        <i class="fas fa-chart-column"></i>
                        <div>
                            <h4>Comparativo por pasteurizadora</h4>
                            <p>Último corte disponible para cada ventana de análisis.</p>
                        </div>
                    </div>
                    <div class="dashboard-trend-view-selector">
                        <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="30147" data-pasteur-trend-type="bar">Barras</button>
                        <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="30147" data-pasteur-trend-type="line">Línea</button>
                    </div>
                    <div id="trend30147Caption" class="dashboard-trend-caption">Corte actual de tendencia 30-14-7.</div>
                </div>
                <div class="chart-container dashboard-trend-chart-container">
                    <canvas id="analisis30147PasteurizadoraChart"></canvas>
                </div>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                <span>Seguimiento de fallas recientes calculado automáticamente desde los análisis registrados de Pasteurizadora</span>
            </div>
        </div>
    </div>
</div>

<div id="alertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Detalle de Alerta</h3>
            <button onclick="closeModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let fallasPasteurizadoraChart, componentesPasteurizadoraChart, planesPasteurizadoraChart, avanceRevisionPasteurizadoraChart, analisis52124PasteurizadoraChart, analisis30147PasteurizadoraChart;
    const pasteurizadorasData = @json($pasteurizadoras->values());
    const fallasPorLineaPasteurizadora = @json($fallasPorLineaPasteurizadora->values());
    const componentesDanadosPasteurizadora = @json($componentesDanadosPasteurizadora->values());
    const planesAccionDashboardPasteurizadora = @json($planesAccionDashboardPasteurizadora);
    const avanceRevisionPasteurizadora = @json($avanceRevisionPasteurizadora);
    const analisis52124Pasteurizadora = @json($analisis52124Pasteurizadora);
    const analisis30147Pasteurizadora = @json($analisis30147Pasteurizadora);
    const pasteurTrendChartTypes = {
        '52124': 'bar',
        '30147': 'bar'
    };
    const pasteurTrendCards = {
        '52124': {
            key: '52124',
            canvasId: 'analisis52124PasteurizadoraChart',
            selectId: 'analisis52124PasteurizadoraLineaSelect',
            dataset: analisis52124Pasteurizadora,
            statusCardId: 'trend52124StatusCard',
            statusTitleId: 'trend52124StatusTitle',
            statusCopyId: 'trend52124StatusCopy',
            statusNoteId: 'trend52124StatusNote',
            windowsId: 'trend52124WindowCards',
            machineGridId: 'trend52124MachineGrid',
            captionId: 'trend52124Caption',
            title: '52-12-4',
            windowRoles: ['Histórico anual', 'Impacto trimestral', 'Control inmediato'],
            colors: [
                ['rgba(16, 185, 129, 0.88)', '#047857', 'rgba(16, 185, 129, 0.22)'],
                ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
                ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)']
            ]
        },
        '30147': {
            key: '30147',
            canvasId: 'analisis30147PasteurizadoraChart',
            selectId: 'analisis30147PasteurizadoraLineaSelect',
            dataset: analisis30147Pasteurizadora,
            statusCardId: 'trend30147StatusCard',
            statusTitleId: 'trend30147StatusTitle',
            statusCopyId: 'trend30147StatusCopy',
            statusNoteId: 'trend30147StatusNote',
            windowsId: 'trend30147WindowCards',
            machineGridId: 'trend30147MachineGrid',
            captionId: 'trend30147Caption',
            title: '30-14-7',
            windowRoles: ['Ventana amplia', 'Seguimiento intermedio', 'Control inmediato'],
            colors: [
                ['rgba(16, 185, 129, 0.88)', '#047857', 'rgba(16, 185, 129, 0.22)'],
                ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
                ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)']
            ]
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        initPasteurizadoraTrendSelectors();
        initPasteurizadoraCarousels();
        setAutoRefresh();
    });

    function initCharts() {
        const fallasCtx = document.getElementById('fallasPasteurizadoraChart').getContext('2d');
        fallasPasteurizadoraChart = new Chart(fallasCtx, {
            type: 'bar',
            data: {
                labels: fallasPorLineaPasteurizadora.map(item => item.linea),
                datasets: [
                    {
                        label: 'Críticos',
                        data: fallasPorLineaPasteurizadora.map(item => item.criticos || 0),
                        backgroundColor: 'rgba(239, 68, 68, 0.9)',
                        borderColor: '#dc2626',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Requiere revisión',
                        data: fallasPorLineaPasteurizadora.map(item => item.requiere_revision || 0),
                        backgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: '#d97706',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Severo / Moderado',
                        data: fallasPorLineaPasteurizadora.map(item => item.desgaste || 0),
                        backgroundColor: 'rgba(249, 115, 22, 0.88)',
                        borderColor: '#ea580c',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', padding: 8 }
                    },
                    x: {
                        stacked: true,
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 13, weight: 600 }, color: '#334155', padding: 8 }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, font: { size: 12, weight: 'bold' }, color: '#334155' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            },
                            footer: function(items) {
                                const item = fallasPorLineaPasteurizadora[items[0]?.dataIndex];
                                return item ? `Total: ${item.total_fallas || 0}` : '';
                            }
                        }
                    }
                }
            }
        });

        const componentesCtx = document.getElementById('componentesPasteurizadoraChart').getContext('2d');
        componentesPasteurizadoraChart = new Chart(componentesCtx, {
            type: 'doughnut',
            data: {
                labels: componentesDanadosPasteurizadora.map(item => item.componente),
                datasets: [{
                    data: componentesDanadosPasteurizadora.map(item => item.total_danios),
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.9)',
                        'rgba(245, 158, 11, 0.9)',
                        'rgba(16, 185, 129, 0.9)',
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(139, 92, 246, 0.9)',
                        'rgba(236, 72, 153, 0.9)',
                        'rgba(14, 165, 233, 0.9)',
                        'rgba(100, 116, 139, 0.9)'
                    ],
                    borderColor: ['#dc2626', '#d97706', '#059669', '#2563eb', '#7c3aed', '#db2777', '#0284c7', '#475569'],
                    borderWidth: 3,
                    borderRadius: 8,
                    hoverBorderWidth: 5,
                    hoverOffset: 12,
                    spacing: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 12, weight: 'bold' }, color: '#334155', padding: 16, usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                cutout: '62%'
            }
        });

        planesPasteurizadoraChart = buildPlanesPasteurizadoraChart();
        avanceRevisionPasteurizadoraChart = buildAvanceRevisionPasteurizadoraChart();
        analisis52124PasteurizadoraChart = buildPasteurizadoraTrendChart(
            'analisis52124PasteurizadoraChart',
            analisis52124Pasteurizadora,
            pasteurTrendCards['52124']
        );
        analisis30147PasteurizadoraChart = buildPasteurizadoraTrendChart(
            'analisis30147PasteurizadoraChart',
            analisis30147Pasteurizadora,
            pasteurTrendCards['30147']
        );
    }

    function buildPlanesPasteurizadoraChart() {
        const canvas = document.getElementById('planesPasteurizadoraChart');
        if (!canvas) return null;

        const rows = Array.isArray(planesAccionDashboardPasteurizadora?.por_linea)
            ? planesAccionDashboardPasteurizadora.por_linea
            : [];

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: rows.map(item => item.linea || 'N/A'),
                datasets: [
                    {
                        label: 'Abiertos',
                        data: rows.map(item => Number(item.abiertos || 0)),
                        backgroundColor: 'rgba(239, 68, 68, 0.86)',
                        borderColor: '#dc2626',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false
                    },
                    {
                        label: 'Completados',
                        data: rows.map(item => Number(item.completados || 0)),
                        backgroundColor: 'rgba(16, 185, 129, 0.86)',
                        borderColor: '#059669',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', precision: 0 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#334155' }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, font: { size: 12, weight: 'bold' }, color: '#334155' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        padding: 14
                    }
                }
            }
        });
    }

    function buildAvanceRevisionPasteurizadoraChart() {
        const canvas = document.getElementById('avanceRevisionPasteurizadoraChart');
        if (!canvas) return null;

        const labels = Array.isArray(avanceRevisionPasteurizadora?.labels)
            ? avanceRevisionPasteurizadora.labels
            : [];
        const values = Array.isArray(avanceRevisionPasteurizadora?.porcentajes)
            ? avanceRevisionPasteurizadora.porcentajes.map(value => Number(value || 0))
            : [];

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Avance de revisión',
                    data: values,
                    backgroundColor: values.map(value => value >= 90
                        ? 'rgba(16, 185, 129, 0.88)'
                        : (value >= 60 ? 'rgba(245, 158, 11, 0.88)' : 'rgba(249, 115, 22, 0.88)')),
                    borderColor: values.map(value => value >= 90
                        ? '#059669'
                        : (value >= 60 ? '#d97706' : '#ea580c')),
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: {
                            font: { size: 12, weight: 600 },
                            color: '#64748b',
                            callback: value => `${value}%`
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#334155' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: context => `${context.raw}% revisado`
                        }
                    }
                }
            }
        });
    }

    function initPasteurizadoraTrendSelectors() {
        document.querySelectorAll('[data-pasteur-trend-type]').forEach((button) => {
            button.addEventListener('click', () => {
                const cardKey = button.dataset.pasteurTrendCard;
                const chartType = button.dataset.pasteurTrendType || 'bar';
                const config = pasteurTrendCards[cardKey];

                if (!config) {
                    return;
                }

                pasteurTrendChartTypes[cardKey] = chartType;

                document.querySelectorAll(`[data-pasteur-trend-type][data-pasteur-trend-card="${cardKey}"]`).forEach((item) => {
                    item.classList.toggle('active', item.dataset.pasteurTrendType === chartType);
                });

                const chart = buildPasteurizadoraTrendChart(config.canvasId, config.dataset, config);

                if (cardKey === '52124') {
                    analisis52124PasteurizadoraChart = chart;
                } else if (cardKey === '30147') {
                    analisis30147PasteurizadoraChart = chart;
                }
            });
        });

        Object.values(pasteurTrendCards).forEach((config) => {
            const select = document.getElementById(config.selectId);

            if (!select) {
                return;
            }

            select.addEventListener('change', () => {
                const chart = buildPasteurizadoraTrendChart(config.canvasId, config.dataset, config);

                if (config.key === '52124') {
                    analisis52124PasteurizadoraChart = chart;
                } else if (config.key === '30147') {
                    analisis30147PasteurizadoraChart = chart;
                }
            });
        });
    }

    function formatTrendCount(value) {
        return new Intl.NumberFormat('es-MX', {
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }

    function getTrendSerieForRow(row, serieKey) {
        return (Array.isArray(row?.series) ? row.series : []).find((item) => item.key === serieKey) || null;
    }

    function getTrendLatestValue(row, serieKey) {
        const serie = getTrendSerieForRow(row, serieKey);
        const values = Array.isArray(serie?.data) ? serie.data : [];

        return Number(values[values.length - 1] || 0);
    }

    function getTrendPreviousValue(row, serieKey) {
        const serie = getTrendSerieForRow(row, serieKey);
        const values = Array.isArray(serie?.data) ? serie.data : [];

        return Number(values.length > 1 ? values[values.length - 2] : 0);
    }

    function getTrendCurrentTotalForRow(row, sourceSeries) {
        return sourceSeries.reduce((sum, serie) => sum + getTrendLatestValue(row, serie.key), 0);
    }

    function trendDeltaCopy(diff) {
        if (diff < 0) {
            return `Bajó ${formatTrendCount(Math.abs(diff))} vs corte anterior`;
        }

        if (diff > 0) {
            return `Subió ${formatTrendCount(diff)} vs corte anterior`;
        }

        return 'Sin cambio vs corte anterior';
    }

    function buildPasteurizadoraTrendSummaries(rows, sourceSeries, config) {
        return sourceSeries.map((serie, index) => {
            const current = rows.reduce((sum, row) => sum + getTrendLatestValue(row, serie.key), 0);
            const previous = rows.reduce((sum, row) => sum + getTrendPreviousValue(row, serie.key), 0);
            const diff = current - previous;
            const impacted = rows.filter((row) => getTrendLatestValue(row, serie.key) > 0).length;

            return {
                key: serie.key,
                label: String(serie.label || `Ventana ${index + 1}`),
                current,
                previous,
                diff,
                impacted,
                role: config.windowRoles?.[index] || 'Ventana de seguimiento',
                color: config.colors?.[index % (config.colors?.length || 1)]?.[1] || '#3b82f6'
            };
        });
    }

    function renderPasteurizadoraTrendExecutive(rows, sourceSeries, config) {
        const statusCard = document.getElementById(config.statusCardId);
        const statusTitle = document.getElementById(config.statusTitleId);
        const statusCopy = document.getElementById(config.statusCopyId);
        const statusNote = document.getElementById(config.statusNoteId);
        const windowsHost = document.getElementById(config.windowsId);
        const machineHost = document.getElementById(config.machineGridId);
        const captionNode = document.getElementById(config.captionId);
        const summaries = buildPasteurizadoraTrendSummaries(rows, sourceSeries, config);
        const recent = summaries[summaries.length - 1] || null;
        const mid = summaries[1] || null;
        const latestLabel = rows.find((row) => Array.isArray(row?.labels) && row.labels.length)?.labels?.slice(-1)?.[0]
            || config.dataset?.periodo?.label
            || 'periodo actual';

        if (machineHost) {
            machineHost.innerHTML = rows.length
                ? rows.map((row) => {
                    const currentTotal = getTrendCurrentTotalForRow(row, sourceSeries);
                    const state = currentTotal > 0 ? 'active' : 'inactive';
                    const icon = currentTotal > 0 ? 'fa-chart-line' : 'fa-circle-check';

                    return `
                        <span class="dashboard-trend-machine-pill ${state}">
                            <i class="fas ${icon}"></i>
                            ${escapeHtml(row.linea || 'N/A')}
                        </span>
                    `;
                }).join('')
                : `
                    <span class="dashboard-trend-machine-pill inactive">
                        <i class="fas fa-circle-info"></i>
                        Sin pasteurizadoras
                    </span>
                `;
        }

        if (windowsHost) {
            if (!summaries.length) {
                windowsHost.innerHTML = `
                    <article class="dashboard-trend-window-card" style="--window-accent: #64748b">
                        <div class="dashboard-trend-window-label">Sin datos</div>
                        <div class="dashboard-trend-window-value">0</div>
                        <div class="dashboard-trend-window-role">No hay información de ventanas para el periodo filtrado.</div>
                    </article>
                `;
            } else {
                windowsHost.innerHTML = summaries.map((summary) => {
                    const deltaClass = summary.diff < 0 ? 'positive' : (summary.diff > 0 ? 'alert' : 'neutral');
                    const deltaIcon = summary.diff < 0 ? 'fa-arrow-down' : (summary.diff > 0 ? 'fa-arrow-up' : 'fa-minus');

                    return `
                        <article class="dashboard-trend-window-card" style="--window-accent: ${summary.color}">
                            <div class="dashboard-trend-window-label">${escapeHtml(summary.label)}</div>
                            <div class="dashboard-trend-window-value">${formatTrendCount(summary.current)}</div>
                            <div class="dashboard-trend-window-role">${escapeHtml(summary.role)}</div>
                            <div class="dashboard-trend-window-delta ${deltaClass}">
                                <i class="fas ${deltaIcon}"></i>
                                <span>${trendDeltaCopy(summary.diff)}</span>
                            </div>
                        </article>
                    `;
                }).join('');
            }
        }

        if (statusCard && statusTitle && statusCopy) {
            let tone = 'neutral';
            let title = 'Monitoreo en curso';
            let copy = `La tendencia ${config.title} se mantiene en observación con datos de Pasteurizadora.`;

            if (!rows.length || !sourceSeries.length) {
                title = 'Sin datos en el periodo';
                copy = 'No hay daños registrados para construir esta tendencia con el filtro actual.';
            } else if ((recent?.current || 0) === 0) {
                tone = 'positive';
                title = 'Sin daños recientes';
                copy = `${recent.label} está en 0 daños al corte actual. Conviene mantener el seguimiento para confirmar estabilidad.`;
            } else if ((recent?.diff || 0) > 0 || (mid?.diff || 0) > 0) {
                tone = 'alert';
                title = 'Repunte reciente';
                copy = `${recent?.label || 'La ventana reciente'} o ${mid?.label || 'la ventana intermedia'} subieron frente al corte anterior. Revisa las pasteurizadoras con mayor incidencia.`;
            } else if ((recent?.diff || 0) <= 0 && (mid?.diff || 0) <= 0) {
                tone = 'positive';
                title = 'Tendencia bajo control';
                copy = `${recent?.label || 'La ventana reciente'} no aumenta frente al corte anterior y el comportamiento general se mantiene estable.`;
            }

            statusCard.className = `dashboard-trend-status dashboard-trend-status--${tone}`;
            statusTitle.textContent = title;
            statusCopy.textContent = copy;
        }

        if (statusNote) {
            const recentText = recent
                ? `${formatTrendCount(recent.impacted)} de ${formatTrendCount(rows.length)} pasteurizadoras con daños en ${recent.label}.`
                : 'Sin pasteurizadoras con daños recientes para esta ventana.';
            statusNote.textContent = recentText;
        }

        if (captionNode) {
            captionNode.textContent = `Corte actual: ${latestLabel}. Comparativo ${config.title} construido solo con análisis de Pasteurizadora.`;
        }
    }

    function buildPasteurizadoraTrendChart(canvasId, dataset, config = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        const rows = Array.isArray(dataset?.lineas) ? dataset.lineas : [];
        const select = document.getElementById(config.selectId);
        const selectedId = select?.value || dataset?.default_linea_id || rows[0]?.linea_id || '';
        const item = rows.find((row) => Number(row.linea_id) === Number(selectedId)) || rows[0] || null;
        const labels = Array.isArray(item?.labels) ? item.labels : [];
        const sourceSeries = Array.isArray(item?.series) ? item.series : [];

        if (select && item?.linea_id) {
            select.value = String(item.linea_id);
        }

        const colors = config.colors || [
            ['rgba(16, 185, 129, 0.88)', '#047857', 'rgba(16, 185, 129, 0.22)'],
            ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
            ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)'],
        ];
        const chartType = pasteurTrendChartTypes[config.key] || config.chartType || 'bar';
        const isBar = chartType === 'bar';
        const chartSeries = sourceSeries.map((serie, index) => ({
            label: String(serie.label || `Serie ${index + 1}`).replace(/dias/gi, 'días'),
            data: Array.isArray(serie.data) ? serie.data.map(value => Number(value || 0)) : [],
            backgroundColor: isBar ? colors[index % colors.length][0] : (colors[index % colors.length][2] || colors[index % colors.length][0]),
            borderColor: colors[index % colors.length][1],
            borderWidth: isBar ? 0 : (index === sourceSeries.length - 1 ? 4 : 3),
            borderDash: isBar ? [] : (index === 0 ? [8, 6] : []),
            borderRadius: isBar ? 10 : 0,
            borderSkipped: isBar ? false : undefined,
            tension: isBar ? 0 : 0.32,
            pointRadius: isBar ? 0 : 4,
            pointHoverRadius: isBar ? 0 : 7,
            pointBackgroundColor: colors[index % colors.length][1],
            pointBorderColor: '#ffffff',
            pointBorderWidth: isBar ? 0 : 2,
            fill: false,
            maxBarThickness: isBar ? 28 : undefined,
            categoryPercentage: isBar ? 0.72 : undefined,
            barPercentage: isBar ? 0.74 : undefined,
        }));

        renderPasteurizadoraTrendExecutive(item ? [item] : [], sourceSeries, config);

        return new Chart(canvas.getContext('2d'), {
            type: chartType,
            data: {
                labels,
                datasets: chartSeries,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false, drawTicks: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', padding: 8, precision: 0 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 12, weight: 600 }, color: '#334155', padding: 8 }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.raw} daños`
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 10, padding: 14, font: { size: 12, weight: 'bold' }, color: '#334155' }
                    }
                }
            }
        });
    }

    function refreshData() {
        window.location.reload();
    }

    function setAutoRefresh() {
        setInterval(() => {
            refreshData();
        }, 300000);
    }

    function initPasteurizadoraCarousels() {
        document.querySelectorAll('.lavadora-carousel').forEach(carousel => {
            const slides = carousel.querySelectorAll('.carousel-slide');
            const prevButton = carousel.querySelector('.carousel-prev');
            const nextButton = carousel.querySelector('.carousel-next');
            const dots = carousel.querySelectorAll('.carousel-dot');
            let currentIndex = 0;

            function showSlide(index) {
                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('active', slideIndex === index);
                });
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('active', dotIndex === index);
                });
                currentIndex = index;
            }

            function goNext() {
                if (slides.length <= 1) return;
                showSlide((currentIndex + 1) % slides.length);
            }

            function goPrev() {
                if (slides.length <= 1) return;
                showSlide((currentIndex - 1 + slides.length) % slides.length);
            }

            if (nextButton) {
                nextButton.addEventListener('click', goNext);
            }

            if (prevButton) {
                prevButton.addEventListener('click', goPrev);
            }

            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    const index = parseInt(dot.dataset.index, 10);
                    if (!isNaN(index)) {
                        showSlide(index);
                    }
                });
            });

            if (slides.length > 1) {
                setInterval(goNext, 6000);
            }
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function(char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function showPasteurizadoraDetail(pasteurizadora) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const estado = pasteurizadora.estado || {};
        const progreso = estado.progreso_revision || {};
        const criticos = estado.analisis_criticos || [];

        modalTitle.innerHTML = `Detalle - ${escapeHtml(pasteurizadora.nombre)}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (estado.nivel === 'riesgo' ? 'bg-orange-50 border-l-4 border-orange-500' : (estado.nivel === 'operativo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500'))}">
                <h4 class="font-bold text-lg mb-2">Estado: ${escapeHtml((estado.nivel || 'bueno').toUpperCase())}</h4>
                <p class="text-gray-700">${escapeHtml(estado.mensaje || 'Sin mensaje de estado')}</p>
            </div>

            <div class="mb-4">
                <h4 class="font-bold text-gray-800 mb-2">Avance de Revisión</h4>
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-sm text-gray-600">Avance</p>
                            <p class="font-semibold text-blue-600">${progreso.porcentaje || 0}%</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Revisados</p>
                            <p class="font-semibold text-green-600">${progreso.revisados || progreso.componentes_revisados || 0}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total</p>
                            <p class="font-semibold text-gray-700">${progreso.total || progreso.total_componentes || 0}</p>
                        </div>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: ${progreso.porcentaje || 0}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Último análisis: ${escapeHtml(estado.ultimo_analisis?.fecha || 'Sin registro')}</p>
                </div>
            </div>
        `;

        if (criticos.length > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Componentes Críticos</h4>
                    <div class="space-y-3">
            `;
            criticos.forEach(analisis => {
                html += `
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-gray-800">${escapeHtml(analisis.componente_nombre || 'Componente')}</div>
                                <p class="text-sm text-gray-600 mt-1">Módulo: ${escapeHtml(analisis.modulo || 'N/A')} · Lado: ${escapeHtml(analisis.lado || 'N/A')}</p>
                                <p class="text-xs text-gray-500 mt-1">Orden: ${escapeHtml(analisis.numero_orden || 'N/A')} · Fecha: ${escapeHtml(analisis.fecha_formateada || analisis.fecha_analisis || 'Sin fecha')}</p>
                            </div>
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">Crítico</span>
                        </div>
                        <p class="text-sm text-gray-700 mt-2">${escapeHtml(analisis.actividad || 'Sin descripción')}</p>
                    </div>
                `;
            });
            html += `</div></div>`;
        }

        if ((estado.acciones_pendientes || 0) > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta pasteurizadora tiene ${estado.acciones_pendientes} acción(es) pendiente(s).</p>
                    </div>
                </div>
            `;
        }

        html += `
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                @if($puedeVerMecanicaPasteurizadora)
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}?linea_id=${pasteurizadora.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Análisis
                </a>
                @endif
                @if($puedeVerPlanesPasteurizadora)
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}?linea_id=${pasteurizadora.id}" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">
                    <i class="fas fa-tasks mr-1"></i> Ver Plan
                </a>
                @endif
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `;

        modalBody.innerHTML = html;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('alertModal');
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('alertModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
</script>
@endsection
