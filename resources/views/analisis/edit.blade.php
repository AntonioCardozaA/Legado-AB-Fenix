<!-- resources/views/analisis/edit.blade.php -->
@extends('layouts.app')

@section('title', 'Editar Análisis')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Análisis #{{ $analisis->id }}</h1>
        <p class="text-gray-600">Modifique la información del análisis realizado</p>
    </div>

    <form action="{{ route('analisis.update', $analisis) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        
        <!-- Información Básica -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                Información del Análisis
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Línea *</label>
                    <select name="linea_id" required class="w-full rounded-lg border-gray-300">
                        @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" {{ $analisis->linea_id == $linea->id ? 'selected' : '' }}>
                            {{ $linea->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" name="fecha_analisis" required 
                           value="{{ $analisis->fecha_analisis->format('Y-m-d') }}"
                           class="w-full rounded-lg border-gray-300">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Orden *</label>
                    <input type="text" name="numero_orden" required 
                           value="{{ $analisis->numero_orden }}"
                           class="w-full rounded-lg border-gray-300">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Horómetro</label>
                    <input type="number" name="horometro" value="{{ $analisis->horometro }}"
                           class="w-full rounded-lg border-gray-300">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="2"
                              class="w-full rounded-lg border-gray-300">{{ $analisis->observaciones }}</textarea>
                </div>
            </div>
        </div>

        <!-- Mediciones de Elongación -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-ruler-combined mr-2 text-blue-600"></i>
                Mediciones de Elongación
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Lado Bombas -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3">Lado Bombas</h3>
                    <div class="grid grid-cols-4 gap-2">
                        @php
                            $medicionBombas = $analisis->mediciones->where('tipo', 'L_BOMBAS')->first();
                        @endphp
                        @for($i = 1; $i <= 8; $i++)
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Medición {{ $i }}</label>
                            <input type="number" step="0.1" name="mediciones_l_bombas[]" 
                                   value="{{ $medicionBombas ? $medicionBombas->{"medicion_{$i}"} : '' }}"
                                   class="w-full rounded-lg border-gray-300">
                        </div>
                        @endfor
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Juego de Rodaja (mm)</label>
                        <input type="number" step="0.1" name="juego_rodaja_bombas" 
                               value="{{ $analisis->juego_rodaja }}"
                               class="w-32 rounded-lg border-gray-300">
                    </div>
                </div>
                
                <!-- Lado Vapor -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3">Lado Vapor</h3>
                    <div class="grid grid-cols-4 gap-2">
                        @php
                            $medicionVapor = $analisis->mediciones->where('tipo', 'L_VAPOR')->first();
                        @endphp
                        @for($i = 1; $i <= 8; $i++)
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Medición {{ $i }}</label>
                            <input type="number" step="0.1" name="mediciones_l_vapor[]" 
                                   value="{{ $medicionVapor ? $medicionVapor->{"medicion_{$i}"} : '' }}"
                                   class="w-full rounded-lg border-gray-300">
                        </div>
                        @endfor
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Juego de Rodaja (mm)</label>
                        <input type="number" step="0.1" name="juego_rodaja_vapor" 
                               value="{{ $analisis->juego_rodaja_vapor ?? '' }}"
                               class="w-32 rounded-lg border-gray-300">
                    </div>
                </div>
            </div>
        </div>

        <!-- Componentes -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-cogs mr-2 text-blue-600"></i>
                Componentes Revisados
            </h2>
            
            <div class="space-y-4" id="componentes-container">
                @foreach($componentes as $index => $componente)
                @php
                    $analisisComponente = $analisis->componentes->where('componente_id', $componente->id)->first();
                @endphp
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-medium text-gray-800">{{ $componente->nombre }}</h3>
                        <span class="text-sm text-gray-500">Total: {{ $componente->cantidad_total }}</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="componentes[{{ $index }}][componente_id]" value="{{ $componente->id }}">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad Revisada</label>
                            <input type="number" name="componentes[{{ $index }}][cantidad_revisada]" 
                                   max="{{ $componente->cantidad_total }}"
                                   value="{{ $analisisComponente ? $analisisComponente->cantidad_revisada : 0 }}"
                                   class="w-full rounded-lg border-gray-300">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select name="componentes[{{ $index }}][estado]" 
                                    class="w-full rounded-lg border-gray-300">
                                <option value="BUENO" {{ $analisisComponente && $analisisComponente->estado == 'BUENO' ? 'selected' : '' }}>Buen Estado</option>
                                <option value="REGULAR" {{ $analisisComponente && $analisisComponente->estado == 'REGULAR' ? 'selected' : '' }}>Regular</option>
                                <option value="DAÑADO" {{ $analisisComponente && $analisisComponente->estado == 'DAÑADO' ? 'selected' : '' }}>Dañado</option>
                                <option value="REEMPLAZADO" {{ $analisisComponente && $analisisComponente->estado == 'REEMPLAZADO' ? 'selected' : '' }}>Reemplazado</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Actividad Realizada</label>
                            <input type="text" name="componentes[{{ $index }}][actividad]" 
                                   value="{{ $analisisComponente ? $analisisComponente->actividad : '' }}"
                                   class="w-full rounded-lg border-gray-300">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Evidencia</label>
                            <input type="file" name="nuevas_fotos[{{ $componente->id }}][]" multiple 
                                   accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea name="componentes[{{ $index }}][observaciones]" rows="2"
                                class="w-full rounded-lg border-gray-300">{{ $analisisComponente ? $analisisComponente->observaciones : '' }}</textarea>
                    </div>
                    
                    <!-- Evidencia existente -->
                    @if($analisisComponente && $analisisComponente->evidencia_fotos)
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Evidencia Existente</label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($analisisComponente->evidencia_fotos as $fotoIndex => $foto)
                            <div class="relative">
                                <img src="{{ Storage::url($foto) }}" 
                                     class="w-16 h-16 object-cover rounded border">
                                <div class="absolute -top-2 -right-2">
                                    <input type="checkbox" name="eliminar_fotos[{{ $componente->id }}][]" 
                                           value="{{ $foto }}" id="eliminar_{{ $componente->id }}_{{ $fotoIndex }}"
                                           class="hidden">
                                    <label for="eliminar_{{ $componente->id }}_{{ $fotoIndex }}"
                                           class="cursor-pointer bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                        <i class="fas fa-times"></i>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('analisis.show', $analisis) }}" 
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200">
                <i class="fas fa-save mr-2"></i>
                Actualizar Análisis
            </button>
        </div>
    </form>
</div>
@endsection