@extends('layouts.app')

@section('title', 'Agregar Análisis - Pasteurizadora')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
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
    <div class="pasteur-form-card">
        {{-- Encabezado --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ $analisisRoute('index', request()->query()) }}"
                   class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-800">
                    Agregar Análisis
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
                                Línea
                            </p>
                            <p class="text-gray-800 font-medium">{{ $linea->nombre ?? 'Sin línea' }}</p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-cubes mr-1"></i>
                                Módulo
                            </p>
                            <p class="text-gray-800 font-medium">
                                @if($modulo)
                                    Módulo {{ $modulo }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-cog mr-1"></i>
                                Componente
                            </p>
                            <p class="text-gray-800 font-medium">
                                {{ $nombreComponente ?? $componente }}
                            </p>
                        </div>

                        <div class="text-center md:text-left">
                            <p class="text-gray-600 font-semibold text-sm mb-1">
                                <i class="fas fa-layer-group mr-1"></i>
                                Nivel
                            </p>
                            <p class="text-gray-800 font-medium">
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

        {{-- Formulario --}}
        <form action="{{ $analisisRoute('store-quick') }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6"
              id="analisisForm">
            @csrf

            {{-- Campos ocultos --}}
            <input type="hidden" name="linea_id" value="{{ $linea->id ?? '' }}">
            <input type="hidden" name="modulo" value="{{ $modulo ?? '' }}">
            <input type="hidden" name="componente" value="{{ $componente ?? '' }}">

            {{-- Selector de Lado --}}
            <div>
                <label for="lado" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                    Lado del Análisis *
                </label>
                <select id="lado" name="lado"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                               @error('lado') border-red-500 @enderror"
                        required>
                    <option value="">Seleccionar lado...</option>
                    <option value="VAPOR" {{ old('lado', $lado) == 'VAPOR' ? 'selected' : '' }}>💨 Lado Vapor</option>
                    <option value="PASILLO" {{ old('lado', $lado) == 'PASILLO' ? 'selected' : '' }}>🚶 Lado Pasillo</option>
                </select>
                @error('lado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nivel (solo lectura si ya viene de la tabla) --}}
            <div>
                <label for="nivel" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                    Nivel del Módulo
                </label>
                <select id="nivel" name="nivel"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Seleccionar nivel...</option>
                    <option value="SUPERIOR" {{ old('nivel', $nivel) == 'SUPERIOR' ? 'selected' : '' }}>⬆️ Nivel Superior</option>
                    <option value="INFERIOR" {{ old('nivel', $nivel) == 'INFERIOR' ? 'selected' : '' }}>⬇️ Nivel Inferior</option>
                </select>
            </div>

            {{-- Estado de Niveles y Lados --}}
            @php
                $estadoRevision = $estadoRevision ?? \App\Models\AnalisisPasteurizadora::getEstadoRevision(
                    $linea->id,
                    $modulo,
                    $componenteKey,
                    null,
                    $analisisArea ?? null,
                    \App\Models\AnalisisPasteurizadora::TIPO_REGISTRO_QUICK
                );
            @endphp
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border border-indigo-200">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-indigo-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-tasks text-indigo-600"></i>
                        Estado de Revisión por Nivel
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach(['SUPERIOR' => '⬆️ Nivel Superior', 'INFERIOR' => '⬇️ Nivel Inferior'] as $nivelKey => $nivelLabel)
                        @php
                            $completado = $estadoRevision[$nivelKey]['completado'];
                            $ladosPendientes = $estadoRevision[$nivelKey]['lados_pendientes'];
                        @endphp
                        <div class="p-4 rounded-lg {{ $completado ? 'bg-green-100 border border-green-300' : 'bg-white border border-indigo-200' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-gray-800">{{ $nivelLabel }}</span>
                                @if($completado)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">
                                        <i class="fas fa-check-circle"></i>
                                        Completado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-600 text-white rounded-full text-xs font-bold">
                                        <i class="fas fa-clock"></i>
                                        Pendiente
                                    </span>
                                @endif
                            </div>

                            @if(!$completado)
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <span class="font-medium">Lados pendientes:</span>
                                    <div class="flex gap-2">
                                        @foreach($ladosPendientes as $ladoPendiente)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $ladoPendiente === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                                <i class="fas {{ $ladoPendiente === 'VAPOR' ? 'fa-wind' : 'fa-walking' }}"></i>
                                                {{ $ladoPendiente === 'VAPOR' ? 'Vapor' : 'Pasillo' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="text-sm text-green-700">
                                    <i class="fas fa-check mr-1"></i>
                                    Ambos lados revisados correctamente
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Sección de checklist de componentes --}}
            <div id="checklist-container" class="{{ ($componente ?? null) === \App\Models\AnalisisPasteurizadora::COMPONENTE_BRAZO_TORSION ? 'hidden' : '' }}">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-800 mb-2">
                            <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                            Seleccione los componentes revisados
                        </label>
                        <div id="quick-remaining-info"
                             class="bg-blue-100 border border-blue-400 rounded-lg p-3 mb-3 {{ $cantidadComponentesRevisados > 0 ? '' : 'hidden' }}">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Ya revisados en este lado y nivel:</strong> {{ $cantidadComponentesRevisados }} de {{ $totalComponentes }} componentes
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1" id="quick-reviewed-badges">
                                @foreach($componentesYaRevisados as $compNum)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-200 text-blue-800 rounded text-xs">
                                        #{{ $compNum }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-sm text-blue-800 mt-2">
                                <strong>Pendientes en este lado y nivel:</strong>
                                <span id="quick-pending-count">{{ $componentesPendientes }}</span> componentes
                            </p>
                        </div>
                    </div>

                    <div id="componentes-checklist" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @php
                            $todosLosNumeros = range(1, $totalComponentes);
                        @endphp

                        @if($totalComponentes > 0 && ($componente ?? null) !== \App\Models\AnalisisPasteurizadora::COMPONENTE_BRAZO_TORSION)
                            @foreach($todosLosNumeros as $numero)
                                @php
                                    $yaRevisado = in_array($numero, $componentesYaRevisados);
                                @endphp
                                <label class="flex items-center gap-3 p-3 bg-white rounded-lg border {{ $yaRevisado ? 'border-gray-200 bg-gray-50 opacity-60' : 'border-gray-200 hover:border-blue-400 hover:shadow-md' }} transition cursor-pointer">
                                    <input type="checkbox"
                                           data-component-value="{{ $numero }}"
                                           class="w-5 h-5 text-blue-600 rounded cursor-pointer focus:ring-blue-500 componente-checkbox"
                                           onchange="actualizarComponentesRevisados()"
                                           {{ $yaRevisado ? 'disabled checked' : '' }}>
                                    <span class="flex-1 {{ $yaRevisado ? 'text-gray-400 line-through' : 'text-gray-700 font-medium' }}">
                                        <i class="fas fa-cube text-blue-500 mr-2"></i>
                                        @if(($componente ?? null) === \App\Models\AnalisisPasteurizadora::COMPONENTE_BRAZO_TORSION)
                                            {{ $nombreComponente ?? $componente }} modulo {{ $numero }}
                                        @else
                                            {{ $nombreComponente ?? $componente }} #{{ $numero }}
                                        @endif
                                        @if($yaRevisado)
                                            <span class="ml-2 text-xs text-green-600">(Ya revisado)</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        @else
                            <div class="col-span-full text-center py-8 bg-yellow-50 rounded-lg border border-yellow-200">
                                <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-2"></i>
                                <p class="text-yellow-700 font-medium">No se encontraron componentes disponibles para este contexto</p>
                            </div>
                        @endif
                    </div>

                    <input type="hidden" name="componentes_revisados" id="componentes_revisados_input" value="{{ json_encode(($componente ?? null) === \App\Models\AnalisisPasteurizadora::COMPONENTE_BRAZO_TORSION ? [1] : old('componentes_revisados', [])) }}">
                    @error('componentes_revisados')
                        <p class="text-red-500 text-sm mt-3">{{ $message }}</p>
                    @enderror

                </div>
            </div>

            {{-- Rango de fechas --}}
            <div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                            Fecha de inicio *
                        </label>
                        <input type="date"
                               id="fecha_inicio"
                               name="fecha_inicio"
                               value="{{ old('fecha_inicio', old('fecha_analisis', $fechaSugerida ?? now()->format('Y-m-d'))) }}"
                               required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                               @error('fecha_inicio') border-red-500 @enderror">
                        @error('fecha_inicio')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fecha_fin" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="far fa-calendar-check text-blue-600 mr-1"></i>
                            Fecha final *
                        </label>
                        <input type="date"
                               id="fecha_fin"
                               name="fecha_fin"
                               value="{{ old('fecha_fin', old('fecha_analisis', $fechaSugerida ?? now()->format('Y-m-d'))) }}"
                               required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                               @error('fecha_fin') border-red-500 @enderror">
                        @error('fecha_fin')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @error('fecha_analisis')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Número de Orden --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                    Número de Orden
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
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                       @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Estado --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                    Estado del Componente *
                </label>
                <select name="estado" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Seleccionar estado...</option>
                    @foreach(\App\Models\AnalisisPasteurizadora::getEstadoOpciones() as $estado => $label)
                        <option value="{{ $estado }}" {{ old('estado') === $estado ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actividad --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad *
                </label>
                <textarea name="actividad"
                          rows="4"
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales sobre el componente..."
                          class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                          @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad') }}</textarea>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Evidencia Fotografica --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencia fotografica
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

                @error('evidencia_fotos')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
                @error('evidencia_fotos.*')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="create-actions pt-6 border-t border-gray-200">
                <a href="{{ $analisisRoute('index', request()->query()) }}"
                   class="create-action create-action--secondary flex-1">
                    Cancelar
                </a>
                <button type="submit"
                        class="create-action flex-1">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Análisis
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const quickNivelSelect = document.getElementById('nivel');
const quickLadoSelect = document.getElementById('lado');
const quickChecklistContainer = document.getElementById('checklist-container');
const quickChecklist = document.getElementById('componentes-checklist');
const quickComponentesInput = document.getElementById('componentes_revisados_input');
const quickRevisionContextUrl = '{{ $analisisRoute("ajax.revision-context") }}';
const quickComponenteNombre = @json($nombreComponente ?? $componente);
const quickOldSelection = @json(old('componentes_revisados', []));
const quickEsBrazoTorsion = @json(($componente ?? null) === \App\Models\AnalisisPasteurizadora::COMPONENTE_BRAZO_TORSION);

document.addEventListener('DOMContentLoaded', () => {
    const numeroOrdenInput = document.querySelector('input[name="numero_orden"]');
    const numeroOrdenContainer = numeroOrdenInput?.closest('div');

    if (numeroOrdenInput) {
        numeroOrdenInput.disabled = true;
        numeroOrdenInput.value = '';
    }

    numeroOrdenContainer?.remove();
});

function normalizarSeleccionQuick(value) {
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

let quickSelectedComponentes = normalizarSeleccionQuick(quickOldSelection);

function actualizarComponentesRevisados() {
    if (quickEsBrazoTorsion) {
        quickSelectedComponentes = [1];
        quickComponentesInput.value = JSON.stringify(quickSelectedComponentes);
        return;
    }

    const checkboxes = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');
    quickSelectedComponentes = Array.from(checkboxes).map((checkbox) => parseInt(checkbox.dataset.componentValue, 10));
    quickComponentesInput.value = JSON.stringify(quickSelectedComponentes);
}

function renderChecklistQuick(totalComponentes, componentesYaRevisados, cantidadComponentesRevisados, componentesPendientes) {
    quickChecklist.innerHTML = '';
    const componentesBloqueados = (componentesYaRevisados || []).map((item) => parseInt(item, 10));

    if (!totalComponentes) {
        quickChecklistContainer.classList.add('hidden');
        return;
    }

    if (quickEsBrazoTorsion) {
        quickSelectedComponentes = [1];
        quickComponentesInput.value = JSON.stringify(quickSelectedComponentes);
        quickChecklistContainer.classList.add('hidden');
        return;
    }

    quickChecklistContainer.classList.remove('hidden');

    for (let indice = 1; indice <= totalComponentes; indice++) {
        const yaRevisado = componentesBloqueados.includes(indice);
        const seleccionado = quickSelectedComponentes.includes(indice);
        const label = document.createElement('label');
        label.className = `flex items-center gap-3 p-3 bg-white rounded-lg border ${yaRevisado ? 'border-gray-200 bg-gray-50 opacity-60' : 'border-gray-200 hover:border-blue-400 hover:shadow-md'} transition cursor-pointer`;
        label.innerHTML = `
            <input type="checkbox"
                   data-component-value="${indice}"
                   class="w-5 h-5 text-blue-600 rounded cursor-pointer focus:ring-blue-500 componente-checkbox"
                   ${yaRevisado ? 'disabled checked' : seleccionado ? 'checked' : ''}>
            <span class="flex-1 ${yaRevisado ? 'text-gray-400 line-through' : 'text-gray-700 font-medium'}">
                <i class="fas fa-cube text-blue-500 mr-2"></i>
                ${quickEsBrazoTorsion ? `${quickComponenteNombre} modulo ${indice}` : `${quickComponenteNombre} #${indice}`}
                ${yaRevisado ? '<span class="ml-2 text-xs text-green-600">(Ya revisado)</span>' : ''}
            </span>
        `;
        quickChecklist.appendChild(label);
    }

    quickChecklist.querySelectorAll('.componente-checkbox:not(:disabled)').forEach((checkbox) => {
        checkbox.addEventListener('change', actualizarComponentesRevisados);
    });

    const infoBox = document.getElementById('quick-remaining-info');
    const badges = document.getElementById('quick-reviewed-badges');
    const pendingCount = document.getElementById('quick-pending-count');

    infoBox.classList.toggle('hidden', cantidadComponentesRevisados === 0);
    badges.innerHTML = componentesBloqueados
        .map((numero) => `<span class="inline-flex items-center px-2 py-0.5 bg-blue-200 text-blue-800 rounded text-xs">#${numero}</span>`)
        .join('');
    pendingCount.textContent = componentesPendientes;
}

function renderLadosPendientesQuick(ladosPendientes, ladoActual) {
    const alertBox = document.getElementById('lados-pendientes-alert');

    if (!alertBox) {
        return;
    }

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
                ${lado === 'VAPOR' ? 'Vapor' : 'Pasillo'}
            </span>
        `).join('')}
    `;
}

async function cargarContextoRevisionQuick() {
    if (!quickNivelSelect.value || !quickLadoSelect.value) {
        quickChecklistContainer.classList.add('hidden');
        return;
    }

    const response = await fetch(quickRevisionContextUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            linea_id: {{ $linea->id }},
            modulo: {{ (int) ($modulo ?? 0) }},
            componente: @json($componente ?? ''),
            nivel: quickNivelSelect.value,
            lado: quickLadoSelect.value
        })
    });

    const data = await response.json();
    if (!data.success) {
        return;
    }

    renderChecklistQuick(
        data.total_componentes || 0,
        data.componentes_ya_revisados || [],
        data.cantidad_componentes_revisados || 0,
        data.componentes_pendientes || 0
    );
    renderLadosPendientesQuick(data.lados_pendientes || [], data.lado);
    actualizarComponentesRevisados();
}

document.addEventListener('DOMContentLoaded', function() {
    quickNivelSelect?.addEventListener('change', cargarContextoRevisionQuick);
    quickLadoSelect?.addEventListener('change', cargarContextoRevisionQuick);

    const inputFotos = document.getElementById('evidencia_fotos');
    const botonGaleria = document.getElementById('btn_evidencia_fotos_galeria');
    const botonCamara = document.getElementById('btn_evidencia_fotos_camara');
    const galeriaFotosInput = document.getElementById('evidencia_fotos_galeria');
    const camaraFotosInput = document.getElementById('evidencia_fotos_camara');
    const previewFotos = document.getElementById('preview_fotos');
    const fotosResumen = document.getElementById('fotos_resumen');
    const maxFotoSize = 5 * 1024 * 1024;
    const soportaDataTransfer = typeof DataTransfer !== 'undefined';
    const extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

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
                imgContainer.className = 'relative group overflow-hidden rounded-lg border border-gray-200 bg-gray-100 shadow-sm';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = file.name;
                img.className = 'aspect-square w-full object-cover';
                imgContainer.appendChild(img);

                if (permitirEliminar) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute right-2 top-2 flex h-10 w-10 items-center justify-center rounded-full bg-red-600 text-base font-bold text-white shadow transition hover:bg-red-700 sm:opacity-0 sm:group-hover:opacity-100';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.setAttribute('aria-label', `Quitar ${file.name}`);
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
            if (!esImagenValida(file)) {
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

    botonGaleria?.addEventListener('click', function() {
        galeriaFotosInput.click();
    });

    botonCamara?.addEventListener('click', function() {
        camaraFotosInput.click();
    });

    inputFotos?.addEventListener('change', function() {
        renderPreview(getFotosPrincipales(), true);
    });

    if (inputFotos && previewFotos && soportaDataTransfer) {
        galeriaFotosInput.addEventListener('change', function() {
            agregarFotos(this.files);
            this.value = '';
        });

        camaraFotosInput.addEventListener('change', function() {
            agregarFotos(this.files);
            this.value = '';
        });

        renderPreview(getFotosPrincipales(), true);
    } else if (inputFotos && previewFotos) {
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

    document.getElementById('analisisForm').addEventListener('submit', function(e) {
        if (quickEsBrazoTorsion) {
            quickComponentesInput.value = JSON.stringify([1]);
            return;
        }

        const seleccionables = document.querySelectorAll('input.componente-checkbox:not(:disabled)');
        const seleccionados = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');

        if (seleccionables.length > 0 && seleccionados.length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un componente revisado.');
            return;
        }

        actualizarComponentesRevisados();
    });

    actualizarComponentesRevisados();
    cargarContextoRevisionQuick();
});
</script>
@endsection
