@extends('layouts.app')

@section('title', 'Revision de Sugerencia IA')

@section('content')
@php
    $actions = old('recommended_actions', $structured['recommended_actions'] ?? []);
    $statusLabels = [
        'pending_review' => 'Pendiente de revision',
        'requires_information' => 'Requiere informacion',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
    ];
    $maintenanceTypeLabels = [
        'inspection' => 'Inspeccion',
        'preventive' => 'Preventivo',
        'corrective' => 'Correctivo',
        'predictive' => 'Predictivo',
    ];
    $historyActionLabels = [
        'generated' => 'Sugerencia generada',
        'generation_failed' => 'Generacion automatica fallida',
        'approved' => 'Sugerencia aprobada',
        'rejected' => 'Sugerencia rechazada',
        'requested_information' => 'Informacion adicional solicitada',
    ];
    $currentMaintenanceTypeLabel = $maintenanceTypeLabels[$plan->maintenance_type] ?? ucfirst((string) $plan->maintenance_type);
    $currentStatusLabel = $statusLabels[$plan->estado] ?? ucfirst(str_replace('_', ' ', (string) $plan->estado));
    $requiresManualReview = $plan->estado === 'requires_information';
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="rounded-3xl bg-slate-900 px-6 py-6 text-white shadow-xl">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route('plan-accion.ai.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white/90 transition hover:bg-white/20">
                    <i class="fas fa-arrow-left"></i>
                    Volver a sugerencias pendientes
                </a>
                <h1 class="mt-4 text-3xl font-black tracking-tight">{{ $plan->actividad }}</h1>
                <p class="mt-2 text-sm text-slate-300">
                    {{ $plan->linea?->nombre ?? 'Sin linea' }} ·
                    {{ $plan->maintenanceEvent?->componente?->nombre ?? 'Cadena de lavadora' }} ·
                    {{ $currentMaintenanceTypeLabel }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-amber-400 px-4 py-2 text-xs font-black uppercase tracking-wide text-slate-950">
                    {{ $currentStatusLabel }}
                </span>
                <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-wide text-white">
                    Confianza {{ number_format((float) $plan->confidence_level * 100, 0) }}%
                </span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-bold">Hay datos por corregir antes de guardar la revision.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($requiresManualReview)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-bold">Esta sugerencia requiere revision manual porque la IA no pudo terminar la generacion automaticamente.</p>
            <p class="mt-2">
                El sistema guardo este borrador para que el hallazgo no se pierda. Puedes revisarlo, completar lo que falte y luego aprobarlo para enviarlo al plan operativo.
            </p>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Ajuste y aprobacion</h2>
                        <p class="mt-1 text-sm text-slate-500">Revisa, corrige y completa esta propuesta antes de publicarla en el plan operativo.</p>
                    </div>
                </div>

                <form action="{{ route('plan-accion.ai.approve', ['planAccion' => $plan->id]) }}" method="POST" class="mt-6 space-y-6">
                    @csrf
                    <input type="hidden" name="requires_human_approval" value="1">

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="title" class="mb-2 block text-sm font-semibold text-slate-700">Titulo de la actividad</label>
                            <input id="title" name="title" type="text" value="{{ old('title', $structured['title'] ?? $plan->actividad) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="priority" class="mb-2 block text-sm font-semibold text-slate-700">Prioridad</label>
                            <select id="priority" name="priority" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach(['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Critica'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', $structured['priority'] ?? $plan->priority_level) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="maintenance_type" class="mb-2 block text-sm font-semibold text-slate-700">Tipo de mantenimiento</label>
                            <select id="maintenance_type" name="maintenance_type" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach(['inspection' => 'Inspeccion', 'preventive' => 'Preventivo', 'corrective' => 'Correctivo', 'predictive' => 'Predictivo'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('maintenance_type', $structured['maintenance_type'] ?? $plan->maintenance_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="responsable_id" class="mb-2 block text-sm font-semibold text-slate-700">Responsable</label>
                            <select id="responsable_id" name="responsable_id" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Sin asignar</option>
                                @foreach($usuariosResponsables as $usuario)
                                    <option value="{{ $usuario->id }}" @selected((string) old('responsable_id', $plan->responsable_id) === (string) $usuario->id)>{{ $usuario->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="suggested_due_date" class="mb-2 block text-sm font-semibold text-slate-700">Fecha sugerida</label>
                            <input id="suggested_due_date" name="suggested_due_date" type="date" value="{{ old('suggested_due_date', $structured['suggested_due_date'] ?? optional($plan->fecha_pcm1)->toDateString()) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                    </div>

                    <div class="grid gap-4">
                        <div>
                            <label for="detected_problem" class="mb-2 block text-sm font-semibold text-slate-700">Problema detectado</label>
                            <textarea id="detected_problem" name="detected_problem" rows="3" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('detected_problem', $structured['detected_problem'] ?? $plan->detected_problem) }}</textarea>
                        </div>

                        <div>
                            <label for="technical_justification" class="mb-2 block text-sm font-semibold text-slate-700">Justificacion tecnica</label>
                            <textarea id="technical_justification" name="technical_justification" rows="4" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('technical_justification', $structured['technical_justification'] ?? $plan->technical_justification) }}</textarea>
                        </div>

                        <div>
                            <label for="risk_if_not_executed" class="mb-2 block text-sm font-semibold text-slate-700">Riesgo si no se ejecuta</label>
                            <textarea id="risk_if_not_executed" name="risk_if_not_executed" rows="3" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('risk_if_not_executed', $structured['risk_if_not_executed'] ?? $plan->risk_if_not_executed) }}</textarea>
                        </div>

                        <div>
                            <label for="review_notes" class="mb-2 block text-sm font-semibold text-slate-700">Notas del revisor</label>
                            <textarea id="review_notes" name="review_notes" rows="3" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('review_notes', $plan->final_observations) }}</textarea>
                        </div>
                    </div>

                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <h3 class="text-sm font-black uppercase tracking-wide text-slate-800">Acciones recomendadas</h3>
                        <p class="mt-2 text-sm text-slate-500">Conserva solo el plan de accion esencial: actividad y detalle tecnico de cada paso.</p>
                        <div class="mt-4 space-y-4">
                            @foreach($actions as $index => $action)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Orden</label>
                                            <input name="recommended_actions[{{ $index }}][order]" type="number" min="1" value="{{ data_get($action, 'order', $loop->iteration) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Actividad</label>
                                            <input name="recommended_actions[{{ $index }}][activity]" type="text" value="{{ data_get($action, 'activity') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Detalle tecnico</label>
                                            <textarea name="recommended_actions[{{ $index }}][technical_detail]" rows="3" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ data_get($action, 'technical_detail') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">
                            <i class="fas fa-check"></i>
                            Aprobar y publicar
                        </button>
                        <a href="{{ route('plan-accion.lavadora.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i class="fas fa-list-check"></i>
                            Ir al plan operativo
                        </a>
                    </div>
                </form>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-black uppercase tracking-wide text-slate-800">Contexto del evento</h2>
                <div class="mt-4 space-y-3 text-sm text-slate-700">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Linea</p>
                        <p class="mt-1 font-semibold text-slate-950">{{ $plan->linea?->nombre ?? 'Sin linea' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Componente</p>
                        <p class="mt-1 font-semibold text-slate-950">{{ $plan->maintenanceEvent?->componente?->nombre ?? 'Cadena de lavadora' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Evento</p>
                        <p class="mt-1 font-semibold text-slate-950">{{ $plan->maintenanceEvent?->title ?? 'Sin titulo' }}</p>
                        <p class="mt-1 text-slate-600">{{ $plan->maintenanceEvent?->description ?? 'Sin descripcion' }}</p>
                    </div>
                    @if($plan->final_observations)
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Motivo de la revision manual</p>
                            <p class="mt-1 text-slate-600">{{ $plan->final_observations }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Generado por</p>
                        <p class="mt-1 font-semibold text-slate-950">{{ strtoupper((string) $plan->ai_provider) }} {{ $plan->ai_model ? '· ' . $plan->ai_model : '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Fuente del hallazgo</p>
                        @if($plan->maintenanceEvent?->sourceUrl())
                            <a href="{{ $plan->maintenanceEvent->sourceUrl() }}" class="mt-1 inline-flex items-center gap-2 font-semibold text-blue-600 hover:text-blue-800">
                                <i class="fas fa-arrow-up-right-from-square"></i>
                                Abrir registro original
                            </a>
                        @else
                            <p class="mt-1 text-slate-500">No disponible</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <h2 class="text-sm font-black uppercase tracking-wide text-rose-800">Rechazar sugerencia</h2>
                <form action="{{ route('plan-accion.ai.reject', ['planAccion' => $plan->id]) }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="reason" rows="4" placeholder="Explica por que no debe avanzar esta sugerencia." class="w-full rounded-xl border-rose-200 text-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-rose-700">
                        <i class="fas fa-ban"></i>
                        Rechazar
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
                <h2 class="text-sm font-black uppercase tracking-wide text-orange-800">Solicitar informacion</h2>
                <form action="{{ route('plan-accion.ai.request-information', ['planAccion' => $plan->id]) }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="message" rows="4" placeholder="Indica que dato falta para validar la sugerencia con seguridad." class="w-full rounded-xl border-orange-200 text-sm focus:border-orange-500 focus:ring-orange-500"></textarea>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-orange-600">
                        <i class="fas fa-circle-question"></i>
                        Marcar como requiere informacion
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-black uppercase tracking-wide text-slate-800">Historial de revision</h2>
                <div class="mt-4 space-y-3">
                    @forelse($plan->review_history ?? [] as $entry)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <p class="font-semibold text-slate-950">{{ $historyActionLabels[$entry['action'] ?? ''] ?? ucfirst(str_replace('_', ' ', (string) ($entry['action'] ?? 'sin accion'))) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $entry['performed_at'] ?? 'Sin fecha' }}</p>
                            @if(!empty($entry['notes'] ?? null))
                                <p class="mt-2 text-slate-600">{{ $entry['notes'] }}</p>
                            @endif
                            @if(!empty($entry['reason'] ?? null))
                                <p class="mt-2 text-slate-600">{{ $entry['reason'] }}</p>
                            @endif
                            @if(!empty($entry['message'] ?? null))
                                <p class="mt-2 text-slate-600">{{ $entry['message'] }}</p>
                            @endif
                            @if(!empty($entry['error'] ?? null))
                                <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-900">{{ $entry['error'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                            Aun no hay movimientos registrados sobre esta sugerencia.
                        </p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
