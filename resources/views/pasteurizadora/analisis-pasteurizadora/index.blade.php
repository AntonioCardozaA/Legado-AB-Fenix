@extends('layouts.app')

@section('title', 'Análisis de Pasteurizadoras')

@section('content')
<div class="max-w-full mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <a href="{{ route('pasteurizadora.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2 mt-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Análisis de Pasteurizadoras
            </h1>
        </div>

        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.select-linea') }}"
           class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition flex items-center gap-2 shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Análisis
        </a>
    </div>
    
    {{-- FILTROS --}}
    @php
        $lineasFiltradas = $lineasFiltradas ?? collect();
        $mostrarTodas = $mostrarTodas ?? true;
        $analisisCollection = isset($analisis) ? collect($analisis) : collect([]);
    @endphp
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => 'todas']) }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 {{ $mostrarTodas ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Todas
                </a>
                @foreach($lineasFiltradas as $l)
                    <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => $l->id]) }}" 
                       class="inline-flex items-center gap-4 px-9 py-4 rounded-full text-sm font-medium transition-all duration-200 {{ (!$mostrarTodas && request('linea_id') == $l->id) ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $l->nombre }}
                    </a>
                @endforeach
            </div>
            
            <div class="flex gap-2">
                <form method="GET" action="{{ route('pasteurizadora.analisis-pasteurizadora.index') }}" class="flex gap-2">
                    <input type="hidden" name="linea_id" value="{{ request('linea_id') }}">
                    <select name="modulo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos los módulos</option>
                        @for($i = 1; $i <= 16; $i++)
                            <option value="{{ $i }}" {{ request('modulo') == $i ? 'selected' : '' }}>Módulo {{ $i }}</option>
                        @endfor
                    </select>
                    <select name="estado" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos los estados</option>
                        @php
                            $estados = ['Buen estado', 'Desgaste moderado', 'Desgaste severo', 'Dañado - Requiere cambio', 'Cambiado'];
                        @endphp
                        @foreach($estados as $estado)
                            <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Filtrar
                    </button>
                </form>
                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.index', ['linea_id' => request('linea_id', 'todas')]) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Limpiar
                </a>
            </div>
        </div>
    </div>
    
    {{-- ESTADÍSTICAS --}}
    @if($analisisCollection->count() > 0)
        @php
            $registrosPorEstado = [
                'total' => $analisisCollection,
                'buen_estado' => $analisisCollection->where('estado', 'Buen estado'),
                'desgaste' => $analisisCollection->whereIn('estado', ['Desgaste moderado', 'Desgaste severo']),
                'danado' => $analisisCollection->where('estado', 'Dañado - Requiere cambio'),
                'cambiado' => $analisisCollection->where('estado', 'Cambiado'),
            ];
            
            $estadisticas = [
                'total' => $analisisCollection->count(),
                'buen_estado' => $registrosPorEstado['buen_estado']->count(),
                'desgaste' => $registrosPorEstado['desgaste']->count(),
                'danado' => $registrosPorEstado['danado']->count(),
                'cambiado' => $registrosPorEstado['cambiado']->count(),
            ];
        @endphp
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            {{-- TOTAL --}}
            <div onclick="openEstadoModal('total', {{ json_encode($registrosPorEstado['total']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})" 
                class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 cursor-pointer transition-all duration-300 hover:shadow-md hover:border-gray-300">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600">Total análisis</span>
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-bold text-gray-900">{{ $estadisticas['total'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">registros totales</div>
                </div>
            </div>
            
            {{-- BUEN ESTADO --}}
            <div onclick="openEstadoModal('buen_estado', {{ json_encode($registrosPorEstado['buen_estado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="bg-white rounded-xl p-5 shadow-sm border border-green-200 cursor-pointer transition-all duration-300 hover:shadow-md hover:border-green-300">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-green-700">Buen estado</span>
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-bold text-green-700">{{ $estadisticas['buen_estado'] }}</div>
                    <div class="text-xs text-green-600 mt-1">en óptimas condiciones</div>
                </div>
            </div>
            
            {{-- DESGASTE --}}
            <div onclick="openEstadoModal('desgaste', {{ json_encode($registrosPorEstado['desgaste']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="bg-white rounded-xl p-5 shadow-sm border border-yellow-200 cursor-pointer transition-all duration-300 hover:shadow-md hover:border-yellow-300">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-yellow-700">Desgaste</span>
                    <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-bold text-yellow-700">{{ $estadisticas['desgaste'] }}</div>
                    <div class="text-xs text-yellow-600 mt-1">requieren monitoreo</div>
                </div>
            </div>
            
            {{-- DAÑADO --}}
            <div onclick="openEstadoModal('danado', {{ json_encode($registrosPorEstado['danado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="bg-white rounded-xl p-5 shadow-sm border border-red-200 cursor-pointer transition-all duration-300 hover:shadow-md hover:border-red-300">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-red-700">Dañado</span>
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-bold text-red-700">{{ $estadisticas['danado'] }}</div>
                    <div class="text-xs text-red-600 mt-1">requieren cambio urgente</div>
                </div>
            </div>
            
            {{-- CAMBIADO --}}
            <div onclick="openEstadoModal('cambiado', {{ json_encode($registrosPorEstado['cambiado']->map(fn($item) => [
                'id' => $item->id,
                'linea' => $item->linea->nombre ?? 'N/A',
                'modulo' => $item->modulo,
                'componente' => $item->componente_nombre,
                'estado' => $item->estado,
                'fecha' => $item->fecha_formateada ?? $item->created_at->format('d/m/Y'),
                'actividad' => Str::limit($item->actividad, 80),
                'lado' => $item->lado,
            ])->values()) }})"
                class="bg-white rounded-xl p-5 shadow-sm border border-blue-200 cursor-pointer transition-all duration-300 hover:shadow-md hover:border-blue-300">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-blue-700">Cambiado</span>
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-bold text-blue-700">{{ $estadisticas['cambiado'] }}</div>
                    <div class="text-xs text-blue-600 mt-1">componentes reemplazados</div>
                </div>
            </div>
        </div>
    @endif
    
    {{-- SECCIÓN PRINCIPAL - TABLA DE ANÁLISIS --}}
    <div class="space-y-6">
        @php
            $lineasToShow = $mostrarTodas ? $lineasFiltradas : collect([$lineaSeleccionada ?? null])->filter();
        @endphp
        
        @foreach($lineasToShow as $linea)
            @php
                if(!$linea) continue;
                
                $nombreLinea = $linea->nombre;
                $componentesLinea = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($nombreLinea);
                $totalModulos = \App\Models\AnalisisPasteurizadora::getModulosPorLinea($nombreLinea);
                $modulosLinea = collect(range(1, $totalModulos));
                
                $analisisLinea = $analisisCollection->filter(fn($item) => $item->linea_id == $linea->id);
                
                $analisisAgrupadosLinea = [];
                foreach ($analisisLinea as $item) {
                    if (!isset($analisisAgrupadosLinea[$item->modulo][$item->componente])) {
                        $analisisAgrupadosLinea[$item->modulo][$item->componente] = collect();
                    }
                    $analisisAgrupadosLinea[$item->modulo][$item->componente]->push($item);
                }
            @endphp
            
            @if(count($componentesLinea) > 0 && $modulosLinea->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">{{ $linea->nombre }}</h3>
                                <p class="text-xs text-gray-300">{{ $totalModulos }} módulos | {{ count($componentesLinea) }} componentes</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="sticky left-0 bg-gray-50 px-4 py-3 text-left font-semibold text-gray-900 border-r border-gray-200 min-w-[100px]">
                                        Módulo
                                    </th>
                                    @foreach($componentesLinea as $codigo => $compData)
                                        <th class="px-4 py-3 text-left font-semibold text-gray-900 min-w-[200px]">
                                            <div class="flex flex-col items-center text-center gap-2">
                                                <div class="text-sm font-semibold text-gray-900">
                                                    {{ $compData['nombre'] }}
                                                </div>
                                                <img
                                                    src="{{ asset('images/componentes-pasteurizadora/' . $codigo . '.png') }}"
                                                    alt="Icono {{ $compData['nombre'] }}"
                                                    class="w-20 h-20 object-contain hover:scale-110 transition-transform"
                                                    onerror="this.src='{{ asset('images/icono-pasteurizadora.png') }}'">
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modulosLinea as $moduloNumero)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="sticky left-0 bg-white px-4 py-3 font-medium text-gray-900 border-r border-gray-200">
                                            Módulo {{ $moduloNumero }}
                                        </td>
                                        @foreach($componentesLinea as $codigo => $compData)
                                            @php
                                                $registros = $analisisAgrupadosLinea[$moduloNumero][$codigo] ?? collect();
                                                $registro = $registros->sortByDesc('fecha_analisis')->first();
                                                $hasData = $registro !== null;
                                                
                                                $bgColor = 'bg-white';
                                                $borderColor = '';
                                                $estadoActual = '';
                                                
                                                if($hasData){
                                                    $estadoActual = $registro->estado ?? 'Buen estado';
                                                    if ($estadoActual === 'Cambiado') {
                                                        $bgColor = 'bg-blue-50';
                                                        $borderColor = 'border-l-4 border-blue-500';
                                                    } elseif ($estadoActual === 'Dañado - Requiere cambio') {
                                                        $bgColor = 'bg-red-50';
                                                        $borderColor = 'border-l-4 border-red-500';
                                                    } elseif (str_contains($estadoActual, 'Desgaste')) {
                                                        $bgColor = 'bg-yellow-50';
                                                        $borderColor = 'border-l-4 border-yellow-500';
                                                    } else {
                                                        $bgColor = 'bg-green-50';
                                                        $borderColor = 'border-l-4 border-green-500';
                                                    }
                                                }
                                            @endphp
                                            
                                            <td class="px-4 py-3 align-top {{ $bgColor }} {{ $borderColor }} cursor-pointer hover:shadow-md transition-all"
                                                @if($hasData)
                                                    onclick="openAnalysisDetail({{ json_encode([
                                                        'id' => $registro->id,
                                                        'linea' => $linea->nombre,
                                                        'modulo' => $moduloNumero,
                                                        'componente' => $compData['nombre'],
                                                        'lado' => $registro->lado,
                                                        'nivel' => $registro->nivel,
                                                        'fecha_analisis' => $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : $registro->created_at->format('d/m/Y'),
                                                        'numero_orden' => $registro->numero_orden,
                                                        'estado' => $estadoActual,
                                                        'actividad' => $registro->actividad,
                                                        'imagenes' => $registro->evidencia_fotos ?? [],
                                                        'componentes_revisados' => $registro->componentes_revisados ?? [],
                                                        'total_piezas' => $registro->total_piezas,
                                                        'edit_url' => route('pasteurizadora.analisis-pasteurizadora.edit', $registro->id),
                                                        'historial_url' => route('pasteurizadora.analisis-pasteurizadora.historial', ['linea_id' => $linea->id, 'modulo' => $moduloNumero, 'componente' => $codigo])
                                                    ]) }})"
                                                @endif>
                                                @if($hasData)
                                                    <div class="space-y-2">
                                                        <div class="flex items-center justify-between text-xs text-gray-600">
                                                            <span class="flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                </svg>
                                                                {{ $registro->fecha_analisis ? $registro->fecha_analisis->format('d/m/Y') : $registro->created_at->format('d/m/Y') }}
                                                            </span>
                                                            <span class="flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                                                </svg>
                                                                #{{ $registro->numero_orden }}
                                                            </span>
                                                        </div>
                                                        
                                                        @if($registro->lado)
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs {{ $registro->lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                                </svg>
                                                                {{ $registro->lado }}
                                                            </span>
                                                        @endif
                                                        
                                                        <div>
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                                                                @if($estadoActual == 'Buen estado') bg-green-100 text-green-700
                                                                @elseif(str_contains($estadoActual, 'Desgaste')) bg-yellow-100 text-yellow-700
                                                                @elseif($estadoActual == 'Dañado - Requiere cambio') bg-red-100 text-red-700
                                                                @else bg-blue-100 text-blue-700
                                                                @endif">
                                                                @if($estadoActual == 'Buen estado')
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                                    </svg>
                                                                @elseif(str_contains($estadoActual, 'Desgaste'))
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                                    </svg>
                                                                @elseif($estadoActual == 'Dañado - Requiere cambio')
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                                    </svg>
                                                                @endif
                                                                {{ $estadoActual }}
                                                            </span>
                                                        </div>
                                                        
                                                        {{-- Mostrar componentes revisados si existen --}}
                                                        @if($registro->componentes_revisados && count($registro->componentes_revisados) > 0)
                                                            <div class="bg-indigo-50 rounded-lg p-2 mt-2">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-xs font-medium text-indigo-700">Revisadas:</span>
                                                                    <span class="text-xs text-indigo-600 font-semibold">{{ count($registro->componentes_revisados) }}/{{ $registro->total_piezas }}</span>
                                                                </div>
                                                                <div class="flex flex-wrap gap-1 mt-1">
                                                                    @foreach($registro->componentes_revisados as $num)
                                                                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-medium">
                                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                                            </svg>
                                                                            #{{ $num }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        <p class="text-xs text-gray-600 line-clamp-2">{{ Str::limit($registro->actividad, 60) }}</p>
                                                        
                                                        <div class="flex gap-2 pt-1">
                                                            @if(count($registro->evidencia_fotos ?? []) > 0)
                                                                <button onclick="event.stopPropagation(); openAllImages({{ json_encode($registro->evidencia_fotos) }}, '{{ $registro->numero_orden }}')" 
                                                                        class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs transition">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                    </svg>
                                                                    {{ count($registro->evidencia_fotos) }}
                                                                </button>
                                                            @endif
                                                            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.create-quick', ['linea_id' => $linea->id, 'modulo' => $moduloNumero, 'componente' => $codigo]) }}"
                                                               class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs transition"
                                                               onclick="event.stopPropagation();">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                                </svg>
                                                                Nuevo
                                                            </a>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-center py-4">
                                                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        <p class="text-xs text-gray-400 mb-2">Sin análisis registrado</p>
                                                        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.create-quick', ['linea_id' => $linea->id, 'modulo' => $moduloNumero, 'componente' => $codigo]) }}"
                                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs transition"
                                                           onclick="event.stopPropagation();">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                            </svg>
                                                            Crear análisis
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
            @endif
        @endforeach
    </div>
</div>

{{-- MODAL DE DETALLE DE ANÁLISIS --}}
<div id="analysisDetailModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closeAnalysisDetailModal()">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Detalle del Análisis</h3>
            <button onclick="closeAnalysisDetailModal()" class="w-8 h-8 rounded-lg hover:bg-gray-200 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-100px)]" id="detailModalContent">
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Cargando...</p>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE ESTADÍSTICAS --}}
<div id="estadoModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closeEstadoModal()">
    <div class="bg-white rounded-xl shadow-xl max-w-5xl w-full max-h-[85vh] overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center" id="estadoModalHeader">
            <h3 class="text-xl font-bold" id="estadoModalTitle">Detalle de registros</h3>
            <button onclick="closeEstadoModal()" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(85vh-80px)]" id="estadoModalContent">
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Cargando...</p>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE IMÁGENES --}}
<div id="allImagesModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closeAllImagesModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-lg">Galería de Imágenes</h3>
            <button onclick="closeAllImagesModal()" class="w-8 h-8 rounded-lg hover:bg-gray-700 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-80px)]">
            <div id="imageGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
            <div id="emptyImages" class="hidden text-center py-16">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-500">No hay imágenes disponibles</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentAnalysisData = null;
