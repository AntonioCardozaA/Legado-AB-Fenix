@extends('layouts.app')

@section('title', 'Agregar Análisis Rápido')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        {{-- Encabezado --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('analisis-componentes.index', request()->query()) }}"
                   class="text-gray-400 hover:text-amber-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    Agregar Análisis Rápido
                </h1>
            </div>
            
            {{-- Información del contexto --}}
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600 font-semibold">Lavadora</p>
                        <p class="text-gray-800">{{ $linea->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Componente</p>
                        <p class="text-gray-800">{{ $componente->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Reductor</p>
                        <p class="text-gray-800">{{ $reductor }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Formulario --}}
        <form action="{{ route('analisis-componentes.store') }}" 
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6">
            @csrf
            
            {{-- Campos ocultos con datos pre-establecidos --}}
            <input type="hidden" name="linea_id" value="{{ $linea->id }}">
            <input type="hidden" name="componente_id" value="{{ $componente->id }}">
            <input type="hidden" name="reductor" value="{{ $reductor }}">
            <input type="hidden" name="redirect_to" value="{{ url()->previous() }}">
            
            {{-- Fecha del análisis --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="far fa-calendar-alt text-amber-600 mr-1"></i>
                    Fecha del Análisis *
                </label>
                <input type="date" 
                       name="fecha_analisis" 
                       value="{{ $fecha_sugerida }}"
                       required
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                       @error('fecha_analisis') border-red-500 @enderror">
                @error('fecha_analisis')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Número de orden --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-hashtag text-amber-600 mr-1"></i>
                    Número de Orden *
                </label>
                <input type="text" 
                       name="numero_orden" 
                       required
                       placeholder="Ej: 123456"
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                       @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Actividad --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-tasks text-amber-600 mr-1"></i>
                    Actividad / Estado *
                </label>
                <select name="actividad" 
                        required
                        class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                        @error('actividad') border-red-500 @enderror">
                    <option value="">Seleccionar estado...</option>
                    <option value="Buen estado" {{ old('actividad') == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                    <option value="Desgaste moderado" {{ old('actividad') == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                    <option value="Desgaste severo" {{ old('actividad') == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                    <option value="Dañado - Requiere cambio" {{ old('actividad') == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                    <option value="Dañado - Cambiado" {{ old('actividad') == 'Dañado - Cambiado' ? 'selected' : '' }}>🔄 Dañado - Cambiado</option>
                </select>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Observaciones --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sticky-note text-amber-600 mr-1"></i>
                    Actividad
                </label>
                <textarea name="actividad" 
                          rows="3"
                          placeholder="Notas adicionales sobre el componente..."
                          class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                          @error('actividad') border-red-500 @enderror">{{ old('actividad') }}</textarea>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Evidencia Fotográfica (múltiples imágenes) --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-camera text-amber-600 mr-1"></i>
                    Evidencia Fotográfica
                </label>
                <input type="file" 
                       id="evidencia_fotos"
                       name="evidencia_fotos[]" 
                       multiple
                       accept="image/*"
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                       @error('evidencia_fotos.*') border-red-500 @enderror">
                <p class="text-xs text-gray-500 mt-1">Puede seleccionar múltiples imágenes (Formatos: JPG, PNG, GIF. Máx: 2MB cada una)</p>
                
                {{-- Contenedor para vista previa --}}
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
                
                @error('evidencia_fotos.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-componentes.index', request()->query()) }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition-all shadow-md">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg px-5 py-3 hover:from-amber-600 hover:to-amber-700 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Análisis
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Script para vista previa de imágenes --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputFotos = document.getElementById('evidencia_fotos');
    const previewFotos = document.getElementById('preview_fotos');

    inputFotos.addEventListener('change', function() {
        previewFotos.innerHTML = ''; // Limpiar previews anteriores
        const files = Array.from(this.files);

        files.forEach(file => {
            if(!file.type.startsWith('image/')) return; // Solo imágenes
            
            // Validar tamaño (2MB = 2097152 bytes)
            if (file.size > 2097152) {
                alert(`La imagen ${file.name} supera el tamaño máximo de 2MB`);
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'relative group';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-lg border border-gray-200 shadow-sm';
                
                // Botón para eliminar (opcional)
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = function() {
                    imgContainer.remove();
                    // Aquí podrías eliminar el archivo del input también
                };
                
                imgContainer.appendChild(img);
                imgContainer.appendChild(removeBtn);
                previewFotos.appendChild(imgContainer);
            }
            reader.readAsDataURL(file);
        });
    });
});
</script>
@endsection