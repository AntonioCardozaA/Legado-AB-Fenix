@extends('layouts.app')

@section('title', 'Editar Análisis de Componente')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            @php
                $backUrl = request()->has('redirect_to') 
                    ? request()->input('redirect_to')
                    : route('analisis-lavadora.index', request()->query());
            @endphp
            <a href="{{ $backUrl }}"
               class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    Editar Análisis de Componente
                </h1>
                <p class="text-gray-600 text-sm mt-1">
                    ID: #{{ $analisisComponente->id }} | 
                    @if($analisisComponente->numero_orden)
                        Orden: {{ $analisisComponente->numero_orden }}
                    @endif
                </p>
            </div>
        </div>
        
        {{-- Mostrar mensajes de éxito/error --}}
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Información del contexto --}}
<div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200 mb-6">

    {{-- Fila superior --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center text-sm">

        {{-- Ícono de máquina --}}
        <div class="flex justify-center md:justify-start">
            <div class="w-20 h-20">
                <img 
                    src="{{ asset('images/icono-maquina.png') }}" 
                    alt="Icono de lavadora"
                    class="w-full h-full object-contain">
            </div>
        </div>

        {{-- Lavadora --}}
        <div>
            <p class="text-gray-600 font-semibold">Lavadora</p>
            <p class="text-gray-800">
                {{ $analisisComponente->linea->nombre ?? 'Lavadora ' . $analisisComponente->linea_id }}
            </p>
        </div>

        {{-- Componente --}}
        <div>
            <p class="text-gray-600 font-semibold">Componente</p>
            <p class="text-gray-800">
                {{ $analisisComponente->componente->nombre ?? 'Componente no encontrado' }}
                @if($analisisComponente->componente->codigo ?? false)
                    <span class="text-gray-500">
                        ({{ $analisisComponente->componente->codigo }})
                    </span>
                @endif
            </p>
        </div>

        {{-- Reductor --}}
        <div>
            <p class="text-gray-600 font-semibold">Reductor</p>
            <p class="text-gray-800">{{ $analisisComponente->reductor }}</p>
        </div>

    </div>

    {{-- Fila inferior --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-300 text-sm">

        <div>
            <p class="text-gray-600 font-semibold">Fecha del análisis</p>
            <p class="text-gray-800">
                {{ \Carbon\Carbon::parse($analisisComponente->fecha_analisis)->format('d/m/Y') }}
            </p>
        </div>

        <div>
            <p class="text-gray-600 font-semibold">Fecha de creación</p>
            <p class="text-gray-800">
                {{ \Carbon\Carbon::parse($analisisComponente->created_at)->format('d/m/Y H:i') }}
            </p>
        </div>

    </div>

</div>


    {{-- Card Form --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" 
              action="{{ route('analisis-lavadora.update', $analisisComponente->id) }}" 
              enctype="multipart/form-data"
              id="editarAnalisisForm">
            @csrf
            @method('PUT')
            
            {{-- Campos ocultos para mantener los valores --}}
            <input type="hidden" name="componente_id" value="{{ $analisisComponente->componente_id }}">
            <input type="hidden" name="reductor" value="{{ $analisisComponente->reductor }}">
            <input type="hidden" name="fecha_analisis" value="{{ $analisisComponente->fecha_analisis }}">
            <input type="hidden" name="redirect_to" value="{{ request()->input('redirect_to') ?? url()->previous() }}">

            {{-- Solo Número de Orden --}}
            <div class="mb-6">
                <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                    Número de Orden *
                </label>
                <input type="text" 
                       id="numero_orden"
                       name="numero_orden" 
                       value="{{ old('numero_orden', $analisisComponente->numero_orden) }}"
                       required
                       maxlength="20"
                       placeholder="Ej: ORD-2024-001"
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm
                       @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Estado --}}
            <div class="mb-6">
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                    Estado del Componente *
                </label>
                <select name="estado" 
                        id="estado"
                        required
                        class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm sm:text-sm
                        @error('estado') border-red-500 @enderror">
                    <option value="Buen estado" {{ old('estado', $analisisComponente->estado) == 'Buen estado' ? 'selected' : '' }}>✅ Buen estado</option>
                    <option value="Desgaste moderado" {{ old('estado', $analisisComponente->estado) == 'Desgaste moderado' ? 'selected' : '' }}>⚠️ Desgaste moderado</option>
                    <option value="Desgaste severo" {{ old('estado', $analisisComponente->estado) == 'Desgaste severo' ? 'selected' : '' }}>⚠️ Desgaste severo</option>
                    <option value="Dañado - Requiere cambio" {{ old('estado', $analisisComponente->estado) == 'Dañado - Requiere cambio' ? 'selected' : '' }}>❌ Dañado - Requiere cambio</option>
                    <option value="Dañado - Cambiado" {{ old('estado', $analisisComponente->estado) == 'Dañado - Cambiado' ? 'selected' : '' }}>🔄 Dañado - Cambiado</option>
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Observaciones --}}
            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad / Observaciones *
                </label>
                <textarea id="actividad" 
                          name="actividad" 
                          rows="4"
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales sobre el componente..."
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                 @error('actividad') border-red-500 @enderror"
                          required>{{ old('actividad', $analisisComponente->actividad) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Describa lo que se realizó durante el análisis o mantenimiento</p>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Evidencia Fotográfica Existente --}}
            @php
                $evidencias = $analisisComponente->evidencia_fotos ?? [];
                $evidencias = is_array($evidencias) ? $evidencias : json_decode($evidencias, true) ?? [];
            @endphp
            
            @if(!empty($evidencias))
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-images text-blue-600 mr-1"></i>
                    Evidencias Actuales
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($evidencias as $index => $ruta)
                    <div class="relative group border border-gray-200 rounded-lg p-2 bg-gray-50 hover:bg-gray-100 transition">
                        {{-- Imagen --}}
                        <div class="mb-2">
                            <img src="{{ Storage::url($ruta) }}" 
                                 alt="Evidencia {{ $loop->iteration }}"
                                 class="w-full h-32 object-cover rounded-md border border-gray-300">
                        </div>
                        
                        {{-- Controles --}}
                        <div class="flex justify-between items-center">
                            <a href="{{ Storage::url($ruta) }}" 
                               target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                                <i class="fas fa-expand text-xs"></i>
                                <span>Ver</span>
                            </a>
                            
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" 
                                       name="eliminar_fotos[]" 
                                       value="{{ $index }}"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-red-600 text-sm hover:text-red-800">
                                    <i class="fas fa-trash text-xs"></i>
                                    Eliminar
                                </span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-2">Marque las casillas para eliminar evidencias</p>
            </div>
            @endif

            {{-- Agregar Nuevas Evidencias --}}
            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Agregar Nuevas Evidencias
                </label>
                <input type="file" 
                       id="evidencia_fotos" 
                       name="evidencia_fotos[]" 
                       multiple 
                       accept="image/*"
                       class="block w-full text-sm text-gray-500 rounded-lg border border-gray-300 shadow-sm
                              focus:ring-blue-500 focus:border-blue-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100
                              @error('evidencia_fotos.*') border-red-500 @enderror">

                <p class="text-gray-500 text-xs mt-1">Puede seleccionar múltiples imágenes (Formatos: JPG, PNG, GIF. Máx: 2MB cada una)</p>

                {{-- Contenedor para vista previa --}}
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>

                @error('evidencia_fotos.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                @php
                    $cancelUrl = request()->has('redirect_to') 
                        ? request()->input('redirect_to')
                        : route('analisis-lavadora.index', request()->query());
                @endphp
                <a href="{{ $cancelUrl }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition-all shadow-md">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg px-5 py-3 hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Análisis
                </button>
            </div>
        </form>
        
        {{-- Formulario de eliminación separado --}}
        @can('delete', $analisisComponente)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-semibold text-red-800 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Zona de Peligro
                </h3>
                <p class="text-sm text-red-600 mb-3">
                    La eliminación es permanente y no se puede deshacer. Todas las evidencias también serán eliminadas.
                </p>
                <button type="button"
                        onclick="confirmarEliminar()"
                        class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg px-4 py-2 hover:from-red-600 hover:to-red-700 transition-all shadow-md hover:shadow-lg text-sm">
                    <i class="fas fa-trash mr-2"></i>
                    Eliminar Análisis
                </button>
            </div>
        </div>
        @endcan
    </div>
</div>

{{-- Modal de confirmación para eliminar --}}
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2 text-center">Confirmar Eliminación</h3>
            <div class="px-7 py-3">
                <p class="text-sm text-gray-500 text-center">
                    ¿Está seguro que desea eliminar este análisis?<br>
                    <strong>ID: #{{ $analisisComponente->id }}</strong><br>
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="flex gap-3 px-4 py-3">
                <button onclick="cerrarModal()"
                        class="flex-1 bg-gray-300 text-gray-700 rounded-md px-4 py-2 hover:bg-gray-400">
                    Cancelar
                </button>
                <form id="deleteForm" 
                      method="POST" 
                      action="{{ route('analisis-lavadora.destroy', $analisisComponente->id) }}"
                      class="flex-1">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="{{ request()->input('redirect_to') }}">
                    <button type="submit"
                            class="w-full bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputFotos = document.getElementById('evidencia_fotos');
    const previewFotos = document.getElementById('preview_fotos');
    const editarForm = document.getElementById('editarAnalisisForm');

    // Vista previa de nuevas imágenes
    inputFotos.addEventListener('change', function() {
        previewFotos.innerHTML = ''; // Limpiar previews anteriores
        const files = Array.from(this.files);

        files.forEach(file => {
            if(!file.type.startsWith('image/')) return; // Solo imágenes
            
            // Validar tamaño (2MB = 2097152 bytes)
            if (file.size > 2097152) {
                alert(`La imagen ${file.name} supera el tamaño máximo de 2MB`);
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'relative group border border-gray-200 rounded-lg p-2 bg-gray-50';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-md border border-gray-300 mb-2';
                
                // Nombre del archivo
                const fileName = document.createElement('p');
                fileName.className = 'text-xs text-gray-600 truncate';
                fileName.textContent = file.name.length > 15 ? 
                    file.name.substring(0, 15) + '...' : file.name;
                
                // Botón para eliminar de la vista previa
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 opacity-0 group-hover:opacity-100 transition-opacity text-xs';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = function() {
                    imgContainer.remove();
                    // Eliminar el archivo del input
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
                imgContainer.appendChild(fileName);
                imgContainer.appendChild(removeBtn);
                previewFotos.appendChild(imgContainer);
            }
            reader.readAsDataURL(file);
        });
    });

    // Depuración: Verificar datos del formulario antes de enviar
    editarForm.addEventListener('submit', function(e) {
        console.log('Formulario enviado');
        console.log('redirect_to:', document.querySelector('input[name="redirect_to"]').value);
    });
});

// Funciones para el modal de eliminación
function confirmarEliminar() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
@endsection