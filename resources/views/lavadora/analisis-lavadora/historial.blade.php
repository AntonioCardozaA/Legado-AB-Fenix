@extends('layouts.app')

@section('title', 'Historial de Análisis')

@section('content')
<style>
    /* Estilos personalizados adicionales */
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
</style>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
    {{-- Header mejorado con gradiente y efectos --}}
    <div class="mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-3 rounded-xl shadow-lg shadow-blue-500/30">
                <i class="fas fa-history text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                    Historial de Registros
                </h1>
            </div>
        </div>

        <a href="{{ route('analisis-lavadora.index') }}"
           class="group inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md border border-gray-200">
            <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
        </a>
    </div>

    @if($analisis->count() > 0)
        {{-- Estadísticas rápidas --}}
        @php
            $totalRegistros = $analisis->count();
            $conImagenes = $analisis->filter(function($item) {
                $imagenes = $item->evidencia_fotos ?? null;
                if (is_string($imagenes)) {
                    $imagenes = json_decode($imagenes, true) ?? [];
                } elseif (is_array($imagenes)) {
                    $imagenes = $imagenes;
                } else {
                    $imagenes = [];
                }
                return count($imagenes) > 0;
            })->count();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl p-5 border border-blue-100 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-blue-600 uppercase tracking-wider">Total Registros</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $totalRegistros }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-emerald-50 to-white rounded-xl p-5 border border-emerald-100 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="bg-emerald-100 p-3 rounded-lg">
                        <i class="fas fa-images text-emerald-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-emerald-600 uppercase tracking-wider">Con Evidencia</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $conImagenes }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline mejorado --}}
        <div class="relative">
            {{-- Línea de tiempo vertical --}}
            <div class="absolute left-6 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-400 to-blue-600 rounded-full timeline-line"></div>

            <div class="space-y-8">
                @foreach($analisis as $index => $item)
                    @php
                        $estado = $item->estado ?? 'Buen estado';

                        // Configuración de colores y badges según estado
                        if (str_contains($estado, 'Dañado - Cambiado')) {
                            $colorBg = 'bg-blue-100';
                            $colorText = 'text-blue-800';
                            $colorBorder = 'border-blue-200';
                            $colorIcon = 'text-blue-600';
                            $badgeIcon = 'fa-exchange-alt';
                            $badgeColor = 'from-blue-500 to-blue-600';
                        } elseif (str_contains($estado, 'Dañado')) {
                            $colorBg = 'bg-red-100';
                            $colorText = 'text-red-800';
                            $colorBorder = 'border-red-200';
                            $colorIcon = 'text-red-600';
                            $badgeIcon = 'fa-times-circle';
                            $badgeColor = 'from-red-500 to-red-600';
                        } elseif (str_contains($estado, 'Desgaste')) {
                            $colorBg = 'bg-yellow-100';
                            $colorText = 'text-yellow-800';
                            $colorBorder = 'border-yellow-200';
                            $colorIcon = 'text-yellow-600';
                            $badgeIcon = 'fa-exclamation-triangle';
                            $badgeColor = 'from-yellow-500 to-yellow-600';
                        } else {
                            $colorBg = 'bg-green-100';
                            $colorText = 'text-green-800';
                            $colorBorder = 'border-green-200';
                            $colorIcon = 'text-green-600';
                            $badgeIcon = 'fa-check-circle';
                            $badgeColor = 'from-green-500 to-green-600';
                        }

                        // Procesar imágenes
                        $imagenes = $item->evidencia_fotos ?? null;
                        if (is_string($imagenes)) {
                            $imagenes = json_decode($imagenes, true) ?? [];
                        } elseif (is_array($imagenes)) {
                            $imagenes = $imagenes;
                        } else {
                            $imagenes = [];
                        }
                        
                        $totalImagenes = count($imagenes);
                    @endphp

                    <div class="relative pl-16 history-card">
                        {{-- Dot del timeline con número --}}
                        <div class="absolute left-3 top-6 w-8 h-8 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full border-4 border-white shadow-lg timeline-dot flex items-center justify-center text-white text-xs font-bold">
                            {{ $index + 1 }}
                        </div>

                        {{-- Tarjeta principal --}}
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300">
                            {{-- Header con gradiente --}}
                            <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-100">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-r {{ $badgeColor }} flex items-center justify-center shadow-md">
                                            <i class="fas {{ $badgeIcon }} text-white"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-500">
                                                    <i class="far fa-calendar-alt mr-1"></i>{{ $item->fecha_analisis?->format('d/m/Y') }}
                                                </span>
                                                <span class="text-gray-300">|</span>
                                                <span class="text-sm text-gray-500">
                                                    <i class="far fa-clock mr-1"></i>{{ $item->fecha_analisis?->format('H:i') }}
                                                </span>
                                            </div>
                                            <h3 class="font-bold text-lg text-gray-800">
                                                Orden #{{ $item->numero_orden }}
                                            </h3>
                                        </div>
                                    </div>
                                    
                                    {{-- Badge de estado mejorado --}}
                                    <span class="status-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold {{ $colorBg }} {{ $colorText }} border {{ $colorBorder }} shadow-sm">
                                        <i class="fas {{ $badgeIcon }}"></i>
                                        {{ $estado }}
                                    </span>
                                </div>
                            </div>

                            {{-- Cuerpo de la tarjeta --}}
                            <div class="p-6">
                                {{-- Grid de información --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl p-4 border border-blue-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-washing-machine text-blue-600"></i>
                                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Lavadora</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->linea->nombre ?? 'N/A' }}</p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-4 border border-purple-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-cog text-purple-600"></i>
                                            <span class="text-xs font-semibold text-purple-600 uppercase tracking-wider">Componente</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->componente->nombre ?? 'N/A' }}</p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br from-amber-50 to-white rounded-xl p-4 border border-amber-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-compress-alt text-amber-600"></i>
                                            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Reductor</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->reductor }}</p>
                                    </div>
                                </div>

                                {{-- Actividad con estilo mejorado --}}
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

                                {{-- Sección de imágenes mejorada --}}
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
                                        
                                        {{-- Botón para ver todas las imágenes --}}
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

                                {{-- Acciones --}}
                                <div class="mt-4 flex justify-end gap-2">
                                    <a href="{{ route('analisis-lavadora.edit', $item->id) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg hover:from-amber-600 hover:to-amber-700 transition-all duration-300 shadow-md hover:shadow-lg text-sm font-medium">
                                        <i class="fas fa-edit"></i>
                                        Editar Registro
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        {{-- Estado vacío mejorado --}}
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg border border-gray-200 p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-folder-open text-4xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No hay registros disponibles</h3>
            <p class="text-gray-500 mb-6">Comienza realizando un nuevo análisis para ver el historial.</p>
            <a href="{{ route('analisis-lavadora.select-linea') }}" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                <i class="fas fa-plus-circle"></i>
                Nuevo Análisis
            </a>
        </div>
    @endif
