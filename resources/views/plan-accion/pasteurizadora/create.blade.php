@extends('layouts.app')

@section('title', 'Nueva Actividad - Pasteurizadora')

@section('content')
<div class="container-fluid px-4 py-4 mx-auto max-w-7xl">
    <div class="flex justify-center">
        <div class="w-full md:w-2/3">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-green-600 px-6 py-4">
                    <h4 class="text-white text-lg font-semibold flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i> 
                        Nueva Actividad - Pasteurizadora 
                        @if(isset($lineaSeleccionada))
                            | P-{{ str_pad($lineaSeleccionada, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </h4>
                </div>

                <div class="p-6">
                    <form action="{{ route('pasteurizadora.analisis-pasteurizadora.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Línea</label>
                            <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md flex items-center">
                                <img src="{{ asset('images/icono-pasteurizadora.png') }}" class="w-10 h-8 mr-2 object-contain">
                                Pasteurizadora P-{{ str_pad($lineaSeleccionada, 2, '0', STR_PAD_LEFT) }}
                                @php $lineaObj = \App\Models\Linea::find($lineaSeleccionada); @endphp
                                @if($lineaObj && $lineaObj->nombre_completo)
                                    <span class="ml-2 text-xs text-gray-500">({{ $lineaObj->nombre_completo }})</span>
                                @endif
                            </div>
                            <input type="hidden" name="linea_id" value="{{ $lineaSeleccionada }}">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Módulo <span class="text-red-500">*</span></label>
                            <input type="number" name="modulo" id="modulo" value="{{ old('modulo') }}" min="1" max="16"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                   placeholder="Ej: 1, 2, 3..." required>
                            @error('modulo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Componente <span class="text-red-500">*</span></label>
                            <select name="componente" id="componente" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                <option value="">Seleccionar componente...</option>
                                @foreach(\App\Models\AnalisisPasteurizadora::getComponentesPorLinea($lineaObj->nombre ?? 'P-03') as $key => $comp)
                                    <option value="{{ $key }}" {{ old('componente') == $key ? 'selected' : '' }}>{{ $comp['nombre'] }}</option>
                                @endforeach
                            </select>
                            @error('componente')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lado <span class="text-red-500">*</span></label>
                            <select name="lado" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                <option value="">Seleccionar lado...</option>
                                <option value="VAPOR" {{ old('lado') == 'VAPOR' ? 'selected' : '' }}>💨 Lado Vapor</option>
                                <option value="PASILLO" {{ old('lado') == 'PASILLO' ? 'selected' : '' }}>🚶 Lado Pasillo</option>
                            </select>
                            @error('lado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nivel</label>
                            <select name="nivel" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Seleccionar nivel...</option>
                                <option value="SUPERIOR" {{ old('nivel') == 'SUPERIOR' ? 'selected' : '' }}>⬆️ Nivel Superior</option>
                                <option value="INFERIOR" {{ old('nivel') == 'INFERIOR' ? 'selected' : '' }}>⬇️ Nivel Inferior</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Actividad a Realizar <span class="text-red-500">*</span></label>
                            <textarea name="actividad" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                      placeholder="Describa detalladamente la actividad de mantenimiento a realizar..." required>{{ old('actividad') }}</textarea>
                            @error('actividad')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Número de Orden <span class="text-red-500">*</span></label>
                            <input type="text" name="numero_orden" value="{{ old('numero_orden') }}" maxlength="20"
                                   placeholder="Ej: OT-2024-001" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            @error('numero_orden')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado <span class="text-red-500">*</span></label>
                            <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                <option value="">Seleccionar estado...</option>
                                <option value="Buen estado" {{ old('estado') == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                                <option value="Desgaste moderado" {{ old('estado') == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                                <option value="Desgaste severo" {{ old('estado') == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                                <option value="Dañado - Requiere cambio" {{ old('estado') == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                                <option value="Cambiado" {{ old('estado') == 'Cambiado' ? 'selected' : '' }}>🔄 Cambiado</option>
                            </select>
                            @error('estado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            @foreach(['1','2','3','4'] as $n)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha PCM {{ $n }}</label>
                                    <input type="date" name="fecha_pcm{{ $n }}" value="{{ old('fecha_pcm'.$n) }}"
                                           min="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="requiere_paro" value="1" {{ old('requiere_paro') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-green-600">
                                <span class="ml-2 text-sm text-gray-600">Requiere paro de línea</span>
                            </label>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index', isset($lineaSeleccionada) ? ['linea_id' => $lineaSeleccionada] : []) }}"
                               class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> Cancelar
                            </a>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center">
                                <i class="fas fa-save mr-2"></i> Guardar Actividad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const modulo = document.getElementById('modulo').value;
        if (!modulo || modulo < 1 || modulo > 16) {
            e.preventDefault();
            alert('El módulo debe estar entre 1 y 16');
            return;
        }
        const componente = document.getElementById('componente').value;
        if (!componente) {
            e.preventDefault();
            alert('Debe seleccionar un componente');
            return;
        }
    });
});
</script>
@endsection
