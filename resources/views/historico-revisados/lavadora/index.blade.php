@extends('layouts.app')

@section('title', 'Lavadoras')

@section('content')
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
    }

    .historico-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    .historico-container *,
    .historico-container *::before,
    .historico-container *::after,
    .modal *,
    .modal *::before,
    .modal *::after {
        box-sizing: border-box;
    }

    /* LÍNEAS EN FORMA DE BOTONES */
    .lineas-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
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

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .linea-btn {
        display: inline-flex;
        align-items: center;
        padding: 10px 24px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 15px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
    }

    .linea-btn i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-2px);
    }

    .linea-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
    }

    .linea-btn.active i {
        color: white;
    }

    /* TABLA DE COMPONENTES */
    .componentes-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        margin-bottom: 24px;
    }

    .table-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-header .badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
    }

    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th:nth-child(1),
    .table td:nth-child(1) {
        width: 50%;
    }

    .table th:nth-child(2),
    .table td:nth-child(2),
    .table th:nth-child(3),
    .table td:nth-child(3) {
        width: 16%;
    }

    .table th:nth-child(4),
    .table td:nth-child(4) {
        width: 18%;
        min-width: 132px;
        white-space: nowrap;
    }

    .table th {
        background: #f8fafc;
        padding: 16px;
        font-weight: 600;
        font-size: 14px;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
        overflow-wrap: normal;
        word-break: normal;
    }

    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .table td:last-child .btn {
        justify-content: center;
        min-width: 84px;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .componente-nombre {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #1e293b;
    }

    .componente-imagen {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .componente-imagen:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .componente-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 4px;
    }

    .componente-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .componente-nombre-texto {
        font-weight: 600;
        color: #1f2937;
        overflow-wrap: anywhere;
    }

    .componente-icono {
        width: 32px;
        height: 32px;
        background: #e2e8f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
    }

    .cantidad-badge {
        background: #e2e8f0;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        color: #1e293b;
    }

    .progreso-numerico {
        font-weight: 600;
        font-size: 15px;
    }

    /* BARRAS DE PROGRESO */
    .progress-container {
        width: 100%;
        background: #e2e8f0;
        border-radius: 8px;
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

    .progress-bar.bg-success {
        background: linear-gradient(90deg, #10b981, #059669) !important;
    }
    
    .progress-bar.bg-info {
        background: linear-gradient(90deg, #3b82f6, #2563eb) !important;
    }
    
    .progress-bar.bg-warning {
        background: linear-gradient(90deg, #f59e0b, #d97706) !important;
    }
    
    .progress-bar.bg-danger {
        background: linear-gradient(90deg, #ef4444, #dc2626) !important;
    }

    .progress-label {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #1e293b;
        font-size: 12px;
        font-weight: 600;
        z-index: 5;
    }

    /* GRÁFICA DE BARRAS VERTICALES */
    .grafica-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        margin-bottom: 24px;
    }

    .grafica-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .grafica-vertical-container {
        display: flex;
        justify-content: space-around;
        align-items: flex-end;
        min-height: 300px;
        padding: 20px 10px;
        background: #f8fafc;
        border-radius: 12px;
        margin-bottom: 20px;
        position: relative;
        border: 1px solid #e2e8f0;
    }

    .grafica-vertical-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: repeating-linear-gradient(
            transparent,
            transparent 49px,
            rgba(0, 0, 0, 0.05) 49px,
            rgba(0, 0, 0, 0.05) 50px
        );
        pointer-events: none;
    }

    .grafica-columna {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        max-width: 120px;
        position: relative;
        z-index: 2;
    }

    .grafica-barra-vertical {
        width: 60px;
        height: 200px;
        background: #e2e8f0;
        border-radius: 8px 8px 0 0;
        position: relative;
        margin-bottom: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .grafica-barra-vertical:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 12px rgba(0,0,0,0.15);
    }

    .grafica-barra-vertical .barra-relleno {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 0%;
        transition: height 1s ease;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding-bottom: 5px;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }

    .grafica-barra-vertical .barra-relleno.bg-success {
        background: linear-gradient(0deg, #10b981, #059669);
    }
    
    .grafica-barra-vertical .barra-relleno.bg-info {
        background: linear-gradient(0deg, #3b82f6, #2563eb);
    }
    
    .grafica-barra-vertical .barra-relleno.bg-warning {
        background: linear-gradient(0deg, #f59e0b, #d97706);
    }
    
    .grafica-barra-vertical .barra-relleno.bg-danger {
        background: linear-gradient(0deg, #ef4444, #dc2626);
    }

    .grafica-etiqueta {
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        color: #475569;
        margin-top: 8px;
        max-width: 100px;
        word-wrap: break-word;
    }

    .grafica-valor {
        font-size: 11px;
        font-weight: 600;
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        background: rgba(0,0,0,0.2);
        padding: 2px 6px;
        border-radius: 12px;
        margin-bottom: 4px;
    }

    .grafica-referencias {
        display: flex;
        justify-content: space-between;
        padding: 10px 20px;
        border-top: 1px solid #e2e8f0;
        margin-top: 10px;
    }

    .grafica-referencias span {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }

    .grafica-leyenda {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }

    .leyenda-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .leyenda-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }

    .leyenda-color.success { background: linear-gradient(0deg, #10b981, #059669); }
    .leyenda-color.info { background: linear-gradient(0deg, #3b82f6, #2563eb); }
    .leyenda-color.warning { background: linear-gradient(0deg, #f59e0b, #d97706); }
    .leyenda-color.danger { background: linear-gradient(0deg, #ef4444, #dc2626); }

    .leyenda-texto {
        font-size: 12px;
        color: #475569;
    }

    /* TARJETAS DE RESUMEN */
    .resumen-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .resumen-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .resumen-icono {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .resumen-icono.total { background: #e2e8f0; color: #475569; }
    .resumen-icono.revisado { background: #dbeafe; color: #2563eb; }
    .resumen-icono.porcentaje { background: #d1fae5; color: #059669; }

    .resumen-info h4 {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 4px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .resumen-info .valor {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }

    .resumen-info .subvalor {
        font-size: 14px;
        color: #64748b;
        margin-top: 4px;
    }

    /* BOTONES DE ACCIÓN */
    .acciones {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 24px;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
    }

    .periodicidad-panel {
        overflow: hidden;
    }

    .periodicidad-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .periodicidad-auto-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 8px;
        background: #ecfdf5;
        color: #047857;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #a7f3d0;
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

    .btn-secondary {
        background: #e2e8f0;
        color: #475569;
    }

    .btn-secondary:hover {
        background: #cbd5e1;
    }

    .btn-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .btn-info {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .btn-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    /* MODAL PARA DETALLE DE COMPONENTE */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
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
        max-width: 500px;
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        overflow-wrap: anywhere;
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
        border: 1px solid #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 0 0 36px;
    }

    .modal-close:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    /* Colores para texto */
    .text-success { color: #10b981 !important; }
    .text-info { color: #3b82f6 !important; }
    .text-warning { color: #f59e0b !important; }
    .text-danger { color: #ef4444 !important; }

    @media (max-width: 768px) {
        .historico-container {
            padding: 12px;
        }

        .lineas-section,
        .componentes-table,
        .grafica-section {
            border-radius: 10px;
        }

        .lineas-section {
            padding: 14px;
            margin-bottom: 16px;
        }

        .lineas-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .linea-btn {
            justify-content: center;
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
        }

        .resumen-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .resumen-card {
            align-items: flex-start;
            padding: 14px;
            border-radius: 10px;
        }

        .resumen-icono {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            font-size: 20px;
        }

        .resumen-info h4 {
            font-size: 12px;
        }

        .resumen-info .valor {
            font-size: 24px;
        }

        .table-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 8px;
            padding: 14px;
        }

        .table-header h3 {
            align-items: flex-start;
            font-size: 15px;
            line-height: 1.35;
        }

        .table-responsive {
            overflow-x: visible;
        }

        .table,
        .table tbody,
        .table tr,
        .table td {
            display: block;
        }

        .table th:nth-child(1),
        .table td:nth-child(1),
        .table th:nth-child(2),
        .table td:nth-child(2),
        .table th:nth-child(3),
        .table td:nth-child(3),
        .table th:nth-child(4),
        .table td:nth-child(4) {
            min-width: 0;
            white-space: normal;
            width: 100%;
        }

        .table thead {
            display: none;
        }

        .table tbody {
            padding: 12px;
            background: #f8fafc;
        }

        .table tbody tr {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            margin-bottom: 12px;
            padding: 12px;
        }

        .table tbody tr:last-child {
            margin-bottom: 0;
        }

        .table tbody tr:hover {
            background: white;
        }

        .table td {
            align-items: flex-start;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 10px 0;
            width: 100%;
        }

        .table td::before {
            color: #64748b;
            content: attr(data-label);
            flex: 0 0 112px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .table td:first-child {
            display: block;
            padding-top: 0;
        }

        .table td:first-child::before {
            display: block;
            flex: none;
            margin-bottom: 8px;
            max-width: none;
        }

        .table td:last-child {
            align-items: center;
            border-bottom: 0;
            padding-bottom: 0;
        }

        .table td:last-child .btn {
            justify-content: center;
            min-height: 40px;
            width: 100%;
        }

        .table td[colspan] {
            display: block;
            text-align: center;
        }

        .table td[colspan]::before {
            display: none;
        }

        .componente-nombre {
            align-items: flex-start;
            gap: 10px;
        }

        .componente-imagen {
            width: 44px;
            height: 44px;
        }

        .componente-nombre-texto {
            font-size: 14px;
            line-height: 1.3;
        }

        .cantidad-badge,
        .progreso-numerico {
            text-align: right;
        }

        .grafica-section {
            padding: 16px 12px;
        }

        .grafica-title {
            align-items: flex-start;
            font-size: 16px;
            line-height: 1.35;
            margin-bottom: 18px;
        }

        .grafica-vertical-container {
            flex-wrap: nowrap;
            gap: 14px;
            justify-content: flex-start;
            min-height: 250px;
            overflow-x: auto;
            padding: 16px 12px;
            scroll-snap-type: x proximity;
            -webkit-overflow-scrolling: touch;
        }

        .grafica-columna {
            flex: 0 0 86px;
            max-width: 86px;
            min-width: 86px;
            scroll-snap-align: start;
        }

        .grafica-barra-vertical {
            height: 180px;
            width: 50px;
        }

        .grafica-etiqueta {
            font-size: 11px;
            max-width: 84px;
        }

        .grafica-referencias {
            padding: 10px 4px;
        }

        .grafica-leyenda {
            gap: 12px;
            justify-content: flex-start;
        }

        .acciones,
        .periodicidad-actions {
            flex-direction: column;
            gap: 10px;
        }

        .acciones .btn,
        .periodicidad-actions .btn,
        .periodicidad-auto-badge {
            justify-content: center;
            min-height: 44px;
            text-align: center;
            width: 100%;
        }

        .periodicidad-panel {
            border-radius: 10px;
            padding: 14px !important;
        }

        .modal {
            align-items: flex-end;
            padding: 10px;
        }

        .modal-content {
            border-radius: 16px 16px 0 0;
            max-height: 92vh;
            max-width: none;
        }

        .modal-header {
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
        }

        .modal-header h3 {
            font-size: 16px;
            line-height: 1.35;
        }

        .modal-body {
            max-height: calc(92vh - 65px);
            padding: 16px;
        }
    }

    @media (max-width: 420px) {
        .historico-container {
            padding: 10px;
        }

        .lineas-grid {
            grid-template-columns: 1fr;
        }

        .table tbody {
            padding: 10px;
        }

        .table tbody tr {
            padding: 10px;
        }

        .table td {
            align-items: stretch;
            flex-direction: column;
            gap: 4px;
        }

        .table td::before {
            flex: none;
            max-width: none;
        }

        .cantidad-badge {
            align-self: flex-start;
        }

        .cantidad-badge,
        .progreso-numerico {
            text-align: left;
        }
    }
</style>

<div class="historico-container">
    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('lavadora.dashboard') }}" 
               class="inline-flex w-full items-center justify-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 sm:w-auto
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="flex items-start gap-2 text-xl font-bold text-gray-800 sm:items-center sm:text-2xl">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Histórico de Revisados
            </h1>
        </div>
    </div>

    {{-- SECCIÓN DE LÍNEAS DE LAVADORA --}}
    <div class="lineas-section">
        <div class="lineas-title">
            <i class="fas fa-washing-machine"></i>
            LÍNEAS DE LAVADORA
        </div>
        
        <div class="lineas-grid">
            @forelse($lineasLavadora as $linea)
                <a href="{{ route('historico-revisados.index', ['linea_id' => $linea->id]) }}" 
                   class="linea-btn {{ $lineaSeleccionada && $lineaSeleccionada->id == $linea->id ? 'active' : '' }}">
                    <i class="fas fa-washing-machine"></i>
                    {{ $linea->nombre }}
                </a>
            @empty
                <div class="text-gray-500 py-2">No hay líneas de lavadora disponibles</div>
            @endforelse
        </div>
    </div>

    {{-- TARJETAS DE RESUMEN --}}
    <div class="resumen-grid">
        <div class="resumen-card">
            <div class="resumen-icono total">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="resumen-info">
                <h4>Total Análisis</h4>
                <div class="valor">{{ $resumen['total_general'] }}</div>
                <div class="subvalor">En toda la línea</div>
            </div>
        </div>

        <div class="resumen-card">
            <div class="resumen-icono revisado">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="resumen-info">
                <h4>Análisis Realizados</h4>
                <div class="valor">{{ $resumen['revisado_general'] }}</div>
            </div>
        </div>

        <div class="resumen-card">
            <div class="resumen-icono porcentaje">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="resumen-info">
                <h4>Progreso General</h4>
                <div class="valor">{{ $resumen['porcentaje_general'] }}%</div>
            </div>
        </div>
    </div>

    
   @php
$rutasImagenes = [
    'SERVO_CHICO' => asset('images/componentes-lavadora/SERVO_CHICO.png'),
    'SERVO_GRANDE' => asset('images/componentes-lavadora/SERVO_GRANDE.png'),
    'BUJE_ESPIGA' => asset('images/componentes-lavadora/BUJE_ESPIGA.png'),
    'GUI_INF_TANQUE' => asset('images/componentes-lavadora/GUI_INF_TANQUE.png'),
    'GUI_INT_TANQUE' => asset('images/componentes-lavadora/GUI_INT_TANQUE.png'),
    'GUI_SUP_TANQUE' => asset('images/componentes-lavadora/GUI_SUP_TANQUE.png'),
    'CATARINAS' => asset('images/componentes-lavadora/CATARINAS.png'),
    'RV200' => asset('images/componentes-lavadora/RV200.png'),
    'RV200_SIN_FIN' => asset('images/componentes-lavadora/RV200_SIN_FIN.png'),
];
@endphp

{{-- TABLA DE COMPONENTES --}}
    <div class="componentes-table">
        <div class="table-header">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                ANÁLISIS DE {{ $lineaSeleccionada->nombre ?? 'LA LÍNEA SELECCIONADA' }}
            </h3>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th>Cantidad Total</th>
                        <th>Cantidad Revisada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estadisticas as $codigo => $data)
                        <tr>
                            <td data-label="Componente">
                                <div class="componente-nombre">
                                    <div class="componente-imagen">
                                        <img src="{{ $rutasImagenes[$codigo] ?? asset('images/componentes-lavadora/default.png') }}" 
                                             alt="{{ $data['nombre'] }}"
                                             class="componente-img"
                                             onerror="this.onerror=null; this.src='{{ asset('images/componentes-lavadora/default.png') }}';">
                                    </div>
                                    <div class="componente-info">
                                        <span class="componente-nombre-texto">{{ $data['nombre'] }}</span>
                                        @if(isset($data['periodo_meses']))
                                            <span class="text-xs text-gray-500">
                                                <i class="fas fa-clock"></i> Cada {{ $data['periodo_meses'] }} meses
                                                @if(!empty($data['ultima_revision_formateada']))
                                                    - Ultima: {{ $data['ultima_revision_formateada'] }}
                                                @endif
                                                @if(($data['pendientes_reset'] ?? 0) > 0)
                                                    - <span class="text-red-600">{{ $data['pendientes_reset'] }} reset pendiente</span>
                                                @elseif(!empty($data['estado_periodicidad_label']))
                                                    - {{ $data['estado_periodicidad_label'] }}
                                                @endif
                                                @if(isset($data['proximo_vencimiento']))
                                                    · Vence: {{ \Carbon\Carbon::parse($data['proximo_vencimiento'])->format('d/m/Y') }}
                                                @endif
                                            </span>
                                        @endif
                                        @if(!empty($data['desglose_lados']))
                                            <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-600">
                                                @foreach($data['desglose_lados'] as $lado => $ladoData)
                                                    <span class="inline-flex items-center gap-1 rounded border border-gray-200 bg-gray-50 px-2 py-0.5">
                                                        {{ $lado === 'VAPOR' ? 'Vapor' : 'Pasillo' }}:
                                                        {{ $ladoData['revisados'] ?? 0 }}/{{ $ladoData['total'] ?? 0 }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Cantidad total">
                                <span class="cantidad-badge">{{ $data['cantidad_total'] }}</span>
                            </td>
                            <td data-label="Revisados">
                                <span class="progreso-numerico text-{{ $data['color'] }}">
                                    {{ $data['cantidad_revisada'] }} / {{ $data['cantidad_total'] }}
                                </span>
                            </td>
                      
                            <td data-label="Acciones">
                                <button class="btn btn-sm btn-primary" 
                                        style="padding: 6px 12px; font-size: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px;"
                                        onclick="verDetalleComponente('{{ $codigo }}', '{{ $data['nombre'] }}', {{ $data['cantidad_revisada'] }}, {{ $data['cantidad_total'] }})">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" data-label="Estado" class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-3xl mb-2"></i>
                                <p>No hay componentes configurados para esta línea</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
{{-- GRÁFICA DE BARRAS VERTICALES --}}
@if(count($estadisticas) > 0)
<div class="grafica-section">
    <div class="grafica-title">
        <i class="fas fa-chart-bar text-blue-600"></i>
        GRÁFICA DE AVANCE POR ANÁLISIS
    </div>

    <div class="grafica-vertical-container">
        @php
            $maxAltura = 200;
            $maxPorcentaje = 100;
        @endphp
        
        @foreach($estadisticas as $codigo => $data)
            @php
                $alturaBarra = ($data['porcentaje'] / $maxPorcentaje) * $maxAltura;
                
                $nombreCorto = $data['nombre'];
                if (strlen($nombreCorto) > 20) {
                    if (strpos($nombreCorto, 'Baquelita') !== false) {
                        $nombreCorto = 'Buje Baquelita';
                    } elseif (strpos($nombreCorto, 'Sin Fin') !== false) {
                        $nombreCorto = 'Reductor S/F';
                    } elseif (strpos($nombreCorto, 'RV250') !== false || strpos($nombreCorto, 'RV200') !== false) {
                        $nombreCorto = 'RV250 S/F';
                    }
                }
            @endphp
            <div class="grafica-columna">
                <div class="grafica-barra-vertical" title="{{ $data['nombre'] }} ({{ $data['cantidad_revisada'] }}/{{ $data['cantidad_total'] }})">
                    <div class="barra-relleno bg-{{ $data['color'] }}" 
                         style="height: {{ $alturaBarra }}px;">
                        <span class="grafica-valor">{{ $data['porcentaje'] }}%</span>
                    </div>
                </div>
                <div class="grafica-etiqueta" title="{{ $data['nombre'] }}">
                    {{ $nombreCorto }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grafica-referencias">
        <span>0%</span>
        <span>25%</span>
        <span>50%</span>
        <span>75%</span>
        <span>100%</span>
    </div>

    <div class="grafica-leyenda">
        <div class="leyenda-item">
            <div class="leyenda-color success"></div>
            <span class="leyenda-texto">80-100%</span>
        </div>
        <div class="leyenda-item">
            <div class="leyenda-color info"></div>
            <span class="leyenda-texto">50-79%</span>
        </div>
        <div class="leyenda-item">
            <div class="leyenda-color warning"></div>
            <span class="leyenda-texto">20-49%</span>
        </div>
        <div class="leyenda-item">
            <div class="leyenda-color danger"></div>
            <span class="leyenda-texto">0-19%</span>
        </div>
    </div>
</div>
@endif

    {{-- BOTONES DE ACCIÓN --}}
    <div class="acciones">
        <a href="{{ route('analisis-lavadora.index', ['linea_id' => $lineaSeleccionada->id ?? '']) }}" 
           class="btn btn-primary">
            <i class="fas fa-chart-pie"></i>
            Ver Análisis Detallado
        </a>
        <button class="btn btn-success" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i>
            Actualizar Datos
        </button>
    </div>

    {{-- GESTIÓN DE PERIODICIDAD (Solo para administradores e ingenieros de mantenimiento) --}}
    @if(auth()->user()?->hasAnyRole(\App\Models\User::elevatedMaintenanceRoles()))
    <div class="periodicidad-panel mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <i class="fas fa-history text-blue-600"></i>
            Gestión de Periodicidad
        </h4>
        
        <div id="resetInfo" class="mb-3 text-sm">
            <div class="flex items-center gap-2">
                <div class="animate-pulse">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                </div>
                <span class="text-gray-600">Cargando información de restablecimientos...</span>
            </div>
        </div>
        
        <div class="periodicidad-actions">
            <span class="periodicidad-auto-badge">
                <i class="fas fa-check-circle"></i>
                Reset automático activo
            </span>

            <button class="btn btn-info" onclick="verProximosResets()"
                    style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                <i class="fas fa-calendar-alt"></i>
                Actualizar estado de resets
            </button>
        </div>
    </div>
    @endif
</div>


{{-- MODAL PARA DETALLE DE COMPONENTE --}}
<div id="componenteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-cog"></i>
                <span id="modalComponenteNombre">Detalle del Componente</span>
            </h3>
            <button onclick="cerrarModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="text-center mb-6">
                <img id="modalComponenteImagen" src="" 
                    alt="Componente" 
                    class="w-32 h-32 object-contain mx-auto mb-4">
                
                <p id="modalComponenteDesc" class="text-gray-600"></p>
            </div>
            <div id="modalComponenteInfo">
                <!-- Se llena con JavaScript -->
            </div>
            
            <div class="mt-6">
                <h4 class="font-semibold text-gray-700 mb-3">Progreso Actual</h4>
                <div class="progress-container" style="height: 32px; margin-bottom: 20px;">
                    <div id="modalProgressBar" class="progress-bar" style="width: 0%;">0%</div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-blue-600" id="modalRevisado">0</div>
                        <div class="text-xs text-gray-500">Revisados</div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-gray-700" id="modalTotal">0</div>
                        <div class="text-xs text-gray-500">Totales</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="#" id="modalVerAnalisisLink" class="btn btn-primary w-full justify-center" style="width: 100%; background: #3b82f6;">
                    <i class="fas fa-chart-pie"></i>
                    Ver Análisis de este Componente
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function verDetalleComponente(codigo, nombre, revisado, total) {
        // 👉 Imagen del componente
    const rutasImagenes = {
        'SERVO_CHICO': "{{ asset('images/componentes-lavadora/SERVO_CHICO.png') }}",
        'SERVO_GRANDE': "{{ asset('images/componentes-lavadora/SERVO_GRANDE.png') }}",
        'BUJE_ESPIGA': "{{ asset('images/componentes-lavadora/BUJE_ESPIGA.png') }}",
        'GUI_INF_TANQUE': "{{ asset('images/componentes-lavadora/GUI_INF_TANQUE.png') }}",
        'GUI_INT_TANQUE': "{{ asset('images/componentes-lavadora/GUI_INT_TANQUE.png') }}",
        'GUI_SUP_TANQUE': "{{ asset('images/componentes-lavadora/GUI_SUP_TANQUE.png') }}",
        'CATARINAS': "{{ asset('images/componentes-lavadora/CATARINAS.png') }}",
        'RV200': "{{ asset('images/componentes-lavadora/RV200.png') }}",
        'RV200_SIN_FIN': "{{ asset('images/componentes-lavadora/RV200_SIN_FIN.png') }}"
    };

    // 👉 Asignar imagen
    document.getElementById('modalComponenteImagen').src =
        rutasImagenes[codigo] || "{{ asset('images/componentes-lavadora/default.png') }}";

    // 👉 Descripción
    document.getElementById('modalComponenteDesc').textContent = nombre;
    const modal = document.getElementById('componenteModal');
    const porcentaje = total > 0 ? Math.round((revisado / total) * 100) : 0;
    
    document.getElementById('modalComponenteNombre').textContent = nombre;
    document.getElementById('modalRevisado').textContent = revisado;
    document.getElementById('modalTotal').textContent = total;
    
    const progressBar = document.getElementById('modalProgressBar');
    progressBar.style.width = porcentaje + '%';
    progressBar.textContent = porcentaje + '%';
    
    progressBar.className = 'progress-bar';
    if (porcentaje >= 80) {
        progressBar.classList.add('bg-success');
    } else if (porcentaje >= 50) {
        progressBar.classList.add('bg-info');
    } else if (porcentaje >= 20) {
        progressBar.classList.add('bg-warning');
    } else {
        progressBar.classList.add('bg-danger');
    }
    
    const lineaId = '{{ $lineaSeleccionada->id ?? '' }}';
    const link = document.getElementById('modalVerAnalisisLink');
    link.href = "{{ route('analisis-lavadora.index') }}?linea_id=" + lineaId + "&componente_id=" + codigo;
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('componenteModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('componenteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const barras = document.querySelectorAll('.barra-relleno');
    barras.forEach(barra => {
        const altura = barra.style.height;
        barra.style.height = '0';
        setTimeout(() => {
            barra.style.height = altura;
        }, 100);
    });
    
    // Cargar información de resets si existe el elemento
    if (document.getElementById('resetInfo')) {
        cargarInfoReset();
    }
});

// Funciones para gestión de periodicidad
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function cargarInfoReset() {
    const resetInfo = document.getElementById('resetInfo');
    const statusUrl = new URL('{{ route("historico-revisados.check-reset-status") }}', window.location.origin);
    const lineaId = @json($lineaSeleccionada->id ?? null);

    if (lineaId) {
        statusUrl.searchParams.set('linea_id', lineaId);
    }

    fetch(statusUrl)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                return;
            }

            const resumen = data.resumen || {};

            let html = `
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="bg-white p-3 rounded-lg border">
                        <div class="text-xs text-gray-500">Ultimo reset de componente</div>
                        <div class="font-semibold">${escapeHtml(resumen.ultimo_reset || 'Nunca')}</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg border">
                        <div class="text-xs text-gray-500">Pendientes</div>
                        <div class="font-semibold text-red-600">${Number(resumen.pendientes || 0)}</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg border">
                        <div class="text-xs text-gray-500">Programados</div>
                        <div class="font-semibold text-green-600">${Number(resumen.programados || 0)}</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg border">
                        <div class="text-xs text-gray-500">Sin revision</div>
                        <div class="font-semibold text-gray-600">${Number(resumen.sin_revision || 0)}</div>
                    </div>
                </div>
            `;

            if (data.estadisticas && data.estadisticas.total_restablecidos > 0) {
                html += `
                    <div class="mt-2 text-xs text-gray-600">
                        Total restablecidos: ${Number(data.estadisticas.total_restablecidos || 0)}
                        (${Number(data.estadisticas.ultimos_30_dias || 0)} en ultimos 30 dias)
                    </div>
                `;
            }

            resetInfo.innerHTML = html;
        })
        .catch(error => {
            resetInfo.innerHTML = `
                <div class="text-red-600 text-sm">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error al cargar informacion
                </div>
            `;
            console.error('Error:', error);
        });

}

function verProximosResets() {
    cargarInfoReset();
    // Mostrar notificación
    const infoDiv = document.getElementById('resetInfo');
    infoDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Resaltar temporalmente
    infoDiv.style.transition = 'background-color 0.5s ease';
    infoDiv.style.backgroundColor = '#fef3c7';
    setTimeout(() => {
        infoDiv.style.backgroundColor = '';
    }, 1000);
}
</script>
@endsection
