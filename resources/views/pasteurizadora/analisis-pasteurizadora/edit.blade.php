@extends('layouts.app')

@section('title', 'Editar Analisis - Pasteurizadora')

@section('content')
@php
    $backUrl = request()->input('redirect_to') ?: route('pasteurizadora.analisis-pasteurizadora.show', $analisis->id);
    $cancelUrl = $backUrl;
    $componentes = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($analisis->linea->nombre ?? null);
    $totalComponentes = (int) ($componentes[$analisis->componente]['cantidad'] ?? $analisis->total_componentes ?? 0);
    $componentesRevisados = \App\Models\AnalisisPasteurizadora::normalizarComponentesRevisados(
        old('componentes_revisados', $analisis->componentes_revisados),
        $totalComponentes
    );
    $esBrazoTorsion = \App\Models\AnalisisPasteurizadora::esBrazoTorsion($analisis->componente);

    if (empty($componentesRevisados) && $analisis->cantidad_componentes_revisados && $totalComponentes > 0) {
        $componentesRevisados = range(1, min($analisis->cantidad_componentes_revisados, $totalComponentes));
    }

    $evidencias = $analisis->evidencia_fotos ?? [];
    if (!is_array($evidencias)) {
        $evidencias = json_decode($evidencias, true) ?? [];
    }

    $estadoBadges = [
        'Buen estado' => 'bg-green-100 text-green-800 border-green-200',
        'Requiere revisión' => 'bg-orange-100 text-orange-800 border-orange-200',
        'Desgaste moderado' => 'bg-amber-100 text-amber-800 border-amber-200',
        'Desgaste severo' => 'bg-orange-100 text-orange-800 border-orange-200',
        'Cambiado' => 'bg-blue-100 text-blue-800 border-blue-200',
    ];
    $estadoBadge = $estadoBadges[$analisis->estado] ?? 'bg-red-100 text-red-800 border-red-200';
    $estadoActualLabel = \App\Models\AnalisisPasteurizadora::getEstadoOpciones()[$analisis->estado]
        ?? (str_contains((string) $analisis->estado, 'Requiere cambio') ? '❌ Dañado - Requiere cambio' : $analisis->estado);
@endphp

