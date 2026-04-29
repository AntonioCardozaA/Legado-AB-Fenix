@extends('layouts.app')

@section('title', 'Detalle del Análisis - Pasteurizadora')

@section('content')
<style>
    .detail-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }
    .detail-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 20px 24px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        padding: 24px;
    }
    .info-item {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #e2e8f0;
    }
    .info-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 8px;
    }
    .info-value {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
    }
    .image-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        padding: 24px;
    }
    .gallery-item {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .gallery-item:hover {
        transform: translateY(-4px);
        border-color: #3b82f6;
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.2);
    }
    .gallery-img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .progress-container {
        width: 100%;
        background: #e2e8f0;
        border-radius: 999px;
        height: 8px;
        overflow: hidden;
    }
    .progress-bar {
        height: 100%;
        border-radius: 999px;
        transition: width 0.5s ease;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 14px;
    }
</style>

<div class="max-w-5xl mx-auto px-4 py-8">
    {{-- Header con navegación --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900
                      bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.historial', ['linea_id' => $analisis->linea_id, 'modulo' => $analisis->modulo, 'componente' => $analisis->componente]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                <i class="fas fa-history"></i>
                Ver Historial
            </a>
        </div>
    </div>

    {{-- Tarjeta principal --}}
    <div class="detail-card">
        <div class="detail-header">
            <div class="flex justify-between items-start flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ asset('images/icono-pasteurizadora.png') }}"
                             class="w-8 h-8 object-contain filter brightness-0 invert">
                        <h1 class="text-2xl font-bold">Detalle del Análisis</h1>
                    </div>
                    <p class="text-blue-200">Orden #{{ $analisis->numero_orden }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.edit', $analisis->id) }}"
                       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <button onclick="confirmDelete()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>

        {{-- Información general --}}
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><i class="fas fa-temperature-high mr-1"></i> Línea</div>
                <div class="info-value">{{ $analisis->linea->nombre ?? 'No especificada' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-cubes mr-1"></i> Módulo</div>
                <div class="info-value">Módulo {{ $analisis->modulo }}</div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-cog mr-1"></i> Componente</div>
                <div class="info-value">{{ $analisis->componente_nombre }}</div>
            </div>
            @if($analisis->lado)
            <div class="info-item">
                <div class="info-label"><i class="fas fa-arrows-alt-h mr-1"></i> Lado</div>
                <div class="info-value">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm {{ $analisis->lado_clase }}">
                        <i class="fas {{ $analisis->lado_icono }}"></i> {{ $analisis->lado }}
                    </span>
                </div>
            </div>
            @endif
            @if($analisis->nivel)
            <div class="info-item">
                <div class="info-label"><i class="fas fa-layer-group mr-1"></i> Nivel</div>
                <div class="info-value">{{ $analisis->nivel }}</div>
            </div>
            @endif
            <div class="info-item">
                <div class="info-label"><i class="far fa-calendar-alt mr-1"></i> Fecha</div>
                <div class="info-value">{{ $analisis->fecha_formateada }}</div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-user mr-1"></i> Responsable</div>
                <div class="info-value">{{ $analisis->responsable ?? 'No especificado' }}</div>
            </div>
        </div>

        {{-- Estado y progreso --}}
        <div class="px-6 pb-4">
            @php
                $badge = $analisis->estado_badge;
                $porcentaje = $analisis->porcentaje_avance;
            @endphp
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 mb-1">Estado actual</div>
                    <span class="status-badge {{ $badge['class'] }}">
                        <i class="fas {{ $badge['icon'] }}"></i>
                        {{ $analisis->estado }}
                    </span>
                </div>
                @if($analisis->total_componentes)
                <div class="flex-1 max-w-xs">
                    <div class="text-sm text-gray-500 mb-1">
                        Progreso de revisión: {{ $analisis->cantidad_componentes_revisados ?? 0 }} / {{ $analisis->total_componentes }} componentes
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar bg-blue-600" style="width: {{ $porcentaje }}%"></div>
                    </div>
                    <div class="text-right text-xs text-gray-500 mt-1">{{ $porcentaje }}% completado</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Componentes revisados --}}
@php
    // Validar que sea un array no vacío
    $componentesRevisados = \App\Models\AnalisisPasteurizadora::normalizarComponentesRevisados(
        $analisis->componentes_revisados,
        $analisis->total_componentes
    );

    if (empty($componentesRevisados) && $analisis->cantidad_componentes_revisados) {
        $componentesRevisados = range(1, min($analisis->cantidad_componentes_revisados, $analisis->total_componentes));
    }
    @endphp
    @if(!empty($componentesRevisados))
    <div class="px-6 pb-4">
        <div class="bg-indigo-50 rounded-xl p-5 border border-indigo-200">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-indigo-600 text-xs"></i>
                </div>
                <h4 class="text-sm font-semibold text-indigo-800 uppercase tracking-wider">Componentes revisados</h4>
                <span class="ml-2 px-2 py-0.5 bg-indigo-200 text-indigo-800 text-xs rounded-full font-medium">
                    {{ count($componentesRevisados) }} de {{ $analisis->total_componentes }}
                </span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3">
                @foreach($componentesRevisados as $componente_num)
                <div class="flex items-center gap-2 bg-white rounded-lg p-3 border border-indigo-200">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-indigo-600"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700">
                        @if(\App\Models\AnalisisPasteurizadora::esBrazoTorsion($analisis->componente))
                            {{ $analisis->componente_nombre }} modulo {{ intval($componente_num) }}
                        @else
                            {{ $analisis->componente_nombre }} #{{ intval($componente_num) }}
                        @endif
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

        {{-- Actividad --}}
        <div class="px-6 pb-4">
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-sticky-note text-blue-600 text-xs"></i>
                    </div>
                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Actividad realizada</h4>
                </div>
                <div class="text-gray-700 whitespace-pre-line leading-relaxed">
                    {{ $analisis->actividad }}
                </div>
            </div>
        </div>

        @if($analisis->observaciones)
        <div class="px-6 pb-4">
            <div class="bg-yellow-50 rounded-xl p-5 border border-yellow-200">
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-comment-dots text-yellow-600"></i>
                    <h4 class="text-sm font-semibold text-yellow-800 uppercase tracking-wider">Observaciones adicionales</h4>
                </div>
                <div class="text-gray-700 whitespace-pre-line">
                    {{ $analisis->observaciones }}
                </div>
            </div>
        </div>
        @endif

        {{-- Resolución (si está resuelto) --}}
        @if($analisis->resuelto_por_cambio)
        <div class="px-6 pb-4">
            <div class="bg-green-50 rounded-xl p-5 border border-green-200">
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <h4 class="text-sm font-semibold text-green-800 uppercase tracking-wider">Registro resuelto</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Fecha de resolución</div>
                        <div class="font-medium">{{ $analisis->fecha_resolucion_formateada }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Resuelto por</div>
                        <div class="font-medium">Orden #{{ $analisis->registroResolutor->numero_orden ?? 'N/A' }}</div>
                    </div>
                </div>
                @if($analisis->nota_resolucion)
                <div class="mt-3 pt-3 border-t border-green-200">
                    <div class="text-xs text-gray-500 mb-1">Nota de resolución</div>
                    <div class="text-sm">{{ $analisis->nota_resolucion }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Evidencia fotográfica --}}
        @if($analisis->evidencia_fotos && count($analisis->evidencia_fotos) > 0)
        <div class="border-t border-gray-200">
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-images text-blue-600"></i>
                        <h3 class="font-semibold text-gray-800">Evidencia Fotográfica</h3>
                        <span class="text-xs text-gray-500">{{ count($analisis->evidencia_fotos) }} imágenes</span>
                    </div>
                    <button onclick="descargarTodasImagenes()"
                            class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                        <i class="fas fa-download"></i> Descargar todas
                    </button>
                </div>
            </div>
            <div class="image-gallery" id="imageGallery">
                @foreach($analisis->evidencia_fotos as $index => $foto)
                <div class="gallery-item" onclick="abrirImagen('{{ Storage::url($foto) }}', {{ $index }})">
                    <img src="{{ Storage::url($foto) }}"
                         alt="Evidencia {{ $index + 1 }}"
                         class="gallery-img">
                    <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">
                        {{ $index + 1 }}
                    </div>
                    <div class="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
                        <i class="fas fa-search-plus"></i> Ampliar
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Análisis 52-12-4 (si tiene datos) --}}
        @if($analisis->valor_actual_52 || $analisis->valor_actual_12 || $analisis->valor_actual_4)
        <div class="border-t border-gray-200">
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600"></i>
                    <h3 class="font-semibold text-gray-800">Análisis de Tendencia (52-12-4 semanas)</h3>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6">
                @if($analisis->valor_actual_52)
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-500 mb-1">52 semanas</div>
                    <div class="text-2xl font-bold text-blue-700">{{ $analisis->valor_actual_52 }}</div>
                    @if($analisis->valor_anterior_52)
                    <div class="text-xs mt-1">
                        <span class="{{ $analisis->valor_actual_52 >= $analisis->valor_anterior_52 ? 'text-green-600' : 'text-red-600' }}">
                            <i class="fas fa-{{ $analisis->valor_actual_52 >= $analisis->valor_anterior_52 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ number_format(abs((($analisis->valor_actual_52 - $analisis->valor_anterior_52) / $analisis->valor_anterior_52) * 100), 1) }}%
                        </span>
                        vs anterior
                    </div>
                    @endif
                </div>
                @endif
                @if($analisis->valor_actual_12)
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-500 mb-1">12 semanas</div>
                    <div class="text-2xl font-bold text-yellow-700">{{ $analisis->valor_actual_12 }}</div>
                    @if($analisis->valor_anterior_12)
                    <div class="text-xs mt-1">
                        <span class="{{ $analisis->valor_actual_12 >= $analisis->valor_anterior_12 ? 'text-green-600' : 'text-red-600' }}">
                            <i class="fas fa-{{ $analisis->valor_actual_12 >= $analisis->valor_anterior_12 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ number_format(abs((($analisis->valor_actual_12 - $analisis->valor_anterior_12) / $analisis->valor_anterior_12) * 100), 1) }}%
                        </span>
                        vs anterior
                    </div>
                    @endif
                </div>
                @endif
                @if($analisis->valor_actual_4)
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-500 mb-1">4 semanas</div>
                    <div class="text-2xl font-bold text-green-700">{{ $analisis->valor_actual_4 }}</div>
                    @if($analisis->valor_anterior_4)
                    <div class="text-xs mt-1">
                        <span class="{{ $analisis->valor_actual_4 >= $analisis->valor_anterior_4 ? 'text-green-600' : 'text-red-600' }}">
                            <i class="fas fa-{{ $analisis->valor_actual_4 >= $analisis->valor_anterior_4 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ number_format(abs((($analisis->valor_actual_4 - $analisis->valor_anterior_4) / $analisis->valor_anterior_4) * 100), 1) }}%
                        </span>
                        vs anterior
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Planes PCM --}}
        @php
            $pcmPlanes = [
                'pcm1' => $analisis->plan_accion_pcm1,
                'pcm2' => $analisis->plan_accion_pcm2,
                'pcm3' => $analisis->plan_accion_pcm3,
                'pcm4' => $analisis->plan_accion_pcm4,
            ];
            $tienePCM = false;
            foreach($pcmPlanes as $plan) {
                if($plan && (!empty($plan['fecha']) || !empty($plan['accion']))) {
                    $tienePCM = true;
                    break;
                }
            }
        @endphp

        @if($tienePCM)
        <div class="border-t border-gray-200">
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-check text-blue-600"></i>
                    <h3 class="font-semibold text-gray-800">Plan de Acción PCM</h3>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-6">
                @foreach(['pcm1' => 'PCM 1', 'pcm2' => 'PCM 2', 'pcm3' => 'PCM 3', 'pcm4' => 'PCM 4'] as $key => $nombre)
                    @php $plan = $analisis->{'plan_accion_' . $key}; @endphp
                    <div class="border rounded-lg p-4 {{ $plan && $plan['fecha'] ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200' }}">
                        <h4 class="font-bold text-gray-700 mb-2">{{ $nombre }}</h4>
                        @if($plan && $plan['fecha'])
                            <div class="text-sm">
                                <div class="flex items-center gap-1 text-gray-600 mb-1">
                                    <i class="far fa-calendar-alt text-xs"></i>
                                    <span>{{ \Carbon\Carbon::parse($plan['fecha'])->format('d/m/Y') }}</span>
                                </div>
                                @if($plan['accion'])
                                    <div class="text-xs text-gray-500 mt-1">{{ Str::limit($plan['accion'], 60) }}</div>
                                @endif
                            </div>
                        @else
                            <div class="text-sm text-gray-400">Sin programación</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Metadatos --}}
        <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 text-xs text-gray-500">
            <div class="flex flex-wrap justify-between gap-4">
                <div>
                    <i class="far fa-clock mr-1"></i> Creado: {{ $analisis->created_at ? $analisis->created_at->format('d/m/Y H:i') : 'N/A' }}
                </div>
                <div>
                    <i class="fas fa-edit mr-1"></i> Última actualización: {{ $analisis->updated_at ? $analisis->updated_at->format('d/m/Y H:i') : 'N/A' }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para ver imágenes ampliadas --}}
