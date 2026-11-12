@extends('layouts.app')

@section('title', 'Maquinas - Envasado')

@section('content')
<style>
    .modulos-container {
        min-height: calc(100vh - 200px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 40px 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .modulos-header {
        text-align: center;
        margin-bottom: 48px;
    }

    .modulos-header h1 {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
    }

    .modulos-header p {
        font-size: 1.1rem;
        color: #64748b;
        max-width: 600px;
        margin: 0 auto;
    }

    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
        gap: 36px;
        max-width: 1320px;
        margin: 0 auto;
        width: 100%;
    }

    .modulo-card {
        background: white;
        border-radius: 32px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        position: relative;
    }

    .modulo-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 35px -12px rgba(0, 0, 0, 0.25);
    }

    .modulo-card.modulo-card-machine {
        --machine-cover-scale: 1.04;
        --machine-cover-hover-scale: 1.08;
        --machine-cover-position: center 47%;
        --machine-tint: rgba(59, 130, 246, 0.16);
        --machine-title-accent-start: #2563eb;
        --machine-title-accent-end: #38bdf8;
        --machine-title-glow: rgba(37, 99, 235, 0.32);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 26px 50px -30px rgba(15, 23, 42, 0.38), 0 16px 30px -24px rgba(59, 130, 246, 0.2);
    }

    .modulo-card.modulo-card-machine.modulo-card-lavadora {
        --machine-cover-scale: 1.02;
        --machine-cover-hover-scale: 1.06;
        --machine-cover-position: center 44%;
        --machine-tint: rgba(59, 130, 246, 0.18);
        --machine-trend-fill: rgba(37, 99, 235, 0.16);
    }

    .modulo-card.modulo-card-machine.modulo-card-pasteurizadora {
        --machine-cover-scale: 1.03;
        --machine-cover-hover-scale: 1.07;
        --machine-cover-position: center 46%;
        --machine-tint: rgba(249, 115, 22, 0.18);
        --machine-trend-fill: rgba(234, 88, 12, 0.16);
        --machine-title-accent-start: #ea580c;
        --machine-title-accent-end: #f59e0b;
        --machine-title-glow: rgba(234, 88, 12, 0.32);
    }

    .modulo-card.modulo-card-machine.modulo-card-etiquetadora {
        --machine-cover-scale: 1;
        --machine-cover-hover-scale: 1.03;
        --machine-cover-position: center 44%;
        --machine-tint: rgba(16, 185, 129, 0.15);
        --machine-trend-fill: rgba(5, 150, 105, 0.16);
        --machine-title-accent-start: #059669;
        --machine-title-accent-end: #10b981;
        --machine-title-glow: rgba(5, 150, 105, 0.28);
    }

    .modulo-card.modulo-card-machine::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        border: 1px solid rgba(255, 255, 255, 0.55);
        pointer-events: none;
        z-index: 0;
    }

    .modulo-card.modulo-card-machine .modulo-header {
        padding: 0 0 26px;
    }

    .modulo-card.modulo-card-machine .modulo-copy {
        position: relative;
        z-index: 4;
        width: calc(100% - 42px);
        margin: -38px auto 0;
        padding: 18px 22px 16px;
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(255, 255, 255, 0.92);
        border-radius: 24px;
        box-shadow: 0 22px 42px -32px rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .modulo-copy p:empty {
        display: none;
    }

    .modulo-card.modulo-card-machine .modulo-copy h2,
    .modulo-card.modulo-card-machine .modulo-copy p {
        padding: 0;
    }

    .modulo-card.modulo-card-machine .modulo-copy h2 {
        margin: 0 auto 6px;
        font-size: 1.9rem;
        letter-spacing: -0.03em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding-bottom: 12px;
        background: linear-gradient(135deg, #0f172a 0%, #334155 58%, var(--machine-title-accent-end) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: none;
    }

    .modulo-title-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .modulo-title-row h2 {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .modulo-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        border-radius: 999px;
        color: #047857;
        background: #dff7eb;
        border: 1px solid rgba(16, 185, 129, 0.2);
        font-size: 0.72rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .modulo-status--attention {
        color: #b91c1c;
        background: #fee2e2;
        border-color: rgba(220, 38, 38, 0.2);
    }

    .modulo-status--attention i {
        animation: criticalPulse 1s ease-in-out infinite;
    }

    .modulo-card.modulo-card-machine .modulo-copy h2::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 78px;
        height: 4px;
        border-radius: 999px;
        transform: translateX(-50%);
        background: linear-gradient(90deg, var(--machine-title-accent-start) 0%, var(--machine-title-accent-end) 100%);
        box-shadow: 0 10px 18px -10px var(--machine-title-glow);
    }

    .modulo-card.modulo-card-machine .modulo-copy p {
        margin: 0;
        color: #60708a;
    }

    .modulo-card.modulo-card-machine .modulo-footer {
        padding: 24px 28px 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }

    .modulo-card.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        filter: grayscale(0.2);
    }

    .modulo-card.disabled:hover {
        transform: none;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .modulo-header {
        padding: 32px;
        text-align: center;
        position: relative;
        transition: all 0.3s ease;
    }

    .modulo-icon {
        width: 100px;
        height: 100px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 48px;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .modulo-icon.has-image {
        background: transparent !important;
        box-shadow: none !important;
        padding: 0;
        width: 200px;
        height: 200px;
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image {
        width: 100%;
        min-width: 100%;
        height: clamp(220px, 24vw, 265px);
        border-radius: 32px 32px 24px 24px;
        overflow: hidden;
        margin: 0;
        position: relative;
        isolation: isolate;
        box-shadow: inset 0 -24px 40px -36px rgba(15, 23, 42, 0.65);
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.06) 0%, rgba(15, 23, 42, 0.03) 48%, rgba(15, 23, 42, 0.16) 100%),
            radial-gradient(circle at top right, var(--machine-tint) 0%, transparent 58%);
        z-index: 2;
        pointer-events: none;
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image::after {
        content: '';
        position: absolute;
        inset: auto 0 0 0;
        height: 42%;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.52) 56%, rgba(255, 255, 255, 0.96) 100%);
        z-index: 3;
        pointer-events: none;
    }

    .modulo-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: all 0.3s ease;
    }

    .modulo-card.modulo-card-machine .modulo-icon.has-image > img {
        width: 100%;
        height: 100%;
        border-radius: 0;
        display: block;
        object-fit: cover;
        object-position: var(--machine-cover-position);
        transform: scale(var(--machine-cover-scale));
        transform-origin: center;
        position: relative;
        z-index: 1;
    }
    
    .modulo-card:hover .modulo-icon.has-image > img {
        transform: scale(1.1);
    }

    .modulo-card.modulo-card-machine:hover .modulo-icon.has-image > img {
        transform: scale(var(--machine-cover-hover-scale));
    }

    .modulo-label-collage {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: clamp(10px, 2vw, 18px);
        width: 100%;
        height: 100%;
        padding: 30px 28px 54px;
        background: linear-gradient(180deg, #ffffff 0%, #eefdf6 100%);
    }

    .modulo-label-collage img {
        width: auto;
        height: clamp(118px, 13vw, 174px);
        max-width: 23%;
        object-fit: contain;
        filter: drop-shadow(0 16px 18px rgba(15, 23, 42, 0.18));
        transform: rotate(var(--label-tilt, 0deg)) translateY(var(--label-lift, 0));
        transform-origin: center bottom;
        transition: transform 0.3s ease, filter 0.3s ease;
    }

    .modulo-card-etiquetadora:hover .modulo-label-collage img {
        filter: drop-shadow(0 20px 22px rgba(15, 23, 42, 0.22));
        transform: rotate(var(--label-tilt, 0deg)) translateY(calc(var(--label-lift, 0px) - 6px)) scale(1.04);
    }

    .modulo-card.modulo-card-machine .modulo-stats {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.75) 0%, #f8fbff 100%);
        padding: 24px 24px 22px;
        border-top: 1px solid rgba(226, 232, 240, 0.85);
        border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    }

    .modulo-update {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
        padding: 0 26px 12px;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .modulo-update i {
        color: #64748b;
    }

    .modulo-card.modulo-card-machine .btn-acceder {
        background: linear-gradient(135deg, #1e293b 0%, #31466b 100%);
        box-shadow: 0 16px 24px -20px rgba(15, 23, 42, 0.78);
        padding: 12px 28px;
    }

    .modulo-card.modulo-card-machine:hover .btn-acceder {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        transform: translateY(-2px);
    }

    .modulo-icon:not(.has-image) {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
    }

    .modulo-icon.blue:not(.has-image) {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
    }

    .modulo-icon.orange:not(.has-image) {
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
        box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
    }

    .modulo-icon.green:not(.has-image) {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
    }

    .modulo-icon.purple:not(.has-image) {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);
    }

    .modulo-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
    }

    .modulo-header p {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .modulo-stats {
        background: #f8fafc;
        padding: 20px 24px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .stat-item {
        text-align: center;
    }

    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
    }

    .stat-value.critico {
        color: #dc2626;
    }

    /* ANIMACIÓN DE PARPADEO PARA ALERTAS CRÍTICAS */
    @keyframes criticalPulse {
        0% {
            color: #dc2626;
            text-shadow: 0 0 0 rgba(220, 38, 38, 0);
            transform: scale(1);
        }
        50% {
            color: #b91c1c;
            text-shadow: 0 0 10px rgba(220, 38, 38, 0.85), 0 0 16px rgba(185, 28, 28, 0.5);
            transform: scale(1.08);
        }
        100% {
            color: #dc2626;
            text-shadow: 0 0 0 rgba(220, 38, 38, 0);
            transform: scale(1);
        }
    }

    @keyframes criticalBlink {
        0% {
            opacity: 1;
            background-color: rgba(254, 226, 226, 0.95);
        }
        50% {
            opacity: 0.82;
            background-color: rgba(248, 113, 113, 0.35);
        }
        100% {
            opacity: 1;
            background-color: rgba(254, 226, 226, 0.95);
        }
    }

    .stat-value.critico.blinking {
        animation: criticalPulse 1s ease-in-out infinite;
        display: inline-block;
    }

    .stat-item.has-critics {
        background: rgba(254, 226, 226, 0.95);
        border: 1px solid rgba(220, 38, 38, 0.18);
        animation: criticalBlink 1.2s ease-in-out infinite;
        border-radius: 12px;
        padding: 4px 0;
        margin: -4px 0;
    }

    .stat-item.has-critics .stat-label,
    .stat-item.has-critics .stat-label i {
        color: #b91c1c;
    }

    .critical-indicator {
        display: inline-block;
        width: 7px;
        height: 7px;
        margin-left: 4px;
        border-radius: 999px;
        background: #dc2626;
        box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.5);
        vertical-align: middle;
    }

    .stat-item.has-critics .critical-indicator {
        animation: criticalDotPulse 1s ease-in-out infinite;
    }

    @keyframes criticalDotPulse {
        0%, 100% {
            opacity: 0.55;
            transform: scale(0.85);
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.45);
        }
        50% {
            opacity: 1;
            transform: scale(1.2);
            box-shadow: 0 0 0 6px rgba(220, 38, 38, 0.12), 0 0 12px rgba(220, 38, 38, 0.45);
        }
    }

    /* Contenedor para el número con efecto de pulso */
    .critical-number {
        display: inline-block;
        transition: all 0.3s ease;
    }

    .critical-number.blinking {
        animation: criticalPulse 0.8s ease-in-out infinite;
    }

    .stat-value.riesgo {
        color: #f97316;
    }

    .stat-value.bueno {
        color: #10b981;
    }

    .modulo-footer {
        padding: 20px 24px;
        text-align: center;
        background: white;
    }

    .modulo-trend {
        padding: 18px 24px 16px;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        border-top: 1px solid rgba(226, 232, 240, 0.9);
    }

    .modulo-trend-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 7px;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .modulo-trend-heading span:first-child {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .modulo-trend-heading i {
        color: var(--machine-title-accent-start, #2563eb);
    }

    .modulo-trend-total {
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .modulo-trend-chart-wrap {
        position: relative;
        padding: 6px 8px 0;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.95), var(--machine-trend-fill, rgba(37, 99, 235, 0.08)));
    }

    .modulo-trend-chart {
        display: block;
        width: 100%;
        height: 94px;
        overflow: visible;
    }

    .modulo-trend-gridline {
        stroke: #e2e8f0;
        stroke-width: 1;
        stroke-dasharray: 3 4;
    }

    .modulo-trend-area {
        fill: var(--machine-trend-fill, rgba(37, 99, 235, 0.12));
    }

    .modulo-trend-line {
        fill: none;
        stroke: var(--machine-title-accent-start, #2563eb);
        stroke-width: 2.5;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .modulo-trend-point {
        fill: #ffffff;
        stroke: var(--machine-title-accent-start, #2563eb);
        stroke-width: 2;
        cursor: help;
        outline: none;
        transition: transform 0.15s ease, stroke-width 0.15s ease;
        transform-box: fill-box;
        transform-origin: center;
    }

    .modulo-trend-point:hover,
    .modulo-trend-point:focus-visible {
        stroke-width: 3;
        transform: scale(1.8);
    }

    .modulo-trend-tooltip {
        position: absolute;
        z-index: 10;
        display: none;
        width: min(270px, calc(100% - 24px));
        max-width: calc(100% - 24px);
        max-height: 150px;
        box-sizing: border-box;
        padding: 8px 10px;
        border: 1px solid rgba(255, 255, 255, 0.28);
        border-radius: 10px;
        background: #17243d;
        color: #ffffff;
        box-shadow: 0 12px 24px -14px rgba(15, 23, 42, 0.72);
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.4;
        pointer-events: none;
        overflow-y: auto;
        white-space: pre-line;
        transform: translate(-50%, 0);
    }

    .modulo-trend-tooltip.is-visible {
        display: block;
    }

    .modulo-trend-labels {
        display: flex;
        justify-content: space-between;
        color: #94a3b8;
        font-size: 0.63rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    @media (prefers-reduced-motion: reduce) {
        .stat-item.has-critics,
        .critical-number.blinking,
        .modulo-status--attention i,
        .stat-item.has-critics .critical-indicator {
            animation: none !important;
        }
    }

    .btn-acceder {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 24px;
        background: #1e293b;
        color: white;
        border-radius: 40px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .modulo-card:hover .btn-acceder {
        background: #3b82f6;
        transform: translateX(5px);
    }

    .modulo-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 6px 12px;
        background: #10b981;
        color: white;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .last-update {
        text-align: center;
        margin-top: 48px;
        color: #94a3b8;
        font-size: 0.8rem;
    }

    @media (max-width: 768px) {
        .modulos-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .modulos-header h1 {
            font-size: 1.8rem;
        }
        
        .modulo-header h2 {
            font-size: 1.4rem;
        }
        
        .modulo-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .modulo-icon {
            width: 80px;
            height: 80px;
            font-size: 36px;
        }

        .modulo-card.modulo-card-machine .modulo-header {
            padding: 0 0 24px;
        }

        .modulo-card.modulo-card-machine .modulo-copy {
            width: calc(100% - 28px);
            margin-top: -30px;
            padding: 16px 18px 14px;
        }

        .modulo-card.modulo-card-machine .modulo-copy h2 {
            font-size: 1.6rem;
            padding-bottom: 10px;
        }

        .modulo-title-row {
            gap: 8px;
        }

        .modulo-status {
            font-size: 0.66rem;
            padding: 5px 9px;
        }

        .modulo-card.modulo-card-machine .modulo-copy h2::after {
            width: 64px;
        }

        .modulo-card.modulo-card-machine .modulo-icon.has-image {
            width: 100%;
            min-width: 100%;
            height: 190px;
        }

    }
</style>

<div class="modulos-container">
    <div class="modulos-header">
        <h1>
            Hola {{ Auth::user()->name }}, Bienvenido.
        </h1>
    </div>

    <div class="modulos-grid">
  @forelse($modulos as $modulo)
    @if($modulo['activo'])
        @php
            $hasCriticos = $modulo['estadisticas']['alertas_criticas'] > 0;
            $usaIconoEtiquetas = ($modulo['id'] ?? '') === 'etiquetadora';
            $tieneImagen = !$usaIconoEtiquetas && isset($modulo['imagen_personalizada']) && $modulo['imagen_personalizada'] && !empty($modulo['icono_imagen']);
            $tieneVisual = $tieneImagen || $usaIconoEtiquetas;
            $bloqueado = $modulo['bloqueado'] ?? false;
            $mensajeBloqueo = $modulo['mensaje_bloqueo'] ?? 'Estamos trabajando en ello, estara disponible muy pronto.';
            // La ruta está definida en el controlador dentro de cada módulo
            $rutaModulo = $modulo['ruta'] ?? route('dashboard_etiquetadora');
            $requiereAtencion = $hasCriticos
                || (($modulo['estadisticas']['en_riesgo'] ?? 0) > 0)
                || (($modulo['estadisticas']['requiere_revision'] ?? 0) > 0);
            $tendencia = $modulo['estadisticas']['tendencia'] ?? [];
            $trendValues = array_values(array_map('intval', $tendencia['values'] ?? array_fill(0, 7, 0)));
            $trendValues = count($trendValues) > 1 ? $trendValues : array_fill(0, 7, 0);
            $trendLabels = array_values($tendencia['labels'] ?? ['lu', 'ma', 'mi', 'ju', 'vi', 'sá', 'do']);
            $estadoLabels = [
                'critico' => 'Crítico',
                'riesgo' => 'Riesgo',
                'operativo' => 'Requiere revisión',
                'bueno' => 'Buen estado',
                'sin_datos' => 'Sin datos',
            ];
            $estadoPreview = $modulo['estadisticas']['estado_preview'] ?? [];
            $estadoPreviewText = collect($estadoPreview)->map(function (array $estado) use ($estadoLabels) {
                $nivel = $estado['nivel'] ?? 'sin_datos';
                return ($estado['nombre'] ?? 'Equipo') . ': ' . ($estadoLabels[$nivel] ?? 'Sin datos');
            })->implode(' · ');
            $trendMax = max(1, max($trendValues));
            $trendPoints = collect($trendValues)->map(function ($value, $index) use ($trendValues, $trendMax) {
                $x = count($trendValues) > 1 ? round(($index * 100) / (count($trendValues) - 1), 2) : 50;
                $y = round(48 - (($value / $trendMax) * 36), 2);
                return $x . ',' . $y;
            })->implode(' ');
            $trendAreaPoints = '0,54 ' . $trendPoints . ' 100,54';
        @endphp
                <div class="modulo-card {{ in_array($modulo['id'] ?? '', ['lavadora', 'pasteurizadora', 'etiquetadora'], true) ? 'modulo-card-machine modulo-card-' . ($modulo['id'] ?? '') : '' }}"
                     @if($bloqueado)
                         data-coming-soon-message="{{ $mensajeBloqueo }}"
                     @else
                         onclick="window.location.href='{{ $rutaModulo }}'"
                     @endif>
                    <div class="modulo-header">
                        <div class="modulo-icon {{ $modulo['color'] }} {{ $tieneVisual ? 'has-image' : '' }} {{ $usaIconoEtiquetas ? 'has-label-collage' : '' }}">
                            @if($usaIconoEtiquetas)
                                <div class="modulo-label-collage" aria-label="Presentaciones de Etiquetadora">
                                    <img src="{{ asset('images/Etiquetas/coronamega.png') }}" alt="Corona Mega" style="--label-tilt: -7deg; --label-lift: 12px;">
                                    <img src="{{ asset('images/Etiquetas/victoria1-cuarto.png') }}" alt="Victoria" style="--label-tilt: 5deg; --label-lift: -2px;">
                                    <img src="{{ asset('images/Etiquetas/Modelo especial.png') }}" alt="Modelo Especial" style="--label-tilt: -2deg; --label-lift: -14px;">
                                    <img src="{{ asset('images/Etiquetas/Barrilito.png') }}" alt="Barrilito" style="--label-tilt: 4deg; --label-lift: 6px;">
                                    <img src="{{ asset('images/Etiquetas/Pacifico-clara.png') }}" alt="Pacifico Clara" style="--label-tilt: 8deg; --label-lift: 18px;">
                                </div>
                            @elseif($tieneImagen)
                                <img src="{{ asset($modulo['icono_imagen']) }}" alt="{{ $modulo['nombre'] }}">
                            @else
                                <i class="fas {{ $modulo['icono'] }}"></i>
                            @endif
                        </div>
                        <div class="modulo-copy">
                            <div class="modulo-title-row">
                                <h2>{{ $modulo['nombre'] }}</h2>
                                <span class="modulo-status {{ $requiereAtencion ? 'modulo-status--attention' : '' }}">
                                    <i class="fas {{ $requiereAtencion ? 'fa-exclamation-triangle' : 'fa-check-circle' }}" aria-hidden="true"></i>
                                    {{ $requiereAtencion ? 'Requiere atención' : 'Operativo' }}
                                </span>
                            </div>
                            <p>{{ $modulo['descripcion'] }}</p>
                        </div>
                    </div>

                    <div class="modulo-update">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        Última actualización: {{ $modulo['estadisticas']['ultima_actualizacion'] ?? 'Ahora' }}
                    </div>
                    
                    <div class="modulo-stats">
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-microchip mr-1"></i>
                                Equipos
                            </div>
                            <div class="stat-value">{{ $modulo['estadisticas']['total_equipos'] }}</div>
                        </div>
                        <div class="stat-item {{ $hasCriticos ? 'has-critics' : '' }}">
                            <div class="stat-label">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Alertas Críticas
                                @if($hasCriticos)
                                    <span class="critical-indicator" aria-label="Alerta crítica activa"></span>
                                @endif
                            </div>
                            <div class="stat-value critico">
                                <span class="critical-number {{ $hasCriticos ? 'blinking' : '' }}">
                                    {{ $modulo['estadisticas']['alertas_criticas'] }}
                                </span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-chart-line mr-1"></i>
                                Severo / Moderado
                            </div>
                            <div class="stat-value riesgo">{{ $modulo['estadisticas']['en_riesgo'] }}</div>
                        </div>
                        @if(isset($modulo['estadisticas']['requiere_revision']))
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-tools mr-1"></i>
                                    Revisión
                                </div>
                                <div class="stat-value" style="color: #f59e0b;">{{ $modulo['estadisticas']['requiere_revision'] }}</div>
                            </div>
                        @endif
                        <div class="stat-item">
                            <div class="stat-label">
                                <i class="fas fa-check-circle mr-1"></i>
                                Buen Estado
                            </div>
                            <div class="stat-value bueno">{{ $modulo['estadisticas']['buen_estado'] }}</div>
                        </div>
                    </div>

                    <div class="modulo-trend" aria-label="Tendencia de actividad de los últimos siete días">
                        <div class="modulo-trend-heading">
                            <span>
                                <i class="fas fa-chart-line" aria-hidden="true"></i>
                                Tendencia (7 días)
                            </span>
                            <span class="modulo-trend-total">
                                Estado actual
                            </span>
                        </div>
                        <div class="modulo-trend-chart-wrap">
                        <svg class="modulo-trend-chart" viewBox="0 0 100 60" role="img" aria-label="Tendencia de análisis de {{ $modulo['nombre'] }}">
                            <line class="modulo-trend-gridline" x1="0" y1="12" x2="100" y2="12"></line>
                            <line class="modulo-trend-gridline" x1="0" y1="30" x2="100" y2="30"></line>
                            <line class="modulo-trend-gridline" x1="0" y1="48" x2="100" y2="48"></line>
                            <polygon class="modulo-trend-area" points="{{ $trendAreaPoints }}"></polygon>
                            <polyline class="modulo-trend-line" points="{{ $trendPoints }}"></polyline>
                            @foreach($trendValues as $trendIndex => $trendValue)
                                @php
                                    $pointX = count($trendValues) > 1 ? round(($trendIndex * 100) / (count($trendValues) - 1), 2) : 50;
                                    $pointY = round(48 - (($trendValue / $trendMax) * 36), 2);
                                @endphp
                                <circle class="modulo-trend-point"
                                        cx="{{ $pointX }}"
                                        cy="{{ $pointY }}"
                                        r="1.6"
                                        tabindex="0"
                                        role="img"
                                        data-status-preview="{{ $estadoPreviewText }}"
                                        data-point-y="{{ $pointY }}"
                                        aria-label="Estado actual por equipo: {{ $estadoPreviewText }}">
                                </circle>
                            @endforeach
                        </svg>
                        <div class="modulo-trend-tooltip" role="status" aria-live="polite"></div>
                        </div>
                        <div class="modulo-trend-labels" aria-hidden="true">
                            @foreach($trendLabels as $trendLabel)
                                <span>{{ $trendLabel }}</span>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="modulo-footer">
                        <button type="button" class="btn-acceder">
                            Acceder al Módulo
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            @else
                <div class="modulo-card disabled {{ in_array($modulo['id'] ?? '', ['lavadora', 'pasteurizadora', 'etiquetadora'], true) ? 'modulo-card-machine modulo-card-' . ($modulo['id'] ?? '') : '' }}">
                    <div class="modulo-header">
                        <div class="modulo-icon {{ $modulo['color'] }}" style="opacity: 0.5;">
                            <i class="fas {{ $modulo['icono'] }}"></i>
                        </div>
                        <div class="modulo-copy">
                            <h2>{{ $modulo['nombre'] }}</h2>
                            <p>{{ $modulo['descripcion'] }}</p>
                        </div>
                    </div>
                    
                    <div class="modulo-footer">
                        <button class="btn-acceder" disabled style="background: #94a3b8; cursor: not-allowed;">
                            <i class="fas fa-lock mr-2"></i>
                            Próximamente
                        </button>
                    </div>
                </div>
            @endif
        @empty
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm font-semibold text-amber-800">
                No tiene modulos disponibles. Solicite al administrador la asignacion del permiso correspondiente.
            </div>
        @endforelse
    </div>

    <div class="last-update">
        <i class="fas fa-sync-alt mr-2"></i>
        Última actualización de datos: {{ $modulos[0]['estadisticas']['ultima_actualizacion'] ?? now()->format('d/m/Y') }}
    </div>
</div>

<script>
    
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.modulo-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        document.querySelectorAll('.modulo-trend-chart').forEach(chart => {
            const wrapper = chart.parentElement;
            const tooltip = wrapper?.querySelector('.modulo-trend-tooltip');

            if (!wrapper || !tooltip) {
                return;
            }

            const hideTrendTooltip = () => {
                tooltip.classList.remove('is-visible');
            };

            const showTrendTooltip = point => {
                const pointY = Number(point.dataset.pointY || 0);

                const statusPreview = point.dataset.statusPreview || 'Sin estados disponibles';
                tooltip.textContent = `Estado actual por equipo:\n${statusPreview}`;
                tooltip.classList.add('is-visible');

                const chartHeight = chart.getBoundingClientRect().height;
                const wrapperWidth = wrapper.getBoundingClientRect().width;
                const wrapperHeight = wrapper.getBoundingClientRect().height;
                const pointTop = Math.max(12, chart.offsetTop + ((pointY / 60) * chartHeight));
                const tooltipHeight = tooltip.getBoundingClientRect().height;
                const innerMargin = 12;
                const left = wrapperWidth / 2;
                const spaceAbove = pointTop - innerMargin;
                const spaceBelow = wrapperHeight - pointTop - innerMargin;
                const showBelow = spaceAbove < tooltipHeight && spaceBelow >= tooltipHeight;
                const top = spaceAbove >= tooltipHeight
                    ? pointTop
                    : (showBelow
                        ? pointTop
                        : Math.max(innerMargin, (wrapperHeight - tooltipHeight) / 2));

                tooltip.style.left = `${left}px`;
                tooltip.style.top = `${top}px`;
                tooltip.style.transform = spaceAbove >= tooltipHeight
                    ? 'translate(-50%, calc(-100% - 10px))'
                    : 'translate(-50%, 0)';
            };

            chart.querySelectorAll('.modulo-trend-point').forEach(point => {
                point.addEventListener('mouseenter', () => showTrendTooltip(point));
                point.addEventListener('focus', () => showTrendTooltip(point));
                point.addEventListener('mouseleave', hideTrendTooltip);
                point.addEventListener('blur', hideTrendTooltip);
            });
        });

        document.querySelectorAll('[data-coming-soon-message]').forEach(card => {
            card.addEventListener('click', function() {
                Swal.fire({
                    icon: 'info',
                    text: this.dataset.comingSoonMessage,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#1e40af'
                });
            });
        });

        
        const criticalNumbers = document.querySelectorAll('.critical-number.blinking');
        if (criticalNumbers.length > 0) {
            console.log(`⚠️ Hay ${criticalNumbers.length} módulo(s) con alertas críticas`);
            
    
            let originalTitle = document.title;
            let criticalCount = 0;
            document.querySelectorAll('.stat-value.critico').forEach(el => {
                let value = parseInt(el.innerText);
                if (value > 0) criticalCount += value;
            });
            
            if (criticalCount > 0) {
                
                setInterval(() => {
                    if (document.title === originalTitle) {
                        document.title = `⚠️ ${criticalCount} ALERTA(S) CRÍTICA(S) ⚠️`;
                    } else {
                        document.title = originalTitle;
                    }
                }, 2000);
            }
        }
    });

    
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.modulo-icon > img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                console.error('Error loading image:', this.src);
                
                const parent = this.parentElement;
                parent.classList.remove('has-image');
                parent.innerHTML = '<i class="fas fa-industry"></i>';
            });
        });
    });
</script>
@endsection
