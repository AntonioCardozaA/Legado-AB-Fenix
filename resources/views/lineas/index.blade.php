@extends('layouts.app')

@section('title', 'Lineas')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lineas</h1>
            <p class="text-sm text-gray-500">Administracion de lineas del sistema.</p>
        </div>
        <a href="{{ route('lineas.create') }}" class="create-action">Nueva linea</a>
    </div>

    @if(session('success'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Descripcion</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lineas as $linea)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $linea->nombre }}</td>
                            <td class="px-4 py-3">{{ $linea->tipo ?? 'lavadora' }}</td>
                            <td class="px-4 py-3">{{ $linea->descripcion ?? 'Sin descripcion' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $linea->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $linea->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('lineas.show', $linea) }}" class="font-semibold text-blue-600 hover:text-blue-800">Ver</a>
                                    <a href="{{ route('lineas.edit', $linea) }}" class="font-semibold text-yellow-600 hover:text-yellow-800">Editar</a>
                                    <form method="POST" action="{{ route('lineas.toggle', $linea) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="font-semibold text-gray-600 hover:text-gray-900">{{ $linea->activo ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay lineas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
