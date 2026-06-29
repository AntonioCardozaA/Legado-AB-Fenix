@extends('layouts.app')

@section('title', 'Lavadoras - Plan de Acción')

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

    .plan-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    /* HEADER CON GRADIENTE */
    .header-gradient {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        padding: 24px 32px;
        margin-bottom: 32px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .header-gradient::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    /* LÍNEAS EN FORMA DE BOTONES MEJORADOS */
    .lineas-section {
        background: white;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        border: 1px solid var(--medium-gray);
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .lineas-title i {
        color: var(--primary-blue);
        font-size: 18px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .linea-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        gap: 8px;
    }

    .linea-btn img {
        width: 18px;
        height: 18px;
        object-fit: contain;
    }

    .linea-btn i {
        font-size: 14px;
    }

    .linea-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    .linea-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .linea-btn.active img {
        filter: brightness(0) invert(1);
    }

    .linea-btn.active i {
        color: white;
    }

    .linea-btn.todas {
        background: #f8fafc;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .linea-btn.todas.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    /* TARJETAS DE ESTADÍSTICAS MEJORADAS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        border: 1px solid var(--medium-gray);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue), var(--success-green));
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .stat-header h4 {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .stat-icon.primary { background: #dbeafe; color: #2563eb; }
    .stat-icon.success { background: #d1fae5; color: #059669; }
    .stat-icon.warning { background: #fed7aa; color: #ea580c; }
    .stat-icon.info { background: #cffafe; color: #0891b2; }

    .stat-valor {
        font-size: 36px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .stat-detalle {
        font-size: 13px;
        color: #64748b;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* TARJETAS DE LÍNEA */
    .linea-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        border: 1px solid var(--medium-gray);
        margin-bottom: 32px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .linea-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    }

    .linea-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .linea-info {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .linea-nombre {
        font-size: 20px;
        font-weight: 700;
    }

    .linea-badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
    }

    .badge-count {
        background: rgba(255,255,255,0.15);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-agregar-rapido {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 44px;
        max-width: 100%;
        padding: 10px 20px;
        background: rgba(255,255,255,0.15);
        color: white;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
        text-decoration: none;
        white-space: normal;
        overflow-wrap: anywhere;
        transition: all 0.2s ease;
        border: 1px solid rgba(255,255,255,0.3);
        cursor: pointer;
        touch-action: manipulation;
    }

    .btn-agregar-rapido:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-2px);
        border-color: rgba(255,255,255,0.5);
    }

    /* TABLA MEJORADA */
    .table-responsive {
        overflow-x: auto;
        padding: 0;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .table th {
        background: #f8fafc;
        padding: 16px 20px;
        font-weight: 700;
        font-size: 13px;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }

    .table td {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    /* Columna actividad con tamaño definido */
    .col-actividad {
        width: 380px;
    }

    .actividad-cell {
        width: 380px;
        word-wrap: break-word;
        white-space: normal;
    }

    .actividad-texto {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .trazabilidad {
        display: grid;
        gap: 4px;
        margin-top: 10px;
        font-size: 12px;
        line-height: 1.35;
        color: #64748b;
    }

    .trazabilidad-item {
        display: flex;
        align-items: flex-start;
        gap: 6px;
    }

    .trazabilidad-item i {
        width: 14px;
        margin-top: 2px;
        color: #94a3b8;
    }

    .trazabilidad-item strong {
        color: #334155;
        font-weight: 700;
    }

    /* Badges de tipo de máquina */
    .tipos-maquina-container {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .tipo-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        gap: 6px;
        background: #f3f4f6;
        color: #1f2937;
    }

    .tipo-badge img {
        width: 14px;
        height: 14px;
        object-fit: contain;
    }

    .tipo-badge.lavadora {
        background: #dbeafe;
        color: #1e40af;
    }

    /* Fechas con animaciones */
    .fecha-cell span {
        transition: all 0.3s ease;
        display: inline-block;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .fecha-cell span:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    .fecha-vencida {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .fecha-proxima {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
        animation: pulse 2s infinite;
    }

    .fecha-cercana {
        background: #fef9c3;
        color: #854d0e;
        border: 1px solid #fef08a;
    }

    .fecha-futura {
        background: #f3f4f6;
        color: #1f2937;
        border: 1px solid #e5e7eb;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Botones de acción */
    .acciones {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-accion {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }

    .btn-accion:hover {
        transform: translateY(-2px);
    }

    .btn-editar { background: #3b82f6; }
    .btn-editar:hover { background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }

    .btn-ver { background: #6b7280; }
    .btn-ver:hover { background: #4b5563; box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3); }

    .btn-eliminar { background: #ef4444; }
    .btn-eliminar:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }

    .btn-checklist { background: #f59e0b; }
    .btn-checklist:hover { background: #d97706; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); }
    .btn-checklist.completado { background: #10b981; }
    .btn-checklist.completado:hover { background: #059669; }

    /* Botón principal */
    .btn-principal {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 40px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-principal:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    /* Notificación lateral */
    .notificacion-lateral {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        max-width: 380px;
        width: 100%;
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Panel de notificaciones */
    .notificacion-panel {
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 10px;
        width: 380px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        border: 1px solid #e2e8f0;
        z-index: 50;
        max-height: 450px;
        overflow-y: auto;
    }

    /* Modal mejorado */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal.show {
        display: flex;
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 550px;
        width: 100%;
        max-height: 85vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
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
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(85vh - 80px);
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
    }

    .modal-close:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    /* Leyenda */
    .leyenda {
        margin-top: 32px;
        padding: 16px 20px;
        background: #f9fafb;
        border-radius: 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        border: 1px solid #e2e8f0;
    }

    .leyenda-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    .leyenda-color {
        width: 14px;
        height: 14px;
        border-radius: 4px;
    }

    /* Paginación */
    .pagination-container {
        margin-top: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .pagination-info {
        font-size: 14px;
        color: #64748b;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .plan-container {
            padding: 16px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .lineas-grid {
            justify-content: center;
        }

        .table th, .table td {
            padding: 12px;
        }

        .col-actividad {
            width: 250px;
        }

        .linea-header {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .linea-info {
            justify-content: center;
            width: 100%;
        }

        .btn-agregar-rapido {
            width: 100%;
        }

        .notificacion-lateral {
            max-width: calc(100% - 40px);
            right: 20px;
        }

        .notificacion-panel {
            width: calc(100vw - 40px);
            right: 20px;
        }
    }
</style>

@php
    $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
    
    try {
        $lineasLavadora = \Illuminate\Support\Facades\Cache::remember('lineas_lavadora_' . md5(implode(',', $lineasLavadoraIds)), 3600, function() use ($lineasLavadoraIds) {
            return \App\Models\Linea::whereIn('id', $lineasLavadoraIds)
                ->orderBy('id')
                ->get();
        });
    } catch (\Exception $e) {
        $lineasLavadora = \App\Models\Linea::whereIn('id', $lineasLavadoraIds)
            ->orderBy('id')
            ->get();
    }
    
    $planesPorLinea = method_exists($planes, 'getCollection') 
        ? $planes->getCollection()->groupBy('linea_id') 
        : (is_array($planes) || $planes instanceof \Illuminate\Support\Collection ? $planes->groupBy('linea_id') : collect());
    
    // Calcular estadísticas
    $totalActividades = $planes->total() ?? $planes->count();
    $actividadesCompletadas = $planes->where('completado', true)->count();
    $porcentajeCompletado = $totalActividades > 0 ? round(($actividadesCompletadas / $totalActividades) * 100) : 0;
    
    // Actividades próximas a vencer (próximos 7 días)
    $fechasProximas = 0;
    foreach($planes as $plan) {
        if ($plan->completado) {
            continue; 
        }
        foreach(['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
            $fechaCampo = 'fecha_' . $pcm;
            if($plan->$fechaCampo) {
                $dias = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($plan->$fechaCampo)->startOfDay(), false);
                if($dias >= 0 && $dias <= 7) {
                    $fechasProximas++;
                    break;
                }
            }
        }
    }
    
    // Actividades vencidas
    $actividadesVencidas = 0;
    foreach($planes as $plan) {
         if($plan->completado) {
        continue; 
    }
        foreach(['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
            $fechaCampo = 'fecha_' . $pcm;
            if($plan->$fechaCampo) {
                $dias = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($plan->$fechaCampo)->startOfDay(), false);
                if($dias < 0) {
                    $actividadesVencidas++;
                    break;
                }
            }
        }
    }
@endphp

<div class="historico-container">
    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('lavadora.dashboard') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Planes de Accion
            </h1>
        </div>
    </div>

    {{-- Notificación lateral de fechas próximas --}}
    @if(isset($alertas) && count($alertas) > 0)
    <div class="notificacion-lateral" id="notificacionLateral">
        <div class="bg-white rounded-lg shadow-xl border-l-4 border-yellow-400 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            ¡Atención! Fechas próximas a vencer ({{ count($alertas) }})
                        </p>
                        <div class="mt-2 text-sm text-gray-600">
                            <div class="max-h-60 overflow-y-auto space-y-2">
                                @foreach($alertas as $alerta)
                                <div class="p-2 {{ $alerta['prioridad'] == 'alta' ? 'bg-red-50' : ($alerta['prioridad'] == 'media' ? 'bg-yellow-50' : 'bg-blue-50') }} rounded">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="font-semibold">{{ $alerta['linea'] ?? 'Sin línea' }}</span>
                                            <span class="text-xs block">
                                                {{ Str::limit($alerta['actividad'] ?? 'Sin actividad', 40) }}
                                            </span>
                                        </div>
                                        <div class="text-right ml-2">
                                            @if($alerta['es_manana'] ?? false)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    ¡MAÑANA!
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ (int) ($alerta['dias_restantes'] ?? 0) }} día(s)
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500"
                                onclick="document.getElementById('notificacionLateral').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TARJETAS DE ESTADÍSTICAS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <h4>Total Actividades</h4>
                <div class="stat-icon primary">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $totalActividades }}</div>
            <div class="stat-detalle">
                <span>En todas las líneas</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4>Completadas</h4>
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $actividadesCompletadas }}</div>
            <div class="stat-detalle">
                <span>{{ $porcentajeCompletado }}% del total</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4>Por Vencer</h4>
                <div class="stat-icon warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $fechasProximas }}</div>
            <div class="stat-detalle">
                <span>Próximos 7 días</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4>Vencidas</h4>
                <div class="stat-icon info">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $actividadesVencidas }}</div>
            <div class="stat-detalle">
                <span>Requieren atención</span>
            </div>
        </div>
    </div>

    {{-- SECCIÓN DE LÍNEAS --}}
    <div class="lineas-section">
        <div class="lineas-title">
            <i class="fas fa-washing-machine"></i>
            LÍNEAS DE LAVADORA
        </div>
        
        <div class="lineas-grid">
            <a href="{{ route('plan-accion.index', ['tipo' => 'lavadora']) }}" 
               class="linea-btn todas {{ !request('linea_id') ? 'active' : '' }}">
                <i class="fas fa-globe"></i>
                Todas las líneas
            </a>
            
            @foreach($lineasLavadora as $linea)
                <a href="{{ route('plan-accion.index', ['tipo' => 'lavadora', 'linea_id' => $linea->id]) }}" 
                   class="linea-btn {{ request('linea_id') == $linea->id ? 'active' : '' }}">
                    <i class="fas fa-washing-machine"></i>
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>
    {{-- Planes de Acción por Línea --}}
    <div class="space-y-6">
        @forelse($lineasLavadora as $linea)
            @php
                $planesLinea = $planesPorLinea->get($linea->id, collect());
                if(request('linea_id') && request('linea_id') != $linea->id) {
                    continue;
                }
                $lineaCompletadas = $planesLinea->where('completado', true)->count();
                $lineaTotal = $planesLinea->count();
                $lineaPorcentaje = $lineaTotal > 0 ? round(($lineaCompletadas / $lineaTotal) * 100) : 0;
            @endphp
            
            <div class="linea-card">
                <div class="linea-header">
                    <div class="linea-info">
                        <span class="linea-nombre">{{ $linea->nombre }}</span>
                        
                        <span class="badge-count">
                            <i class="fas fa-tasks"></i>
                            {{ $planesLinea->count() }} actividades
                        </span>
                        @if($lineaTotal > 0)
                        <span class="badge-count">
                            <i class="fas fa-chart-line"></i>
                            {{ $lineaPorcentaje }}% completado
                        </span>
                        @endif
                    </div>
                    <a href="{{ route('plan-accion.create', ['tipo' => 'lavadora', 'linea_id' => $linea->id]) }}" 
                       class="btn-agregar-rapido create-action create-action--on-dark">
                        <i class="fas fa-plus"></i>
                        <span>Agregar Actividad</span>
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th class="col-actividad">ACTIVIDAD</th>
                                <th class="text-center" style="width: 100px;">PCM 1</th>
                                <th class="text-center" style="width: 100px;">PCM 2</th>
                                <th class="text-center" style="width: 100px;">PCM 3</th>
                                <th class="text-center" style="width: 100px;">PCM 4</th>
                                <th class="text-center" style="width: 120px;">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($planesLinea as $index => $plan)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="actividad-cell">
                                    <div class="actividad-texto">
                                        {{ $plan->actividad }}
                                        @if($plan->completado)
                                            <span class="ml-2 text-xs text-green-600">
                                                <i class="fas fa-check-circle"></i> Completado
                                            </span>
                                        @endif
                                    </div>
                                    @if($plan->tipo_maquina)
                                        <div class="tipos-maquina-container">
                                            @foreach($plan->tipo_maquina as $tipo)
                                                <span class="tipo-badge lavadora">
                                                    <i class="fas fa-washing-machine"></i>
                                                    {{ ucfirst($tipo) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="trazabilidad">
                                        <div class="trazabilidad-item">
                                            <i class="fas fa-user-check"></i>
                                            <span><strong>Responsable:</strong> {{ $plan->responsable?->name ?? 'Sin responsable' }}</span>
                                        </div>
                                        <div class="trazabilidad-item">
                                            <i class="fas fa-user-plus"></i>
                                            <span>
                                                <strong>Fecha:</strong> 
                                                @if($plan->created_at)
                                                    <span class="text-gray-400">|</span> {{ $plan->created_at->format('d/m/Y H:i') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="trazabilidad-item">
                                            <i class="fas fa-user-cog"></i>
                                            <span>
                                                <strong>Ejecutado por:</strong>
                                                {{ $plan->ejecutadoPor?->name ?? ($plan->completado ? 'Sin dato historico' : 'Pendiente') }}
                                                @if($plan->fecha_ejecucion)
                                                    <span class="text-gray-400">|</span> {{ $plan->fecha_ejecucion->format('d/m/Y H:i') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                @foreach(['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm)
                                    @php
                                        $fechaCampo = 'fecha_' . $pcm;
                                        $fecha = $plan->$fechaCampo ?? null;
                                    @endphp
                                    <td class="text-center fecha-cell">
                                        @if($fecha)
                                            @php
                                                $dias = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($fecha)->startOfDay(), false);
                                                    $fechaClass = 'fecha-futura'; // Clase por defecto para completadas o sin problemas
                                                    $tooltip = '';

                                                    if (!$plan->completado) {
                                                        // Sólo calcular vencimiento si NO está completada
                                                        if ($dias < 0) {
                                                            $fechaClass = 'fecha-vencida';
                                                            $tooltip = 'Vencida hace ' . abs($dias) . ' días';
                                                        } elseif ($dias <= 3) {
                                                            $fechaClass = 'fecha-proxima';
                                                            $tooltip = 'Vence en ' . $dias . ' días';
                                                        } elseif ($dias <= 7) {
                                                            $fechaClass = 'fecha-cercana';
                                                            $tooltip = 'Vence en ' . $dias . ' días';
                                                        } else {
                                                            $tooltip = 'Vence en ' . $dias . ' días';
                                                        }
                                                    } else {
                                                        $tooltip = 'Actividad Completada'; // Mensaje especial para completadas
                                                    }
                                                @endphp
                                          <span class="{{ $fechaClass }}" title="{{ $tooltip }}">
                                                {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center">
                                    <div class="acciones">
                                        <a href="{{ route('plan-accion.edit', ['plan_accion' => $plan->id, 'tipo' => 'lavadora']) }}"  
                                           class="btn-accion btn-editar" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn-accion btn-ver ver-btn" 
                                                data-id="{{ $plan->id }}"
                                                title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn-accion btn-checklist checklist-btn {{ $plan->completado ? 'completado' : '' }}" 
                                                data-id="{{ $plan->id }}"
                                                title="{{ $plan->completado ? 'Marcar como pendiente' : 'Marcar como realizada' }}">
                                            <i class="fas {{ $plan->completado ? 'fa-check-circle' : 'fa-circle' }}"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn-accion btn-eliminar eliminar-btn" 
                                                data-id="{{ $plan->id }}"
                                                data-actividad="{{ $plan->actividad }}"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-washing-machine text-5xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500 mb-4">No hay actividades para esta línea</p>
                                        <a href="{{ route('plan-accion.create', ['tipo' => 'lavadora', 'linea_id' => $linea->id]) }}" 
                                           class="create-action">
                                            <i class="fas fa-plus-circle"></i>
                                            Agregar primera actividad
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="flex flex-col items-center">
                    <i class="fas fa-washing-machine text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No hay lavadoras registradas</h3>
                    <p class="text-gray-500">Primero debe registrar lavadoras para poder crear planes de acción.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if(method_exists($planes, 'links') && $planes->total() > 0)
    <div class="pagination-container">
        <div class="pagination-info">
            Mostrando {{ $planes->firstItem() }} - {{ $planes->lastItem() }} de {{ $planes->total() }} registros
        </div>
        <div>
            {{ $planes->appends(request()->query())->links() }}
        </div>
    </div>
    @endif

    {{-- Leyenda --}}
    <div class="leyenda">
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #10b981;"></span>
            <span>Fecha próxima (1-3 días)</span>
        </div>
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #f59e0b;"></span>
            <span>Fecha cercana (4-7 días)</span>
        </div>
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #ef4444;"></span>
            <span>Fecha vencida</span>
        </div>
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #6b7280;"></span>
            <span>Fecha futura (>7 días)</span>
        </div>
        <div class="leyenda-item">
            <i class="fas fa-check-circle text-green-600"></i>
            <span>Actividad completada</span>
        </div>
    </div>
</div>

{{-- Modal para ver detalles --}}
<div class="modal" id="verActividadModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-washing-machine text-blue-600"></i>
                Detalles de la Actividad
            </h3>
            <button class="modal-close modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="detalleActividad">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                    <p class="mt-3 text-sm text-gray-500">Cargando detalles...</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmación para eliminar --}}
<div class="modal" id="eliminarModal">
    <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
            <h3 class="text-red-700">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
                Confirmar Eliminación
            </h3>
            <button class="modal-close modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="text-center">
                <i class="fas fa-trash-alt text-5xl text-red-400 mb-4"></i>
                <p class="text-gray-700 mb-3">¿Está seguro de eliminar la actividad?</p>
                <p class="text-lg font-bold text-gray-900 mb-2" id="actividadEliminar"></p>
                <p class="text-sm text-red-600 mb-6">Esta acción no se puede deshacer.</p>
                
                <form id="eliminarForm" method="POST" class="flex justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tipo" value="lavadora">
                    <button type="submit" class="px-6 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition duration-200 font-medium">
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar
                    </button>
                    <button type="button" class="px-6 py-2.5 bg-gray-500 text-white rounded-xl hover:bg-gray-600 transition duration-200 font-medium modal-close-btn">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let panelVisible = false;

function toggleNotificaciones() {
    const panel = document.getElementById('notificacionesPanel');
    if (panelVisible) {
        panel.classList.add('hidden');
        panelVisible = false;
    } else {
        panel.classList.remove('hidden');
        panelVisible = true;
        cargarNotificaciones();
    }
}

document.addEventListener('click', function(event) {
    const container = document.getElementById('notificacionContainer');
    const panel = document.getElementById('notificacionesPanel');
    
    if (container && !container.contains(event.target) && panelVisible) {
        panel.classList.add('hidden');
        panelVisible = false;
    }
});

function cargarNotificaciones() {
    const lista = document.getElementById('notificacionesLista');
    if (!lista) return;
    
    fetch('/plan-accion/notificaciones-pendientes', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        actualizarBadge(data.total);
        
        if (data.notificaciones && data.notificaciones.length > 0) {
            let html = '';
            data.notificaciones.forEach(notif => {
                const prioridadClass = notif.prioridad === 'alta' ? 'bg-red-50 border-l-4 border-red-500' :
                                      notif.prioridad === 'media' ? 'bg-yellow-50 border-l-4 border-yellow-500' :
                                      'bg-blue-50 border-l-4 border-blue-500';
                
                html += `
                    <div class="p-4 ${prioridadClass} hover:bg-gray-50 transition cursor-pointer notificacion-item" onclick="verActividad(${notif.id})">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">${notif.linea}</p>
                                <p class="text-xs text-gray-600 mt-1">${notif.actividad}</p>
                                <div class="flex items-center mt-2 text-xs">
                                    <span class="text-gray-500">PCM: ${notif.pcm}</span>
                                    <span class="mx-2">•</span>
                                    <span class="${notif.es_manana ? 'text-red-600 font-bold' : 'text-orange-600'}">
                                        ${notif.es_manana ? '¡MAÑANA!' : Math.trunc(Number(notif.dias_restantes)) + ' día(s)'}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    ${notif.fecha}
                                </p>
                            </div>
                            <div class="flex space-x-1">
                                <button onclick="enviarNotificacionIndividual(${notif.id}, '${notif.actividad.replace(/'/g, "\\'")}'); event.stopPropagation();" 
                                        class="text-blue-600 hover:text-blue-800 p-1" title="Enviar notificación ahora">
                                    <i class="fas fa-paper-plane text-xs"></i>
                                </button>
                                <button onclick="marcarComoLeida(${notif.id}); event.stopPropagation();" 
                                        class="text-gray-400 hover:text-gray-600 p-1" title="Marcar como leída">
                                    <i class="fas fa-check text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            lista.innerHTML = html;
        } else {
            lista.innerHTML = `
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl text-green-400 mb-3"></i>
                    <p class="text-sm">No hay notificaciones pendientes</p>
                    <p class="text-xs text-gray-400 mt-1">Todas las fechas están al día</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (lista) {
            lista.innerHTML = `
                <div class="p-4 text-center text-red-500">
                    <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                    <p class="text-sm">Error al cargar notificaciones</p>
                </div>
            `;
        }
    });
}

function actualizarBadge(total) {
    const badge = document.getElementById('notificacionBadge');
    if (badge) {
        if (total > 0) {
            badge.style.display = 'inline-flex';
            badge.textContent = total > 99 ? '99+' : total;
        } else {
            badge.style.display = 'none';
        }
    }
}

function verActividad(id) {
    window.location.href = `/plan-accion/${id}`;
}

function marcarComoLeida(id) {
    fetch(`/plan-accion/notificacion/${id}/marcar-leida`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
            Swal.fire({
                icon: 'success',
                title: 'Notificación marcada como leída',
                showConfirmButton: false,
                timer: 1500
            });
        }
    });
}

function enviarNotificacionIndividual(id, actividad) {
    if (confirm(`¿Enviar notificaciones para: "${actividad}"?`)) {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch(`/plan-accion/${id}/notificar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Enviado!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(cargarNotificaciones, 2000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al enviar la notificación'
            });
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    cargarNotificaciones();
    setInterval(cargarNotificaciones, 300000);

    // Modales
    const verModal = document.getElementById('verActividadModal');
    const eliminarModal = document.getElementById('eliminarModal');
    
    function openModal(modal) {
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    document.querySelectorAll('.modal-close-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) closeModal(modal);
        });
    });
    
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    document.querySelectorAll('.ver-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openModal(verModal);
            
            fetch(`/plan-accion/${id}`)
                .then(response => response.json())
                .then(data => {
                    const usuarioNombre = usuario => usuario && usuario.name ? usuario.name : null;
                    const fechaHora = value => value
                        ? new Date(value).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
                        : null;
                    const responsable = usuarioNombre(data.responsable) || 'Sin responsable';
                    const registradoPor = usuarioNombre(data.registrado_por) || 'Sin dato historico';
                    const fechaRegistro = fechaHora(data.created_at) || 'N/A';
                    const ejecutadoPor = usuarioNombre(data.ejecutado_por) || (data.completado ? 'Sin dato historico' : 'Pendiente');
                    const fechaEjecucion = fechaHora(data.fecha_ejecucion) || (data.completado ? 'Sin dato historico' : 'Pendiente');
                    let html = `
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <label class="text-xs text-gray-500 uppercase font-semibold">Línea</label>
                                <p class="font-medium text-gray-900 mt-1 flex items-center gap-2">
                                    <i class="fas fa-washing-machine text-blue-600"></i>
                                    ${data.linea ? data.linea.nombre : 'No asignada'}
                                </p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <label class="text-xs text-gray-500 uppercase font-semibold">Actividad</label>
                                <p class="font-medium text-gray-900 mt-1">${data.actividad || 'No especificada'}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <label class="text-xs text-gray-500 uppercase font-semibold">Trazabilidad</label>
                                <div class="mt-2 space-y-1 text-sm text-gray-700">
                                    <p><span class="font-semibold">Responsable:</span> ${responsable}</p>
                                    <p><span class="font-semibold">Registrado por:</span> ${registradoPor} | ${fechaRegistro}</p>
                                    <p><span class="font-semibold">Ejecutado por:</span> ${ejecutadoPor} | ${fechaEjecucion}</p>
                                </div>
                            </div>
                    `;
                    
                    if (data.tipo_maquina && data.tipo_maquina.length > 0) {
                        html += `<div class="bg-gray-50 p-4 rounded-xl">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Tipo de máquina</label>
                            <div class="flex flex-wrap gap-2 mt-2">`;
                        data.tipo_maquina.forEach(tipo => {
                            html += `<span class="px-3 py-1.5 text-sm rounded-xl bg-blue-100 text-blue-800 inline-flex items-center gap-2">
                                <i class="fas fa-washing-machine"></i>
                                ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}
                            </span>`;
                        });
                        html += `</div></div>`;
                    }
                    
                    const pcmFields = ['fecha_pcm1', 'fecha_pcm2', 'fecha_pcm3', 'fecha_pcm4'];
                    pcmFields.forEach((campo, index) => {
                        if (data[campo]) {
                            const fecha = new Date(data[campo]);
                            html += `
                                <div class="bg-gray-50 p-4 rounded-xl">
                                    <label class="text-xs text-gray-500 uppercase font-semibold">PCM ${index + 1}</label>
                                    <p class="font-medium text-gray-900 mt-1">${fecha.toLocaleDateString('es-ES')}</p>
                                </div>
                            `;
                        }
                    });
                    
                    if (data.observaciones) {
                        html += `
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <label class="text-xs text-gray-500 uppercase font-semibold">Observaciones</label>
                                <p class="text-gray-700 mt-1">${data.observaciones}</p>
                            </div>
                        `;
                    }
                    
                    if (data.completado) {
                        html += `
                            <div class="bg-green-50 p-4 rounded-xl border border-green-200">
                                <div class="flex items-center gap-2 text-green-700">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="font-semibold">Actividad completada</span>
                                </div>
                            </div>
                        `;
                    }
                    
                    html += `</div>`;
                    document.getElementById('detalleActividad').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('detalleActividad').innerHTML = `
                        <div class="text-center py-8 text-red-600">
                            <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                            <p>Error al cargar los detalles</p>
                        </div>
                    `;
                });
        });
    });
    
    document.querySelectorAll('.eliminar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('actividadEliminar').textContent = this.dataset.actividad;
            document.getElementById('eliminarForm').action = `/plan-accion/${this.dataset.id}`;
            openModal(eliminarModal);
        });
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(modal => {
                closeModal(modal);
            });
        }
    });
    
    // Checklist
    document.querySelectorAll('.checklist-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const boton = this;
            
            fetch(`/plan-accion/${id}/checklist`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.completado) {
                    boton.classList.add('completado');
                    boton.innerHTML = '<i class="fas fa-check-circle"></i>';
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actividad completada!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    boton.classList.remove('completado');
                    boton.innerHTML = '<i class="fas fa-circle"></i>';
                    Swal.fire({
                        icon: 'info',
                        title: 'Actividad marcada como pendiente',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                // Recargar estadísticas
                setTimeout(() => location.reload(), 1500);
            })
            .catch(error => {
                console.error(error);
                Swal.fire('Error', 'No se pudo actualizar el checklist', 'error');
            });
        });
    });
});
</script>
@endsection
