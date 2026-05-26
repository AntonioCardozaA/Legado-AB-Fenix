@extends('layouts.app')

@section('title', 'Seleccionar componente')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Componentes de {{ $linea->nombre }}</h1>
            <p class="text-sm text-gray-500">Selecciona el componente para continuar.</p>
        </div>
        <a href="{{ route('analisis.nuevo') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cambiar linea</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($componentes as $componente)
            <a href="{{ route('analisis.crear', [$linea, $componente]) }}" class="rounded bg-white p-5 shadow hover:shadow-md">
                <div class="text-lg font-semibold text-gray-900">{{ $componente->nombre }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $componente->codigo }}</div>
            </a>
        @empty
            <div class="rounded bg-white p-6 text-gray-500 shadow">No hay componentes activos.</div>
        @endforelse
    </div>
</div>
@endsection
