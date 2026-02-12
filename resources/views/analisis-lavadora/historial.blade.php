@extends('layouts.app')

@section('title', 'Historial de Análisis')

@section('content')

<div class="max-w-5xl mx-auto px-4 py-6">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-history text-blue-600 mr-2"></i>
            Historial de Registros
        </h1>

        <a href="{{ route('analisis-lavadora.index') }}"
           class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm">
            ← Volver
        </a>
    </div>

    @if($analisis->count() > 0)

        <div class="relative border-l-4 border-blue-500 pl-6">

            @foreach($analisis as $item)

                @php
                    $estado = $item->estado ?? 'Buen estado';

                    if (str_contains($estado, 'Dañado - Cambiado')) {
                        $color = 'bg-blue-100 text-blue-800';
                    } elseif (str_contains($estado, 'Dañado')) {
                        $color = 'bg-red-100 text-red-800';
                    } elseif (str_contains($estado, 'Desgaste')) {
                        $color = 'bg-yellow-100 text-yellow-800';
                    } else {
                        $color = 'bg-green-100 text-green-800';
                    }
                @endphp

                <div class="mb-8 relative">

                    <div class="absolute -left-3 top-2 w-6 h-6 bg-blue-600 rounded-full border-4 border-white shadow"></div>

                    <div class="bg-white shadow rounded-lg p-5 border border-gray-200">

                        <div class="flex justify-between items-center mb-3">
                            <div>
                                <div class="text-sm text-gray-500">
                                    {{ $item->fecha_analisis?->format('d/m/Y') }}
                                </div>
                                <div class="font-bold text-lg">
                                    Orden #{{ $item->numero_orden }}
                                </div>
                            </div>

                            <span class="px-3 py-1 rounded text-xs font-medium {{ $color }}">
                                {{ $estado }}
                            </span>
                        </div>

                        <div class="text-sm text-gray-600 mb-3">
                            <strong>Lavadora:</strong> {{ $item->linea->nombre ?? '' }} <br>
                            <strong>Componente:</strong> {{ $item->componente->nombre ?? '' }} <br>
                            <strong>Reductor:</strong> {{ $item->reductor }}
                        </div>

                        <div class="bg-gray-50 p-3 rounded text-sm text-gray-700">
                            {{ $item->actividad }}
                        </div>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('analisis-lavadora.edit', $item->id) }}"
                               class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-xs hover:bg-yellow-200">
                                <i class="fas fa-edit"></i> Editar
                            </a>

                            <a href="{{ route('analisis-lavadora.create-quick', [
                                'linea_id' => $item->linea_id,
                                'componente_codigo' => $item->componente->codigo,
                                'reductor' => $item->reductor
                            ]) }}"
                               class="px-3 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-10 text-gray-500">
            <i class="fas fa-folder-open text-4xl mb-4"></i>
            <p>No hay registros para mostrar.</p>
        </div>

    @endif

</div>

@endsection
