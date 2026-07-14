@extends('layouts.app')

@section('title', 'Plan de Accion | Etiquetadora')

@section('content')
@include('etiquetadora.partials.styles')

@php
    $planesCollection = method_exists($planes, 'getCollection') ? $planes->getCollection() : collect($planes);
    $totalActividades = method_exists($planes, 'total') ? $planes->total() : $planesCollection->count();
    $totalVisible = $planesCollection->count();
    $actividadesCompletadas = $planesCollection->where('completado', true)->count();
    $actividadesPendientes = $planesCollection->where('completado', false)->count();
    $lineaSeleccionada = filled($lineaId) ? (int) $lineaId : null;
    $lineasVisibles = $lineaSeleccionada
        ? $lineasTipo->where('id', $lineaSeleccionada)
        : $lineasTipo;
    $planesPorLinea = $planesCollection->groupBy('linea_id');

    $fechaMeta = function ($fecha): array {
        if (!$fecha) {
            return [
                'label' => 'Sin fecha',
                'class' => 'fecha-futura',
                'icon' => 'fa-calendar-xmark',
            ];
        }

        $fechaObj = $fecha instanceof \Carbon\CarbonInterface
            ? $fecha->copy()->startOfDay()
            : \Carbon\Carbon::parse($fecha)->startOfDay();

        $dias = now()->startOfDay()->diffInDays($fechaObj, false);

        return [
            'label' => $fechaObj->format('d/m/Y'),
            'class' => $dias < 0 ? 'fecha-vencida' : ($dias <= 7 ? 'fecha-proxima' : ($dias <= 30 ? 'fecha-cercana' : 'fecha-futura')),
            'icon' => $dias < 0 ? 'fa-triangle-exclamation' : 'fa-calendar-check',
        ];
    };

    $estadoMeta = function ($plan): array {
        if ((bool) ($plan->completado ?? false)) {
            return [
                'label' => 'Completada',
                'class' => 'is-complete',
                'icon' => 'fa-circle-check',
            ];
        }

        return match ($plan->estado ?? 'pendiente') {
            'atrasada' => [
                'label' => 'Atrasada',
                'class' => 'is-danger',
                'icon' => 'fa-triangle-exclamation',
            ],
            'en_proceso' => [
                'label' => 'En proceso',
                'class' => 'is-process',
                'icon' => 'fa-arrows-rotate',
            ],
            default => [
                'label' => ucfirst($plan->estado ?? 'Pendiente'),
                'class' => 'is-pending',
                'icon' => 'fa-clock',
            ],
        };
    };
@endphp

