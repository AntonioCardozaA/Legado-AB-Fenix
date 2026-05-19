@extends('layouts.app')

@section('title', 'Análisis de Pasteurizadoras')

@section('content')
<style>
    /* VARIABLES CSS PARA CONSISTENCIA */
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --changed-blue: #3b82f6;
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
    }

    .sticky-top { position: sticky; top: 0; z-index: 30; }
    .sticky-left { position: sticky; left: 0; z-index: 20; }
    .sticky-top-left { position: sticky; top: 0; left: 0; z-index: 40; }
    .cell-ok { background-color: #f0f9ff; border-left: 4px solid var(--success-green); }
    .cell-warning { background-color: #fffbeb; border-left: 4px solid var(--warning-yellow); }
    .cell-danger { background-color: #fef2f2; border-left: 4px solid var(--danger-red); }
    .cell-changed { background-color: #eff6ff; border-left: 4px solid var(--changed-blue); }
    .cell-empty { background-color: var(--light-gray); }
    .cell-header { background-color: #eff6ff; }

    .compact-table td, .compact-table th {
        padding: 8px !important;
        font-size: 0.75rem !important;
        min-width: 120px;
    }

    .table-container {
        max-height: 600px;
        overflow-y: auto;
    }

    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
        max-height: 60vh;
        overflow-y: auto;
        padding: 10px;
    }

    .image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid var(--medium-gray);
        transition: all 0.3s ease;
        background: white;
    }

    .image-item:hover {
        border-color: var(--primary-blue);
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .grid-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .grid-image:hover {
        transform: scale(1.05);
    }

    .image-info {
        padding: 8px;
        background: linear-gradient(to bottom, rgba(255,255,255,0.9), white);
        border-top: 1px solid #f3f4f6;
    }

    .image-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 12px;
        z-index: 10;
    }

    .download-image-btn {
        width: 100%;
        padding: 6px;
        margin-top: 8px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.3s ease;
    }

    .download-image-btn:hover {
        background: #2563eb;
    }

    .download-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: var(--success-green);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.3s ease;
    }

    .download-all-btn:hover {
        background: #059669;
    }

    .empty-images {
        text-align: center;
        padding: 40px;
        color: var(--dark-gray);
    }

    .analysis-cell {
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        min-height: 120px;
    }

    .analysis-cell.no-data {
        cursor: default;
    }

    .analysis-cell.no-data:hover {
        background-color: inherit !important;
        transform: none;
    }

    .click-indicator {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 10px;
        color: var(--primary-blue);
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .analysis-cell:not(.no-data):hover .click-indicator {
        opacity: 1;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .detail-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--medium-gray);
    }

    .detail-card h4 {
        font-size: 14px;
        font-weight: 600;
        color: var(--dark-gray);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-card p {
        font-size: 15px;
        color: #374151;
        line-height: 1.5;
    }

    .activity-content {
        background: #f9fafb;
        border-radius: 8px;
        padding: 15px;
        margin-top: 5px;
        border-left: 4px solid var(--primary-blue);
    }

    .activity-content p {
        white-space: pre-wrap;
        word-break: break-word;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 14px;
    }

    .status-badge.ok {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-badge.warning {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-badge.danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-badge.changed {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .lado-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 999px;
        font-weight: 700;
        font-size: 12px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    .lado-badge.vapor {
        background-color: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .lado-badge.pasillo {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .detail-images-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--medium-gray);
        margin-top: 20px;
    }

    .detail-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
    }

    .empty-cell {
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--dark-gray);
        padding: 10px;
    }

    .no-records {
        text-align: center;
        padding: 20px;
        color: var(--dark-gray);
    }

    .empty-cell-icon {
        font-size: 24px;
        margin-bottom: 8px;
        color: #d1d5db;
    }

    .component-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }

    .component-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .component-code {
        font-size: 9px;
        color: var(--dark-gray);
        background: #f3f4f6;
        padding: 2px 4px;
        border-radius: 3px;
    }

    .table-wrapper {
        position: relative;
        overflow: auto;
        border: 1px solid var(--medium-gray);
        border-radius: 8px;
    }

    .table-corner {
        background: #eff6ff;
        border-right: 1px solid #dbeafe;
        border-bottom: 1px solid #dbeafe;
    }

    .scroll-indicator {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        z-index: 50;
        display: none;
    }

    .table-wrapper:hover .scroll-indicator {
        display: block;
    }

    .cell-highlight {
        animation: highlight-pulse 2s ease-out;
    }

    @keyframes highlight-pulse {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    .badge-new {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #ef4444;
        color: white;
        font-size: 8px;
        padding: 2px 6px;
        border-radius: 10px;
        z-index: 5;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
    }

    /* ESTILOS DE FILTROS - ESTILO IMAGEN */
    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lineas-title i {
        color: #3b82f6;
        font-size: 16px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }

    .linea-item {
        display: inline-flex;
        align-items: center;
        padding: 8px 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .linea-item i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-item:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }

    .linea-item.active {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }

    .linea-item.active i {
        color: white;
    }

    .filters-divider {
        margin: 24px 0 16px 0;
        border-top: 2px solid #f1f5f9;
    }

    .filters-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
    }

    .filter-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .filter-link i {
        color: #64748b;
        font-size: 14px;
    }

    .filter-link:hover {
        background: #f8fafc;
        color: #2563eb;
    }

    .filter-link:hover i {
        color: #2563eb;
    }

    .filter-link.active {
        color: #2563eb;
        font-weight: 600;
    }

    .btn-apply {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 28px;
        background: #2563eb;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: auto;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }

    .btn-apply:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 6px 10px -1px rgba(37, 99, 235, 0.3);
    }

    .btn-clear {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: white;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-clear:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #475569;
    }

    .advanced-filters-panel {
        margin-top: 20px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        display: none;
        border: 1px solid #e2e8f0;
    }

    .advanced-filters-panel.show {
        display: block;
    }

    .advanced-filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-select, .filter-input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        color: #1e293b;
        background: white;
        transition: all 0.2s ease;
    }

    .filter-select:focus, .filter-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 83, 192, 0.1);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        position: relative;
        overflow: hidden;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 12px 24px -18px rgba(15, 23, 42, 0.35);
        border: 1px solid #e2e8f0;
        border-top-width: 4px;
        transition: all 0.25s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 30px -18px rgba(15, 23, 42, 0.35);
        border-color: #cbd5e1;
    }

    .stat-card.total { border-top-color: #475569; }
    .stat-card.good { border-top-color: #059669; }
    .stat-card.warning { border-top-color: #d97706; }
    .stat-card.danger { border-top-color: #dc2626; }
    .stat-card.changed { border-top-color: #0284c7; }

    .stat-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #64748b;
    }

    .stat-value {
        font-size: 30px;
        font-weight: 800;
        line-height: 1;
        margin-top: 10px;
        color: #0f172a;
    }

    .stat-trend {
        margin-top: 8px;
        font-size: 12px;
        color: #64748b;
    }

    .stat-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .table-header-container {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
        border-radius: 12px 12px 0 0;
    }

    .page-hero {
        position: relative;
        overflow: hidden;
        margin-bottom: 24px;
        padding: 24px;
        border-radius: 24px;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #2563eb 100%);
        color: white;
        box-shadow: 0 22px 45px -28px rgba(37, 99, 235, 0.55);
    }

    .page-hero::before {
        content: '';
        position: absolute;
        inset: auto -10% -45% auto;
        width: 240px;
        height: 240px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(2px);
    }

    .page-hero > div {
        position: relative;
        z-index: 10;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .hero-kicker {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        color: rgba(226, 232, 240, 0.82);
    }

    .hero-title {
        margin-top: 8px;
        font-size: clamp(1.9rem, 2.8vw, 2.6rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .hero-subtitle {
        margin-top: 10px;
        max-width: 42rem;
        color: rgba(226, 232, 240, 0.9);
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .hero-back-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: white;
        font-weight: 600;
        transition: all 0.25s ease;
        backdrop-filter: blur(12px);
    }

    .hero-back-link:hover {
        background: rgba(255, 255, 255, 0.18);
        transform: translateY(-1px);
    }

    .page-hero > div > a {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: fit-content;
        padding: 10px 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: white;
        font-weight: 600;
        transition: all 0.25s ease;
        backdrop-filter: blur(12px);
    }

    .page-hero > div > a:hover {
        background: rgba(255, 255, 255, 0.18);
        transform: translateY(-1px);
    }

    .page-hero > div > a svg,
    .page-hero > div > a span {
        color: white !important;
    }

    .page-hero > div > h1 {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        color: white !important;
        font-size: clamp(1.9rem, 2.8vw, 2.6rem) !important;
        font-weight: 800 !important;
        line-height: 1.05;
    }

    .page-hero > div > h1 svg {
        width: 48px;
        height: 48px;
        padding: 10px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: #dbeafe !important;
        flex-shrink: 0;
    }

    .lavadora-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 26px 40px -34px rgba(15, 23, 42, 0.55);
        margin-bottom: 30px;
        border: 1px solid #dbe4f0;
    }

    .lavadora-card-header {
        background: linear-gradient(135deg, #0f172a 0%, #172554 55%, #1d4ed8 100%);
        color: white;
        padding: 22px 24px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }

    .lavadora-card-header h3 {
        font-size: 22px;
        font-weight: 800;
        margin: 0;
    }

    .lavadora-card-header .badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.14);
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .linea-meta {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .linea-icon-shell {
        width: 52px;
        height: 52px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.16);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .module-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        width: 100%;
        padding: 18px 20px 0;
    }

    .module-overview-card {
        border-radius: 18px;
        padding: 14px 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        box-shadow: 0 18px 24px -24px rgba(15, 23, 42, 0.65);
    }

    .module-overview-card .label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: #64748b;
        margin-bottom: 6px;
    }

    .module-overview-card .value {
        display: block;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1;
        color: #0f172a;
    }

    .module-overview-card .caption {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #64748b;
    }

    .table-wrapper {
        position: relative;
        overflow: auto;
        border: 0;
        border-radius: 0 0 24px 24px;
        background: linear-gradient(180deg, #e2e8f0 0%, #f8fafc 100%);
        padding: 1px;
    }

    .table-wrapper > table {
        background: white;
        border-radius: 0 0 23px 23px;
    }

    .table-shell-note {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 20px 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.04em;
    }

    .module-cell {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: inset -1px 0 0 #e2e8f0;
    }

    .module-cell.completed {
        background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
    }

    .cell-action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.03em;
        transition: all 0.2s ease;
    }

    .cell-action-button.primary {
        background: #2563eb;
        color: white;
        box-shadow: 0 10px 18px -14px rgba(37, 99, 235, 0.85);
    }

    .cell-action-button.primary:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    .cell-action-button.secondary {
        background: #e0f2fe;
        color: #0369a1;
    }

    .cell-action-button.secondary:hover {
        background: #bae6fd;
    }

    .empty-state-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #2563eb;
        color: white;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.03em;
        box-shadow: 0 10px 18px -14px rgba(37, 99, 235, 0.85);
        transition: all 0.2s ease;
    }

    .empty-state-button:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 999px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    #estadoModalContent > .space-y-6 > div {
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 18px 30px -28px rgba(15, 23, 42, 0.4);
    }

    #estadoModalContent .p-4.space-y-3 > div {
        border-radius: 22px !important;
        box-shadow: 0 12px 22px -22px rgba(15, 23, 42, 0.45);
    }

    #detailModalContent > .grid + .bg-gray-50,
    #detailModalContent > .bg-gray-50 {
        border-radius: 20px;
    }

    @media (max-width: 768px) {
        .lineas-grid {
            gap: 8px;
        }

        .linea-item {
            padding: 6px 16px;
            font-size: 13px;
        }

        .filters-row {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-apply {
            margin-left: 0;
            justify-content: center;
        }

        .compact-table td, .compact-table th {
            min-width: 100px;
            font-size: 0.7rem !important;
            padding: 6px !important;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .page-hero {
            padding: 20px;
            border-radius: 20px;
        }

        .lavadora-card-header {
            padding: 18px;
        }

        .module-overview {
            grid-template-columns: 1fr;
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .animate-modalIn {
        animation: modalIn 0.3s ease-out;
    }

    .image-grid-enhanced {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .image-grid-enhanced .image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
        background: white;
    }

    .image-grid-enhanced .image-item:hover {
        border-color: #4b5563;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
    }

    .image-grid-enhanced .grid-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .image-grid-enhanced .grid-image:hover {
        transform: scale(1.05);
    }

    .image-grid-enhanced .image-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(31, 41, 55, 0.9);
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 4px;
        z-index: 10;
        border: 1px solid #6b7280;
        font-family: monospace;
    }

    .image-grid-enhanced .image-info {
        padding: 8px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }

    .image-grid-enhanced .download-image-btn {
        width: 100%;
        padding: 6px;
        background: #374151;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-family: monospace;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .image-grid-enhanced .download-image-btn:hover {
        background: #1f2937;
    }

    @media (max-width: 768px) {
        .image-grid-enhanced {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .image-grid-enhanced .grid-image {
            height: 120px;
        }
    }

    @media (max-width: 480px) {
        .image-grid-enhanced {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="max-w-full mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="page-hero">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}"
               class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300
                      group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Análisis de Pasteurizadoras
            </h1>
        </div>
    </div>

    {{-- FILTROS --}}
    @php
        $lineasFiltradas = $lineasFiltradas ?? collect();
        $mostrarTodas = $mostrarTodas ?? true;
        $analisisCollection = isset($analisis) ? collect($analisis) : collect([]);
        $seguimientoPasteurizadora = $seguimientoPasteurizadora ?? [];
    @endphp

    @if(isset($lineasFiltradas) && $lineasFiltradas->count() > 0)
    <div class="filters-section">
        <div class="lineas-title">
            <i class="fas fa-chart-line"></i>
            LÍNEAS DE PASTEURIZADORA:
        </div>

        <div class="lineas-grid">
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => 'todas']) }}"
               class="linea-item {{ $mostrarTodas ? 'active' : '' }}">
                <i class="fas fa-globe"></i>
                Todas
            </a>
            @foreach($lineasFiltradas as $l)
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $l->id]) }}"
                   class="linea-item {{ (!$mostrarTodas && request('linea_id') == $l->id) ? 'active' : '' }}">
                    {{ $l->nombre }}
                </a>
            @endforeach
        </div>

        <div class="filters-divider"></div>

        <form method="GET" action="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}" id="filterForm">
            <input type="hidden" name="linea_id" value="{{ request('linea_id', 'todas') }}">

            <div class="filters-row">
                <div class="filter-link {{ request('modulo') || request('estado') ? 'active' : '' }}"
                     onclick="toggleAdvancedFilters()">
                    <i class="fas fa-sliders-h"></i>
                    Filtros avanzados
                    <i id="advancedFiltersIcon" class="fas fa-chevron-down ml-1"></i>
                </div>

                <button type="submit" class="btn-apply">
                    <i class="fas fa-search"></i>
                    Aplicar filtros
                </button>

                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => request('linea_id', 'todas')]) }}" class="btn-clear">
                    <i class="fas fa-times"></i>
                    Limpiar
                </a>
            </div>

            <div id="advancedFiltersPanel" class="advanced-filters-panel {{ request('modulo') || request('estado') ? 'show' : '' }}">
                <div class="advanced-filters-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-cog mr-1"></i> Módulo</label>
                        <select name="modulo" class="filter-select">
                            <option value="">Todos los módulos</option>
                            @for($i = 1; $i <= 16; $i++)
                                <option value="{{ $i }}" {{ request('modulo') == $i ? 'selected' : '' }}>Módulo {{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-clipboard-check mr-1"></i> Estado</label>
                        <select name="estado" class="filter-select">
                            <option value="">Todos los estados</option>
                            @php
                                $estados = ['Buen estado', 'Desgaste moderado', 'Desgaste severo', 'Dañado - Requiere cambio', 'Cambiado'];
                            @endphp
                            @foreach($estados as $estado)
                                <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- ESTADÍSTICAS --}}
    @if($analisisCollection->count() > 0)
        @php
            $registrosPorEstado = [
                'total' => $analisisCollection,
                'buen_estado' => $analisisCollection->where('estado', 'Buen estado'),
                'desgaste' => $analisisCollection->whereIn('estado', ['Desgaste moderado', 'Desgaste severo']),
                'danado' => $analisisCollection->where('estado', 'Dañado - Requiere cambio'),
                'cambiado' => $analisisCollection->where('estado', 'Cambiado'),
            ];

            $estadisticas = [
                'total' => $analisisCollection->count(),
                'buen_estado' => $registrosPorEstado['buen_estado']->count(),
                'desgaste' => $registrosPorEstado['desgaste']->count(),
                'danado' => $registrosPorEstado['danado']->count(),
                'cambiado' => $registrosPorEstado['cambiado']->count(),
            ];
        @endphp

        <div class="stats-grid">
            {{-- TOTAL ANÁLISIS --}}
            <div onclick="openEstadoModal('total', {{ json_encode($registrosPorEstado['total']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="stat-card total cursor-pointer">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total análisis</p>
                        <div class="stat-value text-slate-700">{{ $estadisticas['total'] }}</div>
                        <p class="stat-trend">registros historicos disponibles</p>
                    </div>
                    <span class="stat-icon-wrap bg-slate-100 text-slate-600"><i class="fas fa-chart-line"></i></span>
                </div>
            </div>

            {{-- BUEN ESTADO --}}
            <div onclick="openEstadoModal('buen_estado', {{ json_encode($registrosPorEstado['buen_estado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="stat-card good cursor-pointer">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Buen estado</p>
                        <div class="stat-value text-emerald-700">{{ $estadisticas['buen_estado'] }}</div>
                        <p class="stat-trend text-emerald-600">componentes en condicion estable</p>
                    </div>
                    <span class="stat-icon-wrap bg-emerald-100 text-emerald-600"><i class="fas fa-check-circle"></i></span>
                </div>
            </div>

            {{-- DESGASTE --}}
            <div onclick="openEstadoModal('desgaste', {{ json_encode($registrosPorEstado['desgaste']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="stat-card warning cursor-pointer">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide">Desgaste</p>
                        <div class="stat-value text-amber-700">{{ $estadisticas['desgaste'] }}</div>
                        <p class="stat-trend text-amber-600">requieren seguimiento cercano</p>
                    </div>
                    <span class="stat-icon-wrap bg-amber-100 text-amber-600"><i class="fas fa-exclamation-triangle"></i></span>
                </div>
            </div>

            {{-- DAÑADO --}}
            <div onclick="openEstadoModal('danado', {{ json_encode($registrosPorEstado['danado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="stat-card danger cursor-pointer">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-red-600 uppercase tracking-wide">Dañado</p>
                        <div class="stat-value text-red-700">{{ $estadisticas['danado'] }}</div>
                        <p class="stat-trend text-red-600">requieren cambio o intervencion</p>
                    </div>
                    <span class="stat-icon-wrap bg-red-100 text-red-600"><i class="fas fa-times-circle"></i></span>
                </div>
            </div>

            {{-- CAMBIADO --}}
            <div onclick="openEstadoModal('cambiado', {{ json_encode($registrosPorEstado['cambiado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="stat-card changed cursor-pointer">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-sky-600 uppercase tracking-wide">Cambiado</p>
                        <div class="stat-value text-sky-700">{{ $estadisticas['cambiado'] }}</div>
                        <p class="stat-trend text-sky-600">componentes reemplazados</p>
                    </div>
                    <span class="stat-icon-wrap bg-sky-100 text-sky-600"><i class="fas fa-sync-alt"></i></span>
                </div>
            </div>
        </div>
    @endif

    {{-- SECCIÓN PRINCIPAL - TABLA DE ANÁLISIS --}}
    <div class="space-y-6">
        @php
            $lineasToShow = $mostrarTodas ? $lineasFiltradas : collect([$lineaSeleccionada ?? null])->filter();
        @endphp

        @foreach($lineasToShow as $linea)
            @php
                if(!$linea) continue;

                $nombreLinea = $linea->nombre;
                $componentesLinea = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($nombreLinea);
                $totalModulos = \App\Models\AnalisisPasteurizadora::getModulosPorLinea($nombreLinea);
                $modulosLinea = collect(range(1, $totalModulos));

                $analisisLinea = $analisisCollection->filter(fn($item) => $item->linea_id == $linea->id);
                $seguimientoLinea = $seguimientoPasteurizadora[$linea->id] ?? [];
                $resumenLinea = $seguimientoLinea['resumen'] ?? [
                    'total' => 0,
                    'completados' => 0,
                    'pendientes' => 0,
                    'porcentaje' => 0,
                    'completado' => false,
                ];

                $analisisAgrupadosLinea = [];
                foreach ($analisisLinea as $item) {
                    if (!isset($analisisAgrupadosLinea[$item->modulo][$item->componente])) {
                        $analisisAgrupadosLinea[$item->modulo][$item->componente] = collect();
                    }
                    $analisisAgrupadosLinea[$item->modulo][$item->componente]->push($item);
                }
            @endphp

            @if(count($componentesLinea) > 0 && $modulosLinea->count() > 0)
                <div class="lavadora-card">
                    <div class="lavadora-card-header">
                        <div class="linea-icon-shell">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div>
                            <h3>{{ $linea->nombre }}</h3>
                            <div class="badge">{{ $totalModulos }} módulos | {{ count($componentesLinea) }} componentes</div>
                        </div>
                    </div>

                    <div class="module-overview">
                        <div class="module-overview-card">
                            <span class="label">Progreso</span>
                            <span class="value">{{ $resumenLinea['porcentaje'] ?? 0 }}%</span>
                            <span class="caption">{{ ($resumenLinea['completado'] ?? false) ? 'ciclo completo' : 'avance actual' }}</span>
                        </div>
                        <div class="module-overview-card">
                            <span class="label">Completados</span>
                            <span class="value">{{ $resumenLinea['completados'] ?? 0 }}</span>
                            <span class="caption">modulos terminados</span>
                        </div>
                        <div class="module-overview-card">
                            <span class="label">Pendientes</span>
                            <span class="value">{{ $resumenLinea['pendientes'] ?? 0 }}</span>
                            <span class="caption">elementos por revisar</span>
                        </div>
                    </div>

                    <div class="table-shell-note">
                        <i class="fas fa-arrows-alt-h"></i>
                        Desplazate horizontalmente para revisar todos los componentes.
                    </div>
                    <div class="table-wrapper">
                        <div class="scroll-indicator">
                            <i class="fas fa-arrows-alt-h mr-1"></i> Desplázate para ver más
                        </div>
                        <table class="w-full compact-table border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="sticky-left cell-header text-blue-900 font-bold px-4 py-3 border text-center whitespace-nowrap text-sm">
                                        Módulo
                                    </th>
                                    @foreach($componentesLinea as $codigo => $compData)
                                        <th class="cell-header text-blue-900 font-bold px-4 py-3 border text-center whitespace-nowrap text-sm">
                                            <div class="component-header">
                                                <div class="component-name">{{ $compData['nombre'] }}</div>
                                                <img
                                                    src="{{ asset('images/componentes-pasteurizadora/' . $codigo . '.png') }}"
                                                    alt="Icono {{ $compData['nombre'] }}"
                                                    class="w-20 h-20 object-contain hover:scale-110 transition-transform"
                                                    onerror="this.src='{{ asset('images/icono-pasteurizadora.png') }}'">
                                            </div>
                                        </th>
                                    @endforeach
                            </thead>
                            <tbody>
                                @foreach($modulosLinea as $moduloNumero)
                                    @php
                                        $moduloCompletado = (bool) ($seguimientoLinea['modulos'][$moduloNumero]['completado'] ?? false);
                                    @endphp
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="sticky-left module-cell {{ $moduloCompletado ? 'completed' : '' }} px-4 py-3 font-medium text-gray-900 border-r border-gray-200">
                                            <div>Módulo {{ $moduloNumero }}</div>
                                            @if($moduloCompletado)
                                                <span class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[11px] font-semibold">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Terminado
                                                </span>
                                            @endif
                                        </td>
                                        @foreach($componentesLinea as $codigo => $compData)
                                            @php
                                                $celdaSeguimiento = $seguimientoLinea['celdas'][$moduloNumero][$codigo] ?? null;
                                                $registros = collect($celdaSeguimiento['registros_visibles'] ?? []);
                                                $registro = $registros->sortByDesc(function ($item) {
                                                    return ($item->created_at?->timestamp ?? 0) . '-' . str_pad((string) $item->id, 10, '0', STR_PAD_LEFT);
                                                })->first();
                                                $hasData = $registro !== null;
                                                $totalComponentesComponente = $compData['cantidad'] ?? 0;
                                                $esBrazoTorsion = \App\Models\AnalisisPasteurizadora::esBrazoTorsion($codigo);
                                                $brazoAplicaModulo = !$esBrazoTorsion || $moduloNumero <= \App\Models\AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre);

                                                $componentesRevisadosAcumulados = $registros
                                                    ->flatMap(function ($item) {
                                                        if (is_array($item->componentes_revisados)) {
                                                            return $item->componentes_revisados;
                                                        }
                                                        if (is_string($item->componentes_revisados)) {
                                                            $decoded = json_decode($item->componentes_revisados, true);
                                                            return is_array($decoded) ? $decoded : [];
                                                        }
                                                        return [];
                                                    })
                                                    ->filter(fn($numeroComponente) => is_numeric($numeroComponente))
                                                    ->map(fn($numeroComponente) => (int) $numeroComponente)
                                                    ->unique()
                                                    ->sort()
                                                    ->values();
                                                $revisadasAcumuladas = $componentesRevisadosAcumulados->count();
                                                $pendientesAcumulados = max(0, $totalComponentesComponente - $revisadasAcumuladas);
                                                $estadoPorNivel = [];
                                                $siguienteRevision = null;

                                                $bgColor = 'cell-empty';
                                                $borderColor = '';
                                                $estadoActual = '';
                                                if ($celdaSeguimiento) {
                                                    $estadoPorNivel = $celdaSeguimiento['estado_por_nivel'];
                                                    $siguienteRevision = $celdaSeguimiento['siguiente_revision'];
                                                }
                                                $procesoCompletado = (bool) ($celdaSeguimiento['completado'] ?? ($hasData && !$siguienteRevision));
                                                $moduloCompletado = (bool) ($seguimientoLinea['modulos'][$moduloNumero]['completado'] ?? false);

                                                if($hasData){
                                                    $estadoActual = $registro->estado ?? 'Buen estado';
                                                    if ($estadoActual === 'Cambiado') {
                                                        $bgColor = 'cell-changed';
                                                    } elseif ($estadoActual === 'Dañado - Requiere cambio') {
                                                        $bgColor = 'cell-danger';
                                                    } elseif (str_contains($estadoActual, 'Desgaste')) {
                                                        $bgColor = 'cell-warning';
                                                    } else {
                                                        $bgColor = 'cell-ok';
                                                    }

                                                    if ($procesoCompletado) {
                                                        $bgColor = 'cell-ok';
                                                    }
                                                }
                                            @endphp

                                            @if(!$brazoAplicaModulo)
                                                <td class="px-4 py-3 align-middle bg-slate-50 text-center text-xs font-semibold text-slate-400">
                                                    No aplica
                                                </td>
                                            @else
                                            <td class="border px-4 py-3 align-top {{ $bgColor }} {{ $hasData ? 'analysis-cell' : 'analysis-cell no-data' }}"
                                                @if($hasData)
                                                    onclick="openAnalysisDetail({{ json_encode([
                                                        'id' => $registro->id,
                                                        'linea' => $linea->nombre,
                                                        'modulo' => $moduloNumero,
                                                        'componente' => $compData['nombre'],
                                                        'lado' => $registro->lado,
                                                        'nivel' => $registro->nivel,
                                                        'fecha_analisis' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : $registro->created_at->format('d/m/Y'),
                                                        'numero_orden' => $registro->numero_orden,
                                                        'estado' => $estadoActual,
                                                        'usuario_nombre' => $registro->usuario?->name ?? $registro->responsable ?? 'Usuario no registrado',
                                                        'actividad' => $registro->actividad,
                                                        'imagenes' => $registro->evidencia_fotos ?? [],
                                                        'componentes_revisados' => $componentesRevisadosAcumulados,
                                                        'total_componentes' => $totalComponentesComponente ?: $registro->total_componentes,
                                                        'estado_por_nivel' => $estadoPorNivel,
                                                        'pendientes_por_nivel' => [
                                                            'SUPERIOR' => isset($estadoPorNivel['SUPERIOR']) && !$estadoPorNivel['SUPERIOR']['completado']
                                                                ? $estadoPorNivel['SUPERIOR']['lados_pendientes']
                                                                : [],
                                                            'INFERIOR' => isset($estadoPorNivel['INFERIOR']) && !$estadoPorNivel['INFERIOR']['completado']
                                                                ? $estadoPorNivel['INFERIOR']['lados_pendientes']
                                                                : [],
                                                        ],
                                                        'actualizaciones' => $registros
                                                            ->sortByDesc(function ($item) {
                                                                return ($item->created_at?->timestamp ?? 0) . '-' . str_pad((string) $item->id, 10, '0', STR_PAD_LEFT);
                                                            })
                                                            ->map(function ($item) {
                                                                $componentes = [];

                                                                if (is_array($item->componentes_revisados)) {
                                                                    $componentes = $item->componentes_revisados;
                                                                } elseif (is_string($item->componentes_revisados)) {
                                                                    $decoded = json_decode($item->componentes_revisados, true);
                                                                    $componentes = is_array($decoded) ? $decoded : [];
                                                                }

                                                                return [
                                                                    'id' => $item->id,
                                                                    'fecha' => $item->fecha_analisis ? $item->fecha_analisis->format('d/m/Y') : $item->created_at?->format('d/m/Y'),
                                                                    'hora' => $item->created_at?->format('H:i'),
                                                                    'orden' => $item->numero_orden,
                                                                    'estado' => $item->estado,
                                                                    'usuario_nombre' => $item->usuario?->name ?? $item->responsable ?? 'Usuario no registrado',
                                                                    'actividad' => $item->actividad,
                                                                    'lado' => $item->lado,
                                                                    'nivel' => $item->nivel,
                                                                    'componentes_revisados' => collect($componentes)
                                                                        ->filter(fn($numeroComponente) => is_numeric($numeroComponente))
                                                                        ->map(fn($numeroComponente) => (int) $numeroComponente)
                                                                        ->values(),
                                                                ];
                                                            })
                                                            ->values(),
                                                        'edit_url' => route('pasteurizadora.analisis-pasteurizadora.edit', $registro->id),
                                                        'historial_url' => route('pasteurizadora.analisis-pasteurizadora.historial', ['linea_id' => $linea->id, 'modulo' => $moduloNumero, 'componente' => $codigo])
                                                    ]) }})"
                                                @endif>

                                                @if($hasData)
                                                    <div class="space-y-2">
                                                        @if($procesoCompletado)
                                                            <div class="flex items-center justify-between gap-2 rounded-lg border border-green-200 bg-green-100 px-2 py-1.5 text-green-800">
                                                                <span class="inline-flex items-center gap-1 text-xs font-bold">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                    </svg>
                                                                    Proceso terminado
                                                                </span>
                                                            </div>
                                                        @endif

                                                        <div class="flex items-center justify-between text-xs text-gray-600">
                                                            <span class="flex items-center gap-1">
                                                                <i class="fas fa-calendar-alt"></i>
                                                                {{ $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : $registro->created_at->format('d/m/Y') }}
                                                            </span>
                                                            <span class="flex items-center gap-1">
                                                                <i class="fas fa-hashtag"></i>
                                                                #{{ $registro->numero_orden }}
                                                            </span>
                                                        </div>

                                                        @if($registro->lado)
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $registro->lado === 'VAPOR' ? 'lado-badge vapor' : 'lado-badge pasillo' }}">
                                                                <i class="fas {{ $registro->lado === 'VAPOR' ? 'fa-wind' : 'fa-walking' }}"></i>
                                                                {{ $registro->lado }}
                                                            </span>
                                                        @endif

                                                        <div>
                                                            @php
                                                                $estadoActualDisplay = $estadoActual;
                                                                $statusClass = '';
                                                                $icon = '';
                                                                if ($estadoActualDisplay == 'Buen estado') {
                                                                    $statusClass = 'bg-green-100 text-green-800';
                                                                    $icon = 'fa-check-circle';
                                                                } elseif(str_contains($estadoActualDisplay, 'Desgaste')) {
                                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                                    $icon = 'fa-exclamation-triangle';
                                                                } elseif($estadoActualDisplay == 'Dañado - Requiere cambio') {
                                                                    $statusClass = 'bg-red-100 text-red-800';
                                                                    $icon = 'fa-times-circle';
                                                                } else {
                                                                    $statusClass = 'bg-blue-100 text-blue-800';
                                                                    $icon = 'fa-sync-alt';
                                                                }
                                                            @endphp
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                                                <i class="fas {{ $icon }}"></i>
                                                                {{ $estadoActualDisplay }}
                                                            </span>
                                                        </div>

                                                        <div>
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                                <i class="fas fa-user-check"></i>
                                                                Realizado por: {{ $registro->usuario?->name ?? $registro->responsable ?? 'Usuario no registrado' }}
                                                            </span>
                                                        </div>

                                                        <div class="flex gap-2 pt-1">
                                                            @if(count($registro->evidencia_fotos ?? []) > 0)
                                                                <button onclick="event.stopPropagation(); openAllImages({{ json_encode($registro->evidencia_fotos) }}, '{{ $registro->numero_orden }}')"
                                                                        class="cell-action-button secondary">
                                                                    <i class="fas fa-images"></i>
                                                                    {{ count($registro->evidencia_fotos) }}
                                                                </button>
                                                            @endif
                                                            @if($procesoCompletado)
                                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">
                                                                    <i class="fas fa-check"></i>
                                                                    Terminado
                                                                </span>
                                                                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.create-quick', [
                                                                    'linea_id' => $linea->id,
                                                                    'modulo' => $moduloNumero,
                                                                    'componente' => $codigo,
                                                                    'lado' => $siguienteRevision['lado'] ?? '',
                                                                    'nivel' => $siguienteRevision['nivel'] ?? ''
                                                                ]) }}"
                                                                   class="cell-action-button primary"
                                                                   onclick="event.stopPropagation();">
                                                                    <i class="fas fa-plus"></i>
                                                                    Nuevo análisis
                                                                </a>
                                                            @else
                                                                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.create-quick', [
                                                                    'linea_id' => $linea->id,
                                                                    'modulo' => $moduloNumero,
                                                                    'componente' => $codigo,
                                                                    'lado' => $siguienteRevision['lado'] ?? '',
                                                                    'nivel' => $siguienteRevision['nivel'] ?? ''
                                                                ]) }}"
                                                                   class="cell-action-button primary"
                                                                   onclick="event.stopPropagation();">
                                                                    <i class="fas fa-plus"></i>
                                                                    Continuar
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="empty-cell">
                                                        <div class="empty-cell-icon">
                                                            <i class="fas fa-clipboard"></i>
                                                        </div>
                                                        <p class="text-gray-500 text-xs mb-3">Sin análisis</p>
                                                        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.create-quick', [
                                                            'linea_id' => $linea->id,
                                                            'modulo' => $moduloNumero,
                                                            'componente' => $codigo,
                                                            'lado' => '',
                                                            'nivel' => ''
                                                        ]) }}"
                                                           class="empty-state-button"
                                                           onclick="event.stopPropagation();">
                                                            <i class="fas fa-plus"></i> Nuevo
                                                        </a>
                                                    </div>
                                                @endif
                                            </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>

