@extends('layouts.app')

@section('title', 'Análisis de Lavadoras')

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
        gap: 4px;
        padding: 4px 10px;
        border-radius: 16px;
        font-weight: 500;
        font-size: 13px;
    }
    
    .lado-badge.vapor {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .lado-badge.pasillo {
        background-color: #dbeafe;
        color: #1e40af;
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
    
    .reductor-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }
    
    .reductor-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }
    
    .reductor-label {
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
    
    .ver-mas-btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 20px;
        background: white;
        border: 2px dashed #cbd5e1;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .ver-mas-btn:hover {
        border-color: #3b82f6;
        color: #2563eb;
        background: #eff6ff;
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
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* MODAL PARA VER MÁS LAVADORAS */
    .lineas-modal {
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
    
    .lineas-modal.show {
        display: flex;
    }
    
    .lineas-modal-content {
        background: white;
        border-radius: 24px;
        max-width: 800px;
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    .lineas-modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .lineas-modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .lineas-modal-header i {
        color: #3b82f6;
    }
    
    .lineas-modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
    }
    
    .lineas-modal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
    }
    
    .close-modal-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .close-modal-btn:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        border: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.05);
        border-color: #e2e8f0;
    }
    
    .stat-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }
    
    .stat-trend {
        font-size: 12px;
        color: #94a3b8;
    }
    
    .table-header-container {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
        border-radius: 12px 12px 0 0;
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
        
        .lineas-modal-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .lineas-modal-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ESTILOS ADICIONALES PARA LOS MODALES MEJORADOS */
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
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .status-badge-enhanced {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 20px;
        border-radius: 40px;
        font-weight: 600;
        font-size: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .status-badge-enhanced.ok {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-color: #d1fae5;
    }
    
    .status-badge-enhanced.warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border-color: #fef3c7;
    }
    
    .status-badge-enhanced.danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border-color: #fee2e2;
    }
    
    .status-badge-enhanced.changed {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border-color: #dbeafe;
    }
    
    .image-grid-enhanced {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }
    
    .image-grid-enhanced .image-item {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .image-grid-enhanced .image-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        border-color: #3b82f6;
    }
    
    .image-grid-enhanced .grid-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.5s ease;
    }
    
    .image-grid-enhanced .grid-image:hover {
        transform: scale(1.1);
    }
    
    .image-grid-enhanced .image-number {
        position: absolute;
        top: 12px;
        left: 12px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        font-size: 13px;
        font-weight: bold;
        padding: 4px 12px;
        border-radius: 20px;
        z-index: 10;
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .image-grid-enhanced .image-info {
        padding: 12px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }
    
    .image-grid-enhanced .download-image-btn {
        width: 100%;
        padding: 8px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .image-grid-enhanced .download-image-btn:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: scale(1.02);
    }
    
    .empty-images-enhanced {
        text-align: center;
        padding: 40px;
    }
    
    .lado-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .lado-badge.vapor {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    
    .lado-badge.pasillo {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }
    
    @media (max-width: 768px) {
        .image-grid-enhanced {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
        }
        
        .image-grid-enhanced .grid-image {
            height: 140px;
        }
        
        .status-badge-enhanced {
            padding: 6px 16px;
            font-size: 14px;
        }
    }
</style>

<div class="max-w-full mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
        <a href="{{ route('lavadora.dashboard') }}" 
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
                <i class="fas fa-chart-pie text-blue-600"></i>
                Análisis de Lavadoras
            </h1>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('analisis-lavadora.select-linea') }}"
               class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition flex items-center gap-2 shadow-lg shadow-blue-500/20">
                <i class="fas fa-plus-circle"></i>
                Nuevo Análisis
            </a>
        </div>
    </div>

    {{-- FILTROS ESTILO IMAGEN - CON VER MÁS FUNCIONAL --}}
    @php
        $lineas = $lineas ?? collect([]);
        $todosComponentes = $todosComponentes ?? [];
        $componentesPorLinea = $componentesPorLinea ?? [];
        $analisis = $analisis ?? collect([]);
        $reductoresMostrar = $reductoresMostrar ?? [];
        
        // Filtrar solo las lavadoras que queremos mostrar
        $lavadorasPermitidas = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];
        $lineasFiltradas = $lineas->filter(function($linea) use ($lavadorasPermitidas) {
            return in_array($linea->nombre, $lavadorasPermitidas);
        })->values();
        
        // Todas las lavadoras para el modal de "Ver más"
        $todasLasLineas = $lineas->filter(function($linea) use ($lavadorasPermitidas) {
            return !in_array($linea->nombre, $lavadorasPermitidas) && $linea->nombre != null;
        })->values();
    @endphp
    
    @if(isset($lineas) && $lineas->count() > 0)
        <div class="filters-section">
            {{-- LÍNEAS: con las lavadoras específicas --}}
            <div class="lineas-title">
                <i class="fas fa-washing-machine"></i>
                LÍNEAS:
            </div>
            
            <form method="GET" action="{{ route('analisis-lavadora.index') }}" id="filterForm">
                <div class="lineas-grid">
                    @foreach($lineasFiltradas as $l)
                        <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}" 
                             onclick="selectLinea('{{ $l->id }}')">
                            <i class="fas fa-washing-machine"></i>
                            {{ $l->nombre }}
                        </div>
                    @endforeach
                    
                    @if($todasLasLineas->count() > 0)
                        <div class="ver-mas-btn" onclick="showAllLineas()">
                            <i class="fas fa-ellipsis-h"></i>
                            Ver más
                        </div>
                    @endif
                    
                    {{-- Select oculto para el valor real --}}
                    <input type="hidden" name="linea_id" id="lineaInput" value="{{ request('linea_id') }}">
                    <input type="hidden" name="componente_id" value="{{ request('componente_id') }}">
                    <input type="hidden" name="reductor" value="{{ request('reductor') }}">
                    <input type="hidden" name="fecha" value="{{ request('fecha') }}">
                    <input type="hidden" name="estado" value="{{ request('estado') }}" id="estadoInput">
                </div>

                <div class="filters-divider"></div>

                {{-- FILTROS AVANZADOS Y ACCIONES --}}
                <div class="filters-row">
                    <div class="filter-link {{ request()->has('componente_id') || request()->has('reductor') || request()->has('fecha') ? 'active' : '' }}" 
                         onclick="toggleAdvancedFilters()">
                        <i class="fas fa-sliders-h"></i>
                        Filtros avanzados
                        <i id="advancedFiltersIcon" class="fas fa-chevron-down ml-1"></i>
                    </div>
                    
                    <button type="submit" class="btn-apply">
                        <i class="fas fa-search"></i>
                        Aplicar filtros
                    </button>
                    
                    <a href="{{ route('analisis-lavadora.index') }}" class="btn-clear">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </div>

                {{-- PANEL DE FILTROS AVANZADOS --}}
                <div id="advancedFiltersPanel" class="advanced-filters-panel {{ request()->has('componente_id') || request()->has('reductor') || request()->has('fecha') || request()->has('estado') ? 'show' : '' }}">
                    <div class="advanced-filters-grid">
                        <div class="filter-group">
                            <label><i class="fas fa-cog mr-1"></i> Componente</label>
                            <select name="componente_id" class="filter-select">
                                <option value="">Todos los componentes</option>
                                @foreach(($todosComponentes ?? []) as $key => $nombre)
                                    <option value="{{ $key }}" {{ request('componente_id') == $key ? 'selected' : '' }}>
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-compress-alt mr-1"></i> Reductor</label>
                            <select name="reductor" class="filter-select">
                                <option value="">Todos los reductores</option>
                                @php
                                    $todosReductores = [
                                        'Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5',
                                        'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10',
                                        'Reductor 11', 'Reductor 12', 'Reductor 13', 'Reductor 14', 'Reductor 15',
                                        'Reductor 16', 'Reductor 17', 'Reductor 18', 'Reductor 19', 'Reductor 20',
                                        'Reductor 21', 'Reductor 22', 'Reductor Principal', 'Reductor Loca'
                                    ];
                                @endphp
                                @foreach($todosReductores as $reductor)
                                    <option value="{{ $reductor }}" {{ request('reductor') == $reductor ? 'selected' : '' }}>
                                        {{ $reductor }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="far fa-calendar-alt mr-1"></i> Mes / Año</label>
                            <input type="month" name="fecha" value="{{ request('fecha') }}" class="filter-input">
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-clipboard-check mr-1"></i> Estado</label>
                            <select name="estado" class="filter-select">
                                <option value="">Todos los estados</option>
                                <option value="Buen estado" {{ request('estado') == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                                <option value="Desgaste moderado" {{ request('estado') == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                                <option value="Desgaste severo" {{ request('estado') == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                                <option value="Dañado - Requiere cambio" {{ request('estado') == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                                <option value="Dañado - Cambiado" {{ request('estado') == 'Dañado - Cambiado' ? 'selected' : '' }}>🔄 Dañado - Cambiado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif

    {{-- MODAL PARA VER MÁS LAVADORAS --}}
    <div id="lineasModal" class="lineas-modal">
        <div class="lineas-modal-content">
            <div class="lineas-modal-header">
                <h3>
                    <i class="fas fa-washing-machine"></i>
                    Estas lineas no cuentan con Lavadora
                </h3>
                <button onclick="closeLineasModal()" class="close-modal-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="lineas-modal-body">
                <div class="lineas-modal-grid">
                    @foreach($todasLasLineas as $l)
                        <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}" 
                             onclick="selectLineaFromModal('{{ $l->id }}')">
                            <i class="fas fa-washing-machine"></i>
                            {{ $l->nombre }}
                        </div>
                    @endforeach
                    @if($todasLasLineas->count() == 0)
                        <p class="text-gray-500 col-span-full text-center py-8">
                            No hay más lavadoras disponibles
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- RESÚMENES Y ESTADÍSTICAS --}}
    @php
        $analisisCollection = isset($analisis) ? collect($analisis) : collect([]);

        if ($analisisCollection->count() > 0) {
            $estadisticas = [
                'total' => $analisisCollection->count(),
                'buen_estado' => $analisisCollection
                    ->where('estado', 'Buen estado')
                    ->count(),
                'desgaste' => $analisisCollection
                    ->whereIn('estado', ['Desgaste moderado', 'Desgaste severo'])
                    ->count(),
                'danado_requiere' => $analisisCollection
                    ->where('estado', 'Dañado - Requiere cambio')
                    ->count(),
                'danado_cambiado' => $analisisCollection
                    ->where('estado', 'Dañado - Cambiado')
                    ->count(),
                'recientes' => $analisisCollection->filter(function ($item) {
                    return $item->created_at &&
                           $item->created_at->gt(now()->subDays(7));
                })->count(),
            ];
        }
    @endphp
    @if($analisisCollection->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">

        {{-- TOTAL ANÁLISIS --}}
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border-t-4 border-gray-600 flex items-center justify-between min-h-[120px]">
        <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Total análisis
                </p>
                <h3 class="text-3xl font-bold text-gray-700 mt-2">
                    {{ $estadisticas['total'] ?? 0 }}
                </h3>
            </div>
            <div class="bg-gray-100 text-gray-600 p-3 rounded-full">
                <i class="fas fa-chart-line text-lg"></i>
            </div>
        </div>

        {{-- BUEN ESTADO --}}
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border-t-4 border-green-600 flex items-center justify-between min-h-[120px]">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Buen estado
                </p>
                <h3 class="text-3xl font-bold text-green-600 mt-2">
                    {{ $estadisticas['buen_estado'] ?? 0 }}
                </h3>
            </div>
            <div class="bg-green-100 text-green-600 p-3 rounded-full">
                <i class="fas fa-check-circle text-lg"></i>
            </div>
        </div>

        {{-- DESGASTE --}}
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border-t-4 border-yellow-500 flex items-center justify-between min-h-[120px]">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Desgaste
                </p>
                <h3 class="text-3xl font-bold text-yellow-500 mt-2">
                    {{ $estadisticas['desgaste'] ?? 0 }}
                </h3>
            </div>
            <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
        </div>

        {{-- DAÑADOS --}}
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border-t-4 border-red-600 flex items-center justify-between min-h-[120px]">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Dañados
                </p>
                <h3 class="text-3xl font-bold text-red-600 mt-2">
                    {{ ($estadisticas['danado_requiere'] ?? 0) + ($estadisticas['danado_cambiado'] ?? 0) }}
                </h3>
            </div>
            <div class="bg-red-100 text-red-600 p-3 rounded-full">
                <i class="fas fa-times-circle text-lg"></i>
            </div>
        </div>

        {{-- DAÑADO CAMBIADO --}}
        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border-t-4 border-blue-600 flex items-center justify-between min-h-[120px]">
            <div>
                <p class="text-xs font-semibold text--500 uppercase tracking-wide">
                    Cambiados
                </p>
                <h3 class="text-3xl font-bold text-blue-700 mt-2">
                    {{ $estadisticas['danado_cambiado'] ?? 0 }}
                </h3>
            </div>
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                <i class="fas fa-sync-alt text-lg"></i>
            </div>
        </div>

    </div>
@endif
    {{-- TABLA PRINCIPAL --}}
    @php
        /* ===============================
        LINEA A MOSTRAR
        =============================== */
        $analisisCollection = collect($analisis ?? []);
        
        if (request('linea_id') && isset($lineas)) {
            $lineaMostrar = $lineas->firstWhere('id', request('linea_id'));
        } elseif ($analisisCollection->isNotEmpty()) {
            $lineaMostrar = $analisisCollection->first()->linea ?? null;
        }

        /* ===============================
        COMPONENTES PARA TABLA
        =============================== */
        $componentesParaTabla = collect();

        if ($lineaMostrar && isset($componentesPorLinea[$lineaMostrar->nombre])) {
            foreach ($componentesPorLinea[$lineaMostrar->nombre] as $id => $nombre) {
                $componentesParaTabla->push((object)[
                    'id'     => $id,
                    'nombre' => $nombre,
                    'icono'  => asset("images/componentes-lavadora/{$id}.png"),
                ]);
            }
        }

        if (request('componente_id')) {
            $componentesParaTabla = $componentesParaTabla
                ->where('id', request('componente_id'))
                ->values();
        }

        /* ===============================
        REDUCTORES PARA TABLA
        =============================== */
        $reductoresParaTabla = collect();

        if (request('linea_id') && !empty($reductoresMostrar)) {
            $reductoresParaTabla = collect($reductoresMostrar);
        } elseif ($analisisCollection->count() > 0) {
            $reductoresParaTabla = $analisisCollection
                ->pluck('reductor')
                ->unique()
                ->sort()
                ->values();
        }

        if (request('reductor')) {
            $reductoresParaTabla = $reductoresParaTabla
                ->filter(fn($r) => $r == request('reductor'))
                ->values();
        }

        /* ===============================
        AGRUPAR ANALISIS
        =============================== */
        $analisisAgrupados = [];

        foreach ($analisisCollection as $item) {
            if (!$item->componente) continue;

            $reductor = $item->reductor;
            $codigo   = $item->componente->codigo ?? '';
            $codigoBase = $codigo;

            if (isset($componentesPorLinea)) {
                foreach ($componentesPorLinea as $lineaCodigos) {
                    foreach ($lineaCodigos as $key => $nombre) {
                        if (str_contains($codigo, $key)) {
                            $codigoBase = $key;
                            break 2;
                        }
                    }
                }
            }
            
            if (!isset($analisisAgrupados[$reductor][$codigoBase])) {
                $analisisAgrupados[$reductor][$codigoBase] = collect();
            }

            $analisisAgrupados[$reductor][$codigoBase]->push($item);
        }
    @endphp

    @if((isset($lineaMostrar) && $lineas->count() > 0) || ($analisisCollection->count() > 0))
        <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            {{-- ================= ENCABEZADO DE TABLA ================= --}}
                <div class="table-header-container">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">

                        {{-- LADO IZQUIERDO: ICONO + TITULO + FILTROS --}}
                        <div class="flex items-center gap-5">

                            {{-- ICONO --}}
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 md:w-20 md:h-20">
                                    <img src="{{ asset('images/icono-maquina.png') }}" 
                                        alt="Icono de máquina" 
                                        class="w-full h-full object-contain drop-shadow-lg">
                                </div>
                            </div>

                            {{-- TITULO Y FILTROS --}}
                            <div>
                                {{-- TITULO --}}
                                <h2 class="font-bold text-2xl text-white leading-tight">
                                    {{ $lineaMostrar->nombre ?? 'Análisis de Componentes' }}
                                </h2>

                                {{-- FILTROS ACTIVOS --}}
                                <div class="flex flex-wrap gap-4 mt-2 text-blue-100 text-sm">

                                    @if(request('componente_id') && isset($todosComponentes))
                                        <span class="flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full">
                                            <i class="fas fa-cog text-xs"></i>
                                            {{ $todosComponentes[request('componente_id')] ?? request('componente_id') }}
                                        </span>
                                    @endif

                                    @if(request('reductor'))
                                        <span class="flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full">
                                            <i class="fas fa-sliders-h text-xs"></i>
                                            {{ request('reductor') }}
                                        </span>
                                    @endif

                                    @if(request('fecha'))
                                        <span class="flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full">
                                            <i class="far fa-calendar-alt text-xs"></i>
                                            {{ request('fecha') }}
                                        </span>
                                    @endif

                                </div>
                            </div>

                        </div>

                        {{-- LADO DERECHO (ESPACIO DISPONIBLE PARA BOTONES FUTUROS) --}}
                        {{-- Aquí puedes agregar botones como Exportar, Nuevo Análisis, etc --}}
                        
                    </div>
                </div>
            {{-- TABLA COMPACTA --}}
            @if(isset($componentesParaTabla) && isset($reductoresParaTabla) && count($componentesParaTabla) > 0 && count($reductoresParaTabla) > 0)
                <div class="table-wrapper" id="mainTable">
                    <div class="scroll-indicator">
                        <i class="fas fa-arrows-alt-h mr-1"></i> Desplázate para ver más
                    </div>
                    <table class="w-full compact-table border-collapse" id="analysisTable">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="sticky-top-left sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm table-corner">
                                    <div class="reductor-header">
                                        <div class="reductor-name">REDUCTOR</div>
                                        <div class="reductor-label">COMPONENTE</div>
                                    </div>
                                </th>
                                @foreach($componentesParaTabla as $c)
                                    @php
                                        $conteoEstado = [
                                            'ok' => 0,
                                            'warning' => 0,
                                            'danger' => 0,
                                            'changed' => 0,
                                            'empty' => count($reductoresParaTabla)
                                        ];
                                    @endphp

                                    @foreach($reductoresParaTabla as $r)
                                        @if(isset($analisisAgrupados[$r][$c->id]))
                                            @php
                                                $primerRegistro = $analisisAgrupados[$r][$c->id]->sortByDesc('fecha_analisis')->first();
                                                $estado = $primerRegistro->estado ?? 'Buen estado';
                                                
                                                if (str_contains($estado, 'Dañado - Cambiado')) {
                                                    $conteoEstado['changed']++;
                                                    $conteoEstado['empty']--;
                                                } elseif(str_contains($estado, 'Dañado')) {
                                                    $conteoEstado['danger']++;
                                                    $conteoEstado['empty']--;
                                                } elseif(str_contains($estado, 'Desgaste')) {
                                                    $conteoEstado['warning']++;
                                                    $conteoEstado['empty']--;
                                                } else {
                                                    $conteoEstado['ok']++;
                                                    $conteoEstado['empty']--;
                                                }
                                            @endphp
                                        @endif
                                    @endforeach

                                    <th class="sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                        <div class="component-header">
                                            <div class="component-name">{{ $c->nombre }}</div>
                                          
                                            <img
                                                src="{{ $c->icono }}"
                                                alt="Icono {{ $c->nombre }}"
                                                class="w-20 h-20 object-contain hover:scale-110 transition-transform"
                                                onerror="this.src='{{ asset('images/extras/sin imagen.png') }}'">
                                            <div class="flex justify-center gap-1 mt-1">
                                                @if($conteoEstado['ok'] > 0)
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['warning'] > 0)
                                                    <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['danger'] > 0)
                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                                @endif
                                                @if($conteoEstado['changed'] > 0)
                                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reductoresParaTabla as $r)
                                @php
                                    $conteoReductor = [
                                        'total' => 0,
                                        'ok' => 0,
                                        'warning' => 0,
                                        'danger' => 0,
                                        'changed' => 0
                                    ];
                                    
                                    foreach($componentesParaTabla as $c) {
                                        if(isset($analisisAgrupados[$r][$c->id])) {
                                            $conteoReductor['total']++;
                                            $primerRegistro = $analisisAgrupados[$r][$c->id]->sortByDesc('fecha_analisis')->first();
                                            $estado = $primerRegistro->estado ?? 'Buen estado';
                                            
                                            if (str_contains($estado, 'Dañado - Cambiado')) {
                                                $conteoReductor['changed']++;
                                            } elseif(str_contains($estado, 'Dañado')) {
                                                $conteoReductor['danger']++;
                                            } elseif(str_contains($estado, 'Desgaste')) {
                                                $conteoReductor['warning']++;
                                            } else {
                                                $conteoReductor['ok']++;
                                            }
                                        }
                                    }
                                @endphp
                                <tr>
                                    <th class="sticky-left cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm align-top">
                                        <div class="reductor-header">
                                            <div class="reductor-name">{{ $r }}</div>
                                            <div class="reductor-label">Reductor</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $conteoReductor['total'] }}/{{ count($componentesParaTabla) }}
                                            </div>
                                        </div>
                                    </th>
                                    
                                    @foreach($componentesParaTabla as $c)
                                        @php
                                            $registros = $analisisAgrupados[$r][$c->id] ?? collect();
                                            $registro = $registros->sortByDesc('fecha_analisis')->first();
                                            $totalHistorial = $registros->count();
                                            $hasData = $registros->isNotEmpty() && !empty($registro);
                                            $color = '';
                                            $isNew = false;
                                            $imagenes = [];
                                            
                                            if($hasData){
                                                $estadoActual = $registro->estado ?? 'Buen estado';
                                                
                                                if (str_contains($estadoActual, 'Dañado - Cambiado')) {
                                                    $color = 'cell-changed';
                                                } elseif (str_contains($estadoActual, 'Dañado')) {
                                                    $color = 'cell-danger';
                                                } elseif (str_contains($estadoActual, 'Desgaste')) {
                                                    $color = 'cell-warning';
                                                } else {
                                                    $color = 'cell-ok';
                                                }
                                                
                                                if($registro->created_at && $registro->created_at->gt(now()->subDays(3))) {
                                                    $isNew = true;
                                                }
                                                
                                                $imagenes = $registro->evidencia_fotos ?? null;
                                                if (is_string($imagenes)) {
                                                    $imagenes = json_decode($imagenes, true) ?? [];
                                                } elseif (is_array($imagenes)) {
                                                    $imagenes = $imagenes;
                                                } else {
                                                    $imagenes = [];
                                                }
                                            }
                                        @endphp
                                        
                                        <td class="border px-3 py-2 align-top {{ $hasData ? $color : 'cell-empty' }} {{ $hasData ? 'analysis-cell' : 'analysis-cell no-data' }}" 
                                            @if($hasData)
                                            onclick="openAnalysisDetail({{ json_encode([
                                                'id' => $registro->id,
                                                'linea' => $registro->linea->nombre ?? 'Sin nombre',
                                                'componente' => $registro->componente->nombre ?? 'Sin nombre',
                                                'componente_codigo' => $registro->componente->codigo ?? '',
                                                'reductor' => $registro->reductor,
                                                'lado' => $registro->lado ?? null,
                                                'fecha_analisis' => isset($registro->fecha_analisis) ? $registro->fecha_analisis->format('d/m/Y') : '',
                                                'numero_orden' => $registro->numero_orden,
                                                'estado' => $registro->estado ?? 'Buen estado',
                                                'actividad' => $registro->actividad,
                                                'imagenes' => $imagenes ?? [],
                                                'color' => $color,
                                                'created_at' => isset($registro->created_at) ? $registro->created_at->format('d/m/Y H:i') : '',
                                                'updated_at' => isset($registro->updated_at) ? $registro->updated_at->format('d/m/Y H:i') : '',
                                                'is_new' => $isNew,
                                                'total_historial' => $totalHistorial,
                                                'edit_url' => route('analisis-lavadora.edit', [
                                                    'analisislavadora' => $registro->id
                                                ]),
                                                'historial_url' => route('analisis-lavadora.historial', [
                                                    'linea_id' => $registro->linea_id,
                                                    'componente_id' => $c->id,
                                                    'reductor' => $r
                                                ])
                                            ]) }})"
                                            @endif>
                                            
                                            @if($hasData)
                                                @if($isNew)
                                                    <div class="badge-new">NUEVO</div>
                                                @endif
                                                
                                                <div class="space-y-2">
                                                    <div class="flex flex-col">
                                                        <div class="flex items-center gap-1 mb-1">
                                                            <i class="fas fa-calendar text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-semibold text-gray-700">
                                                                {{ isset($registro->fecha_analisis) ? $registro->fecha_analisis->format('d/m/Y') : '' }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <i class="fas fa-hashtag text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-bold text-gray-800">
                                                                Orden #{{ $registro->numero_orden }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        @php
                                                            $estadoActual = $registro->estado ?? 'Buen estado';
                                                            
                                                            if (str_contains($estadoActual, 'Dañado - Cambiado')) {
                                                                $statusClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                                                $icon = 'fa-exchange-alt';
                                                            } elseif (str_contains($estadoActual, 'Dañado')) {
                                                                $statusClass = 'bg-red-100 text-red-800 border-red-200';
                                                                $icon = 'fa-times-circle';
                                                            } elseif (str_contains($estadoActual, 'Desgaste')) {
                                                                $statusClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                                                $icon = 'fa-exclamation-triangle';
                                                            } else {
                                                                $statusClass = 'bg-green-100 text-green-800 border-green-200';
                                                                $icon = 'fa-check-circle';
                                                            }
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $statusClass }}">
                                                            <i class="fas {{ $icon }} mr-1"></i>
                                                            {{ Str::limit($estadoActual, 20) }}
                                                        </span>
                                                    </div>
                                                    
                                                    <div>
                                                        <p class="text-gray-700 text-xs">
                                                            {{ Str::limit($registro->actividad, 80) }}
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="flex flex-col gap-1 mt-3">
                                                        @if(!empty($imagenes) && count($imagenes) > 0)
                                                            <button onclick="event.stopPropagation(); openAllImages(
                                                                @json($imagenes),
                                                                @json(isset($registro->fecha_analisis) ? $registro->fecha_analisis->format('d/m/Y') : ''),
                                                                @json($registro->numero_orden),
                                                                @json($registro->estado ?? 'Buen estado')
                                                            )"
                                                                class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition text-xs font-medium">
                                                                <i class="fas fa-images mr-1"></i>
                                                                {{ count($imagenes) }} img
                                                            </button>
                                                        @endif
                                                        {{--    
                                                        <a href="{{ route('analisis-lavadora.edit', [
                                                            'analisislavadora' => $registro->id,
                                                            'linea_id' => request('linea_id', ''),
                                                            'componente_id' => request('componente_id', ''),
                                                            'reductor' => request('reductor', ''),
                                                            'fecha' => request('fecha', '')
                                                        ]) }}"
                                                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-edit"></i>
                                                            Editar
                                                        </a>
                                                        --}}
                                                        <a href="{{ route('analisis-lavadora.create-quick', [
                                                                'linea_id' => $registro->linea_id,
                                                                'componente_codigo' => $c->id,
                                                                'reductor' => $r,
                                                                'fecha' => now()->format('Y-m')
                                                                ]) }}"
                                                                class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 transition text-xs font-medium"
                                                                onclick="event.stopPropagation();">
                                                                    <i class="fas fa-plus"></i>
                                                                    Nuevo Registro
                                                        </a>
                                                        {{--
                                                         
                                                        @if($totalHistorial > 1)
                                                            <a href="{{ route('analisis-lavadora.historial', [
                                                                    'linea_id' => $registro->linea_id,
                                                                    'componente_id' => $c->id,
                                                                    'reductor' => $r
                                                                ]) }}"
                                                            class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-purple-100 text-purple-700 rounded hover:bg-purple-200 transition text-xs font-medium"
                                                            onclick="event.stopPropagation();">
                                                                <i class="fas fa-history"></i>
                                                                Historial ({{ $totalHistorial }})
                                                            </a>
                                                        @endif
                                                        --}}
                                                    </div>
                                                    
                                                </div>
                                            @else
                                                <div class="empty-cell">
                                                    <div class="empty-cell-icon">
                                                        <i class="fas fa-clipboard"></i>
                                                    </div>
                                                    <p class="text-gray-500 text-xs mb-3">Sin análisis</p>
                                                    
                                                    @if($lineaMostrar)
                                                        <a href="{{ route('analisis-lavadora.create-quick',[
                                                            'linea_id' => $lineaMostrar->id,
                                                            'componente_codigo' => $c->id,
                                                            'reductor' => $r,
                                                            'fecha' => request('fecha', now()->format('Y-m'))]) }}"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-plus"></i>
                                                            
                                                        </a>
                                                    @else
                                                        <a href="{{ route('analisis-lavadora.select-linea') }}"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-plus"></i>
                                                            Nuevo
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-info-circle text-3xl mb-4"></i>
                    <p>No hay componentes o reductores para mostrar</p>
                    <p class="text-sm text-gray-400 mt-2">Selecciona una lavadora para ver sus análisis</p>
                </div>
            @endif
        </div>
    @else
        {{-- VISTA INICIAL --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="text-blue-400 mb-4">
                <i class="fas fa-clipboard-list text-5xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Bienvenido al Sistema de Análisis de Componentes</h3>
            <p class="text-gray-500 mb-6">Selecciona una lavadora para ver los análisis o crea uno nuevo.</p>
            
            <div class="flex flex-wrap justify-center gap-3 mb-8">
                @foreach($lineasFiltradas as $l)
                    <div class="linea-item {{ request('linea_id') == $l->id ? 'active' : '' }}" 
                         onclick="selectLinea('{{ $l->id }}')">
                        <i class="fas fa-washing-machine"></i>
                        {{ $l->nombre }}
                    </div>
                @endforeach
            </div>
            
            <a href="{{ route('analisis-lavadora.select-linea') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition shadow-lg hover:shadow-xl">
                <i class="fas fa-plus-circle"></i>
                Comenzar Nuevo Análisis
            </a>
        </div>
    @endif
</div>

{{-- MODALES MEJORADOS --}}
<div id="analysisDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300"
     onclick="event.stopPropagation()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300 scale-100 animate-modalIn">
        {{-- HEADER CON GRADIENTE MEJORADO --}}
       <div class="bg-gradient-to-r from-[#1F4E78] via-[#1F4E78] to-[#1F4E78] text-white px-8 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-clipboard-list text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl">
                            <span id="detailModalTitle">Detalle del Análisis</span>
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">Información completa del registro</p>
                    </div>
                </div>
                <button onclick="event.stopPropagation(); closeAnalysisDetailModal()" 
                        class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 transition-all flex items-center justify-center group">
                    <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                </button>
            </div>
        </div>
        
        {{-- CONTENIDO CON SCROLL PERSONALIZADO --}}
        <div class="p-8 overflow-auto max-h-[calc(90vh-100px)] custom-scrollbar">
            {{-- TARJETAS DE INFORMACIÓN MEJORADAS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {{-- Lavadora --}}
                <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl p-5 border border-blue-100 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-washing-machine text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Lavadora</p>
                            <p id="detail-linea" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                {{-- Componente --}}
                <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-5 border border-purple-100 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-cog text-purple-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-purple-600 uppercase tracking-wider">Componente</p>
                            <p id="detail-componente" class="font-bold text-gray-800 text-lg mt-1"></p>
                            <p id="detail-componente-codigo" class="text-xs text-gray-500 mt-1"></p>
                        </div>
                    </div>
                </div>

                {{-- Reductor --}}
                <div class="bg-gradient-to-br from-amber-50 to-white rounded-xl p-5 border border-amber-100 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-amber-100 p-3 rounded-lg">
                            <i class="fas fa-compress-alt text-amber-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Reductor</p>
                            <p id="detail-reductor" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                {{-- Lado del Análisis --}}
                <div class="bg-gradient-to-br from-emerald-50 to-white rounded-xl p-5 border border-emerald-100 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-emerald-100 p-3 rounded-lg">
                            <i class="fas fa-arrows-alt-h text-emerald-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Lado</p>
                            <p id="detail-lado" class="font-bold text-gray-800 text-lg mt-1"></p>
                            <div id="detail-lado-badge-container" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                {{-- Fecha --}}
                <div class="bg-gradient-to-br from-rose-50 to-white rounded-xl p-5 border border-rose-100 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-rose-100 p-3 rounded-lg">
                            <i class="far fa-calendar-alt text-rose-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Fecha</p>
                            <p id="detail-fecha" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                {{-- Número de Orden --}}
                <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl p-5 border border-indigo-100 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-indigo-100 p-3 rounded-lg">
                            <i class="fas fa-hashtag text-indigo-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Orden</p>
                            <p id="detail-orden" class="font-bold text-indigo-700 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ESTADO Y ACTIVIDAD EN TARJETAS DESTACADAS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                {{-- Estado --}}
                <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-5 border border-gray-200 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-gray-200 p-2 rounded-lg">
                            <i class="fas fa-clipboard-check text-gray-700"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700">Estado de Analisis</h4>
                    </div>
                    <div class="flex justify-center">
                        <div id="detail-estado" class="status-badge-enhanced px-6 py-3 text-base"></div>
                    </div>
                </div>

                {{-- Actividad --}}
                <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-5 border border-gray-200 shadow-sm md:col-span-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-gray-200 p-2 rounded-lg">
                            <i class="fas fa-sticky-note text-gray-700"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700">Actividad</h4>
                    </div>
                    <div class="bg-white rounded-lg p-4 border-l-4 border-blue-500 shadow-inner">
                        <p id="detail-actividad" class="text-gray-700 whitespace-pre-line leading-relaxed"></p>
                    </div>
                </div>
            </div>
            
            {{-- SECCIÓN DE IMÁGENES MEJORADA --}}
            <div id="detail-images-section" class="mt-6 hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 rounded-t-xl">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <i class="fas fa-images text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Evidencia Fotográfica</h4>
                            <p class="text-blue-100 text-sm">Documentación visual del análisis</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-b-xl p-6 border-x-2 border-b-2 border-gray-200">
                    <div id="detail-image-grid" class="image-grid-enhanced"></div>
                </div>
            </div>

            {{-- BOTONES DE ACCIÓN MEJORADOS --}}
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <a id="detail-edit-btn" 
                   href="#" 
                   class="px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl hover:from-amber-600 hover:to-amber-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2 font-medium">
                    <i class="fas fa-edit"></i>
                    Editar Análisis
                </a>
                <a id="detail-historial-btn"
                   href="#"
                   class="px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl hover:from-purple-600 hover:to-purple-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2 font-medium hidden">
                    <i class="fas"></i>
                    <span id="detail-historial-text">Ver Historial</span>
                </a>
                <button onclick="closeAnalysisDetailModal()" 
                        class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all shadow-md hover:shadow-lg flex items-center gap-2 font-medium">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE IMÁGENES MEJORADO --}}
<div id="allImagesModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300"
     onclick="closeAllImagesModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300 scale-100 animate-modalIn">
        <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 text-white px-8 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl">
                            <span id="modalTitle">Galería de Imágenes</span>
                        </h3>
                        <p class="text-blue-100 text-sm">Evidencia fotográfica del análisis</p>
                    </div>
                </div>
                <button onclick="closeAllImagesModal()" 
                        class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 transition-all flex items-center justify-center group">
                    <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-100px)] custom-scrollbar">
            <div id="imageGrid" class="image-grid-enhanced"></div>
            <div id="emptyImages" class="empty-images-enhanced hidden">
                <div class="text-center py-16">
                    <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-image text-4xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 text-lg">No hay imágenes disponibles</p>
                    <p class="text-gray-400 text-sm mt-2">Este análisis no cuenta con evidencia fotográfica</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE IMAGEN INDIVIDUAL MEJORADO --}}
