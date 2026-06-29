@extends('layouts.app')

@section('title', 'Detalle de linea')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $linea->nombre }}</h1>
            <p class="text-sm text-gray-500">Detalle de la linea.</p>
        </div>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <a href="{{ route('lineas.edit', $linea) }}" class="inline-flex items-center justify-center rounded bg-yellow-500 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-600">Editar</a>
            <a href="{{ route('lineas.index') }}" class="inline-flex items-center justify-center rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Volver</a>
        </div>
    </div>

    <div class="rounded bg-white p-5 shadow">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Nombre</dt>
                <dd class="text-gray-900">{{ $linea->nombre }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Tipo</dt>
                <dd class="text-gray-900">{{ $linea->tipo ?? 'lavadora' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Estado</dt>
                <dd class="text-gray-900">{{ $linea->activo ? 'Activa' : 'Inactiva' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Creada</dt>
                <dd class="text-gray-900">{{ optional($linea->created_at)->format('d/m/Y H:i') ?? 'N/A' }}</dd>
            </div>
        </dl>

        <div class="mt-5 border-t pt-5">
            <h2 class="font-semibold text-gray-900">Descripcion</h2>
            <p class="mt-2 text-sm text-gray-700">{{ $linea->descripcion ?? 'Sin descripcion' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('lineas.destroy', $linea) }}" class="rounded border border-red-200 bg-red-50 p-4">
        @csrf
        @method('DELETE')
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-red-700">Eliminar una linea puede afectar informacion relacionada.</p>
            <button class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" onclick="return confirm('Eliminar esta linea?')">Eliminar</button>
        </div>
    </form>
</div>
@endsection
