@extends('layouts.app')

@section('title', 'Agregar Análisis Rápido - Pasteurizadora')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        {{-- Encabezado --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', request()->query()) }}"
                   class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    Agregar Análisis Rápido
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

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 flex-grow">
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
                                @php
                                    $componentesConfig = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre ?? '');
                                    $nombreComponente = $componentesConfig[$componente]['nombre'] ?? $componente;
                                @endphp
                                {{ $nombreComponente }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Formulario --}}
        <form action="{{ route('pasteurizadora.analisis-pasteurizadora.store-quick') }}" 
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
                    <option value="VAPOR" {{ old('lado') == 'VAPOR' ? 'selected' : '' }}>💨 Lado Vapor</option>
                    <option value="PASILLO" {{ old('lado') == 'PASILLO' ? 'selected' : '' }}>🚶 Lado Pasillo</option>
                </select>
                @error('lado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Nivel (opcional) --}}
            <div>
                <label for="nivel" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                    Nivel del Módulo
                </label>
                <select id="nivel" name="nivel"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Seleccionar nivel...</option>
                    <option value="SUPERIOR" {{ old('nivel') == 'SUPERIOR' ? 'selected' : '' }}>⬆️ Nivel Superior</option>
                    <option value="INFERIOR" {{ old('nivel') == 'INFERIOR' ? 'selected' : '' }}>⬇️ Nivel Inferior</option>
                </select>
            </div>

            {{-- Sección de checklist dinámico de componentes --}}
            <div id="checklist-container" class="hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <label class="block text-sm font-bold text-gray-800 mb-4">
                        <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                        Seleccione los componentes revisados
                    </label>
                    <div id="componentes-checklist" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <!-- Los checkboxes se generarán dinámicamente aquí -->
                    </div>
                    <input type="hidden" name="componentes_revisados" id="componentes_revisados_input" value="">
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    Marque los componentes que fueron revisados en este análisis
                </p>
            </div>
            
            {{-- Fecha --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                    Fecha del Análisis *
                </label>
                <input type="date" 
                       name="fecha_analisis" 
                       value="{{ old('fecha_analisis', $fecha ?? now()->format('Y-m-d')) }}"
                       required
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                       @error('fecha_analisis') border-red-500 @enderror">
                @error('fecha_analisis')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Número de Orden --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                    Número de Orden *
                </label>
                <input type="text" 
                       name="numero_orden" 
                       value="{{ old('numero_orden') }}"
                       required
                       maxlength="8"
                       placeholder="Ej: OT-2024-001"
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
                    <option value="Buen estado" {{ old('estado') == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                    <option value="Desgaste moderado" {{ old('estado') == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                    <option value="Desgaste severo" {{ old('estado') == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                    <option value="Dañado - Requiere cambio" {{ old('estado') == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                    <option value="Cambiado" {{ old('estado') == 'Cambiado' ? 'selected' : '' }}>🔄 Cambiado</option>
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
                
            
            {{-- Evidencia Fotográfica --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencia Fotográfica
                </label>
                <input type="file" 
                       name="evidencia_fotos[]" 
                       multiple 
                       accept="image/*"
                       class="w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100">
                <p class="text-xs text-gray-500 mt-1">Puede seleccionar múltiples imágenes (Formatos: JPG, PNG. Máx: 5MB cada una)</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
            </div>
            
            {{-- Botones --}}
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
    // ============================================================
    // CHECKLIST DINÁMICO DE COMPONENTES
    // ============================================================
    
    const checklist_container = document.getElementById('checklist-container');
    const componentes_checklist = document.getElementById('componentes-checklist');
    const componentes_revisados_input = document.getElementById('componentes_revisados_input');
    
    // Obtener el componente actual de la URL o del campo hidden
    const componenteActual = '{{ $componente ?? '' }}';
    const lineaNombre = '{{ $linea->nombre ?? '' }}';
    const totalPiezasBackend = {{ $totalPiezas ?? 0 }};
    
    console.log('Componente actual:', componenteActual);
    console.log('Línea:', lineaNombre);
    console.log('Total piezas (backend):', totalPiezasBackend);
    
    // Configuración de componentes con sus cantidades
    const componentesConfig = {
        @php
            $linea = $linea ?? null;
            if ($linea) {
                $componentes = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);
                foreach ($componentes as $codigo => $comp) {
                    $codigoLimpio = addslashes($codigo);
                    echo "'{$codigoLimpio}': " . $comp['cantidad'] . ",\n";
                }
            }
        @endphp
    };
    
    console.log('Configuración de componentes:', componentesConfig);
    
    function generarChecklist() {
        // Limpiar el componente actual
        const componenteKey = componenteActual.trim().toUpperCase();
        
        // Buscar la cantidad
        let cantidad = totalPiezasBackend;
        
        // Si no hay cantidad del backend, buscar en el mapa
        if (cantidad === 0) {
            if (componentesConfig[componenteKey] !== undefined) {
                cantidad = componentesConfig[componenteKey];
            } else {
                for (const [key, value] of Object.entries(componentesConfig)) {
                    if (key.toUpperCase() === componenteKey) {
                        cantidad = value;
                        break;
                    }
                }
            }
        }
        
        console.log('Cantidad encontrada para', componenteKey, ':', cantidad);
        
        if (cantidad > 0) {
            checklist_container.classList.remove('hidden');
            componentes_checklist.innerHTML = '';
            
            // Obtener el nombre del componente
            let componenteNombre = '{{ $nombreComponente ?? $componente ?? "Componente" }}';
            
            for (let i = 1; i <= cantidad; i++) {
                const id = `componente_${i}`;
                const label = document.createElement('label');
                label.className = 'flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer';
                label.innerHTML = `
                    <input type="checkbox" 
                           data-component-value="${i}" 
                           id="${id}"
                           class="w-5 h-5 text-blue-600 rounded cursor-pointer focus:ring-blue-500 componente-checkbox"
                           onchange="window.actualizarComponentesRevisados()">
                    <span class="flex-1 text-gray-700 font-medium">
                        <i class="fas fa-cube text-blue-500 mr-2"></i>
                        ${componenteNombre} #${i}
                    </span>
                `;
                componentes_checklist.appendChild(label);
            }
            
            actualizarComponentesRevisados();
        } else {
            checklist_container.classList.add('hidden');
            componentes_checklist.innerHTML = '';
            console.warn('No se encontró cantidad para el componente:', componenteActual);
        }
    }
    
    function actualizarComponentesRevisados() {
        const checkboxes = document.querySelectorAll('input.componente-checkbox:checked');
        const valores = Array.from(checkboxes).map(cb => parseInt(cb.getAttribute('data-component-value')));
        const json = JSON.stringify(valores);
        
        if (componentes_revisados_input) {
            componentes_revisados_input.value = json;
        }
        
        console.log('Componentes seleccionados:', valores);
        console.log('JSON a enviar:', json);
        
        const debugElement = document.getElementById('debug-json');
        const jsonValueElement = document.getElementById('json-value');
        if (valores.length > 0) {
            if (debugElement) debugElement.classList.remove('hidden');
            if (jsonValueElement) jsonValueElement.textContent = json;
        } else {
            if (debugElement) debugElement.classList.add('hidden');
        }
    }
    
    window.actualizarComponentesRevisados = actualizarComponentesRevisados;
    
    if (componenteActual) {
        generarChecklist();
    } else {
        console.warn('No hay componente definido');
    }
    
    // ============================================================
    // PREVIEW DE IMÁGENES
    // ============================================================
    
    const inputFotos = document.querySelector('input[name="evidencia_fotos[]"]');
    const previewFotos = document.getElementById('preview_fotos');
    
    if (inputFotos && previewFotos) {
        inputFotos.addEventListener('change', function() {
            previewFotos.innerHTML = '';
            const files = Array.from(this.files);
            
            files.forEach(file => {
                if(!file.type.startsWith('image/')) return;
                
                if (file.size > 5242880) {
                    alert(`La imagen ${file.name} supera el tamaño máximo de 5MB`);
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'relative group';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-20 h-20 object-cover rounded-lg border border-gray-200';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 opacity-0 group-hover:opacity-100 transition text-xs flex items-center justify-center';
                    removeBtn.innerHTML = '×';
                    removeBtn.onclick = function() {
                        imgContainer.remove();
                        const dt = new DataTransfer();
                        const inputFiles = inputFotos.files;
                        
                        for (let i = 0; i < inputFiles.length; i++) {
                            if (inputFiles[i] !== file) {
                                dt.items.add(inputFiles[i]);
                            }
                        }
                        
                        inputFotos.files = dt.files;
                    };
                    
                    imgContainer.appendChild(img);
                    imgContainer.appendChild(removeBtn);
                    previewFotos.appendChild(imgContainer);
                }
                reader.readAsDataURL(file);
            });
        });
    }
    
    // ============================================================
    // VALIDACIÓN DEL FORMULARIO
    // ============================================================
    
    document.getElementById('analisisForm').addEventListener('submit', function(e) {
        const lado = document.getElementById('lado');
        if (lado && !lado.value) {
            e.preventDefault();
            alert('Debe seleccionar el lado del análisis (Vapor o Pasillo).');
            lado.focus();
            return;
        }
        
        if (checklist_container && !checklist_container.classList.contains('hidden')) {
            const checkboxes = document.querySelectorAll('input.componente-checkbox:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Debe seleccionar al menos un componente revisado.');
                return;
            }
            
            const jsonValue = componentes_revisados_input ? componentes_revisados_input.value : '';
            console.log('Enviando componentes_revisados:', jsonValue);
            
            if (!jsonValue || jsonValue === '[]') {
                e.preventDefault();
                alert('Error: No se pudieron guardar los componentes seleccionados.');
                return;
            }
        }
    });
});
</script>
@endsection