@extends('layouts.app')

@section('title', 'Plan de Acción PCM - Pasteurizadora')

@section('content')
<style>
    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }
    .linea-btn {
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
        text-decoration: none;
    }
    .linea-btn i {
        margin-right: 8px;
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
    .linea-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
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
    }
    .btn-agregar-rapido:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-2px);
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
        padding: 12px 16px;
        font-weight: 600;
        font-size: 12px;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
    }
    .table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .fecha-cell span {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
    }
    .fecha-vencida { background: #fee2e2; color: #991b1b; }
    .fecha-proxima { background: #dcfce7; color: #166534; animation: pulse 2s infinite; }
    .fecha-cercana { background: #fef9c3; color: #854d0e; }
    .fecha-futura { background: #f3f4f6; color: #1f2937; }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
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
    .btn-editar { background: #3b82f6; }
    .btn-ver { background: #6b7280; }
    .btn-checklist { background: #f59e0b; }
    .btn-checklist.completado { background: #10b981; }
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
    .modal.show { display: flex; }
    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 600px;
        width: 100%;
        max-height: 80vh;
        overflow: hidden;
    }
    .modal-header {
        padding: 20px 24px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        cursor: pointer;
    }
</style>

<div class="max-w-6xl mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('analisis-pasteurizadora.dashboard') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <img src="{{ asset('images/icono-pasteurizadora.png') }}" class="w-8 h-8 object-contain">
                Plan de Acción - Pasteurizadora
            </h1>
        </div>
    </div>

    {{-- Líneas --}}
    <div class="lineas-grid">
        <a href="{{ route('analisis-pasteurizadora.plan-accion') }}" 
           class="linea-btn {{ !request('linea_id') ? 'active' : '' }}">
            <i class="fas fa-globe"></i> Todas
        </a>
        @foreach($lineas as $linea)
            <a href="{{ route('analisis-pasteurizadora.plan-accion', ['linea_id' => $linea->id]) }}" 
               class="linea-btn {{ request('linea_id') == $linea->id ? 'active' : '' }}">
                <i class="fas fa-temperature-high"></i> {{ $linea->nombre }}
            </a>
        @endforeach
    </div>

    {{-- Planes por línea --}}
    @php
        $planesPorLinea = $planes->groupBy('linea_id');
    @endphp

    @forelse($lineas as $linea)
        @php
            $planesLinea = $planesPorLinea->get($linea->id, collect());
            if(request('linea_id') && request('linea_id') != $linea->id) continue;
        @endphp
        
        <div class="linea-card">
            <div class="linea-header">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/icono-pasteurizadora.png') }}" class="w-6 h-6 object-contain filter brightness-0 invert">
                    <span class="font-bold">{{ $linea->nombre }}</span>
                    <span class="bg-white/20 px-2 py-1 rounded-full text-xs">{{ $planesLinea->count() }} actividades</span>
                </div>
                <a href="{{ route('analisis-pasteurizadora.plan-accion.create', ['linea_id' => $linea->id]) }}" 
                   class="btn-agregar-rapido">
                    <i class="fas fa-plus"></i> Agregar Actividad
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Módulo / Componente</th>
                            <th>Actividad</th>
                            <th>PCM 1</th>
                            <th>PCM 2</th>
                            <th>PCM 3</th>
                            <th>PCM 4</th>
                            <th>Acciones</th>
                        </thead>
                        <tbody>
                            @forelse($planesLinea as $index => $plan)
                                @php
                                    $fechas = [
                                        'pcm1' => $plan->plan_accion_pcm1['fecha'] ?? null,
                                        'pcm2' => $plan->plan_accion_pcm2['fecha'] ?? null,
                                        'pcm3' => $plan->plan_accion_pcm3['fecha'] ?? null,
                                        'pcm4' => $plan->plan_accion_pcm4['fecha'] ?? null,
                                    ];
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="font-medium">Módulo {{ $plan->modulo }}</div>
                                        <div class="text-xs text-gray-500">{{ $plan->componente_nombre }}</div>
                                        @if($plan->lado)
                                            <span class="inline-block text-xs px-1 py-0.5 rounded {{ $plan->lado === 'VAPOR' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ $plan->lado }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="max-w-xs">
                                        <div class="text-sm">{{ Str::limit($plan->actividad, 100) }}</div>
                                        @if($plan->nivel)
                                            <div class="text-xs text-gray-500 mt-1"><i class="fas fa-layer-group"></i> {{ $plan->nivel }}</div>
                                        @endif
                                    </td>
                                    @foreach(['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm)
                                        @php
                                            $fecha = $fechas[$pcm];
                                            $fechaClass = '';
                                            if($fecha) {
                                                $dias = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($fecha)->startOfDay(), false);
                                                if($dias < 0) $fechaClass = 'fecha-vencida';
                                                elseif($dias <= 3) $fechaClass = 'fecha-proxima';
                                                elseif($dias <= 7) $fechaClass = 'fecha-cercana';
                                                else $fechaClass = 'fecha-futura';
                                            }
                                        @endphp
                                        <td class="text-center fecha-cell">
                                            @if($fecha)
                                                <span class="{{ $fechaClass }}">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>
                                        <div class="flex gap-1">
                                            <a href="{{ route('analisis-pasteurizadora.edit', $plan->id) }}" 
                                               class="btn-accion btn-editar" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn-accion btn-ver ver-btn" data-id="{{ $plan->id }}" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn-accion btn-checklist checklist-btn {{ $plan->resuelto_por_cambio ? 'completado' : '' }}" 
                                                    data-id="{{ $plan->id }}" title="Marcar realizada">
                                                <i class="fas {{ $plan->resuelto_por_cambio ? 'fa-check' : 'fa-square' }}"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-500">
                                        No hay actividades para esta pasteurizadora
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-700 mb-2">No hay pasteurizadoras registradas</h3>
                <p class="text-gray-500">Primero debe registrar pasteurizadoras para poder crear planes de acción.</p>
            </div>
        @endforelse
    </div>

    {{-- Modales --}}
    <div id="verActividadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="font-bold">Detalles de la Actividad</h3>
                <button class="modal-close modal-close-btn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="detalleActividad">
                <div class="text-center py-4">Cargando...</div>
            </div>
        </div>
    </div>

    <div id="eliminarModal" class="modal">
        <div class="modal-content">
            <div class="modal-header bg-red-50">
                <h3 class="font-bold text-red-700">Confirmar Eliminación</h3>
                <button class="modal-close modal-close-btn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-gray-700 mb-4">¿Está seguro de eliminar la actividad:</p>
                <p class="text-lg font-bold text-gray-900 mb-2" id="actividadEliminar"></p>
                <form id="eliminarForm" method="POST" class="flex justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Eliminar</button>
                    <button type="button" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 modal-close-btn">Cancelar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('.ver-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const modal = document.getElementById('verActividadModal');
            const content = document.getElementById('detalleActividad');
            
            fetch(`/analisis-pasteurizadora/${id}`)
                .then(res => res.json())
                .then(data => {
                    content.innerHTML = `
                        <div class="space-y-4">
                            <div><strong>Línea:</strong> ${data.linea?.nombre || 'N/A'}</div>
                            <div><strong>Módulo:</strong> ${data.modulo}</div>
                            <div><strong>Componente:</strong> ${data.componente_nombre}</div>
                            ${data.lado ? `<div><strong>Lado:</strong> ${data.lado}</div>` : ''}
                            <div><strong>Actividad:</strong><br>${data.actividad}</div>
                            <div><strong>Estado:</strong> ${data.estado}</div>
                        </div>
                    `;
                });
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    });
    
    document.querySelectorAll('.checklist-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(`/analisis-pasteurizadora/${id}/checklist`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.completado) {
                    this.classList.add('completado');
                    this.innerHTML = '<i class="fas fa-check"></i>';
                } else {
                    this.classList.remove('completado');
                    this.innerHTML = '<i class="fas fa-square"></i>';
                }
                location.reload();
            });
        });
    });
    
    document.querySelectorAll('.modal-close-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        });
    });
    </script>
@endsection