{{-- MODAL DE DETALLE DE ANÁLISIS --}}
<div id="analysisDetailModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm"
     onclick="if(event.target === this) closeAnalysisDetailModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden animate-modalIn border border-slate-200">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4 border-b border-slate-700 text-white">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center border border-white/10">
                        <i class="fas fa-chart-line text-blue-100 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-white" id="detailModalTitle">
                        Detalle del Análisis
                    </h3>
                </div>
                <button onclick="closeAnalysisDetailModal()"
                        class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-slate-200 hover:text-white transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="p-8 overflow-auto max-h-[calc(90vh-100px)] bg-gray-50 custom-scrollbar" id="detailModalContent">
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Cargando...</p>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE ESTADÍSTICAS --}}
<div id="estadoModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm" onclick="if(event.target === this) closeEstadoModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[85vh] overflow-hidden animate-modalIn border border-slate-200">
        <div class="px-6 py-4 border-b flex justify-between items-center" id="estadoModalHeader">
            <h3 class="text-xl font-bold" id="estadoModalTitle">Detalle de registros</h3>
            <button onclick="closeEstadoModal()" class="w-9 h-9 rounded-xl hover:bg-white/15 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(85vh-80px)] custom-scrollbar bg-slate-50" id="estadoModalContent">
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Cargando...</p>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE IMÁGENES --}}
<div id="allImagesModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300"
     onclick="closeAllImagesModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300 scale-100 border border-gray-200">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-8 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-gray-700 p-3 rounded-lg border border-gray-600">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl uppercase tracking-wider font-mono">
                            Galería Industrial
                        </h3>
                        <p class="text-gray-300 text-sm">Evidencia fotográfica del análisis</p>
                    </div>
                </div>
                <button onclick="closeAllImagesModal()"
                        class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 transition-all flex items-center justify-center group border border-gray-600">
                    <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-100px)] bg-gray-50">
            <div id="imageGrid" class="image-grid-enhanced"></div>
            <div id="emptyImages" class="hidden">
                <div class="text-center py-16">
                    <div class="bg-gray-200 w-24 h-24 rounded-lg flex items-center justify-center mx-auto mb-4 border border-gray-300">
                        <i class="fas fa-image text-4xl text-gray-500"></i>
                    </div>
                    <p class="text-gray-600 text-lg font-mono">No hay imágenes disponibles</p>
                    <p class="text-gray-400 text-sm mt-2">Este análisis no cuenta con evidencia fotográfica</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE IMAGEN INDIVIDUAL --}}
