@extends('layouts.app')

@section('title', 'Seleccionar Pasteurizadora')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
    $todasLasPasteurizadoras = collect(\App\Models\AnalisisPasteurizadora::getPasteurizadoresConfiguracion())
        ->map(function ($config, $nombre) {
            return [
                'nombre' => $nombre,
                'modulos' => $config['modulos'],
                'tipo' => $config['tipo'],
                'reglillas' => \App\Models\AnalisisPasteurizadora::getCantidadReglillasPorLinea($nombre),
            ];
        })
        ->values();
    $lineasDisponibles = collect($lineas)->keyBy('nombre');
@endphp

<div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="mb-10 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <a href="{{ $analisisRoute('index') }}"
               class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-gray-600 transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 sm:w-auto group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver al Inicio</span>
            </a>

            <h1 class="min-w-0 break-words text-2xl font-bold text-gray-800 sm:text-3xl">
                Seleccionar Pasteurizadora
            </h1>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($todasLasPasteurizadoras as $config)
            @php
                $linea = $lineasDisponibles->get($config['nombre']);
            @endphp

            @if($linea)
                <a href="{{ $analisisRoute('create', $linea->id) }}"
                   class="group min-w-0">
                    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 flex h-24 w-24 items-center justify-center sm:h-28 sm:w-28 lg:h-32 lg:w-32">
                                <img src="{{ asset('images/icono_pas.png') }}"
                                     alt="Pasteurizadora"
                                     class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105"
                                     onerror="this.src='{{ asset('images/icono-pasteurizadora.png') }}'">
                            </div>

                            <h3 class="break-words text-lg font-semibold text-gray-800 mb-1">
                                {{ $linea->nombre }}
                            </h3>

                            <p class="break-words text-sm text-gray-500 mb-4 line-clamp-2">
                                {{ $config['modulos'] }} modulos | Tipo {{ $config['tipo'] }}
                                @if($config['reglillas'] > 0)
                                    | {{ $config['reglillas'] }} camas
                                @endif
                            </p>

                            <span class="create-action create-action--compact">
                                Seleccionar
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endif
        @endforeach
    </div>

    @if($lineasDisponibles->isEmpty())
        <div class="text-center py-16">
            <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-gray-100 mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.406 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>

            <h3 class="text-base font-semibold text-gray-800">
                No hay pasteurizadoras disponibles
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                No se encontraron lineas de pasteurizado activas.
            </p>

            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al Inicio
            </a>
        </div>
    @endif
</div>
@endsection
