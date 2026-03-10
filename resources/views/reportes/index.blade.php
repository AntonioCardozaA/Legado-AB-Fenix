@extends('layouts.app')

@section('title', 'Reportes de Lavadoras')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- ENCABEZADO -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Centro de Reportes</h1>
        <p class="mt-2 text-gray-600">Visualiza y exporta reportes detallados de tus equipos</p>
    </div>

    <!-- FILTROS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <form action="{{ route('reportes.show') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Equipo</label>
                    <select name="tipo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="lavadoras" {{ request('tipo') == 'lavadoras' ? 'selected' : '' }}>Lavadoras</option>
                        <option value="pasteurizadoras" {{ request('tipo') == 'pasteurizadoras' ? 'selected' : '' }}>Pasteurizadoras</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio"
                        value="{{ request('fecha_inicio', now()->subMonth()->format('Y-m-d')) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                    <input type="date" name="fecha_fin"
                        value="{{ request('fecha_fin', now()->format('Y-m-d')) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Generar Reporte General
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- TARJETAS DE LÍNEAS CON LOS 5 MÓDULOS DE LA IMAGEN -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        @forelse($lineas as $linea)
            @php
                $reporteLinea = $reporteGeneral[$linea->id] ?? [];
                $estado = $reporteLinea['estado_general'] ?? ['texto' => 'SIN DATOS', 'color' => 'gray'];
                
                // Datos de elongación - Usar datos del reporte en lugar de consulta adicional
                $promedioBombas = $reporteLinea['promedio_bombas'] ?? 0;
                $promedioVapor = $reporteLinea['promedio_vapor'] ?? 0;
                $maxElongacion = $reporteLinea['elongacion_max'] ?? 0;
                
                // Datos de análisis 52-12-4 - Usar datos del reporte
                $totalDaños4 = $reporteLinea['total_danos_4'] ?? 0;
                $analisisTendenciaCount = $reporteLinea['analisis_tendencia_count'] ?? 0;
                
                // Icono según el tipo
                $icono = $tipoEquipo == 'lavadoras' ? 'fa-soap' : 'fa-flask';
                $colorIcono = $tipoEquipo == 'lavadoras' ? 'text-blue-600' : 'text-green-600';
                $bgIcono = $tipoEquipo == 'lavadoras' ? 'bg-blue-100' : 'bg-green-100';
                $textoTipo = $tipoEquipo == 'lavadoras' ? 'Línea de Lavado' : 'Línea de Pasteurización';
                
                // Determinar color de elongación
                $elongacionColor = 'text-emerald-900';
                if ($maxElongacion >= 2.4) {
                    $elongacionColor = 'text-red-600';
                } elseif ($maxElongacion >= 2.0) {
                    $elongacionColor = 'text-yellow-600';
                }
                
                // Determinar color de estado
                $estadoColor = $estado['color'] ?? 'gray';
            @endphp

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">

                <!-- CABECERA DE LA LÍNEA -->
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 {{ $bgIcono }} rounded-lg">
                                <i class="fas {{ $icono }} {{ $colorIcono }} text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $linea->nombre }}</h3>
                                <p class="text-sm text-gray-500">{{ $textoTipo }}</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-{{ $estadoColor }}-100 text-{{ $estadoColor }}-800">
                            {{ $estado['texto'] }}
                        </span>
                    </div>
                </div>

                <!-- CUERPO CON LOS 5 MÓDULOS DE LA IMAGEN -->
                <div class="p-6">

                    <!-- Grid de los 5 módulos principales -->
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                        
                        <!-- 1. ANÁLISIS LAVADORA -->
                        <div class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-chart-pie text-blue-600 text-sm"></i>
                                <span class="text-xs font-semibold text-blue-800 uppercase tracking-wider">ANÁLISIS</span>
                            </div>
                            <p class="text-lg font-bold text-blue-900">{{ $reporteLinea['total_analisis'] ?? 0 }}</p>
                            <p class="text-xs text-blue-700">Componentes: {{ $reporteLinea['componentes_revisados'] ?? 0 }}/{{ $reporteLinea['total_componentes'] ?? 0 }}</p>
                        </div>

                        <!-- 2. PLAN DE ACCIÓN (COMPONENTES CRÍTICOS) -->
                        <div class="bg-amber-50 rounded-lg p-3 border border-amber-100">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-clipboard-list text-amber-600 text-sm"></i>
                                <span class="text-xs font-semibold text-amber-800 uppercase tracking-wider">PLAN ACCIÓN</span>
                            </div>
                            <p class="text-lg font-bold text-amber-900">{{ $reporteLinea['componentes_criticos'] ?? 0 }}</p>
                            <p class="text-xs text-amber-700">pendientes de ejecución</p>
                        </div>

                        <!-- 3. ELONGACIÓN LAVADORA -->
                        <div class="bg-emerald-50 rounded-lg p-3 border border-emerald-100">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-ruler text-emerald-600 text-sm"></i>
                                <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">ELONGACIÓN</span>
                            </div>
                            <p class="text-lg font-bold {{ $elongacionColor }}">
                                {{ number_format($maxElongacion, 2) }}%
                            </p>
                            <p class="text-xs text-emerald-700">Bombas: {{ number_format($promedioBombas, 2) }}%</p>
                        </div>

                        <!-- 4. ANÁLISIS 52-12-4 -->
                        <div class="bg-purple-50 rounded-lg p-3 border border-purple-100">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-flask text-purple-600 text-sm"></i>
                                <span class="text-xs font-semibold text-purple-800 uppercase tracking-wider">52-12-4</span>
                            </div>
                            <p class="text-lg font-bold text-purple-900">{{ $analisisTendenciaCount }}</p>
                            <p class="text-xs text-purple-700">Daños 4s: {{ number_format($totalDaños4, 2) }}</p>
                        </div>

                        <!-- 5. HISTÓRICO DE REVISADOS -->
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-history text-gray-600 text-sm"></i>
                                <span class="text-xs font-semibold text-gray-800 uppercase tracking-wider">HISTÓRICO</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900">{{ $reporteLinea['historicos'] ?? 0 }}</p>
                            @if(!empty($reporteLinea['ultima_revision']))
                                <p class="text-xs text-gray-500 truncate">Últ: {{ $reporteLinea['ultima_revision'] }}</p>
                            @endif
                        </div>

                        <!-- 6. REDUCTORES (si existen) -->
                        @if(($reporteLinea['reductores_count'] ?? 0) > 0)
                        <div class="bg-indigo-50 rounded-lg p-3 border border-indigo-200">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-compress-alt text-indigo-600 text-sm"></i>
                                <span class="text-xs font-semibold text-indigo-800">REDUCTORES</span>
                                <span class="ml-auto text-sm font-bold text-indigo-900">{{ $reporteLinea['reductores_count'] }}</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- ACCIONES: Ver Detalle y Exportar -->
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 mt-4">
                        <a href="{{ route('reportes.show', [
                                    'lineaId' => $linea->id, 
                                    'tipo' => $tipoEquipo,
                                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                                    'fecha_fin' => $fechaFin->format('Y-m-d')
                                ]) }}"
                                class="flex-1 text-center bg-blue-50 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium">
                                    <i class="fas fa-chart-line mr-2"></i>
                                    Ver Detalle Completo
                        </a>

                        <form action="{{ route('reportes.export-pdf') }}" method="GET" class="flex-1">
                            
                            <input type="hidden" name="export_format" value="pdf">
                            <input type="hidden" name="export_tipo" value="linea">
                            <input type="hidden" name="lineaId" value="{{ $linea->id }}">
                            <input type="hidden" name="tipo" value="{{ $tipoEquipo }}">
                            <input type="hidden" name="fecha_inicio" value="{{ $fechaInicio->format('Y-m-d') }}">
                            <input type="hidden" name="fecha_fin" value="{{ $fechaFin->format('Y-m-d') }}">
                            <button type="submit" 
                                    class="w-full text-center bg-green-50 text-green-700 px-4 py-2 rounded-lg hover:bg-green-100 transition-colors text-sm font-medium">
                                <i class="fas fa-file-pdf mr-2"></i>
                                Exportar PDF
                            </button>
                        </form>

                        <form action="{{ route('reportes.export-excel') }}" method="GET" class="flex-1">
                            <input type="hidden" name="export_format" value="excel">
                            <input type="hidden" name="export_tipo" value="linea">
                            <input type="hidden" name="lineaId" value="{{ $linea->id }}">
                            <input type="hidden" name="tipo" value="{{ $tipoEquipo }}">
                            <input type="hidden" name="fecha_inicio" value="{{ $fechaInicio->format('Y-m-d') }}">
                            <input type="hidden" name="fecha_fin" value="{{ $fechaFin->format('Y-m-d') }}">
                            <button type="submit" 
                                    class="w-full text-center bg-orange-50 text-orange-700 px-4 py-2 rounded-lg hover:bg-orange-100 transition-colors text-sm font-medium">
                                <i class="fas fa-file-excel mr-2"></i>
                                Exportar Excel
                            </button>
                        </form>
                    </div>

                </div>
            </div>

        @empty
            <div class="col-span-2 text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
                <i class="fas fa-exclamation-circle text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">No hay líneas de {{ $tipoEquipo }} disponibles</p>
            </div>
        @endforelse

    </div>

    <!-- SECCIÓN DE ANÁLISIS DETALLADOS POR LÍNEA -->
    @if(isset($reporteDetallado) && isset($reporteDetallado['lineas']) && count($reporteDetallado['lineas']) > 0)
    <div class="mt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-clipboard-list text-blue-600"></i>
            Análisis Detallados por Línea
        </h2>
        
        @foreach($reporteDetallado['lineas'] as $reporteLineaDetallado)
            @php
                $linea = $reporteLineaDetallado['linea'];
                $componentesLista = $reporteLineaDetallado['componentes_lista'] ?? collect([]);
                $reductoresLista = $reporteLineaDetallado['reductores_lista'] ?? collect([]);
                $analisisAgrupados = $reporteLineaDetallado['analisis_agrupados'] ?? [];
            @endphp
            
            @if($componentesLista->count() > 0 && $reductoresLista->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                    <!-- Header de la línea -->
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <i class="fas fa-washing-machine text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $linea->nombre }}</h3>
                                    <p class="text-sm text-gray-500">Análisis: {{ $reporteLineaDetallado['resumen']['total_analisis'] ?? 0 }}</p>
                                </div>
                            </div>
                            <a href="{{ route('reportes.show', ['lineaId' => $linea->id, 'tipo' => $tipoEquipo, 'fecha_inicio' => $fechaInicio->format('Y-m-d'), 'fecha_fin' => $fechaFin->format('Y-m-d')]) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Ver detalle completo <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Tabla de análisis -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="sticky-left bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                        Reductor
                                    </th>
                                    @foreach($componentesLista as $componente)
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r min-w-[150px]">
                                            <div class="flex flex-col items-center">
                                                <span class="font-bold text-gray-700">{{ $componente->nombre }}</span>
                                                <span class="text-gray-400 text-[10px]">{{ $componente->codigo }}</span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($reductoresLista as $reductor)
                                    <tr class="hover:bg-gray-50">
                                        <td class="sticky-left bg-white px-4 py-3 text-sm font-medium text-gray-900 border-r">
                                            {{ $reductor }}
                                        </td>
                                        
                                        @foreach($componentesLista as $componente)
                                            @php
                                                $analisis = $analisisAgrupados[$reductor][$componente->codigo] ?? [];
                                                $ultimoAnalisis = !empty($analisis) ? $analisis[0] : null;
                                                
                                                // Determinar clase de color según el estado
                                                $cellClass = 'bg-gray-50';
                                                if ($ultimoAnalisis) {
                                                    if ($ultimoAnalisis['estado'] === 'Cambiado') {
                                                        $cellClass = 'bg-blue-50';
                                                    } elseif ($ultimoAnalisis['estado'] === 'Dañado - Requiere cambio') {
                                                        $cellClass = 'bg-red-50';
                                                    } elseif (str_contains($ultimoAnalisis['estado'], 'Desgaste')) {
                                                        $cellClass = 'bg-yellow-50';
                                                    } else {
                                                        $cellClass = 'bg-green-50';
                                                    }
                                                }
                                            @endphp
                                            
                                            <td class="px-4 py-3 text-sm border-r {{ $cellClass }}">
                                                @if($ultimoAnalisis)
                                                    <div class="space-y-1">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-semibold 
                                                                @if($ultimoAnalisis['estado'] === 'Cambiado') text-blue-700
                                                                @elseif($ultimoAnalisis['estado'] === 'Dañado - Requiere cambio') text-red-700
                                                                @elseif(str_contains($ultimoAnalisis['estado'], 'Desgaste')) text-yellow-700
                                                                @else text-green-700
                                                                @endif">
                                                                {{ $ultimoAnalisis['estado'] }}
                                                            </span>
                                                            @if($ultimoAnalisis['is_new'] ?? false)
                                                                <span class="bg-red-500 text-white text-[8px] px-1.5 py-0.5 rounded-full">NUEVO</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-600">
                                                            <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>{{ $ultimoAnalisis['fecha_analisis_formateada'] }}
                                                        </div>
                                                        <div class="text-xs text-gray-600">
                                                            <i class="fas fa-hashtag mr-1 text-gray-400"></i>#{{ $ultimoAnalisis['numero_orden'] }}
                                                        </div>
                                                        @if(!empty($ultimoAnalisis['imagenes']))
                                                            <div class="text-xs text-blue-600">
                                                                <i class="fas fa-images mr-1"></i>{{ count($ultimoAnalisis['imagenes']) }} img
                                                            </div>
                                                        @endif
                                                        @if($ultimoAnalisis['lado'] ?? false)
                                                            <div class="text-xs">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium 
                                                                    {{ $ultimoAnalisis['lado'] === 'VAPOR' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                                                    {{ $ultimoAnalisis['lado'] }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        <div class="mt-2 flex gap-1">
                                                            <a href="{{ $ultimoAnalisis['edit_url'] }}" 
                                                               class="text-blue-600 hover:text-blue-800 text-[10px]"
                                                               onclick="event.stopPropagation()">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            @if(count($analisis) > 1)
                                                                <span class="text-gray-400 text-[10px] cursor-help" 
                                                                      title="{{ count($analisis) }} registros en total">
                                                                    <i class="fas fa-history"></i> {{ count($analisis) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-center text-gray-400 text-xs py-2">
                                                        <i class="fas fa-minus"></i>
                                                        <div class="mt-1">Sin datos</div>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Footer con estadísticas -->
                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 text-xs text-gray-600">
                        <div class="flex flex-wrap gap-4">
                            <span><i class="fas fa-chart-line text-blue-600 mr-1"></i> Total: {{ $reporteLineaDetallado['resumen']['total_analisis'] ?? 0 }}</span>
                            <span><i class="fas fa-cog text-green-600 mr-1"></i> Componentes: {{ $reporteLineaDetallado['resumen']['componentes_revisados'] ?? 0 }}/{{ $componentesLista->count() }}</span>
                            <span><i class="fas fa-exclamation-triangle text-red-600 mr-1"></i> Críticos: {{ $reporteLineaDetallado['resumen']['componentes_criticos'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @endif

    <!-- BOTÓN PARA EXPORTAR REPORTE GENERAL -->
    @if(count($lineas) > 0)
    <div class="mt-8 flex justify-end space-x-3">
        <form action="{{ route('reportes.export-pdf') }}" method="GET">
            <input type="hidden" name="export_format" value="pdf">
            <input type="hidden" name="export_tipo" value="completo">
            <input type="hidden" name="tipo" value="{{ $tipoEquipo }}">
            <input type="hidden" name="fecha_inicio" value="{{ $fechaInicio->format('Y-m-d') }}">
            <input type="hidden" name="fecha_fin" value="{{ $fechaFin->format('Y-m-d') }}">
            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-file-pdf mr-2"></i>Exportar Reporte General PDF
            </button>
        </form>
        
        <form action="{{ route('reportes.export-excel') }}" method="GET">
            <input type="hidden" name="export_format" value="excel">
            <input type="hidden" name="export_tipo" value="completo">
            <input type="hidden" name="tipo" value="{{ $tipoEquipo }}">
            <input type="hidden" name="fecha_inicio" value="{{ $fechaInicio->format('Y-m-d') }}">
            <input type="hidden" name="fecha_fin" value="{{ $fechaFin->format('Y-m-d') }}">
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-file-excel mr-2"></i>Exportar Reporte General Excel
            </button>
        </form>
    </div>
    @endif

</div>

<style>
    /* Estilos adicionales para la tabla de reportes */
    .sticky-left {
        position: sticky;
        left: 0;
        background: inherit;
        z-index: 10;
    }
    
    .table-container {
        max-height: 600px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }
    
    .analysis-cell {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .analysis-cell:hover {
        filter: brightness(0.95);
    }
    
    .badge-new {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }
    
    /* Scrollbar personalizada */
    .overflow-x-auto::-webkit-scrollbar {
        height: 8px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
@endsection