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
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
    .history-card {
        transition: all 0.3s ease;
    }
    .history-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
    .filters-container {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }
    .image-hover-zoom {
        transition: transform 0.3s ease;
    }
    .image-hover-zoom:hover {
        transform: scale(1.05);
    }
</style>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
    {{-- Header --}}
    <div class="mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-3 rounded-xl shadow-lg">
                <i class="fas fa-history text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                    Historial de Registros
                </h1>
            </div>
        </div>

        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}"
           class="group inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl transition-all shadow-sm hover:shadow-md">
            <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
        </a>
    </div>

    @if($analisis->count() > 0)
        @php
            $totalRegistros = $analisis->total();
            $conImagenes = $analisis->filter(function($item) {
                return $item->tiene_imagenes;
            })->count();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="rounded-xl p-5 shadow-sm bg-gradient-to-br from-gray-50 to-white border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-gray-100">
                        <i class="fas fa-clipboard-list text-gray-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wider text-gray-500">Total Registros</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $totalRegistros }}</p>
                    </div>
                </div>
            </div>
            
            <div class="rounded-xl p-5 shadow-sm bg-gradient-to-br from-gray-50 to-white border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-gray-100">
                        <i class="fas fa-images text-gray-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wider text-gray-500">Con Evidencia</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $conImagenes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative">
            <div class="absolute left-6 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-400 to-blue-600 rounded-full"></div>

            <div class="space-y-8">
                @foreach($analisis as $index => $item)
                    @php
                        $estado = $item->estado ?? 'Buen estado';
                        $badgeColor = match($estado) {
                            'Buen estado' => 'from-green-500 to-green-600',
                            'Desgaste moderado', 'Desgaste severo' => 'from-yellow-500 to-yellow-600',
                            'Dañado - Requiere cambio' => 'from-red-500 to-red-600',
                            'Cambiado' => 'from-blue-500 to-blue-600',
                            default => 'from-gray-500 to-gray-600',
                        };
                        $badgeIcon = match($estado) {
                            'Buen estado' => 'fa-check-circle',
                            'Desgaste moderado', 'Desgaste severo' => 'fa-exclamation-triangle',
                            'Dañado - Requiere cambio' => 'fa-times-circle',
                            'Cambiado' => 'fa-exchange-alt',
                            default => 'fa-question-circle',
                        };
                        
                        $imagenes = $item->evidencia_fotos ?? [];
                        $totalImagenes = count($imagenes);
                    @endphp

                    <div class="relative pl-16 history-card">
                        <div class="absolute left-3 top-6 w-8 h-8 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full border-4 border-white shadow-lg timeline-dot flex items-center justify-center text-white text-xs font-bold">
                            {{ $analisis->firstItem() + $index }}
                        </div>

                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all">
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
                                    
                                    @if($item->resuelto_por_cambio)
                                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-green-100 text-green-800 border border-green-200">
                                            <i class="fas fa-check-circle"></i> RESUELTO
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-temperature-high text-blue-600"></i>
                                            <span class="text-xs font-semibold uppercase text-gray-500">Línea</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->linea->nombre ?? 'N/A' }}</p>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-cubes text-blue-600"></i>
                                            <span class="text-xs font-semibold uppercase text-gray-500">Módulo</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->modulo_nombre }}</p>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-cog text-blue-600"></i>
                                            <span class="text-xs font-semibold uppercase text-gray-500">Componente</span>
                                        </div>
                                        <p class="font-medium text-gray-800">{{ $item->componente_nombre }}</p>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-arrows-alt-h text-blue-600"></i>
                                            <span class="text-xs font-semibold uppercase text-gray-500">Lado</span>
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

                                @if($item->nivel)
                                <div class="mb-4">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 rounded-full text-xs">
                                        <i class="fas fa-layer-group"></i> Nivel: {{ $item->nivel }}
                                    </span>
                                </div>
                                @endif

                                @if($item->total_piezas)
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>Piezas revisadas: {{ $item->revisadas_piezas ?? 0 }} / {{ $item->total_piezas }}</span>
                                        <span>{{ $item->porcentaje_avance }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $item->porcentaje_avance }}%"></div>
                                    </div>
                                </div>
                                @endif

                                {{-- Agregar después de la sección de progreso --}}
                                @if($item->total_piezas && $item->componentes_revisados && count($item->componentes_revisados) > 0)
                                <div class="mb-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-clipboard-check text-indigo-600 text-sm"></i>
                                        <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Componentes revisados:</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($item->componentes_revisados as $compNum)
                                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-indigo-100 text-indigo-700 text-xs font-medium">
                                                #{{ $compNum }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                
                                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 mb-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-sticky-note text-blue-600 text-xs"></i>
                                        </div>
                                        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Actividad</h4>
                                    </div>
                                    <div class="text-gray-700 whitespace-pre-line leading-relaxed pl-2 border-l-4 border-blue-400">
                                        {{ $item->actividad }}
                                    </div>
                                </div>

                                @if($totalImagenes > 0)
                                    <div class="bg-indigo-50 rounded-xl p-5 border border-indigo-100">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center">
                                                    <i class="fas fa-images text-indigo-600 text-xs"></i>
                                                </div>
                                                <h4 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Evidencia Fotográfica</h4>
                                            </div>
                                            <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium">
                                                {{ $totalImagenes }} {{ $totalImagenes == 1 ? 'imagen' : 'imágenes' }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                            @foreach($imagenes as $imgIndex => $imagen)
                                                <div class="relative group cursor-pointer" onclick="window.open('/storage/{{ $imagen }}', '_blank')">
                                                    <img src="/storage/{{ $imagen }}"
                                                         alt="Evidencia {{ $imgIndex + 1 }}"
                                                         class="w-full h-28 object-cover rounded-lg border-2 border-white shadow-md group-hover:shadow-xl transition-all image-hover-zoom">
                                                    <div class="absolute top-2 left-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                                                        <i class="fas fa-search-plus mr-1"></i> Ver
                                                    </div>
                                                    <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">
                                                        {{ $imgIndex + 1 }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                                        <div class="flex items-center justify-center gap-2 text-gray-400">
                                            <i class="fas fa-image text-lg"></i>
                                            <span class="text-sm">Sin imágenes adjuntas</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-4 flex justify-end gap-2">
                                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.edit', $item->id) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md text-sm font-medium">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.show', $item->id) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition shadow-md text-sm font-medium">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

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
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition shadow-lg font-medium">
                <i class="fas fa-plus-circle"></i> Nuevo Análisis
            </a>
        </div>
    @endif
</div>
@endsection