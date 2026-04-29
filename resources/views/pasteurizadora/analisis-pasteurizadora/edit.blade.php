@extends('layouts.app')

@section('title', 'Editar Análisis - Pasteurizadora')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.show', $analisis->id) }}"
               class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                Editar Análisis
            </h1>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-600 font-semibold">Línea</p>
                    <p class="text-gray-800">{{ $analisis->linea->nombre ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-600 font-semibold">Módulo</p>
                    <p class="text-gray-800">Módulo {{ $analisis->modulo }}</p>
                </div>
                <div>
                    <p class="text-gray-600 font-semibold">Componente</p>
                    <p class="text-gray-800">{{ $analisis->componente_nombre }}</p>
                </div>
                @if($analisis->lado)
                <div>
                    <p class="text-gray-600 font-semibold">Lado</p>
                    <p class="text-gray-800">{{ $analisis->lado }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="{{ route('pasteurizadora.analisis-pasteurizadora.update', $analisis->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="modulo" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cubes text-blue-600 mr-1"></i>
                        Módulo
                    </label>
                    <input type="number" name="modulo" id="modulo" value="{{ old('modulo', $analisis->modulo) }}"
                           min="1" max="16" readonly
                           class="w-full rounded-lg border-gray-300 bg-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500 cursor-not-allowed">
                    @error('modulo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                        Nivel
                    </label>
                    <select name="nivel" id="nivel" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar nivel...</option>
                        <option value="SUPERIOR" {{ old('nivel', $analisis->nivel) == 'SUPERIOR' ? 'selected' : '' }}>â¬†ï¸ Nivel Superior</option>
                        <option value="INFERIOR" {{ old('nivel', $analisis->nivel) == 'INFERIOR' ? 'selected' : '' }}>â¬‡ï¸ Nivel Inferior</option>
                    </select>
                    @error('nivel')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                        Lado *
                    </label>
                    <select name="lado" id="lado" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Seleccionar lado...</option>
                        <option value="VAPOR" {{ old('lado', $analisis->lado) == 'VAPOR' ? 'selected' : '' }}>ðŸ’¨ Lado Vapor</option>
                        <option value="PASILLO" {{ old('lado', $analisis->lado) == 'PASILLO' ? 'selected' : '' }}>ðŸš¶ Lado Pasillo</option>
                    </select>
                    @error('lado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha de Análisis *
                    </label>
                    <input type="date" name="fecha_analisis" id="fecha_analisis"
                           value="{{ old('fecha_analisis', $analisis->fecha_analisis->format('Y-m-d')) }}"
                           required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('fecha_analisis')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        NÃºmero de Orden *
                    </label>
                    <input type="text" name="numero_orden" id="numero_orden"
                           value="{{ old('numero_orden', $analisis->numero_orden) }}"
                           maxlength="20" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('numero_orden')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                        Estado *
                    </label>
                    <select name="estado" id="estado" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Seleccionar estado...</option>
                        @foreach(\App\Models\AnalisisPasteurizadora::ESTADOS as $estado)
                            <option value="{{ $estado }}" {{ old('estado', $analisis->estado) == $estado ? 'selected' : '' }}>
                                @if($estado == 'Buen estado') âœ… Buen estado
                                @elseif($estado == 'Desgaste moderado') âš ï¸ Desgaste moderado
                                @elseif($estado == 'Desgaste severo') âš ï¸ Desgaste severo
                                @elseif($estado == 'DaÃ±ado - Requiere cambio') âŒ DaÃ±ado - Requiere cambio
                                @else ðŸ”„ Cambiado
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('estado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="responsable" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-user text-blue-600 mr-1"></i>
                        Responsable
                    </label>
                    <input type="text" name="responsable" id="responsable"
                           value="{{ old('responsable', $analisis->responsable) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('responsable')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Checklist de componentes revisados --}}
            @php
                $componentes = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($analisis->linea->nombre);
                $totalComponentes = $componentes[$analisis->componente]['cantidad'] ?? 0;
                $componentesRevisados = \App\Models\AnalisisPasteurizadora::normalizarComponentesRevisados(
                    $analisis->componentes_revisados,
                    $totalComponentes
                );

                // Convertir a array de integers para comparación consistente
                if (empty($componentesRevisados) && $analisis->cantidad_componentes_revisados) {
                    $componentesRevisados = range(1, min($analisis->cantidad_componentes_revisados, $totalComponentes));
                }
            @endphp

            @if($totalComponentes > 0 && !\App\Models\AnalisisPasteurizadora::esBrazoTorsion($analisis->componente))
            <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                <h3 class="text-sm font-semibold text-indigo-800 mb-4">
                    <i class="fas fa-clipboard-check mr-1"></i>
                    Componentes revisados ({{ count($componentesRevisados) }} de {{ $totalComponentes }})
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @for($i = 1; $i <= $totalComponentes; $i++)
                        <label class="flex items-center gap-3 p-3 bg-white rounded-lg border border-indigo-200 hover:border-indigo-400 hover:shadow-md transition cursor-pointer">
                            <input type="checkbox"
                                   name="componentes_revisados[]"
                                   value="{{ $i }}"
                                   {{ in_array($i, $componentesRevisados, true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded cursor-pointer focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">
                                <i class="fas fa-cube text-indigo-500 mr-1"></i>
                                @if(\App\Models\AnalisisPasteurizadora::esBrazoTorsion($analisis->componente))
                                    {{ $analisis->componente_nombre }} modulo {{ $i }}
                                @else
                                    {{ $analisis->componente }} #{{ $i }}
                                @endif
                            </span>
                        </label>
                    @endfor
                </div>
            </div>
            @elseif(\App\Models\AnalisisPasteurizadora::esBrazoTorsion($analisis->componente))
                <input type="hidden" name="componentes_revisados[]" value="1">
                <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200 text-sm text-indigo-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Este registro corresponde al Brazo de Torsion del modulo {{ $analisis->modulo }}.
                </div>
            @endif
            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad *
                </label>
                <textarea name="actividad" id="actividad" rows="4" required
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('actividad', $analisis->actividad) }}</textarea>
                @error('actividad')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Evidencia fotográfica existente --}}
            @if($analisis->evidencia_fotos && count($analisis->evidencia_fotos) > 0)
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-images text-blue-600 mr-1"></i>
                    Imágenes actuales
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach($analisis->evidencia_fotos as $index => $foto)
                    <div class="relative group">
                        <img src="{{ Storage::url($foto) }}" alt="Evidencia {{ $index + 1 }}"
                             class="w-full h-24 object-cover rounded-lg border border-gray-200">
                        <button type="button"
                                onclick="eliminarFoto({{ $index }})"
                                class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Subir nuevas imágenes --}}
            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Agregar más imágenes
                </label>
                <input type="file" name="evidencia_fotos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm
                              file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100">
                <p class="text-gray-500 text-xs mt-1">Puede agregar más imágenes (Formatos: JPG, PNG. Máx: 5MB cada una)</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.show', $analisis->id) }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg px-5 py-3 hover:from-blue-600 hover:to-blue-700 transition shadow-md">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Análisis
                </button>
            </div>
        </form>
    </div>
</div>

<form id="deleteFotoForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    document.getElementById('evidencia_fotos')?.addEventListener('change', function() {
        const preview = document.getElementById('preview_fotos');
        preview.innerHTML = '';
        const files = Array.from(this.files);

        files.forEach(file => {
            if (!file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.createElement('div');
                container.className = 'relative group';
                container.innerHTML = `
                    <img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                    <button type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center" onclick="this.parentElement.remove()">Ã—</button>
                `;
                preview.appendChild(container);
            }
            reader.readAsDataURL(file);
        });
    });

    function eliminarFoto(index) {
        if (confirm('Â¿Eliminar esta imagen?')) {
            const form = document.getElementById('deleteFotoForm');
            form.action = "/pasteurizadora/analisis-pasteurizadora/{{ $analisis->id }}/foto/" + index;
            form.submit();
        }
    }
</script>
@endsection