<div class="etq-page">
    <div class="etq-container" style="max-width: 88rem;">
        <header class="etq-header">
            <div class="etq-header-main">
                <div class="etq-machine-icon">
                    <img src="{{ asset('images/icono-maquina.png') }}" alt="Icono de maquinaria">
                </div>
                <div class="etq-accent-bar"></div>
                <div>
                    <a href="{{ route('etiquetadora.dashboard') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-blue-700">
                        <i class="fas fa-arrow-left"></i>
                        Volver a Etiquetadora
                    </a>
                    <h1 class="etq-title">PLAN DE ACCION</h1>
                    <p class="etq-subtitle">Actividades preventivas y correctivas para Etiquetadora</p>
                </div>
            </div>

            <a href="{{ route('plan-accion.create', ['tipo' => 'etiquetadora', 'linea_id' => $lineaSeleccionada]) }}" class="create-action">
                <i class="fas fa-plus-circle"></i>
                Nueva actividad
            </a>
        </header>

        @if(session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="etq-stat-card">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="etq-stat-label">Total actividades</p>
                        <p class="etq-stat-value">{{ $totalActividades }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                        <i class="fas fa-list-check"></i>
                    </span>
                </div>
            </article>
            <article class="etq-stat-card">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="etq-stat-label">Visibles</p>
                        <p class="etq-stat-value text-blue-700">{{ $totalVisible }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700">
                        <i class="fas fa-filter"></i>
                    </span>
                </div>
            </article>
            <article class="etq-stat-card">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="etq-stat-label">Pendientes</p>
                        <p class="etq-stat-value text-amber-700">{{ $actividadesPendientes }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <i class="fas fa-clock"></i>
                    </span>
                </div>
            </article>
            <article class="etq-stat-card">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="etq-stat-label">Completadas</p>
                        <p class="etq-stat-value text-emerald-700">{{ $actividadesCompletadas }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <i class="fas fa-circle-check"></i>
                    </span>
                </div>
            </article>
        </section>

        <section class="etq-section-card mb-6">
            <div class="bg-white p-5">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-black uppercase tracking-wide text-slate-800">
                    <i class="fas fa-route text-blue-600"></i>
                    Lineas de Etiquetadora
                </h2>
                <div class="etq-line-filter">
                    <a href="{{ route('plan-accion.index', ['tipo' => 'etiquetadora']) }}"
                       class="etq-line-filter-link {{ !$lineaSeleccionada ? 'active' : '' }}">
                        <i class="fas fa-layer-group"></i>
                        Todas
                    </a>
                    @foreach($lineasTipo as $linea)
                        <a href="{{ route('plan-accion.index', ['tipo' => 'etiquetadora', 'linea_id' => $linea->id]) }}"
                           class="etq-line-filter-link {{ $lineaSeleccionada === (int) $linea->id ? 'active' : '' }}">
                            @include('etiquetadora.partials.presentation-icons', ['linea' => $linea, 'size' => 'xs'])
                            {{ $linea->nombre }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="space-y-6">
            @forelse($lineasVisibles as $linea)
                @php
                    $planesLinea = $planesPorLinea->get($linea->id, collect());
                    $pendientesLinea = $planesLinea->where('completado', false)->count();
                    $completadasLinea = $planesLinea->where('completado', true)->count();
                @endphp

                <article class="etq-section-card">
                    <div class="etq-section-header">
                        <div class="etq-section-title">
                            <span class="flex min-h-14 min-w-20 items-center justify-center rounded-xl bg-white/10 p-2">
                                @include('etiquetadora.partials.presentation-icons', ['linea' => $linea, 'size' => 'sm'])
                            </span>
                            <div>
                                <h2 class="text-xl font-black leading-tight">{{ $linea->nombre }}</h2>
                                <p class="mt-1 text-sm font-medium text-white/70">Plan de accion de Etiquetadora</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                                {{ $planesLinea->count() }} actividades
                            </span>
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                                {{ $pendientesLinea }} pendientes
                            </span>
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">
                                {{ $completadasLinea }} completadas
                            </span>
                            <a href="{{ route('plan-accion.create', ['tipo' => 'etiquetadora', 'linea_id' => $linea->id]) }}"
                               class="create-action create-action--compact">
                                <i class="fas fa-plus"></i>
                                Agregar
                            </a>
                        </div>
                    </div>

                    @if($planesLinea->isNotEmpty())
                        <div class="etq-plan-table-wrap">
                            <table class="etq-plan-table">
                                <thead>
                                    <tr>
                                        <th style="width: 34%;">Actividad</th>
                                        <th style="width: 16%;">Responsable</th>
                                        <th style="width: 26%;">Fechas PCM</th>
                                        <th style="width: 12%;">Estado</th>
                                        <th style="width: 12%;" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($planesLinea as $plan)
                                        @php
                                            $estado = $estadoMeta($plan);
                                            $pcmFechas = collect(['1', '2', '3', '4'])->mapWithKeys(function ($n) use ($plan, $fechaMeta) {
                                                $campo = 'fecha_pcm' . $n;
                                                return ['PCM ' . $n => $fechaMeta($plan->{$campo})];
                                            });
                                        @endphp

                                        <tr>
                                            <td>
                                                <div class="space-y-2">
                                                    <p class="etq-plan-title">{{ $plan->actividad }}</p>
                                                    <div class="etq-trace-grid">
                                                        <span>
                                                            <i class="fas fa-user-plus mr-1 text-slate-400"></i>
                                                            <strong>Registrado por:</strong> {{ $plan->registradoPor->name ?? 'Sin registro' }}
                                                        </span>
                                                        @if($plan->ejecutadoPor)
                                                            <span>
                                                                <i class="fas fa-circle-check mr-1 text-emerald-500"></i>
                                                                <strong>Ejecutado por:</strong> {{ $plan->ejecutadoPor->name }}
                                                                @if($plan->fecha_ejecucion)
                                                                    el {{ $plan->fecha_ejecucion->format('d/m/Y H:i') }}
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="font-semibold text-slate-700">
                                                    {{ $plan->responsable->name ?? 'Usuario actual' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="etq-date-row">
                                                    @foreach($pcmFechas as $pcm => $meta)
                                                        <span class="etq-date-pill {{ $meta['class'] }}">
                                                            <i class="fas {{ $meta['icon'] }}"></i>
                                                            {{ $pcm }}: {{ $meta['label'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <span class="etq-status-badge {{ $estado['class'] }}">
                                                    <i class="fas {{ $estado['icon'] }}"></i>
                                                    {{ $estado['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex justify-center gap-2">
                                                    <a href="{{ route('plan-accion.edit', ['plan_accion' => $plan->id, 'tipo' => 'etiquetadora']) }}"
                                                       class="etq-icon-action edit"
                                                       title="Editar actividad">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('plan-accion.destroy', ['plan_accion' => $plan->id, 'tipo' => 'etiquetadora']) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="etq-icon-action delete"
                                                                title="Eliminar actividad"
                                                                onclick="return confirm('Eliminar esta actividad?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-5">
                            <div class="etq-empty py-10">
                                <i class="fas fa-clipboard-list mb-3 text-4xl text-blue-300"></i>
                                <h3 class="text-lg font-semibold text-gray-800">Sin actividades registradas</h3>
                                <p class="mt-1 text-sm text-gray-500">Esta linea aun no tiene actividades de plan de accion.</p>
                                <a href="{{ route('plan-accion.create', ['tipo' => 'etiquetadora', 'linea_id' => $linea->id]) }}"
                                   class="create-action mt-5">
                                    <i class="fas fa-plus-circle"></i>
                                    Nueva actividad
                                </a>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="etq-empty">
                    <i class="fas fa-circle-info mb-3 text-4xl text-gray-300"></i>
                    <h3 class="text-lg font-semibold text-gray-800">No hay lineas disponibles</h3>
                    <p class="mt-1 text-sm text-gray-500">No se encontraron lineas activas de Etiquetadora.</p>
                </div>
            @endforelse
        </section>

        <div class="mt-8 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            {{ $planes->links() }}
        </div>
    </div>
</div>
@endsection
