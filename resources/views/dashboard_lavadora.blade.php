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
        margin: 0;
        padding: 16px 20px;
        background: #f8fafc;
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
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 12px 14px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--medium-gray);
        transition: var(--transition);
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
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 16px;
        overflow-x: auto;
        padding-bottom: 8px;
    }

    .lavadora-card {
        border-radius: 12px;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        background: white;
        border: 1px solid var(--medium-gray);
        min-width: 280px;
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
        background-color: #fefce8;
        border-left: 6px solid var(--warning-yellow);
    }

    .lavadora-card.operativo-estado {
        background-color: #fff7ed;
        border-left: 6px solid var(--operational-orange);
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
        align-items: center;
    }

    .lavadora-nombre {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .status-icon {
        font-size: 14px;
    }

    .buen-estado .status-icon { color: var(--success-green); }
    .operativo-estado .status-icon { color: var(--operational-orange); }
    .riesgo-estado .status-icon { color: var(--warning-yellow); }
    .critico-estado .status-icon { color: var(--danger-red); }

    .status-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 16px;
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
    }

    .status-tag.bueno { background: var(--success-light); color: #065f46; }
    .status-tag.operativo { background: var(--operational-light); color: #9a3412; }
    .status-tag.riesgo { background: var(--warning-light); color: #92400e; }
    .status-tag.critico { background: var(--danger-light); color: #991b1b; }

    .lavadora-card-body {
        padding: 10px 12px;
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
        align-items: center;
        gap: 10px;
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
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 11px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
        padding: 8px;
        border-radius: 10px;
        border: 1px solid rgba(59, 130, 246, 0.1);
    }

    .metric-item {
        text-align: center;
        flex: 1;
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
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-top: 1px solid var(--border-light);
        display: flex;
        justify-content: flex-end;
    }

    /* ═══════════════════════════════════════════════════════════════ */
    /* ▓▓▓ SECCIONES MEJORADAS - GRÁFICAS Y COMPONENTES ▓▓▓ */
    /* ═══════════════════════════════════════════════════════════════ */

    /* Tarjetas de Gráficas - Estilo Premium */
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

    /* Títulos de Gráficas */
    .chart-card h3 {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.3px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(59, 130, 246, 0.08);
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
        height: 280px;
        position: relative;
        padding: 12px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, rgba(139, 92, 246, 0.02) 100%);
        border-radius: 12px;
        margin: 4px 0;
    }

    /* Descripción informativa bajo gráfica */
    .chart-description {
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
        gap: 6px;
        border: 1px solid rgba(59, 130, 246, 0.1);
        font-weight: 500;
    }

    .chart-description i {
        font-size: 12px;
        color: var(--primary-blue);
        filter: drop-shadow(0 1px 2px rgba(59, 130, 246, 0.15));
    }

    /* Secciones */
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
        padding: 16px 18px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.92) 100%);
        transition: var(--transition);
        position: relative;
        border-radius: 14px;
        margin-bottom: 0;
        box-shadow: var(--shadow-sm);
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
        gap: 6px;
        transition: var(--transition);
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
        align-items: center;
        font-size: 13px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .ranking-footer i {
        margin-right: 8px;
        color: var(--primary-blue);
        font-size: 14px;
    }

    /* ═══════════════════════════════════════════════════════════════ */
    /* ▓▓▓ TABLA - ESTILO ADMINISTRATIVO PROFESIONAL ▓▓▓ */
    /* ═══════════════════════════════════════════════════════════════ */

    .chart-card .overflow-x-auto {
        border-radius: 14px;
        overflow: hidden;
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
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 16px;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .lavadoras-grid {
            grid-template-columns: 1fr;
        }
        .chart-card {
            padding: 20px;
        }
        .chart-container {
            height: 280px;
        }
        .grid.gap-8 {
            gap: 20px;
        }
        .section-title {
            font-size: 20px;
            margin: 28px 0 20px 0;
            gap: 10px;
            padding-left: 14px;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .chart-card h3 {
            font-size: 16px;
        }
        .chart-container {
            height: 250px;
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
        margin-bottom: 14px;
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.5;
        max-width: 760px;
    }

    .panel-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
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
    }

    .panel-link:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
        background: #f8fafc;
    }

    .panel-select {
        min-width: 150px;
        padding: 10px 12px;
        box-shadow: var(--shadow-sm);
        outline: none;
    }

    .panel-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
    }

    .filter-chip-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .filter-chip {
        padding: 9px 12px;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
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
        gap: 12px;
        margin-bottom: 18px;
    }

    .mini-stats-grid.compact {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .mini-stat {
        background: white;
        border: 1px solid var(--border-light);
        border-radius: 14px;
        padding: 14px;
        box-shadow: var(--shadow-sm);
        min-height: 88px;
    }

    .mini-stat.danger { border-top: 4px solid var(--danger-red); }
    .mini-stat.warning { border-top: 4px solid var(--warning-yellow); }
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
        padding: 14px 16px;
        border-radius: 14px;
        margin-bottom: 18px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .status-banner.critico {
        background: var(--danger-light);
        color: #991b1b;
        border-color: rgba(239, 68, 68, 0.18);
    }

    .status-banner.riesgo {
        background: var(--warning-light);
        color: #92400e;
        border-color: rgba(245, 158, 11, 0.18);
    }

    .status-banner.estable {
        background: var(--success-light);
        color: #065f46;
        border-color: rgba(16, 185, 129, 0.18);
    }

    .chart-shell {
        position: relative;
        margin: 16px 0 14px;
        border-radius: 14px;
        overflow: hidden;
    }

    .chart-shell .chart-container {
        margin: 0;
        padding: 14px 12px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    .chart-shell.compact .chart-container {
        height: 236px;
    }

    .chart-container.tall {
        height: 312px;
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
        min-height: 260px;
        padding: 28px 24px;
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
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .severity-pill.critico {
        background: var(--danger-light);
        color: #991b1b;
    }

    .severity-pill.revision {
        background: var(--operational-light);
        color: #9a3412;
    }

    .severity-pill.severo,
    .severity-pill.moderado {
        background: var(--warning-light);
        color: #92400e;
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
        .filter-chip {
            width: 100%;
            justify-content: center;
        }

        .ranking-asset {
            margin-left: 10px;
            gap: 10px;
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
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    Dashboard Lavadoras
                </h1>
            </div>
            <div class="flex gap-2">
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
        <div class="stat-card" style="border-top: 4px solid var(--warning-yellow);">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">En Riesgo</div>
            <div class="stat-value" style="color: var(--warning-yellow);">{{ $resumenGeneral['en_riesgo'] }}</div>
        </div>
        <div class="stat-card" style="border-top: 4px solid var(--operational-orange);">
            <div class="stat-icon"><i class="fas fa-tools"></i></div>
            <div class="stat-label">Requiere Revisión</div>
            <div class="stat-value" style="color: var(--operational-orange);">{{ $resumenGeneral['requiere_revision'] }}</div>
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
                            {{ ucfirst($estado['nivel']) }}
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
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium shadow-sm">
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        {{-- Gráfica 1: Fallas por Línea --}}
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-bar"></i>
                <span>Fallas por Línea</span>
            </h3>
            <div class="chart-container">
                <canvas id="fallasChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Detección y análisis de fallas por línea de producción
            </div>
        </div>

        {{-- Gráfica 2: Componentes Más Dañados --}}
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-pie"></i>
                <span>Componentes Más Dañados</span>
            </h3>
            <div class="chart-container">
                <canvas id="componentesChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Distribución de daños según tipo de componente
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        {{-- Ranking: Lavadoras con Mayor Daño --}}
        <div class="chart-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Daño</span>
            </h3>
            <ul class="ranking-list" id="rankingList">
                @foreach([] as $index => $item)
                    <li class="ranking-item">
                        <div class="ranking-position {{ $index === 0 ? 'top-1' : ($index === 1 ? 'top-2' : ($index === 2 ? 'top-3' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="ranking-info">
                            <div class="ranking-linea">{{ $item['linea'] }}</div>
                            <div class="ranking-puntaje">
                                <i class="fas fa-star"></i> 
                                Puntaje: <strong>{{ $item['puntaje'] }}</strong>
                            </div>
                        </div>
                        <div class="ranking-badge">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $item['analisis_criticos'] }} críticos
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="ranking-footer" id="rankingFooter">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Ordenado por nivel de criticidad
                </div>
            </div>
        </div>

        {{-- Gráfica 3: Evolución de Elongaciones --}}
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Evolución de Elongaciones</span>
            </h3>
            <div class="chart-container">
                <canvas id="elongacionesChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Comparativa de tendencias: Bombas vs Vapor
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Tabla: Histórico de Revisiones --}}
        <div class="chart-card">
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
                                <td><i class="fas fa-microchip mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                <td>{{ $item['total_analisis'] }}</td>
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

        {{-- Gráfica 4: Análisis 52-12-4 --}}
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Análisis 52-12-4 | Tendencia de Daños</span>
            </h3>
            <div class="chart-container">
                <canvas id="analisis52124Chart"></canvas>
            </div>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-light); display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; text-align: center;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 24px; height: 24px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.85), rgba(59, 130, 246, 1)); border-radius: 6px; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);"></div>
                    <span style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">52 Semanas</span>
                </div>
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 24px; height: 24px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.85), rgba(245, 158, 11, 1)); border-radius: 6px; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);"></div>
                    <span style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">12 Semanas</span>
                </div>
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 24px; height: 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.85), rgba(16, 185, 129, 1)); border-radius: 6px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);"></div>
                    <span style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">4 Semanas</span>
                </div>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Comparativa de tendencias en 3 períodos de tiempo
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
            <div class="mb-4 p-4 rounded-lg ${lavadora.estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (lavadora.estado.nivel === 'riesgo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : (lavadora.estado.nivel === 'operativo' ? 'bg-orange-50 border-l-4 border-orange-500' : 'bg-green-50 border-l-4 border-green-500'))}">
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
        lineas: @json($lineaOptions),
        fallas: @json($fallasPorLinea),
        planes: @json($planesAccionDashboard),
        ranking: @json($rankingDanos),
        elongaciones: @json($evolucionElongaciones),
        historico: @json($historicoRevisiones),
        tendencia: @json($analisis52124)
    };

    const charts = {
        fallas: null,
        planes: null,
        elongaciones: null,
        historico: null,
        tendencia: null
    };

    const state = {
        fallasFilter: 'all',
        rankingScope: 'all',
        rankingSort: 'puntaje',
        elongacionLineaId: data.elongaciones?.default_linea_id ?? data.lineas?.[0]?.id ?? null,
        historicoScope: 'Todas',
        tendenciaLineaId: data.tendencia?.default_linea_id ?? data.lineas?.[0]?.id ?? null
    };

    let layoutReady = false;

    window.initCharts = initCharts = function () {
        if (!layoutReady) {
            setupLayout();
            layoutReady = true;
        }

        renderFallas();
        renderPlanes();
        renderRanking();
        renderElongaciones();
        renderHistorico();
        renderTendencia();
    };

    function setupLayout() {
        setupFallasCard();
        setupPlanesCard();
        setupRankingCard();
        setupElongacionesCard();
        setupHistoricoCard();
        setupTendenciaCard();
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
                    <button type="button" class="filter-chip" data-filter="criticas">Criticas</button>
                    <button type="button" class="filter-chip" data-filter="severas_moderadas">Severas y moderadas</button>
                </div>
            </div>
        `);
        ensureChartShell('fallasChart', 'fallas');

        const description = card.querySelector('.chart-description');
        if (description && !document.getElementById('fallasLegend')) {
            description.insertAdjacentHTML('beforebegin', `
                <div class="legend-inline" id="fallasLegend">
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(239, 68, 68, 0.92);"></span> Critico</span>
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(249, 115, 22, 0.9);"></span> Severo / Moderado</span>
                    <span class="legend-item"><span class="legend-swatch" style="background: rgba(16, 185, 129, 0.9);"></span> Estable</span>
                </div>
            `);
            description.insertAdjacentHTML('afterend', `
                <div class="subpanel-title" style="margin-top: 18px;">Lavadoras con mayor impacto</div>
                <p class="subpanel-copy">Ordenadas automaticamente segun el filtro activo y el porcentaje de afectacion.</p>
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
            description.innerHTML = '<i class="fas fa-info-circle"></i> Seguimiento de planes activos, pendientes y completados por lavadora';
        }

        if (description && !document.getElementById('planesPriorityList')) {
            description.insertAdjacentHTML('afterend', `
                <div class="subpanel-title" style="margin-top: 18px;">Carga por lavadora</div>
                <p class="subpanel-copy">Balance de apertura, cierre y avance por linea.</p>
                <div class="priority-list" id="planesPriorityList"></div>
                <div class="subpanel-title" style="margin-top: 18px;">Planes activos prioritarios</div>
                <p class="subpanel-copy">Actividades abiertas con mayor urgencia o sin fecha definida.</p>
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
                <p class="subpanel-copy">Se muestran los registros recientes disponibles segun el alcance seleccionado.</p>
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
                <th><i class="fas fa-user" style="color: #10b981;"></i> Revision</th>
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
        const card = cardFromCanvas('analisis52124Chart');
        if (!card) return;

        card.classList.add('dashboard-panel');
        updateCardTitle(card, 'Analisis 52-12-4 | Tendencia de danos', 'fas fa-wave-square');
        ensureAfterHeading(card, 'tendenciaCopy', `<p id="tendenciaCopy" class="panel-copy"></p>`);
        ensureAfterElement('tendenciaCopy', 'tendenciaActions', `
            <div id="tendenciaActions" class="panel-actions" style="margin-bottom: 18px; justify-content: flex-start;">
                <select id="analisis52124LineaSelect" class="panel-select">${lineaOptions(state.tendenciaLineaId)}</select>
            </div>
        `);
        ensureChartShell('analisis52124Chart', 'analisis52124', { tall: true });

        const select = document.getElementById('analisis52124LineaSelect');
        if (select && select.dataset.bound !== 'true') {
            select.dataset.bound = 'true';
            select.addEventListener('change', function () {
                state.tendenciaLineaId = Number(this.value);
                renderTendencia();
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
                    ['Criticas', '0', 'Sin registros', 'danger'],
                    ['Severas / Moderadas', '0', 'Sin registros', 'warning']
                ]);
            }
            if (breakdown) breakdown.innerHTML = infoBox('No hay datos disponibles para construir la matriz de fallas por linea.');
            if (description) description.innerHTML = '<i class="fas fa-info-circle"></i> La grafica se activara cuando existan analisis vigentes por lavadora';
            destroy(charts.fallas);
            setChartState('fallas', true, 'Sin datos de fallas', 'No existen componentes evaluados para mostrar la distribucion por linea.', 'fa-database');
            return;
        }

        const key = state.fallasFilter === 'criticas' ? 'criticas' : (state.fallasFilter === 'severas_moderadas' ? 'severas_moderadas' : 'impactados');
        const sorted = rows.slice().sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0) || Number(b.porcentaje_impacto || 0) - Number(a.porcentaje_impacto || 0));

        const criticas = rows.reduce((sum, item) => sum + Number(item.criticas || 0), 0);
        const warnings = rows.reduce((sum, item) => sum + Number(item.severas_moderadas || 0), 0);
        const impactadas = rows.filter((item) => Number(item.impactados || 0) > 0).length;
        const promedio = rows.length ? rows.reduce((sum, item) => sum + Number(item.porcentaje_impacto || 0), 0) / rows.length : 0;

        if (stats) {
            stats.innerHTML = miniStats([
                ['Lavadoras impactadas', impactadas, `${rows.length} monitoreadas`, 'info'],
                ['Fallas criticas', criticas, 'Rojo = requiere cambio', 'danger'],
                ['Severas / Moderadas', warnings, 'Naranja = seguimiento', 'warning'],
                ['Impacto promedio', percent(promedio, 1), 'Sobre componentes vigentes', 'success']
            ]);
        }

        if (breakdown) {
            breakdown.innerHTML = sorted.slice(0, 5).map((item) => `
                <div class="breakdown-item">
                    <div class="breakdown-item-top">
                        <div>
                            <div class="breakdown-title">${escapeHtml(item.linea)}</div>
                            <div class="breakdown-meta">Criticas: ${Number(item.criticas || 0)} · Severo/Moderado: ${Number(item.severas_moderadas || 0)} · Ultima revision: ${escapeHtml(item.ultima_revision_humana || 'Sin fecha')}</div>
                        </div>
                        <span class="severity-pill ${item.estado === 'critico' ? 'critico' : (item.estado === 'riesgo' ? 'severo' : 'estable')}">${percent(item.porcentaje_impacto || 0, 1)}</span>
                    </div>
                    <div class="progress-track"><div class="progress-bar" style="width: ${Math.min(Number(item.porcentaje_impacto || 0), 100)}%;"></div></div>
                </div>
            `).join('');
        }

        if (description) {
            const copy = state.fallasFilter === 'criticas'
                ? 'Vista enfocada solo en fallas criticas que requieren cambio inmediato.'
                : (state.fallasFilter === 'severas_moderadas'
                    ? 'Vista enfocada en desgaste severo y moderado para seguimiento preventivo.'
                    : 'Vista integral con criticidad y desgaste por cada lavadora.');
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${copy}`;
        }

        const datasets = state.fallasFilter === 'all'
            ? [
                { label: 'Criticas', data: sorted.map((item) => Number(item.criticas || 0)), backgroundColor: 'rgba(239, 68, 68, 0.92)', borderColor: '#dc2626', borderWidth: 2, borderRadius: 10, borderSkipped: false },
                { label: 'Severas / Moderadas', data: sorted.map((item) => Number(item.severas_moderadas || 0)), backgroundColor: 'rgba(249, 115, 22, 0.88)', borderColor: '#ea580c', borderWidth: 2, borderRadius: 10, borderSkipped: false },
                { label: 'Estables', data: sorted.map((item) => Number(item.estables || 0)), backgroundColor: 'rgba(16, 185, 129, 0.24)', borderColor: '#10b981', borderWidth: 1, borderRadius: 10, borderSkipped: false }
            ]
            : [{
                label: state.fallasFilter === 'criticas' ? 'Criticas' : 'Severas / Moderadas',
                data: sorted.map((item) => Number(item[state.fallasFilter] || 0)),
                backgroundColor: state.fallasFilter === 'criticas' ? 'rgba(239, 68, 68, 0.92)' : 'rgba(249, 115, 22, 0.9)',
                borderColor: state.fallasFilter === 'criticas' ? '#dc2626' : '#ea580c',
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
                                return [`Impactados: ${Number(item.impactados || 0)}`, `Impacto: ${percent(item.porcentaje_impacto || 0, 1)}`, `Ultima revision: ${item.ultima_revision_humana || 'Sin fecha'}`];
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
            banner.innerHTML = `<i class="fas fa-shield-heart"></i><span>${escapeHtml(status.label || 'Controlado')}: ${escapeHtml(status.mensaje || 'Sin alertas activas')}</span>`;
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
            description.innerHTML = `<i class="fas fa-info-circle"></i> Avance global: ${Number(summary.avance || 0)}% · Total de planes registrados: ${Number(summary.total || 0)}`;
        }

        if (!Number(summary.total || 0)) {
            if (priority) priority.innerHTML = infoBox('No hay planes registrados para las lavadoras seleccionadas.');
            if (work) work.innerHTML = infoBox('Cuando existan actividades abiertas se mostraran aqui.');
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

        const rows = (Array.isArray(data.fallas) ? data.fallas : [])
            .map((item) => ({
                linea: item.linea,
                total_danos: Number(item.criticas || 0) + Number(item.severas_moderadas || 0),
                criticas: Number(item.criticas || 0),
                severas_moderadas: Number(item.severas_moderadas || 0),
            }));

        rows.sort((a, b) => Number(b.total_danos || 0) - Number(a.total_danos || 0) || Number(b.criticas || 0) - Number(a.criticas || 0) || Number(b.severas_moderadas || 0) - Number(a.severas_moderadas || 0));

        hideLoader('rankingLoader');

        if (!rows.length) {
            list.hidden = true;
            if (footer) footer.hidden = true;
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = emptyMarkup('Sin lavadoras con daños', 'Aun no existen daños activos para mostrar en el ranking.', 'fa-list-check');
            }
            return;
        }

        if (empty) empty.hidden = true;
        list.hidden = false;
        if (footer) footer.hidden = true;

        list.innerHTML = rows.slice(0, 10).map((item, index) => `
            <li class="ranking-item">
                <div class="ranking-position ${index === 0 ? 'top-1' : (index === 1 ? 'top-2' : (index === 2 ? 'top-3' : ''))}">${index + 1}</div>
                <div class="ranking-asset">
                    <div class="asset-media">
                        <i class="fas fa-industry" style="font-size: 18px; color: #2563eb;"></i>
                    </div>
                    <div class="ranking-info">
                        <div class="ranking-linea">${escapeHtml(item.linea || 'Sin linea')}</div>
                        <div class="ranking-puntaje"><i class="fas fa-industry"></i> ${escapeHtml(item.linea || 'Sin linea')} · ${escapeHtml(item.reductor || 'Sin reductor')}${item.lado ? ` · ${escapeHtml(item.lado)}` : ''}</div>
                        <div class="ranking-meta">Revision: ${escapeHtml(item.fecha_analisis_humana || 'Sin fecha')} · ${elapsedDaysLabel(item.dias_desde_revision)}</div>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <span class="severity-pill ${severityClass(item.prioridad)}">${escapeHtml(item.prioridad_label || 'Estable')}</span>
                    <div class="ranking-badge"><i class="fas fa-bolt"></i> Puntaje ${number(item.puntaje || 0, 2)}</div>
                </div>
            </li>
        `).join('');

        list.querySelectorAll('.ranking-item').forEach((element, index) => {
            const item = rows[index];
            if (!item) return;

            const puntaje = element.querySelector('.ranking-puntaje');
            const meta = element.querySelector('.ranking-meta');
            const badge = element.querySelector('.ranking-badge');

            if (puntaje) {
                puntaje.innerHTML = `<i class="fas fa-triangle-exclamation"></i> Criticas: ${Number(item.criticas || 0)} · Severos: ${Number(item.severos || 0)} · Moderados: ${Number(item.moderados || 0)}`;
            }

            if (meta) {
                meta.textContent = `Total con dano: ${Number(item.total_danos || 0)} de ${Number(item.total_componentes || 0)} componentes · Impacto ${percent(item.porcentaje_impacto || 0, 1)} · ${elapsedDaysLabel(item.dias_desde_revision)}`;
            }

            if (badge) {
                badge.innerHTML = `<i class="fas fa-bolt"></i> ${number(item.total_danos || 0, 0)} danos`;
            }
        });
    }

    function renderRanking() {
        const list = document.getElementById('rankingList');
        const footer = document.getElementById('rankingFooter');
        const empty = document.getElementById('rankingEmpty');

        if (!list) return;

        const rows = (Array.isArray(data.fallas) ? data.fallas : []).map((item) => ({
            linea: item.linea,
            total_danos: Number(item.criticas || 0) + Number(item.severas_moderadas || 0),
            criticas: Number(item.criticas || 0),
            severos: Number(item.severas_moderadas || 0),
            moderados: 0,
        }));

        rows.sort((a, b) => Number(b.total_danos || 0) - Number(a.total_danos || 0) || Number(b.criticas || 0) - Number(a.criticas || 0) || Number(b.severos || 0) - Number(a.severos || 0));

        hideLoader('rankingLoader');

        if (!rows.length) {
            list.hidden = true;
            if (footer) footer.hidden = true;
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = emptyMarkup('Sin lavadoras con daños', 'Aun no existen daños activos para mostrar en el ranking.', 'fa-list-check');
            }
            return;
        }

        if (empty) empty.hidden = true;
        list.hidden = false;
        if (footer) footer.hidden = true;

        list.innerHTML = rows.slice(0, 10).map((item, index) => `
            <li class="ranking-item">
                <div class="ranking-position ${index === 0 ? 'top-1' : (index === 1 ? 'top-2' : (index === 2 ? 'top-3' : ''))}">${index + 1}</div>
                <div class="ranking-asset">
                    <div class="ranking-info">
                        <div class="ranking-linea">${escapeHtml(item.linea || 'Sin linea')}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center;">
                    <div class="ranking-badge"><i class="fas fa-bolt"></i> ${number(item.total_danos || 0, 0)} daños</div>
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
        const status = current >= Number(item.threshold_cambio || 0) ? 'critico' : (current >= Number(item.threshold_compra || 0) ? 'warning' : 'success');
        if (stats) {
            stats.innerHTML = miniStats([
                ['Mediciones', Number(item.mediciones || 0), escapeHtml(item.linea || ''), 'info'],
                ['Desde', escapeHtml(item.desde || '-'), `Hasta ${escapeHtml(item.hasta || '-')}`, 'success'],
                ['Max actual', percent(current, 2), `Compra ${percent(item.threshold_compra || 0, 2)}`, 'warning'],
                ['Estado', current >= Number(item.threshold_cambio || 0) ? 'Critico' : (current >= Number(item.threshold_compra || 0) ? 'Seguimiento' : 'Estable'), `Cambio ${percent(item.threshold_cambio || 0, 2)}`, status]
            ]);
        }

        if (description) {
            description.innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(item.linea)} · ${Number(item.mediciones || 0)} mediciones desde ${escapeHtml(item.desde || '-')} hasta ${escapeHtml(item.hasta || '-')}`;
        }

        destroy(charts.elongaciones);
        setChartState('elongaciones', false);
        charts.elongaciones = new Chart(document.getElementById('elongacionesChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: item.labels,
                datasets: [
                    { label: 'Bombas', data: (item.bombas || []).map((value) => Number(value || 0)), borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.12)', borderWidth: 3, pointRadius: 4, tension: 0.35, fill: true },
                    { label: 'Vapor', data: (item.vapor || []).map((value) => Number(value || 0)), borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.08)', borderWidth: 3, pointRadius: 4, tension: 0.35, fill: true },
                    { label: 'Umbral compra', data: new Array(item.labels.length).fill(Number(item.threshold_compra || 0)), borderColor: '#f59e0b', borderWidth: 2, pointRadius: 0, borderDash: [8, 4] },
                    { label: 'Umbral cambio', data: new Array(item.labels.length).fill(Number(item.threshold_cambio || 0)), borderColor: '#10b981', borderWidth: 2, pointRadius: 0, borderDash: [8, 4] }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
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
                    x: { grid: { display: false }, ticks: { color: '#64748b', maxRotation: 45, minRotation: 45 } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.16)' }, ticks: { color: '#64748b', callback: (value) => `${value}%` } }
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
        const registros = Array.isArray(data.historico?.registros) ? data.historico.registros.filter((item) => state.historicoScope === 'Todas' || item.linea === state.historicoScope) : [];
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
                        <td>${escapeHtml(item.fecha_humana || '-')}</td>
                        <td>${escapeHtml(item.linea || '-')}</td>
                        <td>${escapeHtml(item.componente || '-')}</td>
                        <td>${escapeHtml(item.reductor || '-')}${item.lado ? ` · ${escapeHtml(item.lado)}` : ''}</td>
                        <td><span class="severity-pill ${severityFromEstado(item.estado)}">${escapeHtml(item.estado || 'Sin estado')}</span></td>
                        <td>${escapeHtml(item.usuario || 'Sin usuario')}${item.actividad ? `<div class="text-xs text-gray-500 mt-1">${escapeHtml(item.actividad)}</div>` : ''}</td>
                    </tr>
                `).join('')
                : `<tr><td colspan="6" class="text-center text-gray-500 py-6">No hay registros recientes para este alcance.</td></tr>`;
        }

        if (footer) {
            footer.innerHTML = `<i class="fas fa-info-circle"></i> Historial dinamico de revisiones para ${escapeHtml(state.historicoScope === 'Todas' ? 'todas las lavadoras' : state.historicoScope)}.`;
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

    function renderTendencia() {
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
            if (description) description.innerHTML = '<i class="fas fa-info-circle"></i> La tendencia 52-12-4 se activara cuando exista historial suficiente';
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
