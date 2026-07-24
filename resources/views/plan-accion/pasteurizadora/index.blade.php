@extends('layouts.app')

@section('title', 'Pasteurizadoras - Plan de Acción')

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
        width: 100%;
        max-width: min(1400px, 100%);
        margin: 0 auto;
        padding: 24px;
        overflow-x: clip;
    }

    .plan-container * {
        box-sizing: border-box;
        min-width: 0;
    }

    .lineas-section,
    .stat-card,
    .linea-card {
        background: white;
        border-radius: 20px;
        border: 1px solid var(--medium-gray);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .lineas-section {
        padding: 24px;
        margin-bottom: 32px;
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
        text-decoration: none;
        gap: 8px;
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        touch-action: manipulation;
    }

    .linea-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .linea-btn.active,
    .linea-btn.todas.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
    }

    .linea-btn.todas {
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        padding: 24px;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue), var(--success-green));
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
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
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
    }

    .linea-card {
        margin-bottom: 32px;
        overflow: hidden;
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
        min-width: 0;
    }

    .linea-nombre {
        font-size: 20px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .badge-count {
        background: rgba(255, 255, 255, 0.15);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: normal;
        text-align: center;
    }

    .btn-agregar-rapido {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 44px;
        max-width: 100%;
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.15);
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
        border: 1px solid rgba(255, 255, 255, 0.3);
        touch-action: manipulation;
    }

    .btn-agregar-rapido:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
    }

    .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        width: 100%;
        min-width: 940px;
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

    .col-actividad {
        width: 320px;
    }

    .actividad-cell {
        width: 320px;
        word-wrap: break-word;
        overflow-wrap: anywhere;
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

    .col-area {
        width: 130px;
    }

    .area-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .area-badge.mecanica {
        background: #e0f2fe;
        color: #075985;
        border: 1px solid #bae6fd;
    }

    .area-badge.hidraulica {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .fecha-cell span {
        transition: all 0.3s ease;
        display: inline-block;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
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

    .acciones {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-accion {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        touch-action: manipulation;
    }

    .btn-accion:hover {
        transform: translateY(-2px);
    }

    .btn-editar { background: #3b82f6; }
    .btn-ver { background: #6b7280; }
    .btn-eliminar { background: #ef4444; }
    .btn-checklist { background: #f59e0b; }
    .btn-checklist.completado { background: #10b981; }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(24, 24, 27, 0.58);
        backdrop-filter: blur(6px);
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
        border-radius: 20px;
        max-width: min(760px, 100%);
        width: 100%;
        max-height: 85vh;
        overflow: hidden;
        border: 1px solid #e4e4e7;
        box-shadow: 0 24px 70px rgba(24, 24, 27, 0.28);
    }

    .modal-header {
        padding: 20px 24px;
        background: #fafafa;
        border-bottom: 1px solid #e4e4e7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(85vh - 80px);
    }

    .modal-close {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #ffffff;
        border: 1px solid #d4d4d8;
        color: #52525b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        touch-action: manipulation;
    }

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

    @media (max-width: 768px) {
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

        .modal {
            padding: 10px;
        }

        .modal-header,
        .modal-body {
            padding: 16px;
        }
    }

    @media (max-width: 480px) {
        .plan-container {
            padding: 14px 10px;
        }

        .lineas-grid {
            flex-direction: column;
            align-items: stretch;
        }

        .linea-btn {
            width: 100%;
        }

        .stat-card,
        .lineas-section {
            padding: 18px;
        }

        .linea-header {
            padding: 18px;
        }

        .table {
            min-width: 860px;
        }

        .notificacion-lateral {
            left: 10px;
            right: 10px;
            max-width: none;
            width: auto;
        }
    }
</style>

@php
    $planesPorLinea = method_exists($planes, 'getCollection')
        ? $planes->getCollection()->groupBy('linea_id')
        : collect($planes)->groupBy('linea_id');

    $totalActividades = method_exists($planes, 'total') ? $planes->total() : $planes->count();
    $actividadesCompletadas = collect($planes)->where('completado', true)->count();
    $porcentajeCompletado = $totalActividades > 0 ? round(($actividadesCompletadas / $totalActividades) * 100) : 0;

    $fechasProximas = 0;
    $actividadesVencidas = 0;

    foreach ($planes as $plan) {
        if ($plan->completado) {
            continue;
        }

        foreach (['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm) {
            $fechaCampo = 'fecha_' . $pcm;
            if (!$plan->$fechaCampo) {
                continue;
            }

            $dias = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($plan->$fechaCampo)->startOfDay(), false);

            if ($dias >= 0 && $dias <= 7) {
                $fechasProximas++;
                break;
            }

            if ($dias < 0) {
                $actividadesVencidas++;
                break;
            }
        }
    }
@endphp

<div class="plan-container">
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}"
               class="responsive-action responsive-action--secondary mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-600"></i>
                Planes de Acción
            </h1>
        </div>
    </div>

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
                            Atención: Fechas próximas a vencer ({{ count($alertas) }})
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
                                            @if(!empty($alerta['area_pasteurizadora_label']))
                                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-white/80 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                                    <i class="fas fa-tools"></i>
                                                    {{ $alerta['area_pasteurizadora_label'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-right ml-2">
                                            @if($alerta['es_manana'] ?? false)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    MAÑANA
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
                        <button class="inline-flex h-11 w-11 items-center justify-center rounded-md bg-white text-gray-400 hover:text-gray-500"
                                onclick="document.getElementById('notificacionLateral').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <h4>Total Actividades</h4>
                <div class="stat-icon primary">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $totalActividades }}</div>
            <div class="stat-detalle">Registros activos del módulo</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4>Completadas</h4>
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $actividadesCompletadas }}</div>
            <div class="stat-detalle">{{ $porcentajeCompletado }}% del total</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4>Por Vencer</h4>
                <div class="stat-icon warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $fechasProximas }}</div>
            <div class="stat-detalle">Próximos 7 días</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4>Vencidas</h4>
                <div class="stat-icon info">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-valor">{{ $actividadesVencidas }}</div>
            <div class="stat-detalle">Requieren atención</div>
        </div>
    </div>

    <div class="lineas-section">
        <div class="lineas-title">
            LÍNEAS DE PASTEURIZADORA
        </div>

        <div class="lineas-grid">
            <a href="{{ route('plan-accion.index', ['tipo' => 'pasteurizadora']) }}"
               class="linea-btn todas {{ !request('linea_id') ? 'active' : '' }}">
                <i class="fas fa-globe"></i>
                Todas las líneas
            </a>

            @foreach($lineasTipo as $linea)
                <a href="{{ route('plan-accion.index', ['tipo' => 'pasteurizadora', 'linea_id' => $linea->id]) }}"
                   class="linea-btn {{ request('linea_id') == $linea->id ? 'active' : '' }}">
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="space-y-6">
        @forelse($lineasTipo as $linea)
            @php
                $planesLinea = $planesPorLinea->get($linea->id, collect());
                if (request('linea_id') && request('linea_id') != $linea->id) {
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
                    <a href="{{ route('plan-accion.create', ['tipo' => 'pasteurizadora', 'linea_id' => $linea->id]) }}"
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
                                <th class="col-actividad">Actividad</th>
                                <th class="text-center col-area">Parte</th>
                                <th class="text-center" style="width: 100px;">PCM 1</th>
                                <th class="text-center" style="width: 100px;">PCM 2</th>
                                <th class="text-center" style="width: 100px;">PCM 3</th>
                                <th class="text-center" style="width: 100px;">PCM 4</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
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
                                        <div class="trazabilidad">
                                            <div class="trazabilidad-item">
                                                <i class="fas fa-user-check"></i>
                                                <span><strong>Responsable:</strong> {{ $plan->responsable?->name ?? 'Sin responsable' }}</span>
                                            </div>
                                            <div class="trazabilidad-item">
                                                <i class="fas fa-user-plus"></i>
                                                <span>
                                                    <strong>Registrado por:</strong> {{ $plan->registradoPor?->name ?? 'Sin dato historico' }}
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
                                    <td class="text-center">
                                        @if($plan->area_pasteurizadora)
                                            @php
                                                $areaClase = $plan->area_pasteurizadora === 'central_hidraulica' ? 'hidraulica' : 'mecanica';
                                                $areaIcono = $plan->area_pasteurizadora === 'central_hidraulica' ? 'fa-droplet' : 'fa-cog';
                                            @endphp
                                            <span class="area-badge {{ $areaClase }}">
                                                <i class="fas {{ $areaIcono }}"></i>
                                                {{ $plan->area_pasteurizadora_label }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
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
                                                    $fechaClass = 'fecha-futura';
                                                    $tooltip = $plan->completado ? 'Actividad Completada' : 'Vence en ' . $dias . ' días';

                                                    if (!$plan->completado) {
                                                        if ($dias < 0) {
                                                            $fechaClass = 'fecha-vencida';
                                                            $tooltip = 'Vencida hace ' . abs($dias) . ' días';
                                                        } elseif ($dias <= 3) {
                                                            $fechaClass = 'fecha-proxima';
                                                        } elseif ($dias <= 7) {
                                                            $fechaClass = 'fecha-cercana';
                                                        }
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
                                            <a href="{{ route('plan-accion.edit', ['plan_accion' => $plan->id, 'tipo' => 'pasteurizadora']) }}"
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
                                    <td colspan="8" class="text-center py-12">
                                        <div class="flex flex-col items-center">
                                            <p class="text-gray-500 mb-4">No hay actividades para esta línea</p>
                                            <a href="{{ route('plan-accion.create', ['tipo' => 'pasteurizadora', 'linea_id' => $linea->id]) }}"
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
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No hay pasteurizadoras registradas</h3>
                    <p class="text-gray-500">Primero debe registrar pasteurizadoras para poder crear planes de acción.</p>
                </div>
            </div>
        @endforelse
    </div>

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

<div class="modal" id="verActividadModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-clipboard-list text-blue-600"></i>
                Detalles de la Actividad
            </h3>
            <button class="modal-close modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="detalleActividad"></div>
        </div>
    </div>
</div>

<div class="modal" id="eliminarModal">
    <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
            <h3 class="text-lg font-bold text-red-700 flex items-center gap-2">
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

                <form id="eliminarForm" method="POST" class="responsive-actions responsive-actions--end">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tipo" value="pasteurizadora">
                    <button type="submit" class="responsive-action responsive-action--danger">
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar
                    </button>
                    <button type="button" class="responsive-action responsive-action--secondary modal-close-btn">
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
document.addEventListener('DOMContentLoaded', function() {
    const verModal = document.getElementById('verActividadModal');
    const eliminarModal = document.getElementById('eliminarModal');

    function openModal(modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.modal-close-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        });
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal(this);
            }
        });
    });

    const detalleActividad = document.getElementById('detalleActividad');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fechaLocal(value, dateOnly = false) {
        if (!value) return null;

        if (dateOnly) {
            const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

            if (match) {
                return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
            }
        }

        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function formatDate(value) {
        const date = fechaLocal(value, true);
        return date ? date.toLocaleDateString('es-MX') : null;
    }

    function formatDateTime(value) {
        const date = fechaLocal(value);
        return date ? date.toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' }) : null;
    }

    function pcmTone(value) {
        const date = fechaLocal(value, true);
        if (!date) return null;

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);
        const days = Math.ceil((date - today) / 86400000);

        if (days < 0) {
            return { label: 'Vencida', detail: `${Math.abs(days)} dia(s) vencida`, classes: 'border-red-200 bg-red-50 text-red-700', icon: 'fa-circle-exclamation' };
        }

        if (days <= 3) {
            return { label: 'Proxima', detail: days === 0 ? 'Vence hoy' : `Faltan ${days} dia(s)`, classes: 'border-blue-200 bg-blue-50 text-blue-800 shadow-sm shadow-blue-100', icon: 'fa-bolt' };
        }

        if (days <= 7) {
            return { label: 'Cercana', detail: `Faltan ${days} dia(s)`, classes: 'border-blue-200 bg-blue-50 text-blue-800 shadow-sm shadow-blue-100', icon: 'fa-clock' };
        }

        return { label: 'Programada', detail: `Faltan ${days} dia(s)`, classes: 'border-zinc-200 bg-zinc-50 text-zinc-700 shadow-sm shadow-zinc-100', icon: 'fa-calendar-check' };
    }

    function renderPlanActionDetail(data, options = {}) {
        if (!detalleActividad) return;

        const usuarioNombre = usuario => usuario && usuario.name ? usuario.name : null;
        const icon = 'fa-clipboard-list';
        const headerGradient = data.completado
            ? 'linear-gradient(135deg, #065f46 0%, #059669 52%, #34d399 100%)'
            : 'linear-gradient(135deg, #1e3a8a 0%, #2563eb 52%, #38bdf8 100%)';
        const iconPanelClasses = data.completado
            ? 'bg-emerald-500/15 text-emerald-50 ring-1 ring-emerald-300/25'
            : 'bg-blue-500/15 text-blue-50 ring-1 ring-blue-300/25';
        const responsable = usuarioNombre(data.responsable) || 'Sin responsable';
        const registradoPor = usuarioNombre(data.registrado_por) || 'Sin dato historico';
        const ejecutadoPor = usuarioNombre(data.ejecutado_por) || (data.completado ? 'Sin dato historico' : 'Pendiente');
        const fechaRegistro = formatDateTime(data.created_at) || 'N/A';
        const fechaEjecucion = formatDateTime(data.fecha_ejecucion) || (data.completado ? 'Sin dato historico' : 'Pendiente');
        const estadoClasses = data.completado ? 'border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm shadow-emerald-100' : 'border-blue-200 bg-blue-50 text-blue-800 shadow-sm shadow-blue-100';
        const estadoIcon = data.completado ? 'fa-circle-check' : 'fa-hourglass-half';
        const estadoLabel = data.completado ? 'Actividad completada' : 'Actividad pendiente';
        const articleBorderClasses = data.completado ? 'border-emerald-100' : 'border-blue-100';
        const areaLabels = { mecanica: 'Mecanica', central_hidraulica: 'Hidraulica' };
        const areaLabel = areaLabels[data.area_pasteurizadora] || data.area_pasteurizadora_label || 'No especificada';
        const pcmCards = ['fecha_pcm1', 'fecha_pcm2', 'fecha_pcm3', 'fecha_pcm4']
            .map((campo, index) => {
                const tone = pcmTone(data[campo]);
                if (!tone) return '';

                return `
                    <div class="rounded-xl border p-4 ${tone.classes}">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-bold uppercase tracking-wide">PCM ${index + 1}</span>
                            <i class="fas ${tone.icon}"></i>
                        </div>
                        <p class="mt-2 text-lg font-bold text-gray-950">${formatDate(data[campo])}</p>
                        <p class="mt-1 text-xs font-semibold">${tone.label} - ${tone.detail}</p>
                    </div>
                `;
            })
            .join('');

        detalleActividad.innerHTML = `
            <article class="overflow-hidden rounded-2xl border ${articleBorderClasses} bg-white shadow-xl shadow-zinc-200/70">
                <div class="relative overflow-hidden px-5 py-5 text-white" style="background: ${headerGradient};">
                    <div class="absolute inset-x-0 bottom-0 h-px bg-white/15"></div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-2xl ${iconPanelClasses}">
                                <i class="fas ${icon}"></i>
                            </span>
                            <div>
                                <div class="mb-1 flex flex-wrap items-center gap-2 text-xs font-bold text-white/85">
                                    <span class="rounded-full bg-white/15 px-3 py-1">Plan #${escapeHtml(data.id || '')}</span>
                                    <span class="rounded-full bg-white/15 px-3 py-1">${escapeHtml(data.linea ? data.linea.nombre : 'Sin linea')}</span>
                                </div>
                                <h4 class="text-xl font-black leading-tight">${escapeHtml(data.actividad || 'Actividad sin nombre')}</h4>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-bold ${estadoClasses}">
                            <i class="fas ${estadoIcon}"></i>
                            ${estadoLabel}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 bg-zinc-50 p-5">
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Responsable</p>
                            <p class="mt-2 font-bold text-zinc-950">${escapeHtml(responsable)}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Registrado por</p>
                            <p class="mt-2 font-bold text-zinc-950">${escapeHtml(registradoPor)}</p>
                            <p class="mt-1 text-xs text-zinc-500">${escapeHtml(fechaRegistro)}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Ejecutado por</p>
                            <p class="mt-2 font-bold text-zinc-950">${escapeHtml(ejecutadoPor)}</p>
                            <p class="mt-1 text-xs text-zinc-500">${escapeHtml(fechaEjecucion)}</p>
                        </div>
                    </div>

                    ${pcmCards ? `<div class="grid gap-3 md:grid-cols-2">${pcmCards}</div>` : ''}

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 text-zinc-800 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide">Parte de Pasteurizadora</p>
                        <p class="mt-2 font-bold">${escapeHtml(areaLabel)}</p>
                    </div>

                    ${data.observaciones ? `
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Observaciones</p>
                            <p class="mt-2 text-sm leading-6 text-zinc-700">${escapeHtml(data.observaciones)}</p>
                        </div>
                    ` : ''}
                </div>
            </article>
        `;
    }

    document.querySelectorAll('.ver-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openModal(verModal);

            document.getElementById('detalleActividad').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                    <p class="mt-3 text-sm text-gray-500">Cargando detalles...</p>
                </div>
            `;

            fetch(`/plan-accion/${id}`, { headers: { 'Accept': 'application/json' } })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.status === 403 ? 'forbidden' : (response.status === 404 ? 'missing' : 'load'));
                    }

                    return response.json();
                })
                .then(data => {
                    renderPlanActionDetail(data, { tipo: 'pasteurizadora' });
                    return;
                    const areaLabels = {
                        mecanica: 'Mecanica',
                        central_hidraulica: 'Hidraulica'
                    };
                    const areaLabel = areaLabels[data.area_pasteurizadora] || 'No especificada';
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
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <label class="text-xs text-gray-500 uppercase font-semibold">Parte de Pasteurizadora</label>
                                <p class="font-medium text-gray-900 mt-1">${areaLabel}</p>
                            </div>
                    `;

                    ['fecha_pcm1', 'fecha_pcm2', 'fecha_pcm3', 'fecha_pcm4'].forEach((campo, index) => {
                        if (data[campo]) {
                            const fecha = new Date(data[campo]);
                            html += `
                                <div class="bg-gray-50 p-4 rounded-xl">
                                    <label class="text-xs text-gray-500 uppercase font-semibold">PCM ${index + 1}</label>
                                    <p class="font-medium text-gray-900 mt-1">${fecha.toLocaleDateString('es-MX')}</p>
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

                    html += '</div>';
                    document.getElementById('detalleActividad').innerHTML = html;
                })
                .catch(error => {
                    const message = error.message === 'forbidden'
                        ? 'No cuentas con autorizacion para visualizar este contenido.'
                        : (error.message === 'missing'
                            ? 'La actividad ya no esta disponible o fue eliminada.'
                            : 'Error al cargar los detalles');

                    document.getElementById('detalleActividad').innerHTML = `
                        <div class="text-center py-8 text-red-600">
                            <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                            <p>${message}</p>
                        </div>
                    `;
                });
        });
    });

    document.querySelectorAll('.eliminar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('actividadEliminar').textContent = this.dataset.actividad;
            document.getElementById('eliminarForm').action = `/plan-accion/${this.dataset.id}?tipo=pasteurizadora`;
            openModal(eliminarModal);
        });
    });

    function openPlanFromNotification(id) {
        const button = document.querySelector(`.ver-btn[data-id="${id}"]`);

        if (button) {
            button.click();
            return;
        }

        openModal(verModal);

        document.getElementById('detalleActividad').innerHTML = `
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                <p class="mt-3 text-sm text-gray-500">Cargando detalles...</p>
            </div>
        `;

        fetch(`/plan-accion/${id}?tipo=pasteurizadora`, { headers: { 'Accept': 'application/json' } })
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.status === 403 ? 'forbidden' : (response.status === 404 ? 'missing' : 'load'));
                }

                return response.json();
            })
            .then(data => {
                renderPlanActionDetail(data, { tipo: 'pasteurizadora' });
                return;
                const areaLabels = {
                    mecanica: 'Mecanica',
                    central_hidraulica: 'Hidraulica'
                };
                const fechaHora = value => value
                    ? new Date(value).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
                    : null;
                const responsable = data.responsable && data.responsable.name ? data.responsable.name : 'Sin responsable';
                const registradoPor = data.registrado_por && data.registrado_por.name ? data.registrado_por.name : 'Sin dato historico';
                const ejecutadoPor = data.ejecutado_por && data.ejecutado_por.name
                    ? data.ejecutado_por.name
                    : (data.completado ? 'Sin dato historico' : 'Pendiente');
                const fechas = ['fecha_pcm1', 'fecha_pcm2', 'fecha_pcm3', 'fecha_pcm4']
                    .map((campo, index) => data[campo]
                        ? `<div class="bg-gray-50 p-4 rounded-xl">
                            <label class="text-xs text-gray-500 uppercase font-semibold">PCM ${index + 1}</label>
                            <p class="font-medium text-gray-900 mt-1">${new Date(data[campo]).toLocaleDateString('es-MX')}</p>
                        </div>`
                        : '')
                    .join('');

                document.getElementById('detalleActividad').innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Linea</label>
                            <p class="font-medium text-gray-900 mt-1">${data.linea ? data.linea.nombre : 'No asignada'}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Actividad</label>
                            <p class="font-medium text-gray-900 mt-1">${data.actividad || 'No especificada'}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Parte de Pasteurizadora</label>
                            <p class="font-medium text-gray-900 mt-1">${areaLabels[data.area_pasteurizadora] || 'No especificada'}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Trazabilidad</label>
                            <div class="mt-2 space-y-1 text-sm text-gray-700">
                                <p><span class="font-semibold">Responsable:</span> ${responsable}</p>
                                <p><span class="font-semibold">Registrado por:</span> ${registradoPor} | ${fechaHora(data.created_at) || 'N/A'}</p>
                                <p><span class="font-semibold">Ejecutado por:</span> ${ejecutadoPor} | ${fechaHora(data.fecha_ejecucion) || (data.completado ? 'Sin dato historico' : 'Pendiente')}</p>
                            </div>
                        </div>
                        ${fechas}
                    </div>
                `;
            })
            .catch(error => {
                const message = error.message === 'forbidden'
                    ? 'No cuentas con autorizacion para visualizar este contenido.'
                    : (error.message === 'missing'
                        ? 'La actividad ya no esta disponible o fue eliminada.'
                        : 'No se pudo cargar la actividad solicitada.');

                document.getElementById('detalleActividad').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                        <p>${message}</p>
                    </div>
                `;
            });
    }

    const openPlanId = @json(request('open_plan_id'));

    if (openPlanId) {
        openPlanFromNotification(openPlanId);
    }

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

                setTimeout(() => location.reload(), 1200);
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudo actualizar el checklist', 'error');
            });
        });
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(closeModal);
        }
    });
});
</script>
@endsection
