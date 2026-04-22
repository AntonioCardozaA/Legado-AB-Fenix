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
            <input type="hidden" name="nivel" value="{{ $nivel ?? '' }}">
            
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
                $estadoRevision = \App\Models\AnalisisPasteurizadora::getEstadoRevision($linea->id, $modulo, $componenteKey, null);
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
            <div id="checklist-container">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-800 mb-2">
                            <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                            Seleccione los componentes revisados
                        </label>
                        @if($alreadyReviewedCount > 0)
                            <div class="bg-blue-100 border border-blue-400 rounded-lg p-3 mb-3">
                                <p class="text-sm text-blue-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Ya revisados en este lado y nivel:</strong> {{ $alreadyReviewedCount }} de {{ $totalPiezas }} piezas
                                </p>
                                @if(!empty($alreadyReviewedComponents))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($alreadyReviewedComponents as $compNum)
                                            <span class="inline-flex items-center px-2 py-0.5 bg-blue-200 text-blue-800 rounded text-xs">
                                                #{{ $compNum }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <p class="text-sm text-blue-800 mt-2">
                                    <strong>Pendientes en este lado y nivel:</strong> {{ $remainingPiezas }} piezas
                                </p>
                            </div>
                        @endif
                    </div>
                    
                    <div id="componentes-checklist" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @php
                            $todosLosNumeros = range(1, $totalPiezas);
                        @endphp
                        
                        @if($totalPiezas > 0)
                            @foreach($todosLosNumeros as $numero)
                                @php
                                    $yaRevisado = in_array($numero, $alreadyReviewedComponents);
                                @endphp
                                <label class="flex items-center gap-3 p-3 bg-white rounded-lg border {{ $yaRevisado ? 'border-gray-200 bg-gray-50 opacity-60' : 'border-gray-200 hover:border-blue-400 hover:shadow-md' }} transition cursor-pointer">
                                    <input type="checkbox" 
                                           data-component-value="{{ $numero }}" 
                                           class="w-5 h-5 text-blue-600 rounded cursor-pointer focus:ring-blue-500 componente-checkbox"
                                           onchange="actualizarComponentesRevisados()"
                                           {{ $yaRevisado ? 'disabled checked' : '' }}>
                                    <span class="flex-1 {{ $yaRevisado ? 'text-gray-400 line-through' : 'text-gray-700 font-medium' }}">
                                        <i class="fas fa-cube text-blue-500 mr-2"></i>
                                        {{ $nombreComponente ?? $componente }} #{{ $numero }}
                                        @if($yaRevisado)
                                            <span class="ml-2 text-xs text-green-600">(Ya revisado)</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        @else
                            <div class="col-span-full text-center py-8 bg-yellow-50 rounded-lg border border-yellow-200">
                                <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-2"></i>
                                <p class="text-yellow-700 font-medium">No se encontraron piezas para este componente</p>
                            </div>
                        @endif
                    </div>
                    
                    <input type="hidden" name="componentes_revisados" id="componentes_revisados_input" value="{{ json_encode(old('componentes_revisados', [])) }}">
                    
                    {{-- Mensaje de lados pendientes --}}
                    @if(isset($ladosPendientes) && count($ladosPendientes) > 0 && $lado)
                        <div class="mt-4 p-3 bg-yellow-100 border border-yellow-400 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Lados pendientes por revisar:</strong>
                                @foreach($ladosPendientes as $lp)
                                    <span class="inline-flex items-center ml-2 px-2 py-1 rounded text-xs {{ $lp === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                        <i class="fas {{ $lp === 'VAPOR' ? 'fa-wind' : 'fa-walking' }} mr-1"></i>
                                        {{ $lp === 'VAPOR' ? 'Vapor' : 'Pasillo' }}
                                    </span>
                                @endforeach
                                @if($lado && in_array($lado, $ladosPendientes))
                                    <span class="ml-2 text-xs">(Actual: {{ $lado === 'VAPOR' ? 'Vapor' : 'Pasillo' }})</span>
                                @endif
                            </p>
                        </div>
                    @endif
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
                       maxlength="50"
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
function actualizarComponentesRevisados() {
    const checkboxes = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');
    const valores = Array.from(checkboxes).map(cb => parseInt(cb.getAttribute('data-component-value')));
    const json = JSON.stringify(valores);
    
    const input = document.getElementById('componentes_revisados_input');
    if (input) {
        input.value = json;
    }
    
    console.log('Componentes seleccionados:', valores);
    console.log('JSON a enviar:', json);
}

// Preview de imágenes
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Inicializar el input con los valores seleccionados
    actualizarComponentesRevisados();
    
    // Validación del formulario
    document.getElementById('analisisForm').addEventListener('submit', function(e) {
        const lado = document.getElementById('lado');
        if (!lado.value) {
            e.preventDefault();
            alert('Debe seleccionar el lado del análisis (Vapor o Pasillo).');
            lado.focus();
            return;
        }
        
        const checkboxes = document.querySelectorAll('input.componente-checkbox:checked:not(:disabled)');
        if (checkboxes.length === 0) {
            // Verificar si hay piezas disponibles para revisar
            const hayPiezasDisponibles = document.querySelectorAll('input.componente-checkbox:not(:disabled)').length > 0;
            if (hayPiezasDisponibles) {
                e.preventDefault();
                alert('Debe seleccionar al menos un componente revisado.');
                return;
            }
        }
    });
});
</script>
@endsection