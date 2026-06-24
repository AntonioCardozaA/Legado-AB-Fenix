@extends('layouts.app')

@section('title', 'Analisis')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analisis</h1>
            <p class="text-sm text-gray-500">Vista general de analisis registrados.</p>
        </div>
        <div class="create-actions">
            <a href="{{ route('analisis.nuevo') }}" class="create-action">Nuevo</a>
            <a href="{{ route('analisis.estadisticas') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Estadisticas</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('analisis.index') }}" class="grid gap-3 rounded bg-white p-4 shadow sm:grid-cols-2 lg:grid-cols-5">
        <select name="linea_id" class="rounded border-gray-300 text-sm">
            <option value="">Todas las lineas</option>
            @foreach($lineas as $linea)
                <option value="{{ $linea->id }}" @selected(request('linea_id') == $linea->id)>{{ $linea->nombre }}</option>
            @endforeach
        </select>

        <select name="componente_id" class="rounded border-gray-300 text-sm">
            <option value="">Todos los componentes</option>
            @foreach($componentes as $componente)
                <option value="{{ $componente->id }}" @selected(request('componente_id') == $componente->id)>{{ $componente->nombre }}</option>
            @endforeach
        </select>

        <select name="categoria_id" class="rounded border-gray-300 text-sm">
            <option value="">Todas las categorias</option>
            @foreach($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected(request('categoria_id') == $categoria->id)>{{ $categoria->nombre }}</option>
            @endforeach
        </select>

        <input type="month" name="fecha" value="{{ request('fecha') }}" class="rounded border-gray-300 text-sm">

        <div class="flex gap-2">
            <button class="flex-1 rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filtrar</button>
            <a href="{{ route('analisis.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="space-y-4">
        @forelse($analisisAgrupados as $lineaNombre => $items)
            <section class="overflow-hidden rounded bg-white shadow">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h2 class="font-semibold text-gray-900">{{ $lineaNombre }}</h2>
                    <span class="text-sm text-gray-500">{{ $items->count() }} registros</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Componente</th>
                                <th class="px-4 py-3">Reductor</th>
                                <th class="px-4 py-3">Orden</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($items as $item)
                                <tr>
                                    <td class="px-4 py-3">{{ optional($item->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}</td>
                                    <td class="px-4 py-3">{{ optional($item->componente)->nombre ?? 'Sin componente' }}</td>
                                    <td class="px-4 py-3">{{ $item->reductor ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $item->numero_orden ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('analisis.show', $item) }}" class="font-semibold text-blue-600 hover:text-blue-800">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded bg-white p-8 text-center text-gray-500 shadow">No hay analisis con los filtros seleccionados.</div>
        @endforelse
    </div>
</div>
@endsection
