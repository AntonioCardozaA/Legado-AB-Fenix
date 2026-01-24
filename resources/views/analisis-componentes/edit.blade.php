@extends('layouts.app')

@section('title', 'Editar Análisis Rápido')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg p-8">

        {{-- Encabezado --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('analisis-componentes.index', request()->query()) }}"
                   class="text-gray-400 hover:text-amber-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    Editar Análisis Rápido
                </h1>
            </div>

            {{-- Contexto --}}
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600 font-semibold">Lavadora</p>
                        <p class="text-gray-800">{{ $analisisComponente->linea->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Componente</p>
                        <p class="text-gray-800">{{ $analisisComponente->componente->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Reductor</p>
                        <p class="text-gray-800">{{ $analisisComponente->reductor }}</p>
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

            <input type="hidden" name="redirect_to" value="{{ url()->previous() }}">

            {{-- Fecha --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="far fa-calendar-alt text-amber-600 mr-1"></i>
                    Fecha del Análisis *
                </label>
                <input type="date"
                       name="fecha_analisis"
                       value="{{ old('fecha_analisis', optional($analisisComponente->fecha_analisis)->format('Y-m-d')) }}"
                       required
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm">
            </div>

            {{-- Número de orden --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-hashtag text-amber-600 mr-1"></i>
                    Número de Orden *
                </label>
                <input type="text"
                       name="numero_orden"
                       value="{{ old('numero_orden', $analisisComponente->numero_orden) }}"
                       required
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm">
            </div>

            {{-- Actividad / Estado --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-tasks text-amber-600 mr-1"></i>
                    Estado del Componente *
                </label>
                <select name="actividad"
                        required
                        class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm">
                    <option value="">Seleccionar...</option>
                    @foreach([
                        'Buen estado',
                        'Desgaste moderado',
                        'Desgaste severo',
                        'Dañado - Requiere cambio',
                        'Dañado - Cambiado'
                    ] as $estado)
                        <option value="{{ $estado }}"
                            {{ old('actividad', $analisisComponente->actividad) === $estado ? 'selected' : '' }}>
                            {{ $estado }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Actividad (si existe la columna) --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sticky-note text-amber-600 mr-1"></i>
                    Actividad
                </label>
                <textarea name="observaciones"
                          rows="3"
                          class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm">{{ old('observaciones', $analisisComponente->observaciones ?? '') }}</textarea>
            </div>

            {{-- Evidencias existentes --}}
            @if(!empty($analisisComponente->evidencia_fotos))
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">Evidencias actuales</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($analisisComponente->evidencia_fotos as $index => $foto)
                            <div class="relative">
                                <img src="{{ asset('storage/'.$foto) }}"
                                     class="w-24 h-24 object-cover rounded-lg border shadow">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Nuevas evidencias --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-camera text-amber-600 mr-1"></i>
                    Agregar nuevas evidencias
                </label>
                <input type="file"
                       name="evidencia_fotos[]"
                       multiple
                       accept="image/*"
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 shadow-sm">
            </div>

            {{-- Botones --}}
            <div class="flex gap-4 pt-6 border-t">
                <a href="{{ route('analisis-componentes.index', request()->query()) }}"
                   class="flex-1 bg-gray-200 text-gray-700 rounded-lg px-5 py-3 text-center hover:bg-gray-300 shadow">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg px-5 py-3 shadow hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Análisis
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
