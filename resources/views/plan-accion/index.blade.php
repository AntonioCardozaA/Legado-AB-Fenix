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

    .plan-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    /* LÍNEAS EN FORMA DE BOTONES - CON PNGs */
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

    .lineas-title img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    /* Estilo de botones con PNGs */
    .linea-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        gap: 6px;
    }

    .linea-btn img {
        width: 18px;
        height: 18px;
        object-fit: contain;
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

    .linea-btn.active img {
        filter: brightness(0) invert(1);
    }

    /* Botón "Todas las líneas" */
    .linea-btn.todas {
        background: #f8fafc;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .linea-btn.todas i {
        color: #3b82f6;
    }

    .linea-btn.todas.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .linea-btn.todas.active i {
        color: white;
    }

    /* Estilos para las tarjetas de líneas */
    .linea-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        margin-bottom: 24px;
        overflow: hidden;
        position: relative;
    }

    .linea-header {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .linea-header .linea-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .linea-header .linea-info img {
        width: 24px;
        height: 24px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    .linea-header .linea-nombre {
        font-size: 18px;
        font-weight: 700;
    }

    .linea-header .linea-badge {
        background: rgba(255,255,255,0.2);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .linea-header .badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
    }

    /* Botón de agregar rápido en cada línea */
    .btn-agregar-rapido {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: rgba(255,255,255,0.15);
        color: white;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(255,255,255,0.3);
        cursor: pointer;
    }

    .btn-agregar-rapido img {
        width: 16px;
        height: 16px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    .btn-agregar-rapido:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-2px);
        border-color: rgba(255,255,255,0.5);
    }

    .btn-agregar-rapido i {
        font-size: 14px;
    }

    /* ===== TABLA ESTABLE Y FIJA ===== */
    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
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
    }

    .table td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    /* Columna actividad tamaño aproximado 10cm x 8cm */
    .col-actividad {
        width: 380px;
        max-width: 380px;
    }

    .actividad-cell {
        width: 380px;
        max-width: 380px;
        min-height: 300px;
        vertical-align: top;
        word-wrap: break-word;
        white-space: normal;
    }

    /* Columna acciones fija */
    .col-acciones {
        width: 150px; /* Aumentado para acomodar el nuevo botón */
    }

    .acciones {
        display: flex;
        gap: 8px;
        justify-content: center;
        min-width: 140px; /* Aumentado para acomodar el nuevo botón */
    }

    /* Fechas */
    .fecha-cell span {
        transition: all 0.3s ease;
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
    }

    .fecha-cell span:hover {
        transform: scale(1.05);
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    /* Tipos de máquina badges con PNGs */
    .tipos-maquina-container {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .tipo-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
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

    .tipo-badge.secadora {
        background: #cffafe;
        color: #155e75;
    }

    .tipo-badge.caldera {
        background: #ffedd5;
        color: #9a3412;
    }

    .tipo-badge.centrifuga {
        background: #f3e8ff;
        color: #6b21a8;
    }

    /* Acciones */
    .btn-accion {
        width: 32px;
        height: 32px;
        border-radius: 8px;
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

    .btn-editar {
        background: #3b82f6;
    }

    .btn-editar:hover {
        background: #2563eb;
        box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
    }

    .btn-ver {
        background: #6b7280;
    }

    .btn-ver:hover {
        background: #4b5563;
        box-shadow: 0 4px 8px rgba(75, 85, 99, 0.3);
    }

    .btn-notificar {
        background: #f59e0b;
    }

    .btn-notificar:hover {
        background: #d97706;
        box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
    }

    .btn-eliminar {
        background: #ef4444;
    }

    .btn-eliminar:hover {
        background: #dc2626;
        box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
    }

    /* Botón nueva actividad principal */
    .btn-nueva {
        display: inline-flex;
        align-items: center;
        padding: 12px 24px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 40px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        margin-bottom: 24px;
    }

    .btn-nueva:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    /* Tarjetas de estadísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid var(--medium-gray);
        display: flex;
        flex-direction: column;
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
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
        width: 28px;
        height: 28px;
        object-fit: contain;
        opacity: 0.7;
    }

    .stat-valor {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .stat-detalle {
        font-size: 13px;
        color: #64748b;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Alertas */
    .alertas-container {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        position: relative;
    }

    .alerta-item {
        padding: 8px 12px;
        border-radius: 6px;
        margin-top: 8px;
    }

    .alerta-item.alta {
        background: #ef4444;
        color: white;
    }

    .alerta-item.media {
        background: #f59e0b;
        color: #1f2937;
    }

    .alerta-item.baja {
        background: #3b82f6;
        color: white;
    }

    /* Paginación */
    .pagination-info {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
    }

    @media (min-width: 640px) {
        .pagination-info {
            flex-direction: row;
        }
    }

    /* Leyenda */
    .leyenda {
        margin-top: 24px;
        padding: 12px;
        background: #f9fafb;
        border-radius: 8px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 8px;
    }

    .leyenda-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    .leyenda-item img {
        width: 16px;
        height: 16px;
        object-fit: contain;
    }

    .leyenda-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    /* Modal */
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
        max-width: 600px;
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
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

    .modal-header img {
        width: 22px;
        height: 22px;
        object-fit: contain;
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
    }

    .modal-close:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    .detalle-icono {
        width: 18px;
        height: 18px;
        object-fit: contain;
        margin-right: 8px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .lineas-grid {
            justify-content: center;
        }
        
        .table td, .table th {
            padding: 12px;
        }
    }

    /* Animación para el botón de agregar rápido */
    @keyframes gentlePulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    .btn-agregar-rapido:hover i {
        animation: gentlePulse 1s infinite;
    }
</style>

<div class="plan-container">
    <!-- Header con botón de volver y título -->
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
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <img src="{{ asset('images/icono-maquina.png') }}" 
                     alt="Icono de máquina" 
                     class="w-8 h-8 object-contain">
                Plan de Acción - Lavadoras
            </h1>
        </div>
    </div>

    <!-- Alertas de fechas próximas -->
@if(count($alertas) > 0)
<div class="alertas-container">
    <button type="button" class="absolute top-4 right-4 text-yellow-600 hover:text-yellow-800" onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
    </button>
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
        <div class="flex-1">
            <strong class="text-yellow-800">¡Atención! Fechas próximas a vencer ({{ count($alertas) }}):</strong>
            <div class="mt-2 space-y-2">
                @foreach($alertas as $alerta)
                {{-- AQUÍ ESTÁ EL PROBLEMA --}}
                <div class="alerta-item {{ $alerta['prioridad'] ?? 'media' }}"> {{-- CORREGIDO: agregué ?? 'media' --}}
                    <div class="flex justify-between items-center">
                        <div>
                            <strong>{{ $alerta['linea'] ?? 'Sin línea' }}</strong> - 
                            {{ Str::limit($alerta['actividad'] ?? 'Sin actividad', 50) }} - 
                            <strong>{{ $alerta['pcm'] ?? 'PCM' }}</strong>
                        </div>
                        <div class="text-right">
                            <span class="bg-white bg-opacity-20 px-2 py-1 rounded text-sm">
                                {{ $alerta['fecha'] ?? 'Fecha no disponible' }}
                            </span>
                            @if($alerta['es_manana'] ?? false)
                                <span class="bg-red-800 text-white px-2 py-1 rounded text-sm ml-2">¡MAÑANA!</span>
                            @else
                                <span class="bg-white bg-opacity-20 px-2 py-1 rounded text-sm ml-2">
                                    {{ $alerta['dias_restantes'] ?? 0 }} día(s)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

    <!-- SECCIÓN DE LÍNEAS DE LAVADORA - CON PNGs -->
    <div class="lineas-section">
        <div class="lineas-title">
            <img src="{{ asset('images/icono-maquina.png') }}" 
                 alt="Icono de máquina">
            LÍNEAS DE LAVADORA
        </div>
        
        <div class="lineas-grid">
            <!-- Todas -->
            <a href="{{ route('plan-accion.index', ['tipo' => 'lavadora']) }}" 
               class="linea-btn todas {{ !request('linea_id') ? 'active' : '' }}">
                <i class="fas fa-globe"></i>
                Todas
            </a>
            
            @foreach([4,5,6,7,8,9,12,13] as $id)
                <a href="{{ route('plan-accion.index', [
                        'tipo' => 'lavadora',
                        'linea_id' => $id
                    ]) }}" 
                   class="linea-btn {{ request('linea_id') == $id ? 'active' : '' }}">
                    <img src="{{ asset('images/icono-maquina.png') }}" class="w-10 h-8 mr-2" alt="Ícono de máquina">
                    L-{{ str_pad($id, 2, '0', STR_PAD_LEFT) }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Planes de Acción por Líneas -->
    <div class="space-y-6">
        @php
            // Definir los IDs de las líneas de lavadora
            $lineasLavadoraIds = [4, 5, 6, 7, 8, 9, 12, 13];
            $lineasLavadora = \App\Models\Linea::whereIn('id', $lineasLavadoraIds)->orderBy('id')->get();
            $planesPorLinea = $planes->groupBy(function($plan) {
                return $plan->linea_id ?? 'sin-linea';
            });
        @endphp

        @forelse($lineasLavadora as $linea)
            @php
                $planesLinea = $planesPorLinea->get($linea->id, collect());
                // Si hay filtro de línea y no coincide, omitir
                if(request('linea_id') && request('linea_id') != $linea->id) {
                    continue;
                }
            @endphp
            
            <div class="linea-card">
                <div class="linea-header">
                    <div class="linea-info">
                        <img src="{{ asset('images/icono-maquina.png') }}" class="w-10 h-8 " alt="Icono de máquina">
                        <span class="linea-nombre">{{ $linea->nombre_completo}}</span>
                        <span class="linea-badge">L-{{ str_pad($linea->id, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="badge">
                            <i class="fas fa-tasks mr-1"></i> {{ $planesLinea->count() }} actividades
                        </span>
                        
                        <!-- BOTÓN DE AGREGAR RÁPIDO EN CADA TARJETA DE LÍNEA -->
                        <a href="{{ route('plan-accion.create', ['tipo' => 'lavadora', 'linea_id' => $linea->id]) }}" 
                           class="btn-agregar-rapido"
                           title="Agregar actividad rápida para {{ $linea->nombre }}">
                            <span class="hidden sm:inline">Agregar Actividad</span>
                            <img src="{{ asset('images/icono-maquina.png') }}" class="w-10 h-8" alt="Lavadora">
                        </a>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th class="col-actividad">ACTIVIDAD</th>
                                <th class="text-center" style="width:110px;">PCM 1</th>
                                <th class="text-center" style="width:110px;">PCM 2</th>
                                <th class="text-center" style="width:110px;">PCM 3</th>
                                <th class="text-center" style="width:110px;">PCM 4</th>
                                <th class="text-center col-acciones">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($planesLinea as $index => $plan)
                            <tr>
                                <td>{{ $index + 1 }}</td>

                                {{-- ACTIVIDAD --}}
                                <td class="actividad-cell">
                                    <div class="font-medium text-gray-900">
                                        {{ $plan->actividad }}
                                    </div>

                                    @if($plan->tipo_maquina)
                                        <div class="tipos-maquina-container">
                                            @foreach($plan->tipo_maquina as $tipo)
                                                @php
                                                    $clases = [
                                                        'lavadora' => 'lavadora',
                                                        'secadora' => 'secadora',
                                                        'caldera' => 'caldera',
                                                        'centrifuga' => 'centrifuga',
                                                    ];
                                                    $clase = $clases[$tipo] ?? '';
                                                @endphp
                                                <span class="tipo-badge {{ $clase }}">
                                                    <img src="{{ asset('images/icono-maquina.png') }}" alt="{{ $tipo }}">
                                                    {{ ucfirst($tipo) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                {{-- PCM --}}
                                @foreach(['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm)
                                    @php
                                        $fechaCampo = 'fecha_' . $pcm;
                                        $fecha = $plan->$fechaCampo;
                                    @endphp
                                    <td class="text-center fecha-cell">
                                        @if($fecha)
                                            @php
                                                $dias = \Carbon\Carbon::now()->diffInDays($fecha, false);
                                                $fechaClass = '';
                                                if($dias < 0) $fechaClass = 'fecha-vencida';
                                                elseif($dias <= 3) $fechaClass = 'fecha-proxima';
                                                elseif($dias <= 7) $fechaClass = 'fecha-cercana';
                                                else $fechaClass = 'fecha-futura';
                                            @endphp
                                            <span class="{{ $fechaClass }}">
                                                {{ $fecha->format('d/m/Y') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach

                                {{-- ACCIONES FIJAS CON BOTÓN DE NOTIFICACIONES --}}
                                <td class="text-center col-acciones">
                                    <div class="acciones">
                                        <a href="{{ route('plan-accion.edit', ['plan_accion' => $plan->id, 'tipo' => 'lavadora']) }}"  
                                           class="btn-accion btn-editar" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <button type="button" 
                                                class="btn-accion btn-ver ver-btn" 
                                                data-id="{{ $plan->id }}"
                                                title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <!-- BOTÓN DE NOTIFICACIONES -->
                                        <button type="button" 
                                                class="btn-accion btn-notificar" 
                                                style="background: #f59e0b;"
                                                onclick="enviarNotificaciones({{ $plan->id }}, '{{ addslashes($plan->actividad) }}')"
                                                title="Enviar notificaciones">
                                            <i class="fas fa-bell"></i>
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
                                <td colspan="8" class="text-center py-8">
                                    <div class="flex flex-col items-center">
                                        <img src="{{ asset('images/icono-maquina.png') }}" alt="Lavadora" class="w-16 h-10 opacity-30 mb-3">
                                        <p class="text-gray-500">No hay actividades para esta lavadora</p>
                                        <a href="{{ route('plan-accion.create', ['tipo' => 'lavadora', 'linea_id' => $linea->id]) }}" 
                                           class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
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
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="flex flex-col items-center">
                    <img src="{{ asset('images/icono-maquina.png') }}" alt="Lavadora" class="w-20 h-20 opacity-30 mb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay lavadoras registradas</h3>
                    <p class="text-gray-500 mb-4">Primero debe registrar lavadoras para poder crear planes de acción.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($planes->hasPages())
    <div class="pagination-info">
        <div class="text-sm text-gray-700">
            Mostrando {{ $planes->firstItem() }} - {{ $planes->lastItem() }} de {{ $planes->total() }} registros
        </div>
        <div>
            {{ $planes->appends(request()->query())->links() }}
        </div>
    </div>
    @endif

    <!-- Leyenda de colores -->
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
            <img src="{{ asset('images/icono-maquina.png') }}" alt="Lavadora">
            <span>Lavadora</span>
        </div>
    </div>
</div>

<!-- Modal para ver detalles -->
<div class="modal" id="verActividadModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <img src="{{ asset('images/icono-maquina.png') }}" alt="Lavadora">
                Detalles de la Actividad
            </h3>
            <button class="modal-close modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="detalleActividad">
                <div class="text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                    <p class="mt-2 text-sm text-gray-500">Cargando...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
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
                <p class="text-gray-700 mb-4">¿Está seguro de eliminar la actividad:</p>
                <p class="text-lg font-bold text-gray-900 mb-2" id="actividadEliminar"></p>
                <p class="text-sm text-red-600 mb-6">Esta acción no se puede deshacer.</p>
                
                <form id="eliminarForm" method="POST" class="flex justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tipo" value="lavadora">
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                        Eliminar
                    </button>
                    <button type="button" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200 modal-close-btn">
                        Cancelar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                    let html = `
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <strong class="text-gray-600">Línea:</strong><br>
                                <span class="flex items-center gap-2">
                                    <img src="{{ asset('images/icono-maquina.png') }}" alt="Lavadora" class="w-4 h-4">
                                    ${data.linea ? data.linea.nombre_completo || data.linea.nombre : 'No asignada'}
                                </span>
                            </div>
                            <div class="col-span-2">
                                <strong class="text-gray-600">Responsable:</strong><br>
                                <span>${data.responsable ? data.responsable.name : 'No asignado'}</span>
                            </div>
                            <div class="col-span-2">
                                <strong class="text-gray-600">Actividad:</strong><br>
                                <span>${data.actividad || 'No especificada'}</span>
                            </div>
                    `;
                    
                    if (data.tipo_maquina && data.tipo_maquina.length > 0) {
                        html += `<div class="col-span-2"><strong class="text-gray-600">Tipo de máquina:</strong><br><div class="flex flex-wrap gap-2 mt-1">`;
                        data.tipo_maquina.forEach(tipo => {
                            const colorClass = tipo == 'lavadora' ? 'bg-blue-100 text-blue-800' : 
                                              tipo == 'secadora' ? 'bg-cyan-100 text-cyan-800' :
                                              tipo == 'caldera' ? 'bg-orange-100 text-orange-800' :
                                              tipo == 'centrifuga' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800';
                            html += `<span class="px-3 py-1 text-sm rounded-full ${colorClass} inline-flex items-center gap-2">
                                <img src="{{ asset('images/icono-maquina.png') }}" alt="${tipo}" class="w-3 h-3">
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
                                <div>
                                    <strong class="text-gray-600">PCM ${index + 1}:</strong><br>
                                    <span>${fecha.toLocaleDateString('es-ES')}</span>
                                </div>
                            `;
                        }
                    });
                    
                    const estadoColors = {
                        'pendiente': 'bg-yellow-100 text-yellow-800',
                        'en_proceso': 'bg-blue-100 text-blue-800',
                        'completada': 'bg-green-100 text-green-800',
                        'atrasada': 'bg-red-100 text-red-800'
                    };
                    
                    
                    if (data.observaciones) {
                        html += `
                            <div class="mt-4 pt-4 border-t">
                                <strong class="text-gray-600">Observaciones:</strong><br>
                                <span>${data.observaciones}</span>
                            </div>
                        `;
                    }
                    
                    document.getElementById('detalleActividad').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('detalleActividad').innerHTML = `
                        <div class="text-center py-4 text-red-600">
                            <i class="fas fa-exclamation-circle text-3xl mb-2"></i>
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
});

// Función para enviar notificaciones
// En resources/views/plan-accion/index.blade.php - Reemplaza la función existente

function enviarNotificaciones(id, actividad) {
    if (confirm(`¿Enviar notificaciones para la actividad: "${actividad}"?`)) {
        // Mostrar indicador de carga
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch(`/plan-accion/${id}/notificar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito con detalles
                Swal.fire({
                    icon: 'success',
                    title: '¡Notificaciones enviadas!',
                    html: `
                        <p>${data.message}</p>
                        ${data.data && data.data.pcm_notificados ? `
                            <div class="mt-3 text-left">
                                <strong>PCMs notificados:</strong><br>
                                ${data.data.pcm_notificados.map(p => 
                                    `- ${p.pcm.toUpperCase()}: ${p.fecha} (${p.dias} día${p.dias !== 1 ? 's' : ''})`
                                ).join('<br>')}
                            </div>
                        ` : ''}
                    `,
                    confirmButtonColor: '#3085d6'
                });
            } else {
                // Mostrar error
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al enviar notificaciones'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al enviar notificaciones'
            });
        })
        .finally(() => {
            // Restaurar botón
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
}
</script>
@endsection