<div id="imageModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-50 p-4" onclick="cerrarModalImagen()">
    <div class="relative max-w-5xl w-full">
        <button onclick="cerrarModalImagen()"
                class="absolute -top-12 right-0 text-white hover:text-gray-300 text-2xl">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" class="w-full h-auto max-h-[85vh] object-contain rounded-lg">
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/70 text-white px-4 py-2 rounded-full text-sm">
            <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
        </div>
        <button onclick="navegarImagen(-1)" id="prevBtn"
                class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black/50 text-white w-10 h-10 rounded-full hover:bg-black/70 hidden">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button onclick="navegarImagen(1)" id="nextBtn"
                class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black/50 text-white w-10 h-10 rounded-full hover:bg-black/70 hidden">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<form id="deleteForm" action="{{ route('pasteurizadora.analisis-pasteurizadora.destroy', $analisis->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    let imagenes = @json($analisis->evidencia_fotos ?? []);
    let imagenesUrls = imagenes.map(img => '{{ Storage::url('') }}' + img);
    let currentImgIndex = 0;

    function abrirImagen(url, index) {
        currentImgIndex = index;
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const currentSpan = document.getElementById('currentImageIndex');
        const totalSpan = document.getElementById('totalImages');

        modalImg.src = url;
        currentSpan.textContent = index + 1;
        totalSpan.textContent = imagenesUrls.length;

        if (imagenesUrls.length > 1) {
            prevBtn.classList.remove('hidden');
            nextBtn.classList.remove('hidden');
        } else {
            prevBtn.classList.add('hidden');
            nextBtn.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalImagen() {
        const modal = document.getElementById('imageModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function navegarImagen(direccion) {
        let nuevoIndex = currentImgIndex + direccion;
        if (nuevoIndex < 0) nuevoIndex = imagenesUrls.length - 1;
        if (nuevoIndex >= imagenesUrls.length) nuevoIndex = 0;
        currentImgIndex = nuevoIndex;

        const modalImg = document.getElementById('modalImage');
        const currentSpan = document.getElementById('currentImageIndex');

        modalImg.src = imagenesUrls[currentImgIndex];
        currentSpan.textContent = currentImgIndex + 1;
    }

    function descargarTodasImagenes() {
        if (imagenesUrls.length === 0) return;

        imagenesUrls.forEach((url, index) => {
            const link = document.createElement('a');
            link.href = url;
            link.download = `evidencia_${index + 1}.jpg`;
            setTimeout(() => link.click(), index * 200);
        });
    }

    function confirmDelete() {
        if (confirm('¿Está seguro de eliminar este análisis? Esta acción no se puede deshacer.')) {
            document.getElementById('deleteForm').submit();
        }
    }

    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('imageModal');
        if (modal.classList.contains('flex')) {
            if (e.key === 'Escape') cerrarModalImagen();
            if (e.key === 'ArrowLeft') navegarImagen(-1);
            if (e.key === 'ArrowRight') navegarImagen(1);
        }
    });
</script>
@endsection
