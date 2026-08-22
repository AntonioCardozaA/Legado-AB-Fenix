@extends('layouts.app')

@section('title', 'Agregar Análisis')

@section('content')
@php
    $reductorLabel = \App\Support\LavadoraCatalog::etiquetaReductorParaValor($linea->nombre ?? null, $reductor);
    $reductorNombre = \App\Support\LavadoraCatalog::nombreReductorParaLinea($linea->nombre ?? null, $reductor);
    $componentesRv250 = array_keys(array_filter(
        \App\Support\LavadoraCatalog::COMPONENTE_NOMBRES,
        fn ($label) => $label === 'RV250 Sin Fin Corona'
    ));
    $nombreComponenteLavadora = function ($nombre, $codigo = null) use ($componentesRv250) {
        $nombreUpper = strtoupper((string) $nombre);
        $codigoUpper = strtoupper((string) $codigo);

        foreach ($componentesRv250 as $codigoBase) {
            if (
                $codigoUpper === $codigoBase
                || str_contains($codigoUpper, $codigoBase)
                || str_contains($nombreUpper, $codigoBase)
            ) {
                return 'RV250 Sin Fin Corona';
            }
        }

        return $nombre;
    };
    $componenteNombreVisible = $nombreComponenteLavadora($componente->nombre ?? '', $componente->codigo ?? null);
