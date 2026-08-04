@extends('layouts.app')

@section('title', 'Agregar Analisis - Pasteurizadora')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
    $componentesLinea = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
    $totalModulos = \App\Models\AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
    $componentesConfiguracion = collect($componentesLinea)->map(function ($config, $codigo) {
        return [
            'codigo' => $codigo,
            'nombre' => $config['nombre'],
            'cantidad' => (int) ($config['cantidad'] ?? 0),
            'es_brazo_torsion' => \App\Models\AnalisisPasteurizadora::esBrazoTorsion($codigo),
        ];
    })->values();
    $componentesSeleccionadosIniciales = old(
        'componentes_revisados',
        old('numero_componente') ? [old('numero_componente')] : []
    );
@endphp

<style>
    .pasteur-form-shell {
        --primary-blue: #3b82f6;
        --border: #e5e7eb;
        --soft-shadow: 0 10px 15px -3px rgba(15, 23, 42, .10), 0 4px 6px -4px rgba(15, 23, 42, .10);
        width: 100%;
        max-width: min(56rem, 100%);
        overflow-x: clip;
    }

    .pasteur-form-shell * {
        box-sizing: border-box;
        min-width: 0;
    }

    .pasteur-form-card {
        background: #ffffff;
        border: 0;
        border-radius: 1rem;
        box-shadow: var(--soft-shadow);
        padding: clamp(1rem, 4vw, 2rem);
    }

    .pasteur-context {
        background: linear-gradient(to right, #f9fafb, #f3f4f6);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 16px;
        overflow: hidden;
    }

    .pasteur-context img {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
    }

    .pasteur-form-shell label i,
    .pasteur-form-shell p i {
        color: var(--primary-blue);
    }

    .pasteur-form-shell h1,
    .pasteur-form-shell p,
    .pasteur-form-shell span,
    .pasteur-form-shell label,
    .pasteur-form-shell textarea {
        overflow-wrap: anywhere;
    }

    .pasteur-form-shell .create-actions {
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .pasteur-form-shell {
            padding: 1.25rem 0.75rem;
        }

        .pasteur-form-shell h1 {
            font-size: 1.5rem;
            line-height: 1.25;
        }

        .pasteur-context {
            padding: 14px;
        }

        .pasteur-form-shell input,
        .pasteur-form-shell select,
        .pasteur-form-shell textarea {
            font-size: 16px;
        }

        .pasteur-form-shell .create-action,
        .pasteur-form-shell .responsive-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="pasteur-form-shell max-w-4xl mx-auto py-10 px-4">
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
                <a href="{{ $analisisRoute('select-linea') }}"
                   class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-800">
                    Agregar Analisis
                </h1>
        </div>

            <div class="pasteur-context">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="flex-shrink-0">
                        <div class="w-20 h-20 mx-auto md:mx-0">
                            <img src="{{ asset('images/icono-pasteurizadora.png') }}"
                                 alt="Icono de pasteurizadora"
                                 class="w-full h-full object-contain"
                                 onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6 flex-grow">
                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                Linea
                            </p>
                            <p class="text-gray-800 font-medium">{{ $linea->nombre }}</p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-cubes mr-1"></i>
                                Modulo
                            </p>
                            <p class="text-gray-800 font-medium" id="summary-modulo">Sin seleccionar</p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-cog mr-1"></i>
                                Componente
                            </p>
                            <p class="text-gray-800 font-medium" id="summary-componente">Sin seleccionar</p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-hashtag mr-1"></i>
                                Pieza
                            </p>
                            <p class="text-gray-800 font-medium" id="summary-pieza">Sin seleccionar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="pasteur-form-card">
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-800">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle mt-0.5"></i>
                <div>
                    <p class="font-semibold">Este formulario registra analisis normales.</p>
                    <p>Usalo para fallas, ordenes de revision, reportes de daño y condiciones especiales de componentes.</p>
                </div>
            </div>
        </div>

        <form action="{{ $analisisRoute('store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6"
              id="analisisNormalForm">
            @csrf

            <input type="hidden" name="linea_id" value="{{ $linea->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="modulo" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-cubes text-blue-600 mr-1"></i>
                        Modulo *
                    </label>
                    <select id="modulo" name="modulo"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('modulo') border-red-500 @enderror"
                            required>
                        <option value="">Seleccionar modulo...</option>
                        @for($i = 1; $i <= $totalModulos; $i++)
                            <option value="{{ $i }}" {{ (string) old('modulo', $modulo ?? '') === (string) $i ? 'selected' : '' }}>
                                Modulo {{ $i }}
                            </option>
                        @endfor
                    </select>
                    @error('modulo')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="componente" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-cog text-blue-600 mr-1"></i>
                        Componente *
                    </label>
                    <select id="componente" name="componente"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('componente') border-red-500 @enderror"
                            required>
                        <option value="">Seleccionar componente...</option>
                        @foreach($componentesLinea as $codigo => $comp)
                            <option value="{{ $codigo }}" {{ old('componente', $componente ?? '') === $codigo ? 'selected' : '' }}>
                                {{ $comp['nombre'] }} ({{ $comp['cantidad'] }} pza)
                            </option>
                        @endforeach
                    </select>
                    @error('componente')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="lado" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                        Lado del analisis *
                    </label>
                    <select id="lado" name="lado"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('lado') border-red-500 @enderror"
                            required>
                        <option value="">Seleccionar lado...</option>
                        <option value="VAPOR" {{ old('lado', $lado ?? '') === 'VAPOR' ? 'selected' : '' }}>Lado Vapor</option>
                        <option value="PASILLO" {{ old('lado', $lado ?? '') === 'PASILLO' ? 'selected' : '' }}>Lado Pasillo</option>
                    </select>
                    @error('lado')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nivel" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                        Nivel del modulo *
                    </label>
                    <select id="nivel" name="nivel"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('nivel') border-red-500 @enderror"
                            required>
                        <option value="">Seleccionar nivel...</option>
                        <option value="SUPERIOR" {{ old('nivel', $nivel ?? '') === 'SUPERIOR' ? 'selected' : '' }}>Nivel Superior</option>
                        <option value="INFERIOR" {{ old('nivel', $nivel ?? '') === 'INFERIOR' ? 'selected' : '' }}>Nivel Inferior</option>
                    </select>
                    @error('nivel')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div id="checklist-container" class="hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-800 mb-2">
                            <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                            Seleccione los componentes revisados
                        </label>
                    </div>

                    <div id="componentes-checklist" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3"></div>

                    <input type="hidden"
                           name="componentes_revisados"
                           id="componentes_revisados_input"
                           value="{{ json_encode($componentesSeleccionadosIniciales) }}">
                    @error('componentes_revisados')
                        <p class="text-red-500 text-sm mt-3">{{ $message }}</p>
                    @enderror
                    @error('numero_componente')
                        <p class="text-red-500 text-sm mt-3">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha del analisis *
                    </label>
                    <input type="date"
                           name="fecha_analisis"
                           value="{{ old('fecha_analisis', $fechaSugerida ?? now()->format('Y-m-d')) }}"
                           required
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm @error('fecha_analisis') border-red-500 @enderror">
                    @error('fecha_analisis')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        Numero de orden
                    </label>
                    <input type="text"
                           name="numero_orden"
                           value="{{ old('numero_orden') }}"
                           maxlength="8"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           autocomplete="off"
                           placeholder="Ej: 35221456"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm @error('numero_orden') border-red-500 @enderror">
                    @error('numero_orden')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                    Estado del componente *
                </label>
                <select name="estado"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('estado') border-red-500 @enderror"
                        required>
                    <option value="">Seleccionar estado...</option>
                    @foreach(\App\Models\AnalisisPasteurizadora::getEstadoOpciones() as $estado => $label)
                        <option value="{{ $estado }}" {{ old('estado') === $estado ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad realizada y/o observaciones *
                </label>
                <textarea name="actividad"
                          rows="5"
                          placeholder="Describe la falla, la revision realizada, la actividad ejecutada o cualquier observacion relevante..."
                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad') }}</textarea>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencias fotograficas
                </label>
                <input type="file"
                       id="evidencia_fotos"
                       name="evidencia_fotos[]"
                       multiple
                       accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp"
                       class="hidden">
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
                <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p id="fotos_resumen" class="text-sm font-medium text-gray-600">Sin imagenes seleccionadas</p>
                        <p class="text-xs text-gray-500">JPG, PNG, WEBP, GIF o BMP. Max. 5MB por imagen.</p>
                    </div>
                    <div id="preview_fotos" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4"></div>
                </div>

                @error('evidencia_fotos')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
                @error('evidencia_fotos.*')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="create-actions pt-6 border-t border-gray-200">
                <a href="{{ $analisisRoute('index', ['linea_id' => $linea->id]) }}"
                   class="create-action create-action--secondary flex-1">
                    Cancelar
                </a>
                <button type="submit"
                        class="create-action flex-1">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Analisis
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const componentesConfiguracion = @json($componentesConfiguracion);
const moduloSelect = document.getElementById('modulo');
const componenteSelect = document.getElementById('componente');
const ladoSelect = document.getElementById('lado');
const nivelSelect = document.getElementById('nivel');
const checklistContainer = document.getElementById('checklist-container');
const componentesChecklist = document.getElementById('componentes-checklist');
const componentesRevisadosInput = document.getElementById('componentes_revisados_input');
const summaryModulo = document.getElementById('summary-modulo');
const summaryComponente = document.getElementById('summary-componente');
const summaryPieza = document.getElementById('summary-pieza');
const oldComponentesRevisados = @json($componentesSeleccionadosIniciales);

function normalizarSeleccionComponentes(value) {
    let valores = value;

    if (typeof valores === 'string' && valores.trim() !== '') {
        try {
            valores = JSON.parse(valores);
        } catch (error) {
            valores = [];
        }
    }

    if (!Array.isArray(valores)) {
        return [];
    }

    return valores
        .map((item) => parseInt(item, 10))
        .filter((item) => Number.isInteger(item) && item > 0);
}

let componentesSeleccionados = normalizarSeleccionComponentes(oldComponentesRevisados);

function obtenerComponenteSeleccionado() {
    return componentesConfiguracion.find((item) => item.codigo === componenteSelect.value) || null;
}

function actualizarResumenFormulario() {
    const componente = obtenerComponenteSeleccionado();

    summaryModulo.textContent = moduloSelect.value ? `Modulo ${moduloSelect.value}` : 'Sin seleccionar';
    summaryComponente.textContent = componente ? componente.nombre : 'Sin seleccionar';

    if (!componente) {
        summaryPieza.textContent = 'Sin seleccionar';
        return;
    }

    if (componente.es_brazo_torsion) {
        summaryPieza.textContent = 'Brazo por modulo';
        return;
    }

    if (componentesSeleccionados.length === 1) {
        summaryPieza.textContent = `Pieza #${componentesSeleccionados[0]}`;
        return;
    }

    if (componentesSeleccionados.length > 1) {
        summaryPieza.textContent = `${componentesSeleccionados.length} piezas seleccionadas`;
        return;
    }

    if (componente.cantidad === 1) {
        summaryPieza.textContent = 'Pieza unica';
        return;
    }

    summaryPieza.textContent = 'Sin seleccionar';
}

function actualizarComponentesRevisados() {
    const componente = obtenerComponenteSeleccionado();

    if (!componente) {
        componentesSeleccionados = [];
        componentesRevisadosInput.value = JSON.stringify(componentesSeleccionados);
        actualizarResumenFormulario();
        return;
    }

    if (componente.es_brazo_torsion) {
        componentesSeleccionados = [1];
        componentesRevisadosInput.value = JSON.stringify(componentesSeleccionados);
        actualizarResumenFormulario();
        return;
    }

    const checkboxes = componentesChecklist.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');
    componentesSeleccionados = Array.from(checkboxes).map((checkbox) => parseInt(checkbox.dataset.componentValue, 10));
    componentesRevisadosInput.value = JSON.stringify(componentesSeleccionados);
    actualizarResumenFormulario();
}

function renderChecklistComponentes({ reiniciarSeleccion = false } = {}) {
    const componente = obtenerComponenteSeleccionado();
    componentesChecklist.innerHTML = '';

    if (!componente) {
        checklistContainer.classList.add('hidden');
        componentesSeleccionados = [];
        componentesRevisadosInput.value = JSON.stringify(componentesSeleccionados);
        actualizarResumenFormulario();
        return;
    }

    if (componente.es_brazo_torsion) {
        checklistContainer.classList.add('hidden');
        componentesSeleccionados = [1];
        componentesRevisadosInput.value = JSON.stringify(componentesSeleccionados);
        actualizarResumenFormulario();
        return;
    }

    checklistContainer.classList.remove('hidden');

    if (componente.cantidad <= 0) {
        componentesSeleccionados = [];
        componentesRevisadosInput.value = JSON.stringify(componentesSeleccionados);
        componentesChecklist.innerHTML = `
            <div class="col-span-full text-center py-8 bg-yellow-50 rounded-lg border border-yellow-200">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-2"></i>
                <p class="text-yellow-700 font-medium">No se encontraron componentes disponibles para este contexto</p>
            </div>
        `;
        actualizarResumenFormulario();
        return;
    }

    if (reiniciarSeleccion) {
        componentesSeleccionados = componente.cantidad === 1 ? [1] : [];
    } else {
        componentesSeleccionados = componentesSeleccionados.filter((numero) => numero <= componente.cantidad);

        if (componente.cantidad === 1 && componentesSeleccionados.length === 0) {
            componentesSeleccionados = [1];
        }
    }

    for (let indice = 1; indice <= componente.cantidad; indice++) {
        const seleccionado = componentesSeleccionados.includes(indice);
        const label = document.createElement('label');
        label.className = 'flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer';
        label.innerHTML = `
            <input type="checkbox"
                   data-component-value="${indice}"
                   class="w-5 h-5 text-blue-600 rounded cursor-pointer focus:ring-blue-500 componente-checkbox"
                   ${seleccionado ? 'checked' : ''}>
            <span class="flex-1 text-gray-700 font-medium">
                <i class="fas fa-cube text-blue-500 mr-2"></i>
                ${componente.nombre} #${indice}
            </span>
        `;
        componentesChecklist.appendChild(label);
    }

    componentesChecklist.querySelectorAll('.componente-checkbox:not(:disabled)').forEach((checkbox) => {
        checkbox.addEventListener('change', actualizarComponentesRevisados);
    });

    actualizarComponentesRevisados();
}

const evidenciaFotosInput = document.getElementById('evidencia_fotos');
const evidenciaFotosGaleriaInput = document.getElementById('evidencia_fotos_galeria');
const evidenciaFotosCamaraInput = document.getElementById('evidencia_fotos_camara');
const fotosResumen = document.getElementById('fotos_resumen');
const previewFotos = document.getElementById('preview_fotos');
const btnGaleria = document.getElementById('btn_evidencia_fotos_galeria');
const btnCamara = document.getElementById('btn_evidencia_fotos_camara');
const maxFotoSize = 5 * 1024 * 1024;
const extensionesPermitidasFotos = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

function crearDataTransferFotos(files = []) {
    if (typeof DataTransfer === 'undefined') {
        return null;
    }

    try {
        const dataTransfer = new DataTransfer();
        files.forEach((file) => dataTransfer.items.add(file));
        return dataTransfer;
    } catch (error) {
        return null;
    }
}

const fotosDataTransfer = crearDataTransferFotos();
const soportaDataTransferFotos = Boolean(fotosDataTransfer);

function esImagenSeleccionable(file) {
    if (!file) {
        return false;
    }

    if ((file.type || '').startsWith('image/')) {
        return true;
    }

    const extension = (file.name.split('.').pop() || '').toLowerCase();
    return extensionesPermitidasFotos.includes(extension);
}

function getFotosPrincipales() {
    return Array.from(evidenciaFotosInput?.files || []);
}

function getFotosFallback() {
    return [
        ...Array.from(evidenciaFotosGaleriaInput?.files || []),
        ...Array.from(evidenciaFotosCamaraInput?.files || []),
    ];
}

function actualizarResumenFotos(total) {
    fotosResumen.textContent = total === 0
        ? 'Sin imagenes seleccionadas'
        : `${total} imagen${total === 1 ? '' : 'es'} seleccionada${total === 1 ? '' : 's'}`;
}

function renderFotoPreview(files, permitirEliminar) {
    previewFotos.innerHTML = '';
    actualizarResumenFotos(files.length);

    files.forEach((file, index) => {
        if (!esImagenSeleccionable(file)) {
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            const card = document.createElement('div');
            card.className = 'relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50';
            card.innerHTML = `
                <img src="${event.target.result}" alt="${file.name}" class="h-28 w-full object-cover">
                <div class="flex items-center justify-between gap-2 px-3 py-2">
                    <span class="truncate text-xs text-gray-600">${file.name}</span>
                    <button type="button"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200"
                            data-remove-index="${index}">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `;
            card.querySelector('[data-remove-index]')?.classList.toggle('hidden', !permitirEliminar);
            previewFotos.appendChild(card);
        };
        reader.readAsDataURL(file);
    });
}

function agregarArchivos(files) {
    if (!soportaDataTransferFotos) {
        renderFotoPreview(getFotosFallback(), false);
        return;
    }

    const fotosActuales = getFotosPrincipales();
    const firmas = new Set(fotosActuales.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
    const nuevasFotos = [...fotosActuales];

    Array.from(files || []).forEach((file) => {
        if (!esImagenSeleccionable(file)) {
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

    const dataTransfer = crearDataTransferFotos(nuevasFotos);
    if (!dataTransfer) {
        return;
    }

    evidenciaFotosInput.files = dataTransfer.files;
    renderFotoPreview(getFotosPrincipales(), true);
}

previewFotos.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-index]');
    if (!button || !soportaDataTransferFotos) {
        return;
    }

    const removeIndex = Number(button.dataset.removeIndex);
    const fotos = getFotosPrincipales().filter((file, index) => index !== removeIndex);
    const dataTransfer = crearDataTransferFotos(fotos);

    if (dataTransfer) {
        evidenciaFotosInput.files = dataTransfer.files;
        renderFotoPreview(getFotosPrincipales(), true);
    }
});

btnGaleria?.addEventListener('click', () => evidenciaFotosGaleriaInput.click());
btnCamara?.addEventListener('click', () => evidenciaFotosCamaraInput.click());
evidenciaFotosInput?.addEventListener('change', () => renderFotoPreview(getFotosPrincipales(), true));

if (soportaDataTransferFotos) {
    evidenciaFotosGaleriaInput?.addEventListener('change', (event) => {
        agregarArchivos(event.target.files);
        event.target.value = '';
    });
    evidenciaFotosCamaraInput?.addEventListener('change', (event) => {
        agregarArchivos(event.target.files);
        event.target.value = '';
    });
    renderFotoPreview(getFotosPrincipales(), true);
} else {
    evidenciaFotosGaleriaInput.name = 'evidencia_fotos[]';
    evidenciaFotosCamaraInput.name = 'evidencia_fotos[]';
    evidenciaFotosInput.disabled = true;

    const renderizarFallbackFotos = () => renderFotoPreview(getFotosFallback(), false);
    evidenciaFotosGaleriaInput?.addEventListener('change', renderizarFallbackFotos);
    evidenciaFotosCamaraInput?.addEventListener('change', renderizarFallbackFotos);
    renderizarFallbackFotos();
}

componenteSelect.addEventListener('change', () => renderChecklistComponentes({ reiniciarSeleccion: true }));
moduloSelect.addEventListener('change', actualizarResumenFormulario);
ladoSelect.addEventListener('change', actualizarResumenFormulario);
nivelSelect.addEventListener('change', actualizarResumenFormulario);

document.getElementById('analisisNormalForm')?.addEventListener('submit', function(event) {
    const componente = obtenerComponenteSeleccionado();

    if (!componente || componente.es_brazo_torsion || componente.cantidad === 1) {
        actualizarComponentesRevisados();
        return;
    }

    const seleccionados = componentesChecklist.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');

    if (seleccionados.length === 0) {
        event.preventDefault();
        alert('Debe seleccionar al menos un componente revisado.');
        return;
    }

    actualizarComponentesRevisados();
});

renderChecklistComponentes();
actualizarResumenFormulario();
</script>
@endsection
