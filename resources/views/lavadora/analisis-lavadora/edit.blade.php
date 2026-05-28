@extends('layouts.app')

@section('title', "Editar Análisis de Componente")

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            @php
                $backUrl = request()->has('redirect_to') 
                    ? request()->input('redirect_to')
                    : url()->previous();
            @endphp
            <a href="{{ $backUrl }}"
               class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    Editar Análisis de Componente
                </h1>
                <p class="text-gray-600 text-sm mt-1">
                    ID: #{{ $analisisComponente->id }} | 
                    @if($analisisComponente->numero_orden)
                        Orden: {{ $analisisComponente->numero_orden }}
                    @endif
                </p>
            </div>
        </div>
        
        {{-- Mostrar mensajes de Ã©xito/error --}}
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        {{-- InformaciÃ³n del contexto --}}
<div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200 mb-6">

    {{-- Fila superior --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center text-sm">

        {{-- Ãcono de mÃ¡quina --}}
        <div class="flex justify-center md:justify-start">
            <div class="w-20 h-20">
                <img 
                    src="{{ asset('images/icono-maquina.png') }}" 
                    alt="Icono de lavadora"
                    class="w-full h-full object-contain">
            </div>
        </div>

        {{-- Lavadora --}}
        <div>
            <p class="text-gray-600 font-semibold">Lavadora</p>
            <p class="text-gray-800">
                {{ $analisisComponente->linea->nombre ?? 'Lavadora ' . $analisisComponente->linea_id }}
            </p>
        </div>

        {{-- Componente --}}
        <div>
            <p class="text-gray-600 font-semibold">Componente</p>
            <p class="text-gray-800">
                {{ $analisisComponente->componente->nombre ?? 'Componente no encontrado' }}
                @if($analisisComponente->componente->codigo ?? false)
                    <span class="text-gray-500">
                        ({{ $analisisComponente->componente->codigo }})
                    </span>
                @endif
            </p>
        </div>

        {{-- Reductor --}}
        <div>
            <p class="text-gray-600 font-semibold">Reductor</p>
            <p class="text-gray-800">{{ $analisisComponente->reductor }}</p>
        </div>

    </div>

    {{-- Fila inferior --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-300 text-sm">

        <div>
            <p class="text-gray-600 font-semibold">Fecha del análisis</p>
            <p class="text-gray-800">
                {{ \Carbon\Carbon::parse($analisisComponente->fecha_analisis)->format('d/m/Y') }}
            </p>
        </div>

        <div>
            <p class="text-gray-600 font-semibold">Fecha de creación</p>
            <p class="text-gray-800">
                {{ \Carbon\Carbon::parse($analisisComponente->created_at)->format('d/m/Y H:i') }}
            </p>
        </div>

    </div>

</div>


    {{-- Card Form --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" 
              action="{{ route('analisis-lavadora.update', $analisisComponente->id) }}" 
              enctype="multipart/form-data"
              id="editarAnalisisForm">
            @csrf
            @method('PUT')
            
            {{-- Campos ocultos para mantener los valores --}}
            <input type="hidden" name="componente_id" value="{{ $analisisComponente->componente_id }}">
            <input type="hidden" name="reductor" value="{{ $analisisComponente->reductor }}">
            <input type="hidden" name="fecha_analisis" value="{{ $analisisComponente->fecha_analisis }}">
            <input type="hidden" name="redirect_to" value="{{ request()->input('redirect_to') ?? url()->previous() }}">

            {{-- Solo NÃºmero de Orden --}}
            <div class="mb-6">
                <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                    Número de Orden *
                </label>
                <input type="text" 
                       id="numero_orden"
                       name="numero_orden" 
                       value="{{ old('numero_orden', $analisisComponente->numero_orden) }}"
                       required
                       maxlength="20"
                       placeholder="Ej: 35221456"
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                       @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Estado --}}
            <div class="mb-6">
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                    Estado del Componente *
                </label>
               <select id="estado"
                       name="estado"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('estado') border-red-500 @enderror"
                       required>
                    @foreach(\App\Models\AnalisisLavadora::getEstadoOpciones() as $estado => $label)
                        <option value="{{ $estado }}" {{ old('estado', $analisisComponente->estado) === $estado ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
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
                <textarea id="actividad" 
                          name="actividad" 
                          rows="4"
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales sobre el componente..."
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                 @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad', $analisisComponente->actividad) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Describa lo que se realizó durante el análisis o mantenimiento</p>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Evidencia Fotográfica Existente --}}
            @php
                $evidencias = $analisisComponente->evidencia_fotos ?? [];
                $evidencias = is_array($evidencias) ? $evidencias : json_decode($evidencias, true) ?? [];
            @endphp
            
            @if(!empty($evidencias))
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-images text-blue-600 mr-1"></i>
                    Evidencias Actuales
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($evidencias as $index => $ruta)
                    <div class="relative group border border-gray-200 rounded-lg p-2 bg-gray-50 hover:bg-gray-100 transition">
                        {{-- Imagen --}}
                        <div class="mb-2">
                            <img src="{{ Storage::url($ruta) }}" 
                                 alt="Evidencia {{ $loop->iteration }}"
                                 class="w-full h-32 object-cover rounded-md border border-gray-300">
                        </div>
                        
                        {{-- Controles --}}
                        <div class="flex justify-between items-center">
                            <a href="{{ Storage::url($ruta) }}" 
                               target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                                <i class="fas fa-expand text-xs"></i>
                                <span>Ver</span>
                            </a>
                            
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" 
                                       name="eliminar_fotos[]" 
                                       value="{{ $index }}"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-red-600 text-sm hover:text-red-800">
                                    <i class="fas fa-trash text-xs"></i>
                                    Eliminar
                                </span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-2">Marque las casillas para eliminar evidencias</p>
            </div>
            @endif

            {{-- Agregar Nuevas Evidencias --}}
            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Agregar Nuevas Evidencias
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
                @php
                    $cancelUrl = request()->has('redirect_to') 
                        ? request()->input('redirect_to')
                        : url()->previous();
                @endphp
                <a href="{{ $cancelUrl }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition-all shadow-md">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg px-5 py-3 hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Análisis
                </button>
            </div>
        </form>
        
        {{-- Formulario de eliminación separado --}}
        @can('delete', $analisisComponente)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-semibold text-red-800 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Zona de Peligro
                </h3>
                <p class="text-sm text-red-600 mb-3">
                    La eliminación es permanente y no se puede deshacer. Todas las evidencias también serán eliminadas.
                </p>
                <button type="button"
                        onclick="confirmarEliminar()"
                        class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg px-4 py-2 hover:from-red-600 hover:to-red-700 transition-all shadow-md hover:shadow-lg text-sm">
                    <i class="fas fa-trash mr-2"></i>
                    Eliminar Análisis
                </button>
            </div>
        </div>
        @endcan
    </div>
</div>

{{-- Modal de confirmaciÃ³n para eliminar --}}
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2 text-center">Confirmar Eliminación</h3>
            <div class="px-7 py-3">
                <p class="text-sm text-gray-500 text-center">
                    ¿Está seguro que desea eliminar este análisis?<br>
                    <strong>ID: #{{ $analisisComponente->id }}</strong><br>
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="flex gap-3 px-4 py-3">
                <button onclick="cerrarModal()"
                        class="flex-1 bg-gray-300 text-gray-700 rounded-md px-4 py-2 hover:bg-gray-400">
                    Cancelar
                </button>
                <form id="deleteForm" 
                      method="POST" 
                      action="{{ route('analisis-lavadora.destroy', $analisisComponente->id) }}"
                      class="flex-1">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="{{ request()->input('redirect_to') }}">
                    <button type="submit"
                            class="w-full bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputFotos = document.getElementById('evidencia_fotos');
    const botonGaleria = document.getElementById('btn_evidencia_fotos_galeria');
    const botonCamara = document.getElementById('btn_evidencia_fotos_camara');
    const galeriaFotosInput = document.getElementById('evidencia_fotos_galeria');
    const camaraFotosInput = document.getElementById('evidencia_fotos_camara');
    const previewFotos = document.getElementById('preview_fotos');
    const fotosResumen = document.getElementById('fotos_resumen');
    const editarForm = document.getElementById('editarAnalisisForm');
    const maxFotoSize = 5 * 1024 * 1024;
    const soportaDataTransfer = typeof DataTransfer !== 'undefined';
    // DepuraciÃ³n: Verificar datos del formulario antes de enviar
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
                imgContainer.className = 'relative group border border-gray-200 rounded-lg p-2 bg-gray-50';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-md border border-gray-300 mb-2';

                const fileName = document.createElement('p');
                fileName.className = 'text-xs text-gray-600 truncate';
                fileName.textContent = file.name.length > 15
                    ? file.name.substring(0, 15) + '...'
                    : file.name;

                imgContainer.appendChild(img);
                imgContainer.appendChild(fileName);

                if (permitirEliminar) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity text-xs';
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
                alert(`El archivo ${file.name} no es una imagen valida.`);
                return;
            }

            if (file.size > maxFotoSize) {
                alert(`La imagen ${file.name} supera el tamano maximo de 5MB.`);
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

    editarForm.addEventListener('submit', function() {
        if (soportaDataTransfer) {
            inputFotos.disabled = getFotosPrincipales().length === 0;
        }
    });
});

// Funciones para el modal de eliminaciÃ³n
function confirmarEliminar() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
@endsection
