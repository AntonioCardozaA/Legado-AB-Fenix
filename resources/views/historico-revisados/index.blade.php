@extends('layouts.app')

@section('title', 'Historico revisados')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Historico revisados</h1>
        <p class="text-sm text-gray-500">Resumen de avance por linea y componente.</p>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 rounded bg-white p-4 shadow">
        <select name="tipo" class="rounded border-gray-300 text-sm">
            <option value="lavadora" @selected(($tipoSeleccionado ?? 'lavadora') === 'lavadora')>Lavadora</option>
            <option value="pasteurizadora" @selected(($tipoSeleccionado ?? '') === 'pasteurizadora')>Pasteurizadora</option>
        </select>
        <select name="linea_id" class="rounded border-gray-300 text-sm">
            @foreach(($lineas ?? collect()) as $linea)
                <option value="{{ $linea->id }}" @selected(optional($lineaSeleccionada ?? null)->id === $linea->id)>{{ $linea->nombre }}</option>
            @endforeach
        </select>
        <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filtrar</button>
    </form>

    <div class="rounded bg-white p-5 shadow">
        <div class="text-sm text-gray-500">Avance general</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $resumen['porcentaje_general'] ?? 0 }}%</div>
        <div class="mt-1 text-sm text-gray-500">{{ $resumen['revisado_general'] ?? 0 }} de {{ $resumen['total_general'] ?? 0 }} revisados</div>
    </div>

    <div class="overflow-hidden rounded bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Componente</th>
                        <th class="px-4 py-3">Revisados</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Porcentaje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($estadisticas ?? []) as $item)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $item['nombre'] }}</td>
                            <td class="px-4 py-3">{{ $item['cantidad_revisada'] }}</td>
                            <td class="px-4 py-3">{{ $item['cantidad_total'] }}</td>
                            <td class="px-4 py-3">{{ $item['porcentaje'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay estadisticas disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
