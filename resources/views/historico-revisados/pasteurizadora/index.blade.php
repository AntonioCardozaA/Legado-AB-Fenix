@extends('layouts.app')

@section('title', $historicoRevisadosTitulo ?? 'Historico de Revisados - Pasteurizadora')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
    $analisisDashboardRoute = $analisisDashboardRoute ?? 'pasteurizadora.dashboard';
    $pasteurizadorSeleccionadoNumero = isset($lineaSeleccionada)
        ? preg_replace('/^P[-\s]?/i', '', trim((string) $lineaSeleccionada->nombre))
        : null;
    $pasteurizadorSeleccionadoLabel = $pasteurizadorSeleccionadoNumero
        ? (preg_match('/^pasteurizador\b/i', $pasteurizadorSeleccionadoNumero)
            ? $pasteurizadorSeleccionadoNumero
            : 'Pasteurizador ' . $pasteurizadorSeleccionadoNumero)
        : 'el pasteurizador seleccionado';
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
        padding: 30px clamp(16px, 3vw, 34px) 42px;
        overflow-x: clip;
    }

    .historico-container * {
        box-sizing: border-box;
        min-width: 0;
    }

    .lineas-section,
    .grafica-section,
    .resumen-card,
    .componentes-table {
        background: white;
        border: 1px solid var(--medium-gray);
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
    }

    .historico-page-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 30px !important;
    }

    .historico-header-content {
        display: flex;
        flex-direction: column;
        gap: 18px;
        width: 100%;
    }

    .historico-back-link {
        width: fit-content;
        min-height: 44px;
        padding: 10px 17px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 12px !important;
        color: #1e3a5f !important;
        background: rgba(255, 255, 255, 0.78) !important;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .historico-back-link:hover {
        transform: translateX(-3px);
        border-color: #60a5fa !important;
        background: #ffffff !important;
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.12);
    }

    .historico-title-row {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .historico-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        color: #2563eb;
        background: linear-gradient(145deg, #dbeafe, #eff6ff);
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.12);
        font-size: 18px;
    }

    .historico-page-title {
        margin: 0;
        color: #0f2747;
        font-size: clamp(24px, 3vw, 34px);
        font-weight: 800;
        letter-spacing: -0.025em;
        line-height: 1.15;
    }

    .lineas-section {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 24px;
        margin-bottom: 26px;
    }

    .lineas-section::before {
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: linear-gradient(90deg, #2563eb, #60a5fa, #8b5cf6);
        content: '';
    }

    .lineas-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        color: #0f2747;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .lineas-title::before {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #2563eb;
        box-shadow: 0 0 0 5px #dbeafe;
        content: '';
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        max-width: 100%;
        min-width: 0;
    }

    .linea-btn {
        display: inline-flex;
        flex: 0 0 94px;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 94px;
        min-width: 0;
        min-height: 48px;
        padding: 11px 12px;
        border: 1px solid #dbe3ef;
        border-radius: 15px;
        color: #334155;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.04);
        font-size: 14px;
        font-weight: 700;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, color 0.2s ease, background 0.2s ease;
        cursor: pointer;
        text-decoration: none;
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
        border-color: #93c5fd;
        color: #1d4ed8;
        background: #eff6ff;
        box-shadow: 0 9px 18px rgba(37, 99, 235, 0.12);
        transform: translateY(-3px);
    }

    .linea-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25), 0 0 0 4px rgba(59, 130, 246, 0.12);
        transform: translateY(-2px);
    }

    .linea-btn:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.32);
        outline-offset: 3px;
    }

    .linea-btn.active::after {
        display: inline-block;
        flex-shrink: 0;
        width: 7px;
        height: 7px;
        margin-left: 9px;
        border-radius: 50%;
        background: #bfdbfe;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.18);
        content: '';
    }

    .linea-btn.active i {
        color: white;
    }

    .resumen-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }

    .resumen-card {
        position: relative;
        min-height: 118px;
        overflow: hidden;
        border-radius: 20px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .resumen-card::before {
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #94a3b8, #475569);
        content: '';
        transition: width 0.2s ease;
    }

    .resumen-card::after {
        position: absolute;
        right: -38px;
        bottom: -58px;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: rgba(226, 232, 240, 0.5);
        content: '';
        pointer-events: none;
        transition: transform 0.25s ease;
    }

    .resumen-card:hover {
        border-color: #bfdbfe;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.11);
        transform: translateY(-4px);
    }

    .resumen-card:hover::before {
        width: 8px;
    }

    .resumen-card:hover::after {
        transform: scale(1.3);
    }

    .resumen-card--revisado::before {
        background: linear-gradient(180deg, #60a5fa, #2563eb);
    }

    .resumen-card--revisado::after {
        background: rgba(219, 234, 254, 0.72);
    }

    .resumen-card--porcentaje::before {
        background: linear-gradient(180deg, #34d399, #059669);
    }

    .resumen-card--porcentaje::after {
        background: rgba(209, 250, 229, 0.72);
    }

    .resumen-icono {
        position: relative;
        z-index: 1;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .resumen-card:hover .resumen-icono {
        transform: scale(1.08) rotate(-4deg);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.14);
    }

    .resumen-icono.total { background: #e2e8f0; color: #475569; }
    .resumen-icono.revisado { background: #dbeafe; color: #2563eb; }
    .resumen-icono.porcentaje { background: #d1fae5; color: #059669; }

    .resumen-info h4 {
        position: relative;
        z-index: 1;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 4px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .resumen-info .valor {
        position: relative;
        z-index: 1;
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }

    .componentes-table {
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .table-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
    }

    .table-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
        gap: 22px;
        padding: 20px;
    }

    .linea-group-title {
        grid-column: 1 / -1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 18px 22px;
    }

    .linea-group-main {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .linea-group-name {
        font-size: 18px;
        font-weight: 800;
        color: #1f2937;
        overflow-wrap: anywhere;
    }

    .linea-group-meta {
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
    }

    .linea-group-progress {
        min-width: 220px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .modulo-summary-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 22px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 18px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07);
    }

    .modulo-summary-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .modulo-summary-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--slate-900);
        margin: 0;
        overflow-wrap: anywhere;
    }

    .modulo-summary-subtitle {
        font-size: 13px;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modulo-summary-badge {
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .modulo-stats,
    .modulo-side-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .modulo-side-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .modulo-stat {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px;
        text-align: center;
    }

    .modulo-stat-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }

    .modulo-stat-value {
        font-size: 18px;
        font-weight: 800;
        color: var(--slate-900);
    }

    .modulo-side-pill {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .modulo-side-pill.pasillo {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .modulo-side-pill-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #1e3a8a;
    }

    .modulo-side-pill.pasillo .modulo-side-pill-label {
        color: #334155;
    }

    .modulo-side-pill-value {
        font-size: 13px;
        font-weight: 700;
        color: var(--slate-900);
    }

    .progress-container {
        width: 100%;
        background: #e2e8f0;
        border-radius: 10px;
        height: 24px;
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

    .modulo-summary-footer {
        display: flex;
        justify-content: flex-end;
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
        min-width: 0;
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
        background: rgba(15, 23, 42, 0.72);
        backdrop-filter: blur(6px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: clamp(10px, 2vw, 28px);
        overscroll-behavior: contain;
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        background: white;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 26px;
        width: min(1180px, 100%);
        max-height: min(900px, calc(100vh - 32px));
        overflow: hidden;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.34), 0 8px 24px rgba(15, 23, 42, 0.12);
    }

    .modal.show .modal-content {
        animation: modal-reveal 0.24s ease-out both;
    }

    @keyframes modal-reveal {
        from {
            opacity: 0;
            transform: translateY(10px) scale(0.985);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        position: relative;
        z-index: 1;
        flex-shrink: 0;
        padding: 20px 28px;
        background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }

    .modal-header-copy {
        min-width: 0;
    }

    .modal-header h3 {
        margin: 0;
        font-size: clamp(20px, 2.2vw, 26px);
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-header h3 i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 12px;
        color: #2563eb;
        background: #dbeafe;
        font-size: 15px;
    }

    .modal-subtitle {
        display: block;
        margin-top: 7px;
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
    }

    .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 26px 28px 30px;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-color: #94a3b8 transparent;
        scrollbar-width: thin;
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

    .modal-close:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.35);
        outline-offset: 3px;
    }

    .modal-module-overview {
        background: linear-gradient(135deg, #f8fbff 0%, #f8fafc 100%);
        color: #0f172a;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 18px;
        margin-bottom: 26px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .modal-overview-item {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 104px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px 18px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .modal-overview-item::before {
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, #60a5fa, #2563eb);
        content: '';
        transition: width 0.2s ease, filter 0.2s ease;
    }

    .modal-overview-item::after {
        position: absolute;
        right: -34px;
        bottom: -40px;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(219, 234, 254, 0.45);
        content: '';
        pointer-events: none;
        transition: transform 0.25s ease, opacity 0.2s ease;
    }

    .modal-overview-item:hover,
    .modal-overview-item:focus-within {
        transform: translateY(-4px);
        border-color: #93c5fd;
        background: #ffffff;
        box-shadow: 0 14px 26px rgba(37, 99, 235, 0.13);
    }

    .modal-overview-item:hover::before,
    .modal-overview-item:focus-within::before {
        width: 7px;
        filter: brightness(1.05);
    }

    .modal-overview-item:hover::after,
    .modal-overview-item:focus-within::after {
        transform: scale(1.35);
        opacity: 0.85;
    }

    .modal-overview-top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .modal-overview-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        color: #2563eb;
        background: #eff6ff;
        font-size: 13px;
        transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
    }

    .modal-overview-item:hover .modal-overview-icon,
    .modal-overview-item:focus-within .modal-overview-icon {
        transform: rotate(-6deg) scale(1.08);
        color: #ffffff;
        background: #2563eb;
    }

    .modal-overview-item--line::before {
        background: linear-gradient(180deg, #a78bfa, #7c3aed);
    }

    .modal-overview-item--line::after {
        background: rgba(237, 233, 254, 0.65);
    }

    .modal-overview-item--line .modal-overview-icon {
        border-color: #ddd6fe;
        color: #7c3aed;
        background: #f5f3ff;
    }

    .modal-overview-item--line:hover .modal-overview-icon,
    .modal-overview-item--line:focus-within .modal-overview-icon {
        background: #7c3aed;
    }

    .modal-overview-item--reviewed::before {
        background: linear-gradient(180deg, #34d399, #059669);
    }

    .modal-overview-item--reviewed::after {
        background: rgba(209, 250, 229, 0.65);
    }

    .modal-overview-item--reviewed .modal-overview-icon {
        border-color: #a7f3d0;
        color: #059669;
        background: #ecfdf5;
    }

    .modal-overview-item--reviewed:hover .modal-overview-icon,
    .modal-overview-item--reviewed:focus-within .modal-overview-icon {
        background: #059669;
    }

    .modal-overview-item--progress::before {
        background: linear-gradient(180deg, #fbbf24, #d97706);
    }

    .modal-overview-item--progress::after {
        background: rgba(254, 243, 199, 0.72);
    }

    .modal-overview-item--progress .modal-overview-icon {
        border-color: #fde68a;
        color: #d97706;
        background: #fffbeb;
    }

    .modal-overview-item--progress:hover .modal-overview-icon,
    .modal-overview-item--progress:focus-within .modal-overview-icon {
        background: #d97706;
    }

    .modal-overview-label {
        position: relative;
        z-index: 1;
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .modal-overview-value {
        position: relative;
        z-index: 1;
        font-size: 24px;
        line-height: 1.1;
        font-weight: 800;
        color: #0f172a;
    }

    .modal-levels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
        align-items: start;
        gap: 20px;
    }

    .modal-level-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 0;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        color: #0f172a;
    }

    .modal-level-card::before {
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: #cbd5e1;
        content: '';
    }

    .modal-level-card.nivel-inferior {
        background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
    }

    .modal-level-card.nivel-inferior::before {
        background: #94a3b8;
    }

    .modal-level-card:hover,
    .modal-level-card.is-selected {
        transform: translateY(-2px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
    }

    .modal-level-card:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.45);
        outline-offset: 3px;
    }

    .modal-level-card.is-selected {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14), 0 16px 30px rgba(15, 23, 42, 0.12);
    }

    .modal-level-card.is-selected::before {
        background: linear-gradient(90deg, #2563eb, #60a5fa);
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
        font-size: clamp(24px, 3vw, 30px);
        font-weight: 800;
        line-height: 1.1;
        margin: 0;
        color: #0f172a;
    }

    .modal-level-subtitle {
        display: block;
        margin-top: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
    }

    .modal-level-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        padding: 8px 13px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        color: #1d4ed8;
    }

    .modal-level-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
    }

    .modal-level-section-title::before {
        width: 7px;
        height: 7px;
        flex-shrink: 0;
        border-radius: 50%;
        background: #60a5fa;
        content: '';
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

    .modal-level-progress-meta {
        margin: 10px 0 7px;
    }

    .modal-level-component-progress-meta {
        margin-bottom: 7px;
    }

    .modal-level-card .progress-container {
        height: 14px;
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        background: #e2e8f0;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .modal-level-card .progress-label {
        color: #1e293b;
    }

    .modal .progress-bar {
        box-sizing: border-box;
        min-width: 0;
        border-radius: inherit;
    }

    .modal .progress-label {
        left: 10px;
        font-size: 11px;
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
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }

    .modal-level-component {
        background: #fbfdff;
        border-radius: 15px;
        padding: 14px;
        gap: 12px;
    }

    .modal-side-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }

    .modal-side-meta {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }

    .componente-nombre {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
        color: inherit;
        flex: 1 1 220px;
        min-width: 0;
    }

    .componente-nombre span {
        display: block;
        flex: 1 1 auto;
        min-width: 0;
        overflow: visible;
        overflow-wrap: anywhere;
        white-space: normal;
        line-height: 1.35;
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
    }

    .componente-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 4px;
    }

    .cantidad-badge {
        background: #e2e8f0;
        color: #334155;
        padding: 5px 10px;
        border-radius: 999px;
        font-weight: 700;
        font-size: 11px;
        white-space: nowrap;
    }

    .modal-review-card {
        border-color: #dbe3ef;
        background: #ffffff;
        gap: 12px;
    }

    .modal-review-summary,
    .modal-review-context {
        display: flex;
        gap: 10px;
    }

    .modal-review-summary {
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        min-width: 0;
    }

    .modal-review-context {
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        flex: 0 1 48%;
        max-width: 54%;
        min-width: 0;
        line-height: 1.35;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .modal-review-context span + span::before {
        content: "/";
        margin-right: 10px;
        color: #cbd5e1;
    }

    .modal-review-progress {
        min-width: 0;
        padding-top: 2px;
    }

    .modal-review-card .progress-container,
    .modal-side-block .progress-container {
        height: 14px !important;
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .modal-review-numbers {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding: 9px 10px;
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        background: #f8fafc;
    }

    .modal-review-numbers-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .modal-review-number-list {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .modal-review-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 7px;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: 12px;
        font-weight: 800;
    }

    .modal-no-side-section {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .grafica-section {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
        background: radial-gradient(circle at 8% 0%, rgba(219, 234, 254, 0.52), transparent 34%), #ffffff;
    }

    .grafica-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 20px;
    }

    .grafica-title {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #0f172a;
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    .grafica-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        padding: 8px 11px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .grafica-status.is-empty {
        border-color: #cbd5e1;
        color: #475569;
        background: #f8fafc;
    }

    .grafica-status.is-complete {
        border-color: #a7f3d0;
        color: #047857;
        background: #ecfdf5;
    }

    .grafica-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        box-shadow: 0 0 0 3px currentColor;
        opacity: 0.18;
    }

    .grafica-avance-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.12fr) minmax(280px, 0.88fr);
        gap: 16px;
        align-items: stretch;
    }

    .grafica-avance-panel,
    .grafica-avance-resumen {
        border: 1px solid #dbe3ef;
        border-radius: 18px;
    }

    .grafica-avance-panel {
        min-width: 0;
        padding: 20px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fbff 100%);
    }

    .grafica-panel-heading,
    .grafica-resumen-heading,
    .grafica-comparacion-top,
    .grafica-comparacion-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .grafica-panel-heading {
        justify-content: flex-end;
    }

    .grafica-eyebrow {
        display: block;
        margin-bottom: 4px;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .grafica-panel-heading h3,
    .grafica-resumen-heading h4 {
        margin: 0;
        color: #0f172a;
        font-size: 17px;
        font-weight: 800;
    }

    .grafica-panel-badge {
        flex-shrink: 0;
        padding: 7px 10px;
        border-radius: 999px;
        color: #1e40af;
        background: #dbeafe;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .grafica-panel-badge {
        border: 1px solid #bfdbfe;
    }

    .grafica-pastel-wrapper {
        width: min(100%, 280px);
        height: 252px;
        margin: 12px auto 0;
        position: relative;
    }

    .grafica-avance-canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .grafica-pastel-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        text-align: center;
    }

    .grafica-pastel-center strong {
        color: #0f172a;
        font-size: 42px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .grafica-pastel-center span {
        margin-top: 8px;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .grafica-comparacion-track {
        width: 100%;
        height: 8px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .grafica-comparacion-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #0f9f8b);
        transition: width 0.5s ease;
    }

    .grafica-avance-resumen {
        min-width: 0;
        padding: 20px;
        background: #f8fafc;
    }

    .grafica-resumen-heading h4 {
        font-size: 16px;
    }

    .grafica-comparacion-list {
        display: grid;
        gap: 14px;
        margin-top: 16px;
        max-height: 284px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .grafica-comparacion-row {
        min-width: 0;
    }

    .grafica-comparacion-empty {
        padding: 14px;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        color: #64748b;
        background: #ffffff;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
        text-align: center;
    }

    .grafica-comparacion-label,
    .grafica-comparacion-top strong {
        overflow: hidden;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .grafica-comparacion-top strong {
        flex-shrink: 0;
        color: #0f172a;
    }

    .grafica-comparacion-track {
        height: 7px;
        margin-top: 7px;
    }

    .grafica-comparacion-meta {
        margin-top: 5px;
        color: #94a3b8;
        font-size: 10px;
        font-weight: 700;
    }

    @media (max-width: 900px) {
        .resumen-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .modal-module-overview {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .modal-review-context {
            flex: 1 1 100%;
            justify-content: flex-start;
            max-width: none;
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

        .modulos-grid,
        .modulo-stats,
        .modulo-side-summary,
        .modal-levels-grid,
        .grafica-avance-layout {
            grid-template-columns: 1fr;
        }

        .linea-group-title,
        .modulo-summary-header,
        .modal-level-header,
        .modal-side-header,
        .modal-level-component-head {
            flex-direction: column;
            align-items: stretch;
        }

        .linea-group-progress {
            min-width: 0;
        }

        .modal-content {
            width: 100%;
            max-height: calc(100vh - 20px);
        }

        .modal-body {
            padding: 18px;
            max-height: calc(100vh - 110px);
        }

        .modal-review-summary {
            flex-direction: column;
            align-items: stretch;
        }

        .modal-review-context {
            justify-content: flex-start;
            max-width: none;
            text-align: left;
        }

        .lineas-grid,
        .acciones,
        .modulo-summary-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .lineas-grid {
            grid-template-columns: 1fr;
        }

        .linea-btn,
        .btn {
            width: 100%;
        }

        .linea-btn {
            flex: 1 1 100%;
        }

        .grafica-avance-panel {
            padding: 14px;
        }

        .grafica-heading {
            flex-direction: column;
        }

        .grafica-status {
            align-self: flex-start;
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

        .resumen-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .historico-title-row {
            align-items: flex-start;
        }

        .historico-page-title {
            font-size: 24px;
        }

        .modulos-grid {
            padding: 14px;
            gap: 14px;
        }

        .resumen-card,
        .modulo-summary-card {
            padding: 16px;
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

        .modal-module-overview {
            grid-template-columns: 1fr;
            padding: 12px;
            gap: 10px;
        }

        .modal-overview-item {
            min-height: 76px;
            padding: 14px 16px;
        }

        .modal-level-card {
            padding: 18px;
            gap: 17px;
        }

        .modal-level-title {
            font-size: 24px;
        }

    }
</style>

<div class="historico-container">
    <div class="historico-page-header mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="historico-header-content">
            <a href="{{ route($analisisDashboardRoute) }}"
               class="historico-back-link responsive-action responsive-action--secondary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <div class="historico-title-row">
                <span class="historico-title-icon" aria-hidden="true">
                    <i class="fas fa-chart-bar"></i>
                </span>
                <h1 class="historico-page-title">
                    {{ $historicoRevisadosTitulo ?? 'Historico de Revisados - Pasteurizadora' }}
                </h1>
            </div>
        </div>
    </div>

    <div class="lineas-section">
        <div class="lineas-title">
            Pasteurizadores
        </div>
        <div class="lineas-grid">
            @foreach($lineas as $linea)
                <a href="{{ $analisisRoute('historico-revisados', ['linea_id' => $linea->id]) }}"
                   class="linea-btn {{ !$mostrarTodas && isset($lineaSeleccionada) && $lineaSeleccionada->id == $linea->id ? 'active' : '' }}">
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    @if(isset($estadisticas['resumen']))
        <div class="resumen-grid">
            <div class="resumen-card resumen-card--total">
                <div class="resumen-icono total">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="resumen-info">
                    <h4>Total análisis</h4>
                    <div class="valor">{{ $estadisticas['resumen']['total_general'] }}</div>
                </div>
            </div>
            <div class="resumen-card resumen-card--revisado">
                <div class="resumen-icono revisado">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="resumen-info">
                    <h4>Revisados</h4>
                    <div class="valor">{{ $estadisticas['resumen']['total_revisado'] }}</div>
                </div>
            </div>
            <div class="resumen-card resumen-card--porcentaje">
                <div class="resumen-icono porcentaje">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="resumen-info">
                    <h4>Progreso general</h4>
                    <div class="valor">{{ $estadisticas['resumen']['porcentaje_general'] }}%</div>
                </div>
            </div>
        </div>
    @endif

    <div class="componentes-table">
        <div class="table-header">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                Revisiones de {{ isset($mostrarTodas) && $mostrarTodas ? 'todos los pasteurizadores' : $pasteurizadorSeleccionadoLabel }}
            </h3>
        </div>

        <div class="modulos-grid">
            @forelse($modulosHistorico as $lineaHistorico)
                @php
                    $lineaTotal = $lineaHistorico['totales']['total'] ?? 0;
                    $lineaRevisado = $lineaHistorico['totales']['revisado'] ?? 0;
                    $lineaPorcentaje = $lineaTotal > 0 ? round(($lineaRevisado / $lineaTotal) * 100) : 0;
                    $lineaColor = $lineaPorcentaje >= 80 ? 'success' : ($lineaPorcentaje >= 50 ? 'info' : ($lineaPorcentaje >= 20 ? 'warning' : 'danger'));
                    $pasteurizadorNumero = preg_replace('/^P[-\s]?/i', '', trim((string) $lineaHistorico['linea_nombre']));
                    $pasteurizadorLabel = preg_match('/^pasteurizador\b/i', $pasteurizadorNumero)
                        ? $pasteurizadorNumero
                        : 'Pasteurizador ' . $pasteurizadorNumero;
                @endphp

                <div class="linea-group-title">
                    <div class="linea-group-main">
                        <span class="linea-group-name">{{ $pasteurizadorLabel }}</span>
                    </div>
                    <div class="linea-group-progress">
                        <div class="progress-container">
                            <span class="progress-label">{{ $lineaPorcentaje }}%</span>
                            <div class="progress-bar bg-{{ $lineaColor }}" style="width: {{ $lineaPorcentaje }}%;"></div>
                        </div>
                    </div>
                </div>

                @foreach($lineaHistorico['modulos'] as $moduloData)
                    <div class="modulo-summary-card">
                        <div class="modulo-summary-header">
                            <div>
                                <h4 class="modulo-summary-title">Modulo {{ $moduloData['numero'] }}</h4>
                            </div>
                            <span class="modulo-summary-badge">{{ count($moduloData['niveles']) }} niveles / {{ count($moduloData['lados']) }} lados</span>
                        </div>

                        <div class="modulo-stats">
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Total</span>
                                <span class="modulo-stat-value">{{ $moduloData['total'] }}</span>
                            </div>
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Revisado</span>
                                <span class="modulo-stat-value">{{ $moduloData['revisado'] }}</span>
                            </div>
                            <div class="modulo-stat">
                                <span class="modulo-stat-label">Avance</span>
                                <span class="modulo-stat-value">{{ $moduloData['porcentaje'] }}%</span>
                            </div>
                        </div>

                        <div class="modulo-side-summary">
                            @foreach($moduloData['lados'] as $ladoModulo)
                                <div class="modulo-side-pill {{ $ladoModulo['key'] === 'PASILLO' ? 'pasillo' : '' }}">
                                    <span class="modulo-side-pill-label">{{ $ladoModulo['label'] }}</span>
                                    <span class="modulo-side-pill-value">{{ $ladoModulo['revisado'] }}/{{ $ladoModulo['total'] }} · {{ $ladoModulo['porcentaje'] }}%</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="progress-container">
                            <span class="progress-label">{{ $moduloData['porcentaje'] }}%</span>
                            <div class="progress-bar bg-{{ $moduloData['color'] }}" style="width: {{ $moduloData['porcentaje'] }}%;"></div>
                        </div>

                        <div class="modulo-summary-footer">
                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick="abrirModalNiveles('modulo-template-{{ $lineaHistorico['linea_id'] }}-{{ $moduloData['numero'] }}', 'Módulo {{ $moduloData['numero'] }}', '{{ $lineaHistorico['linea_nombre'] }}')">
                                <i class="fas fa-layer-group"></i>
                                Ver Detalles
                            </button>
                        </div>
                    </div>

                    <template id="modulo-template-{{ $lineaHistorico['linea_id'] }}-{{ $moduloData['numero'] }}">
                        <div class="modal-module-overview">
                            <div class="modal-overview-item modal-overview-item--module">
                                <div class="modal-overview-top">
                                    <span class="modal-overview-label">Módulo</span>
                                    <span class="modal-overview-icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                                </div>
                                <span class="modal-overview-value">{{ $moduloData['numero'] }}</span>
                            </div>
                            <div class="modal-overview-item modal-overview-item--line">
                                <div class="modal-overview-top">
                                    <span class="modal-overview-label">Pasteurizador</span>
                                    <span class="modal-overview-icon" aria-hidden="true"><i class="fas fa-temperature-half"></i></span>
                                </div>
                                <span class="modal-overview-value">{{ $pasteurizadorNumero }}</span>
                            </div>
                            <div class="modal-overview-item modal-overview-item--reviewed">
                                <div class="modal-overview-top">
                                    <span class="modal-overview-label">Revisado</span>
                                    <span class="modal-overview-icon" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
                                </div>
                                <span class="modal-overview-value">{{ $moduloData['revisado'] }}/{{ $moduloData['total'] }}</span>
                            </div>
                            <div class="modal-overview-item modal-overview-item--progress">
                                <div class="modal-overview-top">
                                    <span class="modal-overview-label">Avance general</span>
                                    <span class="modal-overview-icon" aria-hidden="true"><i class="fas fa-gauge-high"></i></span>
                                </div>
                                <span class="modal-overview-value">{{ $moduloData['porcentaje'] }}%</span>
                            </div>
                        </div>

                        <div class="modal-levels-grid">
                            @foreach($moduloData['niveles'] as $nivelData)
                                <article
                                    class="modal-level-card {{ $nivelData['key'] === 'INFERIOR' ? 'nivel-inferior' : '' }} {{ $loop->first ? 'is-selected' : '' }}"
                                    tabindex="0"
                                    role="button"
                                    aria-label="{{ $nivelData['label'] }}: {{ $nivelData['porcentaje'] }}% de avance"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    onclick="seleccionarNivelModal(this)"
                                    onkeydown="manejarNivelModal(event, this)">
                                    <div class="modal-level-header">
                                        <div>
                                            <h4 class="modal-level-title">{{ $nivelData['label'] }}</h4>
                                        </div>
                                        <span class="modal-level-badge">{{ $nivelData['porcentaje'] }}%</span>
                                    </div>

                                    <div>
                                        <div class="modal-level-progress-meta">
                                            <span>Revisados</span>
                                            <span>{{ $nivelData['revisado'] }}/{{ $nivelData['total'] }}</span>
                                        </div>
                                        <div class="progress-container">
                                            <span class="progress-label">{{ $nivelData['porcentaje'] }}%</span>
                                            <div class="progress-bar bg-{{ $nivelData['color'] }}" style="width: {{ $nivelData['porcentaje'] }}%;"></div>
                                        </div>
                                    </div>

                                    <div class="modal-level-sides">
                                        @if(!empty($nivelData['componentes_sin_lado']))
                                            <div class="modal-no-side-section">
                                                @foreach($nivelData['componentes_sin_lado'] as $componenteData)
                                                    @include('historico-revisados.pasteurizadora.partials.revision-card', [
                                                        'componenteData' => $componenteData,
                                                        'moduloData' => $moduloData,
                                                        'nivelData' => $nivelData,
                                                        'ladoData' => null,
                                                    ])
                                                @endforeach
                                            </div>
                                        @endif

                                        @foreach($nivelData['lados'] as $ladoData)
                                            <div class="modal-side-block">
                                                <div class="modal-side-header">
                                                    <div>
                                                        <div class="modal-side-title">{{ $ladoData['label'] }}</div>
                                                        <div class="modal-side-meta">Avance {{ $ladoData['revisado'] }}/{{ $ladoData['total'] }}</div>
                                                    </div>
                                                    <span class="modal-level-badge">{{ $ladoData['porcentaje'] }}%</span>
                                                </div>

                                                <div class="progress-container" style="height: 18px;">
                                                    <span class="progress-label">{{ $ladoData['porcentaje'] }}%</span>
                                                    <div class="progress-bar bg-{{ $ladoData['color'] }}" style="width: {{ $ladoData['porcentaje'] }}%;"></div>
                                                </div>

                                                <div class="modal-side-components">
                                                    @foreach($ladoData['componentes'] as $componenteData)
                                                        @include('historico-revisados.pasteurizadora.partials.revision-card', [
                                                            'componenteData' => $componenteData,
                                                            'moduloData' => $moduloData,
                                                            'nivelData' => $nivelData,
                                                            'ladoData' => $ladoData,
                                                        ])
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="acciones" style="margin-top: 22px; justify-content: flex-start;">
                            <a
                                href="{{ $analisisRoute('index', ['linea_id' => $lineaHistorico['linea_id'], 'modulo' => $moduloData['numero']]) }}"
                                class="btn btn-primary">
                                <i class="fas fa-chart-pie"></i>
                                Ver analisis del modulo
                            </a>
                        </div>
                    </template>
                @endforeach
            @empty
                <div class="empty-state">
                    <i class="fas fa-info-circle text-3xl mb-2"></i>
                    <p>No hay datos disponibles para este pasteurizador.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($modulosHistorico->isNotEmpty())
        @php
            $chartData = $mostrarTodas
                ? $modulosHistorico->map(function ($lineaHistorico) {
                    $total = (int) ($lineaHistorico['totales']['total'] ?? 0);
                    $revisado = (int) ($lineaHistorico['totales']['revisado'] ?? 0);

                    return [
                        'label' => (function () use ($lineaHistorico) {
                            $numero = preg_replace('/^P[-\s]?/i', '', trim((string) $lineaHistorico['linea_nombre']));

                            return preg_match('/^pasteurizador\b/i', $numero) ? $numero : 'Pasteurizador ' . $numero;
                        })(),
                        'total' => $total,
                        'revisado' => $revisado,
                        'porcentaje' => $total > 0 ? round(($revisado / $total) * 100) : 0,
                    ];
                })->values()
                : $modulosHistorico->flatMap(fn ($lineaHistorico) => collect($lineaHistorico['modulos'] ?? [])->map(fn ($modulo) => [
                    'label' => 'Modulo ' . $modulo['numero'],
                    'total' => (int) ($modulo['total'] ?? 0),
                    'revisado' => (int) ($modulo['revisado'] ?? 0),
                    'porcentaje' => (int) ($modulo['porcentaje'] ?? 0),
                ]))->values();

            $chartTotal = $chartData->sum('total');
            $chartRevisado = $chartData->sum('revisado');
            $chartPendiente = max($chartTotal - $chartRevisado, 0);
            $chartPorcentaje = $chartTotal > 0 ? round(($chartRevisado / $chartTotal) * 100) : 0;
            $chartEstado = $chartPorcentaje >= 100
                ? 'Completado'
                : ($chartRevisado > 0 ? 'En progreso' : 'Sin revisiones');
            $chartEstadoClass = $chartPorcentaje >= 100
                ? 'is-complete'
                : ($chartRevisado > 0 ? 'is-progress' : 'is-empty');
            $chartMetaLabel = $mostrarTodas ? 'pasteurizadores' : 'módulos';
            $chartPieData = [
                'revisado' => $chartRevisado,
                'pendiente' => $chartPendiente,
                'total' => $chartTotal,
                'porcentaje' => $chartPorcentaje,
            ];
        @endphp

        <div class="grafica-section">
            <div class="grafica-heading">
                <div>
                    <div class="grafica-title">
                        <i class="fas fa-chart-pie text-blue-600"></i>
                        Avance de revisiones
                    </div>
                </div>
                <span class="grafica-status {{ $chartEstadoClass }}">
                    <span class="grafica-status-dot" aria-hidden="true"></span>
                    {{ $chartEstado }}
                </span>
            </div>

            <div class="grafica-avance-layout">
                <section class="grafica-avance-panel">
                    <div class="grafica-panel-heading">
                        <span class="grafica-panel-badge">{{ $chartData->count() }} {{ $chartMetaLabel }}</span>
                    </div>

                    <div class="grafica-pastel-wrapper">
                        <canvas
                            id="grafica-avance-historico"
                            class="grafica-avance-canvas"
                            role="img"
                            aria-label="Avance general: {{ $chartPorcentaje }} por ciento, {{ $chartRevisado }} revisados de {{ $chartTotal }}"
                            data-grafica-avance='@json($chartPieData)'></canvas>
                        <div class="grafica-pastel-center">
                            <strong>{{ $chartPorcentaje }}%</strong>
                            <span>avance general</span>
                        </div>
                    </div>
                </section>

                <aside class="grafica-avance-resumen" aria-labelledby="grafica-comparacion-titulo">
                    <div class="grafica-resumen-heading">
                        <div>
                            <h4 id="grafica-comparacion-titulo">Comparación {{ $chartMetaLabel }}</h4>
                        </div>
                    </div>
                    <div class="grafica-comparacion-list">
                        @forelse($chartData as $chartItem)
                            @php
                                $itemPorcentaje = max(0, min((int) ($chartItem['porcentaje'] ?? 0), 100));
                            @endphp
                            <div class="grafica-comparacion-row">
                                <div class="grafica-comparacion-top">
                                    <span class="grafica-comparacion-label">{{ $chartItem['label'] }}</span>
                                    <strong>{{ $chartItem['porcentaje'] }}%</strong>
                                </div>
                                <div class="grafica-comparacion-track" role="progressbar" aria-valuenow="{{ $itemPorcentaje }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $chartItem['label'] }}">
                                    <div class="grafica-comparacion-fill" style="width: {{ $itemPorcentaje }}%;"></div>
                                </div>
                                <div class="grafica-comparacion-meta">
                                    <span>{{ $chartItem['revisado'] }} revisados</span>
                                    <span>{{ $chartItem['total'] }} total</span>
                                </div>
                            </div>
                        @empty
                            <div class="grafica-comparacion-empty">
                                No hay módulos disponibles para comparar en este histórico.
                            </div>
                        @endforelse
                    </div>
                </aside>
            </div>
        </div>
    @endif

    <div class="acciones">
        @if(!$mostrarTodas && isset($lineaSeleccionada))
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

<div id="nivelesModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitulo" aria-describedby="modalSubtitulo" aria-hidden="true" tabindex="-1">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-copy">
                <h3>
                    <i class="fas fa-layer-group text-blue-600"></i>
                    <span id="modalTitulo">Módulo</span>
                </h3>
                <span class="modal-subtitle" id="modalSubtitulo">Pasteurizador</span>
            </div>
            <button type="button" onclick="cerrarModalNiveles()" class="modal-close" aria-label="Cerrar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalNivelesBody"></div>
    </div>
</div>

<script>
let modalTriggerElement = null;
let previousBodyOverflow = '';

function abrirModalNiveles(templateId, titulo, linea) {
    const modal = document.getElementById('nivelesModal');
    const body = document.getElementById('modalNivelesBody');
    const template = document.getElementById(templateId);

    if (!template) {
        return;
    }

    modalTriggerElement = document.activeElement;
    previousBodyOverflow = document.body.style.overflow;
    document.getElementById('modalTitulo').textContent = titulo;
    const lineaNormalizada = String(linea).trim().replace(/^P[-\s]?/i, '');
    const pasteurizadorLabel = /^pasteurizador\b/i.test(lineaNormalizada)
        ? lineaNormalizada
        : 'Pasteurizador ' + lineaNormalizada;
    document.getElementById('modalSubtitulo').textContent = pasteurizadorLabel;
    body.innerHTML = '';
    body.appendChild(template.content.cloneNode(true));

    const firstCard = body.querySelector('.modal-level-card');
    if (firstCard) {
        seleccionarNivelModal(firstCard);
    }

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    window.requestAnimationFrame(() => {
        modal.querySelector('.modal-close')?.focus();
    });
}

function cerrarModalNiveles() {
    const modal = document.getElementById('nivelesModal');

    if (!modal.classList.contains('show')) {
        return;
    }

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = previousBodyOverflow;

    if (modalTriggerElement && typeof modalTriggerElement.focus === 'function') {
        modalTriggerElement.focus();
    }

    modalTriggerElement = null;
}

function seleccionarNivelModal(card) {
    const modalBody = card.closest('.modal-body') || document;
    modalBody.querySelectorAll('.modal-level-card').forEach((item) => {
        item.classList.remove('is-selected');
        item.setAttribute('aria-pressed', 'false');
    });

    card.classList.add('is-selected');
    card.setAttribute('aria-pressed', 'true');
}

function manejarNivelModal(event, card) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        seleccionarNivelModal(card);
    }
}

function inicializarGraficaAvance() {
    if (typeof Chart === 'undefined') {
        return;
    }

    const canvas = document.getElementById('grafica-avance-historico');
    if (!canvas) {
        return;
    }

    const rawData = canvas.getAttribute('data-grafica-avance');
    const summary = rawData ? JSON.parse(rawData) : { revisado: 0, pendiente: 0, total: 0, porcentaje: 0 };
    const hasData = Number(summary.total) > 0;
    const labels = hasData ? ['Revisados', 'Pendientes'] : ['Sin datos'];
    const values = hasData ? [Number(summary.revisado), Number(summary.pendiente)] : [1];
    const colors = hasData ? ['#0f9f8b', '#dbe3ec'] : ['#dbe3ec'];

    new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderColor: '#ffffff',
                borderWidth: 5,
                borderRadius: 8,
                spacing: 3,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                ? false
                : { duration: 700, easing: 'easeOutCubic' },
            interaction: {
                intersect: false,
                mode: 'nearest',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            if (!hasData) {
                                return ' Sin datos registrados';
                            }

                            const value = Number(context.raw || 0);
                            const percentage = Number(summary.total) > 0 ? Math.round((value / Number(summary.total)) * 100) : 0;
                            return ' ' + context.label + ': ' + value + ' (' + percentage + '%)';
                        },
                    },
                },
            },
        },
    });
}

document.getElementById('nivelesModal').addEventListener('click', function (event) {
    if (event.target === this) {
        cerrarModalNiveles();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && document.getElementById('nivelesModal').classList.contains('show')) {
        cerrarModalNiveles();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    inicializarGraficaAvance();
});
</script>
@endsection
