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

            <!-- SECCIÓN DEL CHECKLIST DE PIEZAS -->
            <div id="checklist-container" class="hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="mb-4">
                        <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
                            <label class="block text-sm font-bold text-gray-800">
                                <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>
                                Lista de Piezas del Componente
                            </label>
                            <div class="flex gap-2">
                                <span id="piezas-count-badge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-600 text-white">
                                    <i class="fas fa-cube mr-1"></i>
                                    <span id="total-piezas-count">0</span> piezas
                                </span>
                                <button type="button" id="select-all-componentes" class="text-xs bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-full transition">
                                    <i class="fas fa-check-double mr-1"></i> Seleccionar todos
                                </button>
                                <button type="button" id="clear-componentes" class="text-xs bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-full transition">
                                    <i class="fas fa-times mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                        
                        <div id="remaining-info" class="bg-blue-100 border border-blue-400 rounded-lg p-3 mb-3 hidden">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Ya revisadas en este lado y nivel:</strong>
                                <span id="already-reviewed-count">0</span> de
                                <span id="total-count">0</span> piezas
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1" id="already-reviewed-badges"></div>
                            <p class="text-sm text-blue-800 mt-2">
                                <strong>Pendientes en este lado y nivel:</strong>
                                <span id="remaining-count">0</span> piezas
                            </p>
                        </div>
                    </div>

                    <div id="componentes-checklist" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="col-span-full text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                            <i class="fas fa-cogs text-gray-400 text-5xl mb-3"></i>
                            <p class="text-gray-500 font-medium">Seleccione un módulo, componente, nivel y lado</p>
                            <p class="text-gray-400 text-sm mt-1">Para ver las piezas disponibles para revisión</p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="componentes_revisados" id="componentes_revisados_input" value="{{ json_encode(old('componentes_revisados', [])) }}">

                    <div id="lados-pendientes-alert" class="hidden mt-4 p-3 bg-yellow-100 border border-yellow-400 rounded-lg text-sm text-yellow-800">
                    </div>
                </div>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando script');
    
    const moduloSelect = document.getElementById('modulo');
    const componenteSelect = document.getElementById('componente');
    const nivelSelect = document.getElementById('nivel');
    const ladoSelect = document.getElementById('lado');
    const checklistContainer = document.getElementById('checklist-container');
    const componentesChecklist = document.getElementById('componentes-checklist');
    const componentesRevisadosInput = document.getElementById('componentes_revisados_input');
    
    // Usar la ruta correcta de Laravel
    const revisionContextUrl = '{{ route("pasteurizadora.analisis-pasteurizadora.ajax.revision-context") }}';
    
    console.log('URL del contexto:', revisionContextUrl);
    
    let selectedComponentes = [];
    
    // Cargar valores iniciales desde old() si existen
    try {
        const oldValue = '{{ json_encode(old("componentes_revisados", [])) }}';
        if (oldValue && oldValue !== '[]') {
            const parsedValue = oldValue.replace(/&quot;/g, '"');
            selectedComponentes = JSON.parse(parsedValue);
            console.log('Valores antiguos cargados:', selectedComponentes);
        }
    } catch(e) {
        console.log('Error parsing old valores:', e);
        selectedComponentes = [];
    }
    
    function actualizarResumen() {
        const modulo = moduloSelect.value;
        const componenteNombre = componenteSelect.options[componenteSelect.selectedIndex]?.text?.split(' (')[0] || 'No especificado';
        document.getElementById('summary-modulo').textContent = modulo ? `Módulo ${modulo}` : '—';
        document.getElementById('summary-componente').textContent = componenteSelect.value ? componenteNombre : 'No especificado';
        
        const nivelValor = nivelSelect.value;
        if (nivelValor === 'SUPERIOR') {
            document.getElementById('summary-nivel').innerHTML = '⬆️ Nivel Superior';
        } else if (nivelValor === 'INFERIOR') {
            document.getElementById('summary-nivel').innerHTML = '⬇️ Nivel Inferior';
        } else {
            document.getElementById('summary-nivel').innerHTML = '<span class="text-gray-400">No especificado</span>';
        }
    }
    
    function actualizarComponentesRevisados() {
        const checkboxes = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');
        selectedComponentes = Array.from(checkboxes).map(cb => parseInt(cb.dataset.componentValue, 10));
        componentesRevisadosInput.value = JSON.stringify(selectedComponentes);
        console.log('Componentes seleccionados actualizados:', selectedComponentes);
    }
    
    function renderChecklist(totalPiezas, yaRevisadas, yaRevisadasList, componenteNombre) {
        console.log('Renderizando checklist:', {totalPiezas, yaRevisadas, yaRevisadasList, componenteNombre});
        
        componentesChecklist.innerHTML = '';
        const revisadasNormalizadas = (yaRevisadasList || []).map((item) => parseInt(item, 10));
        
        const totalPiezasCount = document.getElementById('total-piezas-count');
        if (totalPiezasCount) {
            totalPiezasCount.textContent = totalPiezas;
        }
        
        if (!totalPiezas || totalPiezas === 0) {
            componentesChecklist.innerHTML = `
                <div class="col-span-full text-center py-8 bg-yellow-50 rounded-lg border border-yellow-200">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-2"></i>
                    <p class="text-yellow-700 font-medium">No se encontraron piezas para este componente</p>
                </div>
            `;
            return;
        }
        
        // Generar todas las piezas del 1 al totalPiezas
        for (let i = 1; i <= totalPiezas; i++) {
            const yaRevisado = revisadasNormalizadas.includes(i);
            const seleccionado = selectedComponentes.includes(i);
            
            const div = document.createElement('div');
            div.className = `rounded-xl border ${yaRevisado ? 'border-gray-200 bg-gray-50 opacity-60' : seleccionado ? 'border-blue-500 bg-blue-50 shadow-md ring-2 ring-blue-200' : 'border-gray-200 bg-white hover:border-blue-400 hover:shadow-md'} transition overflow-hidden`;
            div.innerHTML = `
                <label class="block cursor-pointer p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full ${yaRevisado ? 'bg-gray-300 text-gray-500' : 'bg-blue-100 text-blue-700'} text-xs font-bold">
                                    ${i}
                                </span>
                                <span class="${yaRevisado ? 'text-gray-400 line-through' : 'text-gray-800'} font-medium text-sm">
                                    ${componenteNombre}
                                </span>
                            </div>
                            <div class="text-xs ${yaRevisado ? 'text-gray-400' : 'text-gray-500'} mt-1">
                                Pieza #${i} de ${totalPiezas}
                            </div>
                        </div>
                        <div>
                            ${yaRevisado ? '<span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-gray-200 text-gray-600"><i class="fas fa-lock mr-1"></i>Bloqueado</span>' : ''}
                            <input type="checkbox"
                                   data-component-value="${i}"
                                   class="w-4 h-4 text-blue-600 rounded componente-checkbox ml-2"
                                   ${yaRevisado ? 'disabled checked' : seleccionado ? 'checked' : ''}>
                        </div>
                    </div>
                </label>
            `;
            componentesChecklist.appendChild(div);
        }
        
        // Agregar event listeners a los checkboxes habilitados
        document.querySelectorAll('.componente-checkbox:not(:disabled)').forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                const parent = this.closest('.rounded-xl');
                if (this.checked) {
                    parent.className = 'rounded-xl border border-blue-500 bg-blue-50 shadow-md ring-2 ring-blue-200 transition overflow-hidden';
                } else {
                    parent.className = 'rounded-xl border border-gray-200 bg-white hover:border-blue-400 hover:shadow-md transition overflow-hidden';
                }
                actualizarComponentesRevisados();
            });
        });
        
        // Mostrar información de piezas ya revisadas
        const remainingInfo = document.getElementById('remaining-info');
        const pendientes = totalPiezas - yaRevisadas;
        
        if (yaRevisadas > 0) {
            remainingInfo.classList.remove('hidden');
            document.getElementById('already-reviewed-count').textContent = yaRevisadas;
            document.getElementById('remaining-count').textContent = pendientes;
            document.getElementById('total-count').textContent = totalPiezas;
            document.getElementById('already-reviewed-badges').innerHTML = revisadasNormalizadas
                .map((value) => `<span class="inline-flex items-center px-2 py-0.5 bg-blue-200 text-blue-800 rounded text-xs">#${value}</span>`)
                .join('');
        } else {
            remainingInfo.classList.add('hidden');
        }
        
        actualizarComponentesRevisados();
    }
    
    async function cargarContextoRevision() {
        const modulo = moduloSelect.value;
        const componente = componenteSelect.value;
        const nivel = nivelSelect.value;
        const lado = ladoSelect.value;
        
        console.log('Cargando contexto:', {modulo, componente, nivel, lado});
        
        // Verificar que todos los campos estén seleccionados
        if (!modulo || !componente || !nivel || !lado) {
            console.log('Faltan campos por seleccionar');
            checklistContainer.classList.add('hidden');
            componentesChecklist.innerHTML = `
                <div class="col-span-full text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                    <i class="fas fa-cogs text-gray-400 text-5xl mb-3"></i>
                    <p class="text-gray-500 font-medium">Complete todos los campos</p>
                    <p class="text-gray-400 text-sm mt-1">Seleccione módulo, componente, nivel y lado</p>
                </div>
            `;
            return;
        }
        
        try {
            // Mostrar loading
            componentesChecklist.innerHTML = `
                <div class="col-span-full text-center py-12 bg-white rounded-lg">
                    <i class="fas fa-spinner fa-spin text-blue-500 text-4xl mb-3"></i>
                    <p class="text-gray-500">Cargando piezas del componente...</p>
                </div>
            `;
            
            const response = await fetch(revisionContextUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    linea_id: {{ $linea->id }},
                    modulo: parseInt(modulo),
                    componente: componente,
                    nivel: nivel,
                    lado: lado
                })
            });
            
            const data = await response.json();
            console.log('Respuesta del servidor:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar las piezas');
            }
            
            // Actualizar el resumen con los datos recibidos
            actualizarResumen();
            
            // Mostrar el contenedor del checklist
            checklistContainer.classList.remove('hidden');
            
            // Renderizar el checklist con los datos recibidos
            renderChecklist(
                data.total_piezas || 0,
                data.already_reviewed || 0,
                data.already_reviewed_components || [],
                data.nombre_componente || componenteSelect.options[componenteSelect.selectedIndex]?.text?.split(' (')[0] || 'Componente'
            );
            
            // Actualizar el estado de revisión si es necesario
            if (data.estado_revision) {
                // Actualizar las tarjetas de estado de revisión
                document.querySelectorAll('[data-nivel-card]').forEach(card => {
                    const nivelCard = card.dataset.nivelCard;
                    const estadoData = data.estado_revision[nivelCard];
                    if (estadoData) {
                        const badge = card.querySelector('[data-status-badge]');
                        const detail = card.querySelector('[data-status-detail]');
                        
                        if (estadoData.completado) {
                            badge.innerHTML = '<i class="fas fa-check-circle"></i>Completado';
                            card.className = 'p-4 rounded-lg bg-green-100 border border-green-300';
                            detail.innerHTML = '<div class="text-sm text-green-700"><i class="fas fa-check mr-1"></i>Ambos lados revisados correctamente</div>';
                        } else {
                            badge.innerHTML = '<i class="fas fa-clock"></i>Pendiente';
                            card.className = 'p-4 rounded-lg bg-white border border-indigo-200';
                            const ladosPendientes = estadoData.lados_pendientes || [];
                            detail.innerHTML = `
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <span class="font-medium">Lados pendientes:</span>
                                    <div class="flex gap-2">
                                        ${ladosPendientes.map(ladoP => `
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs ${ladoP === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">
                                                <i class="fas ${ladoP === 'VAPOR' ? 'fa-wind' : 'fa-walking'}"></i>
                                                ${ladoP === 'VAPOR' ? 'Vapor' : 'Pasillo'}
                                            </span>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
            }
            
        } catch (error) {
            console.error('Error:', error);
            componentesChecklist.innerHTML = `
                <div class="col-span-full text-center py-12 bg-red-50 rounded-lg border border-red-200">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i>
                    <p class="text-red-600 font-medium">Error al cargar las piezas</p>
                    <p class="text-red-500 text-sm mt-1">${error.message}</p>
                    <button type="button" onclick="location.reload()" class="mt-3 bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Reintentar</button>
                </div>
            `;
        }
    }
    
    // Agregar event listeners a los selects
    moduloSelect.addEventListener('change', cargarContextoRevision);
    componenteSelect.addEventListener('change', cargarContextoRevision);
    nivelSelect.addEventListener('change', cargarContextoRevision);
    ladoSelect.addEventListener('change', cargarContextoRevision);
    
    // Botón seleccionar todos
    const selectAllBtn = document.getElementById('select-all-componentes');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.componente-checkbox:not(:disabled)').forEach((checkbox) => {
                checkbox.checked = true;
                const parent = checkbox.closest('.rounded-xl');
                if (parent) {
                    parent.className = 'rounded-xl border border-blue-500 bg-blue-50 shadow-md ring-2 ring-blue-200 transition overflow-hidden';
                }
            });
            actualizarComponentesRevisados();
        });
    }
    
    // Botón limpiar selección
    const clearBtn = document.getElementById('clear-componentes');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            document.querySelectorAll('.componente-checkbox:not(:disabled)').forEach((checkbox) => {
                checkbox.checked = false;
                const parent = checkbox.closest('.rounded-xl');
                if (parent) {
                    parent.className = 'rounded-xl border border-gray-200 bg-white hover:border-blue-400 hover:shadow-md transition overflow-hidden';
                }
            });
            actualizarComponentesRevisados();
        });
    }
    
    // Preview de imágenes
    const inputFotos = document.querySelector('input[name="evidencia_fotos[]"]');
    const previewFotos = document.getElementById('preview_fotos');
    
    if (inputFotos && previewFotos) {
        inputFotos.addEventListener('change', function() {
            previewFotos.innerHTML = '';
            Array.from(this.files).forEach((file) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative group';
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-20 h-20 object-cover rounded-lg border border-gray-200';
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition';
                        removeBtn.innerHTML = '×';
                        removeBtn.onclick = function() {
                            div.remove();
                            const dt = new DataTransfer();
                            const files = Array.from(inputFotos.files);
                            const fileIndex = files.indexOf(file);
                            files.splice(fileIndex, 1);
                            files.forEach(f => dt.items.add(f));
                            inputFotos.files = dt.files;
                        };
                        
                        div.appendChild(img);
                        div.appendChild(removeBtn);
                        previewFotos.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    }
    
    // Validar formulario antes de enviar
    const form = document.getElementById('analisisForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');
            const totalDisponibles = document.querySelectorAll('input.componente-checkbox:not(:disabled)').length;
            
            if (totalDisponibles > 0 && checkboxes.length === 0) {
                e.preventDefault();
                alert('Debe seleccionar al menos una pieza revisada.');
            }
            
            actualizarComponentesRevisados();
        });
    }
    
    // Cargar contexto inicial si hay valores precargados (modo edición)
    if (moduloSelect.value && componenteSelect.value && nivelSelect.value && ladoSelect.value) {
        console.log('Valores precargados encontrados, cargando contexto...');
        setTimeout(cargarContextoRevision, 500);
    }
});
</script>
@endsection