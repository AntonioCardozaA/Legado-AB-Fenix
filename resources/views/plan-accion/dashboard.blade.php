@extends('layouts.app')

@section('title', 'Dashboard plan de accion')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Plan de accion</h1>
            <p class="text-sm text-gray-500">Resumen general de actividades y alertas.</p>
        </div>
        <a href="{{ route('plan-accion.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Ver planes</a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded bg-white p-5 shadow">
            <div class="text-sm text-gray-500">Total actividades</div>
            <div class="mt-1 text-3xl font-bold text-gray-900">{{ $estadisticas['total_actividades'] ?? 0 }}</div>
        </div>
        <div class="rounded bg-white p-5 shadow">
            <div class="text-sm text-gray-500">Proximas 7 dias</div>
            <div class="mt-1 text-3xl font-bold text-yellow-600">{{ $estadisticas['proximas_7_dias'] ?? 0 }}</div>
        </div>
        <div class="rounded bg-white p-5 shadow">
            <div class="text-sm text-gray-500">Proximas 30 dias</div>
            <div class="mt-1 text-3xl font-bold text-blue-600">{{ $estadisticas['proximas_30_dias'] ?? 0 }}</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Alertas</h2>
            <div class="space-y-3">
                @forelse($alertas as $alerta)
                    <div class="rounded border border-gray-200 p-3">
                        <div class="flex justify-between gap-3">
                            <div class="font-semibold text-gray-900">{{ $alerta['linea'] ?? 'Sin linea' }}</div>
                            <div class="text-sm text-gray-500">{{ $alerta['fecha'] ?? '' }}</div>
                        </div>
                        <div class="mt-1 text-sm text-gray-700">{{ $alerta['actividad'] ?? '' }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No hay alertas proximas.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Actividades proximas</h2>
            <div class="space-y-3">
                @forelse($actividadesProximas as $plan)
                    <div class="rounded border border-gray-200 p-3">
                        <div class="font-semibold text-gray-900">{{ optional($plan->linea)->nombre ?? 'Sin linea' }}</div>
                        <div class="mt-1 text-sm text-gray-700">{{ $plan->actividad }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No hay actividades proximas.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded bg-white p-5 shadow">
        <h2 class="mb-4 font-semibold text-gray-900">Actividades por linea</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($actividadesPorLinea as $item)
                <a href="{{ route('plan-accion.por-lavadora', $item->linea_id) }}" class="rounded border border-gray-200 p-4 hover:bg-gray-50">
                    <div class="text-sm text-gray-500">{{ optional($item->linea)->nombre ?? 'Sin linea' }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $item->total }}</div>
                </a>
            @empty
                <div class="text-sm text-gray-500">No hay actividades registradas.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