</div>

{{-- Modal para ver imágenes ampliadas (mejorado) --}}
<div id="imageModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300" onclick="closeImageModal()">
    <div class="relative max-w-6xl w-full max-h-[90vh] flex items-center justify-center modal-fade-in" onclick="event.stopPropagation()">
        {{-- Botón cerrar mejorado --}}
        <button onclick="closeImageModal()" 
                class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl flex items-center justify-center backdrop-blur-sm border border-white/30 transition-all z-10 group">
            <i class="fas fa-times group-hover:rotate-90 transition-transform"></i>
        </button>
        
        {{-- Navegación izquierda --}}
        <button id="prevImageBtn" onclick="navigateImage(-1)" 
                class="absolute left-4 top-1/2 transform -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-sm border border-white/30 transition-all opacity-0 group-hover:opacity-100 disabled:opacity-30 disabled:cursor-not-allowed hidden">
            <i class="fas fa-chevron-left text-xl"></i>
        </button>
        
        {{-- Imagen --}}
        <div class="relative">
            <img id="modalImage" src="" alt="Imagen ampliada" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl border-4 border-white/20">
            
            {{-- Contador de imágenes --}}
            <div id="imageCounter" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/50 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-medium border border-white/20">
                <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
            </div>
        </div>
        
        {{-- Navegación derecha --}}
        <button id="nextImageBtn" onclick="navigateImage(1)" 
                class="absolute right-4 top-1/2 transform -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-sm border border-white/30 transition-all opacity-0 group-hover:opacity-100 disabled:opacity-30 disabled:cursor-not-allowed hidden">
            <i class="fas fa-chevron-right text-xl"></i>
        </button>
        
        {{-- Título de la imagen --}}
        <p id="modalCaption" class="absolute bottom-20 left-1/2 transform -translate-x-1/2 text-white text-sm bg-black/50 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20"></p>
    </div>
</div>

{{-- Modal para galería completa (nuevo) --}}
<div id="galleryModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300" onclick="closeGalleryModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden" onclick="event.stopPropagation()">
        <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-purple-700 text-white px-8 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl">
                            <span id="galleryTitle">Galería de Imágenes</span>
                        </h3>
                        <p class="text-indigo-100 text-sm">Orden #<span id="galleryOrderNumber"></span></p>
                    </div>
                </div>
                <button onclick="closeGalleryModal()" 
                        class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 transition-all flex items-center justify-center group">
                    <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-100px)]">
            <div id="galleryGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
        </div>
    </div>
</div>

<script>
let currentImages = [];
let currentImageIndex = 0;
let currentOrderNumber = '';

// Función mejorada para abrir imagen
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

// Función para navegar entre imágenes
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

// Función para abrir galería completa
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

// Navegación con teclado
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

// Prevenir scroll cuando el modal está abierto
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                const modal = document.getElementById('imageModal');
                if (modal.classList.contains('hidden')) {
                    document.body.style.overflow = 'auto';
                } else {
                    document.body.style.overflow = 'hidden';
                }
            }
        });
    });
    
    observer.observe(document.getElementById('imageModal'), { attributes: true });
});
</script>
@endsection