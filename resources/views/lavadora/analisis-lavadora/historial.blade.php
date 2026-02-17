@extends('layouts.app')

@section('title', 'Historial de Análisis')

@section('content')

<div class="max-w-5xl mx-auto px-4 py-6">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-history text-blue-600 mr-2"></i>
            Historial de Registros
        </h1>

        <a href="{{ route('analisis-lavadora.index') }}"
           class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm">
            ← Volver
        </a>
    </div>

    @if($analisis->count() > 0)

        <div class="relative border-l-4 border-blue-500 pl-6">

            @foreach($analisis as $item)

                @php
                    $estado = $item->estado ?? 'Buen estado';

                    if (str_contains($estado, 'Dañado - Cambiado')) {
                        $color = 'bg-blue-100 text-blue-800';
                    } elseif (str_contains($estado, 'Dañado')) {
                        $color = 'bg-red-100 text-red-800';
                    } elseif (str_contains($estado, 'Desgaste')) {
                        $color = 'bg-yellow-100 text-yellow-800';
                    } else {
                        $color = 'bg-green-100 text-green-800';
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
                @endphp

                <div class="mb-8 relative">

                    <div class="absolute -left-3 top-2 w-6 h-6 bg-blue-600 rounded-full border-4 border-white shadow"></div>

                    <div class="bg-white shadow rounded-lg p-5 border border-gray-200">

                        <div class="flex justify-between items-center mb-3">
                            <div>
                                <div class="text-sm text-gray-500">
                                    {{ $item->fecha_analisis?->format('d/m/Y') }}
                                </div>
                                <div class="font-bold text-lg">
                                    Orden #{{ $item->numero_orden }}
                                </div>
                            </div>

                            <span class="px-3 py-1 rounded text-xs font-medium {{ $color }}">
                                {{ $estado }}
                            </span>
                        </div>

                        <div class="text-sm text-gray-600 mb-3">
                            <strong>Lavadora:</strong> {{ $item->linea->nombre ?? '' }} <br>
                            <strong>Componente:</strong> {{ $item->componente->nombre ?? '' }} <br>
                            <strong>Reductor:</strong> {{ $item->reductor }}
                        </div>

                        <div class="bg-gray-50 p-3 rounded text-sm text-gray-700">
                            {{ $item->actividad }}
                        </div>

                        {{-- Sección de imágenes --}}
                        @if(count($imagenes) > 0)
                            <div class="mt-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-images text-blue-500 mr-1"></i>
                                    Evidencia fotográfica ({{ count($imagenes) }})
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    @foreach($imagenes as $index => $imagen)
                                        @php
                                            $rutaImagen = asset('storage/' . $imagen);
                                        @endphp
                                        <div class="relative group">
                                            <img src="{{ $rutaImagen }}"
                                                 alt="Evidencia {{ $index + 1 }}"
                                                 class="w-full h-24 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition"
                                                 onclick="openImageModal('{{ $rutaImagen }}', 'Evidencia {{ $index + 1 }} - Orden #{{ $item->numero_orden }}')">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mt-4 text-xs text-gray-400 italic">
                                <i class="fas fa-image mr-1"></i> Sin imágenes adjuntas
                            </div>
                        @endif

                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('analisis-lavadora.edit', $item->id) }}"
                               class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-xs hover:bg-yellow-200">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-10 text-gray-500">
            <i class="fas fa-folder-open text-4xl mb-4"></i>
            <p>No hay registros para mostrar.</p>
        </div>

    @endif

</div>

{{-- Modal para ver imágenes ampliadas --}}
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50" onclick="closeImageModal()">
    <div class="relative max-w-4xl max-h-screen p-4" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()" class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75 z-10">
            <i class="fas fa-times text-xl"></i>
        </button>
        <img id="modalImage" src="" alt="Imagen ampliada" class="max-w-full max-h-[90vh] object-contain rounded-lg">
        <p id="modalCaption" class="text-white text-center mt-2 text-sm"></p>
    </div>
</div>

<script>
function openImageModal(imageSrc, caption) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');

    modalImg.src = imageSrc;
    modalCaption.textContent = caption;
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

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>

@endsection