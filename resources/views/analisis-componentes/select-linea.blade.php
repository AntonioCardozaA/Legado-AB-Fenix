@extends('layouts.app')

@section('title', 'Seleccionar Lavadora')

@section('content')
<div class="max-w-7xl mx-auto px-4">

    {{-- Encabezado --}}
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-gray-800">
            Seleccionar Lavadora
        </h1>
        <p class="text-gray-600 mt-1">
            Paso 1 · Seleccione la lavadora para realizar el análisis
        </p>
    </div>

    {{-- Grid de lavadoras --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($lineas as $linea)
            <a href="{{ route('analisis-componentes.create', $linea->id) }}"
               class="group">

                <div class="bg-white rounded-xl border border-gray-100 p-6
                            shadow-sm hover:shadow-xl transition-all duration-300
                            hover:-translate-y-1">

                    <div class="flex flex-col items-center text-center">

                        {{-- Icono --}}
                        <div class="w-16 h-16 mb-4 flex items-center justify-center
                                    bg-blue-50 rounded-full
                                    group-hover:bg-blue-100 transition-colors">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>

                        {{-- Nombre --}}
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">
                            {{ $linea->nombre ?? 'Lavadora ' . $linea->id }}
                        </h3>

                        {{-- Descripción --}}
                        <p class="text-sm text-gray-500 mb-4 line-clamp-2">
                            {{ $linea->descripcion ?? 'Equipo disponible para análisis' }}
                        </p>

                        {{-- Acción --}}
                        <span class="inline-flex items-center gap-1 px-4 py-1.5
                                     rounded-full text-sm font-medium
                                     bg-blue-50 text-blue-700
                                     group-hover:bg-blue-600 group-hover:text-white
                                     transition-colors">
                            Seleccionar
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>

                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Estado vacío --}}
    @if($lineas->isEmpty())
        <div class="text-center py-16">
            <div class="mx-auto w-16 h-16 flex items-center justify-center
                        rounded-full bg-gray-100 mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.406 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>

            <h3 class="text-base font-semibold text-gray-800">
                No hay lavadoras disponibles
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                No se encontraron líneas de lavado activas.
            </p>
        </div>
    @endif

</div>
@endsection
