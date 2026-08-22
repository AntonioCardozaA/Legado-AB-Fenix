@php
    $analisisRoute = $analisisRoute ?? fn ($name, $params = []) => route('pasteurizadora.central-hidraulica.' . $name, $params);
    $isEdit = isset($analisis) && $analisis?->exists;
    $mostrarEvidencias = $mostrarEvidencias ?? $isEdit;
    $mostrarTextoPiezasVerificadas = $mostrarTextoPiezasVerificadas ?? true;
    $mostrarResumenEvidencias = $mostrarResumenEvidencias ?? true;
    $selectedConfig = old('configuracion_id', $analisis->configuracion_id ?? request('configuracion_id'));
    $selectedLado = old('lado', $analisis->lado ?? request('lado'));
    $selectedPiso = old('piso');

    if (!$selectedPiso && $selectedConfig) {
        $selectedPiso = optional($configuraciones->firstWhere('id', (int) $selectedConfig))->piso;
    }

    $selectedPiso = $selectedPiso ?: \App\Models\CentralHidraulicaConfiguracion::PISO_SUPERIOR;
    $selectedPiezas = old('componentes_revisados', $analisis->componentes_revisados_lista ?? []);
    $selectedEstado = old('estado', $analisis->estado ?? \App\Models\AnalisisCentralHidraulica::ESTADO_BUENO);

    if (is_string($selectedPiezas)) {
        $decoded = json_decode($selectedPiezas, true);
        $selectedPiezas = is_array($decoded) ? $decoded : [];
    }

    $selectedPiezas = collect($selectedPiezas)->map(fn ($item) => (int) $item)->filter()->values()->all();
    $configPayload = $configuraciones->map(fn ($config) => [
        'id' => $config->id,
        'piso' => $config->piso,
        'piso_label' => $config->piso_label,
        'nombre' => $config->componente_nombre,
        'codigo' => $config->componente?->codigo,
        'cantidad' => $config->cantidad,
        'unidad' => $config->unidad ?: 'pza',
        'cantidad_label' => $config->cantidad_label,
        'lado_requerido' => $config->lado_requerido,
        'detalle_excel' => $config->detalle_excel,
        'contabilizable' => $config->es_contabilizable,
        'es_revision_aceite' => $config->es_revision_aceite,
        'tipo_elemento_label' => $config->tipo_elemento_label,
    ])->values();
@endphp

<style>
    .central-check-option {
        align-items: center;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        cursor: pointer;
        display: flex;
        gap: 0.75rem;
        min-width: 0;
        padding: 0.75rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease;
    }

    #centralForm {
        width: 100%;
        max-width: 100%;
    }

    #centralForm * {
        min-width: 0;
    }

    #centralForm label,
    #centralForm p,
    #centralForm span,
    #centralForm input,
    #centralForm select,
    #centralForm textarea,
    #centralForm button,
    #centralForm a {
        overflow-wrap: anywhere;
    }

    #centralForm input,
    #centralForm select,
    #centralForm textarea {
        max-width: 100%;
    }

    .central-check-option span {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .central-check-option:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
    }

    .central-check-option.is-selected {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1e40af;
    }

    .central-check-option.is-selected i,
    .central-check-option.is-selected span {
        color: #1e40af;
    }

    @media (max-width: 640px) {
        #centralForm {
            gap: 1.25rem;
        }

        #centralForm input,
        #centralForm select,
        #centralForm textarea {
            font-size: 16px;
        }

        #checklist-field > div {
            padding: 1rem;
        }

        .central-check-option {
            align-items: flex-start;
            padding: 0.7rem;
        }

        #preview_fotos {
            grid-template-columns: 1fr;
        }
    }
</style>

