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
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
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
        grid-template-columns: repeat(4, 1fr);
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
    }

    .linea-nombre {
        font-size: 20px;
        font-weight: 700;
    }

    .badge-count {
        background: rgba(255, 255, 255, 0.15);
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
        gap: 10px;
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .btn-agregar-rapido:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
    }

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
    .btn-ver { background: #6b7280; }
    .btn-eliminar { background: #ef4444; }
    .btn-checklist { background: #f59e0b; }
    .btn-checklist.completado { background: #10b981; }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
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
        max-width: 550px;
        width: 100%;
        max-height: 85vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
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

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .col-actividad {
            width: 250px;
        }

        .linea-header {
            flex-direction: column;
            text-align: center;
        }

        .linea-info {
            justify-content: center;
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

            $dias = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($plan->$fechaCampo)->startOfDay(), false);

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

<div class="historico-container">
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
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
                       class="btn-agregar-rapido">
                        <i class="fas fa-plus"></i>
                        <span class="hidden sm:inline">Agregar Actividad</span>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th class="col-actividad">Actividad</th>
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
                                    </td>
                                    @foreach(['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm)
                                        @php
                                            $fechaCampo = 'fecha_' . $pcm;
                                            $fecha = $plan->$fechaCampo ?? null;
                                        @endphp
                                        <td class="text-center fecha-cell">
                                            @if($fecha)
                                                @php
                                                    $dias = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($fecha)->startOfDay(), false);
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
                                    <td colspan="7" class="text-center py-12">
                                        <div class="flex flex-col items-center">
                                            <p class="text-gray-500 mb-4">No hay actividades para esta línea</p>
                                            <a href="{{ route('plan-accion.create', ['tipo' => 'pasteurizadora', 'linea_id' => $linea->id]) }}"
                                               class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all">
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

                <form id="eliminarForm" method="POST" class="flex justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tipo" value="pasteurizadora">
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

            fetch(`/plan-accion/${id}`)
                .then(response => response.json())
                .then(data => {
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
                .catch(() => {
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
            document.getElementById('eliminarForm').action = `/plan-accion/${this.dataset.id}?tipo=pasteurizadora`;
            openModal(eliminarModal);
        });
    });

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