@endphp
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        {{-- Encabezado --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('analisis-lavadora.index', ['linea_id' => $linea->id]) }}"
                   class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    Agregar Análisis
                </h1>
            </div>
            
            {{-- Información del contexto --}}
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    {{-- Icono de máquina --}}
                    <div class="flex-shrink-0">
                        <div class="w-20 h-20 mx-auto md:mx-0">
                            <img src="{{ asset('images/icono-maquina.png') }}" 
                                 alt="Icono de lavadora" 
                                 class="w-full h-full object-contain">
                        </div>
                    </div>

                    {{-- Información en grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 flex-grow">
                        {{-- Lavadora --}}
                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-washing-machine mr-1"></i>
                                Lavadora
                            </p>
                            <p class="text-gray-800 font-medium">{{ $linea->nombre }}</p>
                        </div>

                        {{-- Componente --}}
                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-cog mr-1"></i>
                                Componente
                            </p>
                            <p class="text-gray-800 font-medium">{{ $componenteNombreVisible }}</p>
                        </div>  

                        {{-- Reductor --}}
                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-sliders-h mr-1"></i>
                                {{ $reductorLabel }}
                            </p>
                            <p class="text-gray-800 font-medium">{{ $reductorNombre }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Formulario --}}
        <form action="{{ route('analisis-lavadora.store') }}" 
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6">
            @csrf

            @error('error')
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ $message }}
                </div>
            @enderror

            @if(($analisisRealizados ?? collect())->isNotEmpty())
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                    <p class="text-sm font-bold text-blue-900 mb-2">Analisis ya registrados para esta seleccion</p>
                    <div class="space-y-2">
                        @foreach($analisisRealizados as $registroRealizado)
                            <div class="flex flex-wrap items-center gap-2 text-xs text-blue-800">
                                <span class="rounded bg-white px-2 py-1 font-semibold">{{ $registroRealizado->fecha_analisis?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                <span class="rounded bg-white px-2 py-1">{{ $registroRealizado->numero_orden ? 'Orden #' . $registroRealizado->numero_orden : 'Sin orden' }}</span>
                                <span class="rounded bg-blue-100 px-2 py-1 font-semibold">Realizado por: {{ $registroRealizado->usuario?->name ?? 'Usuario no registrado' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            {{-- Campos ocultos con datos pre-establecidos --}}
            <input type="hidden" name="linea_id" value="{{ $linea->id }}">
            
            {{-- IMPORTANTE: Pasar el código base del componente, no el código completo --}}
            @php
                // Extraer el código base del componente
                $codigoBase = $componente->codigo;
                $codigosBase = \App\Support\LavadoraCatalog::COMPONENTE_CODIGOS_BASE;
                
                foreach ($codigosBase as $codigo) {
                    if (str_contains($componente->codigo, $codigo)) {
                        $codigoBase = $codigo;
                        break;
                    }
                }
            @endphp
            
            <input type="hidden" name="componente_codigo" value="{{ $codigoBase }}">
            <input type="hidden" name="reductor" value="{{ $reductor }}">
            <input type="hidden" name="redirect_to" value="{{ $redirect_to }}">
            
            {{-- NUEVO: Selector de Lado (solo para Guías y Catarinas) --}}
            <div id="lado-selector-container" class="mb-6 hidden">
                <label for="lado" class="block text-sm font-semibold text-gray-700 mb-2">
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
                <p class="text-gray-500 text-xs mt-1">Indique si el análisis corresponde al lado vapor o lado pasillo (solo para Guías y Catarinas)</p>
                @error('lado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Fecha del análisis --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                    Fecha del Análisis *
                </label>
                <input type="date" 
                       name="fecha_analisis" 
                       value="{{ $fecha_sugerida }}"
                       required
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                       @error('fecha_analisis') border-red-500 @enderror">
                @error('fecha_analisis')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Número de orden --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                    Numero de orden (opcional)
                </label>
                <input type="number"
                       name="numero_orden"
                       value="{{ old('numero_orden') }}"
                       minlength="8"
                       maxlength="8"
                       inputmode="numeric"
                       pattern="[0-9]{8}"
                       autocomplete="off"
                       placeholder="Ej: 35221456"
                       title="Opcional. Si se captura, debe contener exactamente 8 digitos numericos"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)"
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- ESTADO --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                    Estado del Componente *
                </label>
                <select name="estado" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    <option value="">Seleccionar estado...</option>
                    @foreach(\App\Models\AnalisisLavadora::getEstadoOpciones() as $estado => $label)
                        <option value="{{ $estado }}" {{ old('estado') === $estado ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- ACTIVIDAD (Observaciones/descripción) --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad/Observaciones *
                </label>
                <textarea name="actividad" 
                          rows="4"
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales sobre el componente..."
                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                          @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Describa lo que se realizó durante el análisis o mantenimiento</p>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Evidencia Fotográfica --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencia Fotográfica
                </label>
                <input type="file" id="evidencia_fotos" name="evidencia_fotos[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp" class="hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <button type="button"
                                id="btn_evidencia_fotos_galeria"
                                class="create-action create-action--secondary w-full">
                            <i class="fas fa-images"></i>
                            Subir desde galeria
                        </button>
                        <input type="file"
                               id="evidencia_fotos_galeria"
                               accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp"
                               multiple
                               class="sr-only">
                    </div>

                    <div>
                        <button type="button"
                                id="btn_evidencia_fotos_camara"
                                class="create-action create-action--success w-full">
                            <i class="fas fa-camera-retro"></i>
                            Tomar foto ahora
                        </button>
                        <input type="file"
                               id="evidencia_fotos_camara"
                               accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp"
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
            <div class="create-actions pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-lavadora.index', ['linea_id' => $linea->id]) }}"
                class="create-action create-action--secondary flex-1 analysis-create-action--cancel-mobile">
                    Cancelar
                </a>
                <button type="submit"
                        class="create-action flex-1 analysis-create-action--save-mobile">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Análisis
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/evidence-image-compression.js') }}"></script>
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
    const imageCompression = window.EvidenceImageCompression;
    const maxFotoSize = imageCompression?.MAX_INPUT_BYTES ?? 12 * 1024 * 1024;
    const maxFotoSizeMb = Math.round(maxFotoSize / 1024 / 1024);
    const soportaDataTransfer = typeof DataTransfer !== 'undefined';
    const extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    let procesandoFotos = false;
    
    // Código del componente desde PHP
    const componenteCodigo = '{{ $componente->codigo }}';
    const ladoSelector = document.getElementById('lado-selector-container');
    const ladoInput = document.getElementById('lado');
    
    // Códigos de componentes que requieren selección de lado
    const componentesConLado = [
        'GUI_SUP_TANQUE',
        'GUI_INT_TANQUE', 
        'GUI_INF_TANQUE',
        'CATARINAS'
    ];
    
    // Verificar si el componente actual requiere selector de lado
    function checkComponenteLado() {
        for (let codigo of componentesConLado) {
            if (componenteCodigo.includes(codigo)) {
                ladoSelector.classList.remove('hidden');
                ladoInput.setAttribute('required', 'required');
                
                // Si hay un valor previo de old(), mantenerlo
                @if(old('lado'))
                    ladoInput.value = '{{ old('lado') }}';
                @endif
                
                return;
            }
        }
    }
    
    // Ejecutar verificación al cargar la página
    checkComponenteLado();

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

    function esImagenValida(file) {
        if (!file) {
            return false;
        }

        if ((file.type || '').startsWith('image/')) {
            return true;
        }

        const extension = (file.name.split('.').pop() || '').toLowerCase();
        return extensionesPermitidas.includes(extension);
    }

    function renderPreview(files, permitirEliminar) {
        previewFotos.innerHTML = '';
        actualizarResumenFotos(files.length);

        files.forEach((file, index) => {
            if (!esImagenValida(file)) {
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

    async function agregarFotos(files) {
        if (procesandoFotos) {
            alert('Espera a que terminen de optimizarse las imagenes.');
            return;
        }

        const fotosActuales = getFotosPrincipales();
        const firmas = new Set(fotosActuales.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
        const nuevasFotos = [...fotosActuales];
        procesandoFotos = true;
        fotosResumen.textContent = 'Optimizando imagenes...';

        for (const file of Array.from(files || [])) {
            if (!esImagenValida(file)) {
                alert(`El archivo ${file.name} no es una imagen vÃ¡lida.`);
                continue;
            }

            if (file.size > maxFotoSize) {
                alert(`La imagen ${file.name} supera el tamano maximo de ${maxFotoSizeMb}MB.`);
                continue;
            }

            const firma = `${file.name}-${file.size}-${file.lastModified}`;
            if (firmas.has(firma)) {
                continue;
            }

            const fotoOptimizada = imageCompression
                ? await imageCompression.compressImageFile(file)
                : file;

            firmas.add(firma);
            nuevasFotos.push(fotoOptimizada);
        }

        inputFotos.files = crearDataTransfer(nuevasFotos).files;
        renderPreview(getFotosPrincipales(), true);
        procesandoFotos = false;
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

    analisisForm.addEventListener('submit', function(e) {
        if (procesandoFotos) {
            e.preventDefault();
            alert('Espera a que terminen de optimizarse las imagenes.');
            return;
        }
        // Validar que se haya seleccionado un lado si el selector está visible
        if (!ladoSelector.classList.contains('hidden') && !ladoInput.value) {
            e.preventDefault();
            alert('Debe seleccionar el lado del análisis (Vapor o Pasillo).');
            ladoInput.focus();
            return;
        }

        if (soportaDataTransfer) {
            inputFotos.disabled = getFotosPrincipales().length === 0;
        }
    });
});
</script>
@endsection
