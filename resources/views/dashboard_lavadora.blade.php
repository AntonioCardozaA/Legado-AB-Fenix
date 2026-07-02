@extends('layouts.app')

@section('title', 'Lavadoras ')

@section('content')
<style>
    /* Estilos generales */
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
        .ranking-meta,
        .ranking-badge,
        .severity-pill,
        .table-footer,
        .panel-copy,
        .subpanel-copy,
        .breakdown-title,
        .breakdown-meta,
        .priority-title,
        .priority-meta,
        .work-title,
        .work-meta,
        .mini-stat-label,
        .mini-stat-value,
        .mini-stat-meta,
        .status-banner,
        .legend-item
    ) {
        overflow-wrap: anywhere;
        word-break: normal;
    }

    /* Animación de parpadeo para alertas críticas */
    @keyframes blink {
        0% { opacity: 1; background-color: #fee2e2; border-left-color: #ef4444; }
        50% { opacity: 0.7; background-color: #fff5f5; border-left-color: #fca5a5; }
        100% { opacity: 1; background-color: #fee2e2; border-left-color: #ef4444; }
    }

    .alert-critical {
        animation: blink 1s ease-in-out infinite;
    }

    /* Tarjetas de resumen */
    .dashboard-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .dashboard-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    .dashboard-header > div {
        min-width: 0;
    }

    .dashboard-header h1 {
        flex-wrap: wrap;
        line-height: 1.25;
    }

    .dashboard-actions button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 0;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
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

    /* Grid de tarjetas de lavadoras */
    .lavadoras-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 295px), 1fr));
        gap: 12px;
        margin-bottom: 12px;
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

    /* Estados de color para las tarjetas */
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
        border-radius: 16px;
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

    /* ═══════════════════════════════════════════════════════════════ */
    /* ▓▓▓ SECCIONES MEJORADAS - GRÁFICAS Y COMPONENTES ▓▓▓ */
    /* ═══════════════════════════════════════════════════════════════ */

    /* Tarjetas de Gráficas - Estilo Premium */
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
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Borde superior con gradiente animado mejorado */
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

    /* Títulos de Gráficas */
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
        display: flex;
        align-items: center;
        justify-content: center;
        filter: drop-shadow(0 1px 2px rgba(59, 130, 246, 0.15));
    }

    /* Contenedor de gráfica */
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
        box-sizing: border-box;
    }

    .chart-container canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
        max-width: 100%;
        min-width: 0;
        min-height: 0;
    }

    /* Descripción informativa bajo gráfica */
    .chart-description {
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
        gap: 6px;
        flex-wrap: wrap;
        min-width: 0;
        line-height: 1.35;
        border: 1px solid rgba(59, 130, 246, 0.1);
        font-weight: 500;
    }

    .chart-description i {
        font-size: 12px;
        color: var(--primary-blue);
        filter: drop-shadow(0 1px 2px rgba(59, 130, 246, 0.15));
    }

    .chart-card:has(#analisis52124Chart) > .chart-description,
    .chart-card:has(#analisis30147Chart) > .chart-description,
    .chart-card:has(#analisis52124Chart) > .chart-container + div:not(.chart-shell),
    .chart-card:has(#analisis30147Chart) > .chart-container + div:not(.chart-shell) {
        display: none;
    }

    .dashboard-analytics-layout {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        align-items: start;
        margin-top: 6px;
    }

    .dashboard-analytics-column {
        display: contents;
    }

    .dashboard-analytics-layout > .chart-card,
    .dashboard-analytics-column .chart-card,
    .dashboard-analytics-full .chart-card {
        margin-bottom: 0;
    }

    .fallas-card { order: 1; }
    .planes-card { order: 2; }
    .historico-card { order: 3; }
    .elongaciones-card { order: 4; }
    .ranking-card { order: 5; }
    .trend-card-primary { order: 6; }

    .dashboard-analytics-full {
        margin-top: 10px;
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

    /* Secciones */
    .section-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        margin: 28px 0 16px 0;
        display: flex;
        align-items: center;
        gap: 14px;
        border-left: 5px solid var(--primary-blue);
        padding-left: 18px;
        letter-spacing: -0.5px;
        animation: slideInLeft 0.6s ease-out;
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .section-title i {
        font-size: 26px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* ═══════════════════════════════════════════════════════════════ */
    /* ▓▓▓ RANKING - ESTILO LEADERBOARD PREMIUM ▓▓▓ */
    /* ═══════════════════════════════════════════════════════════════ */

    .ranking-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ranking-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 16px 18px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.92) 100%);
        transition: var(--transition);
        position: relative;
        border-radius: 14px;
        margin-bottom: 0;
        box-shadow: var(--shadow-sm);
        min-width: 0;
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
        background: linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(239, 246, 255, 0.92) 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.18);
    }

    .ranking-item:hover::before {
        opacity: 1;
    }

    .ranking-item:last-child {
        margin-bottom: 0;
    }

    /* Posición en ranking */
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

    .ranking-position::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 16px;
        background: transparent;
        border: 2px solid transparent;
        transition: border-color 0.3s ease;
    }

    /* Top 1 - Oro */
    .ranking-position.top-1 {
        background: linear-gradient(135deg, #fef9e7 0%, #fef3c7 50%, #fde68a 100%);
        color: #d97706;
        box-shadow: 0 8px 24px rgba(217, 119, 6, 0.3);
        font-weight: 900;
        border: 2px solid rgba(217, 119, 6, 0.2);
    }

    .ranking-position.top-1::before {
        content: '👑';
        position: absolute;
        font-size: 20px;
        top: -8px;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    /* Top 2 - Plata */
    .ranking-position.top-2 {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 50%, #d1d5db 100%);
        color: #374151;
        box-shadow: 0 6px 20px rgba(107, 114, 128, 0.25);
        border: 2px solid rgba(107, 114, 128, 0.15);
    }

    /* Top 3 - Bronce */
    .ranking-position.top-3 {
        background: linear-gradient(135deg, #fed7aa 0%, #fcd5ce 50%, #fce7f3 100%);
        color: #b45309;
        box-shadow: 0 6px 20px rgba(180, 83, 9, 0.25);
        border: 2px solid rgba(180, 83, 9, 0.15);
    }

    /* Info del ranking */
    .ranking-info {
        flex: 1;
        margin-left: 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
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
        flex-wrap: wrap;
        min-width: 0;
        line-height: 1.35;
    }

    .ranking-puntaje i {
        color: #fbbf24;
        font-size: 12px;
    }

    /* Badge de cantidad de críticos */
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
        justify-content: center;
        gap: 6px;
        transition: var(--transition);
        max-width: 100%;
        min-width: 0;
    }

    .ranking-badge i {
        font-size: 11px;
    }

    .ranking-item:hover .ranking-badge {
        transform: scale(1.08);
        box-shadow: 0 6px 16px rgba(153, 27, 27, 0.25);
    }

    /* Información adicional del ranking */
    .ranking-footer {
        margin-top: 16px;
        padding: 16px 20px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.06) 0%, rgba(139, 92, 246, 0.06) 100%);
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.1);
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        align-items: center;
        font-size: 13px;
        color: var(--text-secondary);
        font-weight: 500;
        line-height: 1.35;
    }

    .ranking-status-stack {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        flex: 0 0 auto;
        max-width: 100%;
        min-width: 0;
    }

    .ranking-footer i {
        margin-right: 8px;
        color: var(--primary-blue);
        font-size: 14px;
    }

    .ranking-card .ranking-list {
        gap: 8px;
        flex: 1 1 auto;
    }

    .ranking-card .ranking-item {
        padding: 10px 14px;
        border-radius: 12px;
    }

    .ranking-card .ranking-position {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-size: 13px;
    }

    .ranking-card .ranking-position::after {
        border-radius: 10px;
    }

    .ranking-card .ranking-position.top-1::before {
        font-size: 16px;
        top: -6px;
    }

    .ranking-card .ranking-asset {
        margin-left: 10px;
        gap: 10px;
    }

    .ranking-card .ranking-info {
        margin-left: 0;
        gap: 2px;
    }

    .ranking-card .ranking-linea {
        font-size: 12px;
    }

    .ranking-card .ranking-puntaje {
        font-size: 10px;
        gap: 4px;
    }

    .ranking-card .ranking-puntaje i {
        font-size: 11px;
    }

    .ranking-card .ranking-meta {
        margin-top: 3px;
        font-size: 9px;
        line-height: 1.3;
    }

    .ranking-card .ranking-badge,
    .ranking-card .severity-pill,
    .historico-card .severity-pill {
        padding: 4px 8px;
        font-size: 9px;
        gap: 4px;
    }

    .ranking-card .ranking-badge i {
        font-size: 10px;
    }

    .ranking-card .ranking-footer,
    .historico-card .table-footer {
        margin-top: 12px;
        padding: 10px 14px;
        font-size: 11px;
    }

    .ranking-card .ranking-footer i,
    .historico-card .table-footer i {
        font-size: 12px;
    }

    .fallas-card .chart-shell .chart-container,
    .planes-card .chart-shell .chart-container {
        min-height: 220px;
    }

    .historico-card .chart-shell.compact .chart-container {
        height: 188px;
    }

    .elongaciones-card .chart-container.tall,
    .trend-card .chart-container.tall {
        height: 264px;
    }

    .elongaciones-card,
    .trend-card-side {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .elongaciones-card .chart-shell,
    .trend-card-side .chart-shell {
        flex: 1;
        display: flex;
        width: 100%;
        min-width: 0;
    }

    .elongaciones-card .chart-shell .chart-container,
    .trend-card-side .chart-shell .chart-container {
        flex: 1;
        min-height: 300px;
        width: 100%;
        min-width: 0;
    }

    .elongaciones-card .chart-container.tall,
    .trend-card-side .chart-container.tall {
        height: 300px;
    }

    .elongaciones-card .chart-shell .chart-container,
    .elongaciones-card .chart-container.tall {
        min-height: clamp(280px, 34vw, 340px);
        height: clamp(280px, 34vw, 340px);
    }

    .trend-card .trend-filter-form {
        gap: 8px;
        align-items: flex-end;
    }

    .trend-card .trend-date-field {
        min-width: 124px;
        flex: 1 1 124px;
    }

    .trend-card .panel-select {
        min-width: 128px;
    }

    .trend-card-side .trend-filter-form {
        display: grid;
        grid-template-columns: minmax(120px, 0.85fr) repeat(2, minmax(120px, 1fr)) auto;
        gap: 8px;
        align-items: end;
    }

    .trend-card-side .panel-button {
        white-space: nowrap;
    }

    .historico-card .subpanel-title {
        margin-top: 14px !important;
        font-size: 12px;
    }

    .historico-card .subpanel-copy {
        font-size: 11px;
        margin-bottom: 10px;
    }

    .historico-card table th {
        padding: 12px 14px;
        font-size: 10px;
    }

    .historico-card table th i {
        font-size: 11px;
        margin-right: 6px;
    }

    .historico-card table td {
        padding: 10px 14px;
        font-size: 11px;
        line-height: 1.3;
    }

    .historico-card table td i {
        margin-right: 6px;
        font-size: 11px;
    }

    .historico-card table td .text-xs {
        font-size: 10px;
        margin-top: 2px;
    }

    .historico-card .overflow-x-auto {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .historico-card table {
        min-width: 760px;
    }

    /* ═══════════════════════════════════════════════════════════════ */
    /* ▓▓▓ TABLA - ESTILO ADMINISTRATIVO PROFESIONAL ▓▓▓ */
    /* ═══════════════════════════════════════════════════════════════ */

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

    .chart-card table th i {
        font-size: 14px;
        margin-right: 8px;
        opacity: 0.7;
    }

    .chart-card table tbody tr {
        border-bottom: 1px solid rgba(59, 130, 246, 0.08);
        transition: var(--transition);
        background: white;
    }

    .chart-card table tbody tr:nth-child(odd) {
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.02) 50%, transparent);
    }

    .chart-card table tbody tr:hover {
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.08) 50%, transparent);
        box-shadow: inset 0 0 0 1.5px rgba(59, 130, 246, 0.12), 0 2px 8px rgba(59, 130, 246, 0.08);
    }

    .chart-card table tbody tr:last-child {
        border-bottom: none;
    }

    .chart-card table td {
        padding: 16px 20px;
        font-size: 14px;
        color: var(--text-primary);
        vertical-align: middle;
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    .chart-card table td:last-child {
        text-align: right;
        font-weight: 700;
        color: var(--primary-blue);
    }

    .chart-card table td i {
        margin-right: 8px;
        font-size: 14px;
    }

    /* Tabla - Información descriptiva */
    .table-footer {
        margin-top: 20px;
        padding: 14px 18px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.06) 0%, rgba(139, 92, 246, 0.06) 100%);
        border-radius: 10px;
        border: 1px solid rgba(59, 130, 246, 0.1);
        text-align: center;
        font-size: 13px;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 500;
    }

    .table-footer i {
        color: var(--primary-blue);
        font-size: 14px;
    }

    /* Grid de gráficas - Espaciado mejorado */
    /* Modal para detalles de alerta */
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
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
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

    /* Componente Header */
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

    /* Responsive */
    @media (min-width: 1280px) {
        .stats-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
    }

    @media (max-width: 1024px) {
        .dashboard-analytics-layout {
            grid-template-columns: 1fr;
        }

        .dashboard-panels-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-analytics-full {
            margin-top: 10px;
        }

        .trend-card-side .trend-filter-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .historico-card table {
            min-width: 700px;
        }
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 16px;
        }
        .dashboard-header {
            align-items: stretch;
        }
        .dashboard-actions {
            width: 100%;
        }
        .dashboard-actions > * {
            flex: 1;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .lavadoras-grid {
            grid-template-columns: 1fr;
        }
        .chart-card {
            padding: 16px;
        }
        .chart-container {
            height: 228px;
        }
        .dashboard-analytics-layout,
        .dashboard-analytics-column {
            gap: 10px;
        }
        .dashboard-panels-grid,
        .dashboard-panels-full {
            gap: 10px;
            margin-bottom: 10px;
        }
        .chart-container.tall {
            height: 242px;
        }
        .chart-shell.compact .chart-container,
        .historico-card .chart-shell.compact .chart-container {
            height: 176px;
        }
        .historico-card .panel-actions,
        .trend-card-side .panel-actions,
        .elongaciones-card .panel-actions {
            width: 100%;
            justify-content: stretch;
        }
        .historico-card .panel-select,
        .elongaciones-card .panel-select {
            width: 100%;
            min-width: 0;
        }
        .trend-card-side .trend-filter-form {
            grid-template-columns: 1fr;
        }
        .trend-card-side .panel-select,
        .trend-card-side .panel-date-input,
        .trend-card-side .panel-button,
        .trend-card-side .trend-date-field {
            width: 100%;
            min-width: 0;
        }
        .historico-card .overflow-x-auto {
            overflow: visible;
            border: 0;
            box-shadow: none;
        }
        .historico-card table {
            min-width: 0;
            border-collapse: separate;
            border-spacing: 0 10px;
            background: transparent;
        }
        .historico-card table thead {
            display: none;
        }
        .historico-card table tbody,
        .historico-card table tr,
        .historico-card table td {
            display: block;
            width: 100%;
        }
        .historico-card table tr {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .historico-card table td {
            display: grid;
            grid-template-columns: minmax(92px, 0.42fr) minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            text-align: left !important;
            font-size: 12px;
        }
        .historico-card table td:last-child {
            border-bottom: 0;
        }
        .historico-card table td::before {
            content: attr(data-label);
            font-size: 10px;
            font-weight: 800;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .historico-card table td[colspan] {
            display: block;
            text-align: center !important;
        }
        .historico-card table td[colspan]::before {
            display: none;
        }
        .elongaciones-card,
        .trend-card-side {
            min-height: 0;
        }
        .elongaciones-card .chart-shell .chart-container,
        .elongaciones-card .chart-container.tall {
            min-height: 280px;
            height: 280px;
        }
        .trend-card-side .chart-shell .chart-container,
        .trend-card-side .chart-container.tall {
            min-height: 250px;
            height: 250px;
        }
        .section-title {
            font-size: 20px;
            margin: 22px 0 14px 0;
            gap: 10px;
            padding-left: 14px;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .lavadora-metricas {
            grid-template-columns: 1fr;
        }
        .chart-card h3 {
            font-size: 15px;
        }
        .chart-container {
            height: 210px;
        }
        .ranking-position {
            width: 42px;
            height: 42px;
            font-size: 14px;
        }
        .ranking-info {
            margin-left: 12px;
        }
        .ranking-badge {
            padding: 4px 10px;
            font-size: 10px;
        }
        .chart-container.tall {
            height: 224px;
        }
        .elongaciones-card .chart-shell .chart-container,
        .elongaciones-card .chart-container.tall {
            min-height: 264px;
            height: 264px;
        }
        .historico-card table td {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }

    .dashboard-panel {
        position: relative;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
    }

    .panel-copy {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.45;
        max-width: none;
    }

    .panel-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
        min-width: 0;
    }

    .panel-link,
    .panel-select,
    .filter-chip {
        border: 1px solid var(--border-light);
        background: white;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-primary);
        transition: var(--transition);
    }

    .panel-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        text-decoration: none;
        box-shadow: var(--shadow-sm);
        max-width: 100%;
        min-width: 0;
    }

    .panel-link:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
        background: #f8fafc;
    }

    .panel-select {
        min-width: 150px;
        max-width: 100%;
        padding: 10px 12px;
        box-shadow: var(--shadow-sm);
        outline: none;
    }

    .trend-filter-form {
        align-items: flex-end;
    }

    .trend-date-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 148px;
        max-width: 100%;
    }

    .trend-date-field span {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-secondary);
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .panel-button,
    .panel-date-input {
        border: 1px solid var(--border-light);
        background: white;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-primary);
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

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
        max-width: 100%;
        min-width: 0;
    }

    .panel-button:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
        background: #f8fafc;
    }

    .panel-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
    }

    .panel-button:focus,
    .panel-date-input:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
        outline: none;
    }

    .filter-chip-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-width: 0;
        max-width: 100%;
    }

    .filter-chip {
        padding: 9px 12px;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
        max-width: 100%;
    }

    .filter-chip:hover {
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .filter-chip.active {
        background: linear-gradient(135deg, #0f172a, #334155);
        border-color: #0f172a;
        color: white;
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
        background: white;
        border: 1px solid var(--border-light);
        border-radius: 14px;
        padding: 12px;
        box-shadow: var(--shadow-sm);
        min-height: 76px;
    }

    .mini-stat.danger { border-top: 4px solid var(--danger-red); }
    .mini-stat.warning,
    .mini-stat.revision { border-top: 4px solid var(--warning-yellow); }
    .mini-stat.severo { border-top: 4px solid var(--operational-orange); }
    .mini-stat.success { border-top: 4px solid var(--success-green); }
    .mini-stat.info { border-top: 4px solid var(--primary-blue); }

    .mini-stat-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--text-secondary);
        font-weight: 700;
        margin-bottom: 6px;
    }

    .mini-stat-value {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.1;
    }

    .mini-stat-meta {
        margin-top: 6px;
        font-size: 11px;
        color: var(--text-secondary);
    }

    .status-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding: 14px 16px;
        border-radius: 14px;
        margin-bottom: 18px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid transparent;
        min-width: 0;
        line-height: 1.4;
    }

    .status-banner.critico {
        background: var(--danger-light);
        color: #991b1b;
        border-color: rgba(239, 68, 68, 0.18);
    }

    .status-banner.operativo {
        background: var(--warning-light);
        color: #92400e;
        border-color: rgba(245, 158, 11, 0.18);
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

    .chart-shell.compact .chart-container {
        height: 212px;
    }

    .chart-container.tall {
        height: 276px;
    }

    .card-loader {
        position: absolute;
        inset: 0;
        z-index: 3;
        border-radius: 12px;
        background: rgba(248, 250, 252, 0.92);
        backdrop-filter: blur(2px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 12px;
        padding: 22px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .card-loader.is-hidden {
        display: none;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .card-loader[hidden] {
        display: none !important;
    }

    .skeleton-line {
        height: 12px;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(226, 232, 240, 0.95) 0%, rgba(248, 250, 252, 1) 50%, rgba(226, 232, 240, 0.95) 100%);
        background-size: 220% 100%;
        animation: shimmer 1.4s linear infinite;
    }

    .skeleton-line.small { width: 42%; }
    .skeleton-line.medium { width: 68%; }
    .skeleton-line.large { width: 100%; }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .chart-empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 10px;
        min-height: 220px;
        padding: 24px 20px;
        border-radius: 12px;
        border: 1px dashed var(--border-light);
        background: white;
        text-align: center;
        color: var(--text-secondary);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    .chart-empty-state[hidden] {
        display: none !important;
    }

    .chart-empty-state i {
        font-size: 26px;
        color: var(--dark-gray);
    }

    .severity-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        max-width: 100%;
        white-space: normal;
        text-align: center;
        line-height: 1.2;
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

    .severity-pill.estable,
    .severity-pill.cambiado {
        background: var(--success-light);
        color: #065f46;
    }

    .linea-breakdown,
    .priority-list,
    .worklist {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .breakdown-item,
    .priority-row,
    .work-item {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 14px;
        padding: 13px 14px;
    }

    .breakdown-item-top,
    .priority-row-top,
    .work-item-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        min-width: 0;
    }

    .breakdown-item-top > div,
    .priority-row-top > div,
    .work-item-top > div {
        min-width: min(100%, 180px);
        flex: 1 1 180px;
    }

    .breakdown-title,
    .priority-title,
    .work-title {
        font-weight: 800;
        color: var(--text-primary);
        font-size: 13px;
    }

    .breakdown-meta,
    .priority-meta,
    .work-meta {
        margin-top: 5px;
        font-size: 11px;
        color: var(--text-secondary);
    }

    .progress-track {
        margin-top: 10px;
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f172a, #3b82f6);
    }

    .subpanel-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 10px;
    }

    .subpanel-copy {
        font-size: 11px;
        color: var(--text-secondary);
        margin-top: -4px;
        margin-bottom: 10px;
    }

    .panel-copy:empty,
    .subpanel-copy:empty {
        display: none;
    }

    .asset-media {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: white;
        border: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: var(--shadow-sm);
    }

    .asset-media img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 12px;
        padding: 4px;
    }

    .ranking-asset {
        flex: 1;
        margin-left: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .ranking-meta {
        font-size: 10px;
        color: var(--text-secondary);
        margin-top: 6px;
        line-height: 1.45;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .legend-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 14px;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-secondary);
    }

    .legend-swatch {
        width: 12px;
        height: 12px;
        border-radius: 999px;
    }

    .trend-window-legend {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border-light);
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 12px;
        text-align: center;
    }

    .trend-window-legend-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .trend-window-legend-swatch {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        flex: 0 0 auto;
    }

    .trend-window-legend-label {
        font-size: 12px;
        color: var(--text-secondary);
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    @media (max-width: 1024px) {
        .mini-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .panel-header {
            flex-direction: column;
            align-items: stretch;
        }

        .panel-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 640px) {
        .mini-stats-grid,
        .mini-stats-grid.compact,
        .info-grid {
            grid-template-columns: 1fr;
        }

        .chart-container,
        .chart-shell.compact .chart-container,
        .chart-container.tall {
            height: 260px;
        }

        .panel-link,
        .panel-select,
        .filter-chip,
        .panel-date-input,
        .panel-button {
            width: 100%;
            justify-content: center;
        }

        .trend-date-field {
            width: 100%;
        }

        .ranking-asset {
            margin-left: 10px;
            gap: 10px;
        }

        .ranking-item {
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .ranking-badge {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .stat-card .stat-icon {
            float: none;
            margin-bottom: 4px;
        }

        .lavadora-card-header {
            align-items: stretch;
        }

        .status-tag {
            max-width: 100%;
        }

        .trend-window-legend {
            grid-template-columns: repeat(auto-fit, minmax(96px, 1fr));
            gap: 10px;
        }

        .modal-content {
            max-height: calc(100vh - 24px);
        }

        .modal-body {
            max-height: calc(100vh - 104px);
        }
    }

    @media (max-width: 640px) {
        .dashboard-container {
            padding: 12px;
        }

        .dashboard-actions,
        .dashboard-actions button {
            width: 100%;
        }

        .dashboard-actions button {
            justify-content: center;
        }

        .ranking-card .ranking-item,
        .ranking-item {
            align-items: flex-start;
            gap: 10px;
        }

        .ranking-asset {
            flex: 1 1 calc(100% - 52px);
            margin-left: 0;
        }

        .ranking-status-stack {
            width: 100%;
            align-items: stretch;
        }

        .ranking-status-stack .severity-pill,
        .ranking-status-stack .ranking-badge {
            width: 100%;
        }

        .breakdown-item-top,
        .priority-row-top,
        .work-item-top {
            align-items: flex-start;
        }

        .trend-window-legend {
            grid-template-columns: 1fr;
        }

        .modal-body .grid {
            grid-template-columns: 1fr !important;
        }

        .modal-body .justify-end {
            flex-wrap: wrap;
        }

        .modal-body .justify-between {
            flex-wrap: wrap;
            gap: 10px;
        }
    }

    @media (max-width: 480px) {
        .carousel-slide-content {
            flex-direction: column;
            align-items: stretch;
        }

        .carousel-slide-image,
        .carousel-slide-icon {
            width: 42px;
            height: 42px;
        }

        .carousel-controls {
            align-items: center;
        }

        .carousel-dots {
            justify-content: center;
        }

        .chart-card {
            padding: 12px;
        }

        .modal {
            padding: 10px;
        }

        .modal-content {
            border-radius: 18px;
        }

        .modal-header,
        .modal-body {
            padding: 16px;
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
    {{-- Header --}}
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 transition">
            <i class="fas fa-arrow-left"></i>
            <span>Volver</span>
        </a>
    </div>
    <div class="mb-6">
        <div class="dashboard-header">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    Dashboard Lavadoras
                </h1>
                @auth
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        Rol: {{ $userRoleLabel ?? auth()->user()->role_label }}
                    </p>
                @endauth
            </div>
            <div class="dashboard-actions">
                <button onclick="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>

    {{-- Tarjetas de Resumen --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-industry"></i></div>
            <div class="stat-label">Total Lavadoras</div>
            <div class="stat-value">{{ $resumenGeneral['total_lavadoras'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--danger-red);">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-label">Alertas Críticas</div>
            <div class="stat-value" style="color: var(--danger-red);">{{ $resumenGeneral['alertas_criticas'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--operational-orange);">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">Severo / Moderado</div>
            <div class="stat-value" style="color: var(--operational-orange);">{{ $resumenGeneral['en_riesgo'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--warning-yellow);">
            <div class="stat-icon"><i class="fas fa-tools"></i></div>
            <div class="stat-label">Requiere Revisión</div>
            <div class="stat-value" style="color: var(--warning-yellow);">{{ $resumenGeneral['requiere_revision'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--success-green);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Buen Estado</div>
            <div class="stat-value" style="color: var(--success-green);">{{ $resumenGeneral['buen_estado'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-label">Pendientes Acción</div>
            <div class="stat-value">{{ $resumenGeneral['pendientes_accion'] }}</div>
        </div>
    </div>

    {{-- ESTADO GENERAL DE LAVADORAS en Tarjetas --}}
    <div class="section-title">
        <i class="fas fa-washing-machine"></i>
        ESTADO GENERAL DE LAVADORAS
    </div>
    <div class="lavadoras-grid">
        @foreach($estadoLavadoras as $lavadora)
            @php
                $estado = $lavadora['estado'];
                $isCritical = $estado['nivel'] === 'critico';
                $cardClass = '';
                if ($estado['nivel'] === 'bueno') {
                    $cardClass = 'buen-estado';
                } elseif ($estado['nivel'] === 'operativo') {
                    $cardClass = 'operativo-estado';
                } elseif ($estado['nivel'] === 'riesgo') {
                    $cardClass = 'riesgo-estado';
                } else {
                    $cardClass = 'critico-estado';
                }
                if ($isCritical) {
                    $cardClass .= ' alert-critical';
                }
            @endphp
            <div class="lavadora-card {{ $cardClass }}">
                <div class="lavadora-card-header">
                    <div class="lavadora-nombre">
                        <i class="fas fa-microchip status-icon"></i>
                        {{ $lavadora['nombre'] }}
                    </div>
                    <div>
                        <span class="status-tag {{ $estado['nivel'] === 'bueno' ? 'bueno' : ($estado['nivel'] === 'operativo' ? 'operativo' : ($estado['nivel'] === 'riesgo' ? 'riesgo' : 'critico')) }}">
                            <i class="fas {{ $estado['nivel'] === 'bueno' ? 'fa-check-circle' : ($estado['nivel'] === 'operativo' ? 'fa-tools' : ($estado['nivel'] === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle')) }}"></i>
                            {{ $estado['nivel'] === 'bueno' ? 'Buen estado' : ($estado['nivel'] === 'operativo' ? 'Requiere revisión' : ($estado['nivel'] === 'riesgo' ? 'Severo / Moderado' : 'Crítico')) }}
                        </span>
                    </div>
                </div>
                <div class="lavadora-card-body">
                    <div class="lavadora-mensaje">
                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                        {{ $estado['mensaje'] }}
                    </div>

                    @if(isset($estado['alert_carousel']) && count($estado['alert_carousel']) > 0)
                        <div class="lavadora-carousel" id="lavadora-carousel-{{ $lavadora['id'] }}">
                            <div class="lavadora-carousel-track">
                                @foreach($estado['alert_carousel'] as $index => $item)
                                    <div class="carousel-slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}">
                                        <div class="carousel-slide-content">
                                            @if($item['type'] === 'componente')
                                                <div class="carousel-slide-image">
                                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" />
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
                                                @if(!empty($item['reductor']))
                                                    <div class="carousel-slide-meta">Reductor: {{ $item['reductor'] }}</div>
                                                @endif
                                                @if(!empty($item['meta']))
                                                    <div class="carousel-slide-meta">Código: {{ $item['meta'] }}</div>
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
                                        @foreach($estado['alert_carousel'] as $index => $item)
                                            <span class="carousel-dot {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}"></span>
                                        @endforeach
                                    </div>
                                    <button type="button" class="carousel-button carousel-next" aria-label="Siguiente">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(isset($estado['ultima_elongacion']))
                    <div class="lavadora-metricas">
                        <div class="metric-item">
                            <div class="metric-label">Elongación Bombas</div>
                            <div class="metric-value" style="color: {{ $estado['ultima_elongacion']['bombas_porcentaje'] >= 1.46 ? 'var(--danger-red)' : ($estado['ultima_elongacion']['bombas_porcentaje'] >= 1.3 ? 'var(--warning-yellow)' : 'var(--success-green)') }}">
                                {{ $estado['ultima_elongacion']['bombas_porcentaje'] }}%
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Elongación Vapor</div>
                            <div class="metric-value" style="color: {{ $estado['ultima_elongacion']['vapor_porcentaje'] >= 1.46 ? 'var(--danger-red)' : ($estado['ultima_elongacion']['vapor_porcentaje'] >= 1.3 ? 'var(--warning-yellow)' : 'var(--success-green)') }}">
                                {{ $estado['ultima_elongacion']['vapor_porcentaje'] }}%
                            </div>
                        </div>
                        @if(isset($estado['analisis_criticos']))
                        <div class="metric-item">
                            <div class="metric-label">Daños Críticos</div>
                            <div class="metric-value" style="color: var(--danger-red);">
                                {{ count($estado['analisis_criticos']) }}
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="lavadora-card-footer">
                    <button onclick='showAlertDetail(@json($lavadora))' 
                            class="lavadora-card-action">
                        <i class="fas fa-chart-simple mr-1"></i> Ver Detalle Completo
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Gráficas Mejoradas --}}
    @php
        $lineaOptions = $lineasLavadora
            ->map(fn ($linea) => ['id' => $linea->id, 'nombre' => $linea->nombre])
            ->values();
    @endphp

    <div class="dashboard-panels-grid">
        {{-- Gráfica 1: Fallas por Línea --}}
        <div class="chart-card fallas-card">
            <h3>
                <i class="fas fa-chart-bar"></i>
                <span>Fallas por Línea</span>
            </h3>
            <div class="chart-container">
                <canvas id="fallasChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Fallas activas
            </div>
        </div>

        {{-- Gráfica 2: Componentes Más Dañados --}}
        <div class="chart-card planes-card">
            <h3>
                <i class="fas fa-chart-pie"></i>
                <span>Componentes Más Dañados</span>
            </h3>
            <div class="chart-container">
                <canvas id="componentesChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Planes activos
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        {{-- Ranking: Lavadoras con Mayor Daño --}}
        <div class="chart-card ranking-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Daño</span>
            </h3>
            <ul class="ranking-list" id="rankingList">
                @foreach($rankingDanos as $index => $item)
                    <li class="ranking-item">
                        <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="ranking-asset">
                            <div class="asset-media">
                                <i class="fas fa-industry" style="font-size: 18px; color: #2563eb;"></i>
                            </div>
                            <div class="ranking-info">
                                <div class="ranking-linea">{{ $item['linea'] }}</div>
                                <div class="ranking-puntaje">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    Criticas: {{ $item['criticas'] ?? 0 }} · Severo / Moderado: {{ ($item['severos'] ?? 0) + ($item['moderados'] ?? 0) }}
                                </div>
                                <div class="ranking-meta">
                                    Total con dano: {{ $item['total_danos'] ?? 0 }} de {{ $item['total_componentes'] ?? 0 }} componentes · Impacto {{ number_format((float) ($item['porcentaje_impacto'] ?? 0), 1) }}% · Revision: {{ $item['fecha_analisis_humana'] ?? 'Sin fecha' }}
                                </div>
                            </div>
                        </div>
                        <div class="ranking-status-stack">
                            <span class="severity-pill {{ $item['prioridad'] ?? 'estable' }}">{{ $item['prioridad_label'] ?? 'Estable' }}</span>
                            <div class="ranking-badge">
                                <i class="fas fa-bolt"></i>
                                {{ number_format((float) ($item['total_danos'] ?? 0), 0) }} danos
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="ranking-footer" id="rankingFooter">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Daños activos
                </div>
            </div>
        </div>

        {{-- Gráfica 3: Evolución de Elongaciones --}}
        <div class="chart-card elongaciones-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Evolución de Elongaciones</span>
            </h3>
            <div class="chart-container">
                <canvas id="elongacionesChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Bombas vs Vapor
            </div>
        </div>
    </div>

    <div class="dashboard-panels-grid">
        {{-- Tabla: Histórico de Revisiones --}}
        <div class="chart-card historico-card">
            <h3>
                <i class="fas fa-history"></i>
                <span>Histórico de Revisiones</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <i class="fas fa-cube" style="color: #3b82f6;"></i> Componente
                            </th>
                            <th class="text-right">
                                <i class="fas fa-hashtag" style="color: #8b5cf6;"></i> Análisis
                            </th>
                        </tr>
                    </thead>
                    <tbody id="historicoTableBody">
                        @foreach([] as $item)
                            <tr>
                                <td data-label="Componente"><i class="fas fa-microchip mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                <td data-label="Análisis">{{ $item['total_analisis'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <i class="fas fa-info-circle"></i>
                Cantidad total de análisis por componente
            </div>
        </div>

        {{-- Gráfica 4: Análisis 30-14-7 --}}
        <div class="chart-card trend-card trend-card-side">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Análisis 30-14-7 | Tendencia de Daños</span>
            </h3>
            <div class="chart-container">
                <canvas id="analisis30147Chart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                30-14-7
            </div>
        </div>
    </div>

    <div class="dashboard-panels-full">
        <div class="chart-card trend-card trend-card-primary">
        <h3>
            <i class="fas fa-chart-line"></i>
            <span>Análisis 52-12-4 | Tendencia de Daños</span>
        </h3>
        <div class="chart-container">
            <canvas id="analisis52124Chart"></canvas>
        </div>
        <div class="trend-window-legend">
            <div class="trend-window-legend-item">
                <div class="trend-window-legend-swatch" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.85), rgba(59, 130, 246, 1)); box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);"></div>
                <span class="trend-window-legend-label">52 semanas</span>
            </div>
            <div class="trend-window-legend-item">
                <div class="trend-window-legend-swatch" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.85), rgba(245, 158, 11, 1)); box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);"></div>
                <span class="trend-window-legend-label">12 semanas</span>
            </div>
            <div class="trend-window-legend-item">
                <div class="trend-window-legend-swatch" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.85), rgba(16, 185, 129, 1)); box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);"></div>
                <span class="trend-window-legend-label">4 semanas</span>
            </div>
        </div>
        <div class="chart-description">
            <i class="fas fa-info-circle"></i>
            52-12-4
        </div>
        </div>
    </div>
</div>

{{-- Modal para Detalle de Alerta --}}
<div id="alertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Detalle de Alerta</h3>
            <button onclick="closeModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let fallasChart, componentesChart, elongacionesChart, analisis52124Chart;

    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        initLavadoraCarousels();
        setAutoRefresh();
    });

    function initCharts() {
        // ─────────────────────────────────────────────────────────────────
        // 1️⃣ GRÁFICA: FALLAS POR LÍNEA
        // ─────────────────────────────────────────────────────────────────
        const fallasCtx = document.getElementById('fallasChart').getContext('2d');
        const fallasData = @json($fallasPorLinea);
        
        fallasChart = new Chart(fallasCtx, {
            type: 'bar',
            data: {
                labels: fallasData.map(item => item.linea),
                datasets: [{
                    label: 'Total de Fallas',
                    data: fallasData.map(item => item.total_fallas),
                    backgroundColor: fallasData.map((item, i) => {
                        const colors = [
                            'rgba(239, 68, 68, 0.9)',
                            'rgba(248, 113, 113, 0.85)',
                            'rgba(252, 165, 165, 0.8)',
                            'rgba(254, 202, 202, 0.75)',
                            'rgba(254, 226, 226, 0.7)'
                        ];
                        return colors[i] || colors[0];
                    }),
                    borderColor: fallasData.map((item, i) => {
                        const colors = ['#dc2626', '#f87171', '#fca5a5', '#fb7185', '#fecdd3'];
                        return colors[i] || colors[0];
                    }),
                    borderWidth: 2,
                    borderRadius: 12,
                    borderSkipped: false,
                    hoverBackgroundColor: 'rgba(239, 68, 68, 1)',
                    hoverBorderColor: '#991b1b',
                    hoverBorderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                            drawTicks: false
                        },
                        ticks: {
                            callback: function(value) {
                                return value;
                            },
                            font: {
                                size: 12,
                                weight: 600
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 13,
                                weight: 600
                            },
                            color: '#334155',
                            padding: 8
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        padding: 14,
                        titleFont: {
                            size: 13,
                            weight: 'bold',
                            family: "'Inter', sans-serif"
                        },
                        bodyFont: {
                            size: 12,
                            weight: 600
                        },
                        callbacks: {
                            label: function(context) {
                                return `Fallas: ${context.raw}`;
                            }
                        },
                        usePointStyle: true,
                        boxPadding: 12,
                        displayColors: true
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 2️⃣ GRÁFICA: COMPONENTES MÁS DAÑADOS (DOUGHNUT)
        // ─────────────────────────────────────────────────────────────────
        const componentesCtx = document.getElementById('componentesChart').getContext('2d');
        const componentesData = @json($componentesDanados);
        
        componentesChart = new Chart(componentesCtx, {
            type: 'doughnut',
            data: {
                labels: componentesData.map(item => item.componente),
                datasets: [{
                    data: componentesData.map(item => item.total_danios),
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.9)',
                        'rgba(245, 158, 11, 0.9)',
                        'rgba(16, 185, 129, 0.9)',
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(139, 92, 246, 0.9)',
                        'rgba(236, 72, 153, 0.9)'
                    ],
                    borderColor: [
                        '#dc2626',
                        '#d97706',
                        '#059669',
                        '#2563eb',
                        '#7c3aed',
                        '#db2777'
                    ],
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
                        labels: {
                            font: {
                                size: 12,
                                weight: 600,
                                family: "'Inter', sans-serif"
                            },
                            color: '#334155',
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: label,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                }));
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#f59e0b',
                        borderWidth: 2,
                        padding: 14,
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12,
                            weight: 600
                        },
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return [`Daños: ${value}`, `${percentage}%`];
                            }
                        }
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 3️⃣ GRÁFICA: EVOLUCIÓN DE ELONGACIONES (LÍNEA)
        // ─────────────────────────────────────────────────────────────────
        const elongacionesCtx = document.getElementById('elongacionesChart').getContext('2d');
        const elongacionesData = @json($evolucionElongaciones);

        elongacionesChart = new Chart(elongacionesCtx, {
            type: 'line',
            data: {
                labels: elongacionesData.map(item => item.fecha),
                datasets: [
                    {
                        label: 'Bombas (%)',
                        data: elongacionesData.map(item => item.bombas),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 10,
                        pointStyle: 'circle',
                        fill: true,
                        tension: 0.4,
                        hoverBorderWidth: 4
                    },
                    {
                        label: 'Vapor (%)',
                        data: elongacionesData.map(item => item.vapor),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 10,
                        pointStyle: 'circle',
                        fill: true,
                        tension: 0.4,
                        hoverBorderWidth: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e0e7ff',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        padding: 14,
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12,
                            weight: 600
                        },
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}%`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 24,
                            font: {
                                size: 13,
                                weight: 'bold'
                            },
                            color: '#334155',
                            generateLabels: function(chart) {
                                const datasets = chart.data.datasets;
                                return datasets.map((dataset, i) => ({
                                    text: dataset.label,
                                    fillStyle: dataset.borderColor,
                                    hidden: false,
                                    index: i,
                                    pointStyle: 'circle'
                                }));
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                            drawTicks: false
                        },
                        title: {
                            display: true,
                            text: 'Porcentaje (%)',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            padding: 10
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                size: 12,
                                weight: 600
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 12,
                                weight: 500
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    }
                }
            }
        });

        // ─────────────────────────────────────────────────────────────────
        // 4️⃣ GRÁFICA: ANÁLISIS 52-12-4 (BARRAS AGRUPADAS)
        // ─────────────────────────────────────────────────────────────────
        if (false) {
        const analisis52124Ctx = document.getElementById('analisis52124Chart').getContext('2d');
        const analisis52124Data = @json($analisis52124);

        const lineasMap = new Map();
        analisis52124Data.forEach(item => {
            const lineaNombre = item.linea?.nombre ?? 'N/A';
            if (!lineasMap.has(lineaNombre)) {
                lineasMap.set(lineaNombre, {
                    '52_semanas': 0,
                    '12_semanas': 0,
                    '4_semanas': 0,
                    periodos: []
                });
            }
            const lineaData = lineasMap.get(lineaNombre);
            lineaData['52_semanas'] += parseFloat(item.total_danos_52_semanas) || 0;
            lineaData['12_semanas'] += parseFloat(item.total_danos_12_semanas) || 0;
            lineaData['4_semanas'] += parseFloat(item.total_danos_4_semanas) || 0;
        });

        const lineasNombres = Array.from(lineasMap.keys());
        const data52 = lineasNombres.map(linea => lineasMap.get(linea)['52_semanas']);
        const data12 = lineasNombres.map(linea => lineasMap.get(linea)['12_semanas']);
        const data4 = lineasNombres.map(linea => lineasMap.get(linea)['4_semanas']);

        analisis52124Chart = new Chart(analisis52124Ctx, {
            type: 'bar',
            data: {
                labels: lineasNombres,
                datasets: [
                    {
                        label: '52 Semanas',
                        data: data52,
                        backgroundColor: 'rgba(59, 130, 246, 0.9)',
                        borderColor: '#1e40af',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(29, 78, 216, 1)',
                        hoverBorderColor: '#1e3a8a',
                        hoverBorderWidth: 3,
                        hoverOffset: 6
                    },
                    {
                        label: '12 Semanas',
                        data: data12,
                        backgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: '#b45309',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(217, 119, 6, 1)',
                        hoverBorderColor: '#92400e',
                        hoverBorderWidth: 3,
                        hoverOffset: 6
                    },
                    {
                        label: '4 Semanas',
                        data: data4,
                        backgroundColor: 'rgba(16, 185, 129, 0.9)',
                        borderColor: '#047857',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(5, 150, 105, 1)',
                        hoverBorderColor: '#065f46',
                        hoverBorderWidth: 3,
                        hoverOffset: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                            drawTicks: false
                        },
                        title: {
                            display: true,
                            text: 'Total de Daños',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: 600
                            },
                            color: '#64748b',
                            padding: 8
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Línea de Lavadora',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: 600
                            },
                            color: '#334155',
                            padding: 8
                        }
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
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12,
                            weight: 600
                        },
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw.toFixed(2)} daños`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 24,
                            font: {
                                size: 13,
                                weight: 'bold'
                            },
                            color: '#334155',
                            generateLabels: function(chart) {
                                const datasets = chart.data.datasets;
                                return datasets.map((dataset, i) => ({
                                    text: dataset.label,
                                    fillStyle: dataset.backgroundColor,
                                    hidden: false,
                                    index: i,
                                    pointStyle: 'rect'
                                }));
                            }
                        }
                    }
                }
            }
        });
        }
    }

    function initLavadoraCarousels() {
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
                const nextIndex = (currentIndex + 1) % slides.length;
                showSlide(nextIndex);
            }

            function goPrev() {
                const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
                showSlide(prevIndex);
            }

            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    goNext();
                });
            }

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    goPrev();
                });
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

    function refreshData() {
        window.location.reload();
    }

    function setAutoRefresh() {
        setInterval(() => {
            refreshData();
        }, 300000); // 5 minutos
    }

    function showAlertDetail(lavadora) {
        const modal = document.getElementById('alertModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');

        modalTitle.innerHTML = `Detalle - ${lavadora.nombre}`;

        let html = `
            <div class="mb-4 p-4 rounded-lg ${lavadora.estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (lavadora.estado.nivel === 'riesgo' ? 'bg-orange-50 border-l-4 border-orange-500' : (lavadora.estado.nivel === 'operativo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500'))}">
                <h4 class="font-bold text-lg mb-2">Estado: ${lavadora.estado.nivel.toUpperCase()}</h4>
                <p class="text-gray-700">${lavadora.estado.mensaje}</p>
            </div>
        `;

        if (lavadora.estado.analisis_criticos && lavadora.estado.analisis_criticos.length > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Componentes Dañados</h4>
                    <div class="space-y-3">
            `;
            lavadora.estado.analisis_criticos.forEach(analisis => {
                const iconoUrl = analisis.componente?.icono || '/images/componentes-lavadora/default.png';
                html += `
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="componente-header">
                                        <div class="componente-icono">
                                            <img src="${iconoUrl}" class="w-8 h-8 object-contain" onerror="this.src='/images/componentes-lavadora/default.png'">
                                        </div>
                                        <div class="flex-1">
                                            <div class="componente-nombre">${analisis.componente?.nombre || 'N/A'}</div>
                                            <div class="text-xs text-gray-500">${analisis.componente?.codigo || ''}</div>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-2">Reductor: ${analisis.reductor}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Fecha: ${new Date(analisis.fecha_analisis).toLocaleDateString()}
                                    </p>
                                </div>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                    Crítico
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 mt-2">
                                ${analisis.actividad || 'Sin descripción'}
                            </p>
                        </div>
                    `;
            });
            html += `</div></div>`;
        }

        if (lavadora.estado.ultima_elongacion) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Última Medición de Elongación</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-sm text-gray-600">Bombas:</p>
                                <p class="font-semibold ${lavadora.estado.ultima_elongacion.bombas_porcentaje >= 1.8 ? 'text-red-600' : (lavadora.estado.ultima_elongacion.bombas_porcentaje >= 1.46 ? 'text-yellow-600' : 'text-green-600')}">
                                    ${lavadora.estado.ultima_elongacion.bombas_porcentaje}%
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Vapor:</p>
                                <p class="font-semibold ${lavadora.estado.ultima_elongacion.vapor_porcentaje >= 1.8 ? 'text-red-600' : (lavadora.estado.ultima_elongacion.vapor_porcentaje >= 1.46 ? 'text-yellow-600' : 'text-green-600')}">
                                    ${lavadora.estado.ultima_elongacion.vapor_porcentaje}%
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Fecha: ${new Date(lavadora.estado.ultima_elongacion.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
            `;
        }

        if (lavadora.estado.acciones_pendientes > 0) {
            html += `
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta lavadora tiene ${lavadora.estado.acciones_pendientes} acción(es) pendiente(s) en el plan de acción.</p>
                        <a href="{{ route('plan-accion.lavadora.index') }}?linea_id=${lavadora.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Acción
                        </a>
                    </div>
                </div>
            `;
        }

        html += `
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                <a href="{{ route('analisis-lavadora.index') }}?linea_id=${lavadora.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Análisis
                </a>
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
<script>
(() => {
    const data = {
        dashboardUrl: @json(route('dashboard.global.lavadoras')),
        lineas: @json($lineaOptions),
        fallas: @json($fallasPorLinea),
        planes: @json($planesAccionDashboard),
        ranking: @json($rankingDanos),
        elongaciones: @json($evolucionElongaciones),
        historico: @json($historicoRevisiones),
        trendFilters: @json($trendFilters ?? []),
        tendencia: @json($analisis52124),
        tendencia30147: @json($analisis30147)
    };

    const charts = {
        fallas: null,
        planes: null,
        elongaciones: null,
        historico: null,
        tendencia: null,
        tendencia30147: null
    };

    const state = {
        fallasFilter: 'all',
        rankingScope: 'all',
        rankingSort: 'puntaje',
        elongacionLineaId: data.elongaciones?.default_linea_id ?? data.lineas?.[0]?.id ?? null,
        historicoScope: 'Todas',
        tendenciaLineaId: data.tendencia?.default_linea_id ?? data.lineas?.[0]?.id ?? null,
        tendencia30147LineaId: data.tendencia30147?.default_linea_id ?? data.lineas?.[0]?.id ?? null
    };

    let layoutReady = false;
    let responsiveChartResizeBound = false;
    let resizeChartsTimer = null;

    window.initCharts = initCharts = function () {
        if (!layoutReady) {
            setupLayout();
            layoutReady = true;
        }

        if (!responsiveChartResizeBound) {
            bindResponsiveChartResize();
            responsiveChartResizeBound = true;
        }

        renderFallas();
        renderPlanes();
        renderRanking();
        renderElongaciones();
        renderHistorico();
        renderTendencia();
        renderTendencia30147();
    };

    function setupLayout() {
        setupFallasCard();
        setupPlanesCard();
        setupRankingCard();
        setupElongacionesCard();
        setupHistoricoCard();
        setupTendenciaCard();
        setupTendencia30147Card();
    }

    function setupFallasCard() {
        const card = cardFromCanvas('fallasChart');
        if (!card) return;

        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Fallas por linea', 'fas fa-chart-bar');
        ensureAfterHeading(card, 'fallasCopy', `<p id="fallasCopy" class="panel-copy"></p>`);
        ensureAfterElement('fallasCopy', 'fallasToolbar', `
            <div id="fallasToolbar" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <div class="filter-chip-group" id="fallasSeverityFilters">
                    <button type="button" class="filter-chip active" data-filter="all">Vista total</button>
                    <button type="button" class="filter-chip" data-filter="criticas">Críticas</button>
                    <button type="button" class="filter-chip" data-filter="requiere_revision">Requiere revisión</button>
                    <button type="button" class="filter-chip" data-filter="severas_moderadas">Severo / Moderado</button>
                </div>
            </div>
        `);
        ensureChartShell('fallasChart', 'fallas');

        const description = card.querySelector('.chart-description');
        if (description && !document.getElementById('fallasLegend')) {
            description.insertAdjacentHTML('beforebegin', `
                <div class="legend-inline" id="fallasLegend">
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(239, 68, 68, 0.92);"></span> Crítico</span>
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(245, 158, 11, 0.92);"></span> Requiere revisión</span>
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(249, 115, 22, 0.9);"></span> Severo / Moderado</span>
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(16, 185, 129, 0.9);"></span> Estable</span>
                </div>
            `);
            description.insertAdjacentHTML('afterend', `
                <div class="subpanel-title" style="margin-top: 18px;">Lavadoras con mayor impacto</div>
                <div class="linea-breakdown" id="fallasBreakdown"></div>
            `);
        }

        document.querySelectorAll('#fallasSeverityFilters .filter-chip').forEach((button) => {
            if (button.dataset.bound === 'true') return;
            button.dataset.bound = 'true';
            button.addEventListener('click', function () {
                state.fallasFilter = this.dataset.filter;
                document.querySelectorAll('#fallasSeverityFilters .filter-chip').forEach((item) => item.classList.toggle('active', item === this));
                renderFallas();
            });
        });
    }

    function setupPlanesCard() {
        const card = cardFromCanvas('componentesChart');
        if (!card) return;

        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Planes de accion', 'fas fa-clipboard-check');
        ensureAfterHeading(card, 'planesCopy', `<p id="planesCopy" class="panel-copy"></p>`);
        ensureAfterElement('planesCopy', 'planesActions', `
            <div id="planesActions" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <a href="{{ route('plan-accion.lavadora.index') }}" class="panel-link">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    Ir al modulo
                </a>
            </div>
        `);
        ensureAfterElement('planesActions', 'planesBanner', `<div id="planesBanner" class="status-banner estable"></div>`);
        ensureChartShell('componentesChart', 'planes', { compact: true });

        const description = card.querySelector('.chart-description');
        if (description) {
            description.innerHTML = '<i class="fas fa-info-circle"></i> Planes activos';
        }

        if (description && !document.getElementById('planesPriorityList')) {
            description.insertAdjacentHTML('afterend', `
                <div class="subpanel-title" style="margin-top: 18px;">Carga por lavadora</div>
                <div class="priority-list" id="planesPriorityList"></div>
                <div class="subpanel-title" style="margin-top: 18px;">Planes activos prioritarios</div>
                <div class="worklist" id="planesWorkList"></div>
            `);
        }
    }

    function setupRankingCard() {
        const list = document.getElementById('rankingList');
        if (!list) return;

        const card = list.closest('.chart-card');
        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Ranking de daños', 'fas fa-trophy');
        if (!document.getElementById('rankingLoader')) {
            list.insertAdjacentHTML('beforebegin', loaderMarkup('rankingLoader'));
            list.insertAdjacentHTML('beforebegin', `<div class="chart-empty-state" id="rankingEmpty" hidden></div>`);
        }
    }

    function setupElongacionesCard() {
        const card = cardFromCanvas('elongacionesChart');
        if (!card) return;

        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Evolucion de elongaciones', 'fas fa-chart-line');
        ensureAfterHeading(card, 'elongacionesCopy', `<p id="elongacionesCopy" class="panel-copy"></p>`);
        ensureAfterElement('elongacionesCopy', 'elongacionesActions', `
            <div id="elongacionesActions" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <select id="elongacionesLineaSelect" class="panel-select">${lineaOptions(state.elongacionLineaId)}</select>
            </div>
        `);
        ensureChartShell('elongacionesChart', 'elongaciones', { tall: true });

        const select = document.getElementById('elongacionesLineaSelect');
        if (select && select.dataset.bound !== 'true') {
            select.dataset.bound = 'true';
            select.addEventListener('change', function () {
                state.elongacionLineaId = Number(this.value);
                renderElongaciones();
            });
        }
    }

    function setupHistoricoCard() {
        const body = document.getElementById('historicoTableBody');
        if (!body) return;

        const card = body.closest('.chart-card');
        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Historico de revisiones', 'fas fa-history');
        ensureAfterHeading(card, 'historicoCopy', `<p id="historicoCopy" class="panel-copy"></p>`);
        ensureAfterElement('historicoCopy', 'historicoActions', `
            <div id="historicoActions" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <select id="historicoScopeSelect" class="panel-select">
                    <option value="Todas">Todas las lavadoras</option>
                    ${data.lineas.map((linea) => `<option value="${escapeHtml(linea.nombre)}">${escapeHtml(linea.nombre)}</option>`).join('')}
                </select>
            </div>
        `);
        const tableWrapper = card.querySelector('.overflow-x-auto');
        if (tableWrapper && !document.getElementById('historicoChart')) {
            tableWrapper.insertAdjacentHTML('beforebegin', `
                <div class="chart-shell compact">
                    ${loaderMarkup('historicoLoader')}
                    <div class="chart-empty-state" id="historicoEmpty" hidden></div>
                    <div class="chart-container" data-chart-container="historico">
                        <canvas id="historicoChart"></canvas>
                    </div>
                </div>
                <div class="subpanel-title" style="margin-top: 18px;">Ultimas revisiones registradas</div>
            `);
        }

        const headerRow = card.querySelector('thead tr');
        if (headerRow) {
            headerRow.innerHTML = `
                <th><i class="fas fa-calendar-day" style="color: #3b82f6;"></i> Fecha</th>
                <th><i class="fas fa-industry" style="color: #2563eb;"></i> Lavadora</th>
                <th><i class="fas fa-cube" style="color: #0f172a;"></i> Componente</th>
                <th><i class="fas fa-location-dot" style="color: #7c3aed;"></i> Ubicacion</th>
                <th><i class="fas fa-signal" style="color: #ef4444;"></i> Estado</th>
                <th><i class="fas fa-user" style="color: #10b981;"></i> Revisión</th>
            `;
        }

        const select = document.getElementById('historicoScopeSelect');
        if (select && select.dataset.bound !== 'true') {
            select.dataset.bound = 'true';
            select.addEventListener('change', function () {
                state.historicoScope = this.value;
                renderHistorico();
            });
        }
    }

    function setupTendenciaCard() {
        setupDamageTrendCard({
            cardId: 'analisis52124Chart',
            title: 'Analisis 52-12-4 | Tendencia de daños',
            icon: 'fas fa-wave-square',
            prefix: 'analisis52124',
            actionsId: 'tendenciaActions',
            selectId: 'analisis52124LineaSelect',
            filterFromName: data.trendFilters?.tendencia?.from_param ?? 'trend_52124_desde',
            filterToName: data.trendFilters?.tendencia?.to_param ?? 'trend_52124_hasta',
            filterFromValue: data.trendFilters?.tendencia?.from_input ?? '',
            filterToValue: data.trendFilters?.tendencia?.to_input ?? '',
            preserveInputs: [
                { name: data.trendFilters?.tendencia30147?.from_param ?? 'trend_30147_desde', value: data.trendFilters?.tendencia30147?.from_input ?? '' },
                { name: data.trendFilters?.tendencia30147?.to_param ?? 'trend_30147_hasta', value: data.trendFilters?.tendencia30147?.to_input ?? '' }
            ],
            stateKey: 'tendenciaLineaId',
            renderFn: renderTendencia
        });
    }

    function setupTendencia30147Card() {
        setupDamageTrendCard({
            cardId: 'analisis30147Chart',
            title: 'Analisis 30-14-7 | Tendencia de daños',
            icon: 'fas fa-chart-line',
            prefix: 'analisis30147',
            actionsId: 'tendencia30147Actions',
            selectId: 'analisis30147LineaSelect',
            filterFromName: data.trendFilters?.tendencia30147?.from_param ?? 'trend_30147_desde',
            filterToName: data.trendFilters?.tendencia30147?.to_param ?? 'trend_30147_hasta',
            filterFromValue: data.trendFilters?.tendencia30147?.from_input ?? '',
            filterToValue: data.trendFilters?.tendencia30147?.to_input ?? '',
            preserveInputs: [
                { name: data.trendFilters?.tendencia?.from_param ?? 'trend_52124_desde', value: data.trendFilters?.tendencia?.from_input ?? '' },
                { name: data.trendFilters?.tendencia?.to_param ?? 'trend_52124_hasta', value: data.trendFilters?.tendencia?.to_input ?? '' }
            ],
            stateKey: 'tendencia30147LineaId',
            renderFn: renderTendencia30147
        });
    }

    function setupDamageTrendCard(config) {
        const card = cardFromCanvas(config.cardId);
        if (!card) return;

        const preserveInputs = (config.preserveInputs || [])
            .map((input) => `<input type="hidden" name="${escapeHtml(input.name)}" value="${escapeHtml(input.value || '')}">`)
            .join('');

        card.querySelectorAll('.chart-description').forEach((node) => node.remove());
        Array.from(card.children)
            .filter((node) => node.tagName === 'DIV' && !node.classList.contains('chart-container') && !node.classList.contains('chart-shell') && node.id !== config.actionsId)
            .forEach((node) => node.remove());

        card.classList.add('dashboard-panel');
        updateCardTitle(card, config.title, config.icon);
        ensureAfterHeading(card, config.actionsId, `
            <form id="${config.actionsId}" class="panel-actions trend-filter-form" method="GET" action="${escapeHtml(data.dashboardUrl || '')}" style="margin-bottom: 18px; justify-content: flex-start;">
                <select id="${config.selectId}" class="panel-select">${lineaOptions(state[config.stateKey])}</select>
                <label class="trend-date-field">
                    <span>Desde</span>
                    <input type="date" name="${escapeHtml(config.filterFromName)}" value="${escapeHtml(config.filterFromValue || '')}" class="panel-date-input">
                </label>
                <label class="trend-date-field">
                    <span>Hasta</span>
                    <input type="date" name="${escapeHtml(config.filterToName)}" value="${escapeHtml(config.filterToValue || '')}" class="panel-date-input">
                </label>
                ${preserveInputs}
                <button type="submit" class="panel-button">
                    <i class="fas fa-filter"></i>
                    Aplicar
                </button>
            </form>
        `);
        ensureChartShell(config.cardId, config.prefix, { tall: true });

        const select = document.getElementById(config.selectId);
        if (select && select.dataset.bound !== 'true') {
            select.dataset.bound = 'true';
            select.addEventListener('change', function () {
                state[config.stateKey] = Number(this.value);
                config.renderFn();
            });
        }
    }

    function renderFallas() {
        const stats = document.getElementById('fallasStats');
        const breakdown = document.getElementById('fallasBreakdown');
        const description = cardFromCanvas('fallasChart')?.querySelector('.chart-description');
        const rows = Array.isArray(data.fallas) ? [...data.fallas] : [];
        const hasData = rows.some((item) => Number(item.total_componentes || 0) > 0);

        if (!hasData) {
            if (stats) {
                stats.innerHTML = miniStats([
                    ['Lavadoras sin datos', '0', 'No hay analisis vigentes', 'info'],
                    ['Críticas', '0', 'Sin registros', 'danger'],
                    ['Requiere revisión', '0', 'Sin registros', 'revision'],
                    ['Severo / Moderado', '0', 'Sin registros', 'severo']
                ]);
            }
            if (breakdown) breakdown.innerHTML = infoBox('No hay datos disponibles para construir la matriz de fallas por linea.');
            if (description) description.innerHTML = '<i class="fas fa-info-circle"></i> Sin datos vigentes';
            destroy(charts.fallas);
            setChartState('fallas', true, 'Sin datos de fallas', 'No existen componentes evaluados para mostrar la distribucion por linea.', 'fa-database');
            return;
        }

        const key = state.fallasFilter === 'criticas'
            ? 'criticas'
            : (state.fallasFilter === 'requiere_revision'
                ? 'requiere_revision'
                : (state.fallasFilter === 'severas_moderadas' ? 'severas_moderadas' : 'impactados'));
        const sorted = rows.slice().sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0) || Number(b.porcentaje_impacto || 0) - Number(a.porcentaje_impacto || 0));

        const criticas = rows.reduce((sum, item) => sum + Number(item.criticas || 0), 0);
        const revisiones = rows.reduce((sum, item) => sum + Number(item.requiere_revision || 0), 0);
        const warnings = rows.reduce((sum, item) => sum + Number(item.severas_moderadas || 0), 0);
        const impactadas = rows.filter((item) => Number(item.impactados || 0) > 0).length;
        const promedio = rows.length ? rows.reduce((sum, item) => sum + Number(item.porcentaje_impacto || 0), 0) / rows.length : 0;

        if (stats) {
            stats.innerHTML = miniStats([
                ['Lavadoras impactadas', impactadas, `${rows.length} monitoreadas`, 'info'],
                ['Fallas críticas', criticas, 'Rojo = requiere cambio', 'danger'],
                ['Requiere revisión', revisiones, 'Amarillo = validar componente', 'revision'],
                ['Severo / Moderado', warnings, 'Naranja = seguimiento', 'severo'],
                ['Impacto promedio', percent(promedio, 1), 'Sobre componentes vigentes', 'success']
            ]);
        }

        if (breakdown) {
            breakdown.innerHTML = sorted.slice(0, 5).map((item) => `
                <div class="breakdown-item">
                    <div class="breakdown-item-top">
                        <div>
                            <div class="breakdown-title">${escapeHtml(item.linea)}</div>
                            <div class="breakdown-meta">Críticas: ${Number(item.criticas || 0)} · Revisión: ${Number(item.requiere_revision || 0)} · Severo / Moderado: ${Number(item.severas_moderadas || 0)} · Última revisión: ${escapeHtml(item.ultima_revision_humana || 'Sin fecha')}</div>
                        </div>
                        <span class="severity-pill ${item.estado === 'critico' ? 'critico' : (item.estado === 'riesgo' ? 'severo' : (item.estado === 'operativo' ? 'revision' : 'estable'))}">${percent(item.porcentaje_impacto || 0, 1)}</span>
                    </div>
                    <div class="progress-track"><div class="progress-bar" style="width: ${Math.min(Number(item.porcentaje_impacto || 0), 100)}%;"></div></div>
                </div>
            `).join('');

            breakdown.querySelectorAll('.breakdown-item').forEach((node, index) => {
                const item = sorted[index] || {};
                const meta = node.querySelector('.breakdown-meta');
                const pill = node.querySelector('.severity-pill');

                if (meta) {
                    meta.textContent = `Críticas: ${Number(item.criticas || 0)} · Revisión: ${Number(item.requiere_revision || 0)} · Severo / Moderado: ${Number(item.severas_moderadas || 0)} · Última revisión: ${item.ultima_revision_humana || 'Sin fecha'}`;
                }

                if (pill) {
                    const tone = item.estado === 'critico'
                        ? 'critico'
                        : (item.estado === 'riesgo' ? 'severo' : (item.estado === 'operativo' ? 'revision' : 'estable'));
                    pill.className = `severity-pill ${tone}`;
                }
            });
        }

        if (description) {
            const datoClave = state.fallasFilter === 'criticas'
                ? `Críticas: ${criticas}`
                : (state.fallasFilter === 'requiere_revision'
                    ? `Revisión: ${revisiones}`
                    : (state.fallasFilter === 'severas_moderadas'
                        ? `Severo / Moderado: ${warnings}`
                        : `Impactadas: ${impactadas}`));
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${datoClave} · Impacto promedio: ${percent(promedio, 1)}`;
        }

        const singleDatasetMeta = {
            criticas: {
                label: 'Críticas',
                backgroundColor: 'rgba(239, 68, 68, 0.92)',
                borderColor: '#dc2626'
            },
            requiere_revision: {
                label: 'Requiere revisión',
                backgroundColor: 'rgba(245, 158, 11, 0.92)',
                borderColor: '#d97706'
            },
            severas_moderadas: {
                label: 'Severo / Moderado',
                backgroundColor: 'rgba(249, 115, 22, 0.9)',
                borderColor: '#ea580c'
            }
        };

        const datasets = state.fallasFilter === 'all'
            ? [
                { label: 'Críticas', data: sorted.map((item) => Number(item.criticas || 0)), backgroundColor: 'rgba(239, 68, 68, 0.92)', borderColor: '#dc2626', borderWidth: 2, borderRadius: 10, borderSkipped: false },
                { label: 'Requiere revisión', data: sorted.map((item) => Number(item.requiere_revision || 0)), backgroundColor: 'rgba(245, 158, 11, 0.92)', borderColor: '#d97706', borderWidth: 2, borderRadius: 10, borderSkipped: false },
                { label: 'Severo / Moderado', data: sorted.map((item) => Number(item.severas_moderadas || 0)), backgroundColor: 'rgba(249, 115, 22, 0.88)', borderColor: '#ea580c', borderWidth: 2, borderRadius: 10, borderSkipped: false },
                { label: 'Estables', data: sorted.map((item) => Number(item.estables || 0)), backgroundColor: 'rgba(16, 185, 129, 0.24)', borderColor: '#10b981', borderWidth: 1, borderRadius: 10, borderSkipped: false }
            ]
            : [{
                label: (singleDatasetMeta[state.fallasFilter] || singleDatasetMeta.severas_moderadas).label,
                data: sorted.map((item) => Number(item[state.fallasFilter] || 0)),
                backgroundColor: (singleDatasetMeta[state.fallasFilter] || singleDatasetMeta.severas_moderadas).backgroundColor,
                borderColor: (singleDatasetMeta[state.fallasFilter] || singleDatasetMeta.severas_moderadas).borderColor,
                borderWidth: 2,
                borderRadius: 10,
                borderSkipped: false
            }];

        destroy(charts.fallas);
        setChartState('fallas', false);
        charts.fallas = new Chart(document.getElementById('fallasChart').getContext('2d'), {
            type: 'bar',
            data: { labels: sorted.map((item) => item.linea), datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { stacked: state.fallasFilter === 'all', grid: { display: false }, ticks: { color: '#334155', font: { size: 12, weight: 700 } } },
                    y: { beginAtZero: true, stacked: state.fallasFilter === 'all', grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { precision: 0, color: '#64748b' } }
                },
                plugins: {
                    legend: { display: state.fallasFilter === 'all', labels: { usePointStyle: true, padding: 16, color: '#334155', font: { size: 11, weight: 700 } } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            afterBody: (context) => {
                                const item = sorted[context[0].dataIndex];
                                return [
                                    `Críticas: ${Number(item.criticas || 0)}`,
                                    `Requiere revisión: ${Number(item.requiere_revision || 0)}`,
                                    `Severo / Moderado: ${Number(item.severas_moderadas || 0)}`,
                                    `Impactados: ${Number(item.impactados || 0)}`,
                                    `Impacto: ${percent(item.porcentaje_impacto || 0, 1)}`,
                                    `Última revisión: ${item.ultima_revision_humana || 'Sin fecha'}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    function renderPlanes() {
        const summary = data.planes?.resumen || {};
        const status = data.planes?.estado_general || {};
        const porLinea = Array.isArray(data.planes?.por_linea) ? data.planes.por_linea : [];
        const plans = Array.isArray(data.planes?.planes) ? data.planes.planes : [];
        const stats = document.getElementById('planesStats');
        const banner = document.getElementById('planesBanner');
        const priority = document.getElementById('planesPriorityList');
        const work = document.getElementById('planesWorkList');
        const description = cardFromCanvas('componentesChart')?.querySelector('.chart-description');

        if (banner) {
            banner.className = `status-banner ${status.nivel || 'estable'}`;
            banner.innerHTML = `<i class="fas fa-shield-heart"></i><span>${escapeHtml(status.label || 'Controlado')} · Pendientes: ${Number(summary.pendientes || 0)} · Vencidos: ${Number(summary.vencidos || 0)}</span>`;
        }

        if (stats) {
            stats.innerHTML = miniStats([
                ['Activos', Number(summary.activos || 0), `${Number(summary.vencidos || 0)} vencidos`, 'danger'],
                ['Pendientes', Number(summary.pendientes || 0), `${Number(summary.proximos_7_dias || 0)} proximos a vencer`, 'warning'],
                ['Completados', Number(summary.completados || 0), `${Number(summary.avance || 0)}% avance global`, 'success'],
                ['Alta prioridad', Number(summary.prioridad_alta || 0), `${Number(summary.lineas_comprometidas || 0)} lavadoras comprometidas`, 'info']
            ]);
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> Avance: ${Number(summary.avance || 0)}% · Planes: ${Number(summary.total || 0)}`;
        }

        if (!Number(summary.total || 0)) {
            if (priority) priority.innerHTML = infoBox('No hay planes registrados para las lavadoras seleccionadas.');
            if (work) work.innerHTML = infoBox('Sin actividades abiertas.');
            destroy(charts.planes);
            setChartState('planes', true, 'Sin planes registrados', 'No existen planes de accion para construir el seguimiento operativo.', 'fa-clipboard');
            return;
        }

        if (priority) {
            priority.innerHTML = porLinea.slice(0, 5).map((item) => `
                <div class="priority-row">
                    <div class="priority-row-top">
                        <div>
                            <div class="priority-title">${escapeHtml(item.linea)}</div>
                            <div class="priority-meta">Abiertos: ${Number(item.abiertos || 0)} · Completados: ${Number(item.completados || 0)} · Alta prioridad: ${Number(item.alta_prioridad || 0)}</div>
                        </div>
                        <span class="severity-pill ${Number(item.alta_prioridad || 0) > 0 ? 'critico' : (Number(item.abiertos || 0) > 0 ? 'severo' : 'estable')}">${Number(item.porcentaje_cierre || 0)}% cierre</span>
                    </div>
                    <div class="progress-track"><div class="progress-bar" style="width: ${Math.min(Number(item.porcentaje_cierre || 0), 100)}%;"></div></div>
                </div>
            `).join('');
        }

        if (work) {
            work.innerHTML = plans.length
                ? plans.map((item) => `
                    <div class="work-item">
                        <div class="work-item-top">
                            <div>
                                <div class="work-title">${escapeHtml(item.linea)}</div>
                                <div class="work-meta">${escapeHtml(item.actividad || 'Sin descripcion')}</div>
                            </div>
                            <span class="severity-pill ${planClass(item.prioridad)}">${escapeHtml(item.prioridad_label || 'Sin fecha')}</span>
                        </div>
                        <div class="work-meta" style="margin-top: 8px;">Proxima fecha: ${escapeHtml(item.proxima_fecha_humana || 'Sin fecha')} · ${daysLabel(item.dias_restantes)}</div>
                    </div>
                `).join('')
                : infoBox('No hay actividades abiertas prioritarias en este momento.');
        }

        destroy(charts.planes);
        setChartState('planes', false);
        charts.planes = new Chart(document.getElementById('componentesChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pendientes criticos', 'Activos programados', 'Completados'],
                datasets: [{
                    data: [Number(summary.pendientes || 0), Number(summary.programados || 0), Number(summary.completados || 0)],
                    backgroundColor: ['rgba(239, 68, 68, 0.92)', 'rgba(245, 158, 11, 0.88)', 'rgba(16, 185, 129, 0.88)'],
                    borderColor: ['#dc2626', '#d97706', '#059669'],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: { label: (context) => `${context.label}: ${context.raw}` }
                    }
                }
            },
            plugins: [{
                id: 'planesCenterLabel',
                beforeDraw(chart) {
                    const { ctx } = chart;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = '#0f172a';
                    ctx.font = '700 24px sans-serif';
                    ctx.fillText(`${Number(summary.avance || 0)}%`, chart.width / 2, chart.height / 2 - 6);
                    ctx.fillStyle = '#64748b';
                    ctx.font = '600 11px sans-serif';
                    ctx.fillText('avance global', chart.width / 2, chart.height / 2 + 16);
                    ctx.restore();
                }
            }]
        });
    }

    function renderRanking() {
        const list = document.getElementById('rankingList');
        const footer = document.getElementById('rankingFooter');
        const empty = document.getElementById('rankingEmpty');

        if (!list) return;

        const rows = (Array.isArray(data.ranking) ? data.ranking : [])
            .map((item) => ({
                ...item,
                total_danos: Number(item.total_danos || 0),
                criticas: Number(item.criticas || 0),
                severos: Number(item.severos || 0),
                moderados: Number(item.moderados || 0),
                total_componentes: Number(item.total_componentes || 0),
                porcentaje_impacto: Number(item.porcentaje_impacto || 0),
                puntaje: Number(item.puntaje || item.total_danos || 0),
            }))
            .filter((item) => item.total_danos > 0);

        rows.sort((a, b) => Number(b.total_danos || 0)
            - Number(a.total_danos || 0)
            || Number(b.criticas || 0)
            - Number(a.criticas || 0)
            || Number(b.severos || 0)
            - Number(a.severos || 0)
            || Number(b.moderados || 0)
            - Number(a.moderados || 0)
            || Number(b.puntaje || 0)
            - Number(a.puntaje || 0));

        hideLoader('rankingLoader');

        if (!rows.length) {
            list.hidden = true;
            if (footer) footer.hidden = true;
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = emptyMarkup('Sin lavadoras con danos', 'Aun no existen danos activos para mostrar en el ranking.', 'fa-list-check');
            }
            return;
        }

        if (empty) empty.hidden = true;
        list.hidden = false;
        if (footer) {
            footer.hidden = false;
            const totalDanos = rows.reduce((sum, item) => sum + Number(item.total_danos || 0), 0);
            footer.innerHTML = `<div><i class="fas fa-info-circle"></i> Daños activos: ${number(totalDanos, 0)} · Lavadoras: ${rows.length}</div>`;
        }

        list.innerHTML = rows.slice(0, 10).map((item, index) => `
            <li class="ranking-item">
                <div class="ranking-position ${index === 0 ? 'top-1' : (index === 1 ? 'top-2' : (index === 2 ? 'top-3' : ''))}">${index + 1}</div>
                <div class="ranking-asset">
                    <div class="asset-media">
                        <i class="fas fa-industry" style="font-size: 18px; color: #2563eb;"></i>
                    </div>
                    <div class="ranking-info">
                        <div class="ranking-linea">${escapeHtml(item.linea || 'Sin linea')}</div>
                        <div class="ranking-puntaje"><i class="fas fa-triangle-exclamation"></i> Criticas: ${Number(item.criticas || 0)} · Severo / Moderado: ${Number(item.severos || 0) + Number(item.moderados || 0)}</div>
                        <div class="ranking-meta">Total con dano: ${Number(item.total_danos || 0)} de ${Number(item.total_componentes || 0)} componentes · Impacto ${percent(item.porcentaje_impacto || 0, 1)} · ${elapsedDaysLabel(item.dias_desde_revision)}</div>
                    </div>
                </div>
                <div class="ranking-status-stack">
                    <span class="severity-pill ${severityClass(item.prioridad)}">${escapeHtml(item.prioridad_label || 'Estable')}</span>
                    <div class="ranking-badge"><i class="fas fa-bolt"></i> ${number(item.total_danos || 0, 0)} danos</div>
                </div>
            </li>
        `).join('');
    }

    function renderElongaciones() {
        const stats = document.getElementById('elongacionesStats');
        const description = cardFromCanvas('elongacionesChart')?.querySelector('.chart-description');
        const select = document.getElementById('elongacionesLineaSelect');
        const rows = Array.isArray(data.elongaciones?.lineas) ? data.elongaciones.lineas : [];
        const item = rows.find((row) => Number(row.linea_id) === Number(state.elongacionLineaId)) || rows[0];

        if (select) select.value = String(item?.linea_id ?? state.elongacionLineaId ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length) {
            if (stats) {
                stats.innerHTML = miniStats([
                    ['Mediciones', 0, 'Sin historial', 'info'],
                    ['Periodo', '-', 'No hay fechas', 'warning'],
                    ['Max actual', '0%', 'Sin lecturas', 'danger']
                ]);
            }
            if (description) description.innerHTML = '<i class="fas fa-info-circle"></i> No existe historico de elongaciones para la lavadora seleccionada';
            destroy(charts.elongaciones);
            setChartState('elongaciones', true, 'Sin historial de elongaciones', 'Registra mediciones para visualizar la tendencia de la cadena.', 'fa-wave-square');
            return;
        }

        const current = Number(item.actual_max || 0);
        const maxTone = current >= Number(item.threshold_cambio || 0) ? 'danger' : (current >= Number(item.threshold_compra || 0) ? 'severo' : 'success');
        const status = current >= Number(item.threshold_cambio || 0) ? 'critico' : (current >= Number(item.threshold_compra || 0) ? 'severo' : 'success');
        if (stats) {
            stats.innerHTML = miniStats([
                ['Mediciones', Number(item.mediciones || 0), escapeHtml(item.linea || ''), 'info'],
                ['Desde', escapeHtml(item.desde || '-'), `Hasta ${escapeHtml(item.hasta || '-')}`, 'success'],
                ['Max actual', percent(current, 2), `Compra ${percent(item.threshold_compra || 0, 2)}`, maxTone],
                ['Estado', current >= Number(item.threshold_cambio || 0) ? 'Crítico' : (current >= Number(item.threshold_compra || 0) ? 'Seguimiento' : 'Estable'), `Cambio ${percent(item.threshold_cambio || 0, 2)}`, status]
            ]);
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(item.linea)} · ${Number(item.mediciones || 0)} mediciones desde ${escapeHtml(item.desde || '-')} hasta ${escapeHtml(item.hasta || '-')}`;
        }

        const compactViewport = window.innerWidth <= 480;
        const narrowViewport = window.innerWidth <= 768;
        const pointRadius = compactViewport ? 2 : (narrowViewport ? 3 : 4);
        const pointHoverRadius = compactViewport ? 4 : 6;
        const maxTicksLimit = compactViewport ? 4 : (narrowViewport ? 6 : 8);

        destroy(charts.elongaciones);
        setChartState('elongaciones', false);
        charts.elongaciones = new Chart(document.getElementById('elongacionesChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: item.labels,
                datasets: [
                    { label: 'Bombas', data: (item.bombas || []).map((value) => Number(value || 0)), borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.12)', borderWidth: compactViewport ? 2 : 3, pointRadius, pointHoverRadius, tension: 0.35, fill: true },
                    { label: 'Vapor', data: (item.vapor || []).map((value) => Number(value || 0)), borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.08)', borderWidth: compactViewport ? 2 : 3, pointRadius, pointHoverRadius, tension: 0.35, fill: true },
                    { label: 'Umbral compra', data: new Array(item.labels.length).fill(Number(item.threshold_compra || 0)), borderColor: '#f97316', borderWidth: 2, pointRadius: 0, borderDash: [8, 4] },
                    { label: 'Umbral cambio', data: new Array(item.labels.length).fill(Number(item.threshold_cambio || 0)), borderColor: '#ef4444', borderWidth: 2, pointRadius: 0, borderDash: [8, 4] }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 120,
                interaction: { mode: 'index', intersect: false },
                layout: {
                    padding: {
                        top: 8,
                        right: compactViewport ? 6 : 12,
                        bottom: compactViewport ? 6 : 10,
                        left: 0
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: { label: (context) => `${context.dataset.label}: ${percent(context.raw || 0, 2)}` }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            autoSkip: true,
                            maxTicksLimit,
                            maxRotation: narrowViewport ? 0 : 45,
                            minRotation: narrowViewport ? 0 : 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '8%',
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                        ticks: {
                            color: '#64748b',
                            padding: 8,
                            callback: (value) => `${value}%`
                        }
                    }
                }
            }
        });
    }

    function renderHistorico() {
        const stats = document.getElementById('historicoStats');
        const footer = document.getElementById('historicoTableBody')?.closest('.chart-card')?.querySelector('.table-footer');
        const body = document.getElementById('historicoTableBody');
        const labels = Array.isArray(data.historico?.labels) ? data.historico.labels : [];
        const series = data.historico?.series || {};
        const values = Array.isArray(series[state.historicoScope]) ? series[state.historicoScope] : (Array.isArray(series.Todas) ? series.Todas : []);
        const registrosPorAlcance = data.historico?.registros_por_alcance || {};
        const registros = Array.isArray(registrosPorAlcance[state.historicoScope])
            ? registrosPorAlcance[state.historicoScope]
            : (Array.isArray(data.historico?.registros)
                ? data.historico.registros
                    .filter((item) => state.historicoScope === 'Todas' || item.linea === state.historicoScope)
                    .slice(0, 5)
                : []);
        const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
        const peak = values.length ? Math.max(...values.map((value) => Number(value || 0))) : 0;
        const average = values.length ? total / values.length : 0;
        const last = registros[0]?.fecha_humana || data.historico?.resumen?.ultima_revision || 'Sin revision';

        if (stats) {
            stats.innerHTML = miniStats([
                ['Revisiones 12m', total, state.historicoScope === 'Todas' ? 'Vista consolidada' : escapeHtml(state.historicoScope), 'info'],
                ['Promedio mensual', number(average, 1), `${values.length || 0} cortes considerados`, 'success'],
                ['Pico mensual', peak, 'Mes con mayor actividad', 'warning'],
                ['Ultima revision', escapeHtml(last), `${registros.length} registros visibles`, 'danger']
            ]);
        }

        if (!values.length && !registros.length) {
            if (body) body.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-6">No hay historial disponible para el filtro seleccionado.</td></tr>`;
            if (footer) footer.innerHTML = '<i class="fas fa-info-circle"></i> Se mostrara la trazabilidad cuando existan revisiones registradas.';
            destroy(charts.historico);
            setChartState('historico', true, 'Sin historico disponible', 'No existen revisiones suficientes para construir la tendencia mensual.', 'fa-history');
            return;
        }

        if (body) {
            body.innerHTML = registros.length
                ? registros.map((item) => `
                    <tr>
                        <td data-label="Fecha">${escapeHtml(item.fecha_humana || '-')}</td>
                        <td data-label="Lavadora">${escapeHtml(item.linea || '-')}</td>
                        <td data-label="Componente">${escapeHtml(item.componente || '-')}</td>
                        <td data-label="Ubicación">${escapeHtml(item.reductor || '-')}${item.lado ? ` · ${escapeHtml(item.lado)}` : ''}</td>
                        <td data-label="Estado"><span class="severity-pill ${severityFromEstado(item.estado)}">${escapeHtml(item.estado || 'Sin estado')}</span></td>
                        <td data-label="Revisión">${escapeHtml(item.usuario || 'Sin usuario')}${item.actividad ? `<div class="text-xs text-gray-500 mt-1">${escapeHtml(item.actividad)}</div>` : ''}</td>
                    </tr>
                `).join('')
                : `<tr><td colspan="6" class="text-center text-gray-500 py-6">No hay registros recientes para este alcance.</td></tr>`;
        }

        if (footer) {
            footer.innerHTML = `<i class="fas fa-info-circle"></i> Revisiones visibles: ${registros.length} · ${escapeHtml(state.historicoScope === 'Todas' ? 'Todas las lavadoras' : state.historicoScope)}`;
        }

        destroy(charts.historico);
        setChartState('historico', false);
        charts.historico = new Chart(document.getElementById('historicoChart').getContext('2d'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: state.historicoScope === 'Todas' ? 'Todas las lavadoras' : state.historicoScope,
                    data: values.map((value) => Number(value || 0)),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.14)',
                    borderWidth: 3,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: '#2563eb',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: { label: (context) => `Revisiones: ${context.raw}` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { precision: 0, color: '#64748b' } }
                }
            }
        });
    }

    function renderTendenciaLegacy() {
        const stats = document.getElementById('analisis52124Stats');
        const description = cardFromCanvas('analisis52124Chart')?.querySelector('.chart-description');
        const select = document.getElementById('analisis52124LineaSelect');
        const rows = Array.isArray(data.tendencia?.lineas) ? data.tendencia.lineas : [];
        const item = rows.find((row) => Number(row.linea_id) === Number(state.tendenciaLineaId)) || rows[0];

        if (select) select.value = String(item?.linea_id ?? state.tendenciaLineaId ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length) {
            if (stats) {
                stats.innerHTML = miniStats([
                    ['52 semanas', 0, 'Sin datos', 'info'],
                    ['12 semanas', 0, 'Sin datos', 'warning'],
                    ['4 semanas', 0, 'Sin datos', 'success']
                ]);
            }
            if (description) description.innerHTML = '<i class="fas fa-info-circle"></i> Sin tendencia';
            destroy(charts.tendencia);
            setChartState('analisis52124', true, 'Sin tendencia disponible', 'Aun no existe historial para calcular las ventanas 52-12-4.', 'fa-wave-square');
            return;
        }

        const resumen = item.resumen || {};
        const current = Number(resumen.semanas_4 || 0);
        const medium = Number(resumen.semanas_12 || 0);
        const label = current > medium ? 'Acelerando' : (current === 0 ? 'Controlado' : 'Estable');

        if (stats) {
            stats.innerHTML = miniStats([
                ['52 semanas', Number(resumen.semanas_52 || 0), `Corte ${escapeHtml(item.ultimo_corte || '-')}`, 'info'],
                ['12 semanas', medium, 'Tendencia media', 'warning'],
                ['4 semanas', current, 'Tendencia corta', 'success'],
                ['Estado actual', label, escapeHtml(item.linea || ''), label === 'Acelerando' ? 'danger' : 'success']
            ]);
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(item.linea)} · Corte mas reciente ${escapeHtml(item.ultimo_corte || '-')}`;
        }

        destroy(charts.tendencia);
        setChartState('analisis52124', false);
        charts.tendencia = new Chart(document.getElementById('analisis52124Chart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: item.labels,
                datasets: [
                    { label: '52 semanas', data: (item.semanas_52 || []).map((value) => Number(value || 0)), borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.82)', borderWidth: 2, borderRadius: 8, borderSkipped: false },
                    { label: '12 semanas', data: (item.semanas_12 || []).map((value) => Number(value || 0)), borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.82)', borderWidth: 2, borderRadius: 8, borderSkipped: false },
                    { label: '4 semanas', data: (item.semanas_4 || []).map((value) => Number(value || 0)), borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.82)', borderWidth: 2, borderRadius: 8, borderSkipped: false }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 18, color: '#334155', font: { size: 11, weight: 700 } } },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.96)', titleColor: '#fff', bodyColor: '#e2e8f0' }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { precision: 0, color: '#64748b' } }
                }
            }
        });
    }

    function renderTendencia() {
        renderDamageTrendCardCompact({
            dataKey: 'tendencia',
            chartKey: 'tendencia',
            cardId: 'analisis52124Chart',
            prefix: 'analisis52124',
            selectId: 'analisis52124LineaSelect',
            statsId: 'analisis52124Stats',
            detailsId: 'analisis52124Breakdown',
            criteriaId: 'analisis52124Criteria',
            stateKey: 'tendenciaLineaId',
            emptyTitle: 'Sin tendencia disponible',
            emptyMessage: 'Aun no existe historial suficiente para calcular las ventanas 52-12-4.',
            emptyIcon: 'fa-wave-square'
        });
    }

    function renderTendencia30147() {
        renderDamageTrendCardCompact({
            dataKey: 'tendencia30147',
            chartKey: 'tendencia30147',
            cardId: 'analisis30147Chart',
            prefix: 'analisis30147',
            selectId: 'analisis30147LineaSelect',
            statsId: 'analisis30147Stats',
            detailsId: 'analisis30147Breakdown',
            criteriaId: 'analisis30147Criteria',
            stateKey: 'tendencia30147LineaId',
            emptyTitle: 'Sin tendencia disponible',
            emptyMessage: 'Aun no existe historial suficiente para calcular las ventanas 30-14-7.',
            emptyIcon: 'fa-chart-line'
        });
    }

    function renderDamageTrendCard(config) {
        const dataset = data[config.dataKey] || {};
        const rows = Array.isArray(dataset.lineas) ? dataset.lineas : [];
        const select = document.getElementById(config.selectId);
        const item = rows.find((row) => Number(row.linea_id) === Number(state[config.stateKey])) || rows[0];

        if (select) select.value = String(item?.linea_id ?? state[config.stateKey] ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length || !Array.isArray(item.series) || !item.series.length) {
            destroy(charts[config.chartKey]);
            setChartState(config.prefix, true, config.emptyTitle, config.emptyMessage, config.emptyIcon);
            return;
        }

        if (breakdown) {
            breakdown.innerHTML = ventanas.length
                ? ventanas.map((window) => `
                    <div class="breakdown-item">
                        <div class="breakdown-item-top">
                            <div>
                                <div class="breakdown-title">${escapeHtml(normalizeTrendLabel(window.label || 'Periodo'))}</div>
                                <div class="breakdown-meta">Actual: ${Number(window.current || 0)} (componentes ${Number(window.current_componentes || 0)}, elongaciones ${Number(window.current_elongaciones || 0)}) · Anterior: ${Number(window.previous || 0)} (componentes ${Number(window.previous_componentes || 0)}, elongaciones ${Number(window.previous_elongaciones || 0)})</div>
                            </div>
                            <span class="severity-pill ${trendPillClass(window.trend, Number(window.current || 0))}">${escapeHtml(trendDeltaLabel(Number(window.delta || 0), window.trend || 'stable'))}</span>
                        </div>
                        <div class="breakdown-meta">Rango actual: ${escapeHtml(window.current_range || '-')} · Rango anterior: ${escapeHtml(window.previous_range || '-')}</div>
                    </div>
                `).join('')
                : infoBox('No hay ventanas comparables disponibles para esta lavadora.');
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(item.linea || 'Sin linea')} · ${escapeHtml(resumen.estado || 'Sin fallas')} · Ultima falla ${escapeHtml(resumen.ultima_falla || 'Sin registro')} · Fuente ${escapeHtml(formatTrendCriteriaLabel(resumen.ultima_fuente || 'Sin registro'))}`;
        }

        const borderColors = ['#2563eb', '#f59e0b', '#10b981'];
        const currentColors = ['rgba(37, 99, 235, 0.82)', 'rgba(245, 158, 11, 0.82)', 'rgba(16, 185, 129, 0.82)'];
        const previousColors = ['rgba(37, 99, 235, 0.22)', 'rgba(245, 158, 11, 0.22)', 'rgba(16, 185, 129, 0.22)'];

        destroy(charts[config.chartKey]);
        setChartState(config.prefix, false);
        charts[config.chartKey] = new Chart(document.getElementById(config.cardId).getContext('2d'), {
            type: 'bar',
            data: {
                labels: (item.labels || []).map((label) => normalizeTrendLabel(label)),
                datasets: [
                    {
                        label: 'Periodo actual',
                        data: (item.actual || []).map((value) => Number(value || 0)),
                        borderColor: borderColors,
                        backgroundColor: currentColors,
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    },
                    {
                        label: 'Periodo anterior',
                        data: (item.anterior || []).map((value) => Number(value || 0)),
                        borderColor: borderColors,
                        backgroundColor: previousColors,
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, color: '#334155', font: { size: 11, weight: 700 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            title: (context) => normalizeTrendLabel(context[0]?.label || ''),
                            label: (context) => `${context.dataset.label}: ${Number(context.raw || 0)} fallas`,
                            afterBody: (contexts) => {
                                const window = ventanas[contexts[0]?.dataIndex ?? -1];
                                if (!window) return [];

                                return [
                                    `Actual: ${window.current_range || '-'}`,
                                    `Anterior: ${window.previous_range || '-'}`,
                                    `Componentes actuales: ${Number(window.current_componentes || 0)}`,
                                    `Elongaciones actuales: ${Number(window.current_elongaciones || 0)}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { precision: 0, color: '#64748b' } }
                }
            }
        });
    }

    function renderDamageTrendCardCompact(config) {
        const dataset = data[config.dataKey] || {};
        const rows = Array.isArray(dataset.lineas) ? dataset.lineas : [];
        const select = document.getElementById(config.selectId);
        const item = rows.find((row) => Number(row.linea_id) === Number(state[config.stateKey])) || rows[0];

        if (select) select.value = String(item?.linea_id ?? state[config.stateKey] ?? '');

        if (!item || item.sin_datos || !Array.isArray(item.labels) || !item.labels.length || !Array.isArray(item.series) || !item.series.length) {
            destroy(charts[config.chartKey]);
            setChartState(config.prefix, true, config.emptyTitle, config.emptyMessage, config.emptyIcon);
            return;
        }

        const palette = ['#22c55e', '#ef4444', '#f08a36'];
        const fills = ['rgba(34, 197, 94, 0.86)', 'rgba(239, 68, 68, 0.86)', 'rgba(240, 138, 54, 0.86)'];
        const datasets = item.series.map((serie, index) => ({
            label: normalizeTrendLabel(serie.label || `Serie ${index + 1}`),
            data: Array.isArray(serie.data) ? serie.data.map((value) => Number(value || 0)) : [],
            borderColor: palette[index % palette.length],
            backgroundColor: fills[index % fills.length],
            borderWidth: 1,
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 42
        }));

        destroy(charts[config.chartKey]);
        setChartState(config.prefix, false);
        charts[config.chartKey] = new Chart(document.getElementById(config.cardId).getContext('2d'), {
            type: 'bar',
            data: {
                labels: (item.labels || []).map((label) => normalizeTrendLabel(label)),
                datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 18,
                            color: '#334155',
                            font: { size: 11, weight: 700 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.96)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            title: (context) => normalizeTrendLabel(context[0]?.label || ''),
                            label: (context) => `${context.dataset.label}: ${Number(context.raw || 0)} daños`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', maxRotation: 45, minRotation: 45 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.16)' },
                        title: {
                            display: true,
                            text: 'Total de daños',
                            color: '#64748b',
                            font: { size: 12, weight: 700 }
                        },
                        ticks: { precision: 0, color: '#64748b' }
                    }
                }
            }
        });
    }

    function renderTrendCriteria(node, criteria) {
        if (!node) return;

        node.innerHTML = (criteria || [])
            .map((label) => `<span class="severity-pill ${trendCriteriaPillClass(label)}">${escapeHtml(formatTrendCriteriaLabel(label))}</span>`)
            .join('');
    }

    function formatTrendCriteriaLabel(label) {
        const raw = String(label || '');
        const lower = raw.toLowerCase();

        if (lower.includes('requiere cambio')) return 'Dañado - Requiere cambio';
        if (lower.includes('desgaste sever')) return 'Desgaste severo';
        if (lower.includes('desgaste moder')) return 'Desgaste moderado';
        if (lower.includes('elong')) return 'Elongación fuera de límite (> 1.46%)';

        return raw;
    }

    function trendCriteriaPillClass(label) {
        const normalized = formatTrendCriteriaLabel(label).toLowerCase();

        if (normalized.includes('requiere cambio')) return 'critico';
        if (normalized.includes('desgaste')) return 'severo';
        if (normalized.includes('elong')) return 'revision';

        return 'estable';
    }

    function normalizeTrendLabel(label) {
        const raw = String(label || '');

        return raw.replace(/dias/gi, 'días');
    }

    function toneToMiniStat(tone) {
        switch (tone) {
            case 'danger':
                return 'danger';
            case 'warning':
                return 'warning';
            case 'success':
                return 'success';
            default:
                return 'info';
        }
    }

    function trendPillClass(trendName, currentValue) {
        if (trendName === 'up') return 'critico';
        if (trendName === 'down') return 'estable';

        return Number(currentValue || 0) > 0 ? 'severo' : 'revision';
    }

    function trendDeltaLabel(delta, trendName) {
        const signed = `${delta > 0 ? '+' : ''}${delta}`;

        if (trendName === 'up') return `Alza ${signed}`;
        if (trendName === 'down') return `Baja ${signed}`;

        return delta === 0 ? 'Estable' : signed;
    }

    function ensureChartShell(canvasId, prefix, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const container = canvas.closest('.chart-container');
        if (!container) return;

        container.dataset.chartContainer = prefix;
        if (options.tall) container.classList.add('tall');

        let shell = container.parentElement;
        if (!shell.classList.contains('chart-shell')) {
            const nextShell = document.createElement('div');
            nextShell.className = `chart-shell${options.compact ? ' compact' : ''}`;
            container.parentNode.insertBefore(nextShell, container);
            nextShell.appendChild(container);
            shell = nextShell;
        } else if (options.compact) {
            shell.classList.add('compact');
        }

        if (!document.getElementById(`${prefix}Loader`)) {
            shell.insertAdjacentHTML('afterbegin', loaderMarkup(`${prefix}Loader`));
        }

        if (!document.getElementById(`${prefix}Empty`)) {
            shell.insertAdjacentHTML('afterbegin', `<div class="chart-empty-state" id="${prefix}Empty" hidden></div>`);
        }
    }

    function setChartState(prefix, empty, title = '', message = '', icon = 'fa-database') {
        const loader = document.getElementById(`${prefix}Loader`);
        const emptyNode = document.getElementById(`${prefix}Empty`);
        const container = document.querySelector(`[data-chart-container="${prefix}"]`);

        if (loader) loader.classList.add('is-hidden');
        if (container) container.hidden = empty;
        if (emptyNode) {
            emptyNode.hidden = !empty;
            if (empty) emptyNode.innerHTML = emptyMarkup(title, message, icon);
        }
    }

    function ensureAfterHeading(card, id, html) {
        if (document.getElementById(id)) return;
        const heading = card.querySelector('h3');
        if (heading) heading.insertAdjacentHTML('afterend', html);
    }

    function ensureAfterElement(referenceId, id, html) {
        if (document.getElementById(id)) return;
        const reference = document.getElementById(referenceId);
        if (reference) reference.insertAdjacentHTML('afterend', html);
    }

    function updateCardTitle(card, text, iconClass) {
        const title = card.querySelector('h3 span');
        const icon = card.querySelector('h3 i');
        if (title) title.textContent = text;
        if (icon) icon.className = iconClass;
    }

    function cardFromCanvas(id) {
        return document.getElementById(id)?.closest('.chart-card') ?? null;
    }

    function loaderMarkup(id) {
        return `
            <div class="card-loader" id="${id}">
                <div class="skeleton-line large"></div>
                <div class="skeleton-line medium"></div>
                <div class="skeleton-line large"></div>
                <div class="skeleton-line small"></div>
            </div>
        `;
    }

    function miniStats(items) {
        return items.map(([label, value, meta, tone]) => `
            <div class="mini-stat ${tone || 'info'}">
                <div class="mini-stat-label">${escapeHtml(String(label))}</div>
                <div class="mini-stat-value">${escapeHtml(String(value))}</div>
                <div class="mini-stat-meta">${escapeHtml(String(meta || ''))}</div>
            </div>
        `).join('');
    }

    function infoBox(message) {
        return `<div class="breakdown-item"><div class="breakdown-meta">${escapeHtml(message)}</div></div>`;
    }

    function emptyMarkup(title, message, icon) {
        return `<i class="fas ${escapeHtml(icon)}"></i><div style="font-weight: 800; color: #0f172a;">${escapeHtml(title)}</div><div>${escapeHtml(message)}</div>`;
    }

    function lineaOptions(selectedId) {
        return (data.lineas || []).map((linea) => `<option value="${linea.id}" ${Number(linea.id) === Number(selectedId) ? 'selected' : ''}>${escapeHtml(linea.nombre)}</option>`).join('');
    }

    function bindResponsiveChartResize() {
        const resizeCharts = () => {
            window.clearTimeout(resizeChartsTimer);
            resizeChartsTimer = window.setTimeout(() => {
                if (charts.elongaciones) {
                    renderElongaciones();
                }

                Object.entries(charts).forEach(([key, chart]) => {
                    if (!chart || key === 'elongaciones' || !chart.canvas || !chart.canvas.isConnected) return;

                    try {
                        chart.resize();
                        chart.update('none');
                    } catch (error) {
                        // Ignora instancias que hayan sido destruidas por estados sin datos.
                    }
                });
            }, 140);
        };

        window.addEventListener('resize', resizeCharts, { passive: true });
        window.addEventListener('orientationchange', resizeCharts);
    }

    function destroy(instance) {
        if (instance) instance.destroy();
    }

    function hideLoader(id) {
        const loader = document.getElementById(id);
        if (loader) loader.classList.add('is-hidden');
    }

    function number(value, decimals = 0) {
        return Number(value || 0).toLocaleString('es-MX', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function percent(value, decimals = 1) {
        return `${number(value, decimals)}%`;
    }

    function daysLabel(value) {
        if (value === null || value === undefined || Number.isNaN(Number(value))) return 'Sin fecha programada';
        const days = Number(value);
        if (days < 0) return `Vencido por ${Math.abs(days)} dias`;
        if (days === 0) return 'Vence hoy';
        if (days === 1) return 'Vence en 1 dia';
        return `Vence en ${days} dias`;
    }

    function elapsedDaysLabel(value) {
        if (value === null || value === undefined || Number.isNaN(Number(value))) return 'Sin antiguedad disponible';
        const days = Math.max(Math.round(Number(value)), 0);
        if (days === 0) return 'Revisado hoy';
        if (days === 1) return 'Sin revisar desde 1 dia';
        return `Sin revisar desde ${days} dias`;
    }

    function severityClass(level) {
        switch (level) {
            case 'critico':
            case 'alta':
                return 'critico';
            case 'severo':
            case 'moderado':
            case 'media':
            case 'sin_fecha':
                return 'severo';
            case 'cambiado':
                return 'cambiado';
            default:
                return 'estable';
        }
    }

    function planClass(level) {
        switch (level) {
            case 'alta':
                return 'critico';
            case 'media':
            case 'sin_fecha':
                return 'severo';
            default:
                return 'estable';
        }
    }

    function severityFromEstado(estado) {
        if (estado === 'Dañado - Requiere cambio') return 'critico';
        if (estado === 'Requiere revisión') return 'revision';
        if (estado === 'Desgaste severo') return 'severo';
        if (estado === 'Desgaste moderado') return 'moderado';
        if (estado === 'Cambiado') return 'cambiado';
        return 'estable';
    }

    function trend(values) {
        const series = Array.isArray(values) ? values.map((value) => Number(value || 0)) : [];
        const total = series.length;
        if (!total) return [];

        const sumX = series.reduce((sum, _, index) => sum + index, 0);
        const sumY = series.reduce((sum, value) => sum + value, 0);
        const sumXY = series.reduce((sum, value, index) => sum + (index * value), 0);
        const sumXX = series.reduce((sum, _, index) => sum + (index * index), 0);
        const divisor = (total * sumXX) - (sumX * sumX);
        if (!divisor) return [...series];

        const slope = ((total * sumXY) - (sumX * sumY)) / divisor;
        const intercept = (sumY - (slope * sumX)) / total;
        return series.map((_, index) => Number((intercept + (slope * index)).toFixed(2)));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
</script>
@endsection
