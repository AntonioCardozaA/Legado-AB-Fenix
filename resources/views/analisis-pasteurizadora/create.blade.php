@extends('layouts.app')

@section('title', 'Crear Análisis de Componente - Pasteurizadora')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header --}}
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
        
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 flex-shrink-0">
                    <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                         alt="Icono de pasteurizadora" 
                         class="w-full h-full object-contain"
                         onerror="this.src='{{ asset('images/icono-maquina.png') }}'">
                </div>
                <div>
                    <p class="text-gray-600 font-semibold">Línea seleccionada</p>
                    <p class="text-gray-800 text-xl font-bold">{{ $linea->nombre ?? 'Pasteurizadora' }}</p>
                    @php
                        $totalModulos = \App\Models\AnalisisPasteurizadora::getModulosPorLinea($linea->nombre);
                        $totalComponentes = count(\App\Models\AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre));
                    @endphp
                    <p class="text-sm text-gray-500">{{ $totalModulos }} módulos | {{ $totalComponentes }} componentes</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="{{ route('analisis-pasteurizadora.store') }}" enctype="multipart/form-data" id="analisisForm">
            @csrf
            <input type="hidden" name="linea_id" value="{{ $linea->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="modulo" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cubes text-blue-600 mr-1"></i>
                        Módulo *
                    </label>
                    <select id="modulo" name="modulo" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar módulo...</option>
                        @for($i = 1; $i <= $totalModulos; $i++)
                            <option value="{{ $i }}" {{ old('modulo') == $i ? 'selected' : '' }}>Módulo {{ $i }}</option>
                        @endfor
                    </select>
                    @error('modulo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                        Nivel del Módulo
                    </label>
                    <select id="nivel" name="nivel" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar nivel...</option>
                        <option value="SUPERIOR" {{ old('nivel') == 'SUPERIOR' ? 'selected' : '' }}>⬆️ Nivel Superior</option>
                        <option value="INFERIOR" {{ old('nivel') == 'INFERIOR' ? 'selected' : '' }}>⬇️ Nivel Inferior</option>
                    </select>
                    @error('nivel')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="componente" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cog text-blue-600 mr-1"></i>
                        Componente *
                    </label>
                    <select id="componente" name="componente" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar componente...</option>
                        @foreach(\App\Models\AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre) as $codigo => $comp)
                            <option value="{{ $codigo }}" 
                                    data-cantidad="{{ $comp['cantidad'] }}"
                                    {{ old('componente') == $codigo ? 'selected' : '' }}>
                                {{ $comp['nombre'] }} ({{ $comp['cantidad'] }} und)
                            </option>
                        @endforeach
                    </select>
                    @error('componente')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                        Lado del Análisis *
                    </label>
                    <select id="lado" name="lado" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar lado...</option>
                        <option value="VAPOR" {{ old('lado') == 'VAPOR' ? 'selected' : '' }}>💨 Lado Vapor</option>
                        <option value="PASILLO" {{ old('lado') == 'PASILLO' ? 'selected' : '' }}>🚶 Lado Pasillo</option>
                    </select>
                    @error('lado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha de Análisis *
                    </label>
                    <input type="date" name="fecha_analisis" id="fecha_analisis"
                           value="{{ old('fecha_analisis', $fechaSugerida ?? date('Y-m-d')) }}"
                           required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('fecha_analisis')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        Número de Orden *
                    </label>
                    <input type="text" name="numero_orden" id="numero_orden"
                           value="{{ old('numero_orden') }}" required maxlength="8"
                           placeholder="Ej: OT-2024-001"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('numero_orden')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-tasks text-blue-600 mr-1"></i>
                        Estado *
                    </label>
                    <select name="estado" id="estado" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar estado...</option>
                        @foreach(\App\Models\AnalisisPasteurizadora::ESTADOS as $estado)
                            <option value="{{ $estado }}" {{ old('estado') == $estado ? 'selected' : '' }}>
                                @if($estado == 'Buen estado') ✅ Buen estado
                                @elseif($estado == 'Desgaste moderado') ⚠️ Desgaste moderado
                                @elseif($estado == 'Desgaste severo') ⚠️ Desgaste severo
                                @elseif($estado == 'Dañado - Requiere cambio') ❌ Dañado - Requiere cambio
                                @else 🔄 Cambiado
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('estado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>


            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad *
                </label>
                <textarea name="actividad" id="actividad" rows="4" required
                          placeholder="Describa la actividad realizada o notas adicionales sobre el componente..."
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('actividad') }}</textarea>
                @error('actividad')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Evidencia Fotográfica
                </label>
                <input type="file" id="evidencia_fotos" name="evidencia_fotos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm
                              file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100">
                <p class="text-gray-500 text-xs mt-1">Puede seleccionar múltiples imágenes (Formatos: JPG, PNG. Máx: 5MB cada una)</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
                @error('evidencia_fotos.*')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-pasteurizadora.select-linea') }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg px-5 py-3 hover:from-blue-600 hover:to-blue-700 transition shadow-md">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Análisis
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const componenteSelect = document.getElementById('componente');
    const cantidadContainer = document.getElementById('cantidad-container');
    const revisadasInput = document.getElementById('revisadas_piezas');
    const totalDisplay = document.getElementById('total-piezas-display');
    
    function actualizarCantidad() {
        const selectedOption = componenteSelect.options[componenteSelect.selectedIndex];
        const total = selectedOption?.dataset?.cantidad || 0;
        
        if (total > 0) {
            cantidadContainer.classList.remove('hidden');
            revisadasInput.max = total;
            revisadasInput.required = true;
            totalDisplay.textContent = total;
        } else {
            cantidadContainer.classList.add('hidden');
            revisadasInput.required = false;
            revisadasInput.value = 0;
        }
    }
    
    componenteSelect.addEventListener('change', actualizarCantidad);
    actualizarCantidad();
    
    // Preview de imágenes
    document.getElementById('evidencia_fotos')?.addEventListener('change', function() {
        const preview = document.getElementById('preview_fotos');
        preview.innerHTML = '';
        const files = Array.from(this.files);
        
        files.forEach(file => {
            if (!file.type.startsWith('image/')) return;
            
            if (file.size > 5242880) {
                alert(`La imagen ${file.name} supera el tamaño máximo de 5MB`);
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.createElement('div');
                container.className = 'relative group';
                container.innerHTML = `
                    <img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                    <button type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center" onclick="this.parentElement.remove()">×</button>
                `;
                preview.appendChild(container);
            }
            reader.readAsDataFile(file);
        });
    });
    
    // Validación del formulario
    document.getElementById('analisisForm').addEventListener('submit', function(e) {
        const lado = document.getElementById('lado');
        if (!lado.value) {
            e.preventDefault();
            alert('Debe seleccionar el lado del análisis (Vapor o Pasillo).');
            lado.focus();
            return;
        }
        
        const modulo = document.getElementById('modulo');
        if (!modulo.value) {
            e.preventDefault();
            alert('Debe seleccionar el módulo.');
            modulo.focus();
            return;
        }
        
        const componente = document.getElementById('componente');
        if (!componente.value) {
            e.preventDefault();
            alert('Debe seleccionar el componente.');
            componente.focus();
            return;
        }
        
        if (!cantidadContainer.classList.contains('hidden')) {
            const revisadas = parseInt(revisadasInput.value);
            const max = parseInt(revisadasInput.max);
            if (isNaN(revisadas) || revisadas < 0) {
                e.preventDefault();
                alert('Debe ingresar una cantidad válida de piezas revisadas.');
                revisadasInput.focus();
                return;
            }
            if (revisadas > max) {
                e.preventDefault();
                alert(`La cantidad de piezas revisadas no puede ser mayor a ${max}.`);
                revisadasInput.focus();
                return;
            }
        }
    });
</script>
@endsection