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
@endphp

<style>
    .pasteur-form-shell {
        --primary-blue: #2563eb;
        --border: #e5e7eb;
        --soft-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    .pasteur-form-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--soft-shadow);
        padding: 24px;
    }

    .pasteur-context {
        background: linear-gradient(to right, #f9fafb, #ffffff);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px;
    }

    .pasteur-context img {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 8px;
    }

    .pasteur-form-shell label i,
    .pasteur-form-shell p i {
        color: var(--primary-blue);
    }
</style>

<div class="pasteur-form-shell max-w-5xl mx-auto px-4 py-8">
    <div class="pasteur-form-card">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ $analisisRoute('select-linea') }}"
                   class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 transition hover:bg-gray-200 hover:text-blue-600">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
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
                                <i class="fas fa-temperature-high mr-1"></i>
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

        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-800">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle mt-0.5"></i>
                <div>
                    <p class="font-semibold">Este formulario registra analisis normales.</p>
                    <p>Usalo para fallas, ordenes de revision, reportes de dano y condiciones especiales de componentes.</p>
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

            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <div>
                        <label for="numero_componente" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-list-ol text-blue-600 mr-1"></i>
                            Numero especifico del componente
                        </label>
                        <select id="numero_componente" name="numero_componente"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('numero_componente') border-red-500 @enderror">
                            <option value="">Seleccionar componente...</option>
                        </select>
                        @error('numero_componente')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="numero-componente-help" class="rounded-lg border border-indigo-200 bg-white px-4 py-3 text-sm text-indigo-800">
                        Selecciona primero el componente para mostrar las piezas disponibles.
                    </div>
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
                           maxlength="50"
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
                    <div id="preview_fotos" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"></div>
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
const numeroComponenteSelect = document.getElementById('numero_componente');
const numeroComponenteHelp = document.getElementById('numero-componente-help');
const summaryModulo = document.getElementById('summary-modulo');
const summaryComponente = document.getElementById('summary-componente');
const summaryPieza = document.getElementById('summary-pieza');
const oldNumeroComponente = @json(old('numero_componente'));

function obtenerComponenteSeleccionado() {
    return componentesConfiguracion.find((item) => item.codigo === componenteSelect.value) || null;
}

function actualizarResumenFormulario() {
    const componente = obtenerComponenteSeleccionado();
    const numero = numeroComponenteSelect.value;

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

    if (numero) {
        summaryPieza.textContent = `Pieza #${numero}`;
        return;
    }

    if (componente.cantidad === 1) {
        summaryPieza.textContent = 'Pieza unica';
        return;
    }

    summaryPieza.textContent = 'Sin seleccionar';
}

function renderNumeroComponenteOptions() {
    const componente = obtenerComponenteSeleccionado();
    numeroComponenteSelect.innerHTML = '<option value="">Seleccionar componente...</option>';
    numeroComponenteSelect.disabled = true;

    if (!componente) {
        numeroComponenteHelp.textContent = 'Selecciona primero el componente para mostrar las piezas disponibles.';
        actualizarResumenFormulario();
        return;
    }

    if (componente.es_brazo_torsion) {
        numeroComponenteSelect.innerHTML = '<option value="1" selected>Registro unico por modulo</option>';
        numeroComponenteSelect.disabled = true;
        numeroComponenteHelp.textContent = 'Brazo de torsion: el modulo identifica la pieza analizada.';
        actualizarResumenFormulario();
        return;
    }

    if (componente.cantidad <= 1) {
        numeroComponenteSelect.innerHTML = '<option value="1" selected>Pieza unica</option>';
        numeroComponenteSelect.disabled = true;
        numeroComponenteHelp.textContent = 'Este componente solo tiene una pieza configurada en el modulo.';
        actualizarResumenFormulario();
        return;
    }

    for (let indice = 1; indice <= componente.cantidad; indice++) {
        const option = document.createElement('option');
        option.value = indice;
        option.textContent = `Pieza #${indice}`;
        if (String(oldNumeroComponente || '') === String(indice)) {
            option.selected = true;
        }
        numeroComponenteSelect.appendChild(option);
    }

    numeroComponenteSelect.disabled = false;
    numeroComponenteHelp.textContent = `Selecciona cual de las ${componente.cantidad} piezas del modulo estas analizando.`;
    actualizarResumenFormulario();
}

const evidenciaFotosInput = document.getElementById('evidencia_fotos');
const evidenciaFotosGaleriaInput = document.getElementById('evidencia_fotos_galeria');
const evidenciaFotosCamaraInput = document.getElementById('evidencia_fotos_camara');
const fotosResumen = document.getElementById('fotos_resumen');
const previewFotos = document.getElementById('preview_fotos');
const btnGaleria = document.getElementById('btn_evidencia_fotos_galeria');
const btnCamara = document.getElementById('btn_evidencia_fotos_camara');
const dt = new DataTransfer();

function syncFotosInput() {
    evidenciaFotosInput.files = dt.files;
    const total = dt.files.length;
    fotosResumen.textContent = total === 0
        ? 'Sin imagenes seleccionadas'
        : `${total} imagen${total === 1 ? '' : 'es'} seleccionada${total === 1 ? '' : 's'}`;
}

function renderFotoPreview() {
    previewFotos.innerHTML = '';

    Array.from(dt.files).forEach((file, index) => {
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
            previewFotos.appendChild(card);
        };
        reader.readAsDataURL(file);
    });
}

function agregarArchivos(files) {
    Array.from(files || []).forEach((file) => {
        dt.items.add(file);
    });

    syncFotosInput();
    renderFotoPreview();
}

previewFotos.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-index]');
    if (!button) {
        return;
    }

    const removeIndex = Number(button.dataset.removeIndex);
    const rebuilt = new DataTransfer();

    Array.from(dt.files).forEach((file, index) => {
        if (index !== removeIndex) {
            rebuilt.items.add(file);
        }
    });

    while (dt.items.length > 0) {
        dt.items.remove(0);
    }

    Array.from(rebuilt.files).forEach((file) => dt.items.add(file));
    syncFotosInput();
    renderFotoPreview();
});

btnGaleria?.addEventListener('click', () => evidenciaFotosGaleriaInput.click());
btnCamara?.addEventListener('click', () => evidenciaFotosCamaraInput.click());
evidenciaFotosGaleriaInput?.addEventListener('change', (event) => agregarArchivos(event.target.files));
evidenciaFotosCamaraInput?.addEventListener('change', (event) => agregarArchivos(event.target.files));
componenteSelect.addEventListener('change', renderNumeroComponenteOptions);
numeroComponenteSelect.addEventListener('change', actualizarResumenFormulario);
moduloSelect.addEventListener('change', actualizarResumenFormulario);

renderNumeroComponenteOptions();
actualizarResumenFormulario();
syncFotosInput();
</script>
@endsection
