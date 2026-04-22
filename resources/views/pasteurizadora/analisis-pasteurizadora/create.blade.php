@extends('layouts.app')

@section('title', ($modoQuick ?? false) ? 'Agregar Análisis Rápido - Pasteurizadora' : 'Crear Análisis de Componente - Pasteurizadora')

@section('content')
@php
    $componentesLinea = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
    $totalModulos = \App\Models\AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
    $estadoRevision = $estadoRevision ?? [];
    $modoQuick = $modoQuick ?? false;
@endphp

<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', request()->query()) }}"
                   class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $modoQuick ? 'Agregar Análisis Rápido' : 'Crear Análisis' }}
                </h1>
            </div>

            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
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
                                Línea
                            </p>
                            <p class="text-gray-800 font-medium">{{ $linea->nombre ?? 'Sin línea' }}</p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-cubes mr-1"></i>
                                Módulo
                            </p>
                            <p class="text-gray-800 font-medium" id="summary-modulo">
                                {{ $modulo ? 'Módulo ' . $modulo : '—' }}
                            </p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-cog mr-1"></i>
                                Componente
                            </p>
                            <p class="text-gray-800 font-medium" id="summary-componente">
                                {{ $nombreComponente ?: 'No especificado' }}
                            </p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-layer-group mr-1"></i>
                                Nivel
                            </p>
                            <p class="text-gray-800 font-medium" id="summary-nivel">
                                @if($nivel)
                                    {{ $nivel === 'SUPERIOR' ? '⬆️ Nivel Superior' : '⬇️ Nivel Inferior' }}
                                @else
                                    <span class="text-gray-400">No especificado</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ $modoQuick ? route('pasteurizadora.analisis-pasteurizadora.store-quick') : route('pasteurizadora.analisis-pasteurizadora.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6"
              id="analisisForm">
            @csrf

            <input type="hidden" name="linea_id" value="{{ $linea->id }}">
            <input type="hidden" name="seguir_flujo_revision" value="1">

            <div>
                <label for="modulo" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-cubes text-blue-600 mr-1"></i>
                    Módulo *
                </label>
                <select id="modulo" name="modulo"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('modulo') border-red-500 @enderror"
                        required>
                    <option value="">Seleccionar módulo...</option>
                    @for($i = 1; $i <= $totalModulos; $i++)
                        <option value="{{ $i }}" {{ old('modulo', $modulo) == $i ? 'selected' : '' }}>
                            Módulo {{ $i }}
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
                        <option value="{{ $codigo }}" {{ old('componente', $componente) == $codigo ? 'selected' : '' }}>
                            {{ $comp['nombre'] }} ({{ $comp['cantidad'] }} und)
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
                    Lado del Análisis *
                </label>
                <select id="lado" name="lado"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('lado') border-red-500 @enderror"
                        required>
                    <option value="">Seleccionar lado...</option>
                    <option value="VAPOR" {{ old('lado', $lado) == 'VAPOR' ? 'selected' : '' }}>💨 Lado Vapor</option>
                    <option value="PASILLO" {{ old('lado', $lado) == 'PASILLO' ? 'selected' : '' }}>🚶 Lado Pasillo</option>
                </select>
                @error('lado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nivel" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                    Nivel del Módulo *
                </label>
                <select id="nivel" name="nivel"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('nivel') border-red-500 @enderror"
                        required>
                    <option value="">Seleccionar nivel...</option>
                    <option value="SUPERIOR" {{ old('nivel', $nivel) == 'SUPERIOR' ? 'selected' : '' }}>⬆️ Nivel Superior</option>
                    <option value="INFERIOR" {{ old('nivel', $nivel) == 'INFERIOR' ? 'selected' : '' }}>⬇️ Nivel Inferior</option>
                </select>
                @error('nivel')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="siguiente-revision-alert"
                 class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
            </div>

            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border border-indigo-200">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-indigo-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-tasks text-indigo-600"></i>
                        Estado de Revisión por Nivel
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="estado-revision-grid">
                    @foreach(\App\Models\AnalisisPasteurizadora::NIVELES as $nivelKey)
                        @php
                            $datosNivel = $estadoRevision[$nivelKey] ?? ['completado' => false, 'lados_pendientes' => \App\Models\AnalisisPasteurizadora::LADOS];
                            $nivelLabel = $nivelKey === 'SUPERIOR' ? '⬆️ Nivel Superior' : '⬇️ Nivel Inferior';
                        @endphp
                        <div class="p-4 rounded-lg {{ $datosNivel['completado'] ? 'bg-green-100 border border-green-300' : 'bg-white border border-indigo-200' }}"
                             data-nivel-card="{{ $nivelKey }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-gray-800">{{ $nivelLabel }}</span>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold {{ $datosNivel['completado'] ? 'bg-green-600 text-white' : 'bg-amber-600 text-white' }}"
                                      data-status-badge>
                                    <i class="fas {{ $datosNivel['completado'] ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                    {{ $datosNivel['completado'] ? 'Completado' : 'Pendiente' }}
                                </span>
                            </div>
                            <div data-status-detail>
                                @if($datosNivel['completado'])
                                    <div class="text-sm text-green-700">
                                        <i class="fas fa-check mr-1"></i>
                                        Ambos lados revisados correctamente
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-sm text-gray-700">
                                        <span class="font-medium">Lados pendientes:</span>
                                        <div class="flex gap-2">
                                            @foreach($datosNivel['lados_pendientes'] as $ladoPendiente)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $ladoPendiente === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                                    <i class="fas {{ $ladoPendiente === 'VAPOR' ? 'fa-wind' : 'fa-walking' }}"></i>
                                                    {{ $ladoPendiente === 'VAPOR' ? 'Vapor' : 'Pasillo' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="checklist-container" class="{{ $totalPiezas > 0 ? '' : 'hidden' }}">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-800 mb-2">
                            <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                            Checklist de piezas a revisar
                        </label>
                        <p class="text-sm text-blue-800 mb-3">
                            Marque una o varias piezas para este registro. Las piezas ya revisadas en este lado y nivel aparecen bloqueadas.
                        </p>
                        <div id="remaining-info" class="bg-blue-100 border border-blue-400 rounded-lg p-3 mb-3 {{ $alreadyReviewedCount > 0 ? '' : 'hidden' }}">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Ya revisados en este lado y nivel:</strong>
                                <span id="already-reviewed-count">{{ $alreadyReviewedCount }}</span> de
                                <span id="total-count">{{ $totalPiezas }}</span> piezas
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1" id="already-reviewed-badges">
                                @foreach($alreadyReviewedComponents as $compNum)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-200 text-blue-800 rounded text-xs">#{{ $compNum }}</span>
                                @endforeach
                            </div>
                            <p class="text-sm text-blue-800 mt-2">
                                <strong>Pendientes en este lado y nivel:</strong>
                                <span id="remaining-count">{{ $remainingPiezas }}</span> piezas
                            </p>
                        </div>
                    </div>

                    <div id="componentes-checklist" class="grid grid-cols-2 md:grid-cols-3 gap-3"></div>
                    <input type="hidden" name="componentes_revisados" id="componentes_revisados_input" value="{{ json_encode(old('componentes_revisados', [])) }}">

                    <div id="lados-pendientes-alert"
                         class="{{ !empty($ladosPendientes) && $lado ? '' : 'hidden' }} mt-4 p-3 bg-yellow-100 border border-yellow-400 rounded-lg text-sm text-yellow-800">
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    Marque los componentes que fueron revisados en este análisis
                </p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                    Fecha del Análisis *
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
                    Número de Orden *
                </label>
                <input type="text"
                       name="numero_orden"
                       value="{{ old('numero_orden') }}"
                       required
                       maxlength="50"
                       placeholder="Ej: OT-2024-001"
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                    Estado del Componente *
                </label>
                <select name="estado" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('estado') border-red-500 @enderror" required>
                    <option value="">Seleccionar estado...</option>
                    @foreach(\App\Models\AnalisisPasteurizadora::ESTADOS as $estado)
                        <option value="{{ $estado }}" {{ old('estado') == $estado ? 'selected' : '' }}>
                            @if($estado === 'Buen estado') ✅ Buen estado
                            @elseif($estado === 'Desgaste moderado') ⚠️ Desgaste moderado
                            @elseif($estado === 'Desgaste severo') ⚠️ Desgaste severo
                            @elseif($estado === 'Dañado - Requiere cambio') ❌ Dañado - Requiere cambio
                            @else 🔄 Cambiado
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad *
                </label>
                <textarea name="actividad"
                          rows="4"
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales sobre el componente..."
                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad') }}</textarea>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencia Fotográfica
                </label>
                <input type="file"
                       name="evidencia_fotos[]"
                       multiple
                       accept="image/*"
                       class="w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-500 mt-1">Puede seleccionar múltiples imágenes (Formatos: JPG, PNG. Máx: 5MB cada una)</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', request()->query()) }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition-all shadow-md">
                    Cancelar
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

<script>
const componenteSelect = document.getElementById('componente');
const moduloSelect = document.getElementById('modulo');
const nivelSelect = document.getElementById('nivel');
const ladoSelect = document.getElementById('lado');
const checklistContainer = document.getElementById('checklist-container');
const componentesChecklist = document.getElementById('componentes-checklist');
const componentesRevisadosInput = document.getElementById('componentes_revisados_input');
const revisionContextUrl = '{{ route("pasteurizadora.analisis-pasteurizadora.ajax.revision-context") }}';
const oldComponentesRaw = @json(old('componentes_revisados', []));

function normalizarComponentesSeleccionados(value) {
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

let selectedComponentes = normalizarComponentesSeleccionados(oldComponentesRaw);

function ladoLabel(lado) {
    return lado === 'VAPOR' ? 'Vapor' : 'Pasillo';
}

function nivelLabel(nivel) {
    return nivel === 'SUPERIOR' ? '⬆️ Nivel Superior' : '⬇️ Nivel Inferior';
}

function actualizarResumen() {
    const modulo = moduloSelect.value;
    const componenteNombre = componenteSelect.options[componenteSelect.selectedIndex]?.text?.split(' (')[0] || 'No especificado';
    document.getElementById('summary-modulo').textContent = modulo ? `Módulo ${modulo}` : '—';
    document.getElementById('summary-componente').textContent = componenteSelect.value ? componenteNombre : 'No especificado';
    document.getElementById('summary-nivel').innerHTML = nivelSelect.value ? nivelLabel(nivelSelect.value) : '<span class="text-gray-400">No especificado</span>';
}

function actualizarComponentesRevisados() {
    const checkboxes = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');
    selectedComponentes = Array.from(checkboxes).map(cb => parseInt(cb.dataset.componentValue, 10));
    componentesRevisadosInput.value = JSON.stringify(selectedComponentes);
}

function renderEstadoRevision(estadoRevision) {
    document.querySelectorAll('[data-nivel-card]').forEach(card => {
        const nivel = card.dataset.nivelCard;
        const data = estadoRevision?.[nivel] || { completado: false, lados_pendientes: ['VAPOR', 'PASILLO'] };
        const badge = card.querySelector('[data-status-badge]');
        const detail = card.querySelector('[data-status-detail]');

        card.className = `p-4 rounded-lg ${data.completado ? 'bg-green-100 border border-green-300' : 'bg-white border border-indigo-200'}`;
        badge.className = `inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold ${data.completado ? 'bg-green-600 text-white' : 'bg-amber-600 text-white'}`;
        badge.innerHTML = `<i class="fas ${data.completado ? 'fa-check-circle' : 'fa-clock'}"></i>${data.completado ? 'Completado' : 'Pendiente'}`;

        if (data.completado) {
            detail.innerHTML = '<div class="text-sm text-green-700"><i class="fas fa-check mr-1"></i>Ambos lados revisados correctamente</div>';
            return;
        }

        detail.innerHTML = `
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <span class="font-medium">Lados pendientes:</span>
                <div class="flex gap-2">
                    ${(data.lados_pendientes || []).map((lado) => `
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs ${lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">
                            <i class="fas ${lado === 'VAPOR' ? 'fa-wind' : 'fa-walking'}"></i>
                            ${ladoLabel(lado)}
                        </span>
                    `).join('')}
                </div>
            </div>
        `;
    });
}

function renderChecklist(totalPiezas, revisadas, reviewedCount, remainingPiezas, componenteNombre) {
    componentesChecklist.innerHTML = '';
    const revisadasNormalizadas = (revisadas || []).map((item) => parseInt(item, 10));

    if (!totalPiezas) {
        checklistContainer.classList.add('hidden');
        return;
    }

    checklistContainer.classList.remove('hidden');

    for (let i = 1; i <= totalPiezas; i++) {
        const yaRevisado = revisadasNormalizadas.includes(i);
        const seleccionado = selectedComponentes.includes(i);
        const label = document.createElement('label');
        label.className = `flex items-center gap-3 p-3 bg-white rounded-lg border ${yaRevisado ? 'border-gray-200 bg-gray-50 opacity-60' : 'border-gray-200 hover:border-blue-400 hover:shadow-md'} transition cursor-pointer`;
        label.innerHTML = `
            <input type="checkbox"
                   data-component-value="${i}"
                   class="w-5 h-5 text-blue-600 rounded cursor-pointer focus:ring-blue-500 componente-checkbox"
                   ${yaRevisado ? 'disabled checked' : seleccionado ? 'checked' : ''}>
            <span class="flex-1 ${yaRevisado ? 'text-gray-400 line-through' : 'text-gray-700 font-medium'}">
                <i class="fas fa-cube text-blue-500 mr-2"></i>
                ${componenteNombre} #${i}
                ${yaRevisado ? '<span class="ml-2 text-xs text-green-600">(Ya revisado)</span>' : ''}
            </span>
        `;
        componentesChecklist.appendChild(label);
    }

    componentesChecklist.querySelectorAll('.componente-checkbox:not(:disabled)').forEach((checkbox) => {
        checkbox.addEventListener('change', actualizarComponentesRevisados);
    });

    document.getElementById('remaining-info').classList.toggle('hidden', reviewedCount === 0);
    document.getElementById('already-reviewed-count').textContent = reviewedCount;
    document.getElementById('remaining-count').textContent = remainingPiezas;
    document.getElementById('total-count').textContent = totalPiezas;
    document.getElementById('already-reviewed-badges').innerHTML = revisadasNormalizadas
        .map((value) => `<span class="inline-flex items-center px-2 py-0.5 bg-blue-200 text-blue-800 rounded text-xs">#${value}</span>`)
        .join('');
}

function renderLadosPendientes(ladosPendientes, ladoActual) {
    const alertBox = document.getElementById('lados-pendientes-alert');

    if (!ladoActual || !ladosPendientes || ladosPendientes.length === 0) {
        alertBox.classList.add('hidden');
        alertBox.innerHTML = '';
        return;
    }

    alertBox.classList.remove('hidden');
    alertBox.innerHTML = `
        <i class="fas fa-info-circle mr-2"></i>
        <strong>Lados pendientes por revisar:</strong>
        ${ladosPendientes.map((lado) => `
            <span class="inline-flex items-center ml-2 px-2 py-1 rounded text-xs ${lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">
                <i class="fas ${lado === 'VAPOR' ? 'fa-wind' : 'fa-walking'} mr-1"></i>
                ${ladoLabel(lado)}
            </span>
        `).join('')}
    `;
}

async function cargarContextoRevision() {
    actualizarResumen();

    if (!moduloSelect.value || !componenteSelect.value) {
        checklistContainer.classList.add('hidden');
        renderEstadoRevision({});
        return;
    }

    const response = await fetch(revisionContextUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            linea_id: {{ $linea->id }},
            modulo: moduloSelect.value,
            componente: componenteSelect.value,
            nivel: nivelSelect.value || null,
            lado: ladoSelect.value || null
        })
    });

    const data = await response.json();
    if (!data.success) {
        return;
    }

    if (data.nivel && nivelSelect.value !== data.nivel) {
        nivelSelect.value = data.nivel;
    }

    if (data.lado && ladoSelect.value !== data.lado) {
        ladoSelect.value = data.lado;
    }

    actualizarResumen();
    renderEstadoRevision(data.estado_revision || {});
    renderChecklist(
        data.total_piezas || 0,
        data.already_reviewed_components || [],
        data.already_reviewed || 0,
        data.remaining_piezas || 0,
        data.nombre_componente || componenteSelect.options[componenteSelect.selectedIndex]?.text?.split(' (')[0] || data.componente
    );
    renderLadosPendientes(data.lados_pendientes || [], data.lado);

    const siguienteAlert = document.getElementById('siguiente-revision-alert');
    if (data.siguiente_revision?.nivel && data.siguiente_revision?.lado) {
        siguienteAlert.classList.remove('hidden');
        siguienteAlert.innerHTML = `
            <i class="fas fa-magic mr-2"></i>
            Se cargó automáticamente la siguiente revisión pendiente:
            <strong>${nivelLabel(data.siguiente_revision.nivel)}</strong>,
            <strong>Lado ${ladoLabel(data.siguiente_revision.lado)}</strong>.
        `;
    } else {
        siguienteAlert.classList.add('hidden');
        siguienteAlert.innerHTML = '';
    }
    actualizarComponentesRevisados();
}

document.addEventListener('DOMContentLoaded', function() {
    [moduloSelect, componenteSelect, nivelSelect, ladoSelect].forEach((field) => {
        field.addEventListener('change', cargarContextoRevision);
    });

    const inputFotos = document.querySelector('input[name="evidencia_fotos[]"]');
    const previewFotos = document.getElementById('preview_fotos');

    if (inputFotos && previewFotos) {
        inputFotos.addEventListener('change', function() {
            previewFotos.innerHTML = '';
            Array.from(this.files).forEach((file) => {
                if (!file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-20 h-20 object-cover rounded-lg border border-gray-200';
                    previewFotos.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    document.getElementById('analisisForm').addEventListener('submit', function(e) {
        const seleccionables = document.querySelectorAll('input.componente-checkbox:not(:disabled)');
        const seleccionados = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');

        if (seleccionables.length > 0 && seleccionados.length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un componente revisado.');
            return;
        }

        actualizarComponentesRevisados();
    });

    cargarContextoRevision();
});
</script>
@endsection
