@extends('layouts.app')

@section('title', 'Detalle de Registro #' . $elongacion->id)

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-file-alt text-blue-600"></i>
                    Detalle de Registro #{{ $elongacion->id }}
                </h1>
                <p class="text-gray-600 mt-1">
                    Análisis de elongación - Línea {{ $elongacion->linea }}
                </p>
            </div>
            <a href="{{ route('elongaciones.index', ['linea' => $elongacion->linea]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left"></i>
                Volver
            </a>
        </div>
    </div>

    {{-- Card Principal --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        
        {{-- Información General --}}
        <div class="border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-gray-200">
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-industry text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Línea</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->linea }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-washer text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Sección</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->seccion }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <i class="fas fa-tachometer-alt text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Hodómetro</p>
                            <p class="text-xl font-bold text-gray-800">{{ number_format($elongacion->hodometro, 0) }} h</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-calendar text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Registrado</p>
                            <p class="text-xl font-bold text-gray-800">{{ $elongacion->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contenido --}}
        <div class="p-6">
            {{-- Mediciones --}}
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-ruler-combined text-blue-600"></i>
                    Mediciones de Elongación
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Lado Bombas --}}
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-blue-50 px-4 py-3 border-b border-blue-100">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-blue-800 flex items-center gap-2">
                                    <i class="fas fa-tint"></i>
                                    LADO BOMBAS
                                </h3>
                                <span class="px-3 py-1 rounded-full text-sm font-medium 
                                    @if($elongacion->bombas_porcentaje >= 2.4) bg-red-100 text-red-800
                                    @elseif($elongacion->bombas_porcentaje >= 2.0) bg-amber-100 text-amber-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ number_format($elongacion->bombas_porcentaje, 2) }}%
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-4">
                                @for($i = 1; $i <= 10; $i++)
                                    @php
                                        $medicion = $elongacion->{"bombas_{$i}"};
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                                        <div class="font-medium p-2 bg-gray-50 rounded-lg border border-gray-200">
                                            {{ $medicion ? number_format($medicion, 1) . ' mm' : '-' }}
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm text-gray-600">Promedio</p>
                                        <p class="text-lg font-bold text-gray-800">
                                            {{ number_format($elongacion->bombas_promedio, 2) }} mm
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Paso inicial</p>
                                        <p class="text-lg font-medium text-gray-700">173.00 mm</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Lado Vapor --}}
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-green-50 px-4 py-3 border-b border-green-100">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-green-800 flex items-center gap-2">
                                    <i class="fas fa-wind"></i>
                                    LADO VAPOR
                                </h3>
                                <span class="px-3 py-1 rounded-full text-sm font-medium 
                                    @if($elongacion->vapor_porcentaje >= 2.4) bg-red-100 text-red-800
                                    @elseif($elongacion->vapor_porcentaje >= 2.0) bg-amber-100 text-amber-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ number_format($elongacion->vapor_porcentaje, 2) }}%
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-4">
                                @for($i = 1; $i <= 10; $i++)
                                    @php
                                        $medicion = $elongacion->{"vapor_{$i}"};
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                                        <div class="font-medium p-2 bg-gray-50 rounded-lg border border-gray-200">
                                            {{ $medicion ? number_format($medicion, 1) . ' mm' : '-' }}
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm text-gray-600">Promedio</p>
                                        <p class="text-lg font-bold text-gray-800">
                                            {{ number_format($elongacion->vapor_promedio, 2) }} mm
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Paso inicial</p>
                                        <p class="text-lg font-medium text-gray-700">173.00 mm</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Juego de Rodaja --}}
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-cogs text-gray-600"></i>
                    Juego de Rodaja - Holgura
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-blue-200 rounded-xl p-4 bg-blue-50/30">
                        <div class="text-sm text-gray-600 mb-2">LADO BOMBAS</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ $elongacion->juego_rodaja_bombas ? number_format($elongacion->juego_rodaja_bombas, 2) . ' mm' : '-' }}
                        </div>
                    </div>

                    <div class="border border-green-200 rounded-xl p-4 bg-green-50/30">
                        <div class="text-sm text-gray-600 mb-2">LADO VAPOR</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ $elongacion->juego_rodaja_vapor ? number_format($elongacion->juego_rodaja_vapor, 2) . ' mm' : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alertas --}}
            @if($elongacion->requiere_cambio)
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        <div>
                            <h4 class="font-medium text-red-800">¡ALERTA CRÍTICA!</h4>
                            <p class="text-sm text-red-600">
                                La elongación supera el 2.4%. Se recomienda cambio de cadena.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($elongacion->bombas_porcentaje >= 2.0 || $elongacion->vapor_porcentaje >= 2.0)
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
                        <div>
                            <h4 class="font-medium text-amber-800">¡ATENCIÓN!</h4>
                            <p class="text-sm text-amber-600">
                                La elongación se acerca al límite de 2.4%. Monitorear frecuentemente.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pie de página --}}
        <div class="border-t border-gray-200 p-6 bg-gray-50">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-600">
                    Registro creado: {{ $elongacion->created_at->format('d/m/Y H:i:s') }}
                    @if($elongacion->created_at != $elongacion->updated_at)
                        <br>Última actualización: {{ $elongacion->updated_at->format('d/m/Y H:i:s') }}
                    @endif
                </div>
                
                <div class="flex items-center gap-3">
                    <a href="{{ route('elongaciones.create') }}" 
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus-circle"></i>
                        Nuevo Registro
                    </a>
                    
                    <form action="{{ route('elongaciones.destroy', $elongacion) }}" 
                          method="POST" 
                          onsubmit="return confirm('¿Eliminar este registro permanentemente?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition border border-red-200">
                            <i class="fas fa-trash"></i>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection