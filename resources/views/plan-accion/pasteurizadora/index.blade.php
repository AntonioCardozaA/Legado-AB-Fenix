@extends('layouts.app')

@section('title', 'Plan de Acción PCM - Pasteurizadora')

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

    .linea-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid var(--medium-gray);
        margin-bottom: 24px;
        overflow: hidden;
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

    .linea-header .badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
    }

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

    .btn-agregar-rapido:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-2px);
        border-color: rgba(255,255,255,0.5);
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
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

    .col-actividad {
        width: 350px;
        max-width: 350px;
    }

    .actividad-cell {
        width: 350px;
        max-width: 350px;
        min-height: 120px;
        vertical-align: top;
        word-wrap: break-word;
        white-space: normal;
    }

    .col-acciones {
        width: 120px;
    }

    .acciones {
        display: flex;
        gap: 8px;
        justify-content: center;
        min-width: 120px;
    }

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

    .btn-eliminar {
        background: #ef4444;
    }

    .btn-eliminar:hover {
        background: #dc2626;
        box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
    }

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

    .leyenda-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

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
</style>
@foreach($pendientes as $registro)
<tr>
    <td>{{ $registro->linea->nombre ?? '-' }}</td>
    <td>{{ $registro->modulo }}</td>
    <td>{{ $registro->componente }}</td>
    <td>{{ $registro->estado }}</td>
</tr>
@endforeach

