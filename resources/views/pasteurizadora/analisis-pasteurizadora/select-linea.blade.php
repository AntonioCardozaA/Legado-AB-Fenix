@extends('layouts.app')

@section('title', 'Seleccionar Pasteurizadora')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
@endphp
<style>
    .pasteur-select {
        --primary-blue: #2563eb;
        --border: #e5e7eb;
        --soft-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    .pasteur-select-header {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--soft-shadow);
        padding: 20px 24px;
    }

    .pasteur-select-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--soft-shadow);
        padding: 18px;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .pasteur-select-card:hover {
        border-color: var(--primary-blue);
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, .08);
        transform: translateY(-2px);
    }

    .pasteur-select-icon {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        height: 58px;
        padding: 8px;
        width: 58px;
    }
</style>

<div class="pasteur-select max-w-7xl mx-auto px-4">

    {{-- Encabezado con boton de volver --}}
    <div class="pasteur-select-header mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ $analisisRoute('index') }}"
               class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300
                      group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver al Inicio</span>
            </a>

            <h1 class="text-3xl font-bold text-gray-800">
                Seleccionar Pasteurizadora
            </h1>
        </div>
    </div>

    @php
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

    {{-- Grid con el mismo estilo de lavadora --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($todasLasPasteurizadoras as $config)
            @php
                $linea = $lineasDisponibles->get($config['nombre']);
            @endphp

            @if($linea)
                <a href="{{ $analisisRoute('create', $linea->id) }}"
                   class="group">
                    <div class="pasteur-select-card">
                        <div class="flex flex-col items-center text-center">
                            <div class="pasteur-select-icon mb-4 flex items-center justify-center">
                                <img src="{{ asset('images/icono_pas.png') }}"
                                     alt="Icono de Pasteurizadora"
                                     class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300">
                            </div>

                            <h3 class="text-lg font-semibold text-gray-800 mb-1">
                                {{ $linea->nombre }}
                            </h3>

                            <p class="text-sm text-gray-500 mb-4 line-clamp-2">
                                {{ $config['modulos'] }} modulos | Tipo {{ $config['tipo'] }}
                                @if($config['reglillas'] > 0)
                                    | {{ $config['reglillas'] }} camas
                                @endif
                            </p>

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
            @endif
        @endforeach
    </div>

    {{-- Estado vacio --}}
    @if($lineasDisponibles->isEmpty())
        <div class="text-center py-16">
            <div class="mx-auto w-16 h-16 flex items-center justify-center
                        rounded-full bg-gray-100 mb-4">
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
               class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700
                      text-white font-medium rounded-lg transition-all duration-300
                      hover:-translate-y-1 hover:shadow-lg">
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
