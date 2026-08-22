@extends('layouts.app')

@section('title', 'Detalle Central Hidraulica')

@section('content')
@php
    $badge = $analisis->estado_badge;
    $evidencias = collect($analisis->evidencia_fotos ?? [])->filter()->values();
    $piezas = $analisis->componentes_revisados_lista;
    $estado = $analisis->estado;
    $estadoStyles = match (true) {
        \App\Models\AnalisisCentralHidraulica::esEstadoDanado($estado) => [
            'class' => 'bg-red-50 text-red-700 border-red-200',
            'icon' => 'fa-exclamation-circle',
            'header' => 'from-red-700 via-red-600 to-rose-500',
            'surface' => 'bg-red-50/70 border-red-100',
            'card' => 'border-red-100 bg-red-50/70',
            'iconBox' => 'bg-red-50 text-red-600',
            'activity' => 'border-red-200 bg-red-50/80',
            'buttonText' => 'text-red-700 hover:bg-red-50',
        ],
        \App\Models\AnalisisCentralHidraulica::esEstadoDesgaste($estado) => [
            'class' => 'bg-orange-50 text-orange-700 border-orange-200',
            'icon' => 'fa-triangle-exclamation',
            'header' => 'from-orange-700 via-orange-600 to-amber-500',
            'surface' => 'bg-orange-50/70 border-orange-100',
            'card' => 'border-orange-100 bg-orange-50/70',
            'iconBox' => 'bg-orange-50 text-orange-600',
            'activity' => 'border-orange-200 bg-orange-50/80',
            'buttonText' => 'text-orange-700 hover:bg-orange-50',
        ],
        \App\Models\AnalisisCentralHidraulica::esEstadoRequiereRevision($estado) => [
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            'icon' => 'fa-screwdriver-wrench',
            'header' => 'from-amber-700 via-amber-600 to-yellow-500',
            'surface' => 'bg-amber-50/70 border-amber-100',
            'card' => 'border-amber-100 bg-amber-50/70',
            'iconBox' => 'bg-amber-50 text-amber-600',
            'activity' => 'border-amber-200 bg-amber-50/80',
            'buttonText' => 'text-amber-700 hover:bg-amber-50',
        ],
        \App\Models\AnalisisCentralHidraulica::esEstadoCambiado($estado) => [
            'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            'icon' => 'fa-arrows-rotate',
            'header' => 'from-blue-700 via-blue-600 to-sky-500',
            'surface' => 'bg-blue-50/70 border-blue-100',
            'card' => 'border-blue-100 bg-blue-50/70',
            'iconBox' => 'bg-blue-50 text-blue-600',
            'activity' => 'border-blue-200 bg-blue-50/80',
            'buttonText' => 'text-blue-700 hover:bg-blue-50',
        ],
        default => [
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'icon' => 'fa-circle-check',
            'header' => 'from-emerald-700 via-emerald-600 to-teal-500',
            'surface' => 'bg-emerald-50/70 border-emerald-100',
            'card' => 'border-emerald-100 bg-emerald-50/70',
            'iconBox' => 'bg-emerald-50 text-emerald-600',
            'activity' => 'border-emerald-200 bg-emerald-50/80',
            'buttonText' => 'text-emerald-700 hover:bg-emerald-50',
        ],
    };
@endphp

<style>
    .central-detail-shell {
        width: 100%;
        max-width: 100%;
    }

    .central-detail-shell * {
        box-sizing: border-box;
        min-width: 0;
    }

    .central-detail-shell :is(h1, h2, h3, h4, p, span, a, button, th, td) {
        overflow-wrap: anywhere;
    }

    .central-detail-table {
        min-width: 720px;
    }

    @media (max-width: 640px) {
        .central-detail-shell {
            padding: 1rem 0.75rem;
        }

        .central-detail-shell section {
            border-radius: 0.875rem;
        }

        .central-detail-actions,
        .central-detail-actions a {
            width: 100%;
        }

        .central-detail-table {
            min-width: 680px;
        }
    }
</style>

