<!-- resources/views/paros/create.blade.php -->
@extends('layouts.app')

@section('title', 'Programar Paro de Máquina')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Programar Paro de Máquina</h1>
        <p class="text-gray-600">Registre un nuevo paro programado o de emergencia</p>
    </div>

    <form action="{{ route('paros.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Información del Paro</h2>
            
            <div class="space-y-4">
                <!-- Línea -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Línea *</label>
                    <select name="linea_id" required class="w-full rounded-lg border-gray-300">
                        <option value="">Seleccione una línea</option>
                        @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" {{ old('linea_id') == $linea->id ? 'selected' : '' }}>
                            {{ $linea->nombre }} - {{ $linea->descripcion }}
                        </option>
                        @endforeach
                    </select>
                    @error('linea_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Tipo de Paro -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Paro *</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer rounded-lg border border-gray-300 p-4 focus:outline-none">
                            <input type="radio" name="tipo" value="Programado" class="sr-only" 
                                   {{ old('tipo') == 'Programado' ? 'checked' : '' }}>
                            <div class="flex w-full items-center justify-between">
                                <div class="flex items-center">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900">Programado</p>
                                        <p class="text-gray-500">Mantenimiento preventivo planeado</p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-white">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="12" fill="#10b981" />
                                        <path d="M7 13l3 3 7-7" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative flex cursor-pointer rounded-lg border border-gray-300 p-4 focus:outline-none">
                            <input type="radio" name="tipo" value="Emergencia" class="sr-only"
                                   {{ old('tipo') == 'Emergencia' ? 'checked' : '' }}>
                            <div class="flex w-full items-center justify-between">
                                <div class="flex items-center">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900">Emergencia</p>
                                        <p class="text-gray-500">Paro no planeado por falla</p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-white">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="12" fill="#ef4444" />
                                        <path d="M12 8v4m0 4h.01" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('tipo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Fechas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio *</label>
                        <input type="datetime-local" name="fecha_inicio" required 
                               value="{{ old('fecha_inicio') }}"
                               class="w-full rounded-lg border-gray-300">
                        @error('fecha_inicio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Fin *</label>
                        <input type="datetime-local" name="fecha_fin" required 
                               value="{{ old('fecha_fin') }}"
                               class="w-full rounded-lg border-gray-300">
                        @error('fecha_fin')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3" 
                              class="w-full rounded-lg border-gray-300">{{ old('descripcion') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Describa el motivo del paro y actividades principales planeadas.</p>
                </div>
                
                <!-- Prioridad -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                    <select name="prioridad" class="w-full rounded-lg border-gray-300">
                        <option value="baja" {{ old('prioridad') == 'baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ old('prioridad') == 'media' ? 'selected' : '' }}>Media</option>
                        <option value="alta" {{ old('prioridad') == 'alta' ? 'selected' : '' }}>Alta</option>
                        <option value="critica" {{ old('prioridad') == 'critica' ? 'selected' : '' }}>Crítica</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Análisis Relevantes -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Análisis Relacionados</h2>
            <p class="text-sm text-gray-600 mb-4">Seleccione los análisis que motivan este paro</p>
            
            <div class="space-y-3">
                @foreach(\App\Models\Analisis::with('linea')
                    ->where('fecha_analisis', '>=', now()->subMonths(3))
                    ->orderBy('fecha_analisis', 'desc')
                    ->limit(10)
                    ->get() as $analisis)
                <label class="flex items-start space-x-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="analisis_relacionados[]" value="{{ $analisis->id }}"
                           class="mt-1 rounded border-gray-300">
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-medium text-gray-800">{{ $analisis->linea->nombre }}</span>
                                <span class="text-sm text-gray-500 ml-2">{{ $analisis->fecha_analisis->format('d/m/Y') }}</span>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $analisis->elongacion_promedio > 178.19 ? 'bg-red-100 text-red-800' : 
                                   ($analisis->elongacion_promedio > 176 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                {{ number_format($analisis->elongacion_promedio, 1) }} mm
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-600">
                            Orden: {{ $analisis->numero_orden }} • 
                            Componentes: {{ $analisis->componentes->whereIn('estado', ['DAÑADO', 'REEMPLAZADO'])->count() }} dañados
                        </div>
                    </div>
                </label>
                @endforeach
                
                @if(\App\Models\Analisis::count() == 0)
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-clipboard-list text-3xl mb-2"></i>
                    <p>No hay análisis registrados</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('paros.index') }}" 
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200">
                <i class="fas fa-calendar-plus mr-2"></i>
                Programar Paro
            </button>
        </div>
    </form>
</div>

<script>
// Validación de fechas
document.addEventListener('DOMContentLoaded', function() {
    const fechaInicio = document.querySelector('input[name="fecha_inicio"]');
    const fechaFin = document.querySelector('input[name="fecha_fin"]');
    
    fechaInicio.addEventListener('change', function() {
        if (fechaInicio.value && fechaFin.value && fechaFin.value < fechaInicio.value) {
            fechaFin.value = fechaInicio.value;
        }
    });
    
    fechaFin.addEventListener('change', function() {
        if (fechaInicio.value && fechaFin.value && fechaFin.value < fechaInicio.value) {
            alert('La fecha de fin no puede ser anterior a la fecha de inicio');
            fechaFin.value = fechaInicio.value;
        }
    });
});
</script>
@endsection