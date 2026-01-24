@extends('layouts.app')

@section('title', 'Crear Análisis de Componente')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">
            Crear Análisis para {{ $linea->nombre ?? 'Lavadora ' . $linea->id }}
        </h1>
        <p class="text-gray-600 mt-1">
            Complete los campos para registrar un nuevo análisis de componente.
        </p>
    </div>

    {{-- Card Form --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">

        <form method="POST" action="{{ route('analisis-componentes.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="linea_id" value="{{ $linea->id }}">

            {{-- Select Componente y Reductor --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                
                {{-- Componente --}}
                <div>
                    <label for="componente_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Componente *
                    </label>
                    <select id="componente_id" name="componente_id"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                   @error('componente_id') border-red-500 @enderror"
                            required>
                        <option value="">Seleccionar componente...</option>
                        @foreach($componentes as $componente)
                            <option value="{{ $componente->id }}"
                                {{ old('componente_id') == $componente->id ? 'selected' : '' }}>
                                {{ $componente->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('componente_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reductor --}}
                <div>
                    <label for="reductor" class="block text-sm font-medium text-gray-700 mb-1">
                        Reductor *
                    </label>
                    <select id="reductor" name="reductor"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                   @error('reductor') border-red-500 @enderror"
                            required>
                        <option value="">Seleccionar reductor...</option>
                        @foreach($reductores as $reductor)
                            <option value="{{ $reductor }}"
                                {{ old('reductor') == $reductor ? 'selected' : '' }}>
                                {{ $reductor }}
                            </option>
                        @endforeach
                    </select>
                    @error('reductor')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Fecha y Número de Orden --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                
                {{-- Fecha de Análisis --}}
                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de Análisis *
                    </label>
                    <input type="date" id="fecha_analisis" name="fecha_analisis"
                           value="{{ old('fecha_analisis', date('Y-m-d')) }}"
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
                        Número de Orden *
                    </label>
                    <input type="text" id="numero_orden" name="numero_orden"
                           value="{{ old('numero_orden') }}"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                  @error('numero_orden') border-red-500 @enderror"
                           required>
                    @error('numero_orden')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Actividad --}}
            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    Actividad *
                </label>
                <textarea id="actividad" name="actividad" rows="4"
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
        Evidencia Fotográfica
    </label>
    <input type="file" id="evidencia_fotos" name="evidencia_fotos[]" multiple accept="image/*"
           class="block w-full text-sm text-gray-500
                  file:mr-4 file:py-2 file:px-4
                  file:rounded-lg file:border-0
                  file:text-sm file:font-semibold
                  file:bg-blue-50 file:text-blue-700
                  hover:file:bg-blue-100
                  @error('evidencia_fotos.*') border-red-500 @enderror">

    <p class="text-gray-400 text-xs mt-1">Puede seleccionar múltiples imágenes (máx. 2MB cada una)</p>

    <!-- Contenedor para los previews -->
    <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>

    @error('evidencia_fotos.*')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<script>
    const inputFotos = document.getElementById('evidencia_fotos');
    const previewFotos = document.getElementById('preview_fotos');

    inputFotos.addEventListener('change', function() {
        previewFotos.innerHTML = ''; // Limpiar previews anteriores
        const files = Array.from(this.files);

        files.forEach(file => {
            if(!file.type.startsWith('image/')) return; // Solo imágenes
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-lg border border-gray-200 shadow-sm';
                previewFotos.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    });
</script>


            {{-- Botones --}}
            <div class="flex flex-col md:flex-row justify-end gap-3">
                <a href="{{ route('analisis-componentes.select-linea') }}"
                   class="px-6 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>

                <button type="submit"
                        class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Guardar Análisis
                </button>
            </div>

        </form>
    </div>
</div>
@endsection