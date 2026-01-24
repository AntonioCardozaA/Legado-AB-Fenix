@extends('layouts.app')

@section('title', 'Líneas')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Líneas</h1>
        <a href="{{ route('lineas.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Nueva Línea</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full border border-gray-200 rounded">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">ID</th>
                <th class="px-4 py-2 text-left">Nombre</th>
                <th class="px-4 py-2 text-left">Descripción</th>
                <th class="px-4 py-2 text-left">Activo</th>
                <th class="px-4 py-2 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lineas as $linea)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $linea->id }}</td>
                    <td class="px-4 py-2">{{ $linea->nombre }}</td>
                    <td class="px-4 py-2">{{ $linea->descripcion ?? '-' }}</td>
                    <td class="px-4 py-2">
                        <form action="{{ route('lineas.toggle', $linea) }}" method="POST" class="inline-block">
                            @csrf
                            @method('PATCH')
                            @if($linea->activo)
                                <button type="submit" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">Desactivar</button>
                            @else
                                <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">Activar</button>
                            @endif
                        </form>
                    </td>
                    <td class="px-4 py-2 space-x-2">
                        <a href="{{ route('lineas.show', $linea) }}" class="text-blue-600 hover:underline">Ver</a>
                        <a href="{{ route('lineas.edit', $linea) }}" class="text-yellow-600 hover:underline">Editar</a>
                        <form action="{{ route('lineas.destroy', $linea) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar esta línea?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-2 text-center text-gray-500">No hay líneas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
