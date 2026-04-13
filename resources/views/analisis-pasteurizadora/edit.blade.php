@extends('layouts.app')

@section('title', 'Editar Análisis - Pasteurizadora')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('analisis-pasteurizadora.show', $analisis->id) }}"
               class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                Editar Análisis
            </h1>
        </div>
        
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-600 font-semibold">Línea</p>
                    <p class="text-gray-800">{{ $analisis->linea->nombre ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-600 font-semibold">Módulo</p>
                    <p class="text-gray-800">Módulo {{ $analisis->modulo }}</p>
                </div>
                <div>
                    <p class="text-gray-600 font-semibold">Componente</p>
                    <p class="text-gray-800">{{ $analisis->componente_nombre }}</p>
                </div>
                @if($analisis->lado)
                <div>
                    <p class="text-gray-600 font-semibold">Lado</p>
                    <p class="text-gray-800">{{ $analisis->lado }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="{{ route('analisis-pasteurizadora.update', $analisis->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="modulo" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cubes text-blue-600 mr-1"></i>
                        Módulo *
                    </label>
                    <input type="number" name="modulo" id="modulo" value="{{ old('modulo', $analisis->modulo) }}"
                           min="1" max="16" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('modulo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                        Nivel
                    </label>
                    <select name="nivel" id="nivel" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar nivel...</option>
                        <option value="SUPERIOR" {{ old('nivel', $analisis->nivel) == 'SUPERIOR' ? 'selected' : '' }}>⬆️ Nivel Superior</option>
                        <option value="INFERIOR" {{ old('nivel', $analisis->nivel) == 'INFERIOR' ? 'selected' : '' }}>⬇️ Nivel Inferior</option>
                    </select>
                    @error('nivel')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                        Lado *
                    </label>
                    <select name="lado" id="lado" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Seleccionar lado...</option>
                        <option value="VAPOR" {{ old('lado', $analisis->lado) == 'VAPOR' ? 'selected' : '' }}>💨 Lado Vapor</option>
                        <option value="PASILLO" {{ old('lado', $analisis->lado) == 'PASILLO' ? 'selected' : '' }}>🚶 Lado Pasillo</option>
                    </select>
                    @error('lado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha de Análisis *
                    </label>
                    <input type="date" name="fecha_analisis" id="fecha_analisis" 
                           value="{{ old('fecha_analisis', $analisis->fecha_analisis->format('Y-m-d')) }}"
                           required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('fecha_analisis')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        Número de Orden *
                    </label>
                    <input type="text" name="numero_orden" id="numero_orden" 
                           value="{{ old('numero_orden', $analisis->numero_orden) }}"
                           maxlength="20" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('numero_orden')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                        Estado *
                    </label>
                    <select name="estado" id="estado" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Seleccionar estado...</option>
                        @foreach(\App\Models\AnalisisPasteurizadora::ESTADOS as $estado)
                            <option value="{{ $estado }}" {{ old('estado', $analisis->estado) == $estado ? 'selected' : '' }}>
                                @if($estado == 'Buen estado') ✅ Buen estado
                                @elseif($estado == 'Desgaste moderado') ⚠️ Desgaste moderado
                                @elseif($estado == 'Desgaste severo') ⚠️ Desgaste severo
                                @elseif($estado == 'Dañado - Requiere cambio') ❌ Dañado - Requiere cambio
                                @else 🔄 Cambiado
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('estado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="responsable" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-user text-blue-600 mr-1"></i>
                        Responsable
                    </label>
                    <input type="text" name="responsable" id="responsable" 
                           value="{{ old('responsable', $analisis->responsable) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('responsable')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Cantidad de piezas revisadas --}}
            @if($analisis->total_piezas)
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h3 class="text-sm font-semibold text-blue-800 mb-3">
                    <i class="fas fa-clipboard-list mr-1"></i>
                    Registro de Piezas
                </h3>
                <div>
                    <label for="revisadas_piezas" class="block text-sm font-medium text-gray-700 mb-1">
                        Piezas Revisadas
                    </label>
                    <input type="number" name="revisadas_piezas" id="revisadas_piezas"
                           value="{{ old('revisadas_piezas', $analisis->revisadas_piezas) }}"
                           min="0" max="{{ $analisis->total_piezas }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Total de piezas en el módulo: <span class="font-semibold">{{ $analisis->total_piezas }}</span>
                    </p>
                </div>
            </div>
            @endif

            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad *
                </label>
                <textarea name="actividad" id="actividad" rows="4" required
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('actividad', $analisis->actividad) }}</textarea>
                @error('actividad')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6">
                <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-comment-dots text-blue-600 mr-1"></i>
                    Observaciones
                </label>
                <textarea name="observaciones" id="observaciones" rows="3"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('observaciones', $analisis->observaciones) }}</textarea>
                @error('observaciones')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Evidencia fotográfica existente --}}
            @if($analisis->evidencia_fotos && count($analisis->evidencia_fotos) > 0)
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-images text-blue-600 mr-1"></i>
                    Imágenes actuales
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach($analisis->evidencia_fotos as $index => $foto)
                    <div class="relative group">
                        <img src="{{ Storage::url($foto) }}" alt="Evidencia {{ $index + 1 }}" 
                             class="w-full h-24 object-cover rounded-lg border border-gray-200">
                        <button type="button" 
                                onclick="eliminarFoto({{ $index }})"
                                class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Subir nuevas imágenes --}}
            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Agregar más imágenes
                </label>
                <input type="file" name="evidencia_fotos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm
                              file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100">
                <p class="text-gray-500 text-xs mt-1">Puede agregar más imágenes (Formatos: JPG, PNG. Máx: 5MB cada una)</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-pasteurizadora.show', $analisis->id) }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg px-5 py-3 hover:from-blue-600 hover:to-blue-700 transition shadow-md">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Análisis
                </button>
            </div>
        </form>
    </div>
</div>

<form id="deleteFotoForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    document.getElementById('evidencia_fotos')?.addEventListener('change', function() {
        const preview = document.getElementById('preview_fotos');
        preview.innerHTML = '';
        const files = Array.from(this.files);
        
        files.forEach(file => {
            if (!file.type.startsWith('image/')) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.createElement('div');
                container.className = 'relative group';
                container.innerHTML = `
                    <img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                    <button type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center" onclick="this.parentElement.remove()">×</button>
                `;
                preview.appendChild(container);
            }
            reader.readAsDataURL(file);
        });
    });
    
    function eliminarFoto(index) {
        if (confirm('¿Eliminar esta imagen?')) {
            const form = document.getElementById('deleteFotoForm');
            form.action = "{{ route('analisis-pasteurizadora.delete-foto', ['id' => $analisis->id, 'fotoIndex' => '']) }}/" + index;
            form.submit();
        }
    }
</script>
@endsection