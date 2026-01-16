<!-- resources/views/paros/show.blade.php -->
@extends('layouts.app')

@section('title', 'Detalles del Paro')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Cabecera -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Paro de Máquina</h1>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="text-lg font-medium text-gray-700">{{ $paro->linea->nombre }}</span>
                    <span class="px-3 py-1 rounded-full text-sm font-medium 
                        {{ $paro->tipo == 'Programado' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' }}">
                        {{ $paro->tipo }}
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Supervisor</div>
                <div class="font-medium">{{ $paro->supervisor->name }}</div>
            </div>
        </div>
        
        <!-- Fechas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div class="card p-4">
                <div class="text-sm text-gray-500">Fecha de Inicio</div>
                <div class="text-lg font-semibold">{{ $paro->fecha_inicio->format('d/m/Y') }}</div>
            </div>
            <div class="card p-4">
                <div class="text-sm text-gray-500">Fecha de Fin</div>
                <div class="text-lg font-semibold">{{ $paro->fecha_fin->format('d/m/Y') }}</div>
            </div>
            <div class="card p-4">
                <div class="text-sm text-gray-500">Duración</div>
                <div class="text-lg font-semibold">{{ $paro->fecha_inicio->diffInDays($paro->fecha_fin) }} días</div>
            </div>
        </div>
    </div>

    <!-- Planes de Acción -->
    <div class="card p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Planes de Acción</h2>
            <button onclick="mostrarModalPlan()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i> Agregar Plan
            </button>
        </div>
        
        <div class="space-y-4">
            @foreach($paro->planesAccion as $plan)
            <div class="border rounded-lg p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-medium text-gray-800">{{ $plan->actividad }}</h3>
                        @if($plan->plan_referencia)
                        <span class="text-sm text-gray-500">Ref: {{ $plan->plan_referencia }}</span>
                        @endif
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                            {{ $this->getEstadoColor($plan->estado) }}">
                            {{ $plan->estado }}
                        </span>
                        @if($plan->encontro_dano)
                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Daño
                        </span>
                        @endif
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                    <div>
                        <div class="text-sm text-gray-500">Responsable</div>
                        <div class="font-medium">{{ $plan->responsable->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Fecha Planeada</div>
                        <div class="font-medium">{{ $plan->fecha_planeada->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Fecha Ejecución</div>
                        <div class="font-medium">
                            {{ $plan->fecha_ejecucion ? $plan->fecha_ejecucion->format('d/m/Y') : 'Pendiente' }}
                        </div>
                    </div>
                </div>
                
                @if($plan->descripcion)
                <div class="mb-3">
                    <div class="text-sm text-gray-500">Descripción</div>
                    <div class="text-gray-700">{{ $plan->descripcion }}</div>
                </div>
                @endif
                
                @if($plan->observaciones_dano)
                <div class="mb-3 p-3 bg-red-50 rounded-lg">
                    <div class="text-sm text-red-700 font-medium mb-1">Observaciones de Daño</div>
                    <div class="text-red-600">{{ $plan->observaciones_dano }}</div>
                </div>
                @endif
                
                <!-- Formulario para actualizar estado -->
                <form action="{{ route('planes-accion.actualizar-estado', $plan) }}" method="POST" class="mt-4">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center space-x-4">
                        <select name="estado" class="rounded-lg border-gray-300">
                            <option value="PENDIENTE" {{ $plan->estado == 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                            <option value="EN_PROCESO" {{ $plan->estado == 'EN_PROCESO' ? 'selected' : '' }}>En Proceso</option>
                            <option value="COMPLETADA" {{ $plan->estado == 'COMPLETADA' ? 'selected' : '' }}>Completada</option>
                            <option value="ATRASADA" {{ $plan->estado == 'ATRASADA' ? 'selected' : '' }}>Atrasada</option>
                        </select>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="encontro_dano" id="dano_{{ $plan->id }}" 
                                   value="1" {{ $plan->encontro_dano ? 'checked' : '' }}
                                   class="rounded border-gray-300">
                            <label for="dano_{{ $plan->id }}" class="ml-2 text-sm text-gray-700">Encontró daño</label>
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Actualizar
                        </button>
                    </div>
                    
                    <div class="mt-2">
                        <textarea name="observaciones_dano" rows="2" placeholder="Observaciones del daño"
                                class="w-full rounded-lg border-gray-300">{{ $plan->observaciones_dano }}</textarea>
                    </div>
                </form>
            </div>
            @endforeach
            
            @if($paro->planesAccion->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                <p>No hay planes de acción registrados para este paro.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para agregar plan -->
<div id="modalPlan" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Agregar Plan de Acción</h3>
            <button onclick="cerrarModalPlan()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="{{ route('paros.agregar-plan-accion', $paro) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Actividad *</label>
                    <input type="text" name="actividad" required 
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea name="descripcion" rows="3" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha Planeada *</label>
                    <input type="date" name="fecha_planeada" required 
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Responsable *</label>
                    <select name="responsable_id" required class="mt-1 w-full rounded-lg border-gray-300">
                        <option value="">Seleccionar</option>
                        @foreach(\App\Models\User::where('activo', true)->get() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Plan de Referencia</label>
                    <input type="text" name="plan_referencia" 
                           placeholder="Ej: PLAN 755778"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="cerrarModalPlan()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Guardar Plan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function mostrarModalPlan() {
    document.getElementById('modalPlan').classList.remove('hidden');
}

function cerrarModalPlan() {
    document.getElementById('modalPlan').classList.add('hidden');
}

@php
function getEstadoColor($estado) {
    switch($estado) {
        case 'PENDIENTE': return 'bg-yellow-100 text-yellow-800';
        case 'EN_PROCESO': return 'bg-blue-100 text-blue-800';
        case 'COMPLETADA': return 'bg-green-100 text-green-800';
        case 'ATRASADA': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
@endphp
</script>
@endpush