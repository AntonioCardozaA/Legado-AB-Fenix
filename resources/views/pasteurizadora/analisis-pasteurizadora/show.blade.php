@extends('layouts.app')

@section('title', 'Detalle del Analisis - Pasteurizadora')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
    $canDeleteAnalysis = $canDeleteAnalysis ?? (auth()->user()?->canDeletePasteurizadoraAnalysis() ?? false);
    $canEditAnalysis = $canEditAnalysis ?? (auth()->user()?->canUseCustomPermission('editar analisis pasteurizadora') ?? false);

    $evidencias = collect($analisis->evidencia_fotos ?? [])
        ->filter()
        ->map(fn ($foto) => ltrim(str_replace('\\', '/', $foto), '/'))
        ->values();

    $estado = $analisis->estado;
    $estadoStyles = match (true) {
        \App\Models\AnalisisPasteurizadora::esEstadoDanado($estado) => [
            'class' => 'bg-red-50 text-red-700 border-red-200',
            'icon' => 'fa-exclamation-circle',
            'header' => 'from-red-700 via-red-600 to-rose-500',
            'surface' => 'bg-red-50/70 border-red-100',
            'card' => 'border-red-100 bg-red-50/70',
            'iconBox' => 'bg-red-50 text-red-600',
            'activity' => 'border-red-200 bg-red-50/80',
            'buttonText' => 'text-red-700 hover:bg-red-50',
        ],
        \App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estado) => [
            'class' => 'bg-orange-50 text-orange-700 border-orange-200',
            'icon' => 'fa-triangle-exclamation',
            'header' => 'from-orange-700 via-orange-600 to-amber-500',
            'surface' => 'bg-orange-50/70 border-orange-100',
            'card' => 'border-orange-100 bg-orange-50/70',
            'iconBox' => 'bg-orange-50 text-orange-600',
            'activity' => 'border-orange-200 bg-orange-50/80',
            'buttonText' => 'text-orange-700 hover:bg-orange-50',
        ],
        \App\Models\AnalisisPasteurizadora::esEstadoRequiereRevision($estado) => [
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            'icon' => 'fa-screwdriver-wrench',
            'header' => 'from-amber-700 via-amber-600 to-yellow-500',
            'surface' => 'bg-amber-50/70 border-amber-100',
            'card' => 'border-amber-100 bg-amber-50/70',
            'iconBox' => 'bg-amber-50 text-amber-600',
            'activity' => 'border-amber-200 bg-amber-50/80',
            'buttonText' => 'text-amber-700 hover:bg-amber-50',
        ],
        \App\Models\AnalisisPasteurizadora::esEstadoCambiado($estado) => [
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

    $componentesRevisados = \App\Models\AnalisisPasteurizadora::normalizarComponentesRevisados(
        $analisis->componentes_revisados,
        $analisis->total_componentes
    );

    if (empty($componentesRevisados) && $analisis->cantidad_componentes_revisados) {
        $componentesRevisados = range(1, min($analisis->cantidad_componentes_revisados, $analisis->total_componentes));
    }

    $porcentajeAvance = (int) ($analisis->porcentaje_avance ?? 0);
    $bloquesTendencia = [
        'Analisis de Tendencia (52-12-4 semanas)' => collect($tendencia52124['ventanas'] ?? []),
        'Analisis de Tendencia (30-14-7 dias)' => collect($tendencia30147['ventanas'] ?? []),
    ];
    $pcmPlanes = [
        'pcm1' => $analisis->plan_accion_pcm1,
        'pcm2' => $analisis->plan_accion_pcm2,
        'pcm3' => $analisis->plan_accion_pcm3,
        'pcm4' => $analisis->plan_accion_pcm4,
    ];
    $tienePCM = collect($pcmPlanes)->contains(fn ($plan) => $plan && (!empty($plan['fecha']) || !empty($plan['accion'])));
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-2xl border {{ $estadoStyles['surface'] }} bg-white shadow-sm">
        <div class="bg-gradient-to-r {{ $estadoStyles['header'] }} px-6 py-7 text-white sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <div class="min-w-0">
                        <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-blue-100">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">
                                Analisis #{{ $analisis->id }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">
                                <i class="far fa-calendar-alt"></i>
                                {{ optional($analisis->fecha_analisis)->format('d/m/Y') ?? optional($analisis->created_at)->format('d/m/Y') ?? 'Sin fecha' }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">
                                {{ $analisis->tipo_registro_label }}
                            </span>
                        </div>
                        <h1 class="break-words text-2xl font-bold leading-tight sm:text-3xl">
                            {{ $analisis->linea->nombre ?? 'Pasteurizadora ' . $analisis->linea_id }}
                        </h1>
                        <p class="mt-2 max-w-3xl break-words text-sm text-blue-50">
                            Modulo {{ $analisis->modulo }} | {{ $analisis->componente_nombre }}
                            @if($analisis->nivel)
                                <span class="mx-2 text-blue-200">|</span>{{ $analisis->nivel }}
                            @endif
                            @if($analisis->lado)
                                <span class="mx-2 text-blue-200">|</span>{{ $analisis->lado }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap">
                    <a href="{{ $analisisRoute('edit', $analisis->id) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold {{ $estadoStyles['buttonText'] }} shadow-sm transition sm:w-auto">
                        <i class="fas fa-edit"></i>
                        Editar
                    </a>
                    <a href="{{ $analisisRoute('historial', ['linea_id' => $analisis->linea_id, 'modulo' => $analisis->modulo, 'componente' => $analisis->componente]) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold {{ $estadoStyles['buttonText'] }} shadow-sm transition sm:w-auto">
                        <i class="fas fa-history"></i>
                        Historial
                    </a>
                    @if($canDeleteAnalysis)
                        <button type="button"
                                id="delete-analysis-button"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 sm:w-auto">
                            <i class="fas fa-trash"></i>
                            Eliminar
                        </button>
                    @endif
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
                <span class="mt-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-semibold {{ $estadoStyles['class'] }}">
                    <i class="fas {{ $estadoStyles['icon'] }}"></i>
                    {{ $estado ?? 'Sin estado' }}
                </span>
            </div>
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Orden</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $analisis->numero_orden ?: 'Sin orden' }}</p>
            </div>
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Avance</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $porcentajeAvance }}%</p>
            </div>
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registro</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $analisis->tipo_registro_label }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $estadoStyles['iconBox'] }}">
                    <i class="fas fa-info-circle"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900">Informacion General</h2>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Linea</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->linea->nombre ?? 'N/A' }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Modulo</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">Modulo {{ $analisis->modulo }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Componente</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->componente_nombre }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registrado por</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->usuario?->name ?? $analisis->responsable ?? 'Sin usuario asignado' }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lado</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->lado ?: 'Sin lado' }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nivel</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisis->nivel ?: 'Sin nivel' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $estadoStyles['iconBox'] }}">
                    <i class="fas fa-clipboard-check"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900">Actividad</h2>
            </div>
            <div class="min-h-40 rounded-xl border p-4 text-sm leading-6 text-gray-800 whitespace-pre-line {{ $estadoStyles['activity'] }}">
                {{ $analisis->actividad ?: 'Sin actividad registrada.' }}
            </div>
        </div>
    </section>

    @if(!empty($componentesRevisados))
        <section class="rounded-2xl border border-indigo-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <i class="fas fa-clipboard-list"></i>
                    </span>
                    <h2 class="text-lg font-bold text-gray-900">Componentes revisados</h2>
                </div>
                <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">
                    {{ count($componentesRevisados) }} de {{ $analisis->total_componentes }}
                </span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($componentesRevisados as $componenteNumero)
                    <div class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-white text-indigo-600">
                            <i class="fas fa-check"></i>
                        </span>
                        <span class="min-w-0 break-words text-sm font-semibold text-gray-800">
                            @if(\App\Models\AnalisisPasteurizadora::esBrazoTorsion($analisis->componente))
                                {{ $analisis->componente_nombre }} modulo {{ intval($componenteNumero) }}
                            @else
                                {{ $analisis->componente_nombre }} #{{ intval($componenteNumero) }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($analisis->observaciones || $analisis->resuelto_por_cambio)
        <section class="grid gap-6 lg:grid-cols-2">
            @if($analisis->observaciones)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-amber-600">
                            <i class="fas fa-comment-dots"></i>
                        </span>
                        <h2 class="text-lg font-bold text-gray-900">Observaciones adicionales</h2>
                    </div>
                    <div class="text-sm leading-6 text-gray-800 whitespace-pre-line">{{ $analisis->observaciones }}</div>
                </div>
            @endif

            @if($analisis->resuelto_por_cambio)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-600">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <h2 class="text-lg font-bold text-gray-900">Registro resuelto</h2>
                    </div>
                    <div class="grid gap-4 text-sm md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha de resolucion</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ $analisis->fecha_resolucion_formateada }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Resuelto por</p>
                            <p class="mt-1 font-semibold text-gray-900">Orden #{{ $analisis->registroResolutor->numero_orden ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @if($analisis->nota_resolucion)
                        <div class="mt-4 border-t border-emerald-200 pt-4 text-sm text-gray-800">
                            {{ $analisis->nota_resolucion }}
                        </div>
                    @endif
                </div>
            @endif
        </section>
    @endif

    @foreach($bloquesTendencia as $tituloTendencia => $ventanasTendencia)
        @if($ventanasTendencia->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <i class="fas fa-chart-line"></i>
                    </span>
                    <h2 class="text-lg font-bold text-gray-900">{{ $tituloTendencia }}</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($ventanasTendencia as $ventana)
                        @php
                            $delta = (int) ($ventana['delta'] ?? 0);
                            $trend = $ventana['trend'] ?? 'stable';
                            $toneClass = $trend === 'up' ? 'text-red-600' : ($trend === 'down' ? 'text-green-600' : 'text-yellow-600');
                            $icon = $trend === 'up' ? 'fa-arrow-up' : ($trend === 'down' ? 'fa-arrow-down' : 'fa-minus');
                        @endphp
                        <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-5 text-center">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $ventana['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold text-blue-700">{{ $ventana['current'] ?? 0 }}</p>
                            <p class="mt-2 text-xs text-gray-600">
                                <span class="{{ $toneClass }}">
                                    <i class="fas {{ $icon }}"></i>
                                    {{ $delta > 0 ? '+' : '' }}{{ $delta }}
                                </span>
                                vs periodo anterior
                            </p>
                            <p class="mt-2 text-[11px] text-gray-500">{{ $ventana['current_range'] ?? '-' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    @if($tienePCM)
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-calendar-check"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900">Plan de Accion PCM</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach(['pcm1' => 'PCM 1', 'pcm2' => 'PCM 2', 'pcm3' => 'PCM 3', 'pcm4' => 'PCM 4'] as $key => $nombre)
                    @php($plan = $analisis->{'plan_accion_' . $key})
                    <div class="rounded-xl border p-4 {{ $plan && $plan['fecha'] ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-gray-50' }}">
                        <h3 class="font-bold text-gray-800">{{ $nombre }}</h3>
                        @if($plan && $plan['fecha'])
                            <p class="mt-2 text-sm font-semibold text-gray-700">
                                <i class="far fa-calendar-alt mr-1 text-blue-600"></i>
                                {{ \Carbon\Carbon::parse($plan['fecha'])->format('d/m/Y') }}
                            </p>
                            @if($plan['accion'])
                                <p class="mt-2 text-xs leading-5 text-gray-600">{{ Str::limit($plan['accion'], 80) }}</p>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-gray-400">Sin programacion</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $estadoStyles['iconBox'] }}">
                    <i class="fas fa-camera"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900">Evidencia Fotografica</h2>
            </div>
            @if($evidencias->isNotEmpty())
                <button type="button" id="download-all-evidence" class="create-action create-action--compact">
                    <i class="fas fa-download"></i>
                    Descargar todas
                </button>
            @endif
        </div>

        @if($evidencias->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($evidencias as $index => $foto)
                    @php($fotoUrl = asset('storage/' . $foto))
                    <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <button type="button"
                                class="group relative block aspect-[4/3] w-full overflow-hidden bg-gray-100"
                                data-photo-url="{{ $fotoUrl }}"
                                data-photo-title="Evidencia #{{ $index + 1 }}">
                            <img src="{{ $fotoUrl }}" alt="Evidencia {{ $index + 1 }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            <span class="absolute left-3 top-3 rounded-full bg-black/70 px-3 py-1 text-xs font-semibold text-white">
                                #{{ $index + 1 }}
                            </span>
                            <span class="absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition group-hover:bg-black/35 group-hover:opacity-100">
                                <i class="fas fa-search-plus text-2xl"></i>
                            </span>
                        </button>
                        <div class="flex flex-col items-stretch gap-2 p-3 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                            <a href="{{ $fotoUrl }}" target="_blank" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold text-blue-600 hover:bg-blue-50 hover:text-blue-800 sm:justify-start">
                                <i class="fas fa-up-right-from-square"></i>
                                Abrir
                            </a>
                            @if($canEditAnalysis)
                                <form action="{{ $analisisRoute('delete-foto', ['analisispasteurizadora' => $analisis->id, 'fotoIndex' => $index]) }}" method="POST" class="m-0 delete-photo-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100 sm:w-auto">
                                        <i class="fas fa-trash"></i>
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed px-6 py-12 text-center {{ $estadoStyles['card'] }}">
                <i class="fas fa-image mb-3 text-3xl text-gray-300"></i>
                <p class="text-sm font-semibold text-gray-700">No hay evidencia fotografica registrada.</p>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-200 bg-gray-50 px-6 py-4 text-xs text-gray-500">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <i class="far fa-clock mr-1"></i>
                Creado: {{ optional($analisis->created_at)->format('d/m/Y H:i') ?: 'N/A' }}
            </div>
            <div>
                <i class="fas fa-edit mr-1"></i>
                Ultima actualizacion: {{ optional($analisis->updated_at)->format('d/m/Y H:i') ?: 'N/A' }}
            </div>
        </div>
    </section>
</div>

@if($canDeleteAnalysis)
    <form id="delete-analysis-form" action="{{ $analisisRoute('destroy', $analisis->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endif

<div id="photoPreviewModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/75 p-2 sm:p-4">
    <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h3 id="photoPreviewTitle" class="min-w-0 break-words text-base font-bold text-gray-900">Evidencia</h3>
            <button type="button" id="photoPreviewClose" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="bg-gray-950 p-3">
            <img id="photoPreviewImage" src="" alt="Evidencia" class="mx-auto max-h-[70vh] w-auto rounded-lg object-contain sm:max-h-[75vh]">
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('photoPreviewModal');
    const image = document.getElementById('photoPreviewImage');
    const title = document.getElementById('photoPreviewTitle');
    const close = document.getElementById('photoPreviewClose');
    const evidencias = @json($evidencias->map(fn ($foto) => asset('storage/' . $foto))->values());

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        image.src = '';
    }

    document.querySelectorAll('[data-photo-url]').forEach(function(button) {
        button.addEventListener('click', function() {
            image.src = this.dataset.photoUrl;
            title.textContent = this.dataset.photoTitle || 'Evidencia';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    close.addEventListener('click', closeModal);
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    document.getElementById('download-all-evidence')?.addEventListener('click', function() {
        evidencias.forEach(function(url, index) {
            const link = document.createElement('a');
            link.href = url;
            link.download = `evidencia_${index + 1}.jpg`;
            setTimeout(function() {
                link.click();
            }, index * 200);
        });
    });

    document.querySelectorAll('.delete-photo-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            Swal.fire({
                icon: 'warning',
                title: 'Eliminar evidencia',
                text: 'Esta accion no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    @if($canDeleteAnalysis)
        const deleteAnalysisButton = document.getElementById('delete-analysis-button');
        const deleteAnalysisForm = document.getElementById('delete-analysis-form');

        if (deleteAnalysisButton && deleteAnalysisForm) {
            deleteAnalysisButton.addEventListener('click', function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Eliminar analisis',
                    text: 'Esta accion es irreversible y eliminara el registro seleccionado.',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        deleteAnalysisForm.submit();
                    }
                });
            });
        }
    @endif
});
</script>
@endsection
