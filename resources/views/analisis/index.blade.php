@extends('layouts.app')

@section('title', 'Análisis de Lavadoras')

@section('content')
<div class="max-w-[95%] mx-auto py-6 space-y-6">

    {{-- ================= ENCABEZADO PRINCIPAL ================= --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                ANÁLISIS TÉCNICO DE LAVADORAS
            </h1>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-industry mr-1 text-xs"></i>
                    VISIÓN INDUSTRIAL
                </span>
                <span class="text-sm text-gray-500">
                    Sistema de monitoreo técnico - Legado AB Fénix
                </span>
            </div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('analisis.exportar.excel', request()->query()) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm transition-all duration-200 font-medium">
                <i class="fas fa-file-excel"></i>
                <span class="hidden sm:inline">Exportar Filtradas</span>
                <span class="sm:hidden">Excel</span>
            </a>
            <a href="{{ route('analisis.exportar.excel') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white rounded-lg shadow-sm transition-all duration-200 font-medium">
                <i class="fas fa-download"></i>
                <span class="hidden sm:inline">Exportar Todas</span>
                <span class="sm:hidden">Todas</span>
            </a>
        </div>
    </div>

    {{-- ================= PANEL DE FILTROS ================= --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-gray-50 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600">
                    <i class="fas fa-sliders-h text-sm"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">PARÁMETROS DE FILTRADO</h3>
                    <p class="text-xs text-gray-500">Seleccione los criterios para el análisis técnico</p>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('analisis.index') }}" 
              class="p-6 grid grid-cols-1 md:grid-cols-6 gap-5">
            
            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-washer text-blue-500 mr-1 text-xs"></i>
                    Lavadora
                </label>
                <select name="linea_id"
                        class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all duration-200">
                    <option value="">Todas las lavadoras</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}"
                            {{ request('linea_id') == $linea->id ? 'selected' : '' }}>
                            {{ strtoupper($linea->nombre) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-cogs text-blue-500 mr-1 text-xs"></i>
                    Componente
                </label>
                <select name="componente_id"
                        class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all duration-200">
                    <option value="">Todos los componentes</option>
                    @foreach($componentes as $componente)
                        <option value="{{ $componente->id }}"
                            {{ request('componente_id') == $componente->id ? 'selected' : '' }}>
                            {{ $componente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-tag text-blue-500 mr-1 text-xs"></i>
                    Categoría
                </label>
                <select name="categoria_id"
                        class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all duration-200">
                    <option value="">Todas</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-filter text-blue-500 mr-1 text-xs"></i>
                    Reductor
                </label>
                <input type="text" name="reductor"
                       value="{{ request('reductor') }}"
                       placeholder="Ej: R1, R9, R12"
                       class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 shadow-sm transition-all duration-200">
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-blue-500 mr-1 text-xs"></i>
                    Período
                </label>
                <input type="month" name="fecha"
                       value="{{ request('fecha') }}"
                       class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all duration-200">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm transition-all duration-200 font-semibold">
                    <i class="fas fa-search"></i>
                    APLICAR FILTROS
                </button>
                <a href="{{ route('analisis.index') }}"
                   class="inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg shadow-sm transition-all duration-200 font-semibold">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- ================= TABLA DE ANÁLISIS ================= --}}
    @if(count($analisisAgrupados) > 0)
        @foreach($analisisAgrupados as $lavadora => $items)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                {{-- Encabezado de Lavadora --}}
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-3 px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-500/20">
                                <i class="fas fa-industry text-lg"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-lg tracking-wide">LAVADORA {{ $lavadora }}</h2>
                                <p class="text-xs text-gray-300">{{ $items->count() }} registros técnicos</p>
                            </div>
                        </div>
                        <div class="text-xs font-medium bg-blue-500/20 px-3 py-1 rounded-full">
                            {{ $items->groupBy('reductor')->count() }} REDUCTORES
                        </div>
                    </div>
                </div>

                {{-- Tabla de Datos --}}
                <div class="overflow-auto max-h-[75vh] border-t border-gray-200">
                    <table class="min-w-full border-collapse">
                        <thead class="sticky top-0 z-30">
                            <tr class="bg-gradient-to-r from-blue-50 to-gray-50">
                                <th class="sticky left-0 z-40 px-4 py-3 text-left border-b border-gray-300 min-w-[140px] bg-blue-50">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-filter text-blue-600 text-xs"></i>
                                        <span class="font-semibold text-gray-700 text-sm">REDUCTOR</span>
                                    </div>
                                </th>
                                @foreach($componentes as $componente)
                                    <th class="px-4 py-3 text-left border-b border-gray-300 min-w-[240px] bg-gray-50">
                                        <div class="space-y-1">
                                            <div class="font-semibold text-gray-700 text-sm uppercase tracking-wide">
                                                {{ $componente->nombre }}
                                            </div>
                                            <div class="text-xs text-gray-500 font-normal">
                                                ID: {{ $componente->id }}
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @foreach($items->groupBy('reductor') as $reductor => $registros)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="sticky left-0 z-20 px-4 py-3 bg-white font-semibold text-gray-900 border-r border-gray-200">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center justify-center w-6 h-6 rounded bg-gray-100 text-gray-600 text-xs">
                                                R
                                            </div>
                                            <span class="font-mono">{{ $reductor }}</span>
                                        </div>
                                    </td>

                                    @foreach($componentes as $componente)
                                        @php
                                            $analisis = $registros->firstWhere('componente_id', $componente->id);
                                            
                                            // Determinar estilo basado en actividad
                                            if ($analisis) {
                                                $actividad = strtolower($analisis->actividad);
                                                if (str_contains($actividad, 'cambio') || str_contains($actividad, 'cambiar') || str_contains($actividad, 'replace')) {
                                                    $bg = 'bg-red-50';
                                                    $border = 'border-l-4 border-l-red-500';
                                                    $icon = 'fas fa-exchange-alt text-red-500';
                                                    $estado = 'CAMBIO';
                                                } elseif (str_contains($actividad, 'repar') || str_contains($actividad, 'manten') || str_contains($actividad, 'repair')) {
                                                    $bg = 'bg-blue-50';
                                                    $border = 'border-l-4 border-l-blue-500';
                                                    $icon = 'fas fa-tools text-blue-500';
                                                    $estado = 'REPARACIÓN';
                                                } elseif (str_contains($actividad, 'revis') || str_contains($actividad, 'inspec') || str_contains($actividad, 'check')) {
                                                    $bg = 'bg-yellow-50';
                                                    $border = 'border-l-4 border-l-yellow-500';
                                                    $icon = 'fas fa-search text-yellow-500';
                                                    $estado = 'REVISIÓN';
                                                } elseif (str_contains($actividad, 'lubri') || str_contains($actividad, 'grease')) {
                                                    $bg = 'bg-indigo-50';
                                                    $border = 'border-l-4 border-l-indigo-500';
                                                    $icon = 'fas fa-oil-can text-indigo-500';
                                                    $estado = 'LUBRICACIÓN';
                                                } else {
                                                    $bg = 'bg-green-50';
                                                    $border = 'border-l-4 border-l-green-500';
                                                    $icon = 'fas fa-check-circle text-green-500';
                                                    $estado = 'OK';
                                                }
                                            } else {
                                                $bg = 'bg-gray-50';
                                                $border = 'border-l-4 border-l-gray-300';
                                                $icon = 'fas fa-minus text-gray-400';
                                                $estado = 'SIN REGISTRO';
                                            }
                                        @endphp

                                        <td class="px-4 py-3 align-top {{ $bg }} {{ $border }}">
                                            @if($analisis)
                                                <div class="space-y-2 text-sm">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <i class="{{ $icon }} text-sm"></i>
                                                            <span class="font-medium text-gray-900">
                                                                OT: {{ $analisis->numero_orden }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-medium px-2 py-1 rounded bg-white text-gray-600 shadow-sm">
                                                                {{ $estado }}
                                                            </span>
                                                            <span class="text-xs font-medium px-2 py-1 rounded bg-white text-gray-600 shadow-sm">
                                                                {{ $analisis->fecha_analisis->format('d/m/Y') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="bg-white rounded p-3 border border-gray-200">
                                                        <div class="text-xs text-gray-500 mb-1">ACTIVIDAD REGISTRADA:</div>
                                                        <p class="text-gray-800 leading-relaxed">
                                                            {{ ucfirst($analisis->actividad) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex flex-col items-center justify-center h-full py-6 text-gray-400">
                                                    <i class="fas fa-ban text-2xl mb-2"></i>
                                                    <span class="text-sm font-medium">{{ $estado }}</span>
                                                    <span class="text-xs mt-1">No hay datos técnicos</span>
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
    @else
        {{-- Mejorar la condición para detectar cuando realmente se aplicaron filtros --}}
        @php
            $filtrosAplicados = request()->hasAny(['linea_id', 'componente_id', 'reductor', 'fecha', 'categoria_id']) && 
                               (request('linea_id') || request('componente_id') || request('reductor') || 
                                request('fecha') || request('categoria_id'));
        @endphp
        
        @if($filtrosAplicados)
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-6 text-center">
                <div class="flex flex-col items-center justify-center space-y-4">
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-database text-2xl"></i>
                    </div>
                    <div class="space-y-2">
                        <h3 class="font-bold text-gray-800 text-lg">NO SE ENCONTRARON REGISTROS</h3>
                        <p class="text-gray-600 max-w-md mx-auto">
                            No hay análisis técnicos disponibles con los filtros seleccionados. 
                            Intente ajustar los parámetros de búsqueda.
                        </p>
                    </div>
                    <a href="{{ route('analisis.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-medium">
                        <i class="fas fa-redo"></i>
                        Restablecer Filtros
                    </a>
                </div>
            </div>
        @else
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-8 text-center">
                <div class="flex flex-col items-center justify-center space-y-4">
                    <div class="flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-filter text-3xl"></i>
                    </div>
                    <div class="space-y-2">
                        <h3 class="font-bold text-gray-800 text-xl">SELECCIONE FILTROS PARA COMENZAR</h3>
                        <p class="text-gray-600 max-w-lg mx-auto">
                            Utilice los filtros superiores para definir los criterios de búsqueda 
                            y visualizar el análisis técnico de las lavadoras.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif

</div>
@endsection