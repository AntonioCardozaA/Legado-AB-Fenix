@extends('layouts.app')

@section('title', 'Detalle de Analisis Lavadora')

@section('content')
@php
    $evidencias = collect($analisislavadora->evidencia_fotos ?? [])->filter()->values();
    $photoPreviewImages = $evidencias
        ->map(fn ($foto, $index) => [
            'url' => asset('storage/' . $foto),
            'title' => 'Evidencia #' . ($index + 1),
        ])
        ->values();
    $estado = $analisislavadora->estado;
    $reductorLabel = \App\Support\LavadoraCatalog::etiquetaReductorParaValor($analisislavadora->linea->nombre ?? null, $analisislavadora->reductor);
    $reductorNombre = \App\Support\LavadoraCatalog::nombreReductorParaLinea($analisislavadora->linea->nombre ?? null, $analisislavadora->reductor);
    $estadoStyles = match (true) {
        \App\Models\AnalisisLavadora::esEstadoDanado($estado) => [
            'class' => 'bg-red-50 text-red-700 border-red-200',
            'icon' => 'fa-exclamation-circle',
            'header' => 'from-red-700 via-red-600 to-rose-500',
            'surface' => 'bg-red-50/70 border-red-100',
            'card' => 'border-red-100 bg-red-50/70',
            'iconBox' => 'bg-red-50 text-red-600',
            'activity' => 'border-red-200 bg-red-50/80',
            'buttonText' => 'text-red-700 hover:bg-red-50',
        ],
        \App\Models\AnalisisLavadora::esEstadoDesgaste($estado) => [
            'class' => 'bg-orange-50 text-orange-700 border-orange-200',
            'icon' => 'fa-triangle-exclamation',
            'header' => 'from-orange-700 via-orange-600 to-amber-500',
            'surface' => 'bg-orange-50/70 border-orange-100',
            'card' => 'border-orange-100 bg-orange-50/70',
            'iconBox' => 'bg-orange-50 text-orange-600',
            'activity' => 'border-orange-200 bg-orange-50/80',
            'buttonText' => 'text-orange-700 hover:bg-orange-50',
        ],
        \App\Models\AnalisisLavadora::esEstadoRequiereRevision($estado) => [
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            'icon' => 'fa-screwdriver-wrench',
            'header' => 'from-amber-700 via-amber-600 to-yellow-500',
            'surface' => 'bg-amber-50/70 border-amber-100',
            'card' => 'border-amber-100 bg-amber-50/70',
            'iconBox' => 'bg-amber-50 text-amber-600',
            'activity' => 'border-amber-200 bg-amber-50/80',
            'buttonText' => 'text-amber-700 hover:bg-amber-50',
        ],
        \App\Models\AnalisisLavadora::esEstadoCambiado($estado) => [
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
    .photo-preview-stage {
        touch-action: pan-y;
        user-select: none;
        -webkit-user-select: none;
        -webkit-user-drag: none;
        cursor: grab;
    }

    .photo-preview-stage.is-swiping {
        cursor: grabbing;
    }

    .photo-preview-stage img {
        touch-action: pan-y;
        user-select: none;
        -webkit-user-select: none;
        -webkit-user-drag: none;
    }
</style>

<div class="mx-auto max-w-7xl space-y-6">
    <section class="overflow-hidden rounded-2xl border {{ $estadoStyles['surface'] }} bg-white shadow-sm">
        <div class="bg-gradient-to-r {{ $estadoStyles['header'] }} px-6 py-7 text-white sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <div class="hidden h-20 w-20 flex-shrink-0 items-center justify-center rounded-2xl bg-white/15 p-3 sm:flex">
                        <img src="{{ asset('images/icono-maquina.png') }}" alt="Lavadora" class="h-full w-full object-contain">
                    </div>
                    <div class="min-w-0">
                        <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-blue-100">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">
                                Analisis #{{ $analisislavadora->id }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-semibold">
                                <i class="far fa-calendar-alt"></i>
                                {{ optional($analisislavadora->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}
                            </span>
                        </div>
                        <h1 class="text-2xl font-bold leading-tight sm:text-3xl">
                            {{ $analisislavadora->linea->nombre ?? 'Lavadora ' . $analisislavadora->linea_id }}
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm text-blue-50">
                            {{ $analisislavadora->componente->nombre ?? 'Componente no asignado' }}
                            @if($analisislavadora->reductor)
                                <span class="mx-2 text-blue-200">|</span>{{ $analisislavadora->reductor }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if($canAccessLavadoraCosts ?? (auth()->user()?->canAccessLavadoraCosts() ?? false))
                        <a href="{{ route('analisis-lavadora.costos.manage', ['analisislavadora' => $analisislavadora->id], false) }}"
                           class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            <i class="fas fa-sack-dollar"></i>
                            Administrar costos
                        </a>
                    @endif
                    <a href="{{ route('analisis-lavadora.edit', ['analisislavadora' => $analisislavadora->id]) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold {{ $estadoStyles['buttonText'] }} shadow-sm transition">
                        <i class="fas fa-edit"></i>
                        Editar
                    </a>
                    @if($canDeleteAnalysis ?? false)
                        <button type="button"
                                id="delete-analysis-button"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            <i class="fas fa-trash"></i>
                            Eliminar
                        </button>
                    @endif
                    <a href="{{ route('analisis-lavadora.index', ['linea_id' => $analisislavadora->linea_id]) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/40 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20">
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
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $analisislavadora->numero_orden ?: 'Sin orden' }}</p>
            </div>
            <div class="rounded-xl border bg-white/85 p-4 shadow-sm {{ $estadoStyles['card'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lado</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $analisislavadora->lado ?: 'No aplica' }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $estadoStyles['iconBox'] }}">
                    <i class="fas fa-info-circle"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Informacion General</h2>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lavadora</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisislavadora->linea->nombre ?? 'Lavadora ' . $analisislavadora->linea_id }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Componente</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisislavadora->componente->nombre ?? 'N/A' }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $reductorLabel }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $reductorNombre ?: 'Sin ' . strtolower($reductorLabel) }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $estadoStyles['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registrado por</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisislavadora->usuario->name ?? 'Sin usuario asignado' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $estadoStyles['iconBox'] }}">
                    <i class="fas fa-clipboard-check"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Actividad</h2>
                </div>
            </div>
            <div class="min-h-40 rounded-xl border p-4 text-sm leading-6 text-gray-800 {{ $estadoStyles['activity'] }}">
                {{ $analisislavadora->actividad ?: 'Sin actividad registrada.' }}
            </div>
        </div>
    </section>

    @if($analisislavadora->cambiosFecha->isNotEmpty())
    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
        <div class="mb-4 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-amber-600">
                <i class="fas fa-history"></i>
            </span>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Historial de cambios de fecha</h2>
            </div>
        </div>

        <div class="space-y-3">
            @foreach($analisislavadora->cambiosFecha->sortByDesc('fecha_cambio') as $cambioFecha)
                <div class="rounded-xl border border-amber-100 bg-white p-4 text-sm text-gray-700">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-semibold text-gray-900">
                            {{ $cambioFecha->usuario->name ?? 'Usuario no disponible' }}
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ optional($cambioFecha->fecha_cambio)->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <p class="mt-2">
                        Cambio de
                        <span class="font-semibold">{{ optional($cambioFecha->fecha_anterior)->format('d/m/Y') }}</span>
                        a
                        <span class="font-semibold">{{ optional($cambioFecha->fecha_nueva)->format('d/m/Y') }}</span>
                    </p>
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
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Evidencia Fotografica</h2>
                </div>
            </div>
        </div>

        @if($evidencias->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($evidencias as $index => $foto)
                    @php($fotoUrl = asset('storage/' . $foto))
                    <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <button type="button"
                                class="group relative block aspect-[4/3] w-full overflow-hidden bg-gray-100"
                                data-photo-url="{{ $fotoUrl }}"
                                data-photo-title="Evidencia #{{ $index + 1 }}"
                                data-photo-index="{{ $index }}">
                            <img src="{{ $fotoUrl }}" alt="Evidencia {{ $index + 1 }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            <span class="absolute left-3 top-3 rounded-full bg-black/70 px-3 py-1 text-xs font-semibold text-white">
                                #{{ $index + 1 }}
                            </span>
                            <span class="absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition group-hover:bg-black/35 group-hover:opacity-100">
                                <i class="fas fa-search-plus text-2xl"></i>
                            </span>
                        </button>
                        <div class="flex items-center justify-between gap-3 p-3">
                            <a href="{{ $fotoUrl }}"
                               target="_blank"
                               class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800">
                                <i class="fas fa-up-right-from-square"></i>
                                Abrir
                            </a>
                            <form action="{{ route('analisis-lavadora.delete-foto', ['analisislavadora' => $analisislavadora->id, 'fotoIndex' => $index]) }}" method="POST" class="m-0 delete-photo-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                                    <i class="fas fa-trash"></i>
                                    Eliminar
                                </button>
                            </form>
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
</div>

@if($canDeleteAnalysis ?? false)
    <form id="delete-analysis-form" action="{{ route('analisis-lavadora.destroy', ['analisislavadora' => $analisislavadora->id]) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endif

<div id="photoPreviewModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/95 p-4 transition-all duration-300" onclick="closePhotoPreviewModal()">
    <div id="photoPreviewStage" class="relative flex h-full w-full max-w-6xl items-center justify-center photo-preview-stage" onclick="event.stopPropagation()">
        <button type="button"
                id="photoPreviewClose"
                class="absolute right-4 top-4 z-20 flex h-12 w-12 items-center justify-center rounded-lg border border-gray-600 bg-gray-800/60 text-2xl text-white backdrop-blur-sm transition hover:bg-gray-700/80 sm:right-6 sm:top-6">
            <i class="fas fa-times"></i>
        </button>

        <button type="button"
                id="photoPreviewPrev"
                class="absolute left-2 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-600 bg-gray-800/60 text-xl text-white backdrop-blur-sm transition hover:bg-gray-700/80 sm:left-4 sm:h-12 sm:w-12 hidden">
            <i class="fas fa-chevron-left"></i>
        </button>

        <img id="photoPreviewImage" src="" alt="Evidencia" class="max-h-[82vh] max-w-full rounded-lg border-4 border-gray-700 object-contain shadow-2xl">

        <button type="button"
                id="photoPreviewNext"
                class="absolute right-2 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-gray-600 bg-gray-800/60 text-xl text-white backdrop-blur-sm transition hover:bg-gray-700/80 sm:right-4 sm:h-12 sm:w-12 hidden">
            <i class="fas fa-chevron-right"></i>
        </button>

        <div id="photoPreviewCounter" class="absolute bottom-6 left-1/2 z-20 -translate-x-1/2 rounded-lg border border-gray-700 bg-gray-900/80 px-4 py-2 font-mono text-sm text-white backdrop-blur-sm hidden">
            <span id="photoPreviewCurrent">1</span> / <span id="photoPreviewTotal">1</span>
        </div>

        <h3 id="photoPreviewTitle" class="absolute bottom-20 left-1/2 z-20 max-w-[calc(100vw-2rem)] -translate-x-1/2 rounded-lg border border-gray-700 bg-gray-900/80 px-4 py-2 text-center text-sm font-semibold text-white backdrop-blur-sm">Evidencia</h3>
    </div>
</div>
@endsection

@section('scripts')
<script>
const PHOTO_PREVIEW_IMAGES = {{ \Illuminate\Support\Js::from($photoPreviewImages) }};
let currentPhotoPreviewIndex = 0;
let photoPreviewSwipe = null;
const PHOTO_PREVIEW_SWIPE_DISTANCE = 50;
const PHOTO_PREVIEW_SWIPE_VERTICAL_TOLERANCE = 1.25;

function photoPreviewElements() {
    return {
        modal: document.getElementById('photoPreviewModal'),
        image: document.getElementById('photoPreviewImage'),
        title: document.getElementById('photoPreviewTitle'),
        prev: document.getElementById('photoPreviewPrev'),
        next: document.getElementById('photoPreviewNext'),
        counter: document.getElementById('photoPreviewCounter'),
        current: document.getElementById('photoPreviewCurrent'),
        total: document.getElementById('photoPreviewTotal'),
        stage: document.getElementById('photoPreviewStage'),
    };
}

function updatePhotoPreview() {
    const elements = photoPreviewElements();
    const item = PHOTO_PREVIEW_IMAGES[currentPhotoPreviewIndex];
    const hasMultipleImages = PHOTO_PREVIEW_IMAGES.length > 1;

    if (!elements.modal || !item) {
        return;
    }

    elements.image.src = item.url;
    elements.title.textContent = item.title || `Evidencia #${currentPhotoPreviewIndex + 1}`;
    elements.current.textContent = currentPhotoPreviewIndex + 1;
    elements.total.textContent = PHOTO_PREVIEW_IMAGES.length;
    elements.prev.classList.toggle('hidden', !hasMultipleImages);
    elements.next.classList.toggle('hidden', !hasMultipleImages);
    elements.counter.classList.toggle('hidden', !hasMultipleImages);
}

function openPhotoPreview(index) {
    const elements = photoPreviewElements();

    if (!elements.modal || PHOTO_PREVIEW_IMAGES.length === 0) {
        return;
    }

    currentPhotoPreviewIndex = Math.max(0, Math.min(index, PHOTO_PREVIEW_IMAGES.length - 1));
    updatePhotoPreview();
    elements.modal.classList.remove('hidden');
    elements.modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function changePhotoPreview(direction) {
    if (PHOTO_PREVIEW_IMAGES.length <= 1) {
        return;
    }

    currentPhotoPreviewIndex = (currentPhotoPreviewIndex + direction + PHOTO_PREVIEW_IMAGES.length) % PHOTO_PREVIEW_IMAGES.length;
    updatePhotoPreview();
}

function closePhotoPreviewModal() {
    const elements = photoPreviewElements();

    if (!elements.modal) {
        return;
    }

    elements.modal.classList.add('hidden');
    elements.modal.classList.remove('flex');
    elements.image.src = '';
    cancelPhotoPreviewSwipe({ currentTarget: elements.stage });
    document.body.style.overflow = '';
}

function getPhotoPreviewSwipePoint(event) {
    const source = event.changedTouches?.[0] || event.touches?.[0] || event;

    return {
        x: source.clientX ?? 0,
        y: source.clientY ?? 0,
    };
}

function startPhotoPreviewSwipe(event) {
    if (event.target?.closest('button') || PHOTO_PREVIEW_IMAGES.length <= 1) {
        return;
    }

    if (event.pointerId !== undefined && event.currentTarget?.setPointerCapture) {
        try {
            event.currentTarget.setPointerCapture(event.pointerId);
        } catch (error) {}
    }

    const point = getPhotoPreviewSwipePoint(event);
    photoPreviewSwipe = {
        startX: point.x,
        startY: point.y,
        lastX: point.x,
        lastY: point.y,
    };

    event.currentTarget?.classList.add('is-swiping');
}

function movePhotoPreviewSwipe(event) {
    if (!photoPreviewSwipe) {
        return;
    }

    const point = getPhotoPreviewSwipePoint(event);
    const deltaX = point.x - photoPreviewSwipe.startX;
    const deltaY = point.y - photoPreviewSwipe.startY;

    photoPreviewSwipe.lastX = point.x;
    photoPreviewSwipe.lastY = point.y;

    if (Math.abs(deltaX) > Math.abs(deltaY) && event.cancelable) {
        event.preventDefault();
    }
}

function finishPhotoPreviewSwipe(event) {
    if (!photoPreviewSwipe) {
        return;
    }

    const point = getPhotoPreviewSwipePoint(event);
    const endX = point.x ?? photoPreviewSwipe.lastX;
    const endY = point.y ?? photoPreviewSwipe.lastY;
    const deltaX = endX - photoPreviewSwipe.startX;
    const deltaY = endY - photoPreviewSwipe.startY;
    const isHorizontalSwipe = Math.abs(deltaX) >= PHOTO_PREVIEW_SWIPE_DISTANCE
        && Math.abs(deltaX) > Math.abs(deltaY) * PHOTO_PREVIEW_SWIPE_VERTICAL_TOLERANCE;

    if (isHorizontalSwipe) {
        changePhotoPreview(deltaX < 0 ? 1 : -1);
    }

    cancelPhotoPreviewSwipe(event);
}

function cancelPhotoPreviewSwipe(event) {
    const stage = event.currentTarget;

    photoPreviewSwipe = null;
    if (event.pointerId !== undefined && stage?.releasePointerCapture) {
        try {
            stage.releasePointerCapture(event.pointerId);
        } catch (error) {}
    }
    stage?.classList.remove('is-swiping');
}

function setupPhotoPreviewSwipe(stage) {
    if (!stage) {
        return;
    }

    if (window.PointerEvent) {
        stage.addEventListener('pointerdown', startPhotoPreviewSwipe);
        stage.addEventListener('pointermove', movePhotoPreviewSwipe);
        stage.addEventListener('pointerup', finishPhotoPreviewSwipe);
        stage.addEventListener('pointercancel', cancelPhotoPreviewSwipe);
        return;
    }

    stage.addEventListener('touchstart', startPhotoPreviewSwipe, { passive: true });
    stage.addEventListener('touchmove', movePhotoPreviewSwipe, { passive: false });
    stage.addEventListener('touchend', finishPhotoPreviewSwipe);
    stage.addEventListener('touchcancel', cancelPhotoPreviewSwipe);
}

document.addEventListener('DOMContentLoaded', function() {
    const elements = photoPreviewElements();
    const close = document.getElementById('photoPreviewClose');
    const prev = document.getElementById('photoPreviewPrev');
    const next = document.getElementById('photoPreviewNext');

    document.querySelectorAll('[data-photo-url]').forEach(function(button) {
        button.addEventListener('click', function() {
            openPhotoPreview(Number(this.dataset.photoIndex || 0));
        });
    });

    close?.addEventListener('click', closePhotoPreviewModal);
    prev?.addEventListener('click', function() {
        changePhotoPreview(-1);
    });
    next?.addEventListener('click', function() {
        changePhotoPreview(1);
    });
    setupPhotoPreviewSwipe(elements.stage);

    document.addEventListener('keydown', function(event) {
        if (!elements.modal || elements.modal.classList.contains('hidden')) {
            return;
        }

        if (event.key === 'Escape') {
            closePhotoPreviewModal();
        }

        if (event.key === 'ArrowLeft') {
            changePhotoPreview(-1);
        }

        if (event.key === 'ArrowRight') {
            changePhotoPreview(1);
        }
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

    @if($canDeleteAnalysis ?? false)
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
