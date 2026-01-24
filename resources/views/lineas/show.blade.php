@extends('layouts.app')

@section('title', 'Detalle de Línea')

@section('content')
<div class="max-w-2xl mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">Detalle de Línea: {{ $linea->nombre }}</h1>

    <div class="mb-4">
        <strong>Nombre:</strong> {{ $linea->nombre }}
    </div>

    <div class="mb-4">
        <strong>Descripción:</strong> {{ $linea->descripcion ?? '-' }}
    </div>

    <div class="mb-4">
        <strong>Activo:</strong> 
        @if($linea->activo)
            <span class="text-green-600 font-semibold">Sí</span>
        @else
            <span class="text-red-600 font-semibold">No</span>
        @endif
    </div>

    <div class="flex space-x-2">
        <a href="{{ route('lineas.edit', $linea) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Editar</a>
        <a href="{{ route('lineas.index') }}" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Volver</a>
    </div>
</div>
@endsection