let currentEstadoData = [];

function openEstadoModal(tipo, registros) {
    currentEstadoData = registros;
    const modal = document.getElementById('estadoModal');
    const title = document.getElementById('estadoModalTitle');
    const header = document.getElementById('estadoModalHeader');
    const content = document.getElementById('estadoModalContent');
    
    let bgColor = '', textColor = '', icono = '';
    switch(tipo) {
        case 'total':
            bgColor = 'bg-gray-100';
            textColor = 'text-gray-800';
            icono = '📊';
            title.innerHTML = `${icono} Todos los registros (${registros.length})`;
            break;
        case 'buen_estado':
            bgColor = 'bg-green-100';
            textColor = 'text-green-800';
            icono = '✅';
            title.innerHTML = `${icono} Registros en Buen Estado (${registros.length})`;
            break;
        case 'desgaste':
            bgColor = 'bg-yellow-100';
            textColor = 'text-yellow-800';
            icono = '⚠️';
            title.innerHTML = `${icono} Registros con Desgaste (${registros.length})`;
            break;
        case 'danado':
            bgColor = 'bg-red-100';
            textColor = 'text-red-800';
            icono = '❌';
            title.innerHTML = `${icono} Registros Dañados (${registros.length})`;
            break;
        case 'cambiado':
            bgColor = 'bg-blue-100';
            textColor = 'text-blue-800';
            icono = '🔄';
            title.innerHTML = `${icono} Registros Cambiados (${registros.length})`;
            break;
        default:
            title.innerHTML = `Registros (${registros.length})`;
    }
    
    header.className = `px-6 py-4 border-b flex justify-between items-center ${bgColor}`;
    title.className = `text-xl font-bold ${textColor}`;
    
    if (registros.length === 0) {
        content.innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-gray-500">No hay registros en esta categoría</p>
            </div>
        `;
    } else {
        const agrupadosPorLinea = {};
        registros.forEach(reg => {
            if (!agrupadosPorLinea[reg.linea]) {
                agrupadosPorLinea[reg.linea] = [];
            }
            agrupadosPorLinea[reg.linea].push(reg);
        });
        
        let html = '';
        for (const [linea, items] of Object.entries(agrupadosPorLinea)) {
            html += `
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-200">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        <h4 class="font-bold text-lg text-gray-800">${linea}</h4>
                        <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">${items.length} registros</span>
                    </div>
                    <div class="space-y-3">
            `;
            
            items.forEach(reg => {
                let estadoClass = '';
                let estadoIcon = '';
                let estadoColor = '';
                if (reg.estado === 'Buen estado') {
                    estadoClass = 'border-l-green-500';
                    estadoIcon = '✅';
                    estadoColor = 'bg-green-50';
                } else if (reg.estado.includes('Desgaste')) {
                    estadoClass = 'border-l-yellow-500';
                    estadoIcon = '⚠️';
                    estadoColor = 'bg-yellow-50';
                } else if (reg.estado === 'Dañado - Requiere cambio') {
                    estadoClass = 'border-l-red-500';
                    estadoIcon = '❌';
                    estadoColor = 'bg-red-50';
                } else if (reg.estado === 'Cambiado') {
                    estadoClass = 'border-l-blue-500';
                    estadoIcon = '🔄';
                    estadoColor = 'bg-blue-50';
                }
                
                html += `
                    <div class="${estadoColor} border-l-4 ${estadoClass} p-4 rounded-lg hover:shadow-md transition-all cursor-pointer" onclick="cerrarEstadoModalYVerAnalisis(${reg.id})">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap mb-2">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">Módulo ${reg.modulo}</span>
                                    <span class="font-semibold text-gray-800">${reg.componente}</span>
                                    ${reg.lado ? `<span class="text-xs px-2 py-1 rounded ${reg.lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">${reg.lado === 'VAPOR' ? '💨 Vapor' : '🚶 Pasillo'}</span>` : ''}
                                    <span class="text-xs text-gray-500">📅 ${reg.fecha}</span>
                                </div>
                                <p class="text-sm text-gray-600">${reg.actividad}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-1 rounded-full font-medium
                                    ${reg.estado === 'Buen estado' ? 'bg-green-100 text-green-700' : 
                                      (reg.estado.includes('Desgaste') ? 'bg-yellow-100 text-yellow-700' : 
                                      (reg.estado === 'Dañado - Requiere cambio' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'))}">
                                    ${estadoIcon} ${reg.estado}
                                </span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `</div></div>`;
        }
        
        content.innerHTML = html;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeEstadoModal() {
    const modal = document.getElementById('estadoModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function cerrarEstadoModalYVerAnalisis(id) {
    closeEstadoModal();
    const registro = currentEstadoData.find(r => r.id === id);
    if (registro) {
        window.location.href = `/analisis-pasteurizadora/${id}`;
    }
}

function openAnalysisDetail(data) {
    currentAnalysisData = data;
    const modal = document.getElementById('analysisDetailModal');
    const content = document.getElementById('detailModalContent');
    
    let estadoClass = '';
    let estadoIcon = '';
    if (data.estado === 'Buen estado') {
        estadoClass = 'bg-green-100 text-green-700';
        estadoIcon = '✅';
    } else if (data.estado.includes('Desgaste')) {
        estadoClass = 'bg-yellow-100 text-yellow-700';
        estadoIcon = '⚠️';
    } else if (data.estado === 'Dañado - Requiere cambio') {
        estadoClass = 'bg-red-100 text-red-700';
        estadoIcon = '❌';
    } else if (data.estado === 'Cambiado') {
        estadoClass = 'bg-blue-100 text-blue-700';
        estadoIcon = '🔄';
    }
    
    let componentesRevisadosHtml = '';
    if (data.componentes_revisados && data.componentes_revisados.length > 0) {
        const totalComponentes = data.total_piezas || data.componentes_revisados.length;
        componentesRevisadosHtml = `
            <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-lg mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-indigo-900 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Componentes revisados
                    </h4>
                    <span class="text-sm font-bold text-indigo-700">${data.componentes_revisados.length} de ${totalComponentes}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    ${data.componentes_revisados.map(num => `
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            #${num}
                        </span>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    let imagenesHtml = '';
    if (data.imagenes && data.imagenes.length > 0) {
        imagenesHtml = `
            <div class="mt-6">
                <h4 class="font-semibold text-gray-700 mb-3">📸 Evidencia Fotográfica</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    ${data.imagenes.map((img, idx) => `
                        <div class="relative group cursor-pointer" onclick="openSingleImage('${img}')">
                            <img src="/storage/${img}" class="w-full h-32 object-cover rounded-lg border-2 border-gray-200 hover:border-blue-500 transition">
                            <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">${idx + 1}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    content.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">🏭 Línea</p>
                <p class="font-bold text-gray-900">${data.linea}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">🔧 Módulo</p>
                <p class="font-bold text-gray-900">Módulo ${data.modulo}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">📏 Nivel</p>
                <p class="font-bold text-gray-900">${data.nivel ? (data.nivel === 'SUPERIOR' ? '⬆️ SUPERIOR' : data.nivel === 'INFERIOR' ? '⬇️ INFERIOR' : data.nivel) : 'No asignado'}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">⚙️ Componente</p>
                <p class="font-bold text-gray-900">${data.componente}</p>
            </div>
            ${data.lado ? `
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">📍 Lado</p>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-sm ${data.lado === 'VAPOR' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">
                    ${data.lado === 'VAPOR' ? '💨' : '🚶'} ${data.lado}
                </span>
            </div>
            ` : ''}
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">📅 Fecha</p>
                <p class="font-bold text-gray-900">${data.fecha_analisis}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">🔢 Orden</p>
                <p class="font-bold font-mono text-gray-900">#${data.numero_orden}</p>
            </div>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <p class="text-xs text-gray-500 mb-2">📊 Estado</p>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg ${estadoClass}">
                ${estadoIcon} ${data.estado}
            </span>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <p class="text-xs text-gray-500 mb-2">📝 Actividad Realizada</p>
            <p class="text-gray-700 whitespace-pre-line">${data.actividad || 'No especificada'}</p>
        </div>
        
        ${componentesRevisadosHtml}
        
        ${imagenesHtml}
        
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="${data.edit_url}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                ✏️ Editar
            </a>
            <a href="${data.historial_url}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                📜 Ver Historial
            </a>
            <button onclick="closeAnalysisDetailModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                Cerrar
            </button>
        </div>
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeAnalysisDetailModal() {
    document.getElementById('analysisDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
}

let currentImages = [];

function openAllImages(imagenes, orden) {
    currentImages = imagenes;
    const modal = document.getElementById('allImagesModal');
    const grid = document.getElementById('imageGrid');
    const empty = document.getElementById('emptyImages');
    
    grid.innerHTML = '';
    
    if (currentImages.length === 0) {
        grid.classList.add('hidden');
        empty.classList.remove('hidden');
    } else {
        grid.classList.remove('hidden');
        empty.classList.add('hidden');
        
        currentImages.forEach((path, index) => {
            const item = document.createElement('div');
            item.className = 'relative group cursor-pointer';
            item.innerHTML = `
                <img src="/storage/${path}" class="w-full h-40 object-cover rounded-lg border-2 border-gray-200 hover:border-blue-500 transition" onclick="openSingleImage('${path}')">
                <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">${index + 1}</div>
                <button onclick="event.stopPropagation(); downloadImage('${path}', ${index + 1})" class="absolute top-2 right-2 bg-blue-600 text-white p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
            `;
            grid.appendChild(item);
        });
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeAllImagesModal() {
    document.getElementById('allImagesModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function openSingleImage(path) {
    window.open(`/storage/${path}`, '_blank');
}

function downloadImage(path, index) {
    const link = document.createElement('a');
    link.href = `/storage/${path}`;
    link.download = `imagen-${index}.jpg`;
    link.click();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAnalysisDetailModal();
        closeAllImagesModal();
        closeEstadoModal();
    }
});
</script>
@endsection