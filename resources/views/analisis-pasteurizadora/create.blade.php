@extends('layouts.app')

@section('title', 'Crear Análisis de Componente - Pasteurizadora')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header mejorado --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('analisis-pasteurizadora.select-linea') }}"
               class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                Crear Análisis
            </h1>
        </div>
        
        {{-- Información del contexto --}}
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-3">
                    <div class="w-20 h-20 flex-shrink-0">
                        <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                             alt="Icono de pasteurizadora" 
                             class="w-full h-full object-contain"
                             onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Línea</p>
                        <p class="text-gray-800">{{ $linea->nombre ?? 'Pasteurizadora ' . $linea->id }}</p>
                    </div>
                </div>
                <div id="modulo-info" class="hidden">
                    <p class="text-gray-600 font-semibold">Módulo</p>
                    <p id="modulo-nombre" class="text-gray-800"></p>
                </div>
                <div id="componente-info" class="hidden">
                    <p class="text-gray-600 font-semibold">Componente</p>
                    <p id="componente-nombre" class="text-gray-800"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Card Form --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="{{ route('analisis-pasteurizadora.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="linea_id" value="{{ $linea->id }}">

            {{-- Select Módulo y Componente --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Módulo --}}
                <div id="modulo-selector-container">
                    <label for="modulo" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cubes text-blue-600 mr-1"></i>
                        Módulo *
                    </label>
                    <select id="modulo" name="modulo"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                   @error('modulo') border-red-500 @enderror"
                            required>
                        <option value="">Seleccionar módulo...</option>
                        @for($i = 1; $i <= 16; $i++)
                            <option value="{{ $i }}" {{ old('modulo') == $i ? 'selected' : '' }}>
                                Módulo {{ $i }}
                            </option>
                        @endfor
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Seleccione el módulo a analizar</p>
                    @error('modulo')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Componente --}}
                <div>
                    <label for="componente" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cog text-blue-600 mr-1"></i>
                        Componente *
                    </label>
                    <select id="componente" name="componente"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                   @error('componente') border-red-500 @enderror"
                            required
                            onchange="actualizarPorComponente()">
                        <option value="">Seleccionar componente...</option>
                        @foreach(App\Models\AnalisisPasteurizadora::COMPONENTES as $codigo => $nombre)
                            <option value="{{ $codigo }}" {{ old('componente') == $codigo ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('componente')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Selector de Lado --}}
            <div id="lado-selector-container" class="mb-6">
                <label for="lado" class="block text-sm font-medium text-gray-700 mb-1">
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
                <p class="text-gray-500 text-xs mt-1">Indique si el análisis corresponde al lado vapor o lado pasillo</p>
                @error('lado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fecha y Número de Orden --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Fecha de Análisis --}}
                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha de Análisis *
                    </label>
                    <input type="date" id="fecha_analisis" name="fecha_analisis"
                           value="{{ old('fecha_analisis', $fechaSugerida ?? date('Y-m-d')) }}"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                  @error('fecha_analisis') border-red-500 @enderror"
                           required>
                    @error('fecha_analisis')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Número de Orden --}}
                <div>
                    <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        Número de Orden *
                    </label>
                    <input type="text" 
                           id="numero_orden"
                           name="numero_orden" 
                           value="{{ old('numero_orden') }}"
                           required
                           maxlength="20"
                           placeholder="Ej: OT-2024-001"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                           @error('numero_orden') border-red-500 @enderror">
                    @error('numero_orden')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Estado --}}
            <div class="mb-6">
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-tasks text-blue-600 mr-1"></i>
                    Estado *
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
            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad *
                </label>
                <textarea id="actividad" name="actividad" rows="4"
                          placeholder="Describa la actividad realizada o notas adicionales sobre el componente..."
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                 @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad') }}</textarea>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Evidencia Fotográfica --}}
            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencia Fotográfica
                </label>
                <input type="file" id="evidencia_fotos" name="evidencia_fotos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm
                              focus:ring-blue-500 focus:border-blue-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100
                              @error('evidencia_fotos.*') border-red-500 @enderror">
                <p class="text-gray-500 text-xs mt-1">Puede seleccionar múltiples imágenes (Formatos: JPG, PNG, GIF. Máx: 2MB cada una)</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
                @error('evidencia_fotos.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-pasteurizadora.select-linea') }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition-all shadow-md">
                    <i class="fas fa-times mr-2"></i>Cancelar
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
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputFotos = document.getElementById('evidencia_fotos');
    const previewFotos = document.getElementById('preview_fotos');
    const moduloSelect = document.getElementById('modulo');
    const componenteSelect = document.getElementById('componente');
    const ladoInput = document.getElementById('lado');
    const revisadasInput = document.getElementById('revisadas');
    const cantidadesContainer = document.getElementById('cantidades-container');

    // Verificar que los elementos existen antes de usarlos
    if (!inputFotos || !previewFotos || !moduloSelect || !componenteSelect || !ladoInput || !cantidadesContainer || !revisadasInput) {
        console.warn('Algunos elementos del formulario no fueron encontrados');
        return;
    }

    // Vista previa de imágenes
    inputFotos.addEventListener('change', function() {
        previewFotos.innerHTML = '';
        const files = Array.from(this.files);

        files.forEach(file => {
            if(!file.type.startsWith('image/')) return;
            
            if (file.size > 2097152) {
                alert(`La imagen ${file.name} supera el tamaño máximo de 2MB`);
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'relative group';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-lg border border-gray-200 shadow-sm';
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity text-xs flex items-center justify-center';
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

    // Validar formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!ladoInput.value) {
            e.preventDefault();
            alert('Debe seleccionar el lado del análisis (Vapor o Pasillo).');
            ladoInput.focus();
            return;
        }
        
        if (revisadasInput && !cantidadesContainer.classList.contains('hidden')) {
            const max = parseInt(revisadasInput.getAttribute('max') || '16');
            const value = parseInt(revisadasInput.value);
            
            if (isNaN(value) || value < 0) {
                e.preventDefault();
                alert('Debe ingresar una cantidad válida de piezas revisadas.');
                revisadasInput.focus();
                return;
            }
            
            if (value > max) {
                e.preventDefault();
                alert(`La cantidad de piezas revisadas no puede ser mayor a ${max}.`);
                revisadasInput.focus();
                return;
            }
        }
    });
});

