@extends('layouts.app')

@section('title', 'Analisis 52-12-4')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Analisis 52-12-4</h1>
        <p class="text-sm text-gray-500">Registros historicos del componente seleccionado.</p>
    </div>

    <div class="overflow-hidden rounded bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Linea</th>
                        <th class="px-4 py-3">Componente</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($analisis as $item)
                        <tr>
                            <td class="px-4 py-3">{{ optional($item->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}</td>
                            <td class="px-4 py-3">{{ optional($item->linea)->nombre ?? 'Sin linea' }}</td>
                            <td class="px-4 py-3">{{ optional($item->componente)->nombre ?? 'Sin componente' }}</td>
                            <td class="px-4 py-3">{{ $item->estado ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay registros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
