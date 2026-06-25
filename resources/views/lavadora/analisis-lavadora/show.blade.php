@extends('layouts.app')

@section('title', 'Detalle de Analisis Lavadora')

@section('content')
@php
    $evidencias = collect($analisislavadora->evidencia_fotos ?? [])->filter()->values();
    $estado = $analisislavadora->estado;
    $estadoStyles = match (true) {
        \App\Models\AnalisisLavadora::esEstadoDanado($estado) => [
            'class' => 'bg-red-50 text-red-700 border-red-200',
            'icon' => 'fa-exclamation-circle',
        ],
        \App\Models\AnalisisLavadora::esEstadoDesgaste($estado) => [
            'class' => 'bg-orange-50 text-orange-700 border-orange-200',
            'icon' => 'fa-triangle-exclamation',
        ],
        \App\Models\AnalisisLavadora::esEstadoRequiereRevision($estado) => [
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            'icon' => 'fa-screwdriver-wrench',
        ],
        \App\Models\AnalisisLavadora::esEstadoCambiado($estado) => [
            'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            'icon' => 'fa-arrows-rotate',
        ],
        default => [
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'icon' => 'fa-circle-check',
        ],
    };
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <section class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-sky-500 px-6 py-7 text-white sm:px-8">
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
                    <a href="{{ route('analisis-lavadora.edit', ['analisislavadora' => $analisislavadora->id]) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
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

        <div class="grid gap-4 border-t border-blue-100 bg-blue-50/60 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4 sm:px-8">
            <div class="rounded-xl border border-white bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</p>
                <span class="mt-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-semibold {{ $estadoStyles['class'] }}">
                    <i class="fas {{ $estadoStyles['icon'] }}"></i>
                    {{ $estado ?? 'Sin estado' }}
                </span>
            </div>
            <div class="rounded-xl border border-white bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Orden</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $analisislavadora->numero_orden ?: 'Sin orden' }}</p>
            </div>
            <div class="rounded-xl border border-white bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lado</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $analisislavadora->lado ?: 'No aplica' }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-info-circle"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Informacion General</h2>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lavadora</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisislavadora->linea->nombre ?? 'Lavadora ' . $analisislavadora->linea_id }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Componente</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisislavadora->componente->nombre ?? 'N/A' }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reductor</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisislavadora->reductor ?: 'Sin reductor' }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registrado por</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $analisislavadora->usuario->name ?? 'Sin usuario asignado' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fas fa-clipboard-check"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Actividad</h2>
                </div>
            </div>
            <div class="min-h-40 rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 text-sm leading-6 text-gray-800">
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
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
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
                                data-photo-title="Evidencia #{{ $index + 1 }}">
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
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center">
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

<div id="photoPreviewModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/75 p-4">
    <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h3 id="photoPreviewTitle" class="text-base font-bold text-gray-900">Evidencia</h3>
            <button type="button" id="photoPreviewClose" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="bg-gray-950 p-3">
            <img id="photoPreviewImage" src="" alt="Evidencia" class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain">
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