<div id="singleImageModal" class="fixed inset-0 bg-black/95 hidden items-center justify-center z-[60] p-4 transition-all duration-300"
     onclick="closeSingleImageModal()">
    <div class="relative max-w-6xl w-full h-full flex items-center justify-center">
        <button onclick="closeSingleImageModal()" 
                class="absolute top-6 right-6 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-white/30 transition-all z-10 group">
            <i class="fas fa-times group-hover:rotate-90 transition-transform"></i>
        </button>
        <div class="relative">
            <img id="singleModalImg" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl border-4 border-white/20">
            <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 bg-black/50 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm">
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

// FUNCIONES DE FILTROS
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

function selectLinea(lineaId) {
    document.getElementById('lineaInput').value = lineaId;
    document.getElementById('filterForm').submit();
}

function selectLineaFromModal(lineaId) {
    closeLineasModal();
    selectLinea(lineaId);
}

// FUNCIONES PARA EL MODAL DE VER MÁS
function showAllLineas() {
    const modal = document.getElementById('lineasModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLineasModal() {
    const modal = document.getElementById('lineasModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// FUNCIONES PRINCIPALES MEJORADAS
function openAnalysisDetail(analysisData) {
    showLoading();
    currentAnalysisData = analysisData;
    
    document.getElementById('detail-linea').textContent = analysisData.linea;
    document.getElementById('detail-componente').textContent = analysisData.componente;
    document.getElementById('detail-componente-codigo').textContent = analysisData.componente_codigo;
    document.getElementById('detail-reductor').textContent = analysisData.reductor;
    
    // Mostrar información del lado con badge mejorado
    const ladoElement = document.getElementById('detail-lado');
    const ladoBadgeContainer = document.getElementById('detail-lado-badge-container');
    
    if (analysisData.lado) {
        ladoElement.textContent = analysisData.lado === 'VAPOR' ? 'Lado Vapor' : 'Lado Pasillo';
        
        const badge = document.createElement('span');
        badge.className = `lado-badge ${analysisData.lado === 'VAPOR' ? 'vapor' : 'pasillo'}`;
        badge.innerHTML = analysisData.lado === 'VAPOR' ? 
            '<i class="fas fa-wind"></i> Vapor' : 
            '<i class="fas fa-walking"></i> Pasillo';
        ladoBadgeContainer.innerHTML = '';
        ladoBadgeContainer.appendChild(badge);
    } else {
        ladoElement.textContent = 'No especificado';
        ladoBadgeContainer.innerHTML = '<span class="text-gray-400 text-sm bg-gray-100 px-4 py-2 rounded-full">-</span>';
    }
    
    document.getElementById('detail-fecha').textContent = analysisData.fecha_analisis;
    document.getElementById('detail-orden').textContent = analysisData.numero_orden;
    document.getElementById('detail-actividad').textContent = analysisData.actividad;
    
    // Estado con badge mejorado
    const estadoElement = document.getElementById('detail-estado');
    estadoElement.textContent = analysisData.estado;
    
    let estadoClass = '';
    let estadoIcon = '';
    
    if (analysisData.color === 'cell-ok') {
        estadoClass = 'ok';
        estadoIcon = '<i class="fas fa-check-circle mr-2"></i>';
    } else if (analysisData.color === 'cell-warning') {
        estadoClass = 'warning';
        estadoIcon = '<i class="fas fa-exclamation-triangle mr-2"></i>';
    } else if (analysisData.color === 'cell-danger') {
        estadoClass = 'danger';
        estadoIcon = '<i class="fas fa-times-circle mr-2"></i>';
    } else {
        estadoClass = 'changed';
        estadoIcon = '<i class="fas fa-exchange-alt mr-2"></i>';
    }
    
    estadoElement.className = `status-badge-enhanced ${estadoClass}`;
    estadoElement.innerHTML = estadoIcon + analysisData.estado;
    
    document.getElementById('detail-edit-btn').href = analysisData.edit_url;
    const historialBtn = document.getElementById('detail-historial-btn');
    const historialText = document.getElementById('detail-historial-text');

    if (analysisData.total_historial > 1) {
        historialBtn.classList.remove('hidden');
        historialBtn.href = analysisData.historial_url;
        historialText.innerHTML = `<i class="fas fa-history mr-2"></i>Ver Historial (${analysisData.total_historial})`;
    } else {
        historialBtn.classList.add('hidden');
    }
    
    const imagesSection = document.getElementById('detail-images-section');
    if (analysisData.imagenes && analysisData.imagenes.length > 0) {
        imagesSection.classList.remove('hidden');
        buildDetailImageGridEnhanced(analysisData.imagenes);
    } else {
        imagesSection.classList.add('hidden');
    }
    
    document.getElementById('analysisDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function buildDetailImageGridEnhanced(imagenes) {
    const grid = document.getElementById('detail-image-grid');
    grid.innerHTML = '';
    
    imagenes.forEach((path, index) => {
        const item = document.createElement('div');
        item.className = 'image-item';
        item.innerHTML = `
            <div class="image-number">#${index + 1}</div>
            <img src="{{ Storage::url('') }}${path}" class="grid-image" onclick="openSingleImage('${path}', ${index})">
            <div class="image-info">
                <button class="download-image-btn" onclick="event.stopPropagation(); downloadSingleImage('${path}', ${index})">
                    <i class="fas fa-download"></i>
                    Descargar
                </button>
            </div>
        `;
        grid.appendChild(item);
    });
}

function openAllImages(imagenes, fecha, orden, estado) {
    showLoading();
    currentImages = Array.isArray(imagenes) ? imagenes : [];
    
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
            item.innerHTML = `
                <div class="image-number">#${index + 1}</div>
                <img src="{{ Storage::url('') }}${path}" class="grid-image" onclick="openSingleImage('${path}', ${index})">
                <div class="image-info">
                    <button class="download-image-btn" onclick="event.stopPropagation(); downloadSingleImage('${path}', ${index})">
                        <i class="fas fa-download"></i>
                        Descargar
                    </button>
                </div>
            `;
            grid.appendChild(item);
        });
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function openSingleImage(imagePath, index) {
    currentImageIndex = index;
    const modal = document.getElementById('singleImageModal');
    const img = document.getElementById('singleModalImg');
    const counter = document.getElementById('currentImageCounter');
    
    img.src = `{{ Storage::url('') }}${imagePath}`;
    
    if (currentImages.length > 0) {
        counter.textContent = `${index + 1} / ${currentImages.length}`;
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function downloadSingleImage(imagePath, index) {
    const link = document.createElement('a');
    link.href = `{{ Storage::url('') }}${imagePath}`;
    link.download = `imagen-${index + 1}.jpg`;
    link.click();
}

function closeAnalysisDetailModal() {
    document.getElementById('analysisDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function closeAllImagesModal() {
    document.getElementById('allImagesModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function closeSingleImageModal() {
    document.getElementById('singleImageModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

// EVENT LISTENERS
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSingleImageModal();
        closeAllImagesModal();
        closeAnalysisDetailModal();
        closeLineasModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Resaltar nuevo registro si existe en URL
    if (window.location.hash === '#new') {
        const newCell = document.querySelector('.badge-new');
        if (newCell) {
            newCell.closest('.analysis-cell').classList.add('cell-highlight');
        }
    }
});

// Cerrar modal al hacer clic fuera del contenido
document.getElementById('lineasModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLineasModal();
    }
});
</script>
@endsection