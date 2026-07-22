@extends('layouts.app')

@section('title', 'Revision de Planes IA')

@section('content')
@php
    $statusLabels = [
        'queue' => 'Pendientes',
        'pending_review' => 'Pendiente de revision',
        'requires_information' => 'Requiere informacion',
        'approved' => 'Aprobados',
        'rejected' => 'Rechazados',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="rounded-3xl bg-slate-900 px-6 py-6 text-white shadow-xl">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route('plan-accion.lavadora.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white/90 transition hover:bg-white/20">
                    <i class="fas fa-arrow-left"></i>
                    Volver a planes de accion
                </a>
                <h1 class="mt-4 text-3xl font-black tracking-tight">Revision de sugerencias IA para lavadoras</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-300">
                    Aqui aprobamos, ajustamos o descartamos los planes generados automaticamente antes de que entren al flujo operativo.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('lavadora.knowledge-documents.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-amber-300">
                    <i class="fas fa-book-open"></i>
                    Base de conocimiento
                </a>
                <a href="{{ route('lavadora.knowledge-documents.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/20">
                    <i class="fas fa-file-circle-plus"></i>
                    Cargar documento
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Pendientes de revision</p>
            <p class="mt-3 text-4xl font-black text-amber-950">{{ $counts['queue'] }}</p>
            <p class="mt-2 text-sm text-amber-800">Sugerencias que siguen esperando revision o informacion adicional.</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Aprobados</p>
            <p class="mt-3 text-4xl font-black text-emerald-950">{{ $counts['approved'] }}</p>
            <p class="mt-2 text-sm text-emerald-800">Ya forman parte del plan operativo.</p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Rechazados</p>
            <p class="mt-3 text-4xl font-black text-rose-950">{{ $counts['rejected'] }}</p>
            <p class="mt-2 text-sm text-rose-800">Se descartaron por falta de pertinencia o seguridad.</p>
        </div>
    </div>

    <form method="GET" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="linea_id" class="mb-2 block text-sm font-semibold text-slate-700">Linea</label>
                <select id="linea_id" name="linea_id" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas las lineas</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" @selected((string) $lineaId === (string) $linea->id)>{{ $linea->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="estado" class="mb-2 block text-sm font-semibold text-slate-700">Estado</label>
                <select id="estado" name="estado" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                    <i class="fas fa-filter"></i>
                    Filtrar
                </button>
                <a href="{{ route('plan-accion.ai.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i class="fas fa-rotate-left"></i>
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    <div class="space-y-4">
        @forelse($plans as $plan)
            @php
                $structured = $plan->currentStructuredContent() ?? [];
                $statusClasses = match ($plan->estado) {
                    'approved' => 'bg-emerald-100 text-emerald-800',
                    'rejected' => 'bg-rose-100 text-rose-800',
                    'requires_information' => 'bg-orange-100 text-orange-800',
                    default => 'bg-amber-100 text-amber-900',
                };
                $priorityClasses = match ($plan->priority_level) {
                    'critical' => 'bg-rose-100 text-rose-800',
                    'high' => 'bg-orange-100 text-orange-800',
                    'medium' => 'bg-blue-100 text-blue-800',
                    default => 'bg-slate-100 text-slate-700',
                };
            @endphp

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $statusClasses }}">
                                {{ $statusLabels[$plan->estado] ?? ucfirst(str_replace('_', ' ', (string) $plan->estado)) }}
                            </span>
                            <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $priorityClasses }}">
                                Prioridad {{ strtoupper((string) $plan->priority_level) }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-700">
                                {{ $plan->linea?->nombre ?? 'Sin linea' }}
                            </span>
                        </div>

                        <div>
                            <h2 class="text-xl font-black text-slate-950">{{ $plan->actividad }}</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $plan->maintenanceEvent?->componente?->nombre ?? 'Cadena de lavadora' }} ·
                                {{ ucfirst((string) $plan->maintenance_type) }} ·
                                Confianza {{ number_format((float) $plan->confidence_level * 100, 0) }}%
                            </p>
                        </div>

                        <p class="max-w-3xl text-sm leading-6 text-slate-700">
                            {{ \Illuminate\Support\Str::limit($structured['detected_problem'] ?? $plan->detected_problem ?? 'Sin descripcion del hallazgo.', 220) }}
                        </p>

                        <div class="flex flex-wrap gap-4 text-xs font-medium text-slate-500">
                            <span><strong class="text-slate-700">Generado:</strong> {{ optional($plan->generated_at)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                            <span><strong class="text-slate-700">Fecha sugerida:</strong> {{ optional($plan->fecha_pcm1)->format('d/m/Y') ?? 'Sin fecha' }}</span>
                            @if($plan->maintenanceEvent?->sourceUrl())
                                <a href="{{ $plan->maintenanceEvent->sourceUrl() }}" class="font-semibold text-blue-600 hover:text-blue-800">Ver fuente del hallazgo</a>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 lg:items-end">
                        <a href="{{ route('plan-accion.ai.review', ['planAccion' => $plan->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                            <i class="fas fa-clipboard-check"></i>
                            Revisar sugerencia
                        </a>
                        @if($plan->reviewedBy)
                            <p class="text-xs text-slate-500">
                                Revisado por {{ $plan->reviewedBy->name }} el {{ optional($plan->reviewed_at)->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-slate-500">
                <i class="fas fa-inbox text-4xl text-slate-300"></i>
                <p class="mt-4 text-lg font-semibold text-slate-700">No hay sugerencias para este filtro.</p>
                <p class="mt-2 text-sm">Cuando el motor detecte eventos de mantenimiento en lavadoras, apareceran aqui para revision humana.</p>
            </div>
        @endforelse
    </div>

    <div>
        {{ $plans->links() }}
    </div>
</div>
@endsection
