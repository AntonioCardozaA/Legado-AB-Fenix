@extends('layouts.app')

@section('title', 'Revisión General de Guías y Catarinas')

@section('content')
@php
    $oldCodigo = old('codigo_base');
    $fechaDefault = date('Y-m-d');
@endphp

<div class="max-w-5xl mx-auto py-10 px-4">
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('analisis-lavadora.index', ['linea_id' => $linea->id]) }}"
               class="text-gray-400 hover:text-blue-600 transition"
               aria-label="Volver">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                Revisión General de Guías y Catarinas
            </h1>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
            <div class="flex flex-col md:flex-row items-center gap-5">
                <div class="w-20 h-20 flex-shrink-0">
                    <img src="{{ asset('images/icono-maquina.png') }}"
                         alt="Icono de lavadora"
                         class="w-full h-full object-contain">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow text-sm">
                    <div class="text-center md:text-left">
                        <p class="text-gray-600 font-semibold">Lavadora</p>
                        <p class="text-gray-800 font-medium">{{ $linea->nombre }}</p>
                    </div>
                    <div class="text-center md:text-left">
                        <p class="text-gray-600 font-semibold">Ubicaciones totales</p>
                        <p class="text-gray-800 font-medium">{{ $totalUbicaciones }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @error('error')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $message }}
        </div>
    @enderror

    @error('codigo_base')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $message }}
        </div>
    @enderror

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        @foreach($componentesRevisionGeneral as $codigo => $nombre)
            @php
                $inputSuffix = strtolower(str_replace('_', '-', $codigo));
                $usarOld = $oldCodigo === $codigo;
                $actividad = $codigo === 'CATARINAS' ? $actividadCatarinas : $actividadGuias;
            @endphp

            <form method="POST"
                  action="{{ route('analisis-lavadora.revision-general.store', ['linea' => $linea->id]) }}"
                  class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 space-y-5">
                @csrf
                <input type="hidden" name="codigo_base" value="{{ $codigo }}">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-bold text-gray-800">{{ $nombre }}</p>
                        <p class="mt-1 text-xs font-semibold text-gray-500">{{ $actividad }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Buen estado
                        </span>
                        <span class="text-xs font-semibold text-gray-500">
                            {{ $totalesPorComponente[$codigo] ?? 0 }} ubicaciones
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="fecha_analisis_{{ $inputSuffix }}" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                            Fecha *
                        </label>
                        <input type="date"
                               id="fecha_analisis_{{ $inputSuffix }}"
                               name="fecha_analisis"
                               value="{{ $usarOld ? old('fecha_analisis', $fechaDefault) : $fechaDefault }}"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $usarOld && $errors->has('fecha_analisis') ? 'border-red-500' : '' }}"
                               required>
                        @if($usarOld)
                            @error('fecha_analisis')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label for="numero_orden_{{ $inputSuffix }}" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                            Orden *
                        </label>
                        <input type="text"
                               id="numero_orden_{{ $inputSuffix }}"
                               name="numero_orden"
                               value="{{ $usarOld ? old('numero_orden') : '' }}"
                               minlength="8"
                               maxlength="8"
                               inputmode="numeric"
                               pattern="[0-9]{8}"
                               autocomplete="off"
                               placeholder="Ej: 35221456"
                               title="Debe contener exactamente 8 dígitos numéricos"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $usarOld && $errors->has('numero_orden') ? 'border-red-500' : '' }}"
                               required>
                        @if($usarOld)
                            @error('numero_orden')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="create-actions pt-5 border-t border-gray-200">
                    <button type="submit" class="create-action w-full">
                        <i class="fas fa-save"></i>
                        Guardar {{ $nombre }}
                    </button>
                </div>
            </form>
        @endforeach
    </div>

    <div class="create-actions mt-6">
        <a href="{{ route('analisis-lavadora.index', ['linea_id' => $linea->id]) }}"
           class="create-action create-action--secondary">
            <i class="fas fa-times"></i>
            Cancelar
        </a>
    </div>
</div>
@endsection
