@extends('layouts.app')

@section('title', 'Análisis de Componentes')

@section('content')
<style>
    .sticky-top { position: sticky; top: 0; z-index: 30; }
    .sticky-left { position: sticky; left: 0; z-index: 20; }
    .sticky-top-left { position: sticky; top: 0; left: 0; z-index: 40; }
    .cell-ok { background-color: #f0f9ff; border-left: 4px solid #10b981; }
    .cell-warning { background-color: #fffbeb; border-left: 4px solid #f59e0b; }
    .cell-danger { background-color: #fef2f2; border-left: 4px solid #ef4444; }
    .cell-empty { background-color: #f9fafb; }
    .cell-header { background-color: #eff6ff; }
    
    .compact-table td, .compact-table th {
        padding: 8px !important;
        font-size: 0.75rem !important;
        min-width: 120px;
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
    
    .analysis-cell {
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        min-height: 120px;
    }
    
    .analysis-cell.no-data {
        cursor: default;
    }
    
    .analysis-cell.no-data:hover {
        background-color: inherit !important;
        transform: none;
    }
    
    .click-indicator {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 10px;
        color: #3b82f6;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .analysis-cell:not(.no-data):hover .click-indicator {
        opacity: 1;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .detail-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .detail-card h4 {
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-card p {
        font-size: 15px;
        color: #374151;
        line-height: 1.5;
    }
    
    .activity-content {
        background: #f9fafb;
        border-radius: 8px;
        padding: 15px;
        margin-top: 5px;
        border-left: 4px solid #3b82f6;
    }
    
    .activity-content p {
        white-space: pre-wrap;
        word-break: break-word;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .status-badge.ok {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-badge.warning {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-badge.danger {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .detail-images-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        margin-top: 20px;
    }
    
    .detail-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
    }
    
    .empty-cell {
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #6b7280;
        padding: 10px;
    }
    
    .no-records {
        text-align: center;
        padding: 20px;
        color: #6b7280;
    }
    
    .empty-cell-icon {
        font-size: 24px;
        margin-bottom: 8px;
        color: #d1d5db;
    }
    
    .component-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }
    
    .component-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }
    
    .component-code {
        font-size: 9px;
        color: #6b7280;
        background: #f3f4f6;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .reductor-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }
    
    .reductor-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }
    
    .reductor-label {
        font-size: 9px;
        color: #6b7280;
        background: #f3f4f6;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .table-wrapper {
        position: relative;
        overflow: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }
    
    .table-corner {
        background: #eff6ff;
        border-right: 1px solid #dbeafe;
        border-bottom: 1px solid #dbeafe;
    }
    
    .scroll-indicator {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        z-index: 50;
        display: none;
    }
    
    .table-wrapper:hover .scroll-indicator {
        display: block;
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
                <select name="linea_id" id="lineaSelect" class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
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
                    @foreach($todosComponentes as $key => $nombre)
                        <option value="{{ $key }}" {{ request('componente_id') == $key ? 'selected' : '' }}>
                            {{ $nombre }}
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
                    @php
                        // Obtener TODOS los reductores de TODAS las líneas
                        $todosReductores = [];
                        $reductoresPorLinea = [
                            'L-04' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                      'Reductor 18', 'Reductor 19', 'Reductor Loca'],
                            'L-05' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                                      'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                                      'Reductor 11', 'Reductor 12', 'Reductor Principal', 'Reductor Loca'],
                            'L-06' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                      'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22'],
                            'L-07' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                      'Reductor 18', 'Reductor 19', 'Reductor 20', 'Reductor 21', 'Reductor 22'],
                            'L-08' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                      'Reductor 18', 'Reductor 19', 'Reductor Loca'],
                            'L-09' => ['Reductor 1', 'Reductor 9', 'Reductor 10', 'Reductor 11', 'Reductor 12', 
                                      'Reductor 13', 'Reductor 14', 'Reductor 15', 'Reductor 16', 'Reductor 17', 
                                      'Reductor 18', 'Reductor 19', 'Reductor Loca'],
                            'L-12' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                                      'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                                      'Reductor 11', 'Reductor 12', 'Reductor Loca'],
                            'L-13' => ['Reductor 1', 'Reductor 2', 'Reductor 3', 'Reductor 4', 'Reductor 5', 
                                      'Reductor 6', 'Reductor 7', 'Reductor 8', 'Reductor 9', 'Reductor 10', 
                                      'Reductor 11', 'Reductor 12', 'Reductor Loca', 'Reductor Principal']
                        ];
                        
                        // Combinar todos los reductores únicos
                        foreach ($reductoresPorLinea as $lineaReductores) {
                            foreach ($lineaReductores as $reductor) {
                                $todosReductores[$reductor] = $reductor;
                            }
                        }
                        
                        // Ordenar alfabéticamente
                        ksort($todosReductores);
                    @endphp
                    
                    @foreach($todosReductores as $reductor)
                        <option value="{{ $reductor }}" {{ request('reductor') == $reductor ? 'selected' : '' }}>
                            {{ $reductor }}
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

    {{-- TABLA PRINCIPAL --}}
    @php
        $analisisCollection = collect($analisis->items());
        
        // Determinar qué línea mostrar
        $lineaMostrar = null;
        if(request('linea_id')) {
            $lineaMostrar = $lineas->firstWhere('id', request('linea_id'));
        } elseif($analisisCollection->count() > 0) {
            $lineaMostrar = $analisisCollection->first()->linea;
        }
        
        // Obtener componentes para la tabla según la línea
        $componentesParaTabla = [];
        if ($lineaMostrar && isset($componentesPorLinea[$lineaMostrar->nombre])) {
            $componentesLinea = $componentesPorLinea[$lineaMostrar->nombre];
            foreach ($componentesLinea as $id => $nombre) {
                $componentesParaTabla[] = (object) ['id' => $id, 'nombre' => $nombre];
            }
        }
        
        // Filtrar componentes si hay filtro específico
        if(request('componente_id')) {
            $componentesParaTabla = array_filter($componentesParaTabla, function($c) {
                return $c->id == request('componente_id');
            });
            $componentesParaTabla = array_values($componentesParaTabla);
        }
        
        // Determinar reductores a mostrar
        if(request('linea_id') && isset($reductoresMostrar) && count($reductoresMostrar) > 0) {
            $reductoresParaTabla = $reductoresMostrar;
        } elseif($analisisCollection->count() > 0) {
            $reductoresParaTabla = $analisisCollection->pluck('reductor')
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        } else {
            $reductoresParaTabla = ['Reductor 1', 'Reductor 2', 'Reductor 3'];
        }
        
        // Filtrar reductores si hay filtro específico
        if(request('reductor')) {
            $reductoresParaTabla = array_filter($reductoresParaTabla, function($r) {
                return $r == request('reductor');
            });
            $reductoresParaTabla = array_values($reductoresParaTabla);
        }
        
        // Obtener análisis agrupados por reductor y componente - CORREGIDO


    // Obtener análisis agrupados por reductor y componente - CORREGIDO
    $analisisAgrupados = [];
    foreach($analisisCollection as $analisisItem) {
        $reductor = $analisisItem->reductor;
        $componente = $analisisItem->componente;
        
        if (!$componente) {
            continue;
        }
        
        // Obtener el código base del componente (sin sufijos)
        $componenteCodigo = $componente->codigo;
        $codigoBase = $componenteCodigo;
        
        // Extraer el código base (sin sufijos como _L_04)
        foreach ($componentesPorLinea as $lineaCodigos) {
            foreach ($lineaCodigos as $codKey => $nombre) {
                if (str_contains($componenteCodigo, $codKey)) {
                    $codigoBase = $codKey;
                    break 2;
                }
            }
        }
        
        if(!isset($analisisAgrupados[$reductor])) {
            $analisisAgrupados[$reductor] = [];
        }
        
        // Usar el código base como clave
        $analisisAgrupados[$reductor][$codigoBase] = $analisisItem;
    }
    
    // DEBUG (opcional para verificar)
    // \Log::info('Analisis agrupados:', $analisisAgrupados);

    @endphp

    @if($lineaMostrar || $analisis->total() > 0)
        <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            {{-- ENCABEZADO DE LÍNEA --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-3">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <h2 class="font-bold text-lg">
                        <i class="fas fa-washing-machine mr-2"></i>
                        Lavadora: {{ $lineaMostrar->nombre ?? 'Seleccione una lavadora' }}
                        @if(request('componente_id'))
                            <span class="text-sm font-normal ml-2">
                                | Componente: {{ $todosComponentes[request('componente_id')] ?? request('componente_id') }}
                            </span>
                        @endif
                        @if(request('reductor'))
                            <span class="text-sm font-normal ml-2">
                                | Reductor: {{ request('reductor') }}
                            </span>
                        @endif
                    </h2>
                    <div class="text-sm text-blue-100">
                        <i class="fas fa-table mr-1"></i>
                        {{ count($componentesParaTabla) }} componentes × {{ count($reductoresParaTabla) }} reductores
                    </div>
                </div>
            </div>

            {{-- TABLA COMPACTA --}}
            @if(count($componentesParaTabla) > 0 && count($reductoresParaTabla) > 0)
                <div class="table-wrapper">
                    <div class="scroll-indicator">
                        <i class="fas fa-arrows-alt-h mr-1"></i> Desplázate para ver más
                    </div>
                    <table class="w-full compact-table border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="sticky-top-left sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm table-corner">
                                    <div class="reductor-header">
                                        <div class="reductor-name">REDUCTOR</div>
                                        <div class="reductor-label">COMPONENTE</div>
                                    </div>
                                </th>
                                @foreach($componentesParaTabla as $c)
                                    <th class="sticky-top cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm">
                                        <div class="component-header">
                                            <div class="component-name">{{ $c->nombre }}</div>
                                            <div class="component-code">{{ $c->id }}</div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reductoresParaTabla as $r)
                                <tr>
                                    <th class="sticky-left cell-header text-blue-900 font-bold px-3 py-2 border text-center whitespace-nowrap text-sm align-top">
                                        <div class="reductor-header">
                                            <div class="reductor-name">{{ $r }}</div>
                                            <div class="reductor-label">Reductor</div>
                                        </div>
                                    </th>
                                    
                                    @foreach($componentesParaTabla as $c)
                                        @php
                                            // Buscar si existe registro para este reductor y componente
                                            $registro = null;
                                            if(isset($analisisAgrupados[$r])) {
                                                // Buscar por código del componente (ej: 'SERVO_CHICO')
                                                foreach($analisisAgrupados[$r] as $codigoComponente => $analisisItem) {
                                                    if($codigoComponente == $c->id) {
                                                        $registro = $analisisItem;
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            $hasData = !empty($registro);
                                            $color = '';
                                            $totalImagenes = 0;
                                            
                                            if($hasData){
                                                $estadoActual = $registro->estado ?? 'Buen estado';
                                                $color = str_contains($estadoActual, 'Dañado') ? 'cell-danger'
                                                       : (str_contains($estadoActual, 'Desgaste') ? 'cell-warning' : 'cell-ok');
                                                
                                                $imagenes = $registro->evidencia_fotos ?? null;
                                                
                                                if (is_string($imagenes)) {
                                                    $imagenes = json_decode($imagenes, true) ?? [];
                                                } elseif (is_array($imagenes)) {
                                                    $imagenes = $imagenes;
                                                } else {
                                                    $imagenes = [];
                                                }
                                                
                                                $totalImagenes = count($imagenes);
                                            }
                                        @endphp
                                        
                                        <td class="border px-3 py-2 align-top {{ $hasData ? $color : 'cell-empty' }} {{ $hasData ? 'analysis-cell' : 'analysis-cell no-data' }}" 
                                            @if($hasData)
                                            onclick="openAnalysisDetail({{ json_encode([
                                                'id' => $registro->id,
                                                'linea' => $registro->linea->nombre ?? 'Sin nombre',
                                                'componente' => $registro->componente->nombre ?? 'Sin nombre',
                                                'reductor' => $registro->reductor,
                                                'fecha_analisis' => $registro->fecha_analisis->format('d/m/Y'),
                                                'numero_orden' => $registro->numero_orden,
                                                'estado' => $registro->estado ?? 'Buen estado',
                                                'actividad' => $registro->actividad,
                                                'imagenes' => $imagenes,
                                                'color' => $color,
                                                'created_at' => $registro->created_at->format('d/m/Y H:i'),
                                                'updated_at' => $registro->updated_at->format('d/m/Y H:i'),
                                            ]) }})"
                                            @endif>
                                            
                                            @if($hasData)
                                                <div class="space-y-2">
                                                    <div class="flex flex-col">
                                                        <div class="flex items-center gap-1 mb-1">
                                                            <i class="fas fa-calendar text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-semibold text-gray-700">
                                                                {{ $registro->fecha_analisis->format('d/m/Y') }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <i class="fas fa-hashtag text-blue-600 text-xs"></i>
                                                            <span class="text-xs font-bold text-gray-800">
                                                                Orden #{{ $registro->numero_orden }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        @php
                                                            $estadoActual = $registro->estado ?? 'Buen estado';
                                                            $statusClass = $color == 'cell-ok' ? 'bg-green-100 text-green-800 border-green-200' 
                                                               : ($color == 'cell-warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' 
                                                               : 'bg-red-100 text-red-800 border-red-200');
                                                            $icon = $color == 'cell-ok' ? 'fa-check-circle' 
                                                               : ($color == 'cell-warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $statusClass }}">
                                                            <i class="fas {{ $icon }} mr-1"></i>
                                                            {{ $estadoActual }}
                                                        </span>
                                                    </div>
                                                    
                                                    <div>
                                                        <p class="text-gray-700 text-xs">
                                                            {{ Str::limit($registro->actividad, 80) }}
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="flex flex-col gap-1 mt-3">
                                                        @if($totalImagenes > 0)
                                                            <button onclick="event.stopPropagation(); openAllImages({{ json_encode($imagenes) }}, 
                                                                    '{{ $registro->fecha_analisis->format('d/m/Y') }}', 
                                                                    '{{ $registro->numero_orden }}', 
                                                                    '{{ $registro->estado ?? 'Buen estado' }}')"
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
                                                        
                                                        <a href="{{ route('analisis-componentes.edit', $registro->id) }}"
                                                           class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition text-xs font-medium"
                                                           onclick="event.stopPropagation();">
                                                            <i class="fas fa-edit"></i>
                                                            Editar Registro
                                                        </a>
                                                    </div>
                                                    
                                                    @if($hasData)
                                                        <div class="click-indicator">
                                                            <i class="fas fa-search-plus mr-1"></i> Ver detalles
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="empty-cell">
                                                    <div class="empty-cell-icon">
                                                        <i class="fas fa-clipboard"></i>
                                                    </div>
                                                    <p class="text-gray-500 text-xs mb-3">Sin análisis registrado</p>
                                                    
                                                    @if($lineaMostrar)
                                                        <a href="{{ route('analisis-componentes.create-quick',[
                                                            'linea_id' => $lineaMostrar->id,
                                                            'componente_codigo' => $c->codigo ?? $c->id,
                                                            'reductor' => $r,
                                                            'fecha' => request('fecha', now()->format('Y-m'))]) }}"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-plus"></i>
                                                            Agregar Análisis
                                                        </a>
                                                    @else
                                                        <a href="{{ route('analisis-componentes.select-linea') }}"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs font-medium"
                                                        onclick="event.stopPropagation();">
                                                            <i class="fas fa-plus"></i>
                                                            Seleccionar Lavadora
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-info-circle text-3xl mb-4"></i>
                    <p>No hay componentes o reductores para mostrar</p>
                </div>
            @endif
        </div>
        
        {{-- PAGINACIÓN --}}
        @if($analisis->total() > 0)
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
        @endif
    @else
        {{-- VISTA INICIAL SIN FILTROS --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-clipboard-list text-4xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Seleccione una lavadora para comenzar</h3>
            <p class="text-gray-500 mb-4">Utilice los filtros para ver los análisis de componentes.</p>
            <p class="text-sm text-gray-400 mb-6">
                <i class="fas fa-info-circle mr-1"></i>
                La tabla mostrará todos los componentes y reductores disponibles, incluso si no tienen registros.
            </p>
            <a href="{{ route('analisis-componentes.select-linea') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus"></i>
                Crear Nuevo Análisis
            </a>
        </div>
    @endif
</div>

{{-- MODALES --}}
<div id="analysisDetailModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4"
     onclick="closeAnalysisDetailModal()">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <div>
                <h3 class="font-bold text-lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span id="detailModalTitle">Detalle del Análisis</span>
                </h3>
                <div class="text-sm text-blue-100 mt-1">
                    <span id="detailModalSubtitle">Información completa del registro</span>
                </div>
            </div>
            <button onclick="closeAnalysisDetailModal()"
                    class="text-white hover:text-yellow-300 text-2xl transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-auto max-h-[calc(90vh-80px)]">
            <div class="detail-grid">
                <div class="detail-card">
                    <h4><i class="fas fa-washing-machine mr-2"></i>Lavadora</h4>
                    <p id="detail-linea" class="font-semibold text-lg"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-cog mr-2"></i>Componente</h4>
                    <p id="detail-componente" class="font-semibold text-lg"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-compress-alt mr-2"></i>Reductor</h4>
                    <p id="detail-reductor" class="font-semibold text-lg"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="far fa-calendar-alt mr-2"></i>Fecha de Análisis</h4>
                    <p id="detail-fecha" class="font-semibold"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-hashtag mr-2"></i>Número de Orden</h4>
                    <p id="detail-orden" class="font-semibold text-lg"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-clipboard-check mr-2"></i>Estado</h4>
                    <div id="detail-estado" class="status-badge ok mt-2"></div>
                </div>
                
                <div class="detail-card">
                    <h4><i class="far fa-calendar-plus mr-2"></i>Fecha de Creación</h4>
                    <p id="detail-created" class="text-sm text-gray-600"></p>
                </div>
                
                <div class="detail-card">
                    <h4><i class="far fa-calendar-check mr-2"></i>Última Actualización</h4>
                    <p id="detail-updated" class="text-sm text-gray-600"></p>
                </div>
            </div>
            
            {{-- Actividad --}}
            <div class="detail-card mt-6">
                <h4><i class="fas fa-sticky-note mr-2"></i>Actividad / Observaciones</h4>
                <div class="activity-content">
                    <p id="detail-actividad"></p>
                </div>
            </div>
            
            {{-- Imágenes --}}
            <div id="detail-images-section" class="detail-images-container hidden">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-images mr-2"></i>Evidencia Fotográfica
                </h4>
                <div id="detail-image-grid" class="image-grid"></div>
            </div>
            
            <div class="detail-actions">
                <a id="detail-edit-btn" href="#"
                   class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    Editar Registro
                </a>
                
                <button id="detail-images-btn" onclick="showDetailImages()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-images"></i>
                    Ver Imágenes
                </button>
                
                <button onclick="closeAnalysisDetailModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
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
let currentAnalysisData = null;

function openAnalysisDetail(analysisData) {
    currentAnalysisData = analysisData;
    const modal = document.getElementById('analysisDetailModal');
    
    document.getElementById('detail-linea').textContent = analysisData.linea;
    document.getElementById('detail-componente').textContent = analysisData.componente;
    document.getElementById('detail-reductor').textContent = analysisData.reductor;
    document.getElementById('detail-fecha').textContent = analysisData.fecha_analisis;
    document.getElementById('detail-orden').textContent = analysisData.numero_orden;
    document.getElementById('detail-actividad').textContent = analysisData.actividad;
    document.getElementById('detail-created').textContent = analysisData.created_at;
    document.getElementById('detail-updated').textContent = analysisData.updated_at;
    
    const editBtn = document.getElementById('detail-edit-btn');
    editBtn.href = `/analisis-componentes/${analysisData.id}/edit`;
    
    const estadoElement = document.getElementById('detail-estado');
    estadoElement.textContent = analysisData.estado;
    
    estadoElement.classList.remove('ok', 'warning', 'danger');
    
    if (analysisData.color === 'cell-ok') {
        estadoElement.classList.add('ok');
    } else if (analysisData.color === 'cell-warning') {
        estadoElement.classList.add('warning');
    } else if (analysisData.color === 'cell-danger') {
        estadoElement.classList.add('danger');
    }
    
    const imagesBtn = document.getElementById('detail-images-btn');
    const imagesSection = document.getElementById('detail-images-section');
    
    if (analysisData.imagenes && analysisData.imagenes.length > 0) {
        imagesBtn.style.display = 'flex';
        imagesSection.classList.remove('hidden');
        buildDetailImageGrid(analysisData.imagenes);
    } else {
        imagesBtn.style.display = 'none';
        imagesSection.classList.add('hidden');
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function buildDetailImageGrid(imagenes) {
    const imageGrid = document.getElementById('detail-image-grid');
    imageGrid.innerHTML = '';
    
    imagenes.forEach((imagePath, index) => {
        const imageItem = document.createElement('div');
        imageItem.className = 'image-item';
        
        const imageNumber = document.createElement('div');
        imageNumber.className = 'image-number';
        imageNumber.textContent = index + 1;
        
        const img = document.createElement('img');
        img.src = `{{ Storage::url('') }}${imagePath}`;
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

function showDetailImages() {
    if (currentAnalysisData && currentAnalysisData.imagenes && currentAnalysisData.imagenes.length > 0) {
        openAllImages(
            currentAnalysisData.imagenes,
            currentAnalysisData.fecha_analisis,
            currentAnalysisData.numero_orden,
            currentAnalysisData.estado
        );
    }
}

function closeAnalysisDetailModal() {
    document.getElementById('analysisDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentAnalysisData = null;
}

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
        img.src = `{{ Storage::url('') }}${imagePath}`;
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
    
    imgElement.src = `{{ Storage::url('') }}${imagePath}`;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function downloadSingleImage(imagePath, index) {
    const link = document.createElement('a');
    link.href = `{{ Storage::url('') }}${imagePath}`;
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
            link.href = `{{ Storage::url('') }}${imagePath}`;
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
        } else if (!document.getElementById('analysisDetailModal').classList.contains('hidden')) {
            closeAnalysisDetailModal();
        }
    }
});

document.querySelectorAll('#allImagesModal > div, #singleImageModal > div, #analysisDetailModal > div').forEach(modalContent => {
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

document.addEventListener('click', function(e) {
    if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('button') || e.target.closest('a')) {
        e.stopPropagation();
    }
});
</script>
@endsection