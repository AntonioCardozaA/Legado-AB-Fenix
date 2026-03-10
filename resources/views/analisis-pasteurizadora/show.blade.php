@extends('layouts.app')

@section('title', 'Detalle del Análisis - Pasteurizadora')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('analisis-pasteurizadora.index', ['linea_id' => $analisis->linea_id]) }}" 
           class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
            Volver al listado
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-t-2xl px-8 py-6 text-white">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold flex items-center gap-3">
                    <i class="fas fa-temperature-high"></i>
                    Detalle del Análisis
                </h1>
                <p class="text-blue-100 mt-2">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    {{ $analisis->fecha_formateada }} - Orden #{{ $analisis->numero_orden }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('analisis-pasteurizadora.edit', $analisis->id) }}" 
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    Editar
                </a>
                <button onclick="confirmarEliminacion()" 
                        class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    {{-- Contenido --}}
    <div class="bg-white rounded-b-2xl shadow-xl p-8">
        {{-- Información principal --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-50 rounded-xl p-5">
                <p class="text-sm text-gray-500 mb-1">Línea</p>
                <p class="text-xl font-bold text-gray-800">{{ $analisis->linea->nombre ?? 'N/A' }}</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-5">
                <p class="text-sm text-gray-500 mb-1">Módulo</p>
                <p class="text-xl font-bold text-gray-800">{{ $analisis->modulo_nombre }}</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-5">
                <p class="text-sm text-gray-500 mb-1">Componente</p>
                <p class="text-xl font-bold text-gray-800">{{ $analisis->componente_nombre }}</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-5">
                <p class="text-sm text-gray-500 mb-1">Lado</p>
                @if($analisis->lado)
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium {{ $analisis->lado_clase }}">
                        <i class="fas {{ $analisis->lado_icono }} mr-2"></i>
                        {{ $analisis->lado }}
                    </span>
                @else
                    <p class="text-gray-400">No especificado</p>
                @endif
            </div>
        </div>

        {{-- Estado y Actividad --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-blue-600"></i>
                    Estado del Componente
                </h3>
                <div class="p-4 rounded-lg {{ $analisis->estado_clase }} inline-block">
                    <i class="fas {{ $analisis->estado_icono }} mr-2"></i>
                    {{ $analisis->estado }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-user text-blue-600"></i>
                    Responsable
                </h3>
                <p class="text-gray-800">{{ $analisis->responsable ?? 'No asignado' }}</p>
            </div>
        </div>

        {{-- Actividad --}}
        <div class="bg-gray-50 rounded-xl p-5 mb-8">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-sticky-note text-blue-600"></i>
                Actividad Realizada
            </h3>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <p class="text-gray-700 whitespace-pre-line">{{ $analisis->actividad }}</p>
            </div>
        </div>

        {{-- Observaciones --}}
        @if($analisis->observaciones)
        <div class="bg-gray-50 rounded-xl p-5 mb-8">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-comment text-blue-600"></i>
                Observaciones Adicionales
            </h3>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <p class="text-gray-700">{{ $analisis->observaciones }}</p>
            </div>
        </div>
        @endif

        {{-- Evidencia Fotográfica --}}
        @if($analisis->tiene_imagenes)
        <div class="bg-gray-50 rounded-xl p-5 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-images text-blue-600"></i>
                    Evidencia Fotográfica ({{ $analisis->cantidad_imagenes }})
                </h3>
                <button onclick="descargarTodasImagenes()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
                    <i class="fas fa-download"></i>
                    Descargar todas
                </button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($analisis->evidencia_fotos as $index => $foto)
                    <div class="relative group">
                        <img src="{{ Storage::url($foto) }}" 
                             alt="Evidencia {{ $index + 1 }}"
                             class="w-full h-40 object-cover rounded-lg cursor-pointer border-2 border-transparent group-hover:border-blue-500 transition"
                             onclick="verImagen('{{ Storage::url($foto) }}', {{ $index + 1 }})">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition rounded-lg flex items-center justify-center gap-2">
                            <button onclick="verImagen('{{ Storage::url($foto) }}', {{ $index + 1 }})" 
                                    class="p-2 bg-white rounded-full hover:bg-gray-200 transition">
                                <i class="fas fa-eye text-blue-600"></i>
                            </button>
                            <button onclick="descargarImagen('{{ $foto }}', {{ $index + 1 }})" 
                                    class="p-2 bg-white rounded-full hover:bg-gray-200 transition">
                                <i class="fas fa-download text-green-600"></i>
                            </button>
                        </div>
                        <span class="absolute top-2 left-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
                            {{ $index + 1 }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

       {{-- Análisis 52-12-4 --}}
@if(isset($analisis52124) && is_array($analisis52124))
<div class="bg-gray-50 rounded-xl p-5">
    <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <i class="fas fa-chart-line text-blue-600"></i>
        Análisis 52-12-4
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- 52 semanas --}}
        @php
            $comp52 = $analisis52124['componente_52'] ?? ['actual'=>0,'anterior'=>0,'variacion'=>0];
            $variacion = $comp52['variacion'];
            $color = $variacion > 0 ? 'text-red-600' : ($variacion < 0 ? 'text-green-600' : 'text-yellow-600');
            $icono = $variacion > 0 ? 'fa-arrow-up' : ($variacion < 0 ? 'fa-arrow-down' : 'fa-minus');
        @endphp

        <div class="bg-white rounded-lg p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500">52 Semanas</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($comp52['actual'], 2) }}</p>
            <p class="text-xs text-gray-500">Anterior: {{ number_format($comp52['anterior'], 2) }}</p>

            <p class="text-xs {{ $color }} mt-1">
                <i class="fas {{ $icono }}"></i>
                {{ number_format(abs($variacion), 2) }}%
            </p>
        </div>

        {{-- 12 semanas --}}
        @php
            $comp12 = $analisis52124['componente_12'] ?? ['actual'=>0,'anterior'=>0,'variacion'=>0];
            $variacion = $comp12['variacion'];
            $color = $variacion > 0 ? 'text-red-600' : ($variacion < 0 ? 'text-green-600' : 'text-yellow-600');
            $icono = $variacion > 0 ? 'fa-arrow-up' : ($variacion < 0 ? 'fa-arrow-down' : 'fa-minus');
        @endphp

        <div class="bg-white rounded-lg p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-500">12 Semanas</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($comp12['actual'], 2) }}</p>
            <p class="text-xs text-gray-500">Anterior: {{ number_format($comp12['anterior'], 2) }}</p>

            <p class="text-xs {{ $color }} mt-1">
                <i class="fas {{ $icono }}"></i>
                {{ number_format(abs($variacion), 2) }}%
            </p>
        </div>

        {{-- 4 semanas --}}
        @php
            $comp4 = $analisis52124['componente_4'] ?? ['actual'=>0,'anterior'=>0,'variacion'=>0];
            $variacion = $comp4['variacion'];
            $color = $variacion > 0 ? 'text-red-600' : ($variacion < 0 ? 'text-green-600' : 'text-yellow-600');
            $icono = $variacion > 0 ? 'fa-arrow-up' : ($variacion < 0 ? 'fa-arrow-down' : 'fa-minus');
        @endphp

        <div class="bg-white rounded-lg p-4 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500">4 Semanas</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($comp4['actual'], 2) }}</p>
            <p class="text-xs text-gray-500">Anterior: {{ number_format($comp4['anterior'], 2) }}</p>

            <p class="text-xs {{ $color }} mt-1">
                <i class="fas {{ $icono }}"></i>
                {{ number_format(abs($variacion), 2) }}%
            </p>
        </div>

    </div>
