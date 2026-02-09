@extends('layouts.app')

@section('title', 'Crear Análisis de Componente')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header mejorado --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                Crear Análisis
            </h1>
        </div>
        
        {{-- Información del contexto --}}
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-3">
    {{-- Icono de máquina --}}
    <div class="w-20 h-20 flex-shrink-0">
        <img src="{{ asset('images/icono-maquina.png') }}" 
             alt="Icono de lavadora" 
             class="w-full h-full object-contain">
    </div>
                <div>
                    <p class="text-gray-600 font-semibold">Lavadora</p>
                    <p class="text-gray-800">{{ $linea->nombre ?? 'Lavadora ' . $linea->id }}</p>
                </div>
                <div id="componente-info" class="hidden">
                    <p class="text-gray-600 font-semibold">Componente</p>
                    <p id="componente-nombre" class="text-gray-800"></p>
                </div>
                <div id="reductor-info" class="hidden">
                    <p class="text-gray-600 font-semibold">Reductor</p>
                    <p id="reductor-nombre" class="text-gray-800"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Card Form --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="{{ route('analisis-componentes.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="linea_id" value="{{ $linea->id }}">

            {{-- Select Componente y Reductor --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Componente --}}
                <div>
                    <label for="componente_codigo" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cog text-blue-600 mr-1"></i>
                        Componente *
                    </label>
                    <select id="componente_codigo" name="componente_codigo"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                   @error('componente_codigo') border-red-500 @enderror"
                            required
                            onchange="actualizarInformacion()">
                        <option value="">Seleccionar componente...</option>
                        @foreach($componentesDisponibles as $codigo => $nombre)
                            <option value="{{ $codigo }}"
                                {{ old('componente_codigo') == $codigo ? 'selected' : '' }}
                                data-nombre="{{ $nombre }}">
                                {{ $nombre }} ({{ $codigo }})
                            </option>
                        @endforeach
                    </select>
                    @error('componente_codigo')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reductor --}}
                <div>
                    <label for="reductor" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-compress-alt text-blue-600 mr-1"></i>
                        Reductor *
                    </label>
                    <select id="reductor" name="reductor"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                   @error('reductor') border-red-500 @enderror"
                            required
                            onchange="actualizarInformacion()">
                        <option value="">Seleccionar reductor...</option>
                        @foreach($reductores as $reductor)
                            <option value="{{ $reductor }}"
                                {{ old('reductor') == $reductor ? 'selected' : '' }}>
                                {{ $reductor }}
                            </option>
                        @endforeach
                    </select>
                    @error('reductor')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Fecha y Número de Orden --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Fecha de Análisis --}}
                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha de Análisis *
                    </label>
                    <input type="date" id="fecha_analisis" name="fecha_analisis"
                           value="{{ old('fecha_analisis', date('Y-m-d')) }}"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                  @error('fecha_analisis') border-red-500 @enderror"
                           required>
                    @error('fecha_analisis')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Número de Orden --}}
                <div>
                    <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        Número de Orden *
                    </label>
                    <input type="text" 
                           id="numero_orden"
                           name="numero_orden" 
                           value="{{ old('numero_orden') }}"
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
            </div>

            {{-- Estado/Actividad --}}
            <div class="mb-6">
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-tasks text-blue-600 mr-1"></i>
                    Estado / Condición *
                </label>
                <select name="estado" 
                        id="estado"
                        required
                        class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm sm:text-sm
                        @error('estado') border-red-500 @enderror">
                    <option value="">Seleccionar estado...</option>
                    <option value="Buen estado" {{ old('estado') == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                    <option value="Desgaste moderado" {{ old('estado') == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                    <option value="Desgaste severo" {{ old('estado') == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                    <option value="Dañado - Requiere cambio" {{ old('estado') == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                    <option value="Dañado - Cambiado" {{ old('estado') == 'Dañado - Cambiado' ? 'selected' : '' }}>🔄 Dañado - Cambiado</option>
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Observaciones --}}
            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad / Observaciones *
                </label>
                <textarea id="actividad" name="actividad" rows="4"
                          placeholder="Describa la actividad realizada o notas adicionales sobre el componente..."
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                 @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad') }}</textarea>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Evidencia Fotográfica --}}
            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencia Fotográfica
                </label>
                <input type="file" id="evidencia_fotos" name="evidencia_fotos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm
                              focus:ring-blue-500 focus:border-blue-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100
                              @error('evidencia_fotos.*') border-red-500 @enderror">

                <p class="text-gray-500 text-xs mt-1">Puede seleccionar múltiples imágenes (Formatos: JPG, PNG, GIF. Máx: 2MB cada una)</p>

                {{-- Contenedor para vista previa --}}
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>

                @error('evidencia_fotos.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-componentes.select-linea') }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition-all shadow-md">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg px-5 py-3 hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Análisis
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

    // Vista previa de imágenes
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
                removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity text-xs';
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

    // Validación de número de orden (solo números, máximo 8)
    numeroOrdenInput.addEventListener('input', function(e) {
        // Solo permite números
        this.value = this.value.replace(/[^0-9]/g, '');
        // Limita a 8 caracteres
        if (this.value.length > 8) {
            this.value = this.value.slice(0, 8);
        }
    });

    // Actualizar información al cargar la página si hay valores previos
    if (document.getElementById('componente_codigo').value || document.getElementById('reductor').value) {
        actualizarInformacion();
    }
});

// Función para actualizar la información del encabezado
function actualizarInformacion() {
    const componenteSelect = document.getElementById('componente_codigo');
    const reductorSelect = document.getElementById('reductor');
    
    const componenteInfo = document.getElementById('componente-info');
    const reductorInfo = document.getElementById('reductor-info');
    
    const componenteNombre = document.getElementById('componente-nombre');
    const reductorNombre = document.getElementById('reductor-nombre');
    
    if (componenteSelect.value) {
        const selectedOption = componenteSelect.options[componenteSelect.selectedIndex];
        componenteNombre.textContent = selectedOption.getAttribute('data-nombre') || selectedOption.textContent;
        componenteInfo.classList.remove('hidden');
    } else {
        componenteInfo.classList.add('hidden');
    }
    
    if (reductorSelect.value) {
        reductorNombre.textContent = reductorSelect.options[reductorSelect.selectedIndex].textContent;
        reductorInfo.classList.remove('hidden');
    } else {
        reductorInfo.classList.add('hidden');
    }
}
</script>
@endsection