@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4 mx-auto max-w-7xl">
    
    <!-- Estadísticas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-600 text-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium opacity-90">Total Lineas</h6>
                        <h2 class="text-2xl font-bold">{{ $estadisticas['total_lavadoras'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-yellow-500 text-gray-900 rounded-lg shadow-lg overflow-hidden">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium opacity-90">Pendientes</h6>
                        <h2 class="text-2xl font-bold">{{ $estadisticas['actividades_pendientes'] }}</h2>
                    </div>
                    <i class="fas fa-clock text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="bg-green-600 text-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium opacity-90">Completadas</h6>
                        <h2 class="text-2xl font-bold">{{ $estadisticas['actividades_completadas'] }}</h2>
                    </div>
                    <i class="fas fa-check-circle text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="bg-red-600 text-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium opacity-90">Atrasadas</h6>
                        <h2 class="text-2xl font-bold">{{ $estadisticas['actividades_atrasadas'] }}</h2>
                    </div>
                    <i class="fas fa-exclamation-triangle text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas de fechas próximas -->
    @if(count($alertas) > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 relative" role="alert">
        <button type="button" class="absolute top-4 right-4 text-yellow-600 hover:text-yellow-800" data-bs-dismiss="alert" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-400 text-2xl mr-3 mt-1"></i>
            <div class="flex-1">
                <strong class="text-yellow-800">¡Atención! Fechas próximas a vencer ({{ count($alertas) }}):</strong>
                <div class="mt-2 space-y-2">
                    @foreach($alertas as $alerta)
                    <div class="p-2 rounded 
                        @if($alerta['prioridad'] == 'alta') bg-red-600 text-white
                        @elseif($alerta['prioridad'] == 'media') bg-yellow-500 text-gray-900
                        @else bg-blue-500 text-white @endif">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>{{ $alerta['linea'] }}</strong> - 
                                {{ Str::limit($alerta['actividad'], 50) }} - 
                                <strong>{{ $alerta['pcm'] }}</strong>
                            </div>
                            <div class="text-right">
                                <span class="bg-white text-gray-800 px-2 py-1 rounded text-sm">
                                    {{ $alerta['fecha'] }}
                                </span>
                                @if($alerta['es_manana'])
                                    <span class="bg-red-800 text-white px-2 py-1 rounded text-sm ml-2">¡MAÑANA!</span>
                                @else
                                    <span class="bg-white text-gray-800 px-2 py-1 rounded text-sm ml-2">
                                        {{ $alerta['dias_restantes'] }} día(s)
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

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-lg mb-6">
        <div class="p-4">
            <form method="GET" action="{{ route('plan-accion.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lavadora</label>
                    <select name="linea_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas las lavadoras</option>
                        @foreach($lavadoras as $lavadora)
                            <option value="{{ $lavadora->id }}" {{ $lavadoraId == $lavadora->id ? 'selected' : '' }}>
                                {{ $lavadora->nombre_completo ?? $lavadora->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="todos" {{ $estado == 'todos' ? 'selected' : '' }}>Todos los estados</option>
                        <option value="pendiente" {{ $estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="en_proceso" {{ $estado == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                        <option value="completada" {{ $estado == 'completada' ? 'selected' : '' }}>Completada</option>
                        <option value="atrasada" {{ $estado == 'atrasada' ? 'selected' : '' }}>Atrasada</option>
                    </select>
                </div>
                <div class="md:col-span-3 flex items-end space-x-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 flex items-center">
                        <i class="fas fa-filter mr-2"></i> Filtrar
                    </button>
                    <a href="{{ route('plan-accion.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 transition duration-200 flex items-center">
                        <i class="fas fa-undo mr-2"></i> Limpiar
                    </a>
                </div>
                <div class="md:col-span-2 flex items-end justify-end">
                    <a href="{{ route('plan-accion.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-200 flex items-center w-full justify-center">
                        <i class="fas fa-plus mr-2"></i> Nueva Actividad
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Planes de Acción por Líneas de Lavadoras -->
    <div class="space-y-6">
        @php
            $planesPorLinea = $planes->groupBy(function($plan) {
                return $plan->linea_id ?? 'sin-linea';
            });
        @endphp

        @forelse($lavadoras as $lavadora)
            @php
                $planesLavadora = $planesPorLinea->get($lavadora->id, collect());
                if($planesLavadora->isEmpty() && $lavadoraId != $lavadora->id && $lavadoraId) {
                    continue;
                }
            @endphp
            
            @if($planesLavadora->isNotEmpty() || !$lavadoraId)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h4 class="text-white text-lg font-semibold flex items-center">
                            <i class="fas fa-tshirt mr-2"></i> {{ $lavadora->nombre_completo ?? $lavadora->nombre }}
                            @if($lavadora->codigo)
                                <span class="text-sm ml-2 bg-blue-800 px-2 py-1 rounded-full">Código: {{ $lavadora->codigo }}</span>
                            @endif
                        </h4>
                        <div class="flex items-center space-x-2">
                            <span class="bg-blue-800 text-white px-3 py-1 rounded-full text-sm">
                                <i class="fas fa-tasks mr-1"></i> {{ $planesLavadora->count() }} actividades
                            </span>
                            @php
                                $pendientes = $planesLavadora->whereIn('estado', ['pendiente', 'en_proceso'])->count();
                            @endphp
                            @if($pendientes > 0)
                                <span class="bg-yellow-500 text-gray-900 px-3 py-1 rounded-full text-sm">
                                    <i class="fas fa-clock mr-1"></i> {{ $pendientes }} pendiente(s)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">ACTIVIDAD</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">TIPO MÁQUINA</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">PCM 1</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">PCM 2</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">PCM 3</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">PCM 4</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">ESTADO</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($planesLavadora as $index => $plan)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $plan->actividad }}</div>
                                        @if($plan->responsable)
                                            <div class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-user mr-1"></i> {{ $plan->responsable->name }}
                                            </div>
                                        @endif
                                        @if($plan->observaciones)
                                            <div class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-comment mr-1"></i> {{ Str::limit($plan->observaciones, 50) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($plan->tipo_maquina)
                                            <div class="flex flex-wrap gap-1 justify-center">
                                                @php
                                                    $iconos = [
                                                        'lavadora' => ['icon' => 'fa-tshirt', 'color' => 'blue'],
                                                        'pasteurizadora' => ['icon' => 'fa-temperature-high', 'color' => 'red'],
                                                        'secadora' => ['icon' => 'fa-wind', 'color' => 'cyan'],
                                                        'caldera' => ['icon' => 'fa-fire', 'color' => 'orange'],
                                                        'centrifuga' => ['icon' => 'fa-compact-disc', 'color' => 'purple'],
                                                        'otros' => ['icon' => 'fa-cog', 'color' => 'gray'],
                                                    ];
                                                @endphp
                                                @foreach($plan->tipo_maquina as $tipo)
                                                    @php
                                                        $config = $iconos[$tipo] ?? ['icon' => 'fa-cog', 'color' => 'gray'];
                                                    @endphp
                                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $config['color'] }}-100 text-{{ $config['color'] }}-800 inline-flex items-center" title="{{ ucfirst($tipo) }}">
                                                        <i class="fas {{ $config['icon'] }} mr-1"></i>
                                                        <span class="hidden sm:inline">{{ ucfirst($tipo) }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs">No especificado</span>
                                        @endif
                                    </td>
                                    @foreach(['pcm1', 'pcm2', 'pcm3', 'pcm4'] as $pcm)
                                        @php
                                            $fechaCampo = 'fecha_' . $pcm;
                                            $fecha = $plan->$fechaCampo;
                                        @endphp
                                        <td class="px-4 py-3 text-center fecha-cell">
                                            @if($fecha)
                                                @php
                                                    $dias = \Carbon\Carbon::now()->diffInDays($fecha, false);
                                                    $bgClass = '';
                                                    if($dias < 0) $bgClass = 'bg-red-100 text-red-800 border-red-200';
                                                    elseif($dias <= 3) $bgClass = 'bg-green-100 text-green-800 border-green-200 animate-pulse';
                                                    elseif($dias <= 7) $bgClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                                    else $bgClass = 'bg-gray-100 text-gray-800 border-gray-200';
                                                @endphp
                                                <span class="inline-block px-2 py-1 rounded text-xs font-medium {{ $bgClass }} border"
                                                      data-fecha="{{ $fecha }}"
                                                      data-dias="{{ $dias }}">
                                                    {{ $fecha->format('d/m/Y') }}
                                                    @if($dias >= 0 && $dias <= 7)
                                                        <br><span class="text-xs">({{ $dias }} días)</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $estadoColors = [
                                                'pendiente' => 'bg-yellow-100 text-yellow-800',
                                                'en_proceso' => 'bg-blue-100 text-blue-800',
                                                'completada' => 'bg-green-100 text-green-800',
                                                'atrasada' => 'bg-red-100 text-red-800'
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $estadoColors[$plan->estado] }}">
                                            {{ strtoupper(str_replace('_', ' ', $plan->estado)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-1">
                                            <a href="{{ route('plan-accion.edit', $plan->id) }}" 
                                               class="p-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition duration-200" title="Editar">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <button type="button" 
                                                    class="p-1.5 bg-blue-400 text-white rounded hover:bg-blue-500 transition duration-200 ver-btn" 
                                                    data-id="{{ $plan->id }}"
                                                    title="Ver detalles">
                                                <i class="fas fa-eye text-xs"></i>
                                            </button>
                                            <button type="button" 
                                                    class="p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition duration-200 eliminar-btn" 
                                                    data-id="{{ $plan->id }}"
                                                    data-actividad="{{ $plan->actividad }}"
                                                    title="Eliminar">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-clipboard-list text-4xl text-gray-400 mb-3"></i>
                                            <p class="text-gray-500">No hay actividades para esta lavadora</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        @empty
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="flex flex-col items-center">
                    <i class="fas fa-tshirt text-5xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay lavadoras registradas</h3>
                    <p class="text-gray-500 mb-4">Primero debe registrar lavadoras para poder crear planes de acción.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($planes->hasPages())
    <div class="mt-6">
        <div class="flex flex-col sm:flex-row justify-between items-center">
            <div class="text-sm text-gray-700 mb-2 sm:mb-0">
                Mostrando {{ $planes->firstItem() }} - {{ $planes->lastItem() }} de {{ $planes->total() }} registros
            </div>
            <div>
                {{ $planes->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
    @endif

    <!-- Leyenda de colores -->
    <div class="mt-6 p-3 bg-gray-50 rounded-lg">
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
            <div class="flex items-center text-xs">
                <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                Fecha próxima (1-3 días)
            </div>
            <div class="flex items-center text-xs">
                <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                Fecha próxima (4-7 días)
            </div>
            <div class="flex items-center text-xs">
                <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                Fecha vencida
            </div>
            <div class="flex items-center text-xs">
                <span class="w-3 h-3 bg-gray-500 rounded-full mr-2"></span>
                Fecha futura (>7 días)
            </div>
            <div class="flex items-center text-xs">
                <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                Tipo de máquina
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles -->
<div class="fixed inset-0 overflow-y-auto hidden" id="verActividadModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity modal-overlay" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-blue-600 px-4 py-3 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-white flex items-center">
                    <i class="fas fa-info-circle mr-2"></i> Detalles de la Actividad
                </h3>
                <button type="button" class="text-white hover:text-gray-200 focus:outline-none modal-close" id="closeModalBtn">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div id="detalleActividad">
                    <div class="text-center py-4">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                        <p class="mt-2 text-sm text-gray-500">Cargando...</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm modal-close">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="fixed inset-0 overflow-y-auto hidden" id="eliminarModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-red-600 px-4 py-3 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-white flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Confirmar Eliminación
                </h3>
                <button type="button" class="text-white hover:text-gray-200 focus:outline-none modal-close">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <p class="text-sm text-gray-500">¿Está seguro de eliminar la actividad:</p>
                        <p class="text-sm font-bold text-gray-900 mt-2" id="actividadEliminar"></p>
                        <p class="text-xs text-red-600 mt-2">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="eliminarForm" method="POST" class="sm:flex sm:flex-row-reverse">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Eliminar
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm modal-close">
                        Cancelar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.fecha-cell span {
    transition: all 0.3s ease;
}
.fecha-cell span:hover {
    transform: scale(1.05);
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.opacity-50 {
    opacity: 0.5;
}

/* Estilos para los badges de tipo máquina */
.bg-blue-100 { background-color: #dbeafe; }
.text-blue-800 { color: #1e40af; }
.bg-red-100 { background-color: #fee2e2; }
.text-red-800 { color: #991b1b; }
.bg-cyan-100 { background-color: #cffafe; }
.text-cyan-800 { color: #155e75; }
.bg-orange-100 { background-color: #ffedd5; }
.text-orange-800 { color: #9a3412; }
.bg-purple-100 { background-color: #f3e8ff; }
.text-purple-800 { color: #6b21a8; }
.bg-gray-100 { background-color: #f3f4f6; }
.text-gray-800 { color: #1f2937; }

/* Estilos para impresión */
@media print {
    .btn, [role="alert"], .bg-gradient-to-r, .pagination, .bg-gray-50.rounded-lg {
        display: none !important;
    }
    .bg-white.rounded-lg {
        border: 1px solid #000 !important;
        page-break-inside: avoid;
    }
    th {
        background-color: #ddd !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .fecha-cell span {
        background-color: #fff !important;
        border: 1px solid #000 !important;
        color: #000 !important;
    }
}

/* Espaciado entre líneas */
.space-y-6 > * + * {
    margin-top: 1.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables para los modales
    const verModal = document.getElementById('verActividadModal');
    const eliminarModal = document.getElementById('eliminarModal');
    
    // Función para abrir modal
    function openModal(modal) {
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Función para cerrar modal
    function closeModal(modal) {
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
    
    // Cerrar modal con el botón X o el botón Cerrar
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.fixed');
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // Cerrar modal al hacer clic en el overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function() {
            const modal = this.closest('.fixed');
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // Ver detalles
    document.querySelectorAll('.ver-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            
            openModal(verModal);
            document.getElementById('detalleActividad').innerHTML = `
                <div class="text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                    <p class="mt-2 text-sm text-gray-500">Cargando...</p>
                </div>
            `;
            
            fetch(`/plan-accion/${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    let html = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-2 md:col-span-1">
                                <strong class="text-sm text-gray-600">Lavadora:</strong><br>
                                <span class="text-sm">
                                    ${data.linea ? 
                                        (data.linea.codigo ? data.linea.codigo + ' - ' : '') + (data.linea.nombre || 'No disponible') 
                                        : 'No asignada'}
                                </span>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <strong class="text-sm text-gray-600">Responsable:</strong><br>
                                <span class="text-sm">${data.responsable ? data.responsable.name : 'No asignado'}</span>
                            </div>
                            <div class="col-span-2">
                                <strong class="text-sm text-gray-600">Actividad:</strong><br>
                                <span class="text-sm">${data.actividad || 'No especificada'}</span>
                            </div>
                    `;
                    
                    // Agregar tipos de máquina
                    if (data.tipo_maquina && data.tipo_maquina.length > 0) {
                        const iconos = {
                            'lavadora': { icon: 'fa-tshirt', color: 'blue', nombre: 'Lavadora Industrial' },
                            'pasteurizadora': { icon: 'fa-temperature-high', color: 'red', nombre: 'Pasteurizadora' },
                            'secadora': { icon: 'fa-wind', color: 'cyan', nombre: 'Secadora' },
                            'caldera': { icon: 'fa-fire', color: 'orange', nombre: 'Caldera' },
                            'centrifuga': { icon: 'fa-compact-disc', color: 'purple', nombre: 'Centrífuga' },
                            'otros': { icon: 'fa-cog', color: 'gray', nombre: 'Otros' }
                        };
                        
                        html += `<div class="col-span-2">
                            <strong class="text-sm text-gray-600">Tipo de Máquina:</strong><br>
                            <div class="flex flex-wrap gap-2 mt-1">`;
                        
                        data.tipo_maquina.forEach(tipo => {
                            const config = iconos[tipo] || { icon: 'fa-cog', color: 'gray', nombre: tipo };
                            html += `<span class="px-3 py-1 text-sm rounded-full bg-${config.color}-100 text-${config.color}-800 inline-flex items-center">
                                <i class="fas ${config.icon} mr-2"></i> ${config.nombre}
                            </span>`;
                        });
                        
                        html += `</div></div>`;
                    }
                    
                    const pcmFields = [
                        { campo: 'fecha_pcm1', label: 'PCM 1' },
                        { campo: 'fecha_pcm2', label: 'PCM 2' },
                        { campo: 'fecha_pcm3', label: 'PCM 3' },
                        { campo: 'fecha_pcm4', label: 'PCM 4' }
                    ];
                    
                    pcmFields.forEach(pcm => {
                        if (data[pcm.campo]) {
                            const fecha = new Date(data[pcm.campo]);
                            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit'
                            });
                            html += `
                                <div>
                                    <strong class="text-sm text-gray-600">${pcm.label}:</strong><br>
                                    <span class="text-sm">${fechaFormateada}</span>
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
                    
                    const estadoClass = estadoColors[data.estado] || 'bg-gray-100 text-gray-800';
                    const estadoTexto = data.estado ? data.estado.replace('_', ' ').toUpperCase() : 'NO ESPECIFICADO';
                    
                    html += `
                            <div class="col-span-2 md:col-span-1">
                                <strong class="text-sm text-gray-600">Estado:</strong><br>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${estadoClass}">
                                    ${estadoTexto}
                                </span>
                            </div>
                        </div>
                    `;
                    
                    if (data.observaciones) {
                        html += `
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <strong class="text-sm text-gray-600">Observaciones:</strong><br>
                                <span class="text-sm">${data.observaciones}</span>
                            </div>
                        `;
                    }
                    
                    document.getElementById('detalleActividad').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detalleActividad').innerHTML = `
                        <div class="text-center py-4 text-red-600">
                            <i class="fas fa-exclamation-circle text-3xl mb-2"></i>
                            <p>Error al cargar los detalles</p>
                            <p class="text-xs mt-2">${error.message}</p>
                        </div>
                    `;
                });
        });
    });
    
    // Eliminar actividad
    document.querySelectorAll('.eliminar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const actividad = this.dataset.actividad;
            
            document.getElementById('actividadEliminar').textContent = actividad;
            document.getElementById('eliminarForm').action = `/plan-accion/${id}`;
            
            openModal(eliminarModal);
        });
    });
    
    // Prevenir que los clics dentro del modal cierren el modal
    document.querySelectorAll('.fixed .inline-block').forEach(modalContent => {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Manejar tecla ESC para cerrar modales
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalesAbiertos = document.querySelectorAll('.fixed:not(.hidden)');
            modalesAbiertos.forEach(modal => {
                closeModal(modal);
            });
        }
    });
});
</script>
@endsection