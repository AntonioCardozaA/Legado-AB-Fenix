@php
    $isEdit = filled($analisis);
    $selectedMaquina = old('maquina', $maquinaSeleccionada ?: ($analisis->maquina ?? ''));
    $selectedComponente = old('componente_id', $componenteSeleccionado ?: ($analisis->componente_id ?? ''));
    $componentesPlanos = $componentesPorMaquina->flatten(1);
    $componentesRevisadosSeleccionados = old('componentes_revisados');
    if ($componentesRevisadosSeleccionados === null && $isEdit) {
        $componentesRevisadosSeleccionados = $analisis->componentes_revisados_lista;
    }
    $componentesRevisadosSeleccionados = \App\Models\AnalisisEtiquetadora::normalizarComponentesRevisados(
        $componentesRevisadosSeleccionados ?? [],
        null
    );
    $action = $isEdit
        ? route('analisis-etiquetadora.update', $analisis)
        : route('analisis-etiquetadora.store');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" name="linea_id" value="{{ old('linea_id', $linea->id) }}">

    @error('error')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $message }}
        </div>
    @enderror

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <label for="maquina" class="mb-1 block text-sm font-medium text-gray-700">
                <i class="fas fa-compress-alt mr-1 text-blue-600"></i>
                Maquina *
            </label>
            <select id="maquina"
                    name="maquina"
                    required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('maquina') border-red-500 @enderror">
                <option value="">Seleccionar maquina...</option>
                @foreach($maquinas as $maquina)
                    <option value="{{ $maquina }}" @selected($selectedMaquina === $maquina)>
                        Maquina {{ $maquina }}
                    </option>
                @endforeach
            </select>
            @error('maquina') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="componente_id" class="mb-1 block text-sm font-medium text-gray-700">
                <i class="fas fa-cog mr-1 text-blue-600"></i>
                Componente *
            </label>
            <select id="componente_id"
                    name="componente_id"
                    required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('componente_id') border-red-500 @enderror">
                <option value="">Seleccionar componente...</option>
                @foreach($componentesPlanos as $componente)
                    @php($maquinaComponente = str_replace('Maquina ', '', str_replace('Máquina ', '', $componente->reductor)))
                    <option value="{{ $componente->id }}"
                            data-maquina="{{ $maquinaComponente }}"
                            data-nombre="{{ $componente->nombre }}"
                            data-grupo="{{ $componente->grupo }}"
                            data-mecanismo="{{ $componente->mecanismo }}"
                            data-cantidad="{{ $componente->cantidad_total }}"
                            data-original="{{ $componente->cantidad_original }}"
                            @selected((string) $selectedComponente === (string) $componente->id)>
                        {{ $componente->nombre }}
                    </option>
                @endforeach
            </select>
            @error('componente_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div id="component-context" class="hidden rounded-xl border p-4 text-sm" style="border-color: var(--etq-ink-border); background: linear-gradient(135deg, var(--etq-ink-soft), #fff); color: var(--etq-ink);">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <span class="block text-xs font-bold uppercase opacity-70">Grupo</span>
                <span id="context-grupo" class="font-semibold"></span>
            </div>
            <div>
                <span class="block text-xs font-bold uppercase opacity-70">Mecanismo</span>
                <span id="context-mecanismo" class="font-semibold"></span>
            </div>
            <div>
                <span class="block text-xs font-bold uppercase opacity-70">Cantidad por maquina</span>
                <span id="context-cantidad" class="font-semibold"></span>
            </div>
        </div>
    </div>

    <div id="componentes-checklist-wrapper" class="hidden rounded-xl border border-indigo-200 bg-indigo-50 p-5">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-indigo-900">
                    <i class="fas fa-clipboard-check mr-1 text-indigo-600"></i>
                    Piezas revisadas
                </h3>
                <p id="componentes-checklist-help" class="mt-1 text-xs font-medium text-indigo-700"></p>
            </div>
            <p id="componentes-checklist-counter" class="text-xs font-semibold text-indigo-700"></p>
        </div>

        <input type="hidden" name="componentes_revisados[]" value="">
        <div id="componentes-checklist" class="grid grid-cols-2 gap-3 md:grid-cols-3"></div>
        @error('componentes_revisados') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('componentes_revisados.*') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <label for="fecha_analisis" class="mb-1 block text-sm font-medium text-gray-700">
                <i class="far fa-calendar-alt mr-1 text-blue-600"></i>
                Fecha de Analisis *
            </label>
            <input type="date"
                   id="fecha_analisis"
                   name="fecha_analisis"
                   value="{{ old('fecha_analisis', optional($analisis?->fecha_analisis)->format('Y-m-d') ?? now()->toDateString()) }}"
                   required
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('fecha_analisis') border-red-500 @enderror">
            @error('fecha_analisis') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="numero_orden" class="mb-1 block text-sm font-medium text-gray-700">
                <i class="fas fa-hashtag mr-1 text-blue-600"></i>
                Numero de Orden *
            </label>
            <input type="text"
                   id="numero_orden"
                   name="numero_orden"
                   value="{{ old('numero_orden', $analisis->numero_orden ?? '') }}"
                   required
                   maxlength="20"
                   placeholder="Ej: 35221456"
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('numero_orden') border-red-500 @enderror">
            @error('numero_orden') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="estado" class="mb-1 block text-sm font-medium text-gray-700">
            <i class="fas fa-clipboard-check mr-1 text-blue-600"></i>
            Estado del Componente *
        </label>
        <select id="estado"
                name="estado"
                required
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('estado') border-red-500 @enderror">
            <option value="">Seleccionar estado...</option>
            @foreach(\App\Models\AnalisisEtiquetadora::getEstadoOpciones() as $estado => $label)
                <option value="{{ $estado }}" @selected(old('estado', $analisis->estado ?? '') === $estado)>{{ $label }}</option>
            @endforeach
        </select>
        @error('estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="actividad" class="mb-1 block text-sm font-medium text-gray-700">
            <i class="fas fa-sticky-note mr-1 text-blue-600"></i>
            Actividad / Observaciones *
        </label>
        <textarea id="actividad"
                  name="actividad"
                  rows="4"
                  required
                  placeholder="Describa la actividad realizada o notas adicionales sobre el componente..."
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('actividad') border-red-500 @enderror">{{ old('actividad', $analisis->actividad ?? '') }}</textarea>
        @error('actividad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @if($isEdit && collect($analisis->evidencia_fotos ?? [])->isNotEmpty())
        <div>
            <label class="mb-3 block text-sm font-medium text-gray-700">
                <i class="fas fa-images mr-1 text-blue-600"></i>
                Evidencias Actuales
            </label>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach($analisis->evidencia_fotos as $index => $foto)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-2 transition hover:bg-gray-100">
                        <img src="{{ asset('storage/' . $foto) }}" alt="Evidencia {{ $index + 1 }}" class="h-32 w-full rounded-md border border-gray-300 object-cover">
                        <label class="mt-2 flex cursor-pointer items-center justify-between gap-2 text-sm">
                            <span class="text-gray-600">Foto {{ $index + 1 }}</span>
                            <span class="inline-flex items-center gap-1 text-red-600">
                                <input type="checkbox" name="eliminar_fotos[]" value="{{ $index }}" class="rounded border-gray-300 text-red-600">
                                Eliminar
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">
            <i class="fas fa-camera mr-1 text-blue-600"></i>
            Evidencia Fotografica
        </label>
        <input type="file"
               id="evidencia_fotos"
               name="evidencia_fotos[]"
               multiple
               accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp"
               class="sr-only">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label for="evidencia_fotos_galeria" class="create-action create-action--secondary w-full cursor-pointer">
                    <i class="fas fa-images"></i>
                    Subir desde galeria
                </label>
                <input type="file"
                       id="evidencia_fotos_galeria"
                       multiple
                       accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp"
                       class="sr-only">
            </div>
            <div>
                <label for="evidencia_fotos_camara" class="create-action create-action--success w-full cursor-pointer">
                    <i class="fas fa-camera-retro"></i>
                    Tomar foto ahora
                </label>
                <input type="file"
                       id="evidencia_fotos_camara"
                       multiple
                       capture="environment"
                       accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/bmp"
                       class="sr-only">
            </div>
        </div>
        <p id="fotos_resumen" class="mt-3 text-sm text-gray-500">Sin imagenes seleccionadas</p>
        <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
        @error('evidencia_fotos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('evidencia_fotos.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="create-actions border-t border-gray-200 pt-6">
        <a href="{{ route('analisis-etiquetadora.index', ['linea_id' => $linea->id, 'maquina' => $selectedMaquina]) }}"
           class="create-action create-action--secondary flex-1">
            <i class="fas fa-times"></i>
            Cancelar
        </a>
        <button type="submit" class="create-action flex-1">
            <i class="fas fa-save"></i>
            {{ $isEdit ? 'Actualizar Analisis' : 'Guardar Analisis' }}
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const maquina = document.getElementById('maquina');
    const componente = document.getElementById('componente_id');
    const context = document.getElementById('component-context');
    const grupo = document.getElementById('context-grupo');
    const mecanismo = document.getElementById('context-mecanismo');
    const cantidad = document.getElementById('context-cantidad');
    const componenteInfo = document.getElementById('componente-info');
    const componenteNombre = document.getElementById('componente-nombre');
    const maquinaInfo = document.getElementById('maquina-info');
    const maquinaNombre = document.getElementById('maquina-nombre');
    const checklistWrapper = document.getElementById('componentes-checklist-wrapper');
    const checklist = document.getElementById('componentes-checklist');
    const checklistHelp = document.getElementById('componentes-checklist-help');
    const checklistCounter = document.getElementById('componentes-checklist-counter');
    const inputFotos = document.getElementById('evidencia_fotos');
    const previewFotos = document.getElementById('preview_fotos');
    const fotosResumen = document.getElementById('fotos_resumen');
    const fotoInputs = [
        document.getElementById('evidencia_fotos_galeria'),
        document.getElementById('evidencia_fotos_camara'),
    ].filter(Boolean);
    const soportaDataTransfer = typeof DataTransfer !== 'undefined';
    const extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    const maxFotoSize = 12 * 1024 * 1024;
    const initialComponentesRevisados = @json($componentesRevisadosSeleccionados);
    let selectedComponentesRevisados = new Set(
        (Array.isArray(initialComponentesRevisados) ? initialComponentesRevisados : [])
            .map((item) => parseInt(item, 10))
            .filter((item) => item > 0)
    );
    let checklistComponentValue = @json((string) $selectedComponente);

    function updateComponents() {
        const selectedMachine = maquina.value;
        const selectedValue = componente.value;
        let visibleSelected = false;

        Array.from(componente.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const visible = !selectedMachine || option.dataset.maquina === selectedMachine;
            option.hidden = !visible;
            option.disabled = !visible;

            if (visible && option.value === selectedValue) {
                visibleSelected = true;
            }
        });

        if (selectedValue && !visibleSelected) {
            componente.value = '';
        }

        updateContext();
    }

    function updateContext() {
        const option = componente.selectedOptions[0];

        if (maquinaInfo && maquinaNombre) {
            if (maquina.value) {
                maquinaNombre.textContent = `Maquina ${maquina.value}`;
                maquinaInfo.classList.remove('hidden');
            } else {
                maquinaInfo.classList.add('hidden');
            }
        }

        if (!option || !option.value) {
            context.classList.add('hidden');
            if (componenteInfo) {
                componenteInfo.classList.add('hidden');
            }
            updateChecklist();
            return;
        }

        grupo.textContent = option.dataset.grupo || '-';
        mecanismo.textContent = option.dataset.mecanismo || '-';
        cantidad.textContent = `${option.dataset.cantidad || '0'} (${option.dataset.original || 'sin dato'})`;
        context.classList.remove('hidden');

        if (componenteInfo && componenteNombre) {
            componenteNombre.textContent = option.dataset.nombre || option.textContent.trim();
            componenteInfo.classList.remove('hidden');
        }

        updateChecklist();
    }

    function syncChecklistCounter(total) {
        if (!checklistCounter) {
            return;
        }

        checklistCounter.textContent = `${selectedComponentesRevisados.size} de ${total} seleccionadas`;
    }

    function updateSelectedFromChecklist(total) {
        selectedComponentesRevisados = new Set(
            Array.from(checklist.querySelectorAll('input[type="checkbox"]:checked'))
                .map((input) => parseInt(input.value, 10))
                .filter((item) => item > 0 && item <= total)
        );

        syncChecklistCounter(total);
    }

    function appendChecklistItem(numero, nombre, total) {
        const label = document.createElement('label');
        label.className = 'flex cursor-pointer items-center gap-3 rounded-lg border border-indigo-200 bg-white p-3 transition hover:border-indigo-400 hover:shadow-sm';

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.name = 'componentes_revisados[]';
        input.value = String(numero);
        input.checked = selectedComponentesRevisados.has(numero);
        input.className = 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500';
        input.addEventListener('change', () => updateSelectedFromChecklist(total));

        const text = document.createElement('span');
        text.className = 'text-sm font-medium text-gray-700';
        text.textContent = `${nombre} #${numero}`;

        label.appendChild(input);
        label.appendChild(text);
        checklist.appendChild(label);
    }

    function updateChecklist() {
        const option = componente.selectedOptions[0];

        if (!checklistWrapper || !checklist || !option || !option.value) {
            checklistWrapper?.classList.add('hidden');
            return;
        }

        if (checklistComponentValue !== option.value) {
            selectedComponentesRevisados = new Set();
            checklistComponentValue = option.value;
        }

        const total = parseInt(option.dataset.cantidad || '0', 10) || 0;
        const nombre = option.dataset.nombre || option.textContent.trim() || 'Pieza';

        checklist.innerHTML = '';

        if (total <= 1) {
            checklistWrapper.classList.add('hidden');
            return;
        }

        checklistWrapper.classList.remove('hidden');

        if (checklistHelp) {
            checklistHelp.textContent = `Selecciona una o varias de las ${total} piezas configuradas para este componente.`;
        }

        for (let numero = 1; numero <= total; numero++) {
            appendChecklistItem(numero, nombre, total);
        }

        syncChecklistCounter(total);
    }

    function updateFileSummary(total) {
        if (!fotosResumen) {
            return;
        }

        fotosResumen.textContent = total === 0
            ? 'Sin imagenes seleccionadas'
            : `${total} ${total === 1 ? 'imagen seleccionada' : 'imagenes seleccionadas'}`;
    }

    function crearDataTransfer(files) {
        const dataTransfer = new DataTransfer();
        files.forEach((file) => dataTransfer.items.add(file));
        return dataTransfer;
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

    function fotosPrincipales() {
        return Array.from(inputFotos?.files || []);
    }

    function fotosFallback() {
        return fotoInputs.flatMap((input) => Array.from(input.files || []));
    }

    function renderPreview(files, permitirEliminar) {
        if (!previewFotos) {
            updateFileSummary(files.length);
            return;
        }

        previewFotos.innerHTML = '';
        updateFileSummary(files.length);

        files.forEach((file, index) => {
            if (!esImagenValida(file)) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const wrapper = document.createElement('div');
                wrapper.className = 'etq-preview-thumb';

                const image = document.createElement('img');
                image.src = event.target.result;
                image.alt = `Evidencia ${index + 1}`;
                wrapper.appendChild(image);

                if (permitirEliminar) {
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'etq-preview-remove';
                    remove.innerHTML = '&times;';
                    remove.setAttribute('aria-label', 'Eliminar imagen');
                    remove.addEventListener('click', function() {
                        const fotos = fotosPrincipales();
                        fotos.splice(index, 1);
                        inputFotos.files = crearDataTransfer(fotos).files;
                        renderPreview(fotosPrincipales(), true);
                    });
                    wrapper.appendChild(remove);
                }

                previewFotos.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        });
    }

    function agregarFotos(files) {
        const actuales = fotosPrincipales();
        const firmas = new Set(actuales.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
        const nuevas = [...actuales];

        Array.from(files || []).forEach((file) => {
            if (!esImagenValida(file)) {
                alert(`El archivo ${file.name} no es una imagen valida.`);
                return;
            }

            if (file.size > maxFotoSize) {
                alert(`La imagen ${file.name} supera el tamano maximo permitido.`);
                return;
            }

            const firma = `${file.name}-${file.size}-${file.lastModified}`;
            if (firmas.has(firma)) {
                return;
            }

            firmas.add(firma);
            nuevas.push(file);
        });

        inputFotos.files = crearDataTransfer(nuevas).files;
        renderPreview(fotosPrincipales(), true);
    }

    maquina.addEventListener('change', updateComponents);
    componente.addEventListener('change', updateContext);
    if (inputFotos && soportaDataTransfer) {
        inputFotos.addEventListener('change', function() {
            renderPreview(fotosPrincipales(), true);
        });

        fotoInputs.forEach((input) => {
            input.addEventListener('change', function() {
                agregarFotos(this.files);
                this.value = '';
            });
        });

        renderPreview(fotosPrincipales(), true);
    } else {
        if (inputFotos) {
            inputFotos.disabled = true;
        }

        fotoInputs.forEach((input) => {
            input.name = 'evidencia_fotos[]';
            input.addEventListener('change', function() {
                renderPreview(fotosFallback(), false);
            });
        });

        renderPreview(fotosFallback(), false);
    }
    updateComponents();
});
</script>
