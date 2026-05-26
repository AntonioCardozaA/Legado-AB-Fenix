@extends('layouts.app')

@section('title', 'Plan por linea')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Plan de accion {{ $linea->nombre }}</h1>
            <p class="text-sm text-gray-500">{{ $estadisticas['total'] ?? 0 }} actividades registradas.</p>
        </div>
        <a href="{{ route('plan-accion.index', ['linea_id' => $linea->id]) }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Volver</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded bg-white p-5 shadow">
            <div class="text-sm text-gray-500">Total</div>
            <div class="mt-1 text-3xl font-bold text-gray-900">{{ $estadisticas['total'] ?? 0 }}</div>
        </div>
        <div class="rounded bg-white p-5 shadow">
            <div class="text-sm text-gray-500">Proximas 7 dias</div>
            <div class="mt-1 text-3xl font-bold text-yellow-600">{{ $estadisticas['proximas'] ?? 0 }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Actividad</th>
                        <th class="px-4 py-3">PCM1</th>
                        <th class="px-4 py-3">PCM2</th>
                        <th class="px-4 py-3">PCM3</th>
                        <th class="px-4 py-3">PCM4</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($planes as $plan)
                        <tr>
                            <td class="px-4 py-3">{{ $plan->actividad }}</td>
                            <td class="px-4 py-3">{{ optional($plan->fecha_pcm1)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ optional($plan->fecha_pcm2)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ optional($plan->fecha_pcm3)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ optional($plan->fecha_pcm4)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $plan->completado ? 'Completado' : 'Pendiente' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay actividades para esta linea.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-4 py-3">{{ $planes->links() }}</div>
    </div>
</div>
@endsection