<div class="central-detail-shell mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-2xl border {{ $estadoStyles['surface'] }} bg-white shadow-sm">
        <div class="bg-gradient-to-r {{ $estadoStyles['header'] }} px-6 py-7 text-white sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-blue-100">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">Analisis #{{ $analisis->id }}</span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">{{ $analisis->tipo_registro_label }}</span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">
                            <i class="far fa-calendar-alt"></i>
                            {{ optional($analisis->fecha_analisis)->format('d/m/Y') }}
                        </span>
                    </div>
                    <h1 class="break-words text-2xl font-bold leading-tight sm:text-3xl">{{ $analisis->linea->nombre ?? 'Central Hidraulica' }}</h1>
                    <p class="mt-2 max-w-3xl break-words text-sm text-blue-50">
                        {{ $analisis->piso_label }} | {{ $analisis->lado ? $analisis->lado_label : 'Piso completo' }} | {{ $analisis->componente_nombre }}
                    </p>
                </div>
                <div class="central-detail-actions flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap">
                    <a href="{{ $analisisRoute('edit', $analisis->id) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold {{ $estadoStyles['buttonText'] }} shadow-sm transition sm:w-auto">
                        <i class="fas fa-pen"></i>
                        Editar
                    </a>
                    <a href="{{ $analisisRoute('historial', ['linea_id' => $analisis->linea_id, 'componente_id' => $analisis->componente_id, 'piso' => $analisis->piso]) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold {{ $estadoStyles['buttonText'] }} shadow-sm transition sm:w-auto">
                        <i class="fas fa-history"></i>
                        Historial
                    </a>
                    <a href="{{ $analisisRoute('index', ['linea_id' => $analisis->linea_id]) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-white/40 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20 sm:w-auto">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 border-t px-6 py-5 sm:grid-cols-2 lg:grid-cols-4 sm:px-8 {{ $estadoStyles['surface'] }}">
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</p>
                <span class="mt-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-semibold {{ $badge['class'] }}">
                    <i class="fas {{ $badge['icon'] }}"></i>
                    {{ $analisis->estado }}
                </span>
            </div>
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cantidad</p>
                <p class="mt-2 text-lg font-bold text-gray-900">
                    {{ $analisis->cantidad_display }}
                </p>
            </div>
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Orden</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $analisis->numero_orden ?: 'Sin orden' }}</p>
            </div>
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registrado por</p>
                <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->usuario?->name ?? $analisis->responsable ?? 'Sin usuario' }}</p>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $estadoStyles['iconBox'] }}">
                    <i class="fas fa-info-circle"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900">Informacion general</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Piso</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->piso_label }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lado</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->lado ? $analisis->lado_label : 'No aplica' }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Componente / revision</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->componente_nombre }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cantidad base</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->configuracion?->cantidad_label ?? 'Sin configuracion' }}</p>
                </div>
            </div>

            @if(!empty($piezas))
                <div class="mt-5">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Piezas revisadas</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($piezas as $pieza)
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-blue-700">#{{ $pieza }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-5 rounded-xl border p-4 {{ $estadoStyles['activity'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Actividad</p>
                <p class="mt-2 whitespace-pre-wrap text-gray-800">{{ $analisis->actividad }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-camera"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900">Evidencias</h2>
            </div>
            @if($evidencias->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                    Sin evidencias registradas.
                </div>
            @else
                <div class="grid gap-3">
                    @foreach($evidencias as $index => $foto)
                        <a href="{{ asset('storage/' . ltrim($foto, '/')) }}" target="_blank" class="block overflow-hidden rounded-lg border border-gray-200">
                            <img src="{{ asset('storage/' . ltrim($foto, '/')) }}" alt="Evidencia {{ $index + 1 }}" class="h-44 w-full object-cover">
                            <p class="px-3 py-2 text-sm font-semibold text-gray-700">Evidencia {{ $index + 1 }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-lg font-bold text-gray-900">Historial del mismo componente</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="central-detail-table w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-gray-600">Fecha</th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600">Cantidad</th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600">Actividad</th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600">Ver</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($historial as $item)
                        @php($itemBadge = $item->estado_badge)
                        <tr>
                            <td class="px-4 py-3">{{ optional($item->fecha_analisis)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $item->cantidad_display }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $itemBadge['class'] }}">{{ $item->estado }}</span>
                            </td>
                            <td class="max-w-md px-4 py-3 text-gray-700">{{ \Illuminate\Support\Str::limit($item->actividad, 100) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ $analisisRoute('show', $item->id) }}" class="text-blue-700 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