// Función para actualizar según componente seleccionado
function actualizarPorComponente() {
    const componenteSelect = document.getElementById('componente');
    const cantidadesContainer = document.getElementById('cantidades-container');
    const revisadasInput = document.getElementById('revisadas');
    const totalPiezas = document.getElementById('total-piezas');
    
    if (!componenteSelect || !cantidadesContainer || !revisadasInput || !totalPiezas) return;
    
    const componente = componenteSelect.value;
    
    // Mostrar contenedor de cantidades para componentes específicos
    const componentesConCantidades = ['PLACAS_PERNO', 'REGILLAS', 'RODAMIENTOS', 'EXCENTRICOS', 'PISTAS', 'ESPARRAGOS', 'ANILLAS'];
    
    if (componentesConCantidades.includes(componente)) {
        cantidadesContainer.classList.remove('hidden');
        revisadasInput.setAttribute('required', 'required');
        
        // Actualizar el total de piezas (siempre 16 por módulo para todos los componentes)
        totalPiezas.textContent = '16';
        revisadasInput.setAttribute('max', '16');
    } else {
        cantidadesContainer.classList.add('hidden');
        revisadasInput.removeAttribute('required');
        revisadasInput.value = '';
    }
    
    // Actualizar información del encabezado
    const moduloInfo = document.getElementById('modulo-info');
    const componenteInfo = document.getElementById('componente-info');
    const moduloNombre = document.getElementById('modulo-nombre');
    const componenteNombre = document.getElementById('componente-nombre');
    
    if (moduloInfo && componenteInfo && moduloNombre && componenteNombre) {
        const moduloSelect = document.getElementById('modulo');
        
        if (moduloSelect && moduloSelect.value) {
            moduloNombre.textContent = 'Módulo ' + moduloSelect.value;
            moduloInfo.classList.remove('hidden');
        } else {
            moduloInfo.classList.add('hidden');
        }
        
        if (componenteSelect.value) {
            const selectedOption = componenteSelect.options[componenteSelect.selectedIndex];
            componenteNombre.textContent = selectedOption.textContent;
            componenteInfo.classList.remove('hidden');
        } else {
            componenteInfo.classList.add('hidden');
        }
    }
}

// Evento para cuando cambia el módulo
document.addEventListener('DOMContentLoaded', function() {
    const moduloSelect = document.getElementById('modulo');
    if (moduloSelect) {
        moduloSelect.addEventListener('change', function() {
            const componenteSelect = document.getElementById('componente');
            const moduloInfo = document.getElementById('modulo-info');
            const moduloNombre = document.getElementById('modulo-nombre');
            
            if (moduloInfo && moduloNombre && componenteSelect) {
                if (this.value) {
                    moduloNombre.textContent = 'Módulo ' + this.value;
                    moduloInfo.classList.remove('hidden');
                } else {
                    moduloInfo.classList.add('hidden');
                }
            }
        });
    }
});
</script>
@endsection