<form action="{{ $formAction }}"
      method="POST"
      enctype="multipart/form-data"
      class="space-y-6"
      id="centralForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" name="linea_id" value="{{ $linea->id }}">

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <label for="piso_selector" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                Piso *
            </label>
            <select id="piso_selector"
                    name="piso"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    required>
                @foreach($pisosCentral as $piso => $label)
                    <option value="{{ $piso }}" {{ $selectedPiso === $piso ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="configuracion_id" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-oil-can text-blue-600 mr-1"></i>
                Componente / revision *
            </label>
            <select id="configuracion_id"
                    name="configuracion_id"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('configuracion_id') border-red-500 @enderror"
                    required>
                <option value="">Seleccionar componente...</option>
            </select>
            @error('configuracion_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div id="lado-field" class="hidden">
            <label for="lado" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-arrows-left-right text-blue-600 mr-1"></i>
                Lado *
            </label>
            <select id="lado"
                    name="lado"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('lado') border-red-500 @enderror">
                <option value="">Seleccionar lado...</option>
                @foreach($ladosCentral as $lado => $label)
                    <option value="{{ $lado }}" {{ $selectedLado === $lado ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('lado')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <section id="checklist-field" class="hidden">
        <div class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
            <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">
                        <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                        Seleccione los componentes revisados
                    </h3>
                    @if($mostrarTextoPiezasVerificadas)
                        <p class="text-sm text-gray-500">Seleccione las piezas verificadas en este registro.</p>
                    @endif
                </div>
                <span class="inline-flex w-fit items-center rounded-full border border-blue-200 bg-white px-3 py-1 text-xs font-bold text-blue-700" id="checklist-counter">0 seleccionadas</span>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3" id="checklist-container"></div>
            @error('componentes_revisados')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <div id="cantidad-field" class="hidden">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <label for="cantidad_componentes_revisados" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-list-ol text-blue-600 mr-1"></i>
                Cantidad revisada
            </label>
            <input id="cantidad_componentes_revisados"
                   type="number"
                   name="cantidad_componentes_revisados"
                   value="{{ old('cantidad_componentes_revisados', $analisis->cantidad_componentes_revisados ?? '') }}"
                   min="0"
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cantidad_componentes_revisados') border-red-500 @enderror">
            <p class="mt-1 text-sm text-gray-500" id="cantidad-help">Use este campo para componentes con cantidades grandes o pendientes por definir.</p>
            @error('cantidad_componentes_revisados')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <label for="fecha_analisis" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                Fecha del analisis *
            </label>
            <input id="fecha_analisis"
                   type="date"
                   name="fecha_analisis"
                   value="{{ old('fecha_analisis', optional($analisis->fecha_analisis ?? null)->format('Y-m-d') ?: $fechaSugerida) }}"
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('fecha_analisis') border-red-500 @enderror"
                   required>
            @error('fecha_analisis')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="numero_orden" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                Numero de orden
            </label>
            <input id="numero_orden"
                   type="text"
                   name="numero_orden"
                   value="{{ old('numero_orden', $analisis->numero_orden ?? '') }}"
                   maxlength="8"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   autocomplete="off"
                   placeholder="Ej: 35221456"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)"
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('numero_orden') border-red-500 @enderror">
            @error('numero_orden')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="estado" class="block text-sm font-semibold text-gray-700 mb-2">
            <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
            Estado del componente *
        </label>
        <select id="estado"
                name="estado"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('estado') border-red-500 @enderror"
                required>
            <option value="">Seleccionar estado...</option>
            @foreach($estadoOpciones as $estado => $label)
                <option value="{{ $estado }}" {{ $selectedEstado === $estado ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('estado')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="actividad" class="block text-sm font-semibold text-gray-700 mb-2">
            <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
            Actividad realizada *
        </label>
        <textarea id="actividad"
                  name="actividad"
                  rows="5"
                  placeholder="Describe la falla, la revision realizada o la actividad ejecutada..."
                  class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('actividad') border-red-500 @enderror"
                  required>{{ old('actividad', $analisis->actividad ?? '') }}</textarea>
        @error('actividad')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if($mostrarEvidencias)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
            <label class="mb-3 block text-sm font-semibold text-gray-800">
                <i class="fas fa-camera mr-1 text-blue-600"></i>
                Evidencias fotograficas
            </label>
            <input type="file"
                   id="evidencia_fotos"
                   name="evidencia_fotos[]"
                   multiple
                   accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp"
                   class="hidden">

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

            @if($mostrarResumenEvidencias)
                <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p id="fotos_resumen" class="text-sm font-medium text-gray-600">Sin imagenes seleccionadas</p>
                        <p class="text-xs text-gray-500">JPG, PNG, WEBP, GIF o BMP. Max. 5MB en total.</p>
                    </div>
                    <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
                </div>
            @else
                <p id="fotos_resumen" class="sr-only">Sin imagenes seleccionadas</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
            @endif

            @error('evidencia_fotos')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('evidencia_fotos.*')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="create-actions border-t border-gray-200 pt-6">
        <a href="{{ $isEdit ? $analisisRoute('show', $analisis->id) : $analisisRoute('index', ['linea_id' => $linea->id]) }}"
           class="create-action create-action--secondary flex-1 {{ $isEdit ? '' : 'analysis-create-action--cancel-mobile' }}">
            <i class="fas fa-arrow-left"></i>
            Cancelar
        </a>
        <button type="submit" class="create-action flex-1 {{ $isEdit ? '' : 'analysis-create-action--save-mobile' }}">
            <i class="fas fa-save"></i>
            {{ $isEdit ? 'Guardar cambios' : 'Guardar analisis' }}
        </button>
    </div>
</form>

@if($mostrarEvidencias)
    <script src="{{ asset('js/evidence-image-compression.js') }}"></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', () => {
    const configs = @json($configPayload);
    const selectedConfigId = @json((string) $selectedConfig);
    const selectedPieces = new Set(@json($selectedPiezas).map(Number));
    const pisoSelector = document.getElementById('piso_selector');
    const configSelect = document.getElementById('configuracion_id');
    const ladoField = document.getElementById('lado-field');
    const ladoSelect = document.getElementById('lado');
    const checklistField = document.getElementById('checklist-field');
    const checklistContainer = document.getElementById('checklist-container');
    const checklistCounter = document.getElementById('checklist-counter');
    const cantidadField = document.getElementById('cantidad-field');
    const cantidadInput = document.getElementById('cantidad_componentes_revisados');
    const cantidadHelp = document.getElementById('cantidad-help');
    const summaryTitle = document.getElementById('config-summary-title');
    const summaryDetail = document.getElementById('config-summary-detail');
    const summaryChip = document.getElementById('config-summary-chip');
    const summaryPiso = document.getElementById('summary-piso');
    const summaryComponente = document.getElementById('summary-componente');
    const summaryCantidadLado = document.getElementById('summary-cantidad-lado');
    const centralForm = document.getElementById('centralForm');

    function currentConfig() {
        return configs.find((config) => String(config.id) === String(configSelect.value));
    }

    function fillComponents() {
        const piso = pisoSelector.value;
        const previousValue = configSelect.value || selectedConfigId;
        configSelect.innerHTML = '<option value="">Seleccionar componente...</option>';

        configs
            .filter((config) => config.piso === piso)
            .forEach((config) => {
                const option = document.createElement('option');
                option.value = config.id;
                option.textContent = config.nombre;
                if (String(config.id) === String(previousValue)) {
                    option.selected = true;
                }
                configSelect.appendChild(option);
            });

        updateConfig();
    }

    function updateCounter() {
        const count = checklistContainer.querySelectorAll('input[type="checkbox"]:checked').length;
        checklistCounter.textContent = `${count} seleccionada${count === 1 ? '' : 's'}`;
    }

    function updateHeaderSummary(config = null) {
        const selectedPisoLabel = pisoSelector.selectedOptions[0]?.textContent || 'Sin seleccionar';
        const selectedLadoLabel = ladoSelect.value
            ? ladoSelect.selectedOptions[0]?.textContent || 'Lado seleccionado'
            : 'Lado pendiente';

        if (summaryPiso) {
            summaryPiso.textContent = config?.piso_label || selectedPisoLabel;
        }

        if (summaryComponente) {
            summaryComponente.textContent = config?.nombre || 'Sin seleccionar';
        }

        if (summaryCantidadLado) {
            summaryCantidadLado.textContent = config
                ? `${config.cantidad_label} | ${config.contabilizable ? (config.lado_requerido ? selectedLadoLabel : 'Piso completo') : 'Revision'}`
                : 'Sin seleccionar';
        }
    }

    function renderChecklist(total, config) {
        checklistContainer.innerHTML = '';

        for (let index = 1; index <= total; index++) {
            const label = document.createElement('label');
            label.className = 'central-check-option text-sm font-semibold text-gray-700';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'componentes_revisados[]';
            checkbox.value = String(index);
            checkbox.className = 'h-5 w-5 cursor-pointer rounded text-blue-600 focus:ring-blue-500';
            checkbox.checked = selectedPieces.has(index);
            checkbox.addEventListener('change', () => {
                label.classList.toggle('is-selected', checkbox.checked);
                updateCounter();
            });

            const icon = document.createElement('i');
            icon.className = 'fas fa-cube text-blue-500';

            const span = document.createElement('span');
            span.className = 'flex-1';
            span.textContent = `${config?.nombre || 'Componente'} #${index}`;

            label.appendChild(checkbox);
            label.appendChild(icon);
            label.appendChild(span);
            checklistContainer.appendChild(label);
            checkbox.dispatchEvent(new Event('change'));
        }

        updateCounter();
    }

    function updateConfig() {
        const config = currentConfig();

        if (!config) {
            ladoField.classList.add('hidden');
            checklistField.classList.add('hidden');
            cantidadField.classList.add('hidden');
            cantidadInput.value = '';
            if (summaryTitle) {
                summaryTitle.textContent = 'Seleccione un componente';
            }
            if (summaryDetail) {
                summaryDetail.textContent = 'La cantidad configurada saldra del Excel base.';
            }
            if (summaryChip) {
                summaryChip.textContent = 'Sin seleccion';
            }
            updateHeaderSummary();
            return;
        }

        if (summaryTitle) {
            summaryTitle.textContent = config.nombre;
        }
        if (summaryDetail) {
            summaryDetail.textContent = config.detalle_excel || 'Cantidad lista para configurar cuando se defina en campo.';
        }
        if (summaryChip) {
            summaryChip.textContent = `${config.piso_label} | ${config.cantidad_label}`;
        }
        updateHeaderSummary(config);

        ladoField.classList.toggle('hidden', !config.lado_requerido);
        ladoSelect.required = Boolean(config.lado_requerido);
        if (!config.lado_requerido) {
            ladoSelect.value = '';
        }

        const total = Number(config.cantidad || 0);
        const isCountable = config.contabilizable !== false;
        const useChecklist = isCountable && total > 1 && total <= 24;
        const singlePiece = isCountable && total === 1;
        checklistField.classList.toggle('hidden', !useChecklist);
        cantidadField.classList.toggle('hidden', !isCountable || useChecklist || singlePiece);

        if (useChecklist) {
            cantidadInput.value = '';
            cantidadInput.required = false;
            cantidadInput.removeAttribute('max');
            renderChecklist(total, config);
        } else if (!isCountable) {
            checklistContainer.innerHTML = '';
            cantidadInput.required = false;
            cantidadInput.min = String(total || 0);
            cantidadInput.max = String(total || 0);
            cantidadInput.value = String(total || 300);
            cantidadHelp.textContent = `${config.nombre} se registra como revision conceptual de ${config.cantidad_label}.`;
        } else if (singlePiece) {
            checklistContainer.innerHTML = '';
            cantidadInput.required = false;
            cantidadInput.min = '1';
            cantidadInput.max = '1';
            cantidadInput.value = '1';
            cantidadHelp.textContent = 'Componente de una sola pieza; se registra automaticamente como revisada.';
        } else {
            checklistContainer.innerHTML = '';
            cantidadInput.required = total > 0;
            cantidadInput.min = total > 0 ? '1' : '0';
            if (total > 0) {
                cantidadInput.max = String(total);
                cantidadHelp.textContent = `Total configurado: ${total} ${config.unidad}.`;
            } else {
                cantidadInput.removeAttribute('max');
                cantidadHelp.textContent = 'El Excel no define cantidad; registra lo revisado y la configuracion se podra completar despues.';
            }
        }
    }

    @if($mostrarEvidencias)
    const evidenciaFotosInput = document.getElementById('evidencia_fotos');
    const evidenciaFotosGaleriaInput = document.getElementById('evidencia_fotos_galeria');
    const evidenciaFotosCamaraInput = document.getElementById('evidencia_fotos_camara');
    const fotosResumen = document.getElementById('fotos_resumen');
    const previewFotos = document.getElementById('preview_fotos');
    const btnGaleria = document.getElementById('btn_evidencia_fotos_galeria');
    const btnCamara = document.getElementById('btn_evidencia_fotos_camara');
    const imageCompression = window.EvidenceImageCompression;
    const maxFotoSize = imageCompression?.MAX_INPUT_BYTES ?? 12 * 1024 * 1024;
    const maxFotoSizeMb = Math.round(maxFotoSize / 1024 / 1024);
    const maxFotosTotalSize = 5 * 1024 * 1024;
    const maxFotosTotalSizeMb = Math.round(maxFotosTotalSize / 1024 / 1024);
    const extensionesPermitidasFotos = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    let procesandoFotos = false;

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

    function totalFotosSize(files) {
        return Array.from(files || []).reduce((total, file) => total + Number(file?.size || 0), 0);
    }

    function fotosSuperanTotal(files) {
        return totalFotosSize(files) > maxFotosTotalSize;
    }

    function actualizarResumenFotos(total) {
        if (!fotosResumen) {
            return;
        }

        fotosResumen.textContent = total === 0
            ? 'Sin imagenes seleccionadas'
            : `${total} imagen${total === 1 ? '' : 'es'} seleccionada${total === 1 ? '' : 's'}`;
    }

    function renderFotoPreview(files, permitirEliminar) {
        if (!previewFotos) {
            return;
        }

        previewFotos.innerHTML = '';
        actualizarResumenFotos(files.length);

        files.forEach((file, index) => {
            if (!esImagenSeleccionable(file)) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                const card = document.createElement('div');
                card.className = 'relative group';

                const img = document.createElement('img');
                img.src = event.target.result;
                img.alt = file.name;
                img.className = 'w-24 h-24 object-cover rounded-lg border border-gray-200 shadow-sm';
                card.appendChild(img);

                if (permitirEliminar) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity text-xs flex items-center justify-center';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.dataset.removeIndex = String(index);
                    card.appendChild(removeBtn);
                }

                previewFotos.appendChild(card);
            };
            reader.readAsDataURL(file);
        });
    }

    async function agregarArchivos(files) {
        if (!soportaDataTransferFotos) {
            renderFotoPreview(getFotosFallback(), false);
            return;
        }

        if (procesandoFotos) {
            alert('Espera a que terminen de optimizarse las imagenes.');
            return;
        }

        const fotosActuales = getFotosPrincipales();
        const firmas = new Set(fotosActuales.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
        const nuevasFotos = [...fotosActuales];
        procesandoFotos = true;
        if (fotosResumen) {
            fotosResumen.textContent = 'Optimizando imagenes...';
        }

        for (const file of Array.from(files || [])) {
            if (!esImagenSeleccionable(file)) {
                alert(`El archivo ${file.name} no es una imagen valida.`);
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

            if (fotosSuperanTotal([...nuevasFotos, fotoOptimizada])) {
                alert(`El total de imagenes no puede superar ${maxFotosTotalSizeMb}MB.`);
                continue;
            }

            firmas.add(firma);
            nuevasFotos.push(fotoOptimizada);
        }

        const dataTransfer = crearDataTransferFotos(nuevasFotos);
        if (dataTransfer && evidenciaFotosInput) {
            evidenciaFotosInput.files = dataTransfer.files;
            renderFotoPreview(getFotosPrincipales(), true);
        }
        procesandoFotos = false;
    }

    previewFotos?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-index]');
        if (!button || !soportaDataTransferFotos) {
            return;
        }

        const removeIndex = Number(button.dataset.removeIndex);
        const fotos = getFotosPrincipales().filter((file, index) => index !== removeIndex);
        const dataTransfer = crearDataTransferFotos(fotos);

        if (dataTransfer && evidenciaFotosInput) {
            evidenciaFotosInput.files = dataTransfer.files;
            renderFotoPreview(getFotosPrincipales(), true);
        }
    });

    btnGaleria?.addEventListener('click', () => evidenciaFotosGaleriaInput?.click());
    btnCamara?.addEventListener('click', () => evidenciaFotosCamaraInput?.click());
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
    } else if (evidenciaFotosInput) {
        evidenciaFotosGaleriaInput.name = 'evidencia_fotos[]';
        evidenciaFotosCamaraInput.name = 'evidencia_fotos[]';
        evidenciaFotosInput.disabled = true;

        const renderizarFallbackFotos = () => renderFotoPreview(getFotosFallback(), false);
        evidenciaFotosGaleriaInput?.addEventListener('change', renderizarFallbackFotos);
        evidenciaFotosCamaraInput?.addEventListener('change', renderizarFallbackFotos);
        renderizarFallbackFotos();
    }

    centralForm?.addEventListener('submit', (event) => {
        if (procesandoFotos) {
            event.preventDefault();
            alert('Espera a que terminen de optimizarse las imagenes.');
            return;
        }

        const fotos = soportaDataTransferFotos ? getFotosPrincipales() : getFotosFallback();
        if (fotosSuperanTotal(fotos)) {
            event.preventDefault();
            alert(`El total de imagenes no puede superar ${maxFotosTotalSizeMb}MB.`);
            return;
        }

        if (soportaDataTransferFotos && evidenciaFotosInput) {
            evidenciaFotosInput.disabled = getFotosPrincipales().length === 0;
        }
    });
    @endif

    pisoSelector.addEventListener('change', fillComponents);
    configSelect.addEventListener('change', updateConfig);
    ladoSelect.addEventListener('change', () => updateHeaderSummary(currentConfig()));
    fillComponents();
});
</script>
