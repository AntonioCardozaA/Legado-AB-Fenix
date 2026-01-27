@extends('layouts.app')

@section('title', 'Editar Análisis Rápido')

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
                    Editar Análisis Rápido
                </h1>
            </div>

            {{-- Contexto --}}
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600 font-semibold">Lavadora</p>
                        <p class="text-gray-800">{{ $analisisComponente->linea->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Componente</p>
                        <p class="text-gray-800">{{ $analisisComponente->componente->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Reductor</p>
                        <p class="text-gray-800">{{ $analisisComponente->reductor }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulario --}}
        <form action="{{ route('analisis-componentes.update', $analisisComponente->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6">

            @csrf
            @method('PUT')

            <input type="hidden" name="redirect_to" value="{{ url()->previous() }}">
            <input type="hidden" name="linea_id" value="{{ $analisisComponente->linea_id }}">
            <input type="hidden" name="componente_id" value="{{ $analisisComponente->componente_id }}">
            <input type="hidden" name="reductor" value="{{ $analisisComponente->reductor }}">

            {{-- Fecha --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="far fa-calendar-alt text-amber-600 mr-1"></i>
                    Fecha del Análisis *
                </label>
                <input type="date"
                       name="fecha_analisis"
                       value="{{ old('fecha_analisis', optional($analisisComponente->fecha_analisis)->format('Y-m-d')) }}"
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
                       id="numero_orden"
                       name="numero_orden"
                       value="{{ old('numero_orden', $analisisComponente->numero_orden) }}"
                       required
                       maxlength="8"
                       pattern="\d{8}"
                       inputmode="numeric"
                       placeholder="Ej: 12345678"
                       title="Debe contener exactamente 8 dígitos numéricos"
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                       @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Estado --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-tasks text-amber-600 mr-1"></i>
                    Estado del Componente *
                </label>
                <select name="estado"
                        required
                        class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                        @error('estado') border-red-500 @enderror">
                    <option value="">Seleccionar estado...</option>
                    <option value="Buen estado" {{ old('estado', $analisisComponente->estado) == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                    <option value="Desgaste moderado" {{ old('estado', $analisisComponente->estado) == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                    <option value="Desgaste severo" {{ old('estado', $analisisComponente->estado) == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                    <option value="Dañado - Requiere cambio" {{ old('estado', $analisisComponente->estado) == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                    <option value="Dañado - Cambiado" {{ old('estado', $analisisComponente->estado) == 'Dañado - Cambiado' ? 'selected' : '' }}>🔄 Dañado - Cambiado</option>
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actividad / Observaciones --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sticky-note text-amber-600 mr-1"></i>
                    Actividad / Observaciones *
                </label>
                <textarea name="actividad"
                          rows="4"
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales sobre el componente..."
                          required
                          class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm
                          @error('actividad') border-red-500 @enderror">{{ old('actividad', $analisisComponente->actividad) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Describa lo que se realizó durante el análisis o mantenimiento</p>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Evidencias existentes con opción de eliminar --}}
            @if(!empty($analisisComponente->evidencia_fotos) && is_array($analisisComponente->evidencia_fotos))
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-images text-amber-600 mr-1"></i>
                        Evidencias actuales
                    </p>
                    <div class="flex flex-wrap gap-4">
                        @foreach($analisisComponente->evidencia_fotos as $index => $foto)
                            <div class="relative group">
                                <img src="{{ Storage::exists($foto) ? asset('storage/'.$foto) : asset('storage/'.$foto) }}"
                                     class="w-32 h-32 object-cover rounded-lg border border-gray-300 shadow-sm">
                                
                                {{-- Checkbox para eliminar --}}
                                <div class="absolute top-2 right-2 bg-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <input type="checkbox" 
                                           name="eliminar_fotos[]" 
                                           value="{{ $foto }}"
                                           class="rounded text-red-500 focus:ring-red-500"
                                           id="eliminar_foto_{{ $index }}">
                                    <label for="eliminar_foto_{{ $index }}" class="text-red-500 text-xs ml-1 cursor-pointer">
                                        <i class="fas fa-trash"></i>
                                    </label>
                                </div>
                                
                                <p class="text-xs text-gray-500 mt-1 text-center truncate w-32">
                                    {{ basename($foto) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Marque las imágenes que desea eliminar</p>
                </div>
            @endif

            {{-- Nuevas evidencias --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-camera text-amber-600 mr-1"></i>
                    Agregar nuevas evidencias
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
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg px-5 py-3 hover:from-amber-600 hover:to-amber-700 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Análisis
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputFotos = document.getElementById('evidencia_fotos');
    const previewFotos = document.getElementById('preview_fotos');
    const numeroOrdenInput = document.getElementById('numero_orden');

    // Vista previa de nuevas imágenes
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
                
                // Botón para eliminar del preview
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity text-xs';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = function() {
                    imgContainer.remove();
                    // También eliminar el archivo del input
                    const dt = new DataTransfer();
                    const inputFiles = inputFotos.files;
                    
                    for (let i = 0; i < inputFiles.length; i++) {
                        if (inputFiles[i] !== file) {
                            dt.items.add(inputFiles[i]);
                        }
                    }
                    
                    inputFotos.files = dt.files;
                };
                
                imgContainer.appendChild(img);
                imgContainer.appendChild(removeBtn);
                previewFotos.appendChild(imgContainer);
            }
            reader.readAsDataURL(file);
        });
    });

    // Validación de número de orden (solo números, máximo 8)
    if (numeroOrdenInput) {
        numeroOrdenInput.addEventListener('input', function(e) {
            // Solo permite números
            this.value = this.value.replace(/[^0-9]/g, '');
            // Limita a 8 caracteres
            if (this.value.length > 8) {
                this.value = this.value.slice(0, 8);
            }
        });
    }

    // Confirmación antes de eliminar imágenes existentes
    document.querySelectorAll('input[name="eliminar_fotos[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            if (this.checked) {
                if (!confirm('¿Está seguro de que desea eliminar esta imagen?')) {
                    this.checked = false;
                }
            }
        });
    });
});
</script>
@endsection