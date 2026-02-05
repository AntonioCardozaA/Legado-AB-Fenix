@extends('layouts.app')

@section('title', 'Editar Análisis Rápido')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">

        {{-- Encabezado --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('analisis-componentes.index') }}"
                   class="text-gray-400 hover:text-amber-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    Editar Análisis Rápido
                </h1>
            </div>

            {{-- Contexto --}}
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600 font-semibold">Lavadora</p>
                        <p class="text-gray-800">
                            {{ $analisisComponente->linea->nombre ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Componente</p>
                        <p class="text-gray-800">
                            {{ $analisisComponente->componente->nombre ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Reductor</p>
                        <p class="text-gray-800">
                            {{ $analisisComponente->reductor }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulario --}}
        <form action="{{ route('analisis-componentes.update', $analisisComponente->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6">

            @csrf
            @method('PUT')

            {{-- 🔴 Forzar redirección al index --}}
            <input type="hidden" name="redirect_to"
                   value="{{ route('analisis-componentes.index') }}">

            <input type="hidden" name="componente_id"
                   value="{{ $analisisComponente->componente_id }}">
            <input type="hidden" name="reductor"
                   value="{{ $analisisComponente->reductor }}">

            {{-- Fecha --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Fecha del Análisis *
                </label>
                <input type="date"
                       name="fecha_analisis"
                       value="{{ old('fecha_analisis', optional($analisisComponente->fecha_analisis)->format('Y-m-d')) }}"
                       required
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 @error('fecha_analisis') border-red-500 @enderror">
                @error('fecha_analisis')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Número de orden --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Número de Orden *
                </label>
                <input type="text"
                       name="numero_orden"
                       value="{{ old('numero_orden', $analisisComponente->numero_orden) }}"
                       required
                       maxlength="8"
                       inputmode="numeric"
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 @error('numero_orden') border-red-500 @enderror">
                @error('numero_orden')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Estado --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Estado *
                </label>
                <select name="estado"
                        required
                        class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 @error('estado') border-red-500 @enderror">
                    <option value="">Seleccionar...</option>
                    @foreach([
                        'Buen estado',
                        'Desgaste moderado',
                        'Desgaste severo',
                        'Dañado - Requiere cambio',
                        'Dañado - Cambiado'
                    ] as $estado)
                        <option value="{{ $estado }}"
                            {{ old('estado', $analisisComponente->estado) === $estado ? 'selected' : '' }}>
                            {{ $estado }}
                        </option>
                    @endforeach
                </select>
                @error('estado')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actividad --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Actividad / Observaciones *
                </label>
                <textarea name="actividad"
                          rows="4"
                          required
                          class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 @error('actividad') border-red-500 @enderror">{{ old('actividad', $analisisComponente->actividad) }}</textarea>
                @error('actividad')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Evidencias existentes --}}
            @php
                $fotos = json_decode($analisisComponente->evidencia_fotos ?? '[]', true);
            @endphp

            @if(!empty($fotos))
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        Evidencias actuales
                    </p>
                    <div class="flex flex-wrap gap-4">
                        @foreach($fotos as $index => $foto)
                            <div class="relative">
                                <img src="{{ asset('storage/'.$foto) }}"
                                     class="w-32 h-32 object-cover rounded-lg border">

                                <label class="absolute top-2 right-2 bg-white rounded-full p-1 shadow cursor-pointer">
                                    <input type="checkbox"
                                           name="eliminar_fotos[]"
                                           value="{{ $index }}"
                                           class="hidden">
                                    <i class="fas fa-trash text-red-600"></i>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Marque las imágenes que desea eliminar
                    </p>
                </div>
            @endif

            {{-- Nuevas evidencias --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Agregar nuevas evidencias
                </label>
                <input type="file"
                       name="evidencia_fotos[]"
                       multiple
                       accept="image/*"
                       class="w-full rounded-lg border-gray-300">
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('analisis-componentes.index') }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 transition shadow-md">
                    Cancelar
                </a>

                <button type="submit"
                        class="flex-1 bg-amber-600 text-white rounded-lg px-5 py-3 hover:bg-amber-700 transition shadow-md">
                    Actualizar Análisis
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
