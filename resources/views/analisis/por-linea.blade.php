@extends('layouts.app')

@section('title', 'Analisis por linea')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $linea->nombre }}</h1>
            <p class="text-sm text-gray-500">Analisis asociados a esta linea.</p>
        </div>
        <a href="{{ route('analisis.index') }}" class="inline-flex w-full items-center justify-center rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:w-auto">Volver</a>
    </div>

    <div class="overflow-hidden rounded bg-white shadow">
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
                    @forelse($analisis as $item)
                        <tr>
                            <td class="px-4 py-3">{{ optional($item->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}</td>
                            <td class="px-4 py-3">{{ optional($item->componente)->nombre ?? 'Sin componente' }}</td>
                            <td class="px-4 py-3">{{ $item->reductor ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $item->numero_orden ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('analisis.show', $item) }}" class="font-semibold text-blue-600 hover:text-blue-800">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Sin analisis registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-4 py-3">{{ $analisis->links() }}</div>
    </div>
</div>
@endsection
