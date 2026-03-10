@extends('layouts.app')

@section('title', 'Historial de Análisis - Pasteurizadora')

@section('content')
<style>
    .timeline-line {
        background: linear-gradient(180deg, #3b82f6 0%, #60a5fa 100%);
    }
    
    .timeline-dot {
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        animation: pulse-dot 2s infinite;
    }
    
    @keyframes pulse-dot {
        0% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
        }
        70% {
            box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
        }
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
    
    .status-badge {
        transition: all 0.3s ease;
    }
    
    .status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .history-card {
        transition: all 0.3s ease;
    }
    
    .history-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .image-counter {
        background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.5));
        backdrop-filter: blur(4px);
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }

    .linea-btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .linea-btn i {
        margin-right: 8px;
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-2px);
    }

    .linea-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-color: #2563eb;
        color: white;
    }

    .linea-btn.active i {
        color: white;
    }

    .filters-container {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }

    .filters-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
    {{-- Header --}}
    <div class="mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-3 rounded-xl shadow-lg shadow-blue-500/30">
                <i class="fas fa-history text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                    Historial de Registros
                </h1>
                <p class="text-gray-500 mt-1">Pasteurizadora - Seguimiento de análisis</p>
            </div>
        </div>

        <a href="{{ route('analisis-pasteurizadora.index') }}"
           class="group inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md border border-gray-200">
            <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
        </a>
    </div>
    </div>

    @if($analisis->count() > 0)
        {{-- Estadísticas rápidas --}}
        @php
            $totalRegistros = $analisis->total();
            $conImagenes = $analisis->filter(function($item) {
                return $item->tiene_imagenes;
            })->count();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="rounded-xl p-5 shadow-sm" 
                style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg" style="background-color: rgba(31, 35, 72, 0.1);">
                        <i class="fas fa-clipboard-list" style="color: rgb(31, 35, 72); font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wider" style="color: rgb(31, 35, 72);">Total Registros</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $totalRegistros }}</p>
                    </div>
                </div>
            </div>
            
            <div class="rounded-xl p-5 shadow-sm" 
                style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg" style="background-color: rgba(31, 35, 72, 0.1);">
                        <i class="fas fa-images" style="color: rgb(31, 35, 72); font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wider" style="color: rgb(31, 35, 72);">Con Evidencia</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $conImagenes }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="relative">
            <div class="absolute left-6 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-400 to-blue-600 rounded-full timeline-line"></div>

            <div class="space-y-8">
                @foreach($analisis as $index => $item)
                    @php
                        $estado = $item->estado ?? 'Buen estado';

                        if ($estado === 'Cambiado') {
                            $colorBg = 'bg-blue-100';
                            $colorText = 'text-blue-800';
                            $colorBorder = 'border-blue-200';
                            $badgeIcon = 'fa-exchange-alt';
                            $badgeColor = 'from-blue-500 to-blue-600';
                        } elseif ($estado === 'Dañado - Requiere cambio') {
                            $colorBg = 'bg-red-100';
                            $colorText = 'text-red-800';
                            $colorBorder = 'border-red-200';
                            $badgeIcon = 'fa-times-circle';
                            $badgeColor = 'from-red-500 to-red-600';
                        } elseif (str_contains($estado, 'Desgaste')) {
                            $colorBg = 'bg-yellow-100';
                            $colorText = 'text-yellow-800';
                            $colorBorder = 'border-yellow-200';
                            $badgeIcon = 'fa-exclamation-triangle';
                            $badgeColor = 'from-yellow-500 to-yellow-600';
                        } else {
                            $colorBg = 'bg-green-100';
                            $colorText = 'text-green-800';
                            $colorBorder = 'border-green-200';
                            $badgeIcon = 'fa-check-circle';
                            $badgeColor = 'from-green-500 to-green-600';
                        }

                        $imagenes = $item->evidencia_fotos ?? [];
                        $totalImagenes = count($imagenes);
                    @endphp

                    <div class="relative pl-16 history-card">
                        <div class="absolute left-3 top-6 w-8 h-8 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full border-4 border-white shadow-lg timeline-dot flex items-center justify-center text-white text-xs font-bold">
                            {{ $analisis->firstItem() + $index }}
                        </div>

                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300">
                            <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-100">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-r {{ $badgeColor }} flex items-center justify-center shadow-md">
                                            <i class="fas {{ $badgeIcon }} text-white"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-500">
                                                    <i class="far fa-calendar-alt mr-1"></i>{{ $item->fecha_formateada }}
                                                </span>
                                                @if($item->created_at)
                                                    <span class="text-gray-300">|</span>
                                                    <span class="text-sm text-gray-500">
                                                        <i class="far fa-clock mr-1"></i>{{ $item->hora_formateada }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h3 class="font-bold text-lg text-gray-800">
                                                Orden #{{ $item->numero_orden }}
                                            </h3>
                                        </div>
                                    </div>
                                    
                                    <span class="status-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold {{ $colorBg }} {{ $colorText }} border {{ $colorBorder }} shadow-sm">
                                        <i class="fas {{ $badgeIcon }}"></i>
                                        {{ $estado }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                    <div class="bg-gradient-to-br p-4 rounded-lg" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-temperature-high" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Línea</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->linea->nombre ?? 'N/A' }}</p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br p-4 rounded-lg" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-cubes" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Módulo</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->modulo_nombre }}</p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br p-4 rounded-lg" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-cog" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Componente</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->componente_nombre }}</p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br p-4 rounded-lg" style="background: linear-gradient(to bottom right, rgba(31, 35, 72, 0.05), white); border: 1px solid rgba(31, 35, 72, 0.2);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-arrows-alt-h" style="color: rgb(31, 35, 72);"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider" style="color: rgb(31, 35, 72);">Lado</span>
                                        </div>
                                        @if($item->lado)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $item->lado_clase }}">
                                                <i class="fas {{ $item->lado_icono }} mr-1"></i>
                                                {{ $item->lado }}
                                            </span>
                                        @else
                                            <p class="text-gray-400">No especificado</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-5 border border-gray-200 mb-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-sticky-note text-blue-600 text-xs"></i>
                                        </div>
                                        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Actividad</h4>
                                    </div>
                                    <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line leading-relaxed pl-2 border-l-4 border-blue-400">
                                        {{ $item->actividad }}
                                    </div>
                                </div>

                                @if($totalImagenes > 0)
                                    <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl p-5 border border-indigo-100">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center">
                                                    <i class="fas fa-images text-indigo-600 text-xs"></i>
                                                </div>
                                                <h4 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">
                                                    Evidencia Fotográfica
                                                </h4>
                                            </div>
                                            <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium">
                                                {{ $totalImagenes }} {{ $totalImagenes == 1 ? 'imagen' : 'imágenes' }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                            @foreach($imagenes as $imgIndex => $imagen)
                                                @php
                                                    $rutaImagen = asset('storage/' . $imagen);
                                                @endphp
                                                <div class="relative group cursor-pointer" onclick="openImageModal('{{ $rutaImagen }}', 'Evidencia {{ $imgIndex + 1 }} - Orden #{{ $item->numero_orden }}', {{ $imgIndex + 1 }}, {{ $totalImagenes }})">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg z-10"></div>
                                                    <img src="{{ $rutaImagen }}"
                                                         alt="Evidencia {{ $imgIndex + 1 }}"
                                                         class="w-full h-28 object-cover rounded-lg border-2 border-white shadow-md group-hover:shadow-xl transition-all duration-300 image-hover-zoom">
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
                                                <button onclick="openAllImages(@json($imagenes), '{{ $item->numero_orden }}')" 
                                                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition text-sm font-medium">
                                                    <i class="fas fa-images"></i>
                                                    Ver todas las imágenes ({{ $totalImagenes }})
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-5 border border-gray-200">
                                        <div class="flex items-center justify-center gap-2 text-gray-400">
                                            <i class="fas fa-image text-lg"></i>
                                            <span class="text-sm">Sin imágenes adjuntas</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-4 flex justify-end gap-2">
                                    <a href="{{ route('analisis-pasteurizadora.edit', $item->id) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg text-sm font-medium"
                                       style="background: linear-gradient(to right, rgb(31, 35, 72), rgb(47, 53, 102)); color: white;">
                                        <i class="fas fa-edit"></i>
                                        Editar Registro
                                    </a>
                                    <a href="{{ route('analisis-pasteurizadora.show', $item->id) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-300 shadow-md hover:shadow-lg text-sm font-medium">
                                        <i class="fas fa-eye"></i>
                                        Ver Detalle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Paginación --}}
        <div class="mt-8">
            {{ $analisis->appends(request()->query())->links() }}
        </div>
    @else
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg border border-gray-200 p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-folder-open text-4xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No hay registros disponibles</h3>
            <p class="text-gray-500 mb-6">Comienza realizando un nuevo análisis para ver el historial.</p>
            <a href="{{ route('analisis-pasteurizadora.select-linea') }}" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                <i class="fas fa-plus-circle"></i>
                Nuevo Análisis
            </a>
        </div>
    @endif
