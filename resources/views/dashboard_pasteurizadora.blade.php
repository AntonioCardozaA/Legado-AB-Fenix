@extends('layouts.app')

@section('title', 'Pasteurizadoras')

@section('content')
@php
    $pasteurizadoras = collect($estadoPasteurizadoras);
    $fallasPorLineaPasteurizadora = collect($fallasPorLineaPasteurizadora ?? []);
    $componentesDanadosPasteurizadora = collect($componentesDanadosPasteurizadora ?? []);
    $historicoRevisionesPasteurizadora = collect($historicoRevisionesPasteurizadora ?? []);
    $analisis52124Pasteurizadora = collect($analisis52124Pasteurizadora ?? []);
    $planesPendientesPasteurizadora = collect($planesPendientesPasteurizadora ?? []);
    $ultimosAnalisisPasteurizadora = collect($ultimosAnalisisPasteurizadora ?? []);
    $totalPasteurizadoras = max((int) ($resumenPasteurizadora['total_pasteurizadoras'] ?? $pasteurizadoras->count()), 1);
    $estadoLineas = [
        'bueno' => $pasteurizadoras->where('estado.nivel', 'bueno')->count(),
        'operativo' => $pasteurizadoras->where('estado.nivel', 'operativo')->count(),
        'riesgo' => $pasteurizadoras->where('estado.nivel', 'riesgo')->count(),
        'critico' => $pasteurizadoras->where('estado.nivel', 'critico')->count(),
    ];
    $avancePromedio = round($pasteurizadoras->avg('estado.progreso_revision.porcentaje') ?? 0);
    $totalRevisados = $pasteurizadoras->sum(fn($item) => (int) data_get($item, 'estado.progreso_revision.revisados', 0));
    $totalConfigurados = $pasteurizadoras->sum(fn($item) => (int) data_get($item, 'estado.progreso_revision.total', 0));
    $rankingPasteurizadoras = $pasteurizadoras
        ->sortByDesc(fn($item) => (($item['estado']['nivel'] ?? 'bueno') === 'critico' ? 300 : (($item['estado']['nivel'] ?? 'bueno') === 'riesgo' ? 220 : (($item['estado']['nivel'] ?? 'bueno') === 'operativo' ? 160 : 100))) + (int) data_get($item, 'estado.acciones_pendientes', 0))
        ->take(5)
        ->values();
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
        margin: 0;
        padding: 16px 20px;
        background: #f8fafc;
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
    .operativo-estado .status-icon { color: var(--warning-yellow); }
    .riesgo-estado .status-icon { color: var(--operational-orange); }
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
    .status-tag.operativo { background: var(--warning-light); color: #92400e; }
    .status-tag.riesgo { background: var(--operational-light); color: #9a3412; }
    .status-tag.critico { background: var(--danger-light); color: #991b1b; }

    .lavadora-card-body {
        padding: 10px 12px;
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
        justify-content: flex-end;
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
        filter: drop-shadow(0 1px 2px rgba(59, 130, 246, 0.15));
    }

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
        gap: 6px;
        border: 1px solid rgba(59, 130, 246, 0.1);
        font-weight: 500;
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
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .lavadoras-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 12px;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
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
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .lavadora-metricas {
            flex-direction: column;
            gap: 8px;
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
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    Dashboard Pasteurizadoras
                </h1>
            </div>
            <div class="flex gap-2">
                <button onclick="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-industry"></i></div>
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="chart-card">
            <h3>
                <i class="fas fa-trophy"></i>
                <span>Ranking de Atención</span>
            </h3>
            <ul class="ranking-list">
                @forelse($rankingPasteurizadoras as $index => $item)
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
                <span>Plan de Acción Pendiente</span>
            </h3>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-industry" style="color: #3b82f6;"></i> Línea</th>
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
                                <td>{{ $plan->linea?->nombre ?? 'Sin línea' }}</td>
                                <td>
                                    <div>{{ Str::limit($plan->actividad ?? 'Sin actividad', 48) }}</div>
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
                Conectado con la vista de plan de acción de pasteurizadora
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="chart-card">
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
                                <td><i class="fas fa-microchip mr-2 text-gray-400"></i>{{ $item['componente'] }}</td>
                                <td>{{ $item['ultimo_analisis'] }}</td>
                                <td>{{ $item['total_analisis'] }}</td>
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
                Información conectada con el historial de análisis de pasteurizadora
            </div>
        </div>

        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                <span>Análisis 52-12-4</span>
            </h3>
            <div class="chart-container">
                <canvas id="analisis52124PasteurizadoraChart"></canvas>
            </div>
            <div class="chart-description">
                <i class="fas fa-info-circle"></i>
                Registros reales conectados con la vista de tendencia mensual
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
    let fallasPasteurizadoraChart, componentesPasteurizadoraChart, analisis52124PasteurizadoraChart;
    const pasteurizadorasData = @json($pasteurizadoras->values());
    const fallasPorLineaPasteurizadora = @json($fallasPorLineaPasteurizadora->values());
    const componentesDanadosPasteurizadora = @json($componentesDanadosPasteurizadora->values());
    const analisis52124Pasteurizadora = @json($analisis52124Pasteurizadora->values());

    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
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

        const analisis52124Ctx = document.getElementById('analisis52124PasteurizadoraChart').getContext('2d');
        analisis52124PasteurizadoraChart = new Chart(analisis52124Ctx, {
            type: 'bar',
            data: {
                labels: analisis52124Pasteurizadora.map(item => item.linea?.nombre || 'N/A'),
                datasets: [
                    {
                        label: '52 Semanas',
                        data: analisis52124Pasteurizadora.map(item => parseFloat(item.valor_actual_52) || 0),
                        backgroundColor: 'rgba(59, 130, 246, 0.9)',
                        borderColor: '#1e40af',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false
                    },
                    {
                        label: '12 Semanas',
                        data: analisis52124Pasteurizadora.map(item => parseFloat(item.valor_actual_12) || 0),
                        backgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: '#b45309',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false
                    },
                    {
                        label: '4 Semanas',
                        data: analisis52124Pasteurizadora.map(item => parseFloat(item.valor_actual_4) || 0),
                        backgroundColor: 'rgba(16, 185, 129, 0.9)',
                        borderColor: '#047857',
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
                        ticks: { font: { size: 12, weight: 600 }, color: '#64748b', padding: 8 }
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
                        padding: 14
                    },
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 18, font: { size: 12, weight: 'bold' }, color: '#334155' }
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
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}?linea_id=${pasteurizadora.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Análisis
                </a>
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index') }}?linea_id=${pasteurizadora.id}" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">
                    <i class="fas fa-tasks mr-1"></i> Ver Plan
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
