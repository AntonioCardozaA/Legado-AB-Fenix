@extends('layouts.app')

@section('title', 'Crear Análisis de Componente')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header mejorado --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('analisis-lavadora.select-linea') }}"
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
                    <div class="w-20 h-20 flex-shrink-0">
                        <img src="{{ asset('images/icono-maquina.png') }}" 
                             alt="Icono de lavadora" 
                             class="w-full h-full object-contain">
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Lavadora</p>
                        <p class="text-gray-800">{{ $linea->nombre ?? 'Lavadora ' . $linea->id }}</p>
                    </div>
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
        <form method="POST" action="{{ route('analisis-lavadora.store') }}" enctype="multipart/form-data">
            @csrf
            @error('error')
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ $message }}
                </div>
            @enderror
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
                            onchange="actualizarInformacion(); toggleLadoSelector();">
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

            {{-- NUEVO: Selector de Lado (solo para Guías y Catarinas) --}}
            <div id="lado-selector-container" class="mb-6 hidden">
                <label for="lado" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                    Lado del Análisis *
                </label>
                <select id="lado" name="lado"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                               @error('lado') border-red-500 @enderror">
                    <option value="">Seleccionar lado...</option>
                    <option value="VAPOR" {{ old('lado') == 'VAPOR' ? 'selected' : '' }}>💨 Lado Vapor</option>
                    <option value="PASILLO" {{ old('lado') == 'PASILLO' ? 'selected' : '' }}>🚶 Lado Pasillo</option>
                </select>
                <p class="text-gray-500 text-xs mt-1">Indique si el análisis corresponde al lado vapor o lado pasillo</p>
                @error('lado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
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
                    Estado *
                </label>
                <select name="estado" class="filter-select" required>
                    <option value="">Seleccionar estado...</option>
                    <option value="Buen estado">✅ Buen estado</option>
                    <option value="Desgaste moderado">⚠️ Desgaste moderado</option>
                    <option value="Desgaste severo">⚠️ Desgaste severo</option>
                    <option value="Dañado - Requiere cambio">❌ Dañado - Requiere cambio</option>
                    <option value="Cambiado">🔄 Cambiado</option>
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Observaciones --}}
            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad*
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
                <input type="file" id="evidencia_fotos" name="evidencia_fotos[]" multiple accept="image/*" class="hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <button type="button"
                                id="btn_evidencia_fotos_galeria"
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                            <i class="fas fa-images"></i>
                            Subir desde galeria
                        </button>
                        <input type="file"
                               id="evidencia_fotos_galeria"
                               accept="image/*"
                               multiple
                               class="sr-only">
                    </div>

                    <div>
                        <button type="button"
                                id="btn_evidencia_fotos_camara"
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            <i class="fas fa-camera-retro"></i>
                            Tomar foto ahora
                        </button>
                        <input type="file"
                               id="evidencia_fotos_camara"
                               accept="image/*"
                               capture="environment"
                               multiple
                               class="sr-only">
                    </div>
                </div>
                <p id="fotos_resumen" class="mt-3 text-sm text-gray-500">Sin imagenes seleccionadas</p>
                {{-- Contenedor para vista previa --}}
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>

                @error('evidencia_fotos')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                @error('evidencia_fotos.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-lavadora.select-linea') }}"
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
    const analisisForm = document.querySelector('form');
    const inputFotos = document.getElementById('evidencia_fotos');
    const botonGaleria = document.getElementById('btn_evidencia_fotos_galeria');
    const botonCamara = document.getElementById('btn_evidencia_fotos_camara');
    const galeriaFotosInput = document.getElementById('evidencia_fotos_galeria');
    const camaraFotosInput = document.getElementById('evidencia_fotos_camara');
    const previewFotos = document.getElementById('preview_fotos');
    const fotosResumen = document.getElementById('fotos_resumen');
    const numeroOrdenInput = document.getElementById('numero_orden');
    const componenteSelect = document.getElementById('componente_codigo');
    const ladoSelector = document.getElementById('lado-selector-container');
    const ladoInput = document.getElementById('lado');
    const maxFotoSize = 5 * 1024 * 1024;
    const soportaDataTransfer = typeof DataTransfer !== 'undefined';

    // Función para mostrar/ocultar el selector de lado
    window.toggleLadoSelector = function() {
        const componenteSeleccionado = componenteSelect.value;
        // Códigos de componentes que requieren selección de lado
        const componentesConLado = [
            'GUI_SUP_TANQUE',    // Guía Superior
            'GUI_INT_TANQUE',     // Guía Intermedia
            'GUI_INF_TANQUE',     // Guía Inferior
            'CATARINAS'           // Catarinas
        ];
        
        if (componentesConLado.includes(componenteSeleccionado)) {
            ladoSelector.classList.remove('hidden');
            ladoInput.setAttribute('required', 'required');
        } else {
            ladoSelector.classList.add('hidden');
            ladoInput.removeAttribute('required');
            ladoInput.value = ''; // Limpiar el valor cuando se oculta
        }
    };

    // Vista previa de imágenes
    function actualizarResumenFotos(totalFotos) {
        fotosResumen.textContent = totalFotos
            ? `${totalFotos} imagen${totalFotos === 1 ? '' : 'es'} seleccionada${totalFotos === 1 ? '' : 's'}`
            : 'Sin imagenes seleccionadas';
    }

    function crearDataTransfer(files) {
        const dataTransfer = new DataTransfer();
        files.forEach((file) => dataTransfer.items.add(file));
        return dataTransfer;
    }

    function getFotosPrincipales() {
        return Array.from(inputFotos.files || []);
    }

    function getFotosFallback() {
        return [
            ...Array.from(galeriaFotosInput.files || []),
            ...Array.from(camaraFotosInput.files || []),
        ];
    }

    function renderPreview(files, permitirEliminar) {
        previewFotos.innerHTML = '';
        actualizarResumenFotos(files.length);

        files.forEach((file, index) => {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'relative group';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-lg border border-gray-200 shadow-sm';
                imgContainer.appendChild(img);

                if (permitirEliminar) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity text-xs flex items-center justify-center';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.onclick = function() {
                        const fotos = getFotosPrincipales();
                        fotos.splice(index, 1);
                        inputFotos.files = crearDataTransfer(fotos).files;
                        renderPreview(getFotosPrincipales(), true);
                    };
                    imgContainer.appendChild(removeBtn);
                }

                previewFotos.appendChild(imgContainer);
            };

            reader.readAsDataURL(file);
        });
    }

    function agregarFotos(files) {
        const fotosActuales = getFotosPrincipales();
        const firmas = new Set(fotosActuales.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
        const nuevasFotos = [...fotosActuales];

        Array.from(files || []).forEach((file) => {
            if (!file.type.startsWith('image/')) {
                alert(`El archivo ${file.name} no es una imagen vÃ¡lida.`);
                return;
            }

            if (file.size > maxFotoSize) {
                alert(`La imagen ${file.name} supera el tamaÃ±o mÃ¡ximo de 5MB.`);
                return;
            }

            const firma = `${file.name}-${file.size}-${file.lastModified}`;
            if (firmas.has(firma)) {
                return;
            }

            firmas.add(firma);
            nuevasFotos.push(file);
        });

        inputFotos.files = crearDataTransfer(nuevasFotos).files;
        renderPreview(getFotosPrincipales(), true);
    }

    botonGaleria.addEventListener('click', function() {
        galeriaFotosInput.click();
    });

    botonCamara.addEventListener('click', function() {
        camaraFotosInput.click();
    });

    inputFotos.addEventListener('change', function() {
        renderPreview(getFotosPrincipales(), true);
    });

    if (soportaDataTransfer) {
        galeriaFotosInput.addEventListener('change', function() {
            agregarFotos(this.files);
            this.value = '';
        });

        camaraFotosInput.addEventListener('change', function() {
            agregarFotos(this.files);
            this.value = '';
        });

        renderPreview(getFotosPrincipales(), true);
    } else {
        galeriaFotosInput.name = 'evidencia_fotos[]';
        camaraFotosInput.name = 'evidencia_fotos[]';
        inputFotos.disabled = true;

        const renderizarFallback = function() {
            renderPreview(getFotosFallback(), false);
        };

        galeriaFotosInput.addEventListener('change', renderizarFallback);
        camaraFotosInput.addEventListener('change', renderizarFallback);
        renderizarFallback();
    }

    numeroOrdenInput.addEventListener('input', function(e) {
        // Solo permite números
        this.value = this.value.replace(/[^0-9]/g, '');
        // Limita a 8 caracteres
        if (this.value.length > 8) {
            this.value = this.value.slice(0, 8);
        }
    });

    // Escuchar cambios en el selector de componente
    componenteSelect.addEventListener('change', function() {
        toggleLadoSelector();
    });

    analisisForm.addEventListener('submit', function() {
        if (soportaDataTransfer) {
            inputFotos.disabled = getFotosPrincipales().length === 0;
        }
    });

    // Actualizar información al cargar la página si hay valores previos
    if (document.getElementById('componente_codigo').value || document.getElementById('reductor').value) {
        actualizarInformacion();
        toggleLadoSelector(); // Verificar si debe mostrarse el selector de lado
    }
});

// Función para actualizar la información del encabezado
window.actualizarInformacion = function() {
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