</div>

{{-- Modales (reutilizar los mismos del index) --}}
@include('analisis-pasteurizadora.partials.image-modals')
@endsection

@section('scripts')
<script>
let currentImages = [];
let currentImageIndex = 0;
let currentOrderNumber = '';

function openImageModal(imageSrc, caption, index, total) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    const prevBtn = document.getElementById('prevImageBtn');
    const nextBtn = document.getElementById('nextImageBtn');
    const counter = document.getElementById('imageCounter');
    const currentIndexSpan = document.getElementById('currentImageIndex');
    const totalSpan = document.getElementById('totalImages');

    modalImg.src = imageSrc;
    modalCaption.textContent = caption;
    
    if (total > 1) {
        prevBtn.classList.remove('hidden');
        nextBtn.classList.remove('hidden');
        counter.classList.remove('hidden');
        currentIndexSpan.textContent = index;
        totalSpan.textContent = total;
        currentImageIndex = index - 1;
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
    const newSrc = `{{ Storage::url('') }}${currentImages[currentImageIndex]}`;
    const modalImg = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    const currentIndexSpan = document.getElementById('currentImageIndex');
    
    modalImg.src = newSrc;
    modalCaption.textContent = `Evidencia ${currentImageIndex + 1} - Orden #${currentOrderNumber}`;
    currentIndexSpan.textContent = currentImageIndex + 1;
}

function openAllImages(imagenes, orden) {
    currentImages = Array.isArray(imagenes) ? imagenes : [];
    currentOrderNumber = orden;
    
    const modal = document.getElementById('galleryModal');
    const grid = document.getElementById('galleryGrid');
    const orderSpan = document.getElementById('galleryOrderNumber');
    
    orderSpan.textContent = orden;
    grid.innerHTML = '';
    
    currentImages.forEach((path, index) => {
        const rutaImagen = `{{ Storage::url('') }}${path}`;
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
            openImageModal(rutaImagen, `Evidencia ${index + 1} - Orden #${orden}`, index + 1, currentImages.length);
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
    document.body.style.overflow = 'auto';
}

function closeGalleryModal() {
    const modal = document.getElementById('galleryModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
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
</script>
@endsection