</div>
@endif

        {{-- Metadatos --}}
        <div class="mt-8 pt-6 border-t border-gray-200 grid grid-cols-2 gap-4 text-sm text-gray-500">
            <div>
                <p><i class="far fa-clock mr-2"></i> Creado: {{ $analisis->created_at ? $analisis->created_at->format('d/m/Y H:i') : 'N/A' }}</p>
                <p><i class="far fa-edit mr-2"></i> Actualizado: {{ $analisis->updated_at ? $analisis->updated_at->format('d/m/Y H:i') : 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Modal de imagen --}}
<div id="imageModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-50 p-4" onclick="cerrarModalImagen()">
    <div class="relative max-w-6xl w-full" onclick="event.stopPropagation()">
        <button onclick="cerrarModalImagen()" 
                class="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full text-white flex items-center justify-center">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="Imagen ampliada" class="w-full max-h-[80vh] object-contain">
        <p id="modalImageCounter" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/70 text-white px-4 py-2 rounded-full text-sm"></p>
    </div>
</div>

{{-- Formulario de eliminación --}}
<form id="deleteForm" action="{{ route('analisis-pasteurizadora.destroy', $analisis->id) }}" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
let imagenes = @json($analisis->evidencia_fotos ?? []);
let imagenActual = 0;

function verImagen(url, index) {
    imagenActual = index - 1;
    const modal = document.getElementById('imageModal');
    const img = document.getElementById('modalImage');
    const counter = document.getElementById('modalImageCounter');
    
    img.src = url;
    if (imagenes.length > 0) {
        counter.textContent = `${index} / ${imagenes.length}`;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function cerrarModalImagen() {
    document.getElementById('imageModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function descargarImagen(path, index) {
    const link = document.createElement('a');
    link.href = `{{ Storage::url('') }}${path}`;
    link.download = `imagen-${index}.jpg`;
    link.click();
}

function descargarTodasImagenes() {
    imagenes.forEach((path, index) => {
        setTimeout(() => {
            descargarImagen(path, index + 1);
        }, index * 500);
    });
}

function confirmarEliminacion() {
    if (confirm('¿Está seguro de eliminar este análisis? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteForm').submit();
    }
}

document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageModal');
    if (!modal.classList.contains('hidden')) {
        if (e.key === 'Escape') {
            cerrarModalImagen();
        } else if (e.key === 'ArrowLeft' && imagenes.length > 0) {
            imagenActual = (imagenActual - 1 + imagenes.length) % imagenes.length;
            verImagen(`{{ Storage::url('') }}${imagenes[imagenActual]}`, imagenActual + 1);
        } else if (e.key === 'ArrowRight' && imagenes.length > 0) {
            imagenActual = (imagenActual + 1) % imagenes.length;
            verImagen(`{{ Storage::url('') }}${imagenes[imagenActual]}`, imagenActual + 1);
        }
    }
});
</script>
@endsection