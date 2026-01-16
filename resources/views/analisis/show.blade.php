<!-- resources/views/analisis/show.blade.php -->
@extends('layouts.app')

@section('title', 'Detalle del Análisis')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Encabezado -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Análisis #{{ $analisis->id }}</h1>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="text-lg font-medium text-gray-700">{{ $analisis->linea->nombre }}</span>
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        Orden: {{ $analisis->numero_orden }}
                    </span>
                    <span class="text-gray-500">{{ $analisis->fecha_analisis->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('analisis.edit', $analisis) }}" 
                   class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                    <i class="fas fa-edit mr-2"></i> Editar
                </a>
                <a href="{{ route('analisis.index') }}" 
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Información General -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Información del Análisis</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Línea:</span>
                    <span class="font-medium">{{ $analisis->linea->nombre }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Fecha:</span>
                    <span class="font-medium">{{ $analisis->fecha_analisis->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Orden:</span>
                    <span class="font-medium">{{ $analisis->numero_orden }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Horómetro:</span>
                    <span class="font-medium">{{ number_format($analisis->horometro) }} horas</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Registrado por:</span>
                    <span class="font-medium">{{ $analisis->usuario->name }}</span>
                </div>
            </div>
        </div>

        <!-- Elongación -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Elongación de Cadena</h3>
            @php
                $porcentaje = (($analisis->elongacion_promedio - 173) / 173) * 100;
                $estado = $porcentaje > 3 ? 'CRÍTICO' : ($porcentaje > 2 ? 'ATENCIÓN' : 'NORMAL');
                $color = $estado == 'CRÍTICO' ? 'red' : ($estado == 'ATENCIÓN' ? 'yellow' : 'green');
            @endphp
            <div class="text-center mb-4">
                <div class="text-4xl font-bold text-{{ $color }}-600 mb-2">
                    {{ number_format($analisis->elongacion_promedio, 2) }} mm
                </div>
                <div class="text-lg text-gray-600">
                    {{ number_format($porcentaje, 2) }}% de elongación
                </div>
                <div class="mt-2">
                    <span class="px-4 py-2 bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full text-sm font-medium">
                        {{ $estado }}
                    </span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t">
                <div class="text-sm text-gray-600">Límite máximo: 178.19 mm (3%)</div>
                <div class="mt-2">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Progreso:</span>
                        <span>{{ number_format(min($porcentaje, 100), 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-{{ $color }}-600 h-2 rounded-full" 
                             style="width: {{ min($porcentaje, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Juego de Rodaja -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Juego de Rodaja</h3>
            <div class="space-y-4">
                <div>
                    <div class="text-sm text-gray-600 mb-1">Medición Lado Bombas</div>
                    <div class="text-2xl font-bold text-gray-800">
                        {{ number_format($analisis->juego_rodaja, 2) }} mm
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 mb-1">Límite Recomendado</div>
                    <div class="text-lg text-gray-700">≤ 3.5 mm</div>
                </div>
                <div class="mt-4">
                    @if($analisis->juego_rodaja > 3.5)
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                            <div>
                                <div class="font-medium text-red-800">Ajuste requerido</div>
                                <div class="text-sm text-red-600">El juego de rodaja excede el límite recomendado</div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <div class="font-medium text-green-800">Dentro de límites</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Mediciones -->
    <div class="card p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Mediciones de Elongación</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if($analisis->mediciones->count() > 0)
                @foreach($analisis->mediciones as $medicion)
                <div>
                    <h4 class="font-medium text-gray-700 mb-3">
                        {{ $medicion->tipo == 'L_BOMBAS' ? 'Lado Bombas' : 'Lado Vapor' }}
                    </h4>
                    <div class="grid grid-cols-4 gap-2">
                        @for($i = 1; $i <= 8; $i++)
                        @php
                            $valor = $medicion->{"medicion_{$i}"};
                            $diferencia = $valor - 173;
                        @endphp
                        <div class="text-center">
                            <div class="text-xs text-gray-500 mb-1">M{{ $i }}</div>
                            <div class="font-medium {{ $diferencia > 5.19 ? 'text-red-600' : ($diferencia > 3 ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ number_format($valor, 1) }}
                            </div>
                            <div class="text-xs text-gray-400">
                                +{{ number_format($diferencia, 1) }}
                            </div>
                        </div>
                        @endfor
                    </div>
                    <div class="mt-4 pt-3 border-t">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Promedio:</span>
                            <span class="font-medium">{{ number_format($medicion->promedio, 2) }} mm</span>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
            <div class="col-span-2 text-center py-8 text-gray-500">
                <i class="fas fa-ruler-combined text-4xl mb-3"></i>
                <p>No hay mediciones registradas</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Componentes Revisados -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Componentes Revisados</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Componente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actividad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evidencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($analisis->componentes as $componente)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $componente->componente->nombre }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $componente->cantidad_revisada }}/{{ $componente->componente->cantidad_total }} revisados
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm">
                                <span class="font-medium">{{ $componente->cantidad_revisada }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $estadoColors = [
                                    'BUENO' => 'bg-green-100 text-green-800',
                                    'REGULAR' => 'bg-yellow-100 text-yellow-800',
                                    'DAÑADO' => 'bg-red-100 text-red-800',
                                    'REEMPLAZADO' => 'bg-blue-100 text-blue-800',
                                ];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $estadoColors[$componente->estado] }}">
                                {{ $componente->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-700">{{ $componente->actividad }}</div>
                            @if($componente->observaciones)
                            <div class="text-xs text-gray-500 mt-1">{{ $componente->observaciones }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($componente->evidencia_fotos && count($componente->evidencia_fotos) > 0)
                            <div class="flex space-x-2">
                                @foreach($componente->evidencia_fotos as $index => $foto)
                                <a href="{{ Storage::url($foto) }}" target="_blank" 
                                   class="relative group" title="Ver imagen {{ $index + 1 }}">
                                    <img src="{{ Storage::url($foto) }}" 
                                         class="w-12 h-12 object-cover rounded border">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all rounded"></div>
                                </a>
                                @endforeach
                            </div>
                            @else
                            <span class="text-gray-400 text-sm">Sin evidencia</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resumen de Estados -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        @php
            $estados = $analisis->componentes->groupBy('estado');
        @endphp
        @foreach(['BUENO', 'REGULAR', 'DAÑADO', 'REEMPLAZADO'] as $estado)
        <div class="card p-4">
            <div class="text-sm text-gray-500">{{ $estado }}</div>
            <div class="text-2xl font-bold mt-1">
                {{ $estados->has($estado) ? $estados[$estado]->sum('cantidad_revisada') : 0 }}
            </div>
            <div class="text-xs text-gray-400 mt-1">componentes</div>
        </div>
        @endforeach
    </div>
</div>
@endsection