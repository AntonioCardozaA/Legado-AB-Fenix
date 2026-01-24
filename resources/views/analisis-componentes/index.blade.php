@extends('layouts.app')

@section('title', 'Análisis de Componentes')

@section('content')
<style>
    .sticky-top { position: sticky; top: 0; z-index: 30; }
    .sticky-left { position: sticky; left: 0; z-index: 20; }
    .cell-ok { background-color: #f0f9ff; border-left: 4px solid #10b981; }
    .cell-warning { background-color: #fffbeb; border-left: 4px solid #f59e0b; }
    .cell-danger { background-color: #fef2f2; border-left: 4px solid #ef4444; }
    
    .compact-table td, .compact-table th {
        padding: 8px !important;
        font-size: 0.75rem !important;
    }
    
    .table-container {
        max-height: 600px;
        overflow-y: auto;
    }
    
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
        max-height: 60vh;
        overflow-y: auto;
        padding: 10px;
    }
    
    .image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
        background: white;
    }
    
    .image-item:hover {
        border-color: #3b82f6;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .grid-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .grid-image:hover {
        transform: scale(1.05);
    }
    
    .image-info {
        padding: 8px;
        background: linear-gradient(to bottom, rgba(255,255,255,0.9), white);
        border-top: 1px solid #f3f4f6;
    }
    
    .image-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 12px;
        z-index: 10;
    }
    
    .download-image-btn {
        width: 100%;
        padding: 6px;
        margin-top: 8px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.3s ease;
    }
    
    .download-image-btn:hover {
        background: #2563eb;
    }
    
    .download-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.3s ease;
    }
    
    .download-all-btn:hover {
        background: #059669;
    }
    
    .empty-images {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
</style>

<div class="max-w-full mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-chart-bar text-yellow-500 mr-2"></i>
                Análisis de Componentes de Lavadoras
            </h1>
            <p class="text-gray-600 text-sm mt-1">Sistema de monitoreo y mantenimiento</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Nuevo Análisis
            </a>

            <a href="{{ route('analisis-componentes.export.excel', request()->query()) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fas fa-file-excel"></i>
                Exportar Excel
            </a>

            <a href="{{ route('analisis-componentes.export.pdf', request()->query()) }}"
               class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                <i class="fas fa-file-pdf"></i>
                Exportar PDF
            </a>
        </div>
    </div>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('analisis-componentes.index') }}"
          class="bg-white rounded-lg shadow-sm p-4 mb-6 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-washing-machine text-blue-600 mr-1"></i>
                    Lavadora
                </label>
                <select name="linea_id" class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas las lavadoras</option>
                    @foreach($lineas as $l)
                        <option value="{{ $l->id }}" {{ request('linea_id') == $l->id ? 'selected' : '' }}>
                            {{ $l->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-cog text-blue-600 mr-1"></i>
                    Componente
                </label>
                <select name="componente_id" class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los componentes</option>
                    @foreach($componentes as $c)
                        <option value="{{ $c->id }}" {{ request('componente_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-compress-alt text-blue-600 mr-1"></i>
                    Reductor
                </label>
                <select name="reductor" class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los reductores</option>
                    @foreach($reductores as $r)
                        <option value="{{ $r }}" {{ request('reductor') == $r ? 'selected' : '' }}>
                            {{ $r }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                    Mes / Año
                </label>
                <input type="month" name="fecha" value="{{ request('fecha') }}"
                       class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex gap-2 items-end">
                <button type="submit" 
                        class="flex-1 bg-blue-600 text-white py-2 rounded text-sm hover:bg-blue-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    Filtrar
                </button>
                <a href="{{ route('analisis-componentes.index') }}"
                   class="flex-1 bg-gray-200 text-gray-700 py-2 rounded text-sm hover:bg-gray-300 transition flex items-center justify-center gap-2">
                    <i class="fas fa-eraser"></i>
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    {{-- TABLAS --}}
    @php
        $analisisCollection = collect($analisis->items());
    @endphp

    @if($analisis->total() > 0)
        @foreach($analisisCollection->groupBy('linea_id') as $lineaId => $grupo)
            @php
                $linea = $grupo->first()->linea ?? null;
                $lineaNombre = $linea->nombre ?? 'Sin nombre';
                
                $ok = $grupo->filter(function($a){
                    return str_contains(strtolower($a->actividad),'buen');
                })->count();

                $warn = $grupo->filter(function($a){
                    return str_contains(strtolower($a->actividad),'desgaste');
                })->count();

                $bad = $grupo->filter(function($a){
                    return str_contains(strtolower($a->actividad),'dañ');
                })->count();

                $total = $grupo->count();
                $grupoReductores = $grupo->pluck('reductor')->unique()->sort()->values();
            @endphp

            <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                {{-- ENCABEZADO DE LÍNEA --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-3">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <h2 class="font-bold text-lg">
                            <i class="fas fa-washing-machine mr-2"></i>
                            Lavadora: {{ $lineaNombre }}
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                                ✅ OK: {{ $ok }}
                            </span>
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">
                                ⚠️ Desgaste: {{ $warn }}
                            </span>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium">
                                ❌ Críticos: {{ $bad }}
                            </span>
                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-medium">
                                📊 Total: {{ $total }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- TABLA COMPACTA --}}
                <div class="table-container overflow-x-auto">
                    <table class="w-full compact-table border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="sticky-top sticky-left bg-blue-50 text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                    REDUCTOR
                                </th>
                                @foreach($componentes as $c)
                                    <th class="sticky-top bg-blue-50 text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                        {{ $c->nombre }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($grupoReductores as $r)
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="sticky-left bg-blue-50 text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                        {{ $r }}
                                    </td>

                                    @foreach($componentes as $c)
                                        @php
                                            $reg = $grupo->first(function($a) use ($r, $c) {
                                                return $a->reductor == $r && $a->componente_id == $c->id;
                                            });
                                            
                                            $hasData = !empty($reg);
                                            $color = '';
                                            $totalImagenes = 0;
                                            
                                            if($hasData){
                                                $act = strtolower($reg->actividad);
                                                $color = str_contains($act,'dañ') ? 'cell-danger'
                                                       : (str_contains($act,'desgaste') ? 'cell-warning' : 'cell-ok');
                                                $imagenes = $reg->evidencia_fotos ?? [];
                                                $totalImagenes = count($imagenes);
                                            }
                                        @endphp

                                        <td class="border px-3 py-2 align-top {{ $color }}" style="min-height: 120px;">
                                            @if($hasData)
                                                <div class="space-y-2">
                                                    <div class="flex flex-col">
                                                        <div class="flex items-center gap-1 mb-1">
                                                            <i class="fas fa-calendar text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-semibold text-gray-700">
                                                                {{ $reg->fecha_analisis->format('d/m/Y') }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <i class="fas fa-hashtag text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-bold text-gray-800">
                                                                Orden #{{ $reg->numero_orden }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        @php
                                                            $statusClass = $color == 'cell-ok' ? 'bg-green-100 text-green-800 border-green-200' 
                                                               : ($color == 'cell-warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' 
                                                               : 'bg-red-100 text-red-800 border-red-200');
                                                            $icon = $color == 'cell-ok' ? 'fa-check-circle' 
                                                               : ($color == 'cell-warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $statusClass }}">
                                                            <i class="fas {{ $icon }} mr-1"></i>
                                                            {{ $reg->actividad }}
                                                        </span>
                                                    </div>

                                                    <div class="flex flex-col gap-1 mt-3">
                                                        @if($totalImagenes > 0)
                                                            <button onclick="openAllImages({{ json_encode($imagenes) }}, 
                                                                    '{{ $reg->fecha_analisis->format('d/m/Y') }}', 
                                                                    '{{ $reg->numero_orden }}', 
                                                                    '{{ $reg->actividad }}')"
                                                                    class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition text-xs font-medium">
                                                                @if($totalImagenes > 1)
                                                                    <i class="fas fa-images mr-1"></i>
                                                                    Ver {{ $totalImagenes }} Imágenes
                                                                @else
                                                                    <i class="fas fa-image mr-1"></i>
                                                                    Ver Imagen
                                                                @endif
                                                            </button>
                                                        @endif
                                                        
                                                        <a href="{{ route('analisis-componentes.edit',$reg->id) }}"
                                                           class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition text-xs font-medium">
                                                            <i class="fas fa-edit"></i>
                                                            Editar Registro
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="h-full flex flex-col items-center justify-center py-4 text-center">
                                                    <div class="text-gray-400 mb-2">
                                                        <i class="fas fa-clipboard text-xl"></i>
                                                    </div>
                                                    <p class="text-gray-500 text-xs mb-3">Sin datos registrados</p>
                                                    
                                                    <a href="{{ route('analisis-componentes.create-quick',[
                                                        'linea_id'=>$lineaId,
                                                        'componente_id'=>$c->id,
                                                        'reductor'=>$r,
                                                        'fecha'=>request('fecha',now()->format('Y-m'))
                                                    ]) }}"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs font-medium">
                                                        <i class="fas fa-plus"></i>
                                                        Agregar Análisis
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        {{-- PAGINACIÓN --}}
        <div class="mt-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Mostrando {{ $analisis->firstItem() }} - {{ $analisis->lastItem() }} de {{ $analisis->total() }} registros
                </div>
                <div class="flex gap-2">
                    {{ $analisis->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-clipboard-list text-4xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">No se encontraron análisis</h3>
            <p class="text-gray-500 mb-4">No hay registros que coincidan con los filtros aplicados.</p>
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus"></i>
                Crear Nuevo Análisis
            </a>
        </div>
    @endif
</div>

{{-- MODAL PARA IMÁGENES --}}
<div id="allImagesModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4"
     onclick="closeAllImagesModal()">
    <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <div>
                <h3 class="font-bold text-lg">
                    <i class="fas fa-images mr-2"></i>
                    <span id="modalTitle">Imágenes del Análisis</span>
                </h3>
                <div class="text-sm text-blue-100 mt-1">
                    <span id="modalDate"></span> • Orden: <span id="modalOrder"></span> • 
                    Estado: <span id="modalStatus"></span> •
                    <span id="imageCount" class="font-bold"></span>
                </div>
            </div>
            <button onclick="closeAllImagesModal()"
                    class="text-white hover:text-yellow-300 text-2xl transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-auto max-h-[calc(90vh-80px)]">
            <div id="imageGrid" class="image-grid"></div>
            
            <div id="emptyImages" class="empty-images hidden">
                <i class="fas fa-image"></i>
                <p class="text-lg font-medium">No hay imágenes disponibles</p>
                <p class="text-sm text-gray-500 mt-2">Este registro no tiene imágenes adjuntas</p>
            </div>
            
            <div class="flex flex-wrap gap-3 mt-6 justify-center">
                <button onclick="closeAllImagesModal()"
                        class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
                
                <button id="downloadAllImagesBtn" onclick="downloadAllImages()"
                        class="download-all-btn">
                    <i class="fas fa-download"></i>
                    Descargar Todas las Imágenes
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PARA IMAGEN INDIVIDUAL --}}
<div id="singleImageModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[60] p-4"
     onclick="closeSingleImageModal()">
    <div class="relative max-w-5xl w-full max-h-[90vh]">
        <img id="singleModalImg" class="max-w-full max-h-[80vh] object-contain rounded-lg mx-auto"
             onerror="this.onerror=null; this.src='https://via.placeholder.com/800x600?text=Imagen+no+disponible';">
        
        <button onclick="closeSingleImageModal()"
                class="absolute top-4 right-4 text-white hover:text-yellow-300 text-3xl transition">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
let currentImages = [];
let currentModalInfo = {};

function openAllImages(imagenes, fecha, orden, estado) {
    const modal = document.getElementById('allImagesModal');
    
    currentImages = Array.isArray(imagenes) ? imagenes.filter(img => img && img.trim() !== '') : [];
    currentModalInfo = { fecha, orden, estado };
    
    document.getElementById('modalDate').textContent = fecha || '';
    document.getElementById('modalOrder').textContent = orden || '';
    document.getElementById('modalStatus').textContent = estado || '';
    
    const imageCount = document.getElementById('imageCount');
    imageCount.textContent = currentImages.length > 0 ? 
        `${currentImages.length} imagen${currentImages.length > 1 ? 'es' : ''}` : '';
    
    buildImageGrid();
    
    const imageGrid = document.getElementById('imageGrid');
    const emptyImages = document.getElementById('emptyImages');
    const downloadAllBtn = document.getElementById('downloadAllImagesBtn');
    
    if (currentImages.length === 0) {
        imageGrid.classList.add('hidden');
        emptyImages.classList.remove('hidden');
        downloadAllBtn.style.display = 'none';
    } else {
        imageGrid.classList.remove('hidden');
        emptyImages.classList.add('hidden');
        downloadAllBtn.style.display = currentImages.length > 1 ? 'flex' : 'none';
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function buildImageGrid() {
    const imageGrid = document.getElementById('imageGrid');
    imageGrid.innerHTML = '';
    
    currentImages.forEach((imagePath, index) => {
        const imageItem = document.createElement('div');
        imageItem.className = 'image-item';
        
        const imageNumber = document.createElement('div');
        imageNumber.className = 'image-number';
        imageNumber.textContent = index + 1;
        
        const img = document.createElement('img');
        img.src = `{{ asset('storage') }}/${imagePath}`;
        img.alt = `Imagen ${index + 1}`;
        img.className = 'grid-image';
        img.onerror = function() {
            this.src = 'https://via.placeholder.com/200x150?text=Imagen+no+disponible';
        };
        img.onclick = () => openSingleImage(imagePath);
        
        const imageInfo = document.createElement('div');
        imageInfo.className = 'image-info';
        
        const fileName = imagePath.split('/').pop();
        const fileNameElement = document.createElement('div');
        fileNameElement.className = 'text-xs text-gray-500 truncate mb-1';
        fileNameElement.title = fileName;
        fileNameElement.textContent = fileName.length > 20 ? fileName.substring(0, 20) + '...' : fileName;
        
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'download-image-btn';
        downloadBtn.innerHTML = '<i class="fas fa-download mr-1"></i> Descargar';
        downloadBtn.onclick = (e) => {
            e.stopPropagation();
            downloadSingleImage(imagePath, index);
        };
        
        imageInfo.appendChild(fileNameElement);
        imageInfo.appendChild(downloadBtn);
        
        imageItem.appendChild(imageNumber);
        imageItem.appendChild(img);
        imageItem.appendChild(imageInfo);
        
        imageGrid.appendChild(imageItem);
    });
}

function openSingleImage(imagePath) {
    const modal = document.getElementById('singleImageModal');
    const imgElement = document.getElementById('singleModalImg');
    
    imgElement.src = `{{ asset('storage') }}/${imagePath}`;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function downloadSingleImage(imagePath, index) {
    const link = document.createElement('a');
    link.href = `{{ asset('storage') }}/${imagePath}`;
    const fileName = imagePath.split('/').pop() || `imagen-${index + 1}-${Date.now()}.jpg`;
    link.download = fileName;
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadAllImages() {
    currentImages.forEach((imagePath, index) => {
        setTimeout(() => {
            const link = document.createElement('a');
            link.href = `{{ asset('storage') }}/${imagePath}`;
            const fileName = imagePath.split('/').pop() || `imagen-${index + 1}-${Date.now()}.jpg`;
            link.download = fileName;
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }, index * 300);
    });
}

function closeAllImagesModal() {
    document.getElementById('allImagesModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentImages = [];
}

function closeSingleImageModal() {
    document.getElementById('singleImageModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (!document.getElementById('singleImageModal').classList.contains('hidden')) {
            closeSingleImageModal();
        } else if (!document.getElementById('allImagesModal').classList.contains('hidden')) {
            closeAllImagesModal();
        }
    }
});

document.querySelectorAll('#allImagesModal > div, #singleImageModal > div').forEach(modalContent => {
    modalContent.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cell-ok, .cell-warning, .cell-danger').forEach(celda => {
        const botonesAgregar = celda.querySelectorAll('a[href*="create-quick"]');
        botonesAgregar.forEach(boton => boton.style.display = 'none');
    });
});
</script>
@endsection