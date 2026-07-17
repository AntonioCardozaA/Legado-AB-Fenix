@once
<style>
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
    .cell-review { background-color: #fefce8; border-left: 4px solid var(--warning-yellow); }
    .cell-warning { background-color: #fff7ed; border-left: 4px solid #f97316; }
    .cell-danger { background-color: #fef2f2; border-left: 4px solid var(--danger-red); }
    .cell-changed { background-color: #eff6ff; border-left: 4px solid var(--changed-blue); }
    .cell-empty { background-color: var(--light-gray); }
    .cell-header { background-color: #eff6ff; }

    .compact-table td,
    .compact-table th {
        padding: 8px !important;
        font-size: 0.75rem !important;
        min-width: 120px;
    }

    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 100%;
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        flex-wrap: wrap;
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
        min-width: 0;
    }

    .linea-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        max-width: 100%;
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
        overflow-wrap: anywhere;
        text-align: center;
    }

    .linea-item i {
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

    .linea-item.active i,
    .linea-item.active .etq-presentations-names {
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
        min-width: 0;
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

    .filter-link:hover,
    .filter-link.active {
        background: #f8fafc;
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
        text-decoration: none;
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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        min-width: 0;
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

    .filter-select,
    .filter-input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        color: #1e293b;
        background: white;
        transition: all 0.2s ease;
    }

    .filter-select:focus,
    .filter-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 83, 192, 0.1);
    }

    .table-header-container,
    .lavadora-card-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
    }

    .table-header-container {
        padding: 16px 20px;
        border-radius: 12px 12px 0 0;
    }

    .lavadoras-section {
        margin-top: 30px;
    }

    .lavadora-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 100%;
    }

    .lavadora-card-header {
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }

    .lavadora-card-header > * {
        min-width: 0;
    }

    .lavadora-card-header h3 {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .lavadora-card-header .badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .table-wrapper {
        position: relative;
        overflow: auto;
        border: 1px solid var(--medium-gray);
        border-radius: 8px;
        max-height: 650px;
        width: 100%;
        max-width: 100%;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
    }

    .lavadora-card .table-wrapper {
        border-radius: 0;
        border: none;
        border-top: 1px solid #e2e8f0;
    }

    .table-wrapper > table,
    .compact-table {
        width: max-content;
        min-width: 100%;
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

    .component-header,
    .reductor-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }

    .component-name,
    .reductor-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .component-code,
    .reductor-label {
        font-size: 9px;
        color: var(--dark-gray);
        background: #f3f4f6;
        padding: 2px 4px;
        border-radius: 3px;
    }

    .component-industrial-icon {
        width: 4.5rem;
        height: 4.5rem;
        margin-top: 0.35rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #eef2ff, #eff6ff);
        color: #2563eb;
        border: 1px solid #dbeafe;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        transition: transform 0.2s ease;
    }

    .component-industrial-icon:hover {
        transform: scale(1.06);
    }

    .analysis-cell {
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        min-height: 120px;
    }

    .analysis-cell:not(.no-data):hover {
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.22);
    }

    .analysis-cell.no-data {
        cursor: default;
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

    .empty-cell-icon {
        font-size: 24px;
        margin-bottom: 8px;
        color: #d1d5db;
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

    .search-target-line {
        border-color: #2563eb;
        box-shadow: 0 18px 35px -22px rgba(37, 99, 235, 0.75);
    }

    .search-target-header,
    .search-target-cell {
        outline: 3px solid rgba(37, 99, 235, 0.85);
        outline-offset: -3px;
        box-shadow: inset 0 0 0 9999px rgba(219, 234, 254, 0.32);
        position: relative;
        z-index: 3;
    }

    .cell-highlight {
        animation: highlight-pulse 2s ease-out;
    }

    @keyframes highlight-pulse {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .animate-modalIn {
        animation: modalIn 0.3s ease-out;
    }

    .lado-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 12px;
        font-family: monospace;
    }

    .lado-badge.vapor {
        background-color: #f3f4f6;
        color: #1363d3;
        border: 1px solid #9ca3af;
    }

    .lado-badge.pasillo {
        background-color: #e5e7eb;
        color: #1363d3;
        border: 1px solid #6b7280;
    }

    .image-grid-enhanced {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
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

    .image-number {
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

    .image-info {
        padding: 8px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }

    .download-image-btn {
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

    .download-image-btn:hover {
        background: #1f2937;
    }

    @media (max-width: 768px) {
        .filters-section {
            padding: 16px;
            border-radius: 12px;
        }

        .lineas-grid { gap: 8px; }
        .linea-item { padding: 6px 16px; font-size: 13px; }
        .filters-row { flex-direction: column; align-items: stretch; }
        .filter-link,
        .btn-apply,
        .btn-clear {
            justify-content: center;
            width: 100%;
            margin-left: 0;
        }

        .lavadora-card-header {
            align-items: stretch;
            padding: 14px;
        }

        .lavadora-card-header > div {
            width: 100%;
        }

        .compact-table td,
        .compact-table th {
            min-width: 100px;
            font-size: 0.7rem !important;
            padding: 6px !important;
        }

        .component-industrial-icon {
            width: 3.5rem;
            height: 3.5rem;
        }

        .image-grid-enhanced {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .grid-image { height: 120px; }
    }

    @media (max-width: 480px) {
        .image-grid-enhanced { grid-template-columns: 1fr; }
    }
</style>
@endonce
