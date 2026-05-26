@extends('layouts.app')

@section('title', 'Seleccionar linea')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Seleccionar linea</h1>
        <p class="text-sm text-gray-500">Elige la linea para iniciar un analisis.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($lineas as $linea)
            <a href="{{ route('analisis.seleccionar-componente', $linea) }}" class="rounded bg-white p-5 shadow hover:shadow-md">
                <div class="text-lg font-semibold text-gray-900">{{ $linea->nombre }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $linea->descripcion ?? 'Sin descripcion' }}</div>
            </a>
        @empty
            <div class="rounded bg-white p-6 text-gray-500 shadow">No hay lineas activas.</div>
        @endforelse
    </div>
</div>
@endsection
