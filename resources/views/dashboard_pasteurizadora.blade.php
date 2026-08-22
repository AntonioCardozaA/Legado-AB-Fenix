@extends('layouts.app')

@section('title', 'Pasteurizadoras')

@section('content')
@php
    $estadoPasteurizadoras = $estadoPasteurizadoras ?? [];
    $dashboardPasteurizadoraParte = $dashboardPasteurizadoraParte ?? \App\Models\AnalisisPasteurizadora::AREA_MECANICA;
    $dashboardPasteurizadoraParte = \App\Models\AnalisisPasteurizadora::normalizarArea($dashboardPasteurizadoraParte);
    $esDashboardMecanica = $dashboardPasteurizadoraParte === \App\Models\AnalisisPasteurizadora::AREA_MECANICA;
    $esDashboardCentral = $dashboardPasteurizadoraParte === \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA;
    $dashboardParteBaseQuery = request()->except('parte');
    $dashboardParteMecanicaUrl = route('dashboard.global.pasteurizadoras', array_merge($dashboardParteBaseQuery, [
        'parte' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA,
    ]));
    $dashboardParteCentralUrl = route('dashboard.global.pasteurizadoras', array_merge($dashboardParteBaseQuery, [
        'parte' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
    ]));
    $pasteurizadoras = collect($estadoPasteurizadoras);
    $resumenPasteurizadora = $resumenPasteurizadora ?? [];
    $fallasPorLineaPasteurizadora = collect($fallasPorLineaPasteurizadora ?? []);
    $historicoRevisionesPasteurizadora = collect($historicoRevisionesPasteurizadora ?? []);
    $analisis52124Pasteurizadora = $analisis52124Pasteurizadora ?? ['lineas' => [], 'criterios' => []];
    $analisis30147Pasteurizadora = $analisis30147Pasteurizadora ?? ['lineas' => [], 'criterios' => []];
    $planesAccionDashboardPasteurizadora = $planesAccionDashboardPasteurizadora ?? ['resumen' => [], 'estado_general' => [], 'por_linea' => [], 'planes' => []];
    $rankingDanosPasteurizadora = collect($rankingDanosPasteurizadora ?? []);
    $avanceRevisionPasteurizadora = $avanceRevisionPasteurizadora ?? ['labels' => [], 'porcentajes' => [], 'revisados' => [], 'totales' => [], 'lineas' => []];
    $ultimosAnalisisPasteurizadora = collect($ultimosAnalisisPasteurizadora ?? []);
    $trendFilters = $trendFilters ?? [];
    $usuarioActual = auth()->user();
    $puedeVerMecanicaPasteurizadora = $usuarioActual?->canAccessPasteurizadoraArea(\App\Models\AnalisisPasteurizadora::AREA_MECANICA) ?? false;
    $puedeVerCentralHidraulicaPasteurizadora = $usuarioActual?->canAccessPasteurizadoraArea(\App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA) ?? false;
    $puedeVerPlanesPasteurizadora = $puedeVerMecanicaPasteurizadora
        && ($usuarioActual?->canViewPlanActionType(\App\Models\User::MODULE_PASTEURIZADORA) ?? false);
    $puedeVerPlanesCentralHidraulica = $puedeVerCentralHidraulicaPasteurizadora
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
    $centralesHidraulicasPasteurizadora = collect($estadoCentralHidraulicaPasteurizadora ?? []);
    $fallasPorLineaCentralHidraulica = collect($fallasPorLineaCentralHidraulica ?? []);
    $historicoRevisionesCentralHidraulica = collect($historicoRevisionesCentralHidraulica ?? []);
    $analisis52124CentralHidraulica = $analisis52124CentralHidraulica ?? ['lineas' => [], 'criterios' => []];
    $analisis30147CentralHidraulica = $analisis30147CentralHidraulica ?? ['lineas' => [], 'criterios' => []];
    $planesAccionDashboardCentralHidraulica = $planesAccionDashboardCentralHidraulica ?? ['resumen' => [], 'estado_general' => [], 'por_linea' => [], 'planes' => []];
    $rankingDanosCentralHidraulica = collect($rankingDanosCentralHidraulica ?? []);
    $avanceRevisionCentralHidraulica = $avanceRevisionCentralHidraulica ?? ['labels' => [], 'porcentajes' => [], 'revisados' => [], 'totales' => [], 'lineas' => []];
    $resumenCentralHidraulicaPasteurizadora = $resumenCentralHidraulicaPasteurizadora ?? [
        'total_pasteurizadoras' => $centralesHidraulicasPasteurizadora->count(),
        'total_analisis' => 0,
        'alertas_criticas' => 0,
        'en_riesgo' => 0,
        'requiere_revision' => 0,
        'buen_estado' => 0,
        'pendientes_accion' => 0,
    ];
    $avanceCentralPromedio = (int) ($avanceRevisionCentralHidraulica['promedio'] ?? round($centralesHidraulicasPasteurizadora->avg('estado.progreso_revision.porcentaje') ?? 0));
    $totalCentralRevisados = (int) ($avanceRevisionCentralHidraulica['total_revisados'] ?? $centralesHidraulicasPasteurizadora->sum(fn($item) => (int) data_get($item, 'estado.progreso_revision.revisados', 0)));
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

    .dashboard-part-switch {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px;
        border: 1px solid var(--border-light);
        border-radius: 10px;
        background: #edf2f7;
    }

    .dashboard-part-btn {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 8px;
        padding: 8px 12px;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.1;
        text-decoration: none;
        transition: var(--transition);
        white-space: nowrap;
    }

    .dashboard-part-btn:hover {
        background: #ffffff;
        color: #1d4ed8;
        box-shadow: var(--shadow-sm);
    }

    .dashboard-part-btn.active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: var(--shadow-md);
    }

    .dashboard-part-btn.dashboard-part-btn--central.active {
        background: #0f766e;
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
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
        align-items: stretch;
    }

    .stat-card {
        --stat-accent: var(--primary-blue);
        --stat-soft: #dbeafe;
        background: white;
        border-radius: 12px;
        padding: 12px 14px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--medium-gray) !important;
        border-top: 4px solid var(--stat-accent) !important;
        transition: var(--transition);
        min-width: 0;
    }

    .stat-card.stat-card--primary {
        --stat-accent: var(--primary-blue);
        --stat-soft: #dbeafe;
    }

    .stat-card.stat-card--danger {
        --stat-accent: var(--danger-red);
        --stat-soft: #fee2e2;
    }

    .stat-card.stat-card--risk {
        --stat-accent: var(--operational-orange);
        --stat-soft: #ffedd5;
    }

    .stat-card.stat-card--warning {
        --stat-accent: var(--warning-yellow);
        --stat-soft: #fef3c7;
    }

    .stat-card.stat-card--success {
        --stat-accent: var(--success-green);
        --stat-soft: #d1fae5;
    }

    .stat-card.stat-card--action {
        --stat-accent: #6366f1;
        --stat-soft: #e0e7ff;
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
        color: var(--stat-accent) !important;
    }

    .stat-card .stat-icon {
        float: right;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: var(--stat-soft) !important;
        font-size: 20px;
        color: var(--stat-accent) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
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
        padding: 18px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(59, 130, 246, 0.1);
        margin-bottom: 0;
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
        height: 4px;
        background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 25%, #10b981 50%, #f59e0b 75%, #3b82f6 100%);
        background-size: 400% 100%;
        animation: gradientShift 8s ease infinite;
    }

    @keyframes gradientShift {
        0%, 100% { background-position: 0% center; }
        50% { background-position: 100% center; }
    }

    .chart-card:hover {
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12), 0 4px 10px rgba(59, 130, 246, 0.12);
        transform: translateY(-3px);
        border-color: rgba(59, 130, 246, 0.2);
    }

    .chart-card h3 {
        font-size: 15px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 14px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        letter-spacing: -0.3px;
        padding-bottom: 10px;
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
        height: 248px;
        position: relative;
        width: 100%;
        min-width: 0;
        padding: 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, rgba(139, 92, 246, 0.02) 100%);
        border-radius: 12px;
        margin: 2px 0;
    }

    .chart-description,
    .table-footer,
    .ranking-footer {
        margin-top: 10px;
        padding: 9px 12px;
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

    .dashboard-history-trend-grid {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 760px), 1fr));
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

    .ranking-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ranking-item {
        gap: 10px;
        min-width: 0;
        margin-bottom: 0;
        padding: 10px 14px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.92) 100%);
        box-shadow: var(--shadow-sm);
    }

    .ranking-item:hover {
        transform: translateY(-2px);
        background: linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(239, 246, 255, 0.92) 100%);
        box-shadow: 0 10px 24px rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.18);
    }

    .ranking-position {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-size: 13px;
    }

    .ranking-asset {
        display: flex;
        flex: 1 1 auto;
        align-items: center;
        gap: 10px;
        margin-left: 10px;
        min-width: 0;
    }

    .asset-media {
        display: none;
    }

    .ranking-info {
        min-width: 0;
        margin-left: 0;
        gap: 2px;
    }

    .ranking-linea {
        font-size: 12px;
    }

    .ranking-puntaje {
        flex-wrap: wrap;
        min-width: 0;
        gap: 4px;
        font-size: 10px;
        line-height: 1.35;
    }

    .ranking-meta {
        display: none;
        margin-top: 3px;
        color: var(--text-secondary);
        font-size: 9px;
        font-weight: 600;
        line-height: 1.3;
    }

    .ranking-status-stack {
        display: flex;
        flex: 0 0 auto;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        max-width: 100%;
        min-width: 0;
    }

    .ranking-status-stack .severity-pill,
    .ranking-badge {
        padding: 4px 8px;
        font-size: 9px;
        gap: 4px;
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
        max-width: 600px;
        width: 100%;
        min-width: 0;
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
        gap: 12px;
    }

    .modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
        overflow-wrap: anywhere;
    }

    .modal-body .flex,
    .modal-body .flex > * {
        min-width: 0;
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: 1px solid var(--border-light);
        color: var(--text-secondary);
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        background: var(--danger-red);
        color: white;
        border-color: var(--danger-red);
        transform: rotate(90deg);
    }

    .componente-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
        min-width: 0;
    }

    .componente-icono {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 4px;
        flex: 0 0 auto;
    }

    .componente-icono img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .componente-nombre {
        font-weight: 600;
        color: var(--text-primary);
        overflow-wrap: anywhere;
    }

    @media (min-width: 1280px) {
        .stats-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
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

        .dashboard-panels-grid,
        .dashboard-history-trend-grid {
            grid-template-columns: 1fr;
        }

        .lavadoras-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 295px), 1fr));
        }

        .dashboard-trend-brief {
            grid-template-columns: 1fr;
        }

        .trend-card-side .trend-filter-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: end;
        }

        .trend-card-side .panel-select,
        .trend-card-side .trend-filter-field,
        .trend-card-side .trend-filter-field input,
        .trend-card-side .trend-filter-button {
            width: 100%;
            min-width: 0;
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

        .dashboard-part-switch {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        .dashboard-part-btn {
            width: 100%;
            white-space: normal;
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

        .ranking-item {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .ranking-asset {
            flex: 1 1 calc(100% - 46px);
            margin-left: 0;
        }

        .ranking-status-stack {
            width: 100%;
            align-items: flex-start;
            padding-left: 46px;
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

        .trend-card-side .trend-filter-form {
            grid-template-columns: 1fr;
        }

        .modal {
            padding: 10px;
        }

        .modal-content {
            max-height: calc(100vh - 24px);
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
            max-height: calc(100vh - 104px);
        }

        .modal-body .grid {
            grid-template-columns: 1fr !important;
        }

        .modal-body .justify-end,
        .modal-body .justify-between {
            flex-wrap: wrap;
            gap: 10px;
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

        .modal-content {
            border-radius: 18px;
        }

        .modal-body .justify-end > * {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            text-align: center;
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
                @auth
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        Rol: {{ $userRoleLabel ?? auth()->user()->role_label }}
                    </p>
                @endauth
            </div>
            <div class="dashboard-actions">
                <button onclick="refreshData()" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
                <div class="dashboard-part-switch" aria-label="Vistas del dashboard de Pasteurizadora">
                    @if($puedeVerMecanicaPasteurizadora)
                        <a href="{{ $dashboardParteMecanicaUrl }}"
                           class="dashboard-part-btn {{ $esDashboardMecanica ? 'active' : '' }}"
                           aria-current="{{ $esDashboardMecanica ? 'page' : 'false' }}">
                            <i class="fas fa-cogs"></i>
                            <span>Parte Mecanica</span>
                        </a>
                    @endif
                    @if($puedeVerCentralHidraulicaPasteurizadora)
                        <a href="{{ $dashboardParteCentralUrl }}"
                           class="dashboard-part-btn dashboard-part-btn--central {{ $esDashboardCentral ? 'active' : '' }}"
                           aria-current="{{ $esDashboardCentral ? 'page' : 'false' }}">
                            <i class="fas fa-droplet"></i>
                            <span>Central Hidraulica</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @if($esDashboardMecanica)
    <div class="stats-grid">
        <div class="stat-card stat-card--primary">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-label">Total Pasteurizadoras</div>
            <div class="stat-value">{{ $resumenPasteurizadora['total_pasteurizadoras'] }}</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Críticas</div>
            <div class="stat-value">{{ $resumenPasteurizadora['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card stat-card--risk">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">Severo / Moderado</div>
            <div class="stat-value">{{ $resumenPasteurizadora['en_riesgo'] }}</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-icon"><i class="fas fa-tools"></i></div>
            <div class="stat-label">Requiere Revisión</div>
            <div class="stat-value">{{ $resumenPasteurizadora['requiere_revision'] }}</div>
        </div>
        <div class="stat-card stat-card--success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value">{{ $resumenPasteurizadora['buen_estado'] }}</div>
        </div>
        <div class="stat-card stat-card--action">
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
                        <i class="fas fa-temperature-high status-icon"></i>
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
                                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" onerror="this.src='{{ $item['fallback_image'] ?? asset('images/icono_pas.png') }}'" />
                                                </div>
                                            @else
                                                <div class="carousel-slide-icon">
                                                    <i class="fas {{ $item['icon'] ?? 'fa-info-circle' }}"></i>
                                                </div>
                                            @endif
                                            <div class="carousel-slide-info">
                                                @if(!empty($item['estado_label']))
                                                    <div class="mb-1">
                                                        <span class="severity-pill {{ $item['estado_key'] ?? 'estable' }}">
                                                            {{ $item['estado_label'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                                <div class="carousel-slide-title">{{ $item['title'] }}</div>
                                                @if(!empty($item['subtitle']) && $item['subtitle'] !== ($item['meta'] ?? null))
                                                    <div class="carousel-slide-subtitle">{{ $item['subtitle'] }}</div>
                                                @endif
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

                    @php
                        $conteoAlertas = $estado['conteo_alertas'] ?? [];
                        $totalAlertas = array_sum($conteoAlertas);
                    @endphp

                    @if($totalAlertas > 0)
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if(($conteoAlertas['critico'] ?? 0) > 0)
                                <span class="severity-pill critico">{{ $conteoAlertas['critico'] }} requiere cambio</span>
                            @endif
                            @if(($conteoAlertas['severo'] ?? 0) > 0)
                                <span class="severity-pill severo">{{ $conteoAlertas['severo'] }} severo</span>
                            @endif
                            @if(($conteoAlertas['moderado'] ?? 0) > 0)
                                <span class="severity-pill moderado">{{ $conteoAlertas['moderado'] }} moderado</span>
                            @endif
                            @if(($conteoAlertas['revision'] ?? 0) > 0)
                                <span class="severity-pill revision">{{ $conteoAlertas['revision'] }} revision</span>
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
        <div class="chart-card fallas-card">
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
    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card ranking-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Daño</span>
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
                        <div class="ranking-asset">
                            <div class="asset-media">
                                <i class="fas fa-temperature-high" style="font-size: 18px; color: #2563eb;"></i>
                            </div>
                        <div class="ranking-info">
                            <div class="ranking-linea">{{ $item['linea'] ?? $item['nombre'] }}</div>
                            <div class="ranking-puntaje">
                                <i class="fas fa-triangle-exclamation"></i>
                                Criticas: {{ $item['criticas'] ?? 0 }} - Severo / Moderado: {{ ($item['severos'] ?? 0) + ($item['moderados'] ?? 0) }} - Revision: {{ $item['requiere_revision'] ?? 0 }}
                            </div>
                            <div class="ranking-meta">
                                Total con dano: {{ $item['total_danos'] ?? 0 }} de {{ $item['total_componentes'] ?? 0 }} registros - Impacto {{ number_format((float) ($item['porcentaje_impacto'] ?? 0), 1) }}% - Revision: {{ $item['fecha_analisis_humana'] ?? 'Sin fecha' }}
                            </div>
                        </div>
                        </div>
                        <div class="ranking-status-stack">
                            <span class="severity-pill {{ $item['prioridad'] ?? 'estable' }}">{{ $item['prioridad_label'] ?? $estadoLabel }}</span>
                        <div class="ranking-badge">
                            <i class="fas fa-bolt"></i>
                            {{ number_format((float) ($item['total_danos'] ?? $pendientes), 0) }} danos
                        </div>
                        </div>
                    </li>
                @empty
                    <li class="ranking-item">
                        <div class="ranking-position">0</div>
                        <div class="ranking-asset">
                        <div class="ranking-info">
                            <div class="ranking-linea">Sin datos</div>
                            <div class="ranking-puntaje">No hay pasteurizadoras para priorizar</div>
                        </div>
                        </div>
                    </li>
                @endforelse
            </ul>
            <div class="ranking-footer">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Daños activos
                </div>
            </div>
        </div>

    </div>

    <div class="dashboard-panels-grid">
        <div class="chart-card planes-card">
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

        <div class="chart-card avance-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Avance de Revisión Mecánica</span>
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

    <div class="dashboard-panels-grid dashboard-history-trend-grid">
        <div class="chart-card historico-card historico-dashboard-card">
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
                <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_MECANICA }}">
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
                    <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4', ['area' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA]) }}" class="trend-open-link">
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
                <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_MECANICA }}">
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
                    <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7', ['area' => \App\Models\AnalisisPasteurizadora::AREA_MECANICA]) }}" class="trend-open-link">
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

    @elseif($esDashboardCentral)
        @php
            $planesCentralResumen = $planesAccionDashboardCentralHidraulica['resumen'] ?? [];
            $planesCentralEstado = $planesAccionDashboardCentralHidraulica['estado_general'] ?? [];
            $planesCentralActivos = collect($planesAccionDashboardCentralHidraulica['planes'] ?? []);
            $avanceCentralLineas = collect($avanceRevisionCentralHidraulica['lineas'] ?? [])->sortBy('porcentaje')->values();
        @endphp

        <div class="stats-grid">
            <div class="stat-card stat-card--primary">
                <div class="stat-icon"><i class="fas fa-oil-can"></i></div>
                <div class="stat-label">Centrales Configuradas</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['total_pasteurizadoras'] }}</div>
            </div>
            <div class="stat-card stat-card--danger">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-label">Alertas Criticas</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['alertas_criticas'] }}</div>
            </div>
            <div class="stat-card stat-card--risk">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-label">Severo / Moderado</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['en_riesgo'] }}</div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-icon"><i class="fas fa-tools"></i></div>
                <div class="stat-label">Requiere Revision</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['requiere_revision'] }}</div>
            </div>
            <div class="stat-card stat-card--success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Buen Estado</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['buen_estado'] }}</div>
            </div>
            <div class="stat-card stat-card--action">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-label">Pendientes Accion</div>
                <div class="stat-value">{{ $resumenCentralHidraulicaPasteurizadora['pendientes_accion'] }}</div>
            </div>
        </div>

        <div class="section-title">
            <i class="fas fa-droplet"></i>
            CENTRAL HIDRAULICA DE PASTEURIZADORAS
        </div>

        <div class="lavadoras-grid">
            @forelse($centralesHidraulicasPasteurizadora as $central)
                @php
                    $estado = $central['estado'];
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
                    $conteoAlertas = $estado['conteo_alertas'] ?? [];
                    $totalAlertas = array_sum($conteoAlertas);
                @endphp
                <div class="lavadora-card {{ $cardClass }}">
                    <div class="lavadora-card-header">
                        <div class="lavadora-nombre">
                            <i class="fas fa-oil-can status-icon"></i>
                            {{ $central['nombre'] }}
                        </div>
                        <div>
                            <span class="status-tag {{ $nivel === 'bueno' ? 'bueno' : ($nivel === 'operativo' ? 'operativo' : ($nivel === 'riesgo' ? 'riesgo' : 'critico')) }}">
                                <i class="fas {{ $nivel === 'bueno' ? 'fa-check-circle' : ($nivel === 'operativo' ? 'fa-tools' : ($nivel === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle')) }}"></i>
                                {{ $nivel === 'bueno' ? 'Buen estado' : ($nivel === 'operativo' ? 'Requiere revision' : ($nivel === 'riesgo' ? 'Severo / Moderado' : 'Critico')) }}
                            </span>
                        </div>
                    </div>
                    <div class="lavadora-card-body">
                        <div class="lavadora-mensaje">
                            <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                            {{ $estado['mensaje'] }}
                        </div>

                        @if(isset($estado['alert_carousel']) && count($estado['alert_carousel']) > 0)
                            <div class="lavadora-carousel" id="central-carousel-{{ $central['id'] }}">
                                <div class="lavadora-carousel-track">
                                    @foreach($estado['alert_carousel'] as $carouselIndex => $item)
                                        <div class="carousel-slide {{ $carouselIndex === 0 ? 'active' : '' }}" data-slide="{{ $carouselIndex }}">
                                            <div class="carousel-slide-content">
                                                <div class="carousel-slide-icon">
                                                    <i class="fas {{ $item['icon'] ?? 'fa-droplet' }}"></i>
                                                </div>
                                                <div class="carousel-slide-info">
                                                    @if(!empty($item['estado_label']))
                                                        <div class="mb-1">
                                                            <span class="severity-pill {{ $item['estado_key'] ?? 'estable' }}">
                                                                {{ $item['estado_label'] }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <div class="carousel-slide-title">{{ $item['title'] }}</div>
                                                    @if(!empty($item['subtitle']) && $item['subtitle'] !== ($item['meta'] ?? null))
                                                        <div class="carousel-slide-subtitle">{{ $item['subtitle'] }}</div>
                                                    @endif
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

                        @if($totalAlertas > 0)
                            <div class="flex flex-wrap gap-2 mb-3">
                                @if(($conteoAlertas['critico'] ?? 0) > 0)
                                    <span class="severity-pill critico">{{ $conteoAlertas['critico'] }} requiere cambio</span>
                                @endif
                                @if(($conteoAlertas['severo'] ?? 0) > 0)
                                    <span class="severity-pill severo">{{ $conteoAlertas['severo'] }} severo</span>
                                @endif
                                @if(($conteoAlertas['moderado'] ?? 0) > 0)
                                    <span class="severity-pill moderado">{{ $conteoAlertas['moderado'] }} moderado</span>
                                @endif
                                @if(($conteoAlertas['revision'] ?? 0) > 0)
                                    <span class="severity-pill revision">{{ $conteoAlertas['revision'] }} revision</span>
                                @endif
                            </div>
                        @endif

                        @if(!empty($estado['pisos']))
                            <div class="flex flex-wrap gap-2 mb-3">
                                @foreach($estado['pisos'] as $piso)
                                    <span class="severity-pill {{ ($piso['alertas'] ?? 0) > 0 ? 'revision' : 'estable' }}">
                                        {{ $piso['label'] }} {{ (int) ($piso['porcentaje'] ?? 0) }}%
                                    </span>
                                @endforeach
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
                        <button onclick='showCentralHidraulicaDetail(@json($central))'
                                class="lavadora-card-action">
                            <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                        </button>
                    </div>
                </div>
            @empty
                <div class="chart-card">
                    <h3>
                        <i class="fas fa-oil-can"></i>
                        <span>Sin centrales configuradas</span>
                    </h3>
                    <div class="chart-description">
                        <i class="fas fa-info-circle"></i>
                        Aun no hay lineas con configuracion de Central Hidraulica.
                    </div>
                </div>
            @endforelse
        </div>

        <div class="dashboard-panels-grid">
            <div class="chart-card fallas-card">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    <span>Fallas por Linea</span>
                </h3>
                <div class="chart-container">
                    <canvas id="fallasCentralHidraulicaChart"></canvas>
                </div>
            </div>
        </div>

        <div class="dashboard-panels-grid">
            <div class="chart-card ranking-card">
                <h3>
                    <i class="fas fa-trophy"></i>
                    <span>Ranking de Daño Hidraulico</span>
                </h3>
                <ul class="ranking-list">
                    @forelse($rankingDanosCentralHidraulica->take(8) as $index => $item)
                        @php
                            $estado = $item['estado'];
                            $nivelEstado = $estado['nivel'] ?? 'bueno';
                            $estadoLabel = $nivelEstado === 'bueno'
                                ? 'Buen estado'
                                : ($nivelEstado === 'operativo'
                                    ? 'Requiere revision'
                                    : ($nivelEstado === 'riesgo' ? 'Severo / Moderado' : 'Critico'));
                            $pendientes = (int) ($estado['acciones_pendientes'] ?? 0);
                        @endphp
                        <li class="ranking-item">
                            <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                                {{ $index + 1 }}
                            </div>
                            <div class="ranking-asset">
                                <div class="asset-media">
                                    <i class="fas fa-oil-can" style="font-size: 18px; color: #0f766e;"></i>
                                </div>
                                <div class="ranking-info">
                                    <div class="ranking-linea">{{ $item['linea'] ?? $item['nombre'] }}</div>
                                    <div class="ranking-puntaje">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        Criticas: {{ $item['criticas'] ?? 0 }} - Severo / Moderado: {{ ($item['severos'] ?? 0) + ($item['moderados'] ?? 0) }} - Revision: {{ $item['requiere_revision'] ?? 0 }}
                                    </div>
                                    <div class="ranking-meta">
                                        Pisos: {{ $item['pisos_afectados'] ?? 'Sin alertas' }} - Impacto {{ number_format((float) ($item['porcentaje_impacto'] ?? 0), 1) }}% - Revision: {{ $item['fecha_analisis_humana'] ?? 'Sin fecha' }}
                                    </div>
                                </div>
                            </div>
                            <div class="ranking-status-stack">
                                <span class="severity-pill {{ $item['prioridad'] ?? 'estable' }}">{{ $item['prioridad_label'] ?? $estadoLabel }}</span>
                                <div class="ranking-badge">
                                    <i class="fas fa-bolt"></i>
                                    {{ number_format((float) ($item['total_danos'] ?? $pendientes), 0) }} danos
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="ranking-item">
                            <div class="ranking-position">0</div>
                            <div class="ranking-asset">
                                <div class="ranking-info">
                                    <div class="ranking-linea">Sin datos</div>
                                    <div class="ranking-puntaje">No hay centrales para priorizar</div>
                                </div>
                            </div>
                        </li>
                    @endforelse
                </ul>
                <div class="ranking-footer">
                    <div>
                        <i class="fas fa-info-circle"></i>
                        Daños activos de Central Hidraulica
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-panels-grid">
            <div class="chart-card planes-card">
                <h3>
                    <i class="fas fa-tasks"></i>
                    <span>Plan de Accion</span>
                </h3>
                <div class="status-banner {{ $planesCentralEstado['nivel'] ?? 'estable' }}">
                    <i class="fas fa-clipboard-check"></i>
                    <span>{{ $planesCentralEstado['label'] ?? 'Controlado' }}: {{ $planesCentralEstado['mensaje'] ?? 'Sin planes abiertos con riesgo inmediato.' }}</span>
                </div>
                <div class="mini-stats-grid">
                    <div class="mini-stat info">
                        <div class="mini-stat-label">Activos</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['activos'] ?? 0 }}</div>
                    </div>
                    <div class="mini-stat danger">
                        <div class="mini-stat-label">Alta prioridad</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['prioridad_alta'] ?? 0 }}</div>
                    </div>
                    <div class="mini-stat warning">
                        <div class="mini-stat-label">Prox. 7 dias</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['proximos_7_dias'] ?? 0 }}</div>
                    </div>
                    <div class="mini-stat success">
                        <div class="mini-stat-label">Cierre</div>
                        <div class="mini-stat-value">{{ $planesCentralResumen['avance'] ?? 0 }}%</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="planesCentralHidraulicaChart"></canvas>
                </div>
                <div class="worklist">
                    @forelse($planesCentralActivos->take(4) as $plan)
                        <div class="work-item">
                            <div class="work-item-top">
                                <div>
                                    <div class="work-title">{{ $plan['linea'] }} - {{ Str::limit($plan['actividad'] ?? 'Sin actividad', 62) }}</div>
                                    <div class="work-meta">Proxima fecha: {{ $plan['proxima_fecha_humana'] ?? 'Sin fecha' }}</div>
                                </div>
                                <span class="severity-pill {{ ($plan['prioridad'] ?? 'baja') === 'alta' ? 'critico' : (($plan['prioridad'] ?? 'baja') === 'media' ? 'severo' : 'estable') }}">
                                    {{ $plan['prioridad_label'] ?? 'Baja' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="work-item">
                            <div class="work-title">Sin planes activos</div>
                            <div class="work-meta">No hay actividades abiertas de plan de accion hidraulico.</div>
                        </div>
                    @endforelse
                </div>
                <div class="table-footer">
                    <i class="fas fa-info-circle"></i>
                    <span>Planes reales del modulo Pasteurizadora filtrados por Central Hidraulica.</span>
                    @if($puedeVerPlanesCentralHidraulica)
                        <a href="{{ route('plan-accion.index', [
                            'tipo' => \App\Models\User::MODULE_PASTEURIZADORA,
                            'area_pasteurizadora' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                        ]) }}">Abrir plan</a>
                    @endif
                </div>
            </div>

            <div class="chart-card avance-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>Avance de Revision Hidraulica</span>
                </h3>
                <div class="mini-stats-grid compact">
                    <div class="mini-stat info">
                        <div class="mini-stat-label">Promedio</div>
                        <div class="mini-stat-value">{{ $avanceCentralPromedio }}%</div>
                        <div class="mini-stat-meta">avance global</div>
                    </div>
                    <div class="mini-stat success">
                        <div class="mini-stat-label">Revisados</div>
                        <div class="mini-stat-value">{{ number_format($totalCentralRevisados) }}</div>
                        <div class="mini-stat-meta">posiciones revisadas</div>
                    </div>
                
                </div>
                <div class="chart-container">
                    <canvas id="avanceRevisionCentralHidraulicaChart"></canvas>
                </div>
                <div class="linea-breakdown">
                    @forelse($avanceCentralLineas->take(4) as $lineaAvance)
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
                            <div class="breakdown-meta">Aun no hay revisiones hidraulicas para graficar.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="dashboard-panels-grid dashboard-history-trend-grid">
            <div class="chart-card historico-card historico-dashboard-card">
                <h3>
                    <i class="fas fa-history"></i>
                    <span>Historico de Revisiones</span>
                </h3>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-layer-group" style="color: #3b82f6;"></i> Piso / Componente</th>
                                <th><i class="fas fa-calendar-alt" style="color: #8b5cf6;"></i> Ultimo analisis</th>
                                <th class="text-right"><i class="fas fa-hashtag" style="color: #10b981;"></i> Analisis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historicoRevisionesCentralHidraulica as $item)
                                <tr>
                                    <td data-label="Piso / Componente"><i class="fas fa-oil-can mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                    <td data-label="Ultimo analisis">{{ $item['ultimo_analisis'] }}</td>
                                    <td data-label="Analisis">{{ $item['total_analisis'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">Sin analisis registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <i class="fas fa-info-circle"></i>
                    <span>Informacion conectada con el historial de Central Hidraulica</span>
                    @if($puedeVerCentralHidraulicaPasteurizadora)
                        <a href="{{ route('pasteurizadora.central-hidraulica.historico-revisados') }}">Abrir historico</a>
                    @endif
                </div>
            </div>

            <div class="chart-card trend-card trend-card-primary dashboard-panel dashboard-trend-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>Central Hidraulica 52-12-4 | Tendencia de Daños</span>
                </h3>
                <div class="dashboard-trend-main-header">
                    <div class="dashboard-trend-title-block">
                        <i class="fas fa-droplet"></i>
                        <div>
                            <div class="dashboard-trend-eyebrow">Analisis de tendencia Central Hidraulica</div>
                            <h3 class="dashboard-trend-heading">Analisis 52-12-4</h3>
                            <p class="dashboard-trend-subcopy">Lectura ejecutiva por central con ventanas de 52, 12 y 4 semanas.</p>
                        </div>
                    </div>
                </div>
                <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters">
                    <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA }}">
                    <select id="analisis52124CentralHidraulicaLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="central52124">
                        @forelse(($analisis52124CentralHidraulica['lineas'] ?? []) as $lineaTrend)
                            <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis52124CentralHidraulica, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                        @empty
                            <option value="">Sin centrales</option>
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
                        <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-52-12-4', ['area' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]) }}" class="trend-open-link">
                            <i class="fas fa-up-right-from-square"></i>
                            Abrir
                        </a>
                    @endif
                </form>
                <div class="dashboard-trend-machine-strip">
                    <div class="dashboard-trend-machine-header">
                        <i class="fas fa-industry"></i>
                        <span>Centrales incluidas</span>
                    </div>
                    <div id="trendCentral52124MachineGrid" class="dashboard-trend-machine-grid"></div>
                </div>
                <div class="dashboard-trend-brief">
                    <div id="trendCentral52124StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                        <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                        <div id="trendCentral52124StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                        <p id="trendCentral52124StatusCopy" class="dashboard-trend-status-copy">
                            Se esta calculando el comportamiento reciente con informacion de Central Hidraulica.
                        </p>
                        <div id="trendCentral52124StatusNote" class="dashboard-trend-status-note">Ventanas: 52, 12 y 4 semanas.</div>
                    </div>
                    <div id="trendCentral52124WindowCards" class="dashboard-trend-window-grid"></div>
                </div>
                <div class="dashboard-trend-chart-shell">
                    <div class="dashboard-trend-chart-header">
                        <div class="dashboard-trend-chart-title">
                            <i class="fas fa-chart-column"></i>
                            <div>
                                <h4>Comparativo por central</h4>
                                <p>Ultimo corte disponible para cada ventana de analisis.</p>
                            </div>
                        </div>
                        <div class="dashboard-trend-view-selector">
                            <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="central52124" data-pasteur-trend-type="bar">Barras</button>
                            <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="central52124" data-pasteur-trend-type="line">Linea</button>
                        </div>
                        <div id="trendCentral52124Caption" class="dashboard-trend-caption">Corte actual de tendencia 52-12-4.</div>
                    </div>
                    <div class="chart-container dashboard-trend-chart-container">
                        <canvas id="analisis52124CentralHidraulicaChart"></canvas>
                    </div>
                </div>
                <div class="chart-description">
                    <i class="fas fa-info-circle"></i>
                    <span>Tendencia calculada solo con registros de Central Hidraulica</span>
                </div>
            </div>

            <div class="chart-card trend-card trend-card-side dashboard-panel dashboard-trend-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>Central Hidraulica 30-14-7 | Tendencia de Daños</span>
                </h3>
                <div class="dashboard-trend-main-header">
                    <div class="dashboard-trend-title-block">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <div class="dashboard-trend-eyebrow">Analisis de tendencia Central Hidraulica</div>
                            <h3 class="dashboard-trend-heading">Analisis 30-14-7</h3>
                            <p class="dashboard-trend-subcopy">Lectura ejecutiva por central con ventanas de 30, 14 y 7 dias.</p>
                        </div>
                    </div>
                </div>
                <form method="GET" action="{{ route('dashboard.global.pasteurizadoras') }}" class="trend-filter-form dashboard-trend-filters">
                    <input type="hidden" name="parte" value="{{ \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA }}">
                    <select id="analisis30147CentralHidraulicaLineaSelect" class="panel-select pasteur-trend-line-select" data-pasteur-trend-card="central30147">
                        @forelse(($analisis30147CentralHidraulica['lineas'] ?? []) as $lineaTrend)
                            <option value="{{ $lineaTrend['linea_id'] }}" @selected((int) data_get($analisis30147CentralHidraulica, 'default_linea_id') === (int) $lineaTrend['linea_id'])>{{ $lineaTrend['linea'] }}</option>
                        @empty
                            <option value="">Sin centrales</option>
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
                        <a href="{{ route('analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7', ['area' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA]) }}" class="trend-open-link">
                            <i class="fas fa-up-right-from-square"></i>
                            Abrir
                        </a>
                    @endif
                </form>
                <div class="dashboard-trend-machine-strip">
                    <div class="dashboard-trend-machine-header">
                        <i class="fas fa-industry"></i>
                        <span>Centrales incluidas</span>
                    </div>
                    <div id="trendCentral30147MachineGrid" class="dashboard-trend-machine-grid"></div>
                </div>
                <div class="dashboard-trend-brief">
                    <div id="trendCentral30147StatusCard" class="dashboard-trend-status dashboard-trend-status--neutral">
                        <div class="dashboard-trend-eyebrow">Estado de seguimiento</div>
                        <div id="trendCentral30147StatusTitle" class="dashboard-trend-status-title">Leyendo tendencia reciente...</div>
                        <p id="trendCentral30147StatusCopy" class="dashboard-trend-status-copy">
                            Se esta calculando el comportamiento reciente con informacion de Central Hidraulica.
                        </p>
                        <div id="trendCentral30147StatusNote" class="dashboard-trend-status-note">Ventanas: 30, 14 y 7 dias.</div>
                    </div>
                    <div id="trendCentral30147WindowCards" class="dashboard-trend-window-grid"></div>
                </div>
                <div class="dashboard-trend-chart-shell">
                    <div class="dashboard-trend-chart-header">
                        <div class="dashboard-trend-chart-title">
                            <i class="fas fa-chart-column"></i>
                            <div>
                                <h4>Comparativo por central</h4>
                                <p>Ultimo corte disponible para cada ventana de analisis.</p>
                            </div>
                        </div>
                        <div class="dashboard-trend-view-selector">
                            <button type="button" class="dashboard-trend-view-btn active" data-pasteur-trend-card="central30147" data-pasteur-trend-type="bar">Barras</button>
                            <button type="button" class="dashboard-trend-view-btn" data-pasteur-trend-card="central30147" data-pasteur-trend-type="line">Linea</button>
                        </div>
                        <div id="trendCentral30147Caption" class="dashboard-trend-caption">Corte actual de tendencia 30-14-7.</div>
                    </div>
                    <div class="chart-container dashboard-trend-chart-container">
                        <canvas id="analisis30147CentralHidraulicaChart"></canvas>
                    </div>
                </div>
                <div class="chart-description">
                    <i class="fas fa-info-circle"></i>
                    <span>Seguimiento calculado solo con registros de Central Hidraulica</span>
                </div>
            </div>
        </div>
    </div>
    @endif

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
    let fallasPasteurizadoraChart, planesPasteurizadoraChart, avanceRevisionPasteurizadoraChart, analisis52124PasteurizadoraChart, analisis30147PasteurizadoraChart;
    let fallasCentralHidraulicaChart, planesCentralHidraulicaChart, avanceRevisionCentralHidraulicaChart, analisis52124CentralHidraulicaChart, analisis30147CentralHidraulicaChart;
    const pasteurizadorasData = @json($esDashboardMecanica ? $pasteurizadoras->values() : []);
    const fallasPorLineaPasteurizadora = @json($esDashboardMecanica ? $fallasPorLineaPasteurizadora->values() : []);
    const planesAccionDashboardPasteurizadora = @json($esDashboardMecanica ? $planesAccionDashboardPasteurizadora : ['por_linea' => []]);
    const avanceRevisionPasteurizadora = @json($esDashboardMecanica ? $avanceRevisionPasteurizadora : ['labels' => [], 'porcentajes' => []]);
    const analisis52124Pasteurizadora = @json($esDashboardMecanica ? $analisis52124Pasteurizadora : ['lineas' => []]);
    const analisis30147Pasteurizadora = @json($esDashboardMecanica ? $analisis30147Pasteurizadora : ['lineas' => []]);
    const centralesHidraulicasData = @json($esDashboardCentral ? $centralesHidraulicasPasteurizadora->values() : []);
    const fallasPorLineaCentralHidraulica = @json($esDashboardCentral ? $fallasPorLineaCentralHidraulica->values() : []);
    const planesAccionDashboardCentralHidraulica = @json($esDashboardCentral ? $planesAccionDashboardCentralHidraulica : ['por_linea' => []]);
    const avanceRevisionCentralHidraulica = @json($esDashboardCentral ? $avanceRevisionCentralHidraulica : ['labels' => [], 'porcentajes' => []]);
    const analisis52124CentralHidraulica = @json($esDashboardCentral ? $analisis52124CentralHidraulica : ['lineas' => []]);
    const analisis30147CentralHidraulica = @json($esDashboardCentral ? $analisis30147CentralHidraulica : ['lineas' => []]);
    const pasteurTrendChartTypes = {
        '52124': 'bar',
        '30147': 'bar',
        'central52124': 'bar',
        'central30147': 'bar'
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
        },
        'central52124': {
            key: 'central52124',
            canvasId: 'analisis52124CentralHidraulicaChart',
            selectId: 'analisis52124CentralHidraulicaLineaSelect',
            dataset: analisis52124CentralHidraulica,
            statusCardId: 'trendCentral52124StatusCard',
            statusTitleId: 'trendCentral52124StatusTitle',
            statusCopyId: 'trendCentral52124StatusCopy',
            statusNoteId: 'trendCentral52124StatusNote',
            windowsId: 'trendCentral52124WindowCards',
            machineGridId: 'trendCentral52124MachineGrid',
            captionId: 'trendCentral52124Caption',
            title: 'Central Hidraulica 52-12-4',
            windowRoles: ['Historico anual', 'Impacto trimestral', 'Control inmediato'],
            colors: [
                ['rgba(20, 184, 166, 0.88)', '#0f766e', 'rgba(20, 184, 166, 0.22)'],
                ['rgba(239, 68, 68, 0.88)', '#dc2626', 'rgba(239, 68, 68, 0.22)'],
                ['rgba(245, 158, 11, 0.9)', '#d97706', 'rgba(245, 158, 11, 0.24)']
            ]
        },
        'central30147': {
            key: 'central30147',
            canvasId: 'analisis30147CentralHidraulicaChart',
            selectId: 'analisis30147CentralHidraulicaLineaSelect',
            dataset: analisis30147CentralHidraulica,
            statusCardId: 'trendCentral30147StatusCard',
            statusTitleId: 'trendCentral30147StatusTitle',
            statusCopyId: 'trendCentral30147StatusCopy',
            statusNoteId: 'trendCentral30147StatusNote',
            windowsId: 'trendCentral30147WindowCards',
            machineGridId: 'trendCentral30147MachineGrid',
            captionId: 'trendCentral30147Caption',
            title: 'Central Hidraulica 30-14-7',
            windowRoles: ['Ventana amplia', 'Seguimiento intermedio', 'Control inmediato'],
            colors: [
                ['rgba(20, 184, 166, 0.88)', '#0f766e', 'rgba(20, 184, 166, 0.22)'],
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
        const fallasCanvas = document.getElementById('fallasPasteurizadoraChart');

        if (fallasCanvas) {
            const fallasCtx = fallasCanvas.getContext('2d');
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
        }

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
        fallasCentralHidraulicaChart = buildFallasCentralHidraulicaChart();
        planesCentralHidraulicaChart = buildPlanesCentralHidraulicaChart();
        avanceRevisionCentralHidraulicaChart = buildAvanceRevisionCentralHidraulicaChart();
        analisis52124CentralHidraulicaChart = buildPasteurizadoraTrendChart(
            'analisis52124CentralHidraulicaChart',
            analisis52124CentralHidraulica,
            pasteurTrendCards['central52124']
        );
        analisis30147CentralHidraulicaChart = buildPasteurizadoraTrendChart(
            'analisis30147CentralHidraulicaChart',
            analisis30147CentralHidraulica,
            pasteurTrendCards['central30147']
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
                    label: 'Avance de revisión mecánica',
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

    function buildFallasCentralHidraulicaChart() {
        const canvas = document.getElementById('fallasCentralHidraulicaChart');
        if (!canvas) return null;

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: fallasPorLineaCentralHidraulica.map(item => item.linea),
                datasets: [
                    {
                        label: 'Criticos',
                        data: fallasPorLineaCentralHidraulica.map(item => item.criticos || 0),
                        backgroundColor: 'rgba(239, 68, 68, 0.9)',
                        borderColor: '#dc2626',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Requiere revision',
                        data: fallasPorLineaCentralHidraulica.map(item => item.requiere_revision || 0),
                        backgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: '#d97706',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false
                    },
                    {
                        label: 'Severo / Moderado',
                        data: fallasPorLineaCentralHidraulica.map(item => item.desgaste || 0),
                        backgroundColor: 'rgba(20, 184, 166, 0.86)',
                        borderColor: '#0f766e',
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
                        bodyColor: '#ccfbf1',
                        borderColor: '#0f766e',
                        borderWidth: 2,
                        padding: 14,
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.raw}`,
                            footer: function(items) {
                                const item = fallasPorLineaCentralHidraulica[items[0]?.dataIndex];
                                return item ? `Total: ${item.total_fallas || 0}` : '';
                            }
                        }
                    }
                }
            }
        });
    }

    function buildPlanesCentralHidraulicaChart() {
        const canvas = document.getElementById('planesCentralHidraulicaChart');
        if (!canvas) return null;

        const rows = Array.isArray(planesAccionDashboardCentralHidraulica?.por_linea)
            ? planesAccionDashboardCentralHidraulica.por_linea
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
                        backgroundColor: 'rgba(20, 184, 166, 0.86)',
                        borderColor: '#0f766e',
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
                        bodyColor: '#ccfbf1',
                        borderColor: '#0f766e',
                        borderWidth: 2,
                        padding: 14
                    }
                }
            }
        });
    }

    function buildAvanceRevisionCentralHidraulicaChart() {
        const canvas = document.getElementById('avanceRevisionCentralHidraulicaChart');
        if (!canvas) return null;

        const labels = Array.isArray(avanceRevisionCentralHidraulica?.labels)
            ? avanceRevisionCentralHidraulica.labels
            : [];
        const values = Array.isArray(avanceRevisionCentralHidraulica?.porcentajes)
            ? avanceRevisionCentralHidraulica.porcentajes.map(value => Number(value || 0))
            : [];

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Avance de revision hidraulica',
                    data: values,
                    backgroundColor: values.map(value => value >= 90
                        ? 'rgba(20, 184, 166, 0.88)'
                        : (value >= 60 ? 'rgba(245, 158, 11, 0.88)' : 'rgba(249, 115, 22, 0.88)')),
                    borderColor: values.map(value => value >= 90
                        ? '#0f766e'
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
                        bodyColor: '#ccfbf1',
                        borderColor: '#0f766e',
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
                } else if (cardKey === 'central52124') {
                    analisis52124CentralHidraulicaChart = chart;
                } else if (cardKey === 'central30147') {
                    analisis30147CentralHidraulicaChart = chart;
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
                } else if (config.key === 'central52124') {
                    analisis52124CentralHidraulicaChart = chart;
                } else if (config.key === 'central30147') {
                    analisis30147CentralHidraulicaChart = chart;
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
            let title = 'Sin danos recientes';
            let copy = 'La ventana reciente no registra danos para esta seleccion.';

            if (!rows.length || !sourceSeries.length) {
                title = 'Sin datos en el periodo';
                copy = 'No hay daños registrados para construir esta tendencia con el filtro actual.';
            } else if ((recent?.current || 0) === 0) {
                tone = 'positive';
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

    function formatModalDate(value) {
        if (!value) {
            return 'Sin fecha';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleDateString('es-MX');
    }

    function pasteurizadoraAnalisisPorEstado(estado) {
        const porEstado = estado.analisis_por_estado || {};

        return {
            critico: Array.isArray(porEstado.critico) ? porEstado.critico : (Array.isArray(estado.analisis_criticos) ? estado.analisis_criticos : []),
            severo: Array.isArray(porEstado.severo) ? porEstado.severo : [],
            moderado: Array.isArray(porEstado.moderado) ? porEstado.moderado : [],
            revision: Array.isArray(porEstado.revision) ? porEstado.revision : (Array.isArray(estado.analisis_revision) ? estado.analisis_revision : [])
        };
    }

    function pasteurizadoraAlertSections(estado) {
        const porEstado = pasteurizadoraAnalisisPorEstado(estado);

        return [
            { key: 'critico', title: 'Requiere cambio', tone: 'critico' },
            { key: 'severo', title: 'Daño severo', tone: 'severo' },
            { key: 'moderado', title: 'Daño moderado', tone: 'moderado' },
            { key: 'revision', title: 'Requiere revision', tone: 'revision' }
        ].filter((section) => Array.isArray(porEstado[section.key]) && porEstado[section.key].length > 0);
    }

    function pasteurizadoraAnalysisLocation(analysis, mode) {
        if (mode === 'central') {
            return [
                analysis.piso ? `Piso: ${analysis.piso}` : null,
                analysis.lado || null
            ].filter(Boolean).join(' · ');
        }

        return [
            analysis.modulo ? `Modulo: ${analysis.modulo}` : null,
            analysis.nivel ? `Nivel: ${analysis.nivel}` : null,
            analysis.lado || null
        ].filter(Boolean).join(' · ');
    }

    function renderPasteurizadoraAnalisisSection(title, tone, analyses, mode = 'mecanica') {
        if (!Array.isArray(analyses) || analyses.length === 0) {
            return '';
        }

        const toneClasses = {
            critico: 'bg-red-100 text-red-700',
            severo: 'bg-orange-100 text-orange-700',
            moderado: 'bg-amber-100 text-amber-700',
            revision: 'bg-yellow-100 text-yellow-700'
        };
        const badgeClass = toneClasses[tone] || 'bg-slate-100 text-slate-700';

        const cards = analyses.map((analysis) => {
            const fallback = mode === 'central' ? '/images/icono_pas.png' : '/images/icono_pas.png';
            const image = analysis.image || analysis.fallback_image || fallback;
            const location = pasteurizadoraAnalysisLocation(analysis, mode);
            const componentName = analysis.componente_nombre || analysis.componente?.nombre || 'Componente';
            const dateLabel = analysis.fecha_formateada || formatModalDate(analysis.fecha_analisis);
            const orderLabel = analysis.numero_orden ? `Orden: ${analysis.numero_orden}` : null;

            return `
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="componente-header">
                                <div class="componente-icono">
                                    <img src="${escapeHtml(image)}" class="w-8 h-8 object-contain" onerror="this.src='${escapeHtml(analysis.fallback_image || fallback)}'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="componente-nombre">${escapeHtml(componentName)}</div>
                                </div>
                            </div>
                            ${location ? `<p class="text-sm text-gray-600 mt-2">${escapeHtml(location)}</p>` : ''}
                            <p class="text-xs text-gray-500 mt-1">
                                Fecha: ${escapeHtml(dateLabel)}${orderLabel ? ` · ${escapeHtml(orderLabel)}` : ''}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold ${badgeClass}">
                            ${escapeHtml(analysis.estado_label || analysis.estado || title)}
                        </span>
                    </div>
                    <p class="text-sm text-gray-700 mt-2">
                        ${escapeHtml(analysis.actividad || 'Sin descripcion')}
                    </p>
                </div>
            `;
        }).join('');

        return `
            <div class="mb-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <h4 class="font-bold text-gray-800">${escapeHtml(title)}</h4>
                    <span class="severity-pill ${tone}">${analyses.length}</span>
                </div>
                <div class="space-y-3">
                    ${cards}
                </div>
            </div>
        `;
    }

    function renderPasteurizadoraAlertChips(conteo) {
        const total = Object.values(conteo || {}).reduce((sum, value) => sum + Number(value || 0), 0);

        if (total <= 0) {
            return '';
        }

        return `
            <div class="mb-4 flex flex-wrap gap-2">
                ${Number(conteo.critico || 0) > 0 ? `<span class="severity-pill critico">${Number(conteo.critico || 0)} requiere cambio</span>` : ''}
                ${Number(conteo.severo || 0) > 0 ? `<span class="severity-pill severo">${Number(conteo.severo || 0)} severo</span>` : ''}
                ${Number(conteo.moderado || 0) > 0 ? `<span class="severity-pill moderado">${Number(conteo.moderado || 0)} moderado</span>` : ''}
                ${Number(conteo.revision || 0) > 0 ? `<span class="severity-pill revision">${Number(conteo.revision || 0)} revision</span>` : ''}
            </div>
        `;
    }

    function renderPasteurizadoraEmptyDetail(message) {
        return `
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm text-slate-600">
                ${escapeHtml(message)}
            </div>
        `;
    }

    function showPasteurizadoraDetail(pasteurizadora) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const estado = pasteurizadora.estado || {};
        const porEstado = pasteurizadoraAnalisisPorEstado(estado);
        const sections = pasteurizadoraAlertSections(estado);
        const conteoAlertas = estado.conteo_alertas || {};

        modalTitle.innerHTML = `Detalle - ${escapeHtml(pasteurizadora.nombre)}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (estado.nivel === 'riesgo' ? 'bg-orange-50 border-l-4 border-orange-500' : (estado.nivel === 'operativo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500'))}">
                <h4 class="font-bold text-lg mb-2">Estado: ${escapeHtml((estado.nivel || 'bueno').toUpperCase())}</h4>
                <p class="text-gray-700">${escapeHtml(estado.mensaje || 'Sin mensaje de estado')}</p>
            </div>
        `;

        html += renderPasteurizadoraAlertChips(conteoAlertas);

        sections.forEach((section) => {
            html += renderPasteurizadoraAnalisisSection(section.title, section.tone, porEstado[section.key] || [], 'mecanica');
        });

        if (estado.ultimo_analisis) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.fecha || 'Sin registro')}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Modulos configurados: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.modulos || 'N/A')}</span></p>
                    </div>
                </div>
            `;
        }

        if ((estado.acciones_pendientes || 0) > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta pasteurizadora tiene ${escapeHtml(estado.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        @if($puedeVerPlanesPasteurizadora)
                        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}?linea_id=${pasteurizadora.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        @endif
                    </div>
                </div>
            `;
        }

        if (sections.length === 0 && !(estado.acciones_pendientes > 0)) {
            html += renderPasteurizadoraEmptyDetail('No hay componentes con alertas activas para esta pasteurizadora.');
        }

        html += `
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                @if($puedeVerMecanicaPasteurizadora)
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}?linea_id=${pasteurizadora.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Analisis
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

    function showCentralHidraulicaDetail(central) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const estado = central.estado || {};
        const conteoAlertas = estado.conteo_alertas || {};
        const porEstado = pasteurizadoraAnalisisPorEstado(estado);
        const sections = pasteurizadoraAlertSections(estado);
        const pisos = Array.isArray(estado.pisos) ? estado.pisos : [];
        const nivel = estado.nivel || 'bueno';
        const toneClass = nivel === 'critico'
            ? 'bg-red-50 border-l-4 border-red-500'
            : (nivel === 'riesgo'
                ? 'bg-orange-50 border-l-4 border-orange-500'
                : (nivel === 'operativo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500'));

        modalTitle.innerHTML = `Detalle - ${escapeHtml(central.nombre)}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${toneClass}">
                <h4 class="font-bold text-lg mb-2">Estado: ${escapeHtml(nivel.toUpperCase())}</h4>
                <p class="text-gray-700">${escapeHtml(estado.mensaje || 'Central Hidraulica sin mensaje de estado')}</p>
            </div>
        `;

        html += renderPasteurizadoraAlertChips(conteoAlertas);

        sections.forEach((section) => {
            html += renderPasteurizadoraAnalisisSection(section.title, section.tone, porEstado[section.key] || [], 'central');
        });

        if (pisos.length > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Avance por piso</h4>
                    <div class="space-y-2">
            `;

            pisos.forEach((piso) => {
                const porcentaje = Math.min(100, Math.max(0, Number(piso.porcentaje || 0)));

                html += `
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-gray-800">${escapeHtml(piso.label || 'Piso')}</span>
                            <span class="text-sm font-bold text-blue-600">${porcentaje}%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: ${porcentaje}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Revisados: ${piso.revisados || 0} / ${piso.total || 0} - Alertas: ${piso.alertas || 0}</p>
                    </div>
                `;
            });

            html += `</div></div>`;
        }

        if (estado.ultimo_analisis) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.fecha || 'Sin registro')}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Componente: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.componente || 'N/A')}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Piso: <span class="font-semibold text-gray-800">${escapeHtml(estado.ultimo_analisis.piso || 'N/A')}</span></p>
                    </div>
                </div>
            `;
        }

        if ((estado.acciones_pendientes || 0) > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta central tiene ${escapeHtml(estado.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        @if($esDashboardCentral && $puedeVerPlanesCentralHidraulica)
                        <a href="{{ route('plan-accion.index', [
                            'tipo' => \App\Models\User::MODULE_PASTEURIZADORA,
                            'area_pasteurizadora' => \App\Models\AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                        ]) }}&linea_id=${central.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        @endif
                    </div>
                </div>
            `;
        }

        if (sections.length === 0 && !(estado.acciones_pendientes > 0)) {
            html += renderPasteurizadoraEmptyDetail('No hay registros con alertas activas para esta Central Hidraulica.');
        }

        html += `
            <div class="flex flex-wrap justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                @if($esDashboardCentral && $puedeVerCentralHidraulicaPasteurizadora)
                <a href="{{ route('pasteurizadora.central-hidraulica.index') }}?linea_id=${central.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-droplet mr-1"></i> Ver Analisis
                </a>
                <a href="{{ route('pasteurizadora.central-hidraulica.historico-revisados') }}?linea_id=${central.id}" class="px-4 py-2 bg-slate-700 text-white rounded hover:bg-slate-800 transition">
                    <i class="fas fa-history mr-1"></i> Historico
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