<div class="plan-container">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('analisis-pasteurizadora.dashboard') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                          bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="font-medium">Volver</span>
                </a>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                     alt="Icono de pasteurizadora" 
                     class="w-8 h-8 object-contain"
                     onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
                Plan de Acción PCM - Pasteurizadora
            </h1>
        </div>
    </div>

    {{-- Líneas --}}
    <div class="lineas-section">
        <div class="lineas-title">
            <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                 alt="Icono" 
                 class="w-5 h-5"
                 onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
            LÍNEAS DE PASTEURIZADORA
        </div>
        
        <div class="lineas-grid">
            <a href="{{ route('analisis-pasteurizadora.plan-accion') }}" 
               class="linea-btn todas {{ !request('linea_id') ? 'active' : '' }}">
                <i class="fas fa-globe"></i>
                Todas
            </a>
            
            @foreach($lineas as $linea)
                <a href="{{ route('analisis-pasteurizadora.plan-accion', ['linea_id' => $linea->id]) }}" 
                   class="linea-btn {{ request('linea_id') == $linea->id ? 'active' : '' }}">
                    <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                         class="w-4 h-4 mr-2" 
                         alt="Ícono"
                         onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
                    {{ $linea->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Plan de Acción por Línea --}}
    <div class="space-y-6">
        @if($lineaSeleccionada)
            <div class="linea-card">
                <div class="linea-header">
                    <div class="linea-info">
                        <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                             alt="Icono"
                             onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
                        <span class="linea-nombre">{{ $lineaSeleccionada->nombre }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="badge">
                            <i class="fas fa-tasks mr-1"></i> Plan de Acción PCM
                        </span>
                        
                        <a href="{{ route('analisis-pasteurizadora.create-quick', ['linea_id' => $lineaSeleccionada->id]) }}" 
                           class="btn-agregar-rapido"
                           title="Agregar actividad rápida">
                            <i class="fas fa-plus-circle"></i>
                            <span class="hidden sm:inline">Nueva Actividad</span>
                        </a>
                    </div>
                </div>
                
                <div class="p-6">
                    <form action="{{ route('analisis-pasteurizadora.plan-accion.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="linea_id" value="{{ $lineaSeleccionada->id }}">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            {{-- PCM 1 --}}
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                    PCM 1
                                </h4>
                                <textarea name="pcm1[]" rows="4" 
                                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                          placeholder="Ingrese las actividades del PCM 1...">{{ $registro->plan_accion_pcm1 ? implode("\n", $registro->plan_accion_pcm1) : '' }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Una actividad por línea</p>
                            </div>

                            {{-- PCM 2 --}}
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-green-600 rounded-full"></span>
                                    PCM 2
                                </h4>
                                <textarea name="pcm2[]" rows="4" 
                                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                          placeholder="Ingrese las actividades del PCM 2...">{{ $registro->plan_accion_pcm2 ? implode("\n", $registro->plan_accion_pcm2) : '' }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Una actividad por línea</p>
                            </div>

                            {{-- PCM 3 --}}
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-yellow-600 rounded-full"></span>
                                    PCM 3
                                </h4>
                                <textarea name="pcm3[]" rows="4" 
                                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                          placeholder="Ingrese las actividades del PCM 3...">{{ $registro->plan_accion_pcm3 ? implode("\n", $registro->plan_accion_pcm3) : '' }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Una actividad por línea</p>
                            </div>

                            {{-- PCM 4 --}}
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-red-600 rounded-full"></span>
                                    PCM 4
                                </h4>
                                <textarea name="pcm4[]" rows="4" 
                                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                          placeholder="Ingrese las actividades del PCM 4...">{{ $registro->plan_accion_pcm4 ? implode("\n", $registro->plan_accion_pcm4) : '' }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Una actividad por línea</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="reset" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                Guardar Plan de Acción
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Historial de actividades recientes --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-history text-blue-600"></i>
                    Actividades Recientes - {{ $lineaSeleccionada->nombre }}
                </h3>

                @php
                    $actividadesRecientes = \App\Models\AnalisisPasteurizadora::porLinea($lineaSeleccionada->id)
                        ->whereNotNull('actividad')
                        ->orderBy('fecha_analisis', 'desc')
                        ->limit(10)
                        ->get();
                @endphp

                @if($actividadesRecientes->count() > 0)
                    <div class="space-y-3">
                        @foreach($actividadesRecientes as $actividad)
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clipboard-check text-blue-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">
                                                Módulo {{ $actividad->modulo }} - {{ $actividad->componente_nombre }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($actividad->actividad, 100) }}</p>
                                        </div>
                                        <span class="text-xs text-gray-400">{{ $actividad->fecha_formateada }}</span>
                                    </div>
                                    <div class="flex gap-2 mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $actividad->estado_clase }}">
                                            <i class="fas {{ $actividad->estado_icono }} mr-1"></i>
                                            {{ $actividad->estado }}
                                        </span>
                                        @if($actividad->lado)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $actividad->lado_clase }}">
                                                <i class="fas {{ $actividad->lado_icono }} mr-1"></i>
                                                {{ $actividad->lado }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 text-center">
                        <a href="{{ route('analisis-pasteurizadora.historial', ['linea_id' => $lineaSeleccionada->id]) }}" 
                           class="text-sm text-blue-600 hover:text-blue-800">
                            Ver todas las actividades <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                @else
                    <p class="text-center text-gray-500 py-4">No hay actividades recientes para esta línea</p>
                @endif
            </div>
        @else
            {{-- Vista de todas las líneas --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($lineas as $linea)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 text-white">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                                     alt="Icono" 
                                     class="w-8 h-8 object-contain"
                                     onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
                                <h3 class="font-bold text-lg">{{ $linea->nombre }}</h3>
                            </div>
                        </div>
                        <div class="p-4">
                            @php
                                $planLinea = \App\Models\AnalisisPasteurizadora::porLinea($linea->id)->latest()->first();
                                $actividadesLinea = \App\Models\AnalisisPasteurizadora::porLinea($linea->id)
                                    ->whereNotNull('actividad')
                                    ->count();
                            @endphp
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total actividades:</span>
                                    <span class="font-semibold">{{ $actividadesLinea }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Último análisis:</span>
                                    <span class="font-semibold">{{ $planLinea ? $planLinea->fecha_formateada : 'N/A' }}</span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <a href="{{ route('analisis-pasteurizadora.plan-accion', ['linea_id' => $linea->id]) }}" 
                                   class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition text-center">
                                    Ver Plan
                                </a>
                                <a href="{{ route('analisis-pasteurizadora.create-quick', ['linea_id' => $linea->id]) }}" 
                                   class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Leyenda --}}
    <div class="leyenda">
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #3b82f6;"></span>
            <span>PCM 1 - Mantenimiento básico</span>
        </div>
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #10b981;"></span>
            <span>PCM 2 - Inspección detallada</span>
        </div>
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #f59e0b;"></span>
            <span>PCM 3 - Mantenimiento mayor</span>
        </div>
        <div class="leyenda-item">
            <span class="leyenda-color" style="background: #ef4444;"></span>
            <span>PCM 4 - Overhaul</span>
        </div>
    </div>
</div>

{{-- Modal de ayuda --}}
<div class="modal" id="ayudaModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-info-circle text-blue-600"></i>
                Instrucciones - Plan de Acción PCM
            </h3>
            <button class="modal-close" onclick="cerrarAyudaModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">📋 ¿Cómo usar el plan de acción?</h4>
                    <p class="text-gray-600 text-sm">Ingrese las actividades de mantenimiento planificadas para cada período PCM. Una actividad por línea.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">🔄 PCM 1 - Mantenimiento básico</h4>
                    <p class="text-gray-600 text-sm">Actividades diarias/semanales: lubricación, limpieza, inspección visual.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">🔍 PCM 2 - Inspección detallada</h4>
                    <p class="text-gray-600 text-sm">Actividades mensuales: verificación de tolerancias, ajustes, mediciones.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">⚙️ PCM 3 - Mantenimiento mayor</h4>
                    <p class="text-gray-600 text-sm">Actividades trimestrales: cambio de componentes, alineación, balanceo.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">🛠️ PCM 4 - Overhaul</h4>
                    <p class="text-gray-600 text-sm">Actividades semestrales/anuales: reconstrucción, cambio de partes críticas.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function abrirAyudaModal() {
    document.getElementById('ayudaModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function cerrarAyudaModal() {
    document.getElementById('ayudaModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarAyudaModal();
    }
});

document.getElementById('ayudaModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarAyudaModal();
    }
});
</script>
@endsection