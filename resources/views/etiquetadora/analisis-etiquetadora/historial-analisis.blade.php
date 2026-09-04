@extends('layouts.app')

@section('title', 'Historial de Analisis')

@section('content')
@php
    $registros = method_exists($analisis, 'getCollection') ? $analisis->getCollection() : collect($analisis);
    $totalRegistros = method_exists($analisis, 'total') ? $analisis->total() : $registros->count();
    $conImagenes = $registros->filter(function ($item) {
        $imagenes = $item->evidencia_fotos ?? [];

        if (is_string($imagenes)) {
            $imagenes = json_decode($imagenes, true) ?? [];
        }

        if (!is_array($imagenes)) {
            return false;
        }

        return count(array_filter($imagenes)) > 0;
    })->count();
@endphp

<style>
    .timeline-line {
        background: linear-gradient(180deg, #3b82f6 0%, #60a5fa 100%);
    }

    .timeline-dot {
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    .image-hover-zoom {
        transition: transform 0.3s ease;
    }

    .image-hover-zoom:hover {
        transform: scale(1.05);
    }

    .modal-fade-in {
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .status-badge,
    .history-card {
        transition: all 0.3s ease;
    }

    .status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .history-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .single-image-stage {
        touch-action: pan-y;
        user-select: none;
        -webkit-user-select: none;
        -webkit-user-drag: none;
        cursor: grab;
    }

    .single-image-stage.is-swiping {
        cursor: grabbing;
    }

    .single-image-stage img {
        touch-action: pan-y;
        user-select: none;
        -webkit-user-select: none;
        -webkit-user-drag: none;
    }

    .etiquetadora-history {
        width: 100%;
        overflow-x: hidden;
        overflow-x: clip;
    }

    .etiquetadora-history *,
    .etiquetadora-history *::before,
    .etiquetadora-history *::after {
        box-sizing: border-box;
        min-width: 0;
    }

    .etiquetadora-history :where(h1, h2, h3, h4, p, span, a, button, div) {
        overflow-wrap: anywhere;
    }

    .etiquetadora-history h1 {
        line-height: 1.15;
    }

    .etiquetadora-history .status-badge {
        max-width: 100%;
        white-space: normal;
        text-align: center;
    }

    .etiquetadora-history .history-info-box,
    .etiquetadora-history .history-section-box {
        max-width: 100%;
    }

    .etiquetadora-history .history-value,
    .etiquetadora-history .history-activity {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .etiquetadora-history .evidence-tile {
        aspect-ratio: 4 / 3;
        overflow: hidden;
        border-radius: 0.5rem;
    }

    .etiquetadora-history .evidence-tile img {
        height: 100%;
    }

    @media (max-width: 640px) {
        .etiquetadora-history {
            padding: 1rem 0.75rem;
        }

        .etiquetadora-history .timeline-line {
            left: 0.75rem !important;
        }

        .etiquetadora-history .history-card {
            padding-left: 2.5rem;
        }

        .etiquetadora-history .timeline-dot {
            left: -0.25rem !important;
        }

        .etiquetadora-history .status-badge,
        .etiquetadora-history .history-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="etiquetadora-history max-w-6xl mx-auto px-4 sm:px-6 py-8">
    <div class="mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex min-w-0 items-center gap-4">
            <div class="flex-shrink-0 bg-gradient-to-r from-blue-600 to-blue-700 p-3 rounded-xl shadow-lg shadow-blue-500/30">
                <i class="fas fa-history text-2xl text-white"></i>
            </div>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                    Historial del Analisis
                </h1>
                <p class="mt-1 text-sm font-semibold text-gray-500">
                    {{ $linea->nombre }} | {{ $maquinaLabel }} | {{ $componente->nombre }}
                </p>
            </div>
        </div>

        <a href="{{ route('analisis-etiquetadora.index', ['linea_id' => $linea->id, 'maquina' => $maquina, 'componente_id' => $componente->id]) }}"
           class="group inline-flex w-full sm:w-auto items-center justify-center px-5 py-2.5 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md border border-gray-200">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
        </a>
    </div>

    @if($registros->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="rounded-xl p-5 shadow-sm"
                 style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex-shrink-0 p-3 rounded-lg" style="background-color: rgba(31, 35, 72, 0.1);">
                        <i class="fas fa-clipboard-list" style="color: rgb(31, 35, 72); font-size: 1.25rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium uppercase tracking-wider" style="color: rgb(31, 35, 72);">Total Registros</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($totalRegistros) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl p-5 shadow-sm"
                 style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex-shrink-0 p-3 rounded-lg" style="background-color: rgba(31, 35, 72, 0.1);">
                        <i class="fas fa-images" style="color: rgb(31, 35, 72); font-size: 1.25rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium uppercase tracking-wider" style="color: rgb(31, 35, 72);">Con Evidencia</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($conImagenes) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative">
            <div class="absolute left-6 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-400 to-blue-600 rounded-full timeline-line"></div>

            <div class="space-y-8">
                @foreach($analisis as $index => $item)
                    @php
                        $estado = $item->estado ?? \App\Models\AnalisisEtiquetadora::ESTADO_BUENO;

                        if (\App\Models\AnalisisEtiquetadora::esEstadoCambiado($estado)) {
                            $colorBg = 'bg-blue-100';
                            $colorText = 'text-blue-800';
                            $colorBorder = 'border-blue-200';
                            $badgeIcon = 'fa-exchange-alt';
                            $badgeColor = 'from-blue-500 to-blue-600';
                        } elseif (\App\Models\AnalisisEtiquetadora::esEstadoDanado($estado)) {
                            $colorBg = 'bg-red-100';
                            $colorText = 'text-red-800';
                            $colorBorder = 'border-red-200';
                            $badgeIcon = 'fa-times-circle';
                            $badgeColor = 'from-red-500 to-red-600';
                        } elseif (\App\Models\AnalisisEtiquetadora::esEstadoRequiereRevision($estado)) {
                            $colorBg = 'bg-yellow-100';
                            $colorText = 'text-yellow-800';
                            $colorBorder = 'border-yellow-200';
                            $badgeIcon = 'fa-tools';
                            $badgeColor = 'from-yellow-500 to-yellow-600';
                        } elseif (\App\Models\AnalisisEtiquetadora::esEstadoDesgaste($estado)) {
                            $colorBg = 'bg-orange-100';
                            $colorText = 'text-orange-800';
                            $colorBorder = 'border-orange-200';
                            $badgeIcon = 'fa-exclamation-triangle';
                            $badgeColor = 'from-orange-500 to-orange-600';
                        } else {
                            $colorBg = 'bg-green-100';
                            $colorText = 'text-green-800';
                            $colorBorder = 'border-green-200';
                            $badgeIcon = 'fa-check-circle';
                            $badgeColor = 'from-green-500 to-green-600';
                        }

                        $imagenes = $item->evidencia_fotos ?? [];
                        if (is_string($imagenes)) {
                            $imagenes = json_decode($imagenes, true) ?? [];
                        } elseif (!is_array($imagenes)) {
                            $imagenes = [];
                        }

                        $imagenes = array_values(array_filter($imagenes));
                        $totalImagenes = count($imagenes);
                        $numeroOrdenEtiqueta = $item->numero_orden ? 'Orden #' . $item->numero_orden : 'Sin orden';
                        $actividadTexto = trim((string) preg_replace('/\s+/', ' ', (string) $item->actividad));
                    @endphp

                    <div class="relative pl-12 sm:pl-16 history-card">
                        <div class="absolute left-3 top-6 w-8 h-8 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full border-4 border-white shadow-lg timeline-dot flex items-center justify-center text-white text-xs font-bold">
                            {{ method_exists($analisis, 'firstItem') ? ($analisis->firstItem() + $index) : ($index + 1) }}
                        </div>

                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300">
                            <div class="bg-gradient-to-r from-gray-50 to-white px-4 sm:px-6 py-4 border-b border-gray-100">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="w-10 h-10 flex-shrink-0 rounded-xl bg-gradient-to-r {{ $badgeColor }} flex items-center justify-center shadow-md">
                                            <i class="fas {{ $badgeIcon }} text-white"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-500">
                                                    <i class="far fa-calendar-alt mr-1"></i>{{ $item->fecha_analisis?->format('d/m/Y') ?? 'Sin fecha' }}
                                                </span>
                                            </div>
                                            <h3 class="font-bold text-lg text-gray-800">
                                                {{ $numeroOrdenEtiqueta }}
                                            </h3>
                                        </div>
                                    </div>

                                    <span class="status-badge inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold {{ $colorBg }} {{ $colorText }} border {{ $colorBorder }} shadow-sm">
                                        <i class="fas {{ $badgeIcon }} flex-shrink-0"></i>
                                        {{ $estado }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                                    <div class="history-info-box bg-gradient-to-br" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border-color: rgba(31, 35, 72, 0.2); border-width: 1px; border-style: solid; border-radius: 0.75rem; padding: 1rem;">
                                        <div class="flex min-w-0 items-center gap-2 mb-2">
                                            <i class="fas fa-tags flex-shrink-0" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Linea</span>
                                        </div>
                                        <p class="history-value font-medium text-gray-800">{{ $item->linea->nombre ?? 'N/A' }}</p>
                                    </div>

                                    <div class="history-info-box bg-gradient-to-br" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border-color: rgba(31, 35, 72, 0.2); border-width: 1px; border-style: solid; border-radius: 0.75rem; padding: 1rem;">
                                        <div class="flex min-w-0 items-center gap-2 mb-2">
                                            <i class="fas fa-cog flex-shrink-0" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Componente</span>
                                        </div>
                                        <p class="history-value font-medium text-gray-800">{{ $item->componente->nombre ?? 'N/A' }}</p>
                                    </div>

                                    <div class="history-info-box bg-gradient-to-br" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border-color: rgba(31, 35, 72, 0.2); border-width: 1px; border-style: solid; border-radius: 0.75rem; padding: 1rem;">
                                        <div class="flex min-w-0 items-center gap-2 mb-2">
                                            <i class="fas fa-industry flex-shrink-0" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Maquina</span>
                                        </div>
                                        <p class="history-value font-medium text-gray-800">{{ $item->maquina ?: $maquinaLabel }}</p>
                                    </div>

                                    <div class="history-info-box bg-gradient-to-br" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border-color: rgba(31, 35, 72, 0.2); border-width: 1px; border-style: solid; border-radius: 0.75rem; padding: 1rem;">
                                        <div class="flex min-w-0 items-center gap-2 mb-2">
                                            <i class="fas fa-user-check flex-shrink-0" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Responsable</span>
                                        </div>
                                        <p class="history-value font-medium text-gray-800">{{ $item->usuario->name ?? 'Usuario no registrado' }}</p>
                                    </div>
                                </div>

                                <div class="history-section-box bg-gradient-to-br from-gray-50 to-white rounded-xl p-4 sm:p-5 border border-gray-200 mb-4">
                                    <div class="flex min-w-0 items-center gap-2 mb-3">
                                        <div class="w-6 h-6 flex-shrink-0 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-sticky-note text-blue-600 text-xs"></i>
                                        </div>
                                        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Actividad</h4>
                                    </div>
                                    <div class="history-activity prose prose-sm max-w-none text-gray-700 leading-relaxed pl-2 border-l-4 border-blue-400">{{ $actividadTexto !== '' ? $actividadTexto : 'Sin actividad registrada.' }}</div>
                                </div>

                                @if($totalImagenes > 0)
                                    <div class="history-section-box bg-gradient-to-br from-indigo-50 to-white rounded-xl p-4 sm:p-5 border border-indigo-100">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <div class="w-6 h-6 flex-shrink-0 rounded-lg bg-indigo-100 flex items-center justify-center">
                                                    <i class="fas fa-images text-indigo-600 text-xs"></i>
                                                </div>
                                                <h4 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">
                                                    Evidencia Fotografica
                                                </h4>
                                            </div>
                                            <span class="inline-flex w-fit max-w-full items-center px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium">
                                                {{ $totalImagenes }} {{ $totalImagenes === 1 ? 'imagen' : 'imagenes' }}
                                            </span>
                                        </div>

                                        @php
                                            $imagenesJson = e(json_encode(array_values($imagenes), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');
                                        @endphp

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                            @foreach($imagenes as $imgIndex => $imagen)
                                                @php
                                                    $rutaImagen = asset('storage/' . ltrim(str_replace('\\', '/', $imagen), '/'));
                                                @endphp
                                                <div class="evidence-tile relative group cursor-pointer history-image-trigger"
                                                     role="button"
                                                     tabindex="0"
                                                     data-image-src="{{ $rutaImagen }}"
                                                     data-image-caption="Evidencia {{ $imgIndex + 1 }} - {{ $numeroOrdenEtiqueta }}"
                                                     data-image-index="{{ $imgIndex + 1 }}"
                                                     data-image-total="{{ $totalImagenes }}"
                                                     data-image-order="{{ $item->numero_orden ?: 'Sin orden' }}"
                                                     data-image-list="{!! $imagenesJson !!}">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg z-10"></div>
                                                    <img src="{{ $rutaImagen }}"
                                                         alt="Evidencia {{ $imgIndex + 1 }}"
                                                         class="w-full object-cover rounded-lg border-2 border-white shadow-md group-hover:shadow-xl transition-all duration-300 image-hover-zoom">
                                                    <div class="absolute top-2 left-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                        <i class="fas fa-search-plus mr-1"></i>
                                                        Ver
                                                    </div>
                                                    <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center z-20">
                                                        {{ $imgIndex + 1 }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($totalImagenes > 4)
                                            <div class="mt-4 text-center">
                                                <button type="button"
                                                        class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition text-sm font-medium history-gallery-trigger"
                                                        data-image-list="{!! $imagenesJson !!}"
                                                        data-image-order="{{ $item->numero_orden ?: 'Sin orden' }}">
                                                    <i class="fas fa-images"></i>
                                                    Ver todas las imagenes ({{ $totalImagenes }})
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="history-section-box bg-gradient-to-br from-gray-50 to-white rounded-xl p-4 sm:p-5 border border-gray-200">
                                        <div class="flex min-w-0 items-center justify-center gap-2 text-center text-gray-400">
                                            <i class="fas fa-image flex-shrink-0 text-lg"></i>
                                            <span class="text-sm">Sin imagenes adjuntas</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-4 flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('analisis-etiquetadora.edit', $item) }}"
                                       class="history-action inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg text-sm font-medium"
                                       style="background: linear-gradient(to right, rgb(31, 35, 72), rgb(47, 53, 102)); color: white;">
                                        <i class="fas fa-edit flex-shrink-0"></i>
                                        Editar Registro
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8">
            {{ $analisis->links() }}
        </div>
    @else
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg border border-gray-200 p-6 sm:p-12 text-center">
            <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-folder-open text-4xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No hay registros disponibles</h3>
            <p class="text-gray-500 mb-6">Este componente todavia no tiene historial de analisis para la maquina seleccionada.</p>
            <a href="{{ route('analisis-etiquetadora.create', ['linea' => $linea->id, 'maquina' => $maquina, 'componente_id' => $componente->id]) }}"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                <i class="fas fa-plus-circle"></i>
                Nuevo Analisis
            </a>
        </div>
    @endif
</div>

<div id="imageModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300" onclick="closeImageModal()">
    <div id="imageSwipeStage" class="relative max-w-6xl w-full max-h-[90vh] flex items-center justify-center modal-fade-in single-image-stage" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()"
                class="absolute top-2 right-2 sm:top-4 sm:right-4 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl flex items-center justify-center backdrop-blur-sm border border-white/30 transition-all z-10 group">
            <i class="fas fa-times group-hover:rotate-90 transition-transform"></i>
        </button>

        <button id="prevImageBtn" onclick="event.stopPropagation(); navigateImage(-1)"
                class="absolute left-2 sm:left-4 top-1/2 transform -translate-y-1/2 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-sm border border-white/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed hidden z-10">
            <i class="fas fa-chevron-left text-xl"></i>
        </button>

        <div class="relative max-w-full">
            <img id="modalImage" src="" alt="Imagen ampliada" draggable="false" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl border-4 border-white/20">
            <div id="imageCounter" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/50 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-medium border border-white/20">
                <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
            </div>
        </div>

        <button id="nextImageBtn" onclick="event.stopPropagation(); navigateImage(1)"
                class="absolute right-2 sm:right-4 top-1/2 transform -translate-y-1/2 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-sm border border-white/30 transition-all disabled:opacity-30 disabled:cursor-not-allowed hidden z-10">
            <i class="fas fa-chevron-right text-xl"></i>
        </button>

        <p id="modalCaption" class="absolute bottom-20 left-1/2 max-w-[calc(100vw-2rem)] transform -translate-x-1/2 text-center text-white text-sm bg-black/50 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20"></p>
    </div>
</div>

<div id="galleryModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300" onclick="closeGalleryModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden" onclick="event.stopPropagation()">
        <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-700 text-white px-4 sm:px-8 py-4 sm:py-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                    <div class="flex-shrink-0 bg-white/20 p-3 rounded-xl">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-lg sm:text-xl leading-tight">
                            <span id="galleryTitle">Galeria de Imagenes</span>
                        </h3>
                        <p class="text-indigo-100 text-sm"><span id="galleryOrderNumber"></span></p>
                    </div>
                </div>
                <button onclick="closeGalleryModal()"
                        class="w-10 h-10 flex-shrink-0 rounded-xl bg-white/20 hover:bg-white/30 transition-all flex items-center justify-center group">
                    <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                </button>
            </div>
        </div>
        <div class="p-4 sm:p-6 overflow-auto max-h-[calc(90vh-100px)]">
            <div id="galleryGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
        </div>
    </div>
</div>

<script>
let currentImages = [];
let currentImageIndex = 0;
let currentOrderNumber = '';
let imageSwipeState = null;
const IMAGE_SWIPE_DISTANCE = 50;
const IMAGE_SWIPE_VERTICAL_TOLERANCE = 1.25;
const EVIDENCE_STORAGE_BASE_URL = @json(rtrim(asset('storage'), '/') . '/');

function resolveEvidenceUrl(path) {
    const rawPath = String(path ?? '').trim().replace(/\\/g, '/');

    if (!rawPath) return '';
    if (/^(https?:)?\/\//i.test(rawPath) || rawPath.startsWith('/') || rawPath.startsWith('data:')) return rawPath;

    const cleanPath = rawPath
        .replace(/^\/+/, '')
        .replace(/^public\//, '')
        .replace(/^app\/public\//, '')
        .replace(/^storage\/app\/public\//, '')
        .replace(/^public\/storage\//, '')
        .replace(/^storage\//, '');

    return EVIDENCE_STORAGE_BASE_URL + cleanPath;
}

function normalizeImageList(images) {
    if (!images) return [];

    if (typeof images === 'string') {
        const value = images.trim();
        if (!value || value === 'null' || value === '[]') return [];

        if ((value.startsWith('[') && value.endsWith(']')) || (value.startsWith('{') && value.endsWith('}'))) {
            try {
                return normalizeImageList(JSON.parse(value));
            } catch (error) {
                return [value];
            }
        }

        return [value.replace(/\\/g, '/')];
    }

    if (typeof images === 'object' && !Array.isArray(images)) {
        return normalizeImageList(Object.values(images));
    }

    if (!Array.isArray(images)) return [];

    return images
        .flatMap((item) => normalizeImageList(item))
        .map((item) => String(item).trim().replace(/\\/g, '/'))
        .filter((item) => item.length > 0);
}

function parseImageList(value) {
    try {
        return normalizeImageList(JSON.parse(value || '[]'));
    } catch (error) {
        return [];
    }
}

function openHistoryImageTrigger(trigger) {
    const images = parseImageList(trigger.dataset.imageList);
    const index = Number.parseInt(trigger.dataset.imageIndex || '1', 10);
    const total = Number.parseInt(trigger.dataset.imageTotal || String(images.length || 1), 10);

    openImageModal(
        trigger.dataset.imageSrc || '',
        trigger.dataset.imageCaption || `Evidencia ${index}`,
        index,
        total,
        images,
        trigger.dataset.imageOrder || ''
    );
}

function openImageModal(imageSrc, caption, index, total, imagenes = null, orden = '') {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    const prevBtn = document.getElementById('prevImageBtn');
    const nextBtn = document.getElementById('nextImageBtn');
    const counter = document.getElementById('imageCounter');
    const currentIndexSpan = document.getElementById('currentImageIndex');
    const totalSpan = document.getElementById('totalImages');

    currentImages = Array.isArray(imagenes) ? normalizeImageList(imagenes) : [imageSrc];
    currentOrderNumber = orden || currentOrderNumber;
    currentImageIndex = Math.max(0, index - 1);

    modalImg.src = resolveEvidenceUrl(currentImages[currentImageIndex] || imageSrc);
    modalCaption.textContent = caption;

    if (total > 1) {
        prevBtn.classList.remove('hidden');
        nextBtn.classList.remove('hidden');
        counter.classList.remove('hidden');
        currentIndexSpan.textContent = index;
        totalSpan.textContent = total;
    } else {
        prevBtn.classList.add('hidden');
        nextBtn.classList.add('hidden');
        counter.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function navigateImage(direction) {
    if (!currentImages || currentImages.length === 0) return;

    currentImageIndex = (currentImageIndex + direction + currentImages.length) % currentImages.length;
    const newSrc = resolveEvidenceUrl(currentImages[currentImageIndex]);

    document.getElementById('modalImage').src = newSrc;
    document.getElementById('modalCaption').textContent = `Evidencia ${currentImageIndex + 1} - ${currentOrderNumber}`;
    document.getElementById('currentImageIndex').textContent = currentImageIndex + 1;
}

function openAllImages(imagenes, orden) {
    currentImages = normalizeImageList(imagenes);
    currentOrderNumber = orden;

    const modal = document.getElementById('galleryModal');
    const grid = document.getElementById('galleryGrid');
    const orderSpan = document.getElementById('galleryOrderNumber');

    orderSpan.textContent = orden;
    grid.innerHTML = '';

    currentImages.forEach((path, index) => {
        const rutaImagen = resolveEvidenceUrl(path);
        const item = document.createElement('div');
        item.className = 'relative group cursor-pointer';
        item.innerHTML = `
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg z-10"></div>
            <img src="${rutaImagen}" alt="Evidencia ${index + 1}"
                 class="w-full h-40 object-cover rounded-lg border-2 border-white shadow-md group-hover:shadow-xl transition-all duration-300 image-hover-zoom">
            <div class="absolute top-2 left-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <i class="fas fa-search-plus mr-1"></i>
                Ampliar
            </div>
            <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center z-20">
                ${index + 1}
            </div>
        `;
        item.onclick = () => {
            closeGalleryModal();
            openImageModal(rutaImagen, `Evidencia ${index + 1} - ${orden}`, index + 1, currentImages.length, currentImages, orden);
        };
        grid.appendChild(item);
    });

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    cancelImageSwipe({ currentTarget: document.getElementById('imageSwipeStage') });
    document.body.style.overflow = 'auto';
}

function closeGalleryModal() {
    const modal = document.getElementById('galleryModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

function getSwipePoint(event) {
    const source = event.changedTouches?.[0] || event.touches?.[0] || event;

    return {
        x: source.clientX ?? 0,
        y: source.clientY ?? 0,
    };
}

function startImageSwipe(event) {
    if (event.target?.closest('button') || currentImages.length <= 1) return;

    if (event.pointerId !== undefined && event.currentTarget?.setPointerCapture) {
        try {
            event.currentTarget.setPointerCapture(event.pointerId);
        } catch (error) {}
    }

    const point = getSwipePoint(event);
    imageSwipeState = {
        startX: point.x,
        startY: point.y,
        lastX: point.x,
        lastY: point.y,
    };

    event.currentTarget?.classList.add('is-swiping');
}

function moveImageSwipe(event) {
    if (!imageSwipeState) return;

    const point = getSwipePoint(event);
    const deltaX = point.x - imageSwipeState.startX;
    const deltaY = point.y - imageSwipeState.startY;

    imageSwipeState.lastX = point.x;
    imageSwipeState.lastY = point.y;

    if (Math.abs(deltaX) > Math.abs(deltaY) && event.cancelable) {
        event.preventDefault();
    }
}

function finishImageSwipe(event) {
    if (!imageSwipeState) return;

    const point = getSwipePoint(event);
    const endX = point.x ?? imageSwipeState.lastX;
    const endY = point.y ?? imageSwipeState.lastY;
    const deltaX = endX - imageSwipeState.startX;
    const deltaY = endY - imageSwipeState.startY;
    const isHorizontalSwipe = Math.abs(deltaX) >= IMAGE_SWIPE_DISTANCE
        && Math.abs(deltaX) > Math.abs(deltaY) * IMAGE_SWIPE_VERTICAL_TOLERANCE;

    if (isHorizontalSwipe) {
        navigateImage(deltaX < 0 ? 1 : -1);
    }

    cancelImageSwipe(event);
}

function cancelImageSwipe(event) {
    const stage = event.currentTarget;

    imageSwipeState = null;
    if (event.pointerId !== undefined && stage?.releasePointerCapture) {
        try {
            stage.releasePointerCapture(event.pointerId);
        } catch (error) {}
    }
    stage?.classList.remove('is-swiping');
}

function setupImageSwipe() {
    const stage = document.getElementById('imageSwipeStage');

    if (!stage) return;

    if (window.PointerEvent) {
        stage.addEventListener('pointerdown', startImageSwipe);
        stage.addEventListener('pointermove', moveImageSwipe);
        stage.addEventListener('pointerup', finishImageSwipe);
        stage.addEventListener('pointercancel', cancelImageSwipe);
        return;
    }

    stage.addEventListener('touchstart', startImageSwipe, { passive: true });
    stage.addEventListener('touchmove', moveImageSwipe, { passive: false });
    stage.addEventListener('touchend', finishImageSwipe);
    stage.addEventListener('touchcancel', cancelImageSwipe);
}

document.addEventListener('keydown', function(e) {
    const imageModal = document.getElementById('imageModal');
    const galleryModal = document.getElementById('galleryModal');

    if (e.key === 'Escape') {
        closeImageModal();
        closeGalleryModal();
    }

    if (e.key === 'ArrowLeft' && !imageModal.classList.contains('hidden') && currentImages.length > 0) {
        navigateImage(-1);
    }

    if (e.key === 'ArrowRight' && !imageModal.classList.contains('hidden') && currentImages.length > 0) {
        navigateImage(1);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    setupImageSwipe();

    document.querySelectorAll('.history-image-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => openHistoryImageTrigger(trigger));
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openHistoryImageTrigger(trigger);
            }
        });
    });

    document.querySelectorAll('.history-gallery-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            openAllImages(parseImageList(trigger.dataset.imageList), trigger.dataset.imageOrder || '');
        });
    });
});
</script>
@endsection