<div id="singleImageModal" class="fixed inset-0 bg-black/95 hidden items-center justify-center z-[60] p-4 transition-all duration-300"
     onclick="closeSingleImageModal()">
    <div class="relative max-w-6xl w-full h-full flex items-center justify-center">
        <button onclick="closeSingleImageModal()"
                class="absolute top-6 right-6 w-12 h-12 rounded-lg bg-gray-800/50 hover:bg-gray-700/70 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-gray-600 transition-all z-10 group">
            <i class="fas fa-times group-hover:rotate-90 transition-transform"></i>
        </button>
        <div class="relative">
            <img id="singleModalImg" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl border-4 border-gray-700">
            <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 bg-gray-900/80 backdrop-blur-sm text-white px-4 py-2 rounded-lg text-sm font-mono border border-gray-700">
                <span id="currentImageCounter"></span>
            </div>
        </div>
    </div>
</div>

<div id="loadingOverlay" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100]">
    <div class="bg-white rounded-lg p-8 shadow-2xl">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-700">Cargando...</p>
    </div>
</div>

<script>
let currentImages = [];
let currentAnalysisData = null;
let currentImageIndex = 0;

// FUNCIONES PARA EL MODAL DE ESTADOS
function openEstadoModal(tipo, registros) {
    currentEstadoData = registros;
    const modal = document.getElementById('estadoModal');
    const title = document.getElementById('estadoModalTitle');
    const header = document.getElementById('estadoModalHeader');
    const content = document.getElementById('estadoModalContent');

    let bgColor = '', textColor = '', icono = '';
    switch(tipo) {
        case 'total':
            bgColor = 'bg-gray-100';
            textColor = 'text-gray-800';
            icono = '📊';
            title.innerHTML = `Todos los registros (${registros.length})`;
            break;
        case 'buen_estado':
            bgColor = 'bg-green-100';
            textColor = 'text-green-800';
            icono = '✅';
            title.innerHTML = `Registros en buen estado (${registros.length})`;
            break;
        case 'desgaste':
            bgColor = 'bg-yellow-100';
            textColor = 'text-yellow-800';
            icono = '⚠️';
            title.innerHTML = `Registros con desgaste (${registros.length})`;
            break;
        case 'danado':
            bgColor = 'bg-red-100';
            textColor = 'text-red-800';
            icono = '❌';
            title.innerHTML = `${icono} Registros Dañados (${registros.length})`;
            break;
        case 'cambiado':
            bgColor = 'bg-blue-100';
            textColor = 'text-blue-800';
            icono = '🔄';
            title.innerHTML = `Registros cambiados (${registros.length})`;
            break;
        default:
            title.innerHTML = `Registros (${registros.length})`;
    }

    header.className = `px-6 py-4 border-b flex justify-between items-center ${bgColor}`;
    title.className = `text-xl font-bold ${textColor}`;

    if (registros.length === 0) {
        content.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No hay registros en esta categoría</p>
            </div>
        `;
    } else {
        const agrupadosPorLinea = {};
        registros.forEach(reg => {
            if (!agrupadosPorLinea[reg.linea]) {
                agrupadosPorLinea[reg.linea] = [];
            }
            agrupadosPorLinea[reg.linea].push(reg);
        });

        let html = '<div class="space-y-6">';
        for (const [linea, items] of Object.entries(agrupadosPorLinea)) {
            html += `
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                    <div class="flex items-center gap-3 px-5 py-3 bg-gray-50 border-b border-gray-200">
                        <i class="fas fa-chart-line text-gray-600"></i>
                        <h4 class="font-bold text-lg text-gray-800">${escapeHtml(linea)}</h4>
                        <span class="text-xs bg-gray-200 px-2 py-1 rounded-full">${items.length} registros</span>
                    </div>
                    <div class="p-4 space-y-3">
            `;

            items.forEach(reg => {
                let estadoClass = '';
                let estadoIcon = '';
                if (reg.estado === 'Buen estado') {
                    estadoClass = 'border-l-green-500 bg-green-50';
                    estadoIcon = '✅';
                } else if (reg.estado.includes('Desgaste')) {
                    estadoClass = 'border-l-yellow-500 bg-yellow-50';
                    estadoIcon = '⚠️';
                } else if (reg.estado === 'Dañado - Requiere cambio') {
                    estadoClass = 'border-l-red-500 bg-red-50';
                    estadoIcon = '❌';
                } else if (reg.estado === 'Cambiado') {
                    estadoClass = 'border-l-blue-500 bg-blue-50';
                    estadoIcon = '🔄';
                }

                html += `
                    <div class="${estadoClass} border-l-4 p-4 rounded-lg hover:shadow-md transition-all cursor-pointer" onclick="cerrarEstadoModalYVerAnalisis(${reg.id})">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap mb-2">
                                    <span class="font-mono text-xs bg-white px-2 py-1 rounded border">Módulo ${reg.modulo}</span>
                                    <span class="font-semibold text-gray-800">${escapeHtml(reg.componente)}</span>
                                    ${reg.lado ? `<span class="text-xs px-2 py-1 rounded ${reg.lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">${reg.lado === 'VAPOR' ? '💨 Vapor' : '🚶 Pasillo'}</span>` : ''}
                                    <span class="text-xs text-gray-500"><i class="far fa-calendar-alt mr-1"></i>${reg.fecha}</span>
                                </div>
                                <p class="text-sm text-gray-600">${escapeHtml(reg.actividad)}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-1 rounded-full font-medium
                                    ${reg.estado === 'Buen estado' ? 'bg-green-100 text-green-700' :
                                      (reg.estado.includes('Desgaste') ? 'bg-yellow-100 text-yellow-700' :
                                      (reg.estado === 'Dañado - Requiere cambio' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'))}">
                                    ${estadoIcon} ${reg.estado}
                                </span>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div></div>`;
        }
        html += '</div>';
        content.innerHTML = html;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeEstadoModal() {
    const modal = document.getElementById('estadoModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function cerrarEstadoModalYVerAnalisis(id) {
    closeEstadoModal();
    window.location.href = `{{ url('/pasteurizadora/analisis-pasteurizadora') }}/${id}`;
}

function openAnalysisDetail(data) {
    showLoading();
    currentAnalysisData = data;
    const content = document.getElementById('detailModalContent');

    let estadoClass = '';
    let estadoIcon = '';
    if (data.estado === 'Buen estado') {
        estadoClass = 'bg-green-100 text-green-700';
        estadoIcon = '✅';
    } else if (data.estado.includes('Desgaste')) {
        estadoClass = 'bg-yellow-100 text-yellow-700';
        estadoIcon = '⚠️';
    } else if (data.estado === 'Dañado - Requiere cambio') {
        estadoClass = 'bg-red-100 text-red-700';
        estadoIcon = '❌';
    } else if (data.estado === 'Cambiado') {
        estadoClass = 'bg-blue-100 text-blue-700';
        estadoIcon = '🔄';
    }

    let componentesRevisadosHtml = '';
    if (data.componentes_revisados && data.componentes_revisados.length > 0) {
        const totalComponentes = data.total_componentes || data.componentes_revisados.length;
        componentesRevisadosHtml = `
            <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-lg mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-indigo-900 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        Componentes revisados
                    </h4>
                    <span class="text-sm font-bold text-indigo-700">${data.componentes_revisados.length} de ${totalComponentes}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    ${data.componentes_revisados.map(num => `
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                            <i class="fas fa-check"></i>
                            #${num}
                        </span>
                    `).join('')}
                </div>
            </div>
        `;
    }

    let nivelEstadoHtml = '';
    if (data.estado_por_nivel) {
        const nivelesOrden = ['SUPERIOR', 'INFERIOR'];
        nivelEstadoHtml = `
            <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg mb-6">
                <h4 class="font-semibold text-purple-900 mb-3 flex items-center gap-2">
                    <i class="fas fa-layer-group"></i>
                    Estado de revisión por nivel
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    ${nivelesOrden.map((nivel) => {
                        const info = data.estado_por_nivel[nivel] || { completado: false, lados_pendientes: [] };
                        const ladosPendientes = info.lados_pendientes || [];
                        const nivelNombre = nivel === 'SUPERIOR' ? 'Nivel Superior' : 'Nivel Inferior';
                        const nivelIcono = nivel === 'SUPERIOR' ? '⬆️' : '⬇️';
                        return `
                            <div class="bg-white p-3 rounded-lg border ${info.completado ? 'border-green-200' : 'border-amber-200'}">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-xs font-semibold ${info.completado ? 'text-green-700' : 'text-amber-700'}">
                                        ${nivelIcono} ${nivelNombre}
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs ${info.completado ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}">
                                        <i class="fas ${info.completado ? 'fa-check-circle' : 'fa-clock'}"></i>
                                        ${info.completado ? 'Completado' : 'Pendiente'}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-700">
                                    ${info.completado
                                        ? '✓ Ambos lados ya fueron revisados.'
                                        : ladosPendientes.length > 0
                                            ? `⚠️ Falta revisar: ${ladosPendientes.map((lado) => lado === 'VAPOR' ? 'Vapor' : 'Pasillo').join(', ')}`
                                            : 'Pendiente de revisión'}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    let actualizacionesHtml = '';
    if (data.actualizaciones && data.actualizaciones.length > 0) {
        actualizacionesHtml = `
            <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-history"></i>
                        Historial de actualizaciones
                    </h4>
                    <span class="text-sm font-bold text-slate-700">${data.actualizaciones.length}</span>
                </div>
                <div class="space-y-3">
                    ${data.actualizaciones.map((item, index) => {
                        const nivelNombre = item.nivel === 'SUPERIOR' ? 'Superior' : (item.nivel === 'INFERIOR' ? 'Inferior' : '');
                        const ladoNombre = item.lado === 'VAPOR' ? 'Vapor' : (item.lado === 'PASILLO' ? 'Pasillo' : '');
                        let estadoItemClass = '';
                        if (item.estado === 'Buen estado') estadoItemClass = 'bg-green-100 text-green-700';
                        else if (item.estado.includes('Desgaste')) estadoItemClass = 'bg-yellow-100 text-yellow-700';
                        else if (item.estado === 'Dañado - Requiere cambio') estadoItemClass = 'bg-red-100 text-red-700';
                        else estadoItemClass = 'bg-blue-100 text-blue-700';
                        return `
                        <div class="bg-white border border-slate-200 rounded-lg p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-700 font-semibold text-xs">${index === 0 ? 'Último registro' : 'Registro #' + (data.actualizaciones.length - index)}</span>
                                    <span class="text-slate-700 font-medium">${item.fecha || ''} ${item.hora || ''}</span>
                                    ${item.orden ? `<span class="text-slate-500 text-xs">Orden #${item.orden}</span>` : ''}
                                    <span class="text-slate-600 text-xs font-semibold">Realizado por: ${item.usuario_nombre || 'Usuario no registrado'}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    ${nivelNombre ? `<span class="text-xs px-2 py-1 rounded ${item.nivel === 'SUPERIOR' ? 'bg-purple-100 text-purple-700' : 'bg-purple-100 text-purple-700'}">${item.nivel === 'SUPERIOR' ? '⬆️' : '⬇️'} ${nivelNombre}</span>` : ''}
                                    ${ladoNombre ? `<span class="text-xs px-2 py-1 rounded ${item.lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">${item.lado === 'VAPOR' ? '💨' : '🚶'} ${ladoNombre}</span>` : ''}
                                    <span class="text-xs px-2 py-1 rounded ${estadoItemClass}">${item.estado}</span>
                                </div>
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-line">${escapeHtml(item.actividad) || 'Sin actividad registrada.'}</p>
                            ${item.componentes_revisados && item.componentes_revisados.length > 0 ? `
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="text-xs text-gray-500">Componentes revisados:</span>
                                    ${item.componentes_revisados.map((numeroComponente) => `<span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-xs font-medium">#${numeroComponente}</span>`).join('')}
                                </div>
                            ` : ''}
                        </div>
                    `}).join('')}
                </div>
            </div>
        `;
    }

    let imagenesHtml = '';
    if (data.imagenes && data.imagenes.length > 0) {
        const normalizedImages = normalizeEvidenceImages(data.imagenes);
        imagenesHtml = `
            <div class="mt-6">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-images"></i>
                    Evidencia Fotográfica
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    ${normalizedImages.map((img, idx) => `
                        <div class="relative group cursor-pointer" onclick="openSingleImage('${img.replace(/'/g, "\\'")}', ${idx})">
                            <img src="${resolveEvidenceImageUrl(img)}" class="w-full h-32 object-cover rounded-lg border-2 border-gray-200 hover:border-blue-500 transition" onerror="this.src='{{ asset('images/placeholder.png') }}'">
                            <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">${idx + 1}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    content.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg p-4 border-l-4 border-gray-700 shadow-sm">
                <p class="text-xs text-gray-500 mb-1"><i class="fas fa-industry"></i> Línea</p>
                <p class="font-bold text-gray-900">${escapeHtml(data.linea)}</p>
            </div>
            <div class="bg-white rounded-lg p-4 border-l-4 border-gray-700 shadow-sm">
                <p class="text-xs text-gray-500 mb-1"><i class="fas fa-cog"></i> Módulo</p>
                <p class="font-bold text-gray-900">Módulo ${data.modulo}</p>
            </div>
            <div class="bg-white rounded-lg p-4 border-l-4 border-gray-700 shadow-sm">
                <p class="text-xs text-gray-500 mb-1"><i class="fas fa-microchip"></i> Componente</p>
                <p class="font-bold text-gray-900">${escapeHtml(data.componente)}</p>
            </div>
            ${data.lado ? `
            <div class="bg-white rounded-lg p-4 border-l-4 border-gray-700 shadow-sm">
                <p class="text-xs text-gray-500 mb-1"><i class="fas fa-arrows-alt-h"></i> Lado</p>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm ${data.lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">
                    ${data.lado === 'VAPOR' ? '💨' : '🚶'} ${data.lado}
                </span>
            </div>
            ` : ''}
            <div class="bg-white rounded-lg p-4 border-l-4 border-gray-700 shadow-sm">
                <p class="text-xs text-gray-500 mb-1"><i class="far fa-calendar-alt"></i> Fecha</p>
                <p class="font-bold text-gray-900">${data.fecha_analisis}</p>
            </div>
            <div class="bg-white rounded-lg p-4 border-l-4 border-gray-700 shadow-sm">
                <p class="text-xs text-gray-500 mb-1"><i class="fas fa-hashtag"></i> Orden</p>
                <p class="font-bold font-mono text-gray-900">#${data.numero_orden}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-xs text-blue-600 mb-1"><i class="fas fa-user-check"></i> Técnico</p>
                <p class="font-bold text-blue-900">Realizado por: ${escapeHtml(data.usuario_nombre) || 'Usuario no registrado'}</p>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <p class="text-xs text-gray-500 mb-2"><i class="fas fa-chart-pie"></i> Estado actual</p>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg ${estadoClass}">
                ${estadoIcon} ${data.estado}
            </span>
        </div>

        ${nivelEstadoHtml}

        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <p class="text-xs text-gray-500 mb-2"><i class="fas fa-clipboard-list"></i> Actividad Realizada</p>
            <p class="text-gray-700 whitespace-pre-line">${escapeHtml(data.actividad) || 'No especificada'}</p>
        </div>

        ${componentesRevisadosHtml}

        ${actualizacionesHtml}

        ${imagenesHtml}

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="${data.edit_url}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition shadow-md">
                <i class="fas fa-edit"></i>
                Editar
            </a>
            <a href="${data.historial_url}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition shadow-md">
                <i class="fas fa-history"></i>
                Ver Historial
            </a>
            <button onclick="closeAnalysisDetailModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                Cerrar
            </button>
        </div>
    `;

    document.getElementById('analysisDetailModal').classList.remove('hidden');
    document.getElementById('analysisDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function closeAnalysisDetailModal() {
    document.getElementById('analysisDetailModal').classList.add('hidden');
    document.getElementById('analysisDetailModal').classList.remove('flex');
    document.body.style.overflow = '';
}

function openAllImages(imagenes, orden) {
    showLoading();
    currentImages = normalizeEvidenceImages(imagenes);
    const modal = document.getElementById('allImagesModal');
    const grid = document.getElementById('imageGrid');
    const empty = document.getElementById('emptyImages');

    grid.innerHTML = '';

    if (currentImages.length === 0) {
        grid.classList.add('hidden');
        empty.classList.remove('hidden');
    } else {
        grid.classList.remove('hidden');
        empty.classList.add('hidden');

        currentImages.forEach((path, index) => {
            const item = document.createElement('div');
            item.className = 'image-item';
            const safePath = String(path).replace(/'/g, "\\'");
            item.innerHTML = `
                <div class="image-number">#${index + 1}</div>
                <img src="${resolveEvidenceImageUrl(path)}" class="grid-image" onclick="openSingleImage('${safePath}', ${index})" alt="Evidencia ${index + 1}">
                <div class="image-info">
                    <button class="download-image-btn" onclick="event.stopPropagation(); downloadSingleImage('${safePath}', ${index})">
                        <i class="fas fa-download"></i>
                        Descargar
                    </button>
                </div>
            `;
            const img = item.querySelector('img');
            img.onerror = function() { this.src = '{{ asset('images/placeholder.png') }}'; };
            grid.appendChild(item);
        });
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function closeAllImagesModal() {
    document.getElementById('allImagesModal').classList.add('hidden');
    document.getElementById('allImagesModal').classList.remove('flex');
    document.body.style.overflow = '';
}

function openSingleImage(path, index) {
    currentImageIndex = index;
    const modal = document.getElementById('singleImageModal');
    const img = document.getElementById('singleModalImg');
    const counter = document.getElementById('currentImageCounter');

    img.src = resolveEvidenceImageUrl(path);
    img.onerror = function() { this.src = '{{ asset('images/placeholder.png') }}'; };

    if (currentImages.length > 0) {
        counter.textContent = `${index + 1} / ${currentImages.length}`;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeSingleImageModal() {
    document.getElementById('singleImageModal').classList.add('hidden');
    document.getElementById('singleImageModal').classList.remove('flex');
    document.body.style.overflow = '';
}

function downloadSingleImage(imagePath, index) {
    const link = document.createElement('a');
    link.href = resolveEvidenceImageUrl(imagePath);
    link.download = `imagen-${index + 1}.jpg`;
    link.click();
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function toggleAdvancedFilters() {
    const panel = document.getElementById('advancedFiltersPanel');
    const icon = document.getElementById('advancedFiltersIcon');
    panel.classList.toggle('show');

    if (panel.classList.contains('show')) {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function normalizeEvidenceImages(imagenes) {
    if (!imagenes) return [];
    if (typeof imagenes === 'string') {
        if (imagenes.trim() === '' || imagenes === 'null' || imagenes === '[]') return [];
        try {
            return normalizeEvidenceImages(JSON.parse(imagenes));
        } catch (error) {
            return [imagenes];
        }
    }
    if (typeof imagenes === 'object' && !Array.isArray(imagenes)) {
        return normalizeEvidenceImages(Object.values(imagenes));
    }
    if (!Array.isArray(imagenes)) return [];

    return imagenes
        .flatMap((item) => normalizeEvidenceImages(item))
        .map((item) => String(item).trim())
        .filter((item) => item.length > 0);
}

function resolveEvidenceImageUrl(path) {
    if (!path) return '';
    if (path.startsWith('http')) return path;

    const cleanPath = path.replace(/^\/+/, '');
    const baseUrl = window.location.origin;

    // Probar diferentes rutas comunes
    const candidates = [
        baseUrl + '/storage/' + cleanPath,
        baseUrl + '/storage/analisis-evidencias/' + cleanPath.split('/').pop(),
        baseUrl + '/analisis-evidencias/' + cleanPath.split('/').pop(),
        baseUrl + '/storage/app/public/' + cleanPath,
        baseUrl + '/public/storage/' + cleanPath,
        '{{ asset('storage') }}/' + cleanPath
    ];

    return candidates[0];
}

// Event Listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAnalysisDetailModal();
        closeAllImagesModal();
        closeSingleImageModal();
        closeEstadoModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips o efectos si es necesario
});
</script>
@endsection
