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

    .ranking-item:last-child {
        border-bottom: none;
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
        margin-top: 24px;
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
                        <span class="status-tag {{ $estado['nivel'] === 'bueno' ? 'bueno' : ($estado['nivel'] === 'riesgo' ? 'riesgo' : 'critico') }}">
                            <i class="fas {{ $estado['nivel'] === 'bueno' ? 'fa-check-circle' : ($estado['nivel'] === 'riesgo' ? 'fa-exclamation-triangle' : 'fa-times-circle') }}"></i>
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
            <ul class="ranking-list">
                @foreach($rankingDanos as $index => $item)
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
            <div class="ranking-footer">
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
                    <tbody>
                        @foreach($historicoRevisiones as $item)
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
            <div class="mb-4 p-4 rounded-lg ${lavadora.estado.nivel === 'critico' ? 'bg-red-50 border-l-4 border-red-500' : (lavadora.estado.nivel === 'riesgo' ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-green-50 border-l-4 border-green-500')}">
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
@endsection