<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="mb-8">
        <div class="flex items-start gap-3 mb-4">
            <a href="{{ $backUrl }}" class="mt-1 text-gray-400 hover:text-blue-600 transition" aria-label="Volver">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Editar Analisis de Pasteurizadora</h1>
                <p class="text-gray-600 text-sm mt-1">
                    ID: #{{ $analisis->id }}
                    @if($analisis->numero_orden)
                        <span class="mx-1">|</span> Orden: {{ $analisis->numero_orden }}
                    @endif
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                Revise los campos marcados antes de guardar.
            </div>
        @endif

        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
            <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5 lg:items-center">
                <div class="flex justify-center sm:justify-start">
                    <div class="h-20 w-20 rounded-xl bg-white border border-gray-200 p-3 shadow-sm">
                        <img src="{{ asset('images/icono_pas.png') }}" alt="Pasteurizadora" class="h-full w-full object-contain">
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Linea</p>
                    <p class="text-gray-900">{{ $analisis->linea->nombre ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Modulo</p>
                    <p class="text-gray-900">Modulo {{ $analisis->modulo }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Componente</p>
                    <p class="text-gray-900">{{ $analisis->componente_nombre }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Estado actual</p>
                    <span class="mt-1 inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $estadoBadge }}">
                        {{ $estadoActualLabel }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 mt-4 pt-4 border-t border-gray-300 text-sm sm:grid-cols-3">
                <div>
                    <p class="font-semibold text-gray-600">Nivel</p>
                    <p class="text-gray-900">{{ $analisis->nivel ?: 'Sin nivel' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Lado</p>
                    <p class="text-gray-900">{{ $analisis->lado ?: 'Sin lado' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Fecha de creacion</p>
                    <p class="text-gray-900">{{ optional($analisis->created_at)->format('d/m/Y H:i') ?: 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-5 sm:p-8">
        <form method="POST"
              action="{{ route('pasteurizadora.analisis-pasteurizadora.update', $analisis->id) }}"
              enctype="multipart/form-data"
              id="editarAnalisisPasteurizadoraForm">
            @csrf
            @method('PUT')

            <input type="hidden" name="componente" value="{{ $analisis->componente }}">
            <input type="hidden" name="redirect_to" value="{{ $backUrl }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="modulo" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cubes text-blue-600 mr-1"></i>
                        Modulo
                    </label>
                    <input type="number"
                           name="modulo"
                           id="modulo"
                           value="{{ old('modulo', $analisis->modulo) }}"
                           min="1"
                           readonly
                           class="w-full rounded-lg border-gray-300 bg-gray-100 shadow-sm cursor-not-allowed focus:ring-blue-500 focus:border-blue-500">
                    @error('modulo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha de Analisis *
                    </label>
                    <input type="date"
                           name="fecha_analisis"
                           id="fecha_analisis"
                           value="{{ old('fecha_analisis', optional($analisis->fecha_analisis)->format('Y-m-d')) }}"
                           required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('fecha_analisis') border-red-500 @enderror">
                    @error('fecha_analisis')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                        Nivel *
                    </label>
                    <select name="nivel" id="nivel" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('nivel') border-red-500 @enderror">
                        <option value="">Seleccionar nivel...</option>
                        <option value="SUPERIOR" {{ old('nivel', $analisis->nivel) === 'SUPERIOR' ? 'selected' : '' }}>Nivel Superior</option>
                        <option value="INFERIOR" {{ old('nivel', $analisis->nivel) === 'INFERIOR' ? 'selected' : '' }}>Nivel Inferior</option>
                    </select>
                    @error('nivel')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                        Lado *
                    </label>
                    <select name="lado" id="lado" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('lado') border-red-500 @enderror">
                        <option value="">Seleccionar lado...</option>
                        <option value="VAPOR" {{ old('lado', $analisis->lado) === 'VAPOR' ? 'selected' : '' }}>Lado Vapor</option>
                        <option value="PASILLO" {{ old('lado', $analisis->lado) === 'PASILLO' ? 'selected' : '' }}>Lado Pasillo</option>
                    </select>
                    @error('lado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        Numero de Orden
                    </label>
                    <input type="text"
                           name="numero_orden"
                           id="numero_orden"
                           value="{{ old('numero_orden', $analisis->numero_orden) }}"
                           maxlength="20"
                           placeholder="Ej: 35221456"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('numero_orden') border-red-500 @enderror">
                    @error('numero_orden')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                        Estado *
                    </label>
                    <select name="estado" id="estado" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('estado') border-red-500 @enderror">
                        <option value="">Seleccionar estado...</option>
                        @foreach(\App\Models\AnalisisPasteurizadora::getEstadoOpciones() as $estado => $estadoLabel)
                            <option value="{{ $estado }}" {{ old('estado', $analisis->estado) === $estado ? 'selected' : '' }}>{{ $estadoLabel }}</option>
                        @endforeach
                    </select>
                    @error('estado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

            </div>

            @if($totalComponentes > 0 && !$esBrazoTorsion)
                <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-4">
                        <h3 class="text-sm font-semibold text-indigo-900">
                            <i class="fas fa-clipboard-check mr-1"></i>
                            Componentes revisados
                        </h3>
                        <p class="text-xs font-medium text-indigo-700">
                            {{ count($componentesRevisados) }} de {{ $totalComponentes }} seleccionados
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @for($i = 1; $i <= $totalComponentes; $i++)
                            <label class="flex items-center gap-3 rounded-lg border border-indigo-200 bg-white p-3 cursor-pointer transition hover:border-indigo-400 hover:shadow-sm">
                                <input type="checkbox"
                                       name="componentes_revisados[]"
                                       value="{{ $i }}"
                                       {{ in_array($i, $componentesRevisados, true) ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-cube text-indigo-500 mr-1"></i>
                                    {{ $analisis->componente }} #{{ $i }}
                                </span>
                            </label>
                        @endfor
                    </div>
                    @error('componentes_revisados')<p class="text-red-500 text-sm mt-3">{{ $message }}</p>@enderror
                </div>
            @elseif($esBrazoTorsion)
                <input type="hidden" name="componentes_revisados[]" value="1">
                <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                    <i class="fas fa-info-circle mr-1"></i>
                    Este registro corresponde al Brazo de Torsion del modulo {{ $analisis->modulo }}.
                </div>
            @endif

            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad / Observaciones *
                </label>
                <textarea name="actividad"
                          id="actividad"
                          rows="4"
                          required
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales..."
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('actividad') border-red-500 @enderror">{{ old('actividad', $analisis->actividad) }}</textarea>
                @error('actividad')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            @if(!empty($evidencias))
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-images text-blue-600 mr-1"></i>
                        Evidencias actuales
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($evidencias as $index => $foto)
                            <div class="relative group border border-gray-200 rounded-lg p-2 bg-gray-50 hover:bg-gray-100 transition">
                                <img src="{{ Storage::url($foto) }}"
                                     alt="Evidencia {{ $loop->iteration }}"
                                     class="w-full h-32 object-cover rounded-md border border-gray-300 mb-2">

                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ Storage::url($foto) }}"
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800 text-sm inline-flex items-center gap-1">
                                        <i class="fas fa-expand text-xs"></i>
                                        <span>Ver</span>
                                    </a>

                                    <label class="inline-flex items-center gap-2 cursor-pointer">
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
                    <p class="text-xs text-gray-500 mt-2">Las imagenes se conservaran si no marca eliminar.</p>
                </div>
            @endif

            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Agregar nuevas evidencias
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
                        <input type="file" id="evidencia_fotos_galeria" accept="image/*" multiple class="sr-only">
                    </div>

                    <div>
                        <button type="button"
                                id="btn_evidencia_fotos_camara"
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            <i class="fas fa-camera-retro"></i>
                            Tomar foto ahora
                        </button>
                        <input type="file" id="evidencia_fotos_camara" accept="image/*" capture="environment" multiple class="sr-only">
                    </div>
                </div>

                <p id="fotos_resumen" class="mt-3 text-sm text-gray-500">Sin imagenes seleccionadas</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>

                @error('evidencia_fotos')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                @error('evidencia_fotos.*')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                <a href="{{ $cancelUrl }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition-all shadow-md">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg px-5 py-3 hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Analisis
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
    const botonGaleria = document.getElementById('btn_evidencia_fotos_galeria');
    const botonCamara = document.getElementById('btn_evidencia_fotos_camara');
    const galeriaFotosInput = document.getElementById('evidencia_fotos_galeria');
    const camaraFotosInput = document.getElementById('evidencia_fotos_camara');
    const previewFotos = document.getElementById('preview_fotos');
    const fotosResumen = document.getElementById('fotos_resumen');
    const editarForm = document.getElementById('editarAnalisisPasteurizadoraForm');
    const maxFotoSize = 5 * 1024 * 1024;
    const soportaDataTransfer = typeof DataTransfer !== 'undefined';

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
                fileName.className = 'text-xs text-gray-600 truncate w-24';
                fileName.textContent = file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name;

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
</script>
@endsection
