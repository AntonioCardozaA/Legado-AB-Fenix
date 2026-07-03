@extends('layouts.app')

@section('title', 'Analisis 52-12-4')

@section('content')
@php
    $registros = collect($analisis ?? [])->sortByDesc('fecha_analisis')->values();
    $total = $registros->count();
    $lavadoras = $registros->map(fn ($item) => optional($item->linea)->nombre)->filter()->unique()->count();
    $componentes = $registros->map(fn ($item) => optional($item->componente)->nombre)->filter()->unique()->count();
    $danos = $registros->pluck('estado')->filter()->countBy()->sortDesc();
    $componentesTop = $registros
        ->groupBy(fn ($item) => optional($item->componente)->nombre ?? 'Sin componente')
        ->map(fn ($items, $componente) => [
            'componente' => $componente,
            'total' => $items->count(),
            'estado' => $items->pluck('estado')->filter()->countBy()->sortDesc()->keys()->first(),
            'ultima' => optional($items->sortByDesc('fecha_analisis')->first()?->fecha_analisis)->format('d/m/Y'),
        ])
        ->sortByDesc('total')
        ->values();
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analisis 52-12-4</h1>
            <p class="text-sm text-gray-500">Resumen historico de danos por lavadora, componente y tipo de falla.</p>
        </div>
        <a href="{{ route('analisis-tendencia-mensual.lavadora.analisis-52-12-4') }}"
           class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
            Ver tendencia automatica
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Total danos</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ number_format($total) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Lavadoras</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ number_format($lavadoras) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Componentes</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ number_format($componentes) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Dano frecuente</p>
            <p class="mt-2 text-lg font-black text-gray-900">{{ $danos->keys()->first() ?? 'Sin dato' }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-black uppercase tracking-wide text-gray-800">Componentes con mayor incidencia</h2>
            <div class="mt-4 space-y-3">
                @forelse($componentesTop->take(8) as $componente)
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900">{{ $componente['componente'] }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Dano principal: {{ $componente['estado'] ?? 'Sin estado' }} · Ultima falla: {{ $componente['ultima'] ?? 'Sin fecha' }}
                                </p>
                            </div>
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">{{ number_format($componente['total']) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">No hay componentes afectados.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <h2 class="text-sm font-black uppercase tracking-wide text-gray-800">Registros historicos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Lavadora</th>
                            <th class="px-4 py-3">Componente</th>
                            <th class="px-4 py-3">Ubicacion</th>
                            <th class="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($registros as $item)
                            <tr>
                                <td class="px-4 py-3">{{ optional($item->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}</td>
                                <td class="px-4 py-3">{{ optional($item->linea)->nombre ?? 'Sin lavadora' }}</td>
                                <td class="px-4 py-3">{{ optional($item->componente)->nombre ?? 'Sin componente' }}</td>
                                <td class="px-4 py-3">{{ collect([$item->reductor ?? null, $item->lado ?? null])->filter()->implode(' / ') ?: 'Sin ubicacion' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-black text-orange-700">{{ $item->estado ?? 'N/A' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